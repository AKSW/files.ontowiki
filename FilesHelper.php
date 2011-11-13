<?php
/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2011, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * Helper for the OntoWiki Files Extension
 *
 * @category OntoWiki
 * @package  OntoWiki_extensions_files
 * @author   Sebastian Tramp <mail@sebastian.tramp.name>
 * @author   Christoph Rieß <c.riess.dev@googlemail.com>
 * @author   Norman Heino <norman.heino@gmail.com>
 */
class FilesHelper extends OntoWiki_Component_Helper
{
    public function __construct()
    {
        $owApp = OntoWiki::getInstance();
        // if a model has been selected
        if ($owApp->selectedModel != null) {
            // register with extras menu
            $translate  = $owApp->translate;
            $url        = new OntoWiki_Url(array('controller' => 'files', 'action' => 'manage'));
            $extrasMenu = OntoWiki_Menu_Registry::getInstance()->getMenu('application')->getSubMenu('Extras');
            $extrasMenu->setEntry($translate->_('File Manager'), (string) $url);
        }
    }
}
