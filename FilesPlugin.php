<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2013, {@link http://aksw.org AKSW}
 * @license   http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The main class for the files plugin.
 *
 * @category   OntoWiki
 * @package    OntoWiki_Extensions_Files
 * @author     Sebastian Tramp <mail@sebastian.tramp.name>
 */
class FilesPlugin extends OntoWiki_Plugin
{
    private $_owApp;
    private $_request;

    /**
     * Event handler method, which is called on file upload
     *
     * @param Erfurt_Event $event
     *
     * @return bool
     */
    public function onFilesExtensionUploadFile($event)
    {
        $this->_owApp          = OntoWiki::getInstance();
        $store                 = $this->_owApp->erfurt->getStore();
        $this->_request        = $event->request;
        $this->_configModelUri = Erfurt_App::getInstance()->getConfig()->sysont->modelUri;

        $dmsNs = $this->_privateConfig->DMS_NS;
        if (isset($event->defaultUri)) {
            $defaultUri = $event->defaultUri;
        } else {
            $defaultUri = $this->_config->urlBase . 'files/';
        }

        if (!$this->_request->isPost()) {
            $this->_owApp->appendMessage(
                new OntoWiki_Message('Provided request is not a POST.', OntoWiki_Message::ERROR)
            );
            return false;
        }

        if ($_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
            $this->_owApp->appendMessage(
                new OntoWiki_Message('Error during file upload.', OntoWiki_Message::ERROR)
            );
            return false;
        }

        // upload ok, move file
        $fileUri  = $this->_request->getPost('file_uri');
        $fileName = $_FILES['upload']['name'];
        $tmpName  = $_FILES['upload']['tmp_name'];
        $mimeType = $_FILES['upload']['type'];

        // check for unchanged uri
        if ($fileUri == $defaultUri) {
            $fileUri = $defaultUri
                . 'file'
                . (count(scandir(_OWROOT . $this->_privateConfig->path)) - 2);
        }

        // build path
        //require_once 'FilesController.php';
        $pathHashed = FilesController::getFullPath($fileUri);

        // move file
        if (!move_uploaded_file($tmpName, $pathHashed)) {
            $this->_owApp->appendMessage(
                new OntoWiki_Message('Error during file moving.', OntoWiki_Message::ERROR)
            );
            return false;
        }

        $mimeProperty = $this->_privateConfig->mime->property;
        $fileClass    = $this->_privateConfig->class;
        $fileModel    = $this->_privateConfig->model;

        // use super class as default
        $fileClassLocal = 'http://xmlns.com/foaf/0.1/Document';

        // use mediaType-ontologie if available
        if ($store->isModelAvailable($dmsNs)) {
            $allTypes = $store->sparqlQuery(
                Erfurt_Sparql_SimpleQuery::initWithString(
                    'SELECT * FROM <' . $dmsNs . '>
                    WHERE {
                        ?type a <' . EF_OWL_CLASS . '> .
                        OPTIONAL { ?type <' . $dmsNs . 'mimeHint> ?mimeHint . }
                        OPTIONAL { ?type <' . $dmsNs . 'suffixHint> ?suffixHint . }
                    } ORDER BY ?type'
                )
            );

            $mimeHintArray = array();
            $suffixHintArray = array();

            // check for better suited class
            foreach ($allTypes as $singleType) {
                if (!empty($singleType['mimeHint'])) {
                    $mimeHintArray[$singleType['mimeHint']]     = $singleType['type'];
                }
                if (!empty($singleType['suffixHint'])) {
                    $suffixHintArray[$singleType['suffixHint']]   = $singleType['type'];
                }
            }

            $suffixType = substr($fileName, strrpos($fileName, '.'));
            if (array_key_exists($suffixType, $suffixHintArray)) {
                $fileClassLocal = $suffixHintArray[$suffixType];
            }

            if (array_key_exists($mimeType, $mimeHintArray)) {
                $fileClassLocal = $mimeHintArray[$mimeType];
            }
        }

        // add file resource as instance in local model
        $store->addStatement(
            (string)$this->_owApp->selectedModel,
            $fileUri,
            EF_RDF_TYPE,
            array('value' => $fileClassLocal, 'type' => 'uri')
        );
        // add file resource as instance in system model
        $store->addStatement(
            (string)$this->_configModelUri,
            $fileUri,
            EF_RDF_TYPE,
            array('value' => $fileClass, 'type' => 'uri'),
            false
        );
        // add file resource mime type
        $store->addStatement(
            (string)$this->_configModelUri,
            $fileUri,
            $mimeProperty,
            array('value' => $mimeType, 'type' => 'literal'),
            false
        );
        // add file resource model
        $store->addStatement(
            (string)$this->_configModelUri,
            $fileUri,
            $fileModel,
            array('value' => (string)$this->_owApp->selectedModel, 'type' => 'uri'),
            false
        );

        return true;
    }

}
