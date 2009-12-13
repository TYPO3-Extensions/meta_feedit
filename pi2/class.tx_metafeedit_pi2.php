<?php
/**
 * @author     Christophe BALISKY <cbalisky@metaphore.fr>
 * @package    TYPO3
 * @subpackage	tx_metafeedit
 */
 /***************************************************************
*  Copyright notice
*
*  (c) 2007 Christophe BALISKY <cbalisky@metaphore.fr>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_ajax.php');


/**
 * Plugin for the 'tx_metabooking_pi2' extension.
 * 
 * @author     Christophe BALISKY <cbalisky@metaphore.fr>
 * @package    TYPO3
 * @subpackage	tx_metabooking
 */
 
class tx_metafeedit_pi2 extends tslib_pibase {
    var $prefixId      = 'tx_metafeedit_pi2';        // Same as class name
    var $scriptRelPath = 'pi2/class.tx_metafeedit_pi2.php';    // Path to this script relative to the extension dir.
    var $extKey        = 'meta_fedit';    // The extension key.
    
    /**
     * The main method of the PlugIn
     *
     * @param    string        $content: The PlugIn content
     * @param    array        $conf: The PlugIn configuration
     * @return    The content that is displayed on the website
     */
    function main($content='',$conf='')    {
			 // Exit, if script is called directly (must be included via eID in index_ts.php)
			if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');
    	    // Initialize FE user object:
			$feUserObj = tslib_eidtools::initFeUser();
			$GLOBALS["TSFE"]->fe_user=$feUserObj;
			// Connect to database:
			tslib_eidtools::connectDB();
			$ajax = t3lib_div::makeInstance('tx_metafeedit_ajax');
			$ajax->init($this,$conf);
    }
}

$test = t3lib_div::makeInstance('tx_metafeedit_pi2');
$test->main();

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/pi2/class.tx_metafeedit_pi2.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/pi2/class.tx_metafeedit_pi2.php']);
}

?>