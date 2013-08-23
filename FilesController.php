<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2012, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Controller for the OntoWiki files extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_files
 * @author   Christoph Rieß <c.riess.dev@googlemail.com>
 * @author   Norman Heino <norman.heino@gmail.com>
 * @author   {@link http://sebastian.tramp.name Sebastian Tramp}
 */
class FilesController extends OntoWiki_Controller_Component
{
    protected $_configModel;

    /**
     * Default action. Forwards to get action.
     */
    public function __call($action, $params)
    {
        $this->_forward('get', 'files');
    }

    /**
     * deletes a file resource from the disk as well as from the config model
     * but NOT from the user-model
     */
    private function _deleteFile($fileResource)
    {
        $store = $this->_owApp->erfurt->getStore();

        // remove file from file system (silently)
        $pathHashed = $this->getFullPath($fileResource);
        if (is_readable($pathHashed)) {
            unlink($pathHashed);
        }

        // remove all statements from sysconfig
        $store->deleteMatchingStatements(
            (string)$this->_getConfigModelUri(),
            $fileResource,
            null,
            null
        );
    }

    /**
     * action to delete a file resource either via post (multiple) or via get
     * (setResource parameter
     */
    public function deleteAction()
    {
        // delete file resources via Post array
        if ($this->_request->isPost()) {
            foreach ($this->_request->getPost('selectedFiles') as $fileUri) {
                $fileUri = rawurldecode($fileUri);
                $this->_deleteFile($fileUri);
            }

            $url = new OntoWiki_Url(array('controller' => 'files', 'action' => 'manage'), array());
            $this->_redirect((string)$url);
        } else if (isset($this->_request->setResource)) {
            // delete a resource via get setResource parameter
            $fileUri = rawurldecode($this->_request->setResource);
            $this->_deleteFile($this->_request->setResource);
            $this->_owApp->appendMessage(
                new OntoWiki_Message('File attachment deleted', OntoWiki_Message::SUCCESS)
            );
            $resourceUri = new OntoWiki_Url(array('route' => 'properties'), array('r'));
            $resourceUri->setParam('r', $this->_request->setResource, true);
            $this->_redirect((string)$resourceUri);
        } else {
            // action just requested without anything
            $this->_forward('manage', 'files');
        }
    }

    /**
     * get / download a file resource
     */
    public function getAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        // TODO: check acl
        if (isset($this->_request->setResource)) {
            $fileUri = $this->_request->setResource;
        } else {
            $fileUri = $this->_config->urlBase . ltrim($this->_request->getPathInfo(), '/');
        }
        $mimeProperty = $this->_privateConfig->mime->property;
        $store        = $this->_owApp->erfurt->getStore();

        $query = new Erfurt_Sparql_SimpleQuery();
        $query->setProloguePart('SELECT DISTINCT ?mime_type')
            ->addFrom((string)$this->_getConfigModelUri())
            ->setWherePart('WHERE {<' . $fileUri . '> <' . $mimeProperty . '> ?mime_type. }');

        if ($result = $store->sparqlQuery($query, array('use_ac' => false))) {
            $mimeType = $result[0]['mime_type'];
        } else {
            // we set the default download file type to
            // application/octet-stream
            $mimeType = 'application/octet-stream';
        }

        // TODO: generate a proper file name here
        $response = $this->getResponse();
        $response->setRawHeader('Content-Type:' . $mimeType);
        $pathHashed = $this->getFullPath($fileUri);
        if (is_readable($pathHashed)) {
            $response->setBody(file_get_contents($pathHashed));
        }
    }

    /**
     * manage file resources (main GUI)
     */
    public function manageAction()
    {
        $mimeProperty = $this->_privateConfig->mime->property;
        $fileClass    = $this->_privateConfig->class;
        $fileModel    = $this->_privateConfig->model;
        $store        = $this->_owApp->erfurt->getStore();

        $query = new Erfurt_Sparql_SimpleQuery();
        $query->setProloguePart('SELECT DISTINCT ?mime_type ?uri')
            ->addFrom((string)$this->_getConfigModelUri())
            ->setWherePart(
                'WHERE
                {
                    ?uri a <' . $fileClass . '>.
                    ?uri <' . $fileModel . '> <' . (string)$this->_owApp->selectedModel . '>.
                    ?uri <' . $mimeProperty . '> ?mime_type.
                }'
            )
            ->setOrderClause('?uri')
            ->setLimit(10); // TODO: paging

        if ($result = $store->sparqlQuery($query, array('use_ac' => false))) {
            $files = array();
            foreach ($result as $row) {
                if (is_readable($this->getFullPath($row['uri']))) {
                    array_push($files, $row);
                }
            }
            $this->view->files = $files;
        } else {
            $this->view->files = array();
        }

        $this->view->placeholder('main.window.title')->set($this->_owApp->translate->_('File Manager'));
        OntoWiki::getInstance()->getNavigation()->disableNavigation();

        $toolbar = $this->_owApp->toolbar;

        $filePath = _OWROOT
                  . rtrim($this->_privateConfig->path, '/')
                  . DIRECTORY_SEPARATOR;

        $url = new OntoWiki_Url(array('controller' => 'files', 'action' => 'upload'), array());

        if (is_writable($filePath)) {

            $toolbar->appendButton(
                OntoWiki_Toolbar::DELETE,
                array('name' => 'Delete Files', 'class' => 'submit actionid', 'id' => 'filemanagement-delete')
            );

            $toolbar->appendButton(
                OntoWiki_Toolbar::ADD,
                array('name' => 'Upload File', 'class' => 'upload-file', 'url' => (string)$url)
            );

            $this->view->placeholder('main.window.toolbar')->set($toolbar);
        } else {
            $msgString = sprintf(
                $this->_owApp->translate->_('Directory "%s" is not writeable. To upload files set it writable.'),
                rtrim($this->_privateConfig->path, '/') . DIRECTORY_SEPARATOR
            );
            $this->_owApp->appendMessage(
                new OntoWiki_Message($msgString, OntoWiki_Message::INFO)
            );
        }

        if (!defined('ONTOWIKI_REWRITE')) {
            $this->_owApp->appendMessage(
                new OntoWiki_Message('Rewrite mode is off. File URIs may not be accessible.', OntoWiki_Message::WARNING)
            );
            return;
        }

        $url->action = 'delete';
        $this->view->formActionUrl = (string)$url;
        $this->view->formMethod    = 'post';
        $this->view->formClass     = 'simple-input input-justify-left';
        $this->view->formName      = 'filemanagement-delete';
    }

    /**
     * upload a file resource via POST or get upload GUI
     */
    public function uploadAction()
    {
        // default file URI
        $defaultUri = $this->_config->urlBase . 'files/';

        // store for sparql queries
        $store        = $this->_owApp->erfurt->getStore();

        // DMS NS var
        $dmsNs = $this->_privateConfig->DMS_NS;

        // check if DMS needs to be imported
        if ($store->isModelAvailable($dmsNs) && $this->_privateConfig->import_DMS) {
            $this->_checkDMS();
        }

        $url = new OntoWiki_Url(
            array('controller' => 'files', 'action' => 'upload'),
            array()
        );

        // check for POST'ed data
        if ($this->_request->isPost()) {
            $event           = new Erfurt_Event('onFilesExtensionUploadFile');
            $event->request  = $this->_request;
            $event->defaultUri = $defaultUri;
            // process upload in plugin
            $eventResult = $event->trigger();
            if ($eventResult === true) {
                if (isset($this->_request->setResource)) {
                    $this->_owApp->appendMessage(
                        new OntoWiki_Message('File attachment added', OntoWiki_Message::SUCCESS)
                    );
                    $resourceUri = new OntoWiki_Url(array('route' => 'properties'), array('r'));
                    $resourceUri->setParam('r', $this->_request->setResource, true);
                    $this->_redirect((string)$resourceUri);
                } else {
                    $url->action = 'manage';
                    $this->_redirect((string)$url);
                }
            }
        }

        $this->view->placeholder('main.window.title')->set($this->_owApp->translate->_('Upload File'));
        OntoWiki::getInstance()->getNavigation()->disableNavigation();

        $toolbar = $this->_owApp->toolbar;
        $url->action = 'manage';
        $toolbar->appendButton(
            OntoWiki_Toolbar::SUBMIT, array('name' => 'Upload File')
        );
        $toolbar->appendButton(
            OntoWiki_Toolbar::EDIT, array('name' => 'File Manager', 'class' => '', 'url' => (string)$url)
        );

        $this->view->defaultUri = $defaultUri;
        $this->view->placeholder('main.window.toolbar')->set($toolbar);

        $url->action = 'upload';
        $this->view->formActionUrl = (string)$url;
        $this->view->formMethod    = 'post';
        $this->view->formClass     = 'simple-input input-justify-left';
        $this->view->formName      = 'fileupload';
        $this->view->formEncoding  = 'multipart/form-data';
        if (isset($this->_request->setResource)) {
            // forward URI to form so we can redirect later
            $this->view->setResource  = $this->_request->setResource;
        }

        if (!is_writable($this->_privateConfig->path)) {
            $this->_owApp->appendMessage(
                new OntoWiki_Message('Uploads folder is not writable.', OntoWiki_Message::WARNING)
            );
            return;
        }

        // FIX: http://www.webmasterworld.com/macintosh_webmaster/3300569.htm
        header('Connection: close');
    }

    /**
     * method to check import of DMS Schema in current model
     */
    private function _checkDMS()
    {
        $store        = $this->_owApp->erfurt->getStore();

        // checking if model is imported
        $allImports = $this->_owApp->selectedModel->sparqlQuery(
            Erfurt_Sparql_SimpleQuery::initWithString(
                'SELECT *
                WHERE {
                    <' . (string)$this->_owApp->selectedModel . '> <' . EF_OWL_IMPORTS . '> ?import .
                }'
            )
        );

        // import if missing
        if (!in_array(array('import' => $this->_privateConfig->DMS_NS), $allImports)) {
            $this->_owApp->selectedModel->addStatement(
                (string)$this->_owApp->selectedModel,
                EF_OWL_IMPORTS,
                array('value' => $this->_privateConfig->DMS_NS, 'type' => 'uri'),
                false
            );
        }
    }

    protected function _getConfigModelUri()
    {
        if (null === $this->_configModel) {
            $this->_configModel = Erfurt_App::getInstance()->getConfig()->sysont->modelUri;
        }

        return $this->_configModel;
    }

    /**
     * return the file path incl. filename for a given resource
     */
    public static function getFullPath($fileResource)
    {
        $extensionManager = OntoWiki::getInstance()->extensionManager;
        $privateConfig    = $extensionManager->getPrivateConfig('files');
        $path             = $privateConfig->path;

        return _OWROOT . $path . DIRECTORY_SEPARATOR . md5($fileResource);
    }

    /**
     * Returns the queried mime type (or application/octet-stream) for a given
     * file resource
     */
    public static function getMimeType($fileResource)
    {
        $owApp            = OntoWiki::getInstance();
        $store            = $owApp->erfurt->getStore();
        $extensionManager = $owApp->extensionManager;
        $configModel      = $owApp->erfurt->getConfig()->sysont->modelUri;
        $privateConfig    = $extensionManager->getPrivateConfig('files');
        $mimeProperty     = $privateConfig->mime->property;

        $query = new Erfurt_Sparql_SimpleQuery();
        $query->setProloguePart('SELECT DISTINCT ?mime_type')
            ->addFrom($configModel)
            ->setWherePart('WHERE {<' . $fileResource . '> <' . $mimeProperty . '> ?mime_type. }');

        if ($result = $store->sparqlQuery($query, array('use_ac' => false))) {
            $mimeType = $result[0]['mime_type'];
        } else {
            // we set the default download file type to
            // application/octet-stream
            $mimeType = 'application/octet-stream';
        }
        return $mimeType;
    }
}

