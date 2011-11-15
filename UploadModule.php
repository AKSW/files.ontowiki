<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2011, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Upload module for the OntoWiki files extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_files
 * @author   {@link http://sebastian.tramp.name Sebastian Tramp}
 */
class UploadModule extends OntoWiki_Module
{
    /*
     * An array of positive regexps to check the class URI of the resource
     * (an empty array means, show always)
     */
    private $_typeExpressions = array();

    /**
     * Constructor
     */
    public function init()
    {
        $config = $this->_privateConfig;

        if (isset($config->typeExpression)) {
            $this->_typeExpressions = $config->typeExpression->toArray();
        }

    }

    public function getTitle()
    {
        return "File Attachment";
    }

    public function shouldShow()
    {
        // show only if type matches
        return $this->_checkClass();
    }

    public function getContents()
    {
        $data['file_uri'] = $this->_owApp->selectedResource;

        if ($this->_checkFile()) {
            return $this->render('files/moduleFile', $data);
        } else {
            return $this->render('files/moduleUpload', $data);
        }
    }

    /*
     * checks for an attached file on the current resource
     * @todo: use static function from the controller
     */
    private function _checkFile()
    {
        $pathHashed = _OWROOT
                    . $this->_privateConfig->path
                    . DIRECTORY_SEPARATOR
                    . md5((string) $this->_owApp->selectedResource);

        return is_readable($pathHashed) ? true : false;
    }

    /*
     * checks the resource types agains the configured patterns
     */
    private function _checkClass()
    {
        $resource = $this->_owApp->selectedResource;
        $rModel   = $resource->getMemoryModel();
        foreach ($this->_typeExpressions as $typeExpression) {
            // search using the preg matchtype
            if (
                $rModel->hasSPvalue(
                    (string) $resource,
                    EF_RDF_TYPE,
                    $typeExpression,
                    'preg'
                )
            ) {
                return true;
            }
        }
        return false;
    }
}


