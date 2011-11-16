<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2011, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Attachment module for the OntoWiki files extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_files
 * @author   {@link http://sebastian.tramp.name Sebastian Tramp}
 */
class AttachmentModule extends OntoWiki_Module
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
        $selectedResource = $this->_owApp->selectedResource;

        $data = array();
        $data['file_uri'] = $selectedResource;

        require_once('FilesController.php');
        $pathHashed = FilesController::getFullPath($selectedResource);
        if (is_readable($pathHashed)) {
            $data['mimeType'] = FilesController::getMimeType($selectedResource);
            return $this->render('files/moduleFile', $data);
        } else {
            return $this->render('files/moduleUpload', $data);
        }
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


