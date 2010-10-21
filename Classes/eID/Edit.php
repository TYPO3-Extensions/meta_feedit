<?php

/**
 * Ajax call to meta_feedit
 * @example http://desktop.ard.fr/?eID=tx_metafeedit&tx_metafeedit[exporttype]=PDF&cmd[usagers]=edit&rU[usagers]=2458
 * @example http://desktop.ard.fr/?eID=tx_metafeedit&tx_metafeedit[exporttype]=PDF
 */

require_once(t3lib_extMgm::extPath('meta_feedit').'pi1/class.tx_metafeedit_pi1.php');
// We call script
		// we initialize page id from calling page.		
		$GLOBALS['TSFE']->id=0;
		// we create front end....
    	$GLOBALS["TSFE"]= t3lib_div::makeInstance('tslib_fe',
				$TYPO3_CONF_VARS,
				0,
				0,
				1);
		
		// we initialize fronte end user
		$feUserObj = tslib_eidtools::initFeUser();
		$GLOBALS['TSFE']->additionalJavaScript=array();
		
		$GLOBALS["TSFE"]->fe_user=$feUserObj;
		//$GLOBALS["TSFE"]->checkAlternativeIdMethods();
		//$GLOBALS["TSFE"]->clear_preview();
		$GLOBALS["TSFE"]->determineId();
		$GLOBALS["TSFE"]->initTemplate();
		$GLOBALS["TSFE"]->getConfigArray();	
		
		$GLOBALS['TSFE']->cObj = t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
		$GLOBALS['TSFE']->cObj->start(array());
		
		// Render charset must be UTF8 for json encode !
		$GLOBALS['TSFE']->renderCharset='utf-8';
		// Get TypoScript for  Controller
		//$tsparserObj = t3lib_div::makeInstance('t3lib_TSparser');
		//$ts = $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_setup.'][43];
		// Parsing  Typoscript
		//$tsparserObj->parse($ts);
						
		$configFile=t3lib_div::_GP('config')?t3lib_div::_GP('config').'.json':'';
		$c=new tx_metafeedit_pi1();
		$c->cObj=$GLOBALS['TSFE']->cObj;
		$content= $c->main('','',$configFile);
		$scripts1=implode(chr(10),$GLOBALS['TSFE']->additionalHeaderData);
		$scripts2="";
		// We update  user int scripts here if necessary
		if ($GLOBALS['TSFE']->isINTincScript())	
		{
			//print_r($GLOBALS['TSFE']->config);
			//echo $content;
			$GLOBALS['TSFE']->content=$content;
			$GLOBALS['TSFE']->INTincScript();
			$content=$GLOBALS['TSFE']->content;
			$scripts2=implode(chr(10),$GLOBALS['TSFE']->additionalHeaderData);
		}
		echo '<html><head><link href="'.t3lib_extMgm::siteRelPath('meta_feedit').'res/css/meta_feedit.css" rel="stylesheet" type="text/css"/>'.$scripts1.$scripts2.'</head><body>'.$content.'</body></html>';
		unset($c);
?>