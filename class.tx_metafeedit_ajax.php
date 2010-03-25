<?php
/**
* This file is the ajax call processor.
* It handles all the ajax calls. The specific processing done in the ajax call is 
* done in the tx_metafeedit_lib class. 
*
* @author	Christophe BALISKY <cbalisky@metaphore.fr>
* @package TYPO3
* @subpackage	tx_metafeedit
* @todo 	Mettre typoscript pour url pour fonctions d'impression
* @todo 	Vérification cohérence des données
**/

/***************************************************************
*  Copyright notice
*
*  (c) 2007 Christophe BALISKY (cbalisky@metaphore.fr)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
***************************************************************/
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_ajaxlib.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_widgets.php');


/** Ajax call processor class.
 * It handles all the ajax calls. The specific processing done in the ajax call is 
 * done in the tx_metafeedit_lib class. 
 * @package TYPO3
 * @subpackage	tx_metafeedit
 */
 
 class tx_metafeedit_ajax {
	// Private fields
	/**
	* @var string Prefix Id of the package 
	**/
	var $prefixId = 'tx_metafeedit';					// Same as class name
	/**
	* @var string Relative path to the class definition file */
	var $scriptRelPath = 'class.tx_metafeedit_ajax.php';	// Path to this script relative to the extension dir.
	/** 
	* @var string Extension Key */
	var $extKey = 'meta_feedit';						// The extension key.
	/** 
	* @var object Typo3 cObj Object */
	var $cObj;
	/** 
	* @var conf Typo3 TS configuration array */
	var $conf;
	/** 
	* @var boolean Xajax ??? */
	var $xajax;
	/** 
	* @var string Mode the extension is in (Qui, Quoi , Qui Paie Quoi, Paiement ..) */
	var $mode;
	/** 
	* @var object caller object.. */
	var $caller;
	/** 
	* @var object feedit Functions Library called through Ajax */
	var $metafeeditlib;

	/** Basic Iniatialisation method
	* @param object caller object
	* @param array plugin TS configuration array	
	* @return void
	*/

	function init(&$caller,&$conf) {
		$this->cObj = &$GLOBALS['TSFE']->cObj;
		//echo "init";
		//print_r($conf);
		$this->conf=&$conf;
		$this->caller=$caller;
		$this->makeXajaxInstance();
	}

	/** Instantiate the xajax object and configure it
	 * @return void
	 */	

	function makeXajaxInstance() {
		require_once (t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php');
		// Make the instance
		$this->xajax = t3lib_div::makeInstance('tx_xajax');
		$this->xajax->setRequestURI("?eID=tx_metafeedit_pi2");
		$this->xajax->decodeUTF8InputOn();
		$this->xajax->setCharEncoding('utf-8');
		//$this->xajax->debugOn ();

		// To prevent conflicts, prepend the extension prefix
		$this->xajax->setWrapperPrefix($this->prefixId);

		// Do you want messages in the status bar?
		//$this->xajax->statusMessagesOn();

		// Register the names of the PHP functions you want to be able to call through xajax
		$this->xajax->registerFunction(array('processFormData', &$this, 'processFormData'));
		$this->xajax->registerFunction(array('processErrors', &$this, 'processErrors'));

		// If this is an xajax request, call our registered function, send output and exit
		$this->xajax->processRequests();

		// Else create javascript and add it to the header output
		if (!t3lib_div::_GP('ajx')) $GLOBALS['TSFE']->additionalHeaderData[$this->conf['prefixId']] = $this->xajax->getJavascript(t3lib_extMgm::siteRelPath('xajax'));
	}


	//****************************
	// REGISTERED FUNCTIONS
	//***************************
	/** Error processing method
	 * @param array data array given by calling object
	 * @return void
	 */
	function processErrors($data)	{
		print_r($data);
  }

	/** Ajax call processing method
	 * @param array data array given by calling object
	 * @return void
	 */
	function processFormData($data)	{
		$content='';
		$metafeeditlib= t3lib_div::makeInstance('tx_metafeedit_ajaxlib');
		$this->metafeeditlib=&$metafeeditlib;
		$objResponse = new tx_xajax_response();
		$search=$data[$this->prefixId]['code'];
		$mode=$data[$this->prefixId]['mode'];
		$val=$data[$this->prefixId]['val'];
		$cmd=$data[$this->prefixId]['cmd'];

		$code=$data[$this->prefixId]['code'];

		switch ($cmd) {
			case "combolist";
				$ajaxWidgets = t3lib_div::makeInstance('tx_metafeedit_widgets');
				$ajaxWidgets->prefixId=$this->prefixId;
				$ajaxWidgets->handleComboList($data,$objResponse,$metafeeditlib);
				break;
			default :
				$objResponse->addAlert ('Commande : '.$cmd.' inconnue !' );

		}
		// Pour debugger
		$debug=$GLOBALS["TSFE"]->fe_user->getKey('ses','debugflag');
		//$debug=true;
		$l=strlen(serialize($GLOBALS["TSFE"]->fe_user->sesData));
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
		if ($debug) {
				$content="<hr>len: $l <br>";
				$content.="debug<hr>".$GLOBALS["TSFE"]->fe_user->getKey('ses','dbg').'<br>';
				
				// TODO vérifier cohérence des données....

				$objResponse->addAssign('debug', 'innerHTML', $content);
	  }
		return $objResponse->getXML();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_ajax.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_ajax.php']);
}

?>