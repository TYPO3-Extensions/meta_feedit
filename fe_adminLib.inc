<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasper@typo3.com)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * FE admin lib
 *
 * $Id: fe_adminLib.inc,v 1.19 2005/04/01 14:37:14 typo3 Exp $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @modified by	Christophe BALISKY <cbalisky@metaphore.fr>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  138: class user_feAdmin extends tslib_pibase
 *  189:     function init($content,$conf)
 *
 *              SECTION: Data processing
 *  450:     function parseValues()
 *  549:     function processFiles($cmdParts,$theField)
 *  682:     function overrideValues()
 *  698:     function defaultValues()
 *  717:     function evalValues()
 *  842:     function userProcess($mConfKey,$passVar)
 *  860:     function userProcess_alt($confVal,$confArr,$passVar)
 *  879:     function MetaDBmayFEUserEditSelect($table,$feUserRow,$allowedGroups='',$feEditSelf=0, &$mmTable)
 *  925:     function DBmayFEUserEditSelectMM($table,$fe_user,$allowedGroups,$fe_userEditSelf, &$mmTable)
 *  944:     function DBmayFEUserEditSelect($table,$fe_user,$allowedGroups,$fe_userEditSelf, &$mmTable)
 *  964:     function DBmayFEUserEdit($table,$origArr,$fe_user,$allowedGroups,$fe_userEditSelf)
 *
 *              SECTION: Database manipulation functions
 *  986:     function save()
 * 1055:     function deleteRecord()
 * 1085:     function deleteFilesFromRecord($uid)
 *
 *              SECTION: Command "display" functions
 * 1142:     function displayDeleteScreen()
 * 1170:     function displayCreateScreen()
 * 1194:     function displayListScreen($TABLES,$DBSELECT)
 * 1194:     function displayGridScreen($TABLES,$DBSELECT)
 * 1194:     function displayCalendarScreen($TABLES,$DBSELECT)
 * 1282:     function displayEditScreen()
			foreach($this->conf['email.'] as $key_mode=>$val) {
 * 1377:     function procesSetFixed()
 *
 *              SECTION: Template processing functions
 * 1466:     function removeRequired($templateCode,$failure)
 * 1484:     function getPlainTemplate($key,$r='')
 * 1501:     function modifyDataArrForFormUpdate($inputArr)
 * 1569:     function setCObjects($templateCode,$currentArr=array(),$markerArray='',$specialPrefix='')
 *
 *              SECTION: Emailing
 * 1631:     function sendInfoMail()
 * 1679:     function compileMail($key, $DBrows, $recipient, $setFixedConfig=array())
 * 1725:     function sendMail($recipient, $admin, $content='', $adminContent='')
 * 1770:     function isHTMLContent($c)
 * 1791:     function sendHTMLMail($content,$recipient,$dummy,$fromEmail,$fromName,$replyTo='')
 *
 *              SECTION: Various helper functions
 * 1875:     function aCAuth($r)
 * 1889:     function authCode($r,$extra='')
 * 1915:     function setfixed($markerArray, $setfixed, $r)
 * 1954:     function setfixedHash($recCopy,$fields='')
 * 1980:     function isPreview()
 * 1989:     function createFileFuncObj()
 * 2000:     function clearCacheIfSet()
 * 2015:     function getFailure($theField, $theCmd, $label)
 *
 * TOTAL FUNCTIONS: 38
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once (PATH_t3lib.'class.t3lib_basicfilefunc.php');		// For use with images.
require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_srfeuserregister_pi1_urlvalidator.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_lib.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_grid.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_ajax.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_calendar.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_export.php');
if (t3lib_extMgm::isLoaded('fpdf')) require_once(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');

/**
 * This library provides a HTML-template file based framework for Front End creating/editing/deleting records authenticated by email or fe_user login.
 * It is used in the extensions "direct_mail_subscription" and "feuser_admin" (and the depreciated(!) static template "plugin.feadmin.dmailsubscription" and "plugin.feadmin.fe_users" which are the old versions of these two extensions)
 * Further the extensions "t3consultancies" and "t3references" also uses this library but contrary to the "direct_mail_subscription" and "feuser_admin" extensions which relies on external HTML templates which must be adapted these two extensions delivers the HTML template code from inside.
 * Generally the fe_adminLib appears to be hard to use. Personally I feel turned off by all the template-file work involved and since it is very feature rich (and for that sake pretty stable!) there are lots of things that can go wrong - you feel. Therefore I like the concept used by "t3consultancies"/"t3references" since those extensions uses the library by supplying the HTML-template code automatically.
 * Suggestions for improvement and streamlining is welcome so this powerful class could be used more and effectively.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=396&cHash=d267c36546
 */
 
class tx_metafeedit_user_feAdmin extends tslib_pibase	{

	// External, static:
	var $recInMarkersHSC = TRUE;		// If true, values from the record put into markers going out into HTML will be passed through htmlspecialchars()!
	var $prefixId = "tx_metafeedit";            // Same as class name
	var $dataArr = array();
	var $extKey='tx_metafeedit';
	var $failureMsg = array();
	var $theTable = '';
	var $thePid = 0;
	var $markerArray = array();
	var $templateCode='';
	var $cObj;
	var $cmd;
	var $preview;
	var $backURL;
	var $recUid;
	var $metafeeditlib;
	var $metafeeditgrid;
	var $metafeeditexport;

	var $exporttype=0;	
	var $value =10; // ??? kesako

	//var $performanceaudit; // performance audit flag
	var $perfArray= array();
	var $originUid;
	var $originTable;
	var $originUidsField;
	var $failure=0;		// is set if data did not have the required fields set.
	var $error='';
	var $saved=0;		// is set if data is saved
	var $requiredArr;
	var $currentArr = array();
	var $LOCAL_LANG;
	var $previewLabel='';
	var $nc = '';		// '&no_cache=1' if you want that parameter sent.
	var $additionalUpdateFields='';
	var $emailMarkPrefix = 'EMAIL_TEMPLATE_';
	var $codeLength;
	var $cmdKey;
	var $blogFieldList;
	var $fileFunc='';	// Set to a basic_filefunc object
	var $filesStoredInUploadFolders=array();		// This array will hold the names of files transferred to the uploads/* folder if any. If the records are NOT saved, these files should be deleted!! Currently this is not working!

	// Internal vars, dynamic:
	var $unlinkTempFiles = array();			// Is loaded with all temporary filenames used for upload which should be deleted before exit...

    /**
    * Main function. Called from TypoScript.
    * This
    * - initializes internal variables,
    * - fills in the markerArray with default substitution string
    * - saves/emails if such commands are sent
    * - calls functions for display of the screen for editing/creation/deletion etc.
    *
    * @param	string		Empty string, ignore.
    * @param	array		TypoScript properties following the USER_INT object which uses this library
    * @return	string		HTML content
    * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=396&cHash=d267c36546
    */

	function user_init($content,&$conf)	{
		$DEBUG='';
		//$conf['recInMarkersHSC']=$this->recInMarkersHSC;
 		//$this->conf = &$conf;
		/*if (t3lib_div::_GP('xajax'))  {
  		   	$xajax=t3lib_div::makeInstance('tx_metafeedit_ajax');
  		   	$xajax->init($conf);
  		   	return;
      	}*/

      	if ( $conf['list.']['advancedSearchAjaxSelector'] || is_array($conf['list.']['advancedSearchAjaxSelector.'])) {
		    $ajax = t3lib_div::makeInstance('tx_metafeedit_ajax');
		    $ajax->init($this,$conf);
	    }

      	
        $this->cObj=$GLOBALS['TSFE']->cObj;
        $this->conf = &$conf;
		if (is_object($conf['caller'])) {
			$this->metafeeditlib=$conf['caller']->metafeeditlib;
		} else {
		    // We are called by USER_INT function ...
		    $this->metafeeditlib=t3lib_div::makeInstance('tx_metafeedit_lib');
        }
        $this->metafeeditlib->feadminlib=&$this;
        
		$this->metafeedit=t3lib_div::makeInstance('tx_metafeedit');
		//new export class - CMD (do we need to load this here ?)...
		$this->metafeeditexport=t3lib_div::makeInstance('tx_metafeedit_export');
		$this->metafeeditexport->init($this);

		// We should handle here all GET//POST//PIVARS ... à mettre dans le pi1
		$this->pi_setPiVarDefaults();
		$this->conf['piVars']=$this->piVars;		
	 	//$this->conf['metafeeditlib']=&$this->metafeeditlib;
		//$this->conf['feAdminLib']=&$this; // 300 K
        if ($this->conf['general.']['listMode']==2) {
            $this->metafeeditgrid=t3lib_div::makeInstance('tx_metafeedit_grid');
            $this->metafeeditgrid->init($this->metafeeditlib,$this);
        }
	  
        if ($conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Init ']=strlen(serialize($conf))." Bytes"; 
        if ($conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Conf before init size ']=$this->metafeeditlib->displaytime()." Seconds";
 

		// template file is fetched.
		$this->templateCode = $this->conf['templateContent']; //? $this->conf['templateContent']: $this->cObj->fileResource($this->conf['templateFile']);
		// get table
		$this->theTable = $this->conf['table'];

		// Getting the cmd var

		$this->cmd = (string)$this->conf['inputvar.']['cmd'];
		
		// we check here editUnique Creation Mode (must optimize this);

        if ($conf['editUnique']) {
			$mmTable='';
                        $DBSELECT=$this->metafeeditlib->DBmayFEUserEditSelectMM($this->theTable,$GLOBALS['TSFE']->fe_user->user, $this->conf['allowedGroups'],$this->conf['fe_userEditSelf'],$mmTable,$this->conf).$GLOBALS['TSFE']->sys_page->deleteClause($this->theTable);
                        $thePid = intval($this->conf['pid']) ? intval($this->conf['pid']) : $GLOBALS['TSFE']->id;
									if ($thePid) {
                        $lockPid = $this->conf['edit.']['menuLockPid'] ? ' AND pid='.intval($thePid) : '';
                        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->theTable.($mmTable?','.$mmTable:'') , '1 '.$lockPid.$DBSELECT);
                        $resu=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
                        if ($resu===0) $this->conf['inputvar.']['cmd']='create';
						 				}
        }

		// Getting the preview var
		$this->preview = $this->conf['inputvar.']['preview'];
		// Preview mode is activate also if edit mode is disabled but edit preview mode active
		if (!$this->preview) $this->preview=$this->conf['disableEdit']&&$this->conf['edit.']['preview'] && $this->conf['inputvar.']['cmd']=='edit';
		// backURL is a given URL to return to when login is performed
		// this should be a seperate function ...
	
		$this->backURL = $this->conf['inputvar.']['backURL'];
		if (!$this->backURL && !$this->piVars['referer'][$this->conf['pluginId']]) {
			$this->backURL=$_SERVER['HTTP_REFERER'];
			$this->piVars['referer'][$this->conf['pluginId']]=$_SERVER['HTTP_REFERER'];
		} elseif (!$this->backURL) {
	  	$this->backURL=$this->piVars['referer'][$this->conf['pluginId']];
	  }		
		if (!strpos($this->backURL,'?')) $this->backURL.='?';
		str_replace("&BACK[".$this->conf['pluginId']."]=1","",$this->backURL);
	  $this->backURL.="&BACK[".$this->conf['pluginId']."]=1";
		
		// Uid to edit:		
		//$this->recUid = t3lib_div::_GP('rU');
		if ($this->conf['inputvar.']['rU']) $this->recUid = $this->conf['inputvar.']['rU'];

    // Fe User Fields
      
    $this->fUField = $this->conf['fUField']?$this->conf['fUField']:t3lib_div::_GP('fUField['.$this->conf['pluginId'].']');
    $this->fUKeyField = $this->conf['fUKeyField']?$this->conf['fUKeyField']:t3lib_div::_GP('fUKeyField['.$this->conf['pluginId'].']');
		//$this->fUField = $this->conf['fUField'][$this->conf['pluginId']]?$this->conf['fUField'][$this->conf['pluginId']]:t3lib_div::_GP('fUField['.$this->conf['pluginId'].']');
		//$this->fUKeyField = $this->conf['fUKeyField'][$this->conf['pluginId']]?$this->conf['fUKeyField'][$this->conf['pluginId']]:t3lib_div::_GP('fUKeyField['.$this->conf['pluginId'].']');
		$this->fU = $this->conf['fU']?$this->conf['fU']:t3lib_div::_GP('fU['.$this->conf['pluginId'].']');

		$this->conf['recUid']=$this->recUid;
		$this->conf['originUid'] = $this->conf['originUid']?$this->conf['originUid']:t3lib_div::_GP('oU');
		$this->conf['originTable'] = $this->conf['originTable']?$this->conf['originTable']:t3lib_div::_GP('oUTable');
		$this->conf['originUidsField'] = $this->conf['originUidsField']?$this->conf['originUidsField']:t3lib_div::_GP('oUField');
		$this->conf['originKeyField'] = $this->conf['originKeyField']?$this->conf['originKeyField']:t3lib_div::_GP('oUKeyField');

		// *****************
		// Ajout CMD - gestion de l'order
		// *******************
		if ($this->conf['inputvar.']['orderDir']==1 && !$this->preview && !$conf['inputvar.']['doNotSave'])	{	// Delete record if delete command is sent + the preview flag is NOT set.
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc order start:']=$this->metafeeditlib->displaytime()." Seconds";
			$this->orderRecord();
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc order end:']=$this->metafeeditlib->displaytime()." Seconds";
		}

		
		// Authentication code:
		$this->authCode = t3lib_div::_GP('aC');

		$this->nc = $this->conf['cacheMode']==0 ? '&no_cache=1' : $this->nc;
		// pid
		$this->thePid = intval($this->conf['pid']) ? intval($this->conf['pid']) : $GLOBALS['TSFE']->id;
		 if ($conf['debug']) echo t3lib_div::view_array(array('thePid '=>$this->thePid ));
		//
		$this->codeLength = intval($this->conf['authcodeFields.']['codeLength']) ? intval($this->conf['authcodeFields.']['codeLength']) : 8;

		$this->LOCAL_LANG=$conf['LOCAL_LANG'];

		// Setting the hardcoded lists of fields allowed for editing and creation.

		$this->metafeeditlib->getFieldList($this->conf);
		$fArr=t3lib_div::trimexplode(',',$this->conf['fieldList']);
    foreach($fArr as $fN) {
			$this->markerArray['###EVAL_ERROR_FIELD_'.$fN.'###']='';
			//MODIF CBY
			$this->markerArray['###FIELD_EVAL_'.$fN.'###']='';

 			if ( $GLOBALS['TCA'][$this->theTable]['columns'][$fN]['config']['type']=='group') {
				if ($GLOBALS['TCA'][$this->theTable]['columns'][$fN]['config']['internal_type']=='file') $this->markerArray['###EVAL_ERROR_FIELD_'.$fN.'_file###']='';
				$this->markerArray['###FIELD_EVAL_'.$fN.'###']='';
			}
		}
		//Si blog
		$fbArr=t3lib_div::trimexplode(',',$this->conf['blogFieldList']);
    foreach($fbArr as $fN) {
			$this->markerArray['###EVAL_ERROR_FIELD_'.$fN.'###']='';

			//$this->markerArray['###FIELD_EVAL_'.$fN.'###']='';

 			if ( $GLOBALS['TCA'][$this->theTable]['columns'][$fN]['config']['type']=='group') {
				if ($GLOBALS['TCA'][$this->theTable]['columns'][$fN]['config']['internal_type']=='file') $this->markerArray['###EVAL_ERROR_FIELD_'.$fN.'_file###']='';
				$this->markerArray['###FIELD_EVAL_'.$fN.'###']='';
			}
		}
		
		// </CBY>
		// globally substituted markers, fonts and colors.
		$splitMark = md5(microtime());
		list($this->markerArray['###GW1B###'],$this->markerArray['###GW1E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap1.']));
		list($this->markerArray['###GW2B###'],$this->markerArray['###GW2E###']) = explode($splitMark,$this->cObj->stdWrap($splitMark,$this->conf['wrap2.']));
		$this->markerArray['###GC1###'] = $this->cObj->stdWrap($this->conf['color1'],$this->conf['color1.']);
		$this->markerArray['###GC2###'] = $this->cObj->stdWrap($this->conf['color2'],$this->conf['color2.']);
		$this->markerArray['###GC3###'] = $this->cObj->stdWrap($this->conf['color3'],$this->conf['color3.']);

		// Initialize markerArray, setting FORM_URL and HIDDENFIELDS

		//<CBY
		$formid=$GLOBALS['TSFE']->id;
		//modif CMD
		$formType=$GLOBALS['TSFE']->type;

		if ($this->conf['createPid']) { $formid=$this->conf['createPid']; }
		if ($this->conf['editPid'] && $this->conf['inputvar.']['cmd']=='edit') { $formid=$this->conf['editPid']; }

		


		// we handle Global parameters for links from other page
		$this->markerArray['###GLOBALPARAMS###']='';
		
		// === Put into Session
		
		//$A=t3lib_div::_GP($this->theTable);
		//if  ($A['lV'] && $A['lField']) {
		//  $this->markerArray['###GLOBALPARAMS###'].='&'.$this->theTable.'[lV]='.$A['lV'].'&'.$this->theTable.'[lField]='.$A['lField'];
		//}
		//$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['advancedSearch'][$this->conf['pluginId']]?$this->metafeeditlib->keepVars('&tx_metafeedit[advancedSearch]['.$this->conf['pluginId'].']',$this->piVars['advancedSearch'][$this->conf['pluginId']]):'';
		//$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['sword'][$this->conf['pluginId']]?'&tx_metafeedit[sword]['.$this->conf['pluginId'].']='.$this->piVars['sword'][$this->conf['pluginId']]:'';
		//$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['sort'][$this->conf['pluginId']]?'&tx_metafeedit[sort]['.$this->conf['pluginId'].']='.$this->piVars['sort'][$this->conf['pluginId']]:'';
		//$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['sortLetter'][$this->conf['pluginId']]?'&tx_metafeedit[sortLetter]['.$this->conf['pluginId'].']='.$this->piVars['sortLetter'][$this->conf['pluginId']]:'';
		//$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['pointer'][$this->conf['pluginId']]?'&tx_metafeedit[pointer]['.$this->conf['pluginId'].']='.$this->piVars['pointer'][$this->conf['pluginId']]:'';
		
		//=====
		
		//if ($this->piVars['reset']) unset($this->piVars['advancedSearch']);
		/*$this->markerArray['###GLOBALPARAMS###'].=$this->conf['originUid']?'&oU='.$this->conf['originUid']:'';
		$this->markerArray['###GLOBALPARAMS###'].=$this->conf['originTable']?'&oUTable='.$this->conf['originTable']:'';
		$this->markerArray['###GLOBALPARAMS###'].=$this->conf['originUidsField']?'&oUField='.$this->conf['originUidsField']:'';
		$this->markerArray['###GLOBALPARAMS###'].=$this->conf['originKeyField']?'&oUKeyField='.$this->conf['originKeyField']:'';
		*/
		//$this->markerArray['###GLOBALPARAMS###'].=$this->fUField?'&fUField='.$this->fUField:'';
		//$this->markerArray['###GLOBALPARAMS###'].=$this->fUKeyField?'&fUKeyField='.$this->fUKeyField:'';
		//$this->markerArray['###GLOBALPARAMS###'].=$this->fU?'&fU='.$this->fU:'';
		$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['referer'][$this->conf['pluginId']]?'&tx_metafeedit[referer]['.$this->conf['pluginId'].']='.rawurlencode($this->piVars['referer'][$this->conf['pluginId']]):'';

		/*
		$this->markerArray['###GLOBALPARAMS###'].=$this->fUField?'&fUField['.$this->conf['pluginId'].']='.$this->fUField:'';
		$this->markerArray['###GLOBALPARAMS###'].=$this->fUKeyField?'&fUKeyField['.$this->conf['pluginId'].']='.$this->fUKeyField:'';
		$this->markerArray['###GLOBALPARAMS###'].=$this->fU?'&fU['.$this->conf['pluginId'].']='.$this->fU:'';
		*/
	    $this->conf['GLOBALPARAMS']=$this->markerArray['###GLOBALPARAMS###'];						
		$prma=array();
		if ($this->nc) $prma['no_cache']=1;
		//modif CMD pour gérer le type de la page
		if ($formType != 0) $formid .=','.$formType;
		$pl=$this->pi_getPageLink($formid,'',$prma);//,,$this->nc.$this->conf['addParams']);	
		if (!strpos($pl,'?')) $pl.='?';
		//$pl=$this->metafeeditlib->hsc($this->conf,$pl);
		$this->markerArray['###FORM_URL###'] = $this->metafeeditlib->hsc($this->conf,$pl.$this->markerArray['###GLOBALPARAMS###']);
		$this->markerArray['###FORM_URL_NO_PRM###']=$this->metafeeditlib->hsc($this->conf,$pl);;
		$this->markerArray['###FORM_URL_ENC###'] = rawurlencode($this->markerArray['###FORM_URL###']);
		$this->markerArray['###FORM_URL_HSC###'] = htmlspecialchars($pl.$this->markerArray['###GLOBALPARAMS###']);
		$this->markerArray['###NEW_URL###']=$this->metafeeditlib->hsc($this->conf,$this->pi_getPageLink($formid,'',array( 'no_cache'=>1, 'cmd['.$this->conf['pluginId'].']'=>'create', 'rU['.$this->conf['pluginId'].']' => '', 'backURL['.$this->conf['pluginId'].']'=> $pl)));
		//$this->markerArray['###NEW_URL###']=$this->metafeeditlib->hsc($this->conf,$this->pi_getPageLink($formid,'',array( 'no_cache'=>1, 'cmd['.$this->conf['pluginId'].']'=>'create', 'backURL['.$this->conf['pluginId'].']'=> $pl)));
		$this->markerArray['###BACK_URL###'] = $this->metafeeditlib->hsc($this->conf,$this->backURL.$this->markerArray['###GLOBALPARAMS###']);
		$this->markerArray['###BACK_URL_ENC###'] = rawurlencode($this->markerArray['###BACK_URL###']);
		$this->markerArray['###BACK_URL_HSC###'] = htmlspecialchars($this->backURL.$this->markerArray['###GLOBALPARAMS###']);
		$this->markerArray['###EVAL_ERROR###'] = '';
		$this->markerArray['###THE_PID###'] = $this->thePid;
		$this->markerArray['###AUTH_CODE###'] = $this->authCode;
		$this->markerArray['###THIS_ID###'] = $GLOBALS['TSFE']->id;
		$this->markerArray['###THIS_URL###'] = htmlspecialchars(t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR'));

		$FEUSER=$GLOBALS['TSFE']->fe_user->user;
		if (!is_array($FEUSER)) $FEUSER=array();
		$this->markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $FEUSER, '', TRUE, 'FEUSER_FIELD_', $this->conf['general.']['xhtml']);

		// Setting cmdKey which is either 'edit' or 'create'
		switch($this->conf['inputvar.']['cmd'])	{
			case 'edit':
				$this->conf['cmdKey']='edit';
				$this->conf['cmdmode']='edit';
			break;
			default:
				$this->conf['cmdKey']='create';
				$this->conf['cmdmode']='edit';
			break;
		}
		$pluginId=$conf['pluginId'];
		$this->markerArray['###HIDDENFIELDS###'] =
			($this->conf['inputvar.']['cmd']?'<input type="hidden" name="cmd['.$pluginId.']" value="'.htmlspecialchars($this->conf['inputvar.']['cmd']).'" />':'').
			($this->authCode?'<input type="hidden" name="aC['.$pluginId.']" value="'.htmlspecialchars($this->authCode).'" />':'').
			($this->backURL?'<input type="hidden" name="backURL['.$pluginId.']" value="'.htmlspecialchars($this->backURL).'" />':'').
			($conf['blogData']?'<input type="hidden" name="cameFromBlog['.$pluginId.']" value="1" />':'');
		// Setting requiredArr to the fields in 'required' intersected field the total field list in order to remove invalid fields.
		$this->requiredArr = array_intersect(
			t3lib_div::trimExplode(',',$this->conf[$this->conf['cmdKey'].'.']['required'],1),
			t3lib_div::trimExplode(',',$this->conf[$this->conf['cmdKey'].'.']['fields'],1)
		);
		// Setting incoming data. Non-stripped

		$fe=$conf['inputvar.']['fedata'];
		$this->dataArr = $fe[$this->theTable];	// Incoming data.

		$this->conf['dataArr']=&$this->dataArr;
		// Setting blog incoming data. Non-stripped
		$this->markerArray['###EVAL_BLOG_ERROR###'] = '';
		if ($this->conf['blogData'] && is_array($fe['tx_metafeedit_comments'])) {
			$this->dataArr = $fe['tx_metafeedit_comments'];	
			$this->dataArr['linked_row'] =$this->theTable.'_'.$conf['inputvar.']['rU'];
			// checking CAPTCHA
			if ($this->conf['blog.']['captcha'] && is_object($this->metafeeditlib->freeCap) && !$this->metafeeditlib->freeCap->checkWord($this->piVars['captcha_response'])) {
				$this->markerArray['###EVAL_BLOG_ERROR###'] = $this->metafeeditlib->getLL('blog_captcha_error',$this->conf);
				$this->failure=1;
				$this->failureMsg['blog_captcha']=$this->markerArray['###EVAL_BLOG_ERROR###'];
		   }
		}
		// Incoming data.
		if (!$this->recUid) $this->recUid=$this->dataArr[$this->conf['uidField']];
		//if (!$this->recUid) $this->recUid=$this->dataArr['uid'];
		$this->conf['recUid']=$this->recUid;
		$this->markerArray['###REC_UID###'] = $this->recUid;

		// Checking template file and table value

		if (!$this->templateCode)	{
			$content = 'No template file found: '.$this->conf['templateFile'];
			return $content;
		}

		if (!$this->theTable || !$this->conf['fieldList'])	{
			$content = 'Wrong table: '.$this->theTable.', Fields : '.$this->conf['fieldList'];
			return $content;		// Not listed or editable table!
		}
        if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Init done:']=$this->metafeeditlib->displaytime()." Seconds";

		// *****************
		// If data is submitted, we take care of it here.
		// *******************
		if ($this->conf['inputvar.']['cmd']=='delete' && !$this->preview && !$conf['inputvar.']['doNotSave'])	{	// Delete record if delete command is sent + the preview flag is NOT set.
      if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc delete start:']=$this->metafeeditlib->displaytime()." Seconds";
			$this->deleteRecord();
      if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc delete end:']=$this->metafeeditlib->displaytime()." Seconds";
		}

		// If incoming data is seen...
		if (is_array($this->dataArr))	{
			if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc incoming data start:']=$this->metafeeditlib->displaytime()." Seconds";

			// Evaluation of data:
			if (is_array($this->dataArr['grid'])) {
				// We are in datagrid mode ...
				// A VALIDER CBY
				$this->conf['cmdmode']='grid';
				$saveData=$this->dataArr;
				$rowData=$this->dataArr['grid'];				
				$nbcols=$this->dataArr['nbcols'];
				$sqlModeArr=$this->dataArr['grid-sqlmode'];
				foreach($rowData as $row=>$colData) {
					foreach($colData as $col=>$dataArr) {
					  $this->failure=0;
						if ( (!$this->preview || $this->conf['blogData']) && !$conf['inputvar.']['doNotSave'])	{	// doNotSave is a global var (eg a 'Cancel' submit button) that prevents the data from being processed
							
							if ($nbcols>1  && is_array($dataArr) && !array_key_exists('uid', $dataArr)) {  // secondaryFields
								foreach($dataArr as $col2=>$dataArr2) {						
									switch ($sqlModeArr[$row][$col][$col2]) {
										case 'insert':
											$this->conf['cmdKey']='create';
											break;
										case 'update':
											$this->conf['cmdKey']='edit';
											break;
											
									}
									$this->dataArr=$dataArr2;
									$this->parseValues();
									$this->overrideValues();
									$this->evalValues();
									if ($this->conf['evalFunc'])	{
										$this->dataArr = $this->userProcess('evalFunc',$this->dataArr);
									}
									$this->saveGrid($this->conf,$row,$col,$this->dataArr,$sqlModeArr[$row][$col][$col2],array($col2));
								}
							} else {
							    
								switch ($sqlModeArr[$row][$col]) {
									case 'insert':
										$this->conf['cmdKey']='create';
										break;
									case 'update':
										$this->conf['cmdKey']='edit';
										break;						
								}
								$this->dataArr=$dataArr;
							    $this->parseValues();
								$this->overrideValues();
								$this->evalValues();
								if ($this->conf['evalFunc'])	{
									$this->dataArr = $this->userProcess('evalFunc',$this->dataArr);
								}
								$this->saveGrid($this->conf,$row,$col,$this->dataArr,$sqlModeArr[$row][$col]);
						  }
							if ($this->conf['evalFunc'])	{

								$this->currentArr = $this->userProcess('evalFunc',$this->currentArr);
							}
						} else {
							if ($this->conf['debug']) debug($this->failure);
						}
                    if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Incoming data end:']=$this->metafeeditlib->displaytime()." Seconds";
		  		}
			}
			$this->dataArr=$saveData;

		  } else {
		  		//krumo($this->dataArr);

		        // We are in normal List or edit mode
				$this->parseValues();
				$this->overrideValues();
				$this->evalValues();
				if ($this->conf['evalFunc'])	{

					$this->dataArr = $this->userProcess('evalFunc',$this->dataArr);
				}
				// if not preview and no failures, then set data...

				if (!$this->failure && (!$this->preview || $this->conf['blogData']) && !$conf['inputvar.']['doNotSave'])	{	// doNotSave is a global var (eg a 'Cancel' submit button) that prevents the data from being processed
					$this->save($this->conf);
					if ($this->conf['evalFunc'])	{
						$this->currentArr = $this->userProcess('evalFunc',$this->currentArr);
					}

				} else {
					if ($this->conf['debug'])		debug($this->failure);
				}
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Incoming data end:']=$this->metafeeditlib->displaytime()." Seconds";
		  }
		} else {
			// We have no incoming data
			if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc No incoming data start:']=$this->metafeeditlib->displaytime()." Seconds";
			$dataArr=array();
			//moved before creating default value, else it will be unseted
			$this->dataArr=$dataArr;
			$this->defaultValues($this->conf);	// If no incoming data, this will set the default values.
		 	// here we  load overrideValues !
			// override value arr to transmit to fe_adminLib
			$fNA=$this->metafeeditlib->getOverrideFields($this->conf['inputvar.']['cmd'],$this->conf);
			$this->cObj->start($this->dataArr,$this->theTable);
			//print_r($this->cObj);
			foreach($fNA as $fN) {
				$val=$this->metafeeditlib->getOverrideValue($fN,$this->conf['inputvar.']['cmd'],$this->conf,$this->cObj);
				$dataArr[$fN]=$val;
				$this->dataArr[$fN]=$val;
			}			
			$this->currentArr=$dataArr;
			// ugly hack to handle checkboxes properly
			$this->dataArr['tx_metafeedit_dont_ctrl_checkboxes']=1;

			if (is_array($this->dataArr) && count($this->dataArr)>1) {
				$this->parseValues(1);
				$this->evalValues();

			}
			if ($this->conf['evalFunc'])	{
					$this->dataArr = $this->userProcess('evalFunc',$this->dataArr);
			}
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc No incoming data end:']=$this->metafeeditlib->displaytime()." Seconds";

		}
		if ($this->failure && !$this->conf['blogData'])	{$this->preview=0;}	// No preview flag if a evaluation failure has occured
		//if ($this->failure)	{$this->preview=0;}	// No preview flag if a evaluation failure has occured
		$this->previewLabel = ($this->preview || $this->conf['blogData'])? '_PREVIEW' : '';	// Setting preview label prefix.


		// *********************
		// DISPLAY FORMS:
		// ***********************
		if ($this->saved) {
			$this->dataArr=array_merge($this->dataArr,$this->currentArr);
			// Clear page cache
			$this->clearCacheIfSet();

				// Displaying the page here that says, the record has been saved. You're able to include the saved values by markers.
			switch($this->conf['inputvar.']['cmd'])	{
				case 'delete':
					$key='DELETE';
				break;
				case 'edit':
					$key='EDIT';
				break;
				default:
					$key='CREATE';
				break;
			}

			if (($this->conf[$this->conf['inputvar.']['cmd'].'.']['statusScreen'] && ($this->conf['inputvar.']['cmd']=='edit' || $this->conf['inputvar.']['cmd']=='create')) ||  ($this->conf['inputvar.']['cmd']!='edit' && $this->conf['inputvar.']['cmd']!='create')) {
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Status screen start:']=$this->metafeeditlib->displaytime()." Seconds";
			$this->conf['cmdmode']='status';

			// Output message
			// this should be message in Edit Screen;

			$templateCode = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE_'.$key.'_SAVED###');
			$this->metafeeditlib->setCObjects($this->conf,$this->markerArray,$templateCode,$this->currentArr);
			// ???
			$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $this->currentArr, '', TRUE, 'FIELD_', $this->conf['general.']['xhtml']);
			$content = $this->cObj->substituteMarkerArray($templateCode, $markerArray);
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Status screen end:']=$this->metafeeditlib->displaytime()." Seconds";
			} else {
				// no status screen we want too stay on the edit form if in create mode we must switch to edit template
				if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc No status screen start:']=$this->metafeeditlib->displaytime()." Seconds";

				if ($this->conf['inputvar.']['cmd']!='edit') {
					$this->conf['inputvar.']['cmd']='edit';
					$this->metafeeditlib->getFieldList($this->conf);	
				}
				// thanks to Karl-Ernst Kiel [kekiel@kekiel.de]
				$this->markerArray['###EVAL_ERROR###'] = $this->metafeeditlib->getLL('edit_saved_message',$this->conf);
       	        $content = $this->displayEditScreen();
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc No istatus screen end:']=$this->metafeeditlib->displaytime()." Seconds";
			}

			// Notification Mails

			// mail admin
			// mail feuser
			// mail datamail

			// SetFixed Mails (moderation)...
			// mail admin
			// mail feuser
			// mail datamail ?
			
			// we reset email array if data is empty.

			foreach($this->conf['email.'] as $key_mode=>$val) {
				if ($this->conf['email.'][$key_mode]===0 || $this->conf['email.'][$key_mode]=='0') unset($this->conf['email.'][$key_mode]);
			}

			
			if ($this->conf['email.']['sendAdminMail'] || $this->conf['email.']['sendAdminMail'] || $this->conf['email.']['sendFEUserMail'] || $this->conf['email.']['sendDataMail'] || $this->conf['email.']['sendDataInfoMail'] || $this->conf['email.']['sendFEUserInfoMail'] || $this->conf['email.']['sendAdminInfoMail']) {
			if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Notification mail start:']=$this->metafeeditlib->displaytime()." Seconds";
				
			$this->compileMail(
				$key.'_SAVED',
				array($this->currentArr),
				$this->getFeuserMail($this->currentArr,$this->conf),
				$this->conf['setfixed.']
			);
	
			if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Notification mail end:']=$this->metafeeditlib->displaytime()." Seconds";
			}
		} elseif ($this->error) {	

			// If there was an error, we return the template-subpart with the error message
			$this->markerArray['###EVAL_ERROR###'] = $this->metafeeditlib->getLL('error_occured',$this->conf);

			$templateCode = $this->cObj->getSubpart($this->templateCode, $this->error);
			$this->metafeeditlib->setCObjects($this->conf,$this->markerArray,$templateCode);
			$content = $this->cObj->substituteMarkerArray($templateCode, $this->markerArray);
		} else {
			// Finally, if there has been no attempt to save. That is either preview or just displaying and empty or not correctly filled form:
			if (!$this->conf['inputvar.']['cmd'])	{
				$this->conf['inputvar.']['cmd']=$this->conf['defaultCmd'];
			}
			if ($this->conf['debug'])		debug('Display form: '.$this->conf['inputvar.']['cmd'],1);
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc process start:']=$this->metafeeditlib->displaytime()." Seconds";
		    switch($this->conf['inputvar.']['cmd'])	{
			    case 'setfixed':
			        $this->conf['cmdmode']='setfixed';
				    $content = $this->procesSetFixed();
			        break;
			    case 'infomail':
			        $this->conf['cmdmode']='infomail';
				    $content = $this->sendInfoMail();
			        break;
			    case 'delete':
			        $this->conf['cmdmode']='delete';
				    $content = $this->displayDeleteScreen();
			        break;
			    case 'edit':
				    if ($this->conf['debug']) echo t3lib_div::view_array(array('displayEditScreen'=>'on'));
			        $this->conf['cmdmode']='edit';
				    $content = $this->displayEditScreen();
			        break;
			    case 'create':
			        $this->conf['cmdmode']='create';
				    $content = $this->displayCreateScreen($this->conf);
			        break;
		     }
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc process end:']=$this->metafeeditlib->displaytime()." Seconds";
            if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc Conf process end size']=strlen(serialize($this->conf))." Bytes"; 
            if ($this->conf['performanceaudit']) $this->perfArray['Conf TCAN size ']=strlen(serialize($this->conf['TCAN']))." Bytes"; 
            if ($this->conf['performanceaudit']) $this->perfArray['Conf LOCALLANG size ']=strlen(serialize($this->conf['LOCAL_LANG']))." Bytes"; 
            if ($this->conf['performanceaudit']) $this->perfArray['Conf Template size ']=strlen(serialize($this->conf['templateContent']))." Bytes"; 
			if ($this->conf['debug.']['krumo'] && t3lib_extmgm::isLoaded('krumo')) {
				krumo($this->conf);
				krumo($this->conf['TCAN']);
				krumo($this->conf['LOCAL_LANG']);
			}
	    }
	    
	    // Draw RTEs :	        
		/*
		if (count($conf['RTE'])) {
				$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
				$this->thisConfig = $pageTSConfig['RTE.']['default.']['FE.'];
				$this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');//&t3lib_BEfunc::RTEgetObj();
				$this->thePidValue = $GLOBALS['TSFE']->id;
				$this->RTEtypeVal='text';
		}
		
		if (is_array($this->conf['RTE'])) foreach($conf['RTE'] as $key=>$RTE) {
	        if ($RTE['cmdmode']==$this->conf['cmdmode']) {
				
                $this->RTEcounter=$key;
                $this->formName =$conf['RTE'][$key]['formName'];
				$this->strEntryField = $conf['RTE'][$key]['field'];
				$this->specConf =$conf['RTE'][$key]['spec'];  
				$this->PA =$conf['RTE'][$key]['PA'];				
				$RTEItem = $this->RTEObj->drawRTE($this,$conf['RTE'][$key]['table'],$conf['RTE'][$key]['field'],$row=array(), $this->PA, $this->specConf, $this->thisConfig, $this->RTEtypeVal, '', $this->thePidValue);
				$content = $this->cObj->substituteMarker($content,'###RTE_'.$key.'###',$RTEItem);
			}
		}
		
		if (!$GLOBALS['TSFE']->config['config']['removeDefaultJS']) {
	   		$content = $this->additionalJS_initial.$content;
	    	if ($this->additionalJS_pre) $content.='<script type="text/javascript">'. implode('', $this->additionalJS_pre).'</script>';
	    	if ($this->additionalJS_post) $content.='<script type="text/javascript">'. implode('', $this->additionalJS_post).'</script>';
	    	*/
		//if ($this->additionalJS_submit) $content.='<script type="text/javascript">/*<![CDATA[*/'. implode('', $this->additionalJS_submit).'/*]]>*/</script>';
	  	/*
		} else {
	   		$content= $this->additionalJS_initial.$content;
	    	if ($this->additionalJS_pre) $content.=TSpagegen::inline2TempFile( implode('', $this->additionalJS_pre),'js');
	    	if ($this->additionalJS_post) $content.=TSpagegen::inline2TempFile( implode('', $this->additionalJS_post),'js');
	    	//if ($this->additionalJS_submit) $content.=TSpagegen::inline2TempFile( implode('', $this->additionalJS_submit),'js');
		}
		*/
		/*hack for rte pleasewait error, very ugly but RTE is too hard linked with typo3 core to prevent this !*/
		if (is_array($this->conf['RTE'])) foreach($conf['RTE'] as $key=>$RTE) {
	        if ($RTE['cmdmode']!=$this->conf['cmdmode']) {
				$content = $this->cObj->substituteMarker($content,'###RTE_'.$key.'###',$conf['RTEItem'][$key]);
				$content.='<div style="display:none;" id="pleasewait'.$key.'"/>';
				$content.='<div style="display:none;" id="editorWrap'.$key.'"/>';
				$content.='<textarea style="display:none;" id="RTEarea'.$key.'"/>';
			}
		}

		// Delete temp files:
		
		foreach($this->unlinkTempFiles as $tempFileName)	{
			t3lib_div::unlink_tempfile($tempFileName);
	    }
	    
        if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc process end:']=$this->metafeeditlib->displaytime()." Seconds";

	    // Return content:
	    $content.=$this->metafeeditlib->getJSAfter($this,$this->conf);
	    if ($conf['debug.']['vars']) {
	  	    $this->metafeeditlib->debug('Post Vars :',$_POST,$DEBUG);
	  	    $this->metafeeditlib->debug('GET Vars :',$_GET,$DEBUG);
	  	    $this->metafeeditlib->debug('PI Vars :',$this->piVars,$DEBUG);
	  	    $this->metafeeditlib->debug('METAFEEDIT Vars :',$this->conf['inputvar.'],$DEBUG);
	    }
	    if ($conf['debug.']['markerArray']) $this->metafeeditlib->debug('Marker Array :',$this->markerArray,$DEBUG);
	    if ($conf['debug.']['langArray']) {
	  	    $this->metafeeditlib->debug('Local Lang Array :'.$this->conf['LLKEY'],$this->conf['LLKEY'],$DEBUG);
	  	    $this->metafeeditlib->debug('LOCAL_LANG',$this->conf['LOCAL_LANG'],$DEBUG);
	  	    $this->metafeeditlib->debug('_LOCAL_LANG',$this->conf['_LOCAL_LANG.'],$DEBUG);
        }
	    if ($conf['debug.']['conf']) $this->metafeeditlib->debug('Conf :',$this->conf,$DEBUG);
	    if ($conf['debug.']['template']) $this->metafeeditlib->debug('Templates :',$this->conf['templateContent'],$DEBUG);
		if ($conf['debug.']['tsfe']) $this->metafeeditlib->debug('TSFE :',$GLOBALS['TSFE'],$DEBUG);

        return ($conf['performanceaudit']?t3lib_div::view_array($this->perfArray):'').$content.$conf['debug.']['debugString'].$DEBUG;
	}

	function getFeuserMail($Arr,&$conf) {
			// gérer le cas du mail utilisateur !!!!
			if ($conf['fe_cruser_id']) {
				$feuserid=$Arr[$conf['fe_cruser_id']];
				$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('fe_users','uid',$feuserid,'','','1');
				$recipient=$DBrows[0]['email'];
				
			} elseif ($GLOBALS['TSFE']->fe_user->user[email]) {
				$recipient=$GLOBALS['TSFE']->fe_user->user[email];
			} else {
				$recipient=$Arr[$conf['email.']['field']];
			}
			return $recipient;
	}

	/*****************************************
	 *
	 * Data processing
	 *
	 *****************************************/

	/**
	 * Performs processing on the values found in the input data array, $this->dataArr.
	 * The processing is done according to configuration found in TypoScript
	 * Examples of this could be to force a value to an integer, remove all non-alphanumeric characters, trimming a value, upper/lowercase it, or process it due to special types like files submitted etc.
	 * Called from init() if the $this->dataArr is found to be an array
	 *
	 * @return	void
	 * @see init()
	 */
	function parseValues($workOnlyOnDataArr=0)	{
		//Blog Hack
		$evalValues=$this->conf['blogData']?$this->metafeeditlib->getBlogEvalValues($this->conf):$this->conf[$this->conf['cmdKey'].'.']['evalValues.'];		
		//$parseValues=array_merge(is_array($this->conf['parseValues.'])?$this->conf['parseValues.']:array(),is_array($evalValues)?$evalValues:array());
		if(is_array($this->conf['parseValues.'])) {
			$parseValues=$this->conf['parseValues.'];
	  	if (is_array($evalValues)) {
	   		$parseValues=array_merge($parseValues,$evalValues);
	  	}
		}
		else $parseValues = $evalValues;
		
		if ($workOnlyOnDataArr) {
			$theParseValues=array();
			foreach ($this->dataArr as $field=>$val) {
				if ($theParseValues[$field]) $parseValues[]=$parseValues[$field];
			}
			unset ($parseValues);
			$parseValues=&$theParseValues;
		}

		if (is_array($parseValues))	{
				reset($parseValues);
			  while(list($theField,$theValue)=each($parseValues))	{
				$this->markerArray['###EVAL_ERROR_FIELD_'.$theField.'###']='';
				$listOfCommands = t3lib_div::trimExplode(',',$theValue,1);
				while(list(,$cmd)=each($listOfCommands))	{
					$cmdParts = split('\[|\]',$cmd);	// Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
					$theCmd=trim($cmdParts[0]);
					switch($theCmd)	{
						case 'int':
							$this->dataArr[$theField]=intval($this->dataArr[$theField]);
						break;
						case 'lower':
						case 'upper':
							$this->dataArr[$theField] = $this->cObj->caseshift($this->dataArr[$theField],$theCmd);
						break;
						case 'nospace':
							$this->dataArr[$theField] = str_replace(' ', '', $this->dataArr[$theField]);
						break;
						case 'alpha':
							$this->dataArr[$theField] = ereg_replace('[^a-zA-Z]','',$this->dataArr[$theField]);
						break;
						case 'num':
							$this->dataArr[$theField] = ereg_replace('[^0-9]','',$this->dataArr[$theField]);
						break;
						case 'alphanum':
							$this->dataArr[$theField] = ereg_replace('[^a-zA-Z0-9]','',$this->dataArr[$theField]);
						break;
						case 'alphanum_x':
							$this->dataArr[$theField] = ereg_replace('[^a-zA-Z0-9_-]','',$this->dataArr[$theField]);
						break;
						case 'trim':
							$this->dataArr[$theField] = trim($this->dataArr[$theField]);
						break;
						case 'strip_tags':
							$this->dataArr[$theField] = strip_tags($this->dataArr[$theField]);
						  break;
						case 'noaccents':
							$this->dataArr[$theField] = removeaccents($this->dataArr[$theField]);
							break;
						case 'invert':
							$this->dataArr[$theField]=$this->dataArr[$theField]?0:1;	
						break;
						case 'random':
							$this->dataArr[$theField] = substr(md5(uniqid(microtime(),1)),0,intval($cmdParts[1]));
						break;
						case 'files':
							$this->processFiles($cmdParts,$theField);
						break;
						case 'setEmptyIfAbsent':
							if (!isset($this->dataArr[$theField]))	{
								$this->dataArr[$theField]='';
							}
						break;
						case 'multiple':
							if (is_array($this->dataArr[$theField]))	{
								$this->dataArr[$theField] = implode(',',$this->dataArr[$theField]);
							}
						break;
						case 'checkArray':
							if (is_array($this->dataArr[$theField]))	{
								reset($this->dataArr[$theField]);
								$val = 0;
								while(list($kk,$vv)=each($this->dataArr[$theField]))	{
									$kk = t3lib_div::intInRange($kk,0);
									if ($kk<=30)	{
										if ($vv)	{
											$val|=pow(2,$kk);
										}
									}
								}
								$this->dataArr[$theField] = $val;
							} else {$this->dataArr[$theField]=0;}
						break;
						case 'uniqueHashInt':
							$otherFields = t3lib_div::trimExplode(';',$cmdParts[1],1);
							$hashArray=array();
							while(list(,$fN)=each($otherFields))	{
								$vv = $this->dataArr[$fN];
								$vv = ereg_replace('[[:space:]]','',$vv);
								$vv = ereg_replace('[^[:alnum:]]','',$vv);
								$vv = strtolower($vv);
								$hashArray[]=$vv;
							}
							$this->dataArr[$theField]=hexdec(substr(md5(serialize($hashArray)),0,8));
						break;
					}
				}
			}
		}
	  // Call to user parse function
		$this->conf['parentObj']=&$this;
		if ($this->conf['userFunc_afterParse']) {
		    t3lib_div::callUserFunction($this->conf['userFunc_afterParse'],$this->conf,$this);
		}
	}


	/**
	 * Remove latin accents ...
	 *
	 * @param	string		string from which to remove accents
	 * @return	string
	 * @access private
	 */


	Function removeaccents($string)   
        {    
	     $string= strtr($string,    
	   "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",   
	   "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");    
	      
	    return $string;    
    	}   
		
	


	/**
	 * Processing of files.
	 * NOTICE: for now files can be handled only on creation of records. But a more advanced feature is that PREVIEW of files is handled.
	 *
	 * @param	array		Array with cmd-parts (from parseValues()). This will for example contain information about allowed file extensions and max size of uploaded files.
	 * @param	string		The fieldname with the files.
	 * @return	void
	 * @access private
	 * @see parseValues()
	 */

	function processFiles($cmdParts,$theField)	{
		// First, make an array with the filename and file reference, whether the file is just uploaded or a preview
		$filesArr = array();

		if (is_string($this->dataArr[$theField]))	{		// files from preview.
			$tmpArr = explode(',',$this->dataArr[$theField]);
			reset($tmpArr);
			while(list(,$val)=each($tmpArr))	{
				$valParts = explode('|',$val);
				$filesArr[] = array (
					'name'=>$valParts[1],
					'tmp_name'=>PATH_site.'typo3temp/'.$valParts[0]
				);
			}
		} elseif (is_array($_FILES['FE'][$this->theTable][$theField]['name']))	{	// Files from upload
			reset($_FILES['FE'][$this->theTable][$theField]['name']);
			while(list($kk,$vv)=each($_FILES['FE'][$this->theTable][$theField]['name']))	{
				if ($vv)	{
					$tmpFile = t3lib_div::upload_to_tempfile($_FILES['FE'][$this->theTable][$theField]['tmp_name'][$kk]);
					if ($tmpFile)	{
						$this->unlinkTempFiles[]=$tmpFile;
						$filesArr[] = array (
							'name'=>$vv,
							'tmp_name'=>$tmpFile
						);
					}
				}
			}
		} elseif (is_array($_FILES['FE']['name'][$this->theTable][$theField]))	{	// Files from upload
			reset($_FILES['FE']['name'][$this->theTable][$theField]);
			while(list($kk,$vv)=each($_FILES['FE']['name'][$this->theTable][$theField]))	{
				if ($vv)	{
					$tmpFile = t3lib_div::upload_to_tempfile($_FILES['FE']['tmp_name'][$this->theTable][$theField][$kk]);
					if ($tmpFile)	{
						$this->unlinkTempFiles[]=$tmpFile;
						$filesArr[] = array (
							'name'=>$vv,
							'tmp_name'=>$tmpFile
						);
					}
				}
			}
		}

		// Then verify the files in that array; check existence, extension and size
		//$this->dataArr[$theField]='';
		//unset($this->dataArr[$theField]);

		$finalFilesArr=array();
		if (count($filesArr))	{
			$extArray = t3lib_div::trimExplode(';',strtolower($cmdParts[1]),1);
			$maxSize = intval($cmdParts[3]);
			reset($filesArr);
			while(list(,$infoArr)=each($filesArr))	{
			   if ($infoArr['name']) {
				$fI = pathinfo($infoArr['name']);
				if (t3lib_div::verifyFilenameAgainstDenyPattern($fI['name']))	{
					if (!count($extArray) || in_array(strtolower($fI['extension']), $extArray))	{
						$tmpFile = $infoArr['tmp_name'];
						if (@is_file($tmpFile))	{
							if (!$maxSize || filesize($tmpFile)<$maxSize*1024)	{
								$finalFilesArr[]=$infoArr;
							} else	{
								debug('Size is beyond '.$maxSize.' kb ('.filesize($tmpFile).' bytes) and the file cannot be saved.');
								$this->failure=1;
								$this->failureMsg[$theField][].=sprintf($this->metafeeditlib->getLL('error_file_size',$this->conf),$maxSize,filesize($tmpFile));
							}

						} else {
							$this->failure=1;
							$this->failureMsg[$theField][].=sprintf($this->metafeeditlib->getLL('error_file_upload',$this->conf),$vv,$tmpFile);
						};
					} else 	{
						$this->failure=1;
						$this->failureMsg[$theField][].=sprintf($this->metafeeditlib->getLL('error_file_extension',$this->conf),$fI['extension']);

					}
				} else	{
					$this->failure=1;
					$this->failureMsg[$theField][].=$this->metafeeditlib->getLL('error_file_pattern',$this->conf);
				}
			    }
			}
		}

		// Copy the files in the resulting array to the proper positions based on preview/non-preview.

		reset($finalFilesArr);
		$fileNameList=array();
		while(list(,$infoArr)=each($finalFilesArr))	{
			if ($this->isPreview())	{		// If the form is a preview form (and data is therefore not going into the database...) do this.
				$this->createFileFuncObj();
				$fI = pathinfo($infoArr['name']);
				$tmpFilename = $this->theTable.'_'.t3lib_div::shortmd5(uniqid($infoArr['name'])).'.'.$fI['extension'];
				$theDestFile = $this->fileFunc->getUniqueName($this->fileFunc->cleanFileName($tmpFilename), PATH_site.'typo3temp/');
				t3lib_div::upload_copy_move($infoArr['tmp_name'],$theDestFile);
				// Setting the filename in the list
				$fI2 = pathinfo($theDestFile);
				$fileNameList[] = $fI2['basename'].'|'.$this->removeaccents($infoArr['name']);
			} else {
				$this->createFileFuncObj();
				//CBY ...$GLOBALS['TSFE']->includeTCA();
				t3lib_div::loadTCA($this->theTable);
				$theF=substr($theField,0,strlen($theField)-5);

				if (is_array($GLOBALS['TCA'][$this->theTable]['columns'][$theF]))	{
					$uploadPath = $GLOBALS['TCA'][$this->theTable]['columns'][$theF]['config']['uploadfolder'];
				}
				if ($uploadPath)	{
					$theDestFile = $this->fileFunc->getUniqueName($this->fileFunc->cleanFileName($infoArr['name']), PATH_site.$uploadPath);
					t3lib_div::upload_copy_move($infoArr['tmp_name'],$theDestFile);
						// Setting the filename in the list
					$fI2 = pathinfo($theDestFile);
					$fileNameList[] = $fI2['basename'];
					$this->filesStoredInUploadFolders[]=$theDestFile;
				}
				else
				{
					$this->failure=1;
                                        $this->failureMsg[$theField][]=$this->metafeeditlib->getLL('error_no_upload_path',$this->conf);
				}
			}
			// Implode the list of filenames
			$this->dataArr[$theField] = implode(',',$fileNameList);
		}
		if ($this->failure) {
			$this->markerArray['###EVAL_ERROR_FIELD_'.$theField.'###'] = is_array($this->failureMsg[$theField]) ? implode('<br />',$this->failureMsg[$theField]) : '';
			$this->markerArray['###EVAL_ERROR###'] = $this->metafeeditlib->getLL('error_occured',$this->conf);
		}	
	}

	/**
	 * Overriding values in $this->dataArr if configured for that in TypoScript ([edit/create].overrideValues)
	 *
	 * @return	void
	 * @see init()
	 */

	function overrideValues()	{
		
		// Addition of overriding values		
		// TODO add check and warnings on non existent fields ...
		
		if (is_array($this->conf[$this->conf['cmdKey'].'.']['overrideValues.']))	{
			reset($this->conf[$this->conf['cmdKey'].'.']['overrideValues.']);
			while(list($theField,$theValue)=each($this->conf[$this->conf['cmdKey'].'.']['overrideValues.']))	{
			
				$FValue=$this->dataArr[$theField];
			
			    //here we handle special values ... 	
				$fieldArr=explode('.',$theValue);
				$data=$FValue;
				$c=count($fieldArr);
				if ($c > 1) {
					$data=$this->cObj->getData($fieldArr[0],0);
					$i=1;
					while ($i<=$c) {
						if (is_object($data)) {
							$data=get_object_vars($data);
						}
						if (is_array($data)) {
							$key=$fieldArr[$i];
							$data=$data[$fieldArr[$i]];
						}
					 	$i++;
					}
		
				} elseif (strpos($theValue,":")) {
					$data=tx_metafeedit_lib::getData($theValue,0,$this->cObj);
					if (!$data) $data=$this->dataArr[$theField];
				} else {
					$data=$theValue;
				}
				if ($data) $this->dataArr[$theField] = $data;
			}
		}
		
		// call to user override function
		if ($this->conf[$this->conf['cmdKey'].'.']['userFunc_afterOverride']) {
		    t3lib_div::callUserFunction($this->conf[$this->conf['cmdKey'].'.']['userFunc_afterOverride'],$this->conf,$this);
		}else if ($this->conf['userFunc_afterOverride']) {
		    t3lib_div::callUserFunction($this->conf['userFunc_afterOverride'],$this->conf,$this);
		}
	}

	/**
	 * Called if there is no input array in $this->dataArr. Then this function sets the default values configured in TypoScript
	 *
	 * @return	void
	 * @see init()
	 */
	function defaultValues(&$conf)	{
			// Addition of default values
		if (is_array($conf[$conf['cmdKey'].'.']['defaultValues.']))	{
			reset($conf[$conf['cmdKey'].'.']['defaultValues.']);
			while(list($theField,$theValue)=each($conf[$conf['cmdKey'].'.']['defaultValues.']))	{
				if (strpos($theValue,":")) {
					$data=tx_metafeedit_lib::getData($theValue,0,$this->cObj);
					if (!$data) $data=$this->dataArr[$theField];
				} else {
					$data=$theValue;
				}
				$this->dataArr[$theField] = $data;
			}
		}
	}

	/**
	 * This will evaluate the input values from $this->dataArr to see if they conforms with the requirements configured in TypoScript per field.
	 * For example this could be checking if a field contains a valid email address, a unique value, a value within a certain range etc.
	 * It will populate arrays like $this->failure and $this->failureMsg with error messages (which can later be displayed in the template). Mostly it does NOT alter $this->dataArr (such parsing of values was done by parseValues())
	 * Works based on configuration in TypoScript key [create/edit].evalValues
	 *
	 * @return	void
	 * @see init(), parseValues()
	 */

	function evalValues()	{
		// Check required, set failure if not ok.
		reset($this->requiredArr);
		$masterTable=$this->conf['blogData']?'tx_metafeedit_comments':$this->theTable;
		$tempArr=array();
		while(list(,$theField)=each($this->requiredArr))	{
			if (!trim($this->dataArr[$theField]) )	{
				if ($this->conf['TCAN'][$this->theTable]['columns'][$theField]['config']['type']=='group' && $this->conf['TCAN'][$this->theTable]['columns'][$theField]['config']['internal_type']=='file')	{
					
					if (!trim($this->dataArr[$theField.'_file']) )	{
					
						$tempArr[]=$theField;
					}
				} else {
					$tempArr[]=$theField;
				}
			}
		}

		// Evaluate: This evaluates for more advanced things than 'required' does. But it returns the same error code, so you must let the required-message tell, if further evaluation has failed!
		$recExist=0;
		$evalValues=$this->conf['blogData']?$this->metafeeditlib->getBlogEvalValues($this->conf):$this->conf[$this->conf['cmdKey'].'.']['evalValues.'];
		if (is_array($evalValues))	{
			switch($this->conf['inputvar.']['cmd'])	{
				case 'edit':
					if (isset($this->dataArr['pid']))	{			// This may be tricked if the input has the pid-field set but the edit-field list does NOT allow the pid to be edited. Then the pid may be false.
						$recordTestPid = intval($this->dataArr['pid']);
					} else {
						$tempRecArr = $GLOBALS['TSFE']->sys_page->getRawRecord($masterTable,$this->dataArr[$this->conf['uidField']]);
						$recordTestPid = intval($tempRecArr['pid'])?intval($tempRecArr['pid']):$this->thePid;
					}
					$recExist=1;
				break;
				default:
					$recordTestPid = $this->thePid ? $this->thePid : t3lib_div::intval_positive($this->dataArr['pid']);
				break;
			}

			reset($evalValues);
			while(list($theField,$theValue)=each($evalValues))	{
				$listOfCommands = t3lib_div::trimExplode(',',$theValue,1);
				while(list(,$cmd)=each($listOfCommands))	{
					$cmdParts = split('\[|\]',$cmd);	// Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
					$theCmd = trim($cmdParts[0]);
					switch($theCmd)	{
						case 'uniqueGlobal':
								if ($DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField($masterTable,$theField,$this->dataArr[$theField],'','','','1'))	{
								if (!$recExist || $DBrows[0][$this->conf['uidField']]!=$this->dataArr[$this->conf['uidField']])	{	// Only issue an error if the record is not existing (if new...) and if the record with the false value selected was not our self.
									//$tempArr[]=$theField;
									$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_value_existed',$this->conf));
								}
							}
						break;
						case 'uniqueFields':

					 		$i=0;	
							foreach($cmdParts as $cmdP) {
								if ($i>0 && trim($cmdP)) $Where.= " and ".$cmdParts[$i]."='".$this->dataArr[$cmdParts[$i]]."'";
								$i++;
							}

							if ($DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField($masterTable,$theField,$this->dataArr[$theField], 'AND pid IN ('.$recordTestPid.')'.$Where,'','','1'))	{
								if (!$recExist || $DBrows[0][$this->conf['uidField']]!=$this->dataArr[$this->conf['uidField']])	{	// Only issue an error if the record is not existing (if new...) and if the record with the false value selected was not our self.
									//$tempArr[]=$theField;
									$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_mvalue_existed',$this->conf));
								}
							}
						break;
						case 'uniqueLocal':
						case 'uniqueInPid':
							if ($DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField($masterTable,$theField,$this->dataArr[$theField], 'AND pid IN ('.$recordTestPid.')','','','1'))	{
								if (!$recExist || $DBrows[0][$this->conf['uidField']]!=$this->dataArr[$this->conf['uidField']])	{	// Only issue an error if the record is not existing (if new...) and if the record with the false value selected was not our self.
									//$tempArr[]=$theField;
									$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_value_existed',$this->conf));
								}
							}
						break;
						case 'twice':
							if (strcmp($this->dataArr[$theField], $this->dataArr[$theField.'_again']))	{
								$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_value_twice',$this->conf));
							}
						break;
						case 'email':
							if (trim($this->dataArr[$theField])) {
								if (!$this->cObj->checkEmail($this->dataArr[$theField]))	{
									$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd,  $this->metafeeditlib->getLL('error_valid_email',$this->conf));
								}
							}
						break;
						case 'required':
							if (!trim($this->dataArr[$theField]))	{
								if ($this->conf['TCAN'][$this->theTable]['columns'][$theField]['config']['type']=='group' && $this->conf['TCAN'][$this->theTable]['columns'][$theField]['config']['internal_type']=='file')	{
									if (!trim($this->dataArr[$theField.'_file']) )	{
										$tempArr[]=$theField;
										$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_value_required',$this->conf));

									}
								} else {
									$tempArr[]=$theField;
									$this->failureMsg[$theField][] = $this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_value_required',$this->conf));
								}
								//print_r($this->dataArr);
							}
						break;
						case 'atLeast':
							$chars=intval($cmdParts[1]);
							if (strlen($this->dataArr[$theField])<$chars)	{
								//$tempArr[]=$theField;
								$this->failureMsg[$theField][] = sprintf($this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_min_char',$this->conf)), $chars);
							}
						break;
						case 'invert':
							$this->dataArr[$theField]=$this->dataArr[$theField]?0:1;	
						break;
						case 'atMost':
							$chars=intval($cmdParts[1]);
							if (strlen($this->dataArr[$theField])>$chars)	{
								//$tempArr[]=$theField;
								$this->failureMsg[$theField][] = sprintf($this->getFailure($theField, $theCmd,  $this->metafeeditlib->getLL('error_max_char',$this->conf)), $chars);
							}
						break;
						case 'inBranch':
							$pars = explode(';',$cmdParts[1]);
							if (intval($pars[0]))	{
								$pid_list = $this->cObj->getTreeList(
									intval($pars[0]),
									intval($pars[1]) ? intval($pars[1]) : 999,
									intval($pars[2])
								);
								if (!$pid_list || !t3lib_div::inList($pid_list,$this->dataArr[$theField]))	{
									//$tempArr[]=$theField;
									$this->failureMsg[$theField][] = sprintf($this->getFailure($theField, $theCmd, $this->metafeeditlib->getLL('error_value_notInList',$this->conf)), $pid_list);
								}
							}
						break;
						case 'unsetEmpty':
							if (!$this->dataArr[$theField])	{
								$hash = array_flip($tempArr);
								unset($hash[$theField]);
								$tempArr = array_keys($hash);
								unset($this->failureMsg[$theField]);
								unset($this->dataArr[$theField]);	// This should prevent the field from entering the database.
							}
						break;
						//should go in parse values ?
						case 'wwwURL':
								if ($this->dataArr[$theField]) {
										$wwwURLOptions = array (
										'AssumeProtocol' => 'http' ,
												'AllowBracks' => TRUE ,
												'AllowedProtocols' => array(0 => 'http', 1 => 'https', ) ,
												'Require' => array('Protocol' => FALSE , 'User' => FALSE , 'Password' => FALSE , 'Server' => TRUE , 'Resource' => FALSE , 'TLD' => TRUE , 'Port' => FALSE , 'QueryString' => FALSE , 'Anchor' => FALSE , ) ,
												'Forbid' => array('Protocol' => FALSE , 'User' => TRUE , 'Password' => TRUE , 'Server' => FALSE , 'Resource' => FALSE , 'TLD' => FALSE , 'Port' => TRUE , 'QueryString' => FALSE , 'Anchor' => FALSE , ) ,
												);
										$wwwURLResult = tx_metafeedit_srfeuserregister_pi1_urlvalidator::_ValURL($this->dataArr[$theField], $wwwURLOptions);
										if ($wwwURLResult['Result'] = 'EW_OK' ) {
												$this->dataArr[$theField] = $wwwURLResult['Value'];
										}
								}
								break;
					}
				}
				$this->markerArray['###EVAL_ERROR_FIELD_'.$theField.'###'] = is_array($this->failureMsg[$theField]) ? implode('<br />',$this->failureMsg[$theField]) : '';
			}
		}
		//$this->failure=implode(',',$tempArr);	 //$failure will show which fields were not OK
		if (count($this->failureMsg) >0) {
			$this->failure=1;
			if (count($tempArr)) $this->failure=implode(',',$tempArr);
			$this->markerArray['###EVAL_ERROR###'] = $this->metafeeditlib->getLL('error_occured',$this->conf);
		} else { 
			$this->failure=0;
			if (count($tempArr)) {
			$this->failure=implode(',',$tempArr);
			$this->markerArray['###EVAL_ERROR###'] =  $this->getFailure('_FORM', '_REQUIRED', $this->metafeeditlib->getLL('error_required',$this->conf)); //$this->metafeeditlib->getLL('error_required',$this->conf);
			}
		}
		
		// Call to user eval function

		
		if ($this->conf['userFunc_afterEval']) t3lib_div::callUserFunction($this->conf['userFunc_afterEval'],$this->conf,$this);

	}

	/**
	 * Performs user processing of input array - triggered right after the function call to evalValues() IF TypoScript property "evalFunc" was set.
	 *
	 * @param	string		Key pointing to the property in TypoScript holding the configuration for this processing (here: "evalFunc.*"). Well: at least its safe to say that "parentObj" in this array passed to the function is a reference back to this object.
	 * @param	array		The $this->dataArr passed for processing
	 * @return	array		The processed $passVar ($this->dataArr)
	 * @see init(), evalValues()
	 */
	function userProcess($mConfKey,$passVar)	{
		if ($this->conf[$mConfKey])	{
			$funcConf = $this->conf[$mConfKey.'.'];
			$funcConf['parentObj']=&$this;
			$passVar = $GLOBALS['TSFE']->cObj->callUserFunction($this->conf[$mConfKey], $funcConf, $passVar);
		}
		return $passVar;
	}

	/**
	 * User processing of contnet
	 *
	 * @param	string		Value of the TypoScript object triggering the processing.
	 * @param	array		Properties of the TypoScript object triggering the processing. The key "parentObj" in this array is passed to the function as a reference back to this object.
	 * @param	mixed		Input variable to process
	 * @return	mixed		Processed input variable, $passVar
	 * @see userProcess(), save(), modifyDataArrForFormUpdate()
	 */
	function userProcess_alt($confVal,$confArr,$passVar)	{

		if ($confVal)	{
			$funcConf = $confArr;
			$funcConf['parentObj']=&$this;
			$passVar = $GLOBALS['TSFE']->cObj->callUserFunction($confVal, $funcConf, $passVar);
		}
		return $passVar;
	}






	/*****************************************
	 *
	 * Database manipulation functions
	 *
	 *****************************************/

	/**
	 * Performs the saving of records, either edited or created.
	 *
	 * @return	void
	 * @see init()
	 */
	function save(&$conf)	{
		switch($conf['inputvar.']['cmd'])	{
			case 'edit':
				if ($conf['blogData']) {
					$this->dataArr['remote_addr'] = $_SERVER['REMOTE_ADDR'];
					$newFieldList=$conf['blog.']['show_fields']?$conf['blog.']['show_fields']:'firstname,surname,email,homepage,place,entry,entrycomment,linked_row,remote_addr';
					$this->cObj->DBgetInsert('tx_metafeedit_comments', $this->thePid, $this->dataArr, $newFieldList, TRUE);
					//MODIF CBY
					if ($conf['debug.']['sql']) 
							$conf['debug.']['debugString'].="<br/>INSERT SQL <br/>".$this->cObj->DBgetInsert('tx_metafeedit_comments', $this->thePid, $this->dataArr, $newFieldList, FALSE);
					$this->saved=1;
					break;
				} 
			
				$theUid = $this->dataArr[$conf['uidField']];
				$this->markerArray['###REC_UID###'] = $theUid;
				$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$theUid);		// Fetches the original record to check permissions
				if ($conf['edit'] && ($GLOBALS['TSFE']->loginUser || !$conf['requireLogin'] || $this->aCAuth($origArr)))	{	// Must be logged in in order to edit  (OR be validated by email) or requireLogin is unchecked
					$newFieldList = implode(',',array_intersect(explode(',',$conf['fieldList']),t3lib_div::trimExplode(',',$conf['edit.']['show_fields'],1)));


					if ($this->aCAuth($origArr) || $this->metafeeditlib->DBmayFEUserEdit($this->theTable,$origArr,$GLOBALS['TSFE']->fe_user->user,$conf['allowedGroups'],$conf['fe_userEditSelf'],$conf))	{
						$this->cObj->DBgetUpdate($this->theTable, $theUid, $this->dataArr, $newFieldList, TRUE);

						//MODIF CBY
						if ($conf['debug.']['sql']) 
							$conf['debug.']['debugString'].="<br/>UPDATE SQL <br/>".$this->cObj->DBgetUpdate($this->theTable, $theUid, $this->dataArr, $newFieldList, FALSE);
						$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$theUid);



						$this->userProcess_alt($conf['edit.']['userFunc_afterSave'],$conf['edit.']['userFunc_afterSave.'],array('rec'=>$this->currentArr, 'origRec'=>$origArr));
						$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$theUid);

						$this->saved=1;
					} else {
						$this->error='###TEMPLATE_NO_PERMISSIONS###';
					}
				} else {
					$this->error='###TEMPLATE_NO_PERMISSIONS###';
				}
			break;
			default:
				if ($conf['create'])	{
						
					$newFieldList = implode(',',array_intersect(explode(',',$conf['fieldList']),t3lib_div::trimExplode(',',$conf['create.']['fields'],1)));
					$this->cObj->DBgetInsert($this->theTable, $this->thePid, $this->dataArr, $newFieldList, TRUE);
					//MODIF CBY
					if ($conf['debug.']['sql']) 
							$conf['debug.']['debugString'].="<br/>INSERT SQL <br/>".$this->cObj->DBgetInsert($this->theTable, $this->thePid, $this->dataArr, $newFieldList, FALSE);
					$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
					$this->recUid=$newId;
					$conf['recUid']=$this->recUid;
					$this->markerArray['###REC_UID###'] = $this->recUid;

					if ($this->theTable=='fe_users' && $conf['fe_userOwnSelf'])	{		// enables users, creating logins, to own them self.
						$extraList='';
						$dataArr = array();
						if ($GLOBALS['TCA'][$this->theTable]['ctrl']['fe_cruser_id'])		{
							$field=$GLOBALS['TCA'][$this->theTable]['ctrl']['fe_cruser_id'];
							$dataArr[$field]=$newId;
							$extraList.=','.$field;
						}
						if ($GLOBALS['TCA'][$this->theTable]['ctrl']['fe_crgroup_id'])	{
							$field=$GLOBALS['TCA'][$this->theTable]['ctrl']['fe_crgroup_id'];
							list($dataArr[$field])=explode(',',$this->dataArr['usergroup']);
							$dataArr[$field]=intval($dataArr[$field]);
							$extraList.=','.$field;

						}
						if (count($dataArr))	{
					
							$this->cObj->DBgetUpdate($this->theTable, $newId, $dataArr, $extraList, TRUE);
							//MODIF CBY
							if ($conf['debug.']['sql']) 
								$conf['debug.']['debugString'].="<br/>UPDATE SQL <br/>".$this->cObj->DBgetUpdate($this->theTable, $newId, $dataArr, $extraList, FALSE);
						}
					}

					$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$newId);
					$this->userProcess_alt($conf['create.']['userFunc_afterSave'],$conf['create.']['userFunc_afterSave.'],array('rec'=>$this->currentArr));
					$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$newId);
					$this->saved=1;
				}
			break;
		}
	}




	/**
	* Update MM relations
	*
	* @return void
	*/
	function updateMMRelations($origArr = array()) {
		global $TYPO3_DB;
		// update the MM relation
		$fieldsList = array_keys($origArr);
		foreach ($GLOBALS['TCA'][$this->theTable]['columns'] as $colName => $colSettings) {
			if (in_array($colName, $fieldsList) && $colSettings['config']['type'] == 'select' && $colSettings['config']['MM']) {
				$valuesList = $origArr[$colName];
				if ($valuesList) {
					$res = $TYPO3_DB->exec_DELETEquery($colSettings['config']['MM'], 'uid_local='.intval($origArr['uid']));
					$valuesArray = explode(',', $valuesList);
					reset($valuesArray);
					$insertFields = array();
					$insertFields['uid_local'] = intval($origArr['uid']);
					$insertFields['tablenames'] = '';
					$insertFields['sorting'] = 0;
					while (list(, $theValue) = each($valuesArray)) {
						$insertFields['uid_foreign'] = intval($theValue);
						$insertFields['sorting']++;
						$res = $TYPO3_DB->exec_INSERTquery($colSettings['config']['MM'], $insertFields);
					}
				}
			}
		}
	}	// updateMMRelations




	function saveGrid(&$conf,$row,$col,$dataArr,$sqlMode,$secondaryCols=array())	{
        $table=$conf['table'];    
    	// we handle sub columns here ...Must do better checks here	
        if (count($secondaryCols)) {
        	//$this->dataArr[$conf['grid.']['secondaryColFields']]=$secondaryCols[0];
        	$dataArr[$conf['grid.']['secondaryColFields']]=$secondaryCols[0];
      	}
        $dataArr[$conf['grid.']['rowField']]=$row;
        $dataArr[$conf['grid.']['colField']]=$col;
        
		switch($sqlMode)	{
			case 'update':				 					
				$theUid = $dataArr[$conf['uidField']];
				$this->markerArray['###REC_UID###'] = $theUid;
				$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$theUid);		// Fetches the original record to check permissions
				if ($conf['edit'] && ($GLOBALS['TSFE']->loginUser || !$conf['requireLogin'] || $this->aCAuth($origArr)))	{	// Must be logged in in order to edit  (OR be validated by email) or requireLogin is unchecked
					$newFieldList = implode(',',array_unique(array_merge(array_merge(explode(',',$conf['fieldList']),t3lib_div::trimExplode(',',$conf['edit.']['fields'],1)),t3lib_div::trimExplode(',',$conf['grid.']['show_fields'],1))));			
    			//MODIF CBY
    			if (count($secondaryCols)) $newFieldList.=','.$conf['grid.']['secondaryColFields'];					
					if ($this->aCAuth($origArr) || $this->metafeeditlib->DBmayFEUserEdit($table,$origArr,$GLOBALS['TSFE']->fe_user->user,$conf['allowedGroups'],$conf['fe_userEditSelf'],$conf))	{

						$sql=$this->cObj->DBgetUpdate($table, $theUid, $dataArr, $newFieldList, TRUE);
						//MODIF CBY
				    if ($conf['debug.']['sql']) 
							$conf['debug.']['debugString'].="<br/>GRID UPDATE SQL <br/>".$this->cObj->DBgetUpdate($table, $theUid, $dataArr, $newFieldList, FALSE);   
						$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$theUid);
						$this->userProcess_alt($conf['edit.']['userFunc_afterSave'],$conf['edit.']['userFunc_afterSave.'],array('rec'=>$this->currentArr, 'origRec'=>$origArr));
						$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$theUid);
						$this->saved=1;
					} else {
						$this->error='###TEMPLATE_NO_PERMISSIONS###';
					}
				} else {
					$this->error='###TEMPLATE_NO_PERMISSIONS###';
				}
			break;
			default:
				if ($conf['create'])	{
					//$newFieldList = implode(',',array_intersect(explode(',',$conf['fieldList']),t3lib_div::trimExplode(',',$conf['create.']['fields'],1)));
					$newFieldList = implode(
						',',
							array_unique(
								array_merge(
									array_merge(
										array_merge(
											t3lib_div::trimExplode(',',$conf['grid.']['extraFields'],1),
											explode(',',$conf['fieldList'])
										),
										t3lib_div::trimExplode(',',$conf['edit.']['fields'],1)
									),
									t3lib_div::trimExplode(',',$conf['grid.']['show_fields'],1)
								)
							)
						);	
    			//MODIF CBY
    			if (count($secondaryCols)) $newFieldList.=','.$conf['grid.']['secondaryColFields'];					
					$this->cObj->DBgetInsert($table, $this->thePid, $dataArr, $newFieldList, TRUE);
					//MODIF CBY
				  if ($conf['debug.']['sql']) 
							$conf['debug.']['debugString'].="<br/>GRID INSERT SQL <br/>".$this->cObj->DBgetInsert($table, $this->thePid, $dataArr, $newFieldList, FALSE);
;   
					$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
					$conf['recUid']=$newId;
					$this->markerArray['###REC_UID###'] = $newId;

					if ($table=='fe_users' && $conf['fe_userOwnSelf'])	{		// enables users, creating logins, to own them self.
						$extraList='';
						$dataArr = array();
						if ($conf['TCAN'][$table]['ctrl']['fe_cruser_id'])		{
							$field=$conf['TCAN'][$table]['ctrl']['fe_cruser_id'];
							$dataArr[$field]=$newId;
							$extraList.=','.$field;
						}
						if ($conf['TCAN'][$table]['ctrl']['fe_crgroup_id'])	{
							$field=$conf['TCAN'][$table]['ctrl']['fe_crgroup_id'];
							list($dataArr[$field])=explode(',',$dataArr['usergroup']);
							$dataArr[$field]=intval($dataArr[$field]);
							$extraList.=','.$field;

						}
						if (count($dataArr))	{
					
							$this->cObj->DBgetUpdate($table, $newId, $dataArr, $extraList, TRUE);
				    	if ($conf['debug.']['sql']) 
								$conf['debug.']['debugString'].="<br/>GRID UPDATE SQL <br/>".$this->cObj->DBgetUpdate($table, $newId, $dataArr, $extraList, FALSE);   
						}
					}
					$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$newId);
					$this->userProcess_alt($conf['create.']['userFunc_afterSave'],$conf['create.']['userFunc_afterSave.'],array('rec'=>$this->currentArr));
					$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$newId);
					$this->saved=1;
				}
			break;
		}
	}
	/**
	 * Deletes the record from table/uid, $this->theTable/$this->recUid, IF the fe-user has permission to do so.
	 * If the deleted flag should just be set, then it is done so. Otherwise the record truely is deleted along with any attached files.
	 * Called from init() if "cmd" was set to "delete" (and some other conditions)
	 *
	 * @return	string		void
	 * @see init()
	 */
	function deleteRecord()	{
		if ($this->conf['delete'])	{	// If deleting is enabled
	
			$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,  $this->recUid);
			if (($GLOBALS['TSFE']->loginUser &&  $this->conf['requireLogin']) || ( $this->aCAuth($origArr)&& $this->conf['requireLogin'])||!$this->conf['requireLogin'])	{	// Must be logged in OR be authenticated by the aC code in order to delete
					// If the recUid selects a record.... (no check here)
				if (is_array($origArr))	{
					if ($this->aCAuth($origArr) || $this->metafeeditlib->DBmayFEUserEdit($this->theTable,$origArr, $GLOBALS['TSFE']->fe_user->user,$this->conf['allowedGroups'],$this->conf['fe_userEditSelf'],$this->conf))	{	// Display the form, if access granted.
						if (!$GLOBALS['TCA'][$this->theTable]['ctrl']['delete'])	{	// If the record is fully deleted... then remove the image (or any file) attached.
							$this->deleteFilesFromRecord($this->recUid);
						}
						$this->cObj->DBgetDelete($this->theTable, $this->recUid, TRUE);
						$this->currentArr = $origArr;
						$this->saved = 1;
					} else {
						$this->error = '###TEMPLATE_NO_PERMISSIONS###';
					}
				}
			} else {
						$this->error = '###TEMPLATE_NO_PERMISSIONS###';
		 }
		}
	}

	/**
	 * Deletes the files attached to a record and updates the record.
	 * Table/uid is $this->theTable/$uid
	 *
	 * @param	integer		Uid number of the record to delete from $this->theTable
	 * @return	void
	 * @access private
	 * @see deleteRecord()
	 */
	function deleteFilesFromRecord($uid)	{
		$table = $this->theTable;
		$rec = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$uid);

		//$GLOBALS['TSFE']->includeTCA();
		t3lib_div::loadTCA($table);
		reset($GLOBALS['TCA'][$table]['columns']);
		$iFields=array();
		while(list($field,$conf)=each($GLOBALS['TCA'][$table]['columns']))	{
			if ($conf['config']['type']=='group' && $conf['config']['internal_type']=='file')	{

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $this->conf['uidField'].'='.intval($uid), array($field => ''));

				$delFileArr = explode(',',$rec[$field]);
				reset($delFileArr);
				while(list(,$n)=each($delFileArr))	{
					if ($n)	{
						$fpath = $conf['config']['uploadfolder'].'/'.$n;
						unlink($fpath);
					}
				}
			}
		}
	}

	/**
	 * Order the record from table/uid, $this->theTable/$this->recUid, IF the fe-user has permission to do so.
	 * Called from init() if "cmd" was set to "order" (and some other conditions)
	 *
	 * @return	string		void
	 * @see init()
	 */
	function orderRecord()	{
		if (($GLOBALS['TSFE']->loginUser &&  $this->conf['requireLogin']) || ( $this->aCAuth($origArr)&& $this->conf['requireLogin'])||!$this->conf['requireLogin'])	{	// Must be logged in OR be authenticated by the aC code in order to reorder
			//on instancie la tce_main
			$tce_main=t3lib_div::makeInstance('t3lib_TCEmain');

			//récup de l'ordre dans lequel on trie
			if ($this->conf['inputvar.']['orderDir.']['dir']=='up') $toUp = true;
			else $toUp = false;

			//réup de l'uid a modifier
			$rU = $this->conf['inputvar.']['orderDir.']['rU'];

			//récup de la colonne de delete si existe et de la colonne de hidden si existe.
			$deletedField = isset($this->conf['TCAN'][$this->theTable]['ctrl']['delete'])?$this->conf['TCAN'][$this->theTable]['ctrl']['delete']:'';
			$hiddenField = isset($this->conf['TCAN'][$this->theTable]['ctrl']['enablecolumns']['disabled'])?$this->conf['TCAN'][$this->theTable]['ctrl']['enablecolumns']['disabled']:'';

			// on récupère l'enregistrement suivant dans l'ordre du sorting
			$fields = $this->theTable.'1.'.$this->conf['uidField']." as 'nextUid'";
			$tables = $this->theTable.' as '.$this->theTable.'1, '.$this->theTable.' as '.$this->theTable.'2';
			$where = '1';
			$where.= ($deletedField!=''?' and '.$this->theTable.'1.deleted=0 and '.$this->theTable.'2.deleted=0':''); //deleted clause
			$where.= ($hiddenField!=''?' and '.$this->theTable.'1.hidden=0 and '.$this->theTable.'2.hidden=0':''); //hidden clause
			$where.= ' and '.$this->theTable.'1.sorting '.($toUp?'<':'>').' '.$this->theTable.'2.sorting';
			$where.= ' and '.$this->theTable.'2.'.$this->conf['uidField'].'='.$rU;
			$order = $this->theTable.'1.sorting '.($toUp?'DESC':'ASC');
			$limit = ($toUp?'1':'0').',1';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $tables, $where, '', $order, $limit);
			//si on trouve un résultat (on est donc pas sur le premier second enregistrement ni sur le dernier)
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) >= 1) {
				$occ = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				//recherche du nouveau sorting
				$arrSort = $tce_main->getSortNumber($this->theTable, $rU, '-'.$occ['nextUid']);
				//on met à jour
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->theTable, $this->conf['uidField'].'='.$rU, array('sorting' => $arrSort['sortNumber']));
			} elseif ($toUp) {
				//si on cherche à faire monter l'enregistrement et qu'on est pas au milieu de la liste
				//on change les limites
				$limit = '0,1';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $tables, $where, '', $order, $limit);
				//si on a une line on est sur le second enregistrement
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res) >= 1) {
					//recherche du nouveau sorting
					$arrSort = $tce_main->getSortNumber($this->theTable, $rU, $this->conf['pid']);
					//on met à jour
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->theTable, $this->conf['uidField'].'='.$rU, array('sorting' => $arrSort['sortNumber']));
				}
			}
		} else {
					$this->error = '###TEMPLATE_NO_PERMISSIONS###';
		}
	}

	/*****************************************
	 *
	 * Command "display" functions
	 *
	 *****************************************/

	/**
	 * Creates the preview display of delete actions
	 *
	 * @return	string		HTML content
	 * @see init()
	 */
	function displayDeleteScreen()	{
		$pluginId=$conf['pluginId'];
		if ($this->conf['delete'])	{	// If deleting is enabled
			$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,  $this->recUid);
						
			if (($GLOBALS['TSFE']->loginUser &&  $this->conf['requireLogin']) || ($this->aCAuth($origArr) && $this->conf['requireLogin']) || !$this->conf['requireLogin'])	{	// Must be logged in OR be authenticated by the aC code in order to delete
					// If the recUid selects a record.... (no check here)
					
				if (is_array($origArr))	{
				
					if ($this->aCAuth($origArr) || $this->metafeeditlib->DBmayFEUserEdit($this->theTable,$origArr, $GLOBALS['TSFE']->fe_user->user,$this->conf['allowedGroups'],$this->conf['fe_userEditSelf'],$this->conf))	{	// Display the form, if access granted.
						$this->markerArray['###HIDDENFIELDS###'].= '<input type="hidden" name="rU['.$pluginId.']" value="'.$this->recUid.'" />';
						$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_DELETE_PREVIEW###', $origArr);
					} else {	// Else display error, that you could not edit that particular record...
						$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_NO_PERMISSIONS###');
					}
				}
			} else {	// Finally this is if there is no login user. This must tell that you must login. Perhaps link to a page with create-user or login information.
				$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_AUTH###');
			}
		} else {
			$content.='Delete-option is not set in TypoScript';
		}
		return $content;
	}

	/**
	 * Creates the "create" screen for records
	 *
	 * @return	string		HTML content
	 * @see init()
	 */
	function displayCreateScreen(&$conf)	{
		if ($conf['create'] && !$conf['disableCreate'])	{
			$pluginId=$conf['pluginId'];
			$templateCode = $this->cObj->getSubpart($this->templateCode, ((!$GLOBALS['TSFE']->loginUser||$conf['create.']['noSpecialLoginForm'])?'###TEMPLATE_CREATE'.$this->previewLabel.'###':'###TEMPLATE_CREATE_LOGIN'.$this->previewLabel.'###'));
			$failure = t3lib_div::_GP('noWarnings')?'':$this->failure;
			if (!$failure)	$templateCode = $this->cObj->substituteSubpart($templateCode, '###SUB_REQUIRED_FIELDS_WARNING###', '');
			$templateCode = $this->removeRequired($templateCode,$failure);
			$this->metafeeditlib->setCObjects($conf,$this->markerArray,$templateCode);
			$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $this->dataArr, '', TRUE, 'FIELD_', FALSE);
			// Modif CBY to clear unset fields
			$fArr=t3lib_div::trimexplode(',',$this->conf['fieldList']);
    	foreach($fArr as $fN) {
    		if (!$this->dataArr[$fN]) $markerArray['###FIELD_'.$fN.'###']='';
			}
			//$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $this->dataArr, '', TRUE, 'FIELD_', $conf['general.']['xhtml']);
			if ($conf['create.']['preview'] && !$this->previewLabel)	{$markerArray['###HIDDENFIELDS###'].= '<input type="hidden" name="preview['.$pluginId.']" value="1" />';}
			$content = $this->cObj->substituteMarkerArray($templateCode, $markerArray);
			$content.=$this->cObj->getUpdateJS($this->modifyDataArrForFormUpdate($this->dataArr), $this->theTable.'_form', 'FE['.$this->theTable.']', $conf['fieldList'].$this->additionalUpdateFields);
		} else { die('PLUGIN META FEEDIT : Create mode not allowed !');}
		return $content;
	}
	/**
	 * PrepareListTemplates
	 *
	 * @param	[type]		$TABLES: ...
	 * @param	[type]		$markerArray
	 * @return	[type]		...
	 */
	 
	 function prepareListTemplates(&$conf,&$markerArray,$exporttype) {
		// template blocks
		$tpl=array();
		$tpl['templateCode'] = $this->metafeeditlib->getPlainTemplate($conf,$markerArray,'###TEMPLATE_EDITMENU'.($exporttype?'_'.$exporttype:'').'###');
		$tpl['allItemsCode'] = $this->cObj->getSubpart($tpl['templateCode'], '###ALLITEMS###');

		$tpl['GBCode'] = $this->cObj->getSubpart($tpl['allItemsCode'], '###GROUPBYFIELDS###');
		$tpl['GBFCode']=$this->cObj->getSubpart($tpl['allItemsCode'], '###GROUPBYFOOTERFIELDS###');
		$tpl['allItemsCode'] = $this->cObj->substituteSubpart($tpl['allItemsCode'], '###GROUPBYFIELDS###','');
		$tpl['allItemsCode'] = $this->cObj->substituteSubpart($tpl['allItemsCode'], '###GROUPBYFOOTERFIELDS###','');


		if ($dispDir=='Down') {
			$tpl['itemColCode'] = $this->cObj->getSubpart($tpl['allItemsCode'], '###ITEM-COL###');
			$tpl['itemCode'] = $this->cObj->getSubpart($tpl['itemColCode'], '###ITEM###');
		} else {
			$tpl['itemRowCode'] = $this->cObj->getSubpart($tpl['allItemsCode'], '###ITEM-ROW###');
			$tpl['itemCode'] = $this->cObj->getSubpart($tpl['itemRowCode'], '###ITEM###');

			// backward compatibility
			if (!$tpl['itemCode']) {
				$tpl['itemCode'] = $this->cObj->getSubpart($tpl['templateCode'], '###ITEM###');
			}
		}
		$tpl['itemElCode'] = $this->cObj->getSubpart($tpl['itemCode'], '###ITEM-EL###');
		$tpl['itemSumCode'] = $this->cObj->getSubpart($tpl['templateCode'], '###SUM_FIELDS###');
		return $tpl;
	}



    /**
    * Displays List Screens, Excel File, Pdf Page ...
    *
    * @param	[type]		$TABLES: ...
    * @param	[type]		$
    * @return	[type]		...
    */
	
	function displayListScreen($TABLES,$DBSELECT,&$conf)	{
		if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc displaylist start:']=$this->metafeeditlib->displaytime()." Seconds";
		//-- initialisation
		$conf['cmdmode']='list';
		$content='';
		$DEBUG='';
		$dispDir= $conf['list.']['displayDirection']?$conf['list.']['displayDirection']:'Right'; //-- this should be handled in template choice...
		//global $TCA;
		//$pluginId=$conf['pluginId'];
		//---  Gestion des templates selon exporttype (can't this be done in Pi1 ?)
		$exporttype=$this->piVars['exporttype'];
		
		if (($exporttype == 'PDF') && ($conf['list.']['nbCols']))
		{	$exporttype = "PDFTAB";		}
		//---
		
		//$this->markerArray['###BACK_URL###'] = "";
        // return navigation ... should this be not handled in template ... hmm ...
		if (!$conf['no_action']) {
			$this->backURL=$this->metafeeditlib->makeBackURLTypoLink($conf,$this->backURL);
			if (!strpos($this->backURL,'?')) $this->backURL.='?';
			$this->markerArray['###BACK_URL###'] = $this->backURL;
		}
		
		// TOBE replaced by specific pagebrowser 
	    if ($conf['list.']['pageSize']) $this->internal['results_at_a_time'] = $conf['list.']['pageSize']; // Number of results to show in a listing.
		if ($conf['list.']['pageSize']&& $conf['list.']['nbCols']) $this->internal['results_at_a_time'] = $conf['list.']['pageSize']*$conf['list.']['nbCols'];
	    if ($conf['list.']['maxPages']) $this->internal['maxPages'] = $conf['list.']['maxPages']; // The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
	    $this->internal['currentTable'] = $this->theTable;
		//-- end init...
		
		// We build the SQL Query
		$sql=$this->metafeeditlib->getListSQL($TABLES,$DBSELECT,$conf,$this->markerArray,$DEBUG);
		//TODO : we get sort variable and sort direction from GET/POST Vars (should this be not done in Pi1 ?)...hmm ...

		$Arr=explode(',',$conf['list.']['show_fields']);
		if ($conf['list.']['sortFields']){
    	foreach($Arr as $fieldName) {
				$this->markerArray['###SORT_CLASS_'.$fieldName.'###']=($fieldName==$this->internal['orderBy'])?($this->internal['descFlag']?'mfedt_sortdesc':'mfedt_sortasc'):'NOSORT';
				$this->markerArray['%23%23%23SORT_DIR_'.$fieldName.'%23%23%23']=($fieldName==$this->internal['orderBy'])?($this->internal['descFlag']?'0':'1'):'1';
			}
		}
        //-- end sort calculations
        //-- page calculations
		// We handle pagination here and should just do a count here ...
		
		$cols=$conf['list.']['nbCols'];
		$ps=$conf['list.']['pageSize'];

		// default page size is 100
 
		if (!$ps) $ps=100;
		$ncols=1;
		$nbdispcols=0;
		if ($cols) $ncols=$cols;

		$nbe=$ncols; // number of elements per column (Down)/row (RIGHT) 
		$nbep=$ps*$ncols; //Number of elements per page
		if ($conf['list.']['jumpPageOnGroupBy']) $nbe=$nbep;
		if ($dispDir=='Down') {
			$nbdispcols=$ncols;
			$ncols=$cols=1;
			$nbe=$ps;
		}
        //-- end page calculations		
		// This counts the number of lines ...

		//TODO add distinct or not through flexform ...

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having']);
	    if ($conf['debug.']['sql']) $this->metafeeditlib->debug('displayList row count ',$GLOBALS['TYPO3_DB']->SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having']),$DEBUG);
		$num=$GLOBALS['TYPO3_DB']->sql_num_rows($res);	// If there are menu-items ...

		$NBSup=0; // Number of empty elements for page breaks and group by breaks;
		$pageidx=0;
		$pi=1; //page number we start at 1
		$pagedelta=array(); // array of page deltas for page browser
		$paged=0; // current page delta due to page and groupby breaks
		$oldpage=0; // pagechangeflag
		$pageid=1; // page number for grouby nb elts
		$mode=0;
		$NbGroupBys=0;

		// CALENDAR_SETUP
		if ($conf['list.']['calendarSearch']){			
					  $cal=t3lib_div::makeInstance('tx_metafeedit_calendar');
					  $cal->init($this->metafeeditlib);
					  $cal->cObj=$this->cObj;
					  $this->markerArray['###CALENDAR_SEARCH###']=$cal->main('', $conf,$sql);
		}
		
		// Group by calculations
		
		if ($sql['groupBy']) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('mod(count(*),'.$nbe.') as moduloelts, count(*) as nbelts, ceiling(count(*)/'.$nbep.') as pages'.$sql['gbFields'].$sql['calcFieldsSql'].($sql['groupBy']?','.$conf['table'].'.*':''), $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql']);
			//$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('mod(count(*),'.$nbe.') as moduloelts, count(*) as nbelts, ceiling(count(*)/'.$nbep.') as pages'.$sql['gbFields'].$sql['calcFieldsSql'].($sql['groupBy']?','.$conf['table'].'.*':''), $sql['fromTables'], '1 '.$sql['where'].$sql['orderBySql']);
	  	if ($conf['debug.']['sql']) $this->metafeeditlib->debug('displayList group by row count ',$GLOBALS['TYPO3_DB']->SELECTquery('mod(count(*),'.$nbe.') as moduloelts, count(*) as nbelts, ceiling(count(*)/'.$nbep.') as pages'.$sql['gbFields'].$sql['calcFieldsSql'].($sql['groupBy']?','.$conf['table'].'.*':''), $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql']),$DEBUG);

			$GBTA=array();
			
			while($GbRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){
				$gbLabel=$GbRow[$GBFieldLabel];
				$GBTA[$gbLabel]['mod']=$GbRow['moduloelts'];
				$GBTA[$gbLabel]['pages']=$GbRow['pages'];
				$GBTA[$gbLabel]['nbelts']=$GbRow['nbelts'];
				$GBTA[$gbLabel]['nbsup']=$NBSup;

				for ($i = $pi; $i <= ($GbRow['pages']+$pi); $i++) {
					$pagedelta[$i]=$NBSup;
				}


				//$nbsupd=(($NBSup%$nbe)+($nbe-($NBSup%$nbe));
				$nbsupd=(($NBSup%$nbe)+($nbe-($NBSup%$nbe)));
	
				$nbbefelts=($pageid-1)*$nbep+$nbpelts;
				$nballelts=$nbbefelts +$GbRow['nbelts'];

				$paged=floor($NBSup/$nbep); //Number of empty elements...
				$firstpage=floor($nbbefelts/$nbep)+1;
				$lastpage=ceil($nballelts/$nbep);

				for ($i = 1; $i < $firstpage; $i++) {
					$pageGrpElts[$gbLabel][$i]=0;
				}

				for ($i = $firstpage; $i < $lastpage; $i++) {
					$pageGrpElts[$gbLabel][$i]=$nbep;
				}

				$pageGrpElts[$gbLabel][$lastpage]=$GbRow['moduloelts']+($nbe-$GbRow['moduloelts']);
				$pageGrpElts[$gbLabel][$firstpage]=(($GbRow['nbelts']>$nbep)?$nbep:$GbRow['nbelts'])-$NBSup-$mode;

				if($GBTA[$gbLabel]['mod']) $NBSup=$NBSup+($nbe-$GBTA[$gbLabel]['mod']);
				// wrong !!
				$pi+=$GbRow['pages'];
				$pageid=$lastpage;
				if ($oldpage<$firstpage) { 
					$oldpage=$lastpage;
					$nbpelts=0;
				}
				$nbpelts+=$nbsupd;

				$NbGroupBys++;
				$mode=$GbRow['moduloelts'];
			}	
		}
	
		if ($this->metafeeditlib->is_extent($conf['list.']['showFirstLast'])) $this->internal['showFirstLast']=$conf['list.']['showFirstLast'];
		if ($this->metafeeditlib->is_extent($conf['list.']['pagefloat'])) $this->internal['pagefloat']=$conf['list.']['pagefloat'];
		//if ($this->is_extent($conf['maxPages'])) $this->internal[maxPages']=$conf['maxPages'];
		if ($this->metafeeditlib->is_extent($conf['list.']['showRange'])) $this->internal['showRange']=$conf['list.']['showRange'];
		if ($this->metafeeditlib->is_extent($conf['list.']['dontLinkActivePage'])) $this->internal['dontLinkActivePage']=$conf['list.']['dontLinkActivePage'];

		if ($conf['list.']['pagination']) {
			$this->internal['res_count']=$num+$NBSup;

			//$pointer = $this->piVars['pointer'][$conf['pluginId']];
			//$pointer = $this->piVars['pointer'];
			$pointer = $conf['inputvar.']['pointer'];
        	$pointer = intval($pointer);
        	if ($pointer>(($num+$NBSup)/$conf['list.']['pageSize'])) $pointer=0;
        	// HACK to set naavigation pointer for page browser;
        	$this->piVars['pointer']=$pointer;
        	$results_at_a_time = t3lib_div::intInRange($this->internal['results_at_a_time'],1,1000);
        	$LIMIT = ' LIMIT '.($pointer*$results_at_a_time - $pagedelta[$pointer+1]).','.$results_at_a_time;
			if ($conf['list.']['no_detail']) {
				$LIMIT='';
			}
		}


		// List SQL REQUEST with limitations, pagination, etc ...
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql'].($exporttype?'':$LIMIT));
	    if ($conf['debug.']['sql']) $this->metafeeditlib->debug('displayList rows',$GLOBALS['TYPO3_DB']->SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql'].($exporttype?'':$LIMIT)),$DEBUG);
		//echo $GLOBALS['TYPO3_DB']->SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql'].($exporttype?'':$LIMIT));

 		$tpl=$this->prepareListTemplates($conf,&$this->markerArray,$exporttype);

		// process variables

		$out='';	
		$lcols='';
		$col=''; 
		$row='';
		$rows='';
		$i=0;  // col count
		$lc=0; // line count
		$nblines=0; // line count
		$gc=0; //groupby count
		$g_mod=0; // modulo for vertical display..
		$nbrowscol=0;
		$nbenreg=$num+$NbGroupBys;
		
		if ($conf['list.']['groupBySize'] && $dispDir!='Down') die("PLUGIN META FEEDIT : Group By Size is only handled when Display Direction is Down");
		if (!$conf['list.']['groupBySize'] && $dispDir=='Down') die("PLUGIN META FEEDIT : Group By Size must be set when Display Direction is Down");
		if ($conf['list.']['groupBySize'] && $dispDir!='Down') die("PLUGIN META FEEDIT : Group By Size must not be set when Display Direction is not Down");
		if ($conf['list.']['pageSize'] && $dispDir=='Down') die("PLUGIN META FEEDIT : Page size must not be set when Display Direction is Down");
		if ($conf['list.']['pagination'] && $dispDir=='Down') die("PLUGIN META FEEDIT : Pagination must not be set when Display Direction is Down");
		if ($conf['list.']['groupBySize']) $nbenreg=$num+($NbGroupBys*$conf['list.']['groupBySize']);
		
		$nblines=$ps;

		if ($dispDir=='Down'){
			if ($conf['list.']['pagination']) {
				$nbrowscol=$ps;
				$nbenreg=$nbdispcols*$ps;
				$nblines=$nbenreg;
			}
			else {
				$nbrowscol=ceil($nbenreg/$nbdispcols);
			}
			$width=floor(100/$nbdispcols);
		} else {
			$nbrowscol=$nbe;
			//$nbrowscol=2;
		}
		// we iterate on list items here
		$mediafile='';
		$mediaplayer='';
		$nbrows=0;
		
		// MODIF CBY
		// List alternate templates
		$nbAltRow=$conf['list.']['nbAltRows'];
		$nar=1;

		$templateExport='';
		
		// List Item Loop  
		
		while(($menuRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) && ($exporttype || $lc<$nblines)){
			$nbrows++;
			//TODO : $this->displayListItem($menuRow,$lc,$nbrows,$conf);
			//if ($this->performanceaudit) t3lib_div::devLog($conf['inputvar.']['cmd']." display Item List start :".$this->metafeeditlib->displaytime(), $this->extKey );

			// to display mediaplayer first file
			if ($lc==0 && $conf['list.']['mediaPlayer'] && !$mediaru) {
				foreach($menuRow as $key=>$val) {
					if ($conf['TCAN'][$this->theTable]['columns'][$key]['config']['type']=='group' && $conf['TCAN'][$this->theTable]['columns'][$key]['config']['internal_type'] == 'file') {
						$mediafiles = t3lib_div::trimexplode(',', $val);
						$mediafile=$conf['TCAN'][$this->theTable]['columns'][$key]['config']['uploadfolder'].'/'.$mediafiles[0];
						$mediaru=$menuRow['uid'];
						$mediaplayer = $this->metafeeditlib->getMediaPlayer($mediafile, $conf);
				  }
				}
			}
			//if ($this->performanceaudit) t3lib_div::devLog($conf['inputvar.']['cmd']." display Item List start :".$this->metafeeditlib->displaytime(), $this->extKey );

			// We get foreign table data if necessary (should be in the the sql query ...)

			$this->foreignTableSetup($conf,$menuRow,$this->markerArray);

			// to be removed as soon as i find an alternative

			$this->markerArray['###OPENROW###']="";
			$this->markerArray['###CLOSEROW###']="";
			
			// List Item Data processing
			
			$menuRow=$this->userProcess('evalFunc',$menuRow);
			
			// group by  field handling 
			$pagejump=0;

			$groupByFields=$this->groupByHeader($conf,$tpl['GBFCode'].$tpl['GBCode'],$menuRow,$groupBy,$evalGroupBy,$GrpByField,$lc,$gc,$i,$pagejump,$sql,false,$DEBUG);
			If ($pagejump) break;

			// New cols 

			// here for compatibility reasons .... to be removed

			if ($cols && $i==0) {
				$this->markerArray['###OPENROW###']='<tr '.$conf['caller']->pi_classParam('list-row-'.$nar).'>';
			}

			$i++;

			// here for compatibility reasons .... to be removed

			if ($cols && $i>=$cols && $dispDir=='Right') {
				$i=0;
				$lc++;
				$this->markerArray['###CLOSEROW###']="</tr>";
			}

			// List Item Data processing
			$conf['recUid']=$menuRow[$conf['uidField']];
			
			// Call to user item marker function
			
		   if ($conf['userFunc_afterItemMark']) t3lib_div::callUserFunction($conf['list.']['userFunc_afterItemMark'],$conf,$this);

			//$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $menuRow, '', TRUE, 'FIELD_', $conf['general.']['xhtml']);
			
			$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $menuRow, '', TRUE, 'FIELD_', FALSE);
			$markerArray = $this->metafeeditlib->setCObjects($conf,$this->markerArray,$tpl['itemElCode'],$menuRow,$markerArray,'ITEM_');

			// MODIF CBY
			// alternate template marker ...
			$markerArray['###LIST-ROW-ALT###']=$nar;
			$nar++;
			if ($nar>$nbAltRow)$nar=1;

			$item=$this->cObj->substituteMarkerArray($tpl['itemElCode'], $markerArray);
			// List item stdWrap
			if (is_array($conf['list.']['item_stdWrap.'][$this->theTable.'.'])) {
				$this->cObj->start($menuRow,$this->theTable);
				$item=$this->cObj->stdWrap($item,$conf['list.']['item_stdWrap.'][$this->theTable.'.']);
			}
			if (!$conf['list.']['no_detail']) {
				$item=$this->cObj->substituteSubpart($tpl['itemCode'], '###ITEM-EL###', $item);
				$item=$this->cObj->substituteMarker($item, '###ACTIONS-LIST-ELEMENT###', $this->metafeeditlib->getListItemActions($conf,$this,$markerArray));
			}
			$item= $this->cObj->substituteMarkerArray($groupByFields.$item, $markerArray);
			// Col or row display 

			if ($dispDir=='Down') {
				if (!$conf['list.']['no_detail']) $lc++; //MMMM
				$col.=$item;
				// If Display direction is Down (cols first) 
				if ($lc%$nbrowscol<$g_mod) {
							$col=$this->cObj->substituteSubpart($tpl['itemColCode'], '###ITEM###', $col);
							$lcols.= $this->cObj->substituteMarkerArray($col, $markerArray);
							$col='';
				}

				if ($nbrowscol) $g_mod=$lc%$nbrowscol;


			} else {
				// Display Right (rows first)
				$row.=$item;
        if ($lc>$g_mod) {
					$row=$this->cObj->substituteSubpart($tpl['itemRowCode'], '###ITEM###', $row);
					$rows.= $this->cObj->substituteMarkerArray($row, $markerArray);
					$row='';

        }
				if ($nbrowscol) {
						$g_mod=$lc;
				}
				if (!$tpl['itemRowCode']) $out.=$item;
			}
			//if ($conf['performanceaudit']) t3lib_div::devLog($conf['inputvar.']['cmd']." display Item List end :".$this->metafeeditlib->displaytime(), $this->extKey );
			// end of list item iteration
		}
	if ($markerArray==null) $markerArray= array(); //TODO : this should not be here
	if ($dispDir=='Down' && $col) {
		$col=$this->cObj->substituteSubpart($tpl['itemColCode'], '###ITEM###', $col);
		$lcols.= $this->cObj->substituteMarkerArray($col, $markerArray);
		$col='';
	} else {	
		$row=$this->cObj->substituteSubpart($tpl['itemRowCode'], '###ITEM###', $row);
		$rows.= $this->cObj->substituteMarkerArray($row, $markerArray);
		$row='';
	}

	if (!$nbrows) $rows='';
	
	// List post processing
	
	// Add last group by totals :
	
	$groupByFields=$this->groupByHeader($conf,$tpl['GBFCode'].$tpl['GBCode'],$menuRow,$groupBy,$evalGroupBy,$GrpByField,$lc,$gc,$i,$pagejump,$sql,true,$DEBUG);
	$out.=$groupByFields;

	$wraparray=array();
	if ($this->metafeeditlib->is_extent($conf['list.']['disabledLinkWrap'])) $wraparray['disabledLinkWrap']=$conf['list.']['disabledLinkWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['inactiveLinkWrap'])) $wraparray['inactiveLinkWrap']=$conf['list.']['inactiveLinkWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['activeLinkWrap'])) $wraparray['activeLinkWrap']=$conf['list.']['activeLinkWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['browseLinksWrap'])) $wraparray['browseLinksWrap']=$conf['list.']['browseLinksWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['showResultsWrap'])) $wraparray['showResultsWrap']=$conf['list.']['showResultsWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['showResultsNumbersWrap'])) $wraparray['showResultsNumbersWrap']=$conf['list.']['showResultsNumbersWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['browseBoxWrap'])) $wraparray['browseBoxWrap']=$conf['list.']['browseBoxWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['prevLinkWrap'])) $wraparray['prevLinkWrap']=$conf['list.']['prevLinkWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['nextLinkWrap'])) $wraparray['nextLinkWrap']=$conf['list.']['nextLinkWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['firstLinkWrap'])) $wraparray['firstLinkWrap']=$conf['list.']['firstLinkWrap'];
	if ($this->metafeeditlib->is_extent($conf['list.']['lastLinkWrap'])) $wraparray['lastLinkWrap']=$conf['list.']['lastLinkWrap'];
	$src=$this->metafeeditlib->is_extent($conf['list.']['showResultCount'])?$conf['list.']['showResultCount']:1;
	$this->markerArray['###PAGENAV###'] =$conf['list.']['pagination']?$this->pi_list_browseresults($src,'',$wraparray):'';
	
	// should create media player function here ...
	$this->markerArray['###MEDIAPLAYER###']=$this->markerArray['###MEDIA_ACTION_BLOG###']='';
	if ($conf['list.']['mediaPlayer']) {
	    $this->markerArray['###MEDIAPLAYER###'] = $this->piVars['mediaplayer'] ? $this->metafeeditlib->showMediaPlayer($this->piVars['mediaplayer'],$this->piVars['mediafile'],$conf):($mediaplayer?$this->metafeeditlib->showMediaPlayer($mediaplayer,$mediafile,$conf):'');
        // media player data
        $mediauid=$this->piVars['mediaplayer'] ? $this->piVars['mediaru'] : $mediaru;
        if 	($mediauid) {
            $this->markerArray['###MEDIA_ACTION_BLOG###']=$this->metafeeditlib->getBlogActions($conf,$this,$mediauid );
            $mediarec = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$mediauid);
            if (is_array($mediarec)) {
                //on start pour pouvoir stdwrapper
                $this->cObj->start($mediarec,$this->theTable);
        		foreach($mediarec as $key=>$val) {
        			$this->markerArray['###MEDIAPLAYER_'.$key.'###']=$val;
                    $this->markerArray['###MEDIAPLAYER_EVAL_'.$key.'###']=$this->cObj->stdWrap($val,$conf['list.']['mediaplayerWrap.'][$key.'.']);
            	}
            }
            //we wrap the whole mediaplayer here.
            $this->markerArray['###EVAL_MEDIAPLAYER###']=$this->cObj->stdWrap(trim($this->markerArray['###MEDIAPLAYER###']),$conf['list.']['mediaplayerWrap.']['mediaplayer.']);
        }
	}
	if ($dispDir=='Down') {
		$lcols=$this->cObj->substituteSubpart($tpl['allItemsCode'], '###ITEM-COL###', $lcols);
		$lcols= $this->cObj->substituteMarkerArray($lcols, $markerArray);
		$content=$this->cObj->substituteSubpart($tpl['templateCode'], '###ALLITEMS###', $lcols);
	} else {
		if ($rows) {
			$rows=$this->cObj->substituteSubpart($tpl['allItemsCode'], '###ITEM-ROW###', $rows);
			$rows=$this->cObj->substituteMarkerArray($rows, $markerArray);
		}
		if ($out) {
			$out=$this->cObj->substituteSubpart($tpl['allItemsCode'], '###ITEM###', $out);
		} else {
			$out=$rows;
		}
		$content=$this->cObj->substituteSubpart($tpl['templateCode'], '###ALLITEMS###', $out);
	}
	if (!$this->markerArray['###MEDIAPLAYER###']) $content=$this->cObj->substituteSubpart($content,'###MEDIAPLAYERTAG###','');
	// We handle List Actions here
	$content=$this->cObj->substituteMarker($content,'###ACTIONS-LIST-LIB###',$this->metafeeditlib->getListItemActionsLib($conf));
	$content=$this->cObj->substituteMarker($content,'###ACTIONS-LIST-TOP###',$this->metafeeditlib->getListTopActions($conf,$this));
	$content=$this->cObj->substituteMarker($content,'###ACTIONS-LIST-BOTTOM###',$this->metafeeditlib->getListBottomActions($conf,$this));

    // AS Search replace
		//new_url is set with the current url and with cmd = create , rU = , ...
    if ($conf['list.']['advancedSearch']) {
        $fields=$conf['list.']['advancedSearchFields']?$conf['list.']['advancedSearchFields']:($conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field);
        $fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
        foreach($fieldArray as $FN) {
			$params=t3lib_div::trimExplode(";",$FN);
			if ($params[0]!='--div--' && $params[0]!='--fse--' && $params[0]!='--fsb--') {
			    $curTable = $this->metafeeditlib->getForeignTableFromField($FN, $conf);
	 		    $type = $conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['type'];
				switch((string)$type) {
	        	case 'select':
	        	case 'group' :
	       			$val=$conf['inputvar.']['advancedSearch'][$FN];
					//TODO check if ajax widget has been generated ..
					$this->markerArray['###AS_FIELD_'.$FN.'###']=$this->metafeeditlib->getSelectOptions($curTable['fNiD'],$curTable['relTable'],$conf,1,$val);
	        	break;
				}
			}
        }
    }
  
    // AS Search end
	if (!$num) $this->markerArray['###EVAL_ERROR###']=$this->cObj->substituteMarkerArray($this->metafeeditlib->getPlainTemplate($conf,$this->markerArray,'###TEMPLATE_EDITMENU_NOITEMS###'),$this->markerArray);
	// Call to user  marker function
	if ($conf['list.']['userFunc_afterMark']) $this->userProcess_alt($conf['list.']['userFunc_afterMark'],$conf['list.']['userFunc_afterMark.'],array($conf['list.']['whereString']));
	//t3lib_div::callUserFunction($conf['metafeedit.']['userFunc_afterInitConf'],$conf,$this);
	if ($conf['userFunc_afterMark']) t3lib_div::callUserFunction($conf['metafeedit.']['userFunc_afterMark'],$conf,$this);
	$content=$this->cObj->stdWrap(trim($this->cObj->substituteMarkerArray($content, $this->markerArray)),$conf['list.']['formWrap.'][$this->theTable.'.']);

	//if (!$num) $content .= $this->cObj->substituteMarkerArray($this->metafeeditlib->getPlainTemplate($conf,$this->markerArray,'###TEMPLATE_EDITMENU_NOITEMS###'),$this->markerArray);

	$this->getListSums($conf,$sql,$content,$tpl,$DEBUG);

	switch ($exporttype)
	{
		case "CSV": 
		    $this->metafeeditexport->getCSV($content,$this);
		    break;
		case "PDF": 
		    $this->metafeeditexport->getPDF($content,$this);
		    break;
		case "PDFTAB": 
		    $this->metafeeditexport->getPDFTAB($content,$this);
		    break;
		case "PDFDET": 
		    $this->metafeeditexport->getPDFDET($content,$this);
		    break;
		case "EXCEL": 
		    $this->metafeeditexport->getEXCEL($content,$this);
		    break;
	}
	if ($this->conf['performanceaudit']) $this->perfArray['fe_adminLib.inc displaylist end:']=$this->metafeeditlib->displaytime()." Seconds";
	return $content.$DEBUG;

	}
	
	

	/* calculates Sum Footer
	*/
	
	function getListSums(&$conf,&$sql,&$content,&$tpl,&$DEBUG) {	
		if ($conf['list.']['sumFields']) {
			$table=$conf['table'];
			$sumFields = '';
			$sumSQLFields = '';
			$somme = 0;
			$sumFields = explode(',', $conf['list.']['sumFields']);
			foreach($sumFields as $fieldName) {
				if ($conf['list.']['sqlcalcfields.'][$fieldName]) {
					$calcField=$conf['list.']['sqlcalcfields.'][$fieldName]; // TO BE IMPROVED
					if ($calcField) {
						if (eregi("min\(|max\(|count\(|sum\(|avg\(",$calcField)) {
							// we test for group by functions
							$sumSQLFields.=$sumSQLFields?",$calcField as sum_$fieldName":"$calcField as sum_$fieldName";
						} else {
							$sumSQLFields.=$sumSQLFields?",sum($calcField) as sum_$fieldName":"sum($calcField) as sum_$fieldName";
						}
					}
				} else {
					$sumSQLFields.=$sumSQLFields?",sum($table.$fieldName) as sum_$fieldName":"sum($table.$fieldName) as sum_$fieldName";
				}
			}
			
			$sumSQLFields.=', count(*) as metafeeditnbelts';//.($sql['groupBy']?','.$conf['table'].'.*':'');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sumSQLFields, $sql['fromTables'], '1 '.$sql['where']); //.$sql['groupBy'].$sql['having']);		
		    if ($conf['debug.']['sql']) $this->metafeeditlib->debug('DisplayList Sum SQL ',$GLOBALS['TYPO3_DB']->SELECTquery($sumSQLFields.$sql['gbFields'], $sql['fromTables'], '1 '.$sql['where']),$DEBUG);
			$resu=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
			//We handle here multiple group bys ...
			$value=array();
	    while($valueelt = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
	    	foreach($valueelt as $key=>$val) {
	    		$value[$key]+=$val;
	    		if ($key=='metafeeditnbelts') break;
	    	}
	    }
			foreach ($sumFields as $fieldName){
				// we handle a stdWrap on the Data..
				$std=$conf[$conf['cmdmode'].'.']['stdWrap.']?$conf[$conf['cmdmode'].'.']['stdWrap.']:$conf['stdWrap.'];
		
				if ($std[$fieldName.'.'] || $std[$table.'.'][$fieldName.'.']) {
					if ($std[$fieldName.'.']) $stdConf = $std[$fieldName.'.'];
					if ($std[$table.'.'][$fieldName.'.']) $stdConf = $std[$table.'.'][$fieldName.'.'];
						//$dataArr['EVAL_'.$_fN] = 
					$value['sum_'.$fieldName]=$this->cObj->stdWrap($value['sum_'.$fieldName], $stdConf);
				}
		
			 $this->markerArray["###SUM_FIELD_$fieldName###"]= $value['sum_'.$fieldName];
			}
			$this->markerArray["###SUM_FIELD_metafeeditnbelts###"]= $value['metafeeditnbelts'];
			$sumcontent=$this->cObj->stdWrap(trim($this->cObj->substituteMarkerArray($tpl['itemSumCode'], $this->markerArray)),$conf['list.']['sumWrap.']);
			$content=$this->cObj->substituteSubpart($content,'###SUM_FIELDS###',$sumcontent);
		} else {
			$this->markerArray["###SUM_FIELD_metafeeditnbelts###"]="";
			$sumcontent=$this->cObj->stdWrap(trim($this->cObj->substituteMarkerArray($tpl['itemSumCode'], $this->markerArray)),$conf['list.']['sumWrap.']);
			$content=$this->cObj->substituteSubpart($content,'###SUM_FIELDS###',$sumcontent);
		}
	}

	// This functions gets Foreign Table Data if necessary.
	function foreignTableSetup(&$conf,&$menuRow,&$markerArray) {	
		// to be removed as soon as I find an alternative

		if ($conf['foreignTables']) {					
			//MM not implemented
			//Not MM
			// We get foreign key data here (this is a bad way to do it !)?
			// we should get every data from the orignal data array

			$FTRels=t3lib_div::trimexplode(',',$conf['foreignTables']);
			foreach($FTRels as $FTRel) {
				
				$FTable=$conf['TCAN'][$conf['table']]['columns'][$FTRel]['config']['foreign_table'];
				//TODO handle muliple relations here (a.b.c);
				if ($FTable) {
					if (!$conf['TCAN'][$conf['table']]['columns'][$FTRel]['config']['MM']) {
								$FTUid=$menuRow[$FTRel];
					} else {
					//$FTUids=$this->metafeeditlib->getMMUids($conf,$conf['table'],$FTRel,$menuRow);
					}	

					// what if multiple ???
					// what if editmenu list  ???

					if ($FTUid) {
						$FTorigArr = $GLOBALS['TSFE']->sys_page->getRawRecord($FTable,  $FTUid);
						if (is_array($FTorigArr)) {
							foreach($FTorigArr as $key =>$val) $menuRow[$FTRel.'.'.$key]=$val;
						}
					}
					else
					{
						 $FTCA=$conf['TCAN'][$FTable]['columns'];
						 if (!is_array($FTCA)) die( "PLUGIN META FEEDIT : Rel $FTRel - $FTable has no columns !");
						 foreach($FTCA as $key=>$val) {												
							 $markerArray['###FIELD_'.$FTRel.'.'.$key.'###']='';
							 $markerArray['###FIELD_EVAL_'.$FTRel.'.'.$key.'###']='';
							 $markerArray['###EVAL_ERROR_FIELD_'.$FTRel.'.'.$key.'###']='';
						}
					}
				}

			}
		}
	}

/**
 * groupByHeader : generates Group By Header if necessary
 *
 * @return	string		HTML content
 * @see init()
 */
 
function groupByHeader(&$conf,$GBCode,$menuRow,&$groupBy,&$evalGroupBy,$GrpByField,&$lc,&$gc,&$i,&$pagejump,&$sql,$end=false,&$DEBUG) {
	// group by  field handling
	$GBmarkerArray=array();

  $groupByFields="";
  $gbflag=0;
  $newGroupBy=array();
  if ($conf['list.']['groupByFieldBreaks']) {
  	$fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFieldBreaks']);
    foreach($fNA as $fN) {
    	$fN2=t3lib_div::trimexplode(':',$fN);
      $fN=$fN2[0];
      //if ($conf['list.']['hiddenGroupByField.'][$fN]) continue;

      $GBmarkerArray['###GROUPBY_'.$fN.'###']="";
      if (($groupBy[$fN]!=$menuRow[$GrpByField[$fN]?$GrpByField[$fN]:$fN] || $end)  && !$conf['list.']['hiddenGroupByField.'][$fN]) {
				// Group by field change !
				$GBmarkerArray['###FOOTERSUM_'.$fN.'_FIELD_metafeeditnbelts###']='';
        if ($evalGroupBy[$fN]) {
        	$GBmarkerArray['###GROUPBYFOOTER_'.$fN.'###']=$evalGroupBy[$fN]; 
					$this->metafeeditlib->getGroupByFooterSums($conf,'FOOTERSUM',$GBmarkerArray,$fN,$sql,$groupBy,$end,$DEBUG);
        } else {
           $GBCode=$this->cObj->substituteSubpart($GBCode, '###GROUPBYFOOTERFIELD_'.$fN.'###','');
        }     	
      	$std=$menuRow[$fN];

        // Default we get value from std group by field ...
        if ($GrpByField[$fN]) {
          $std=$menuRow[$GrpByField[$fN]];
          if ($menuRow['EVAL_'.str_replace('.','_',$GrpByField[$fN])]) $std=$menuRow['EVAL_'.str_replace('.','_',$GrpByField[$fN])];
        }
        
				// stdWrap on group by.		
				$_fN=str_replace('.','_',$fN);
        if ($conf['list.']['groupByFields.']['stdWrap.'][$_fN.'.']) {
         $this->cObj->start($menuRow,$this->theTable);
         $std=$this->cObj->stdWrap($std,$conf['list.']['groupByFields.']['stdWrap.'][$_fN.'.']);
        }
        
				// ??? 	
				
        if ($GrpByField[$fN]) {
                $newGroupBy[$fN]=$menuRow[$GrpByField[$fN]];
        } else {
                $newGroupBy[$fN]=$menuRow[$fN];
          			if ($menuRow['EVAL_'.$_fN]) $std=$menuRow['EVAL_'.$_fN];
        }
        
        
				// we set group by flag
        $gbflag=1;
        $GBmarkerArray['###GROUPBY_'.$fN.'###']=$std;
				$evalGroupBy[$fN]=$std;

      } else {
      		// we clear unused group bys 
         $GBCode=$this->cObj->substituteSubpart($GBCode, '###GROUPBYFIELD_'.$fN.'###','');
         $GBCode=$this->cObj->substituteSubpart($GBCode, '###GROUPBYFOOTERFIELD_'.$fN.'###','');
      }
      // We clear next group by if end of list is reached
      if ($end) $GBCode=$this->cObj->substituteSubpart($GBCode, '###GROUPBYFIELD_'.$fN.'###','');

    }
    foreach($newGroupBy as $fN=>$val) {
    	$groupBy[$fN]=$val;
    }
    
  }
  if ($gbflag) {
    $groupByFields=$this->cObj->substituteMarkerArray($GBCode, $GBmarkerArray);
    $gc++;
    // Jump page on group by
    if ($gc>1 && $conf['list.']['jumpPageOnGroupBy']) {
       $pagejump=1;
    }
    if ($conf['list.']['groupBySize']) {
    	$lc=$lc+$conf['list.']['groupBySize'];
    }
    if ($conf['list.']['no_detail']) $lc++; //MMM

    if ($gc>1 && $dispDir=='Right') $lc++;
  	$i=0; // pour fin de ligne
	}
	return $groupByFields;
}

    /**
    * Creates the edit-screen for records
    *
    * @return	string		HTML content
    * @see init()
    */
    function displayEditScreen()	{
        
    	// We handle here Edit mode or Preview Mode
    	// Edit Mode : User can edit fields
    	// Preview Mode : user can only see fields	
    	if ($this->conf['debug']) echo t3lib_div::view_array(array('displayEditScreen'=>'on'));
    	$exporttype=$this->piVars['exporttype'];		
		if ($exporttype == 'PDF') $exporttype = "PDFDET";
    	$this->conf['cmdmode']='edit';    
    	// We handle here Edit mode or Preview Mode
     	// Edit Mode : User can edit fields
     	// Preview Mode : user can only see fields     
     	//$this->markerArray['###BACK_URL###'] = "";
     	
		//We handle backurl...
        if ($this->conf['edit.']['backPagePid'] && !$this->conf['no_action']) {
            $this->backURL=$this->pi_getPageLink($this->conf['edit.']['backPagePid']);
            if (!strpos($this->backURL,'?')) $this->backURL.='?';
            $this->markerArray['###BACK_URL###'] = $this->backURL;
        }
	    if ($this->conf['edit'] || $this->preview || $this->conf['list'] )	{	// If editing is enabled
    		
			// hack for lists in second plugin ... to be checked.., Will not work if we want to edit in second plugin ...
    		if ($this->conf['debug']) echo t3lib_div::view_array(array('Edit or preview'=>'on'));
    		$uid=$this->dataArr[$this->conf['uidField']]?$this->dataArr[$this->conf['uidField']]:$this->recUid;
    		if ($this->conf['list.']['rUJoinField']=='uid' && $uid){
    			if ($this->conf['debug']) echo t3lib_div::view_array(array('UIDFIELD'=>$this->conf['uidField'].' : '.$this->recUid));
    			if ($this->conf['debug']) echo t3lib_div::view_array(array('dataArr'=>$this->dataArr[$this->conf['uidField']]));
    			$origArr = $this->metafeeditlib->getRawRecord($this->theTable,$uid,$this->conf);
    		}
			
    		if ($this->conf['debug']) echo t3lib_div::view_array(array('editMode'=>'on'));

    		// here we handle foreign tables not the best way , we should work on join tables especially if we handle lists...
    		//
    		if ($this->conf['foreignTables'] && is_array($origArr)) {
				
				//MM not implemented
				//Not MM
				
				$FTRels=t3lib_div::trimexplode(',',$this->conf['foreignTables']);
				foreach($FTRels as $FTRel) {
					$FTable=$GLOBALS['TCA'][$this->theTable]['columns'][$FTRel]['config']['foreign_table'];
					$FTUid=$origArr[$FTRel];
					// what if multiple ???
					// what if editmenu list  ???
					if ($FTUid) {
					     //on récup l'id de l'enregistrement a associer
						if ($GLOBALS['TCA'][$this->theTable]['columns'][$FTRel]['config']['MM']) { //si on est dans une MM faut d'abord récup les id de la table MM
							$MMT = $GLOBALS['TCA'][$this->theTable]['columns'][$FTRel]['config']['MM'];
							$LTUid=$origArr["uid"];
							$MMTreq = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query('*',$this->theTable,$MMT,$FTable,'AND '.$this->theTable.'.uid='.$origArr['uid']);
							$resu=$GLOBALS['TYPO3_DB']->sql_num_rows($MMTreq);
							if ($resu>=1) {
								while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($MMTreq))   {
									foreach($row as $key =>$val) $origArr[$FTRel.'.'.$key]=$val;
								}
							}
						}
						// modif by CMD
						else
						{
							// originally there only these 2 lines in this condition (if ($FTUid) )
							$FTorigArr = $GLOBALS['TSFE']->sys_page->getRawRecord($FTable, $FTUid);
							foreach($FTorigArr as $key =>$val) $origArr[$FTRel.'.'.$key]=$val;
						}
					}
					else
					{
						$FTCA=$GLOBALS['TCA'][$FTable]['columns'];
						//krumo ($FTCA);
						foreach($FTCA as $key=>$val) {
							if (in_array($FTCA,array('--div--;Tab','--fsb--;FSB','--fse--;FSE'))) continue;
							$this->markerArray['###FIELD_'.$FTRel.'.'.$key.'###']='';
							$this->markerArray['###FIELD_EVAL_'.$FTRel.'.'.$key.'###']='';
							$this->markerArray['###EVAL_ERROR_FIELD_'.$FTRel.'.'.$key.'###']='';
						} 
					}
					
				}
			}
			
			//<CBY>  on passe en mode détail directement si editUnique est à true et qu'il n'y a qu'un seul élément à éditer.
			$DBSELECT=$this->metafeeditlib->DBmayFEUserEditSelectMM($this->theTable,$GLOBALS['TSFE']->fe_user->user, $this->conf['allowedGroups'],$this->conf['fe_userEditSelf'],$mmTable,$this->conf).$GLOBALS['TSFE']->sys_page->deleteClause($this->theTable);

			$TABLES=$mmTable?$this->theTable.','.$mmTable:$this->theTable;
			if ($this->conf['debug']) echo t3lib_div::view_array(array('EDIT'=>$origArr));
			if (!is_array($origArr)&&$this->conf['editUnique']) {
				$lockPid = ($this->conf['edit.']['menuLockPid'] && $this->conf['pid'])? ' AND pid='.intval($this->thePid) : '';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $TABLES, '1 '.$lockPid.$DBSELECT);
				if ($this->conf['debug']) 	echo t3lib_div::view_array(array('EDITUNIQUE TEST'=>$GLOBALS['TYPO3_DB']->SELECTquery('*', $TABLES, '1 '.$lockPid.$DBSELECT)));
				$resu=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
				if ($resu>=1)	{
 					while($menuRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))   {
						$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,  $menuRow[$this->conf['uidField']]);
						$this->recUid=$menuRow[$this->conf['uidField']];
						$this->conf['recUid']=$this->recUid;
					}
				} 
			}

			// <CBY>
			$this->markerArray['###REC_UID###']=$this->recUid;
			//if ($GLOBALS['TSFE']->loginUser || $this->aCAuth($origArr))	{	// Must be logged in OR be authenticated by the aC code in order to edit
			if (($GLOBALS['TSFE']->loginUser &&  $this->conf['requireLogin']) || ( $this->aCAuth($origArr)&& $this->conf['requireLogin'])||!$this->conf['requireLogin'])	{	// Must be logged in OR be authenticated by the aC code in order to edit

		  	// If the recUid selects a record.... (no check here)
			if ($this->conf['debug']) echo t3lib_div::view_array(array('EDIT'=>"No login"));
			
			if (is_array($origArr) && !($this->conf['inputvar.']['BACK'] && $this->conf['inputvar.']['cameFromBlog']))	{
				if ($this->conf['blogData']) $this->preview=1; 
				// we check if edit or preview mode is allowed ...
				if (!$this->conf['edit'] && !$this->preview )	{	// If editing is enabled
					$content.='Edit-option or Preview-option is not set in TypoScript';
					return $content;
				}
				
				if ($this->conf['disableEdit'] && !$this->preview )	{	// If editing is enabled
					$content.='Edit-option disabled and Preview-option is not set in TypoScript';
					return $content;
				}

				if ($this->aCAuth($origArr) || $this->metafeeditlib->DBmayFEUserEdit($this->theTable,$origArr, $GLOBALS['TSFE']->fe_user->user,$this->conf['allowedGroups'],$this->conf['fe_userEditSelf'],$this->conf))	{	// Display the form, if access granted.
				if ($this->conf['debug']) echo t3lib_div::view_array(array('EDIT'=>"User may edit"));
					if ($this->conf['evalFunc'])	{
						$origArr = $this->userProcess('evalFunc',$origArr);
					}
					$this->markerArray = $this->setfixed($this->markerArray, $this->conf['setfixed.'], $origArr);
					$content=$this->displayEditForm($origArr,$this->conf,$exporttype);
				} else {
	                // Else display error, that you could not edit that particular record...	
				    $content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_NO_PERMISSIONS###');
				}
			} else { 
				// If the recUid did not select a record, we display a menu of records. (eg. if no recUid)
				// we check if list mode is allowed ...
				if (!$this->conf['list'])	{	// If editing is enabled
			     $content.='List-option is not set in TypoScript';
					 return $content;
				}
				//$content=($this->conf['general.']['listMode']==2)?$this->metafeeditgrid->displayGridScreen($TABLES,$DBSELECT,$this->conf):$this->displayListScreen($TABLES,$DBSELECT,$this->conf);
				switch($this->conf['general.']['listMode']) {
					case 2 :
						$content=$this->metafeeditgrid->displayGridScreen($TABLES,$DBSELECT,$this->conf);
						break;
					case 1 :
						//$content=$this->metafecalendar->displayCalendarScreen($TABLES,$DBSELECT,$this->conf);
						$cal=t3lib_div::makeInstance('tx_metafeedit_calendar');
					  $cal->initObj($this->metafeeditlib,$this->cObj);
						$content=$cal->displayCalendarScreen($TABLES,$DBSELECT,$this->conf);
						break;
					default :
						$content=$this->displayListScreen($TABLES,$DBSELECT,$this->conf);
				}
				}; 

				
				
				
			} else {	// Finally this is if there is no login user. This must tell that you must login. Perhaps link to a page with create-user or login information.
				$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_AUTH###');
			}
		} else {
			$content.='Edit-option , Preview-option or List-option is not set in TypoScript';
		}
		return $content;
	}

	/**
	 * Subfunction for displayEditScreen(); Takes a record and creates an edit form based on the template code for it.
	 * This function is called if the user is editing a record and permitted to do so. Checked in displayEditScreen()
	 *
	 * @param	array		The array with the record to edit
	 * @return	string		HTML content
	 * @access private
	 * @see displayEditScreen()
	 */
	 
	function displayEditForm($origArr,&$conf,$exporttype='')	{
		//We merge data with override values and eval values ...
		$currentArr = array_merge($origArr,(array)$this->dataArr);
		$arr=explode(',',$conf['fieldList']);
		$pluginId=$conf['pluginId'];
		$back_lnk = $this->conf['typoscript.'][$pluginId.'.']['edit.']['nobackbutton']?false:($this->conf['typoscript.']['default.']['edit.']['nobackbutton']?false:true);

		foreach($arr as $key) {
			if (!$this->markerArray['###EVAL_ERROR_FIELD_'.$key.'###']) $this->markerArray['###EVAL_ERROR_FIELD_'.$key.'###']='';
			if ( $GLOBALS['TCA'][$this->theTable]['columns'][$key]['config']['type']=='group' &&  $GLOBALS['TCA'][$this->theTable]['columns'][$key]['config']['internal_type']=='file')	{
				if (!$this->markerArray['###EVAL_ERROR_FIELD_'.$key.'_file###']) $this->markerArray['###EVAL_ERROR_FIELD_'.$key.'_file###']='';
			// we handle here field_file copy in field.
			//if ($this->dataArr[$key.'_file'] and !in_array($this->dataArr[$key.'_file'],explode(',',$currentArr[$key]))) $currentArr[$key]=$currentArr[$key]?$currentArr[$key].','.$this->dataArr[$key.'_file']:$this->dataArr[$key.'_file'];
			}
		}

		if ($this->conf['debug'])	debug('displayEditForm(): '.'###TEMPLATE_EDIT'.$this->previewLabel.'###',1);
		$templateCode = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE_EDIT'.$this->previewLabel.($exporttype?'_'.$exporttype:'').'###');
		$failure = t3lib_div::_GP('noWarnings')?'':$this->failure;
		if (!$failure)	{$templateCode = $this->cObj->substituteSubpart($templateCode, '###SUB_REQUIRED_FIELDS_WARNING###', '');}
		$templateCode = $this->removeRequired($templateCode,$failure);
		$this->metafeeditlib->setCObjects($this->conf,$this->markerArray,$templateCode,$currentArr);
		//krumo($this->markerArray);
		$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $currentArr, '', TRUE, 'FIELD_', FALSE);
		//$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $currentArr, '', TRUE, 'FIELD_', $this->conf['general.']['xhtml']);
		$markerArray['###HIDDENFIELDS###'].= '<input type="hidden" name="FE['.$this->theTable.']['.$this->conf['uidField'].']" value="'.$currentArr[$this->conf['uidField']].'" />'.($conf['blogData']?'<input type="hidden" name="cameFromBlog['.$pluginId.']" value="1" />':'');
		if ($this->conf['edit.']['preview'] && !$this->previewLabel)	{$markerArray['###HIDDENFIELDS###'].= '<input type="hidden" name="preview['.$pluginId.']" value="1" />';}
		
		// Here we handle actions
		
		$actions =array('SAVE','DELETE','BACK', 'NEW','PDF');
		$action=array();
		if ($this->conf['list.']['recordactions']) {
   		$ActionArr=t3lib_div::trimexplode(chr(10),$this->conf['list.']['recordactions']);
		foreach($ActionArr as $act) {
				$cmdarr=t3lib_div::trimexplode('|',$act);
				if (count($cmdarr)>2) {
					$actionId=$cmdarr[0];
					$actionLib=$cmdarr[1];
					$actionUrl=$cmdarr[2];
				} else {
					$actionId=$this->metafeeditlib->enleveaccentsetespaces($cmdarr[0]);
					$actionLib=$cmdarr[0];
					$actionUrl=$cmdarr[1];
				}
				$actionId=strtoupper($actionId);
				$actions[]=$actionId;
				$js='this.form.action=\''.$actionUrl.'\'; this.form.submit();';
				$action[$actionId]='<div class="'.$this->pi_getClassName('action-'.$actionId).'"><form name="'.$actionLib.'" method="post" action=""><input type="submit" name="'.$actionLib.'['.$pluginId.']" value="'.$actionLib.'" onclick="'.$js.'" '.$this->pi_classParam('form-submit').' /></form></div>';
			}
		}
		

		if($this->conf['delete']) {			
				$backURL=rawurlencode($this->metafeeditlib->makeFormTypoLink($this->conf,"&rU[$pluginId]=".$this->recUid));

			if($this->conf['delete.']['preview'] && !$this->conf['disableDelete'] && !$this->conf['disableEditDelete']) {
				$deleteURL=$this->metafeeditlib->makeFormTypoLink($this->conf,"&cmd[$pluginId]=delete&preview[$pluginId]=1&rU[$pluginId]=".$this->recUid."&backURL[$pluginId]=".$backURL);
				$js='this.form.action=\''.$deleteURL.'\'; this.form.submit();';
				$action['DELETE']='<div><form name="'.$this->metafeeditlib->getLL("edit_delete_label",$this->conf).'" method="post" action=""><input type="submit" class="'.$this->pi_getClassName('action').' '.$this->pi_getClassName('action-DELETE').'" name="'.$this->metafeeditlib->getLL("edit_delete_label",$this->conf).'['.$pluginId.']" value="'.$this->metafeeditlib->getLL("edit_delete_label",$this->conf).'" onclick="'.$js.'" /></form></div>';
			} else if (!$this->conf['disableDelete']  && !$this->conf['disableEditDelete']) {
				$deleteURL=$this->metafeeditlib->makeFormTypoLink($this->conf,"&cmd[$pluginId]=delete&rU[$pluginId]=".$this->recUid."&backURL[$pluginId]=".$backURL);
				$js='this.form.action=\''.$deleteURL.'\'; this.form.submit();';
				$action['DELETE']='<div><form name="'.$this->metafeeditlib->getLL("edit_delete_label",$this->conf).'" method="post" action=""><input type="submit" class="'.$this->pi_getClassName('action').' '.$this->pi_getClassName('action-DELETE').'" name="'.$this->metafeeditlib->getLL("edit_delete_label",$this->conf).'['.$pluginId.']" value="'.$this->metafeeditlib->getLL("edit_delete_label",$this->conf).'" onclick="'.$js.'" /></form></div>';
			}
		}
	
		$action['SAVE']=($this->conf['disableEdit']|| $this->conf['blogData'])?'':'<div class="'.$this->pi_getClassName('action-SAVE').'" ><button type="submit" name="submit['.$pluginId.']" value="save" '.$this->pi_classParam('form-submit').'>'.($this->conf['edit.']['preview']?$this->metafeeditlib->getLL("edit_submit_label",$this->conf):$this->metafeeditlib->getLL("edit_preview_submit_label",$this->conf)).'</button></div>';
		// We handle Export Actions Here
		$action['PDF']=$conf['edit.']['pdf']?$this->metafeeditexport->CreatePDFButtonDetail($conf,$conf['caller'],false,$this->recUid):'';
		//modif OSR 2006-12-09 Add "add new" button in edit screen. onclick on the button , document reload with the create url 
		//maybe need an value in conf to disable this button
		//new_url is set with the current url and with cmd = create , rU = , 
		
		$js='this.form.action=\'###NEW_URL###\'; this.form.submit();';
		$action['NEW']=($this->conf['disableCreate']||$this->conf['create.']['hide'])?'':'<div><form name="'.$this->metafeeditlib->getLL("back_label",$this->conf).'" method="post" action=""><input type="button" class="'.$this->pi_getClassName('action').' '.$this->pi_getClassName('action-NEW').'" name="'.$this->metafeeditlib->getLL("new_label",$this->conf).'['.$pluginId.']" value="'.$this->metafeeditlib->getLL("new_label",$this->conf).'" onclick="'. $js .'" /></form></div>'; 
		//$action['BACK']='<div class="'.$this->pi_getClassName('link').' '.$this->pi_getClassName('link-back').'"><a href="###BACK_URL###">'.$this->metafeeditlib->getLL("back_label",$this->conf).'</a></div>';
		$js='this.form.action=\'###BACK_URL###\'; this.form.submit();';
		//modif by CMD - on cache le lien de retour
		//$action['BACK']='<div><form name="'.$this->metafeeditlib->getLL("back_label",$this->conf).'" method="post" action=""><input type="submit" class="'.$this->pi_getClassName('action').' '.$this->pi_getClassName('action-BACK').'" name="'.$this->metafeeditlib->getLL("back_label",$this->conf).'['.$pluginId.']" value="'.$this->metafeeditlib->getLL("back_label",$this->conf).'" onclick="'. $js .'" /></form></div>'; 
		$action['BACK']=($back_lnk)?'<div><form name="'.$this->metafeeditlib->getLL("back_label",$this->conf).'" method="post" action=""><input type="submit" class="'.$this->pi_getClassName('action').' '.$this->pi_getClassName('action-BACK').'" name="'.$this->metafeeditlib->getLL("back_label",$this->conf).'['.$pluginId.']" value="'.$this->metafeeditlib->getLL("back_label",$this->conf).'" onclick="'. $js .'" /></form></div>':'';
		//a href="###BACK_URL###">'.$this->metafeeditlib->getLL("back_label",$this->conf).'</a></div>';

		foreach($actions as $act) {
			$markerArray['###ACTION-'.$act.'###']=$this->conf['no_action']?'':$this->cObj->substituteMarkerArray($action[$act],$markerArray);
		}

		// End of actions
 		$content = $this->cObj->substituteSubpart($templateCode,'###TEMPLATE_BLOG###',$this->metafeeditlib->getBlogComments($templateCode,$this->theTable,$this->recUid,$this->conf));
		if ($this->conf['blogData']) $content = $this->cObj->substituteSubpart($content,'###PREVIEWACTIONS###',$action['BACK']); ;

		$content = $this->cObj->substituteMarkerArray($content, $markerArray);
		// Blog Comment Display :
		// We handle empty fields here ..
 		if ($this->conf['preview.']['noemptyfields'] && $this->preview) {
                        foreach($arr as $key) {
                                if (!$currentArr[$key]) {

                                        $cnt = '';
                                        $content=$this->cObj->substituteSubpart($content, '###editITEM-'.$key.'###',$cnt);
                                }
                        }
                }

		if ($this->conf['edit.']['field_stdWrap.']) {
                        $this->cObj->start($currentArr,$this->theTable);
			foreach($arr as $key) {
				if ($this->conf['edit.']['field_stdWrap.'][$key.'.']) {
					$cnt = $this->cObj->getSubpart($content, '###editITEM-'.$key.'###');
					$cnt = $this->cObj->stdWrap($cnt,$this->conf['edit.']['field_stdWrap.'][$key.'.']);
					$content=$this->cObj->substituteSubpart($content, '###editITEM-'.$key.'###',$cnt);
				}
			}	
		}
		
		switch ($exporttype)
		{
			case "CSV": 
		    $this->metafeeditexport->getCSV($content,$this);
		    break;
			case "PDF": 
		    $this->metafeeditexport->getPDF($content,$this);
		    break;
			case "PDFTAB": 
		    $this->metafeeditexport->getPDFTAB($content,$this);
		    break;
			case "PDFDET": 
		    $this->metafeeditexport->getPDFDET($content,$this);
		    break;
			case "EXCEL": 
		    $this->metafeeditexport->getEXCEL($content,$this);
		    break;
		}
		
		$content = $this->cObj->stdWrap($content,$this->conf['edit.']['formWrap.']);
		$content.=$this->cObj->getUpdateJS($this->modifyDataArrForFormUpdate($currentArr), $this->theTable.'_form',  'FE['.$this->theTable.']', $this->conf['fieldList'].$this->additionalUpdateFields);
		$content.=$this->conf['blog.']['allowComments']?$this->cObj->getUpdateJS($this->modifyDataArrForFormUpdate($currentArr), 'tx_metafeedit_comments_form',  'FE[tx_metafeedit_comments]', $this->conf['blogFieldList']):'';
		return $content;
	}

	/**
	 * Processes socalled "setfixed" commands. These are commands setting a certain field in a certain record to a certain value. Like a link you can click in an email which will unhide a record to enable something. Or likewise a link which can delete a record by a single click.
	 * The idea is that only some allowed actions like this is allowed depending on the configured TypoScript.
	 *
	 * @return	string		HTML content displaying the status of the action
	 */
	function procesSetFixed()	{
		if ($this->conf['setfixed'])	{
			$theUid = intval($this->recUid);
			$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,  $theUid);
			if ($this->conf['evalFunc'])	{
				$origArr = $this->userProcess('evalFunc',$origArr);
			}
			$fD = t3lib_div::_GP('fD');
			$sFK = t3lib_div::_GP('sFK');
			$fieldArr=array();
			if (is_array($fD) || $sFK=='DELETE')	{
				if (is_array($fD))	{
					reset($fD);
					while(list($field,$value)=each($fD))	{
						$origArr[$field]=$value;
						$fieldArr[]=$field;
					}
				}

				$theCode = $this->setfixedHash($origArr,$origArr['_FIELDLIST']);
				if (!strcmp($this->authCode,$theCode))	{
					if ($sFK=='DELETE')	{
						$this->cObj->DBgetDelete($this->theTable, $theUid, TRUE);
					} else {
						$newFieldList = implode(',',array_intersect(t3lib_div::trimExplode(',',$this->conf['fieldList']),t3lib_div::trimExplode(',',implode($fieldArr,','),1)));
						$this->cObj->DBgetUpdate($this->theTable, $theUid, $fD, $newFieldList, TRUE);
						$this->currentArr = $GLOBALS['TSFE']->sys_page->getRawRecord($this->theTable,$theUid);
						$this->userProcess_alt($this->conf['setfixed.']['userFunc_afterSave'],$this->conf['setfixed.']['userFunc_afterSave.'],array('rec'=>$this->currentArr, 'origRec'=>$origArr));
					}

						// Outputting template
					//$this->markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $origArr, '', TRUE, 'FIELD_', $this->conf['general.']['xhtml']);
					$this->markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $origArr, '', TRUE, 'FIELD_', FALSE);
					$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_SETFIXED_OK_'.$sFK.'###');
					if (!$content)	{$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_SETFIXED_OK###');}

					// Compiling email

					$this->compileMail(
						'SETFIXED_'.$sFK,
						array($origArr),
						$this->getFeuserMail($origArr,$this->conf),
						$this->conf['setfixed.']
					);
						// Clearing cache if set:
					$this->clearCacheIfSet();
				} else $content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_SETFIXED_FAILED###');
			} else $content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_SETFIXED_FAILED###');
		}
		return $content;
	}



	/*****************************************
	 *
	 * Template processing functions
	 *
	 *****************************************/



	/**
	 * Remove required parts from template code string
	 * 	 Works like this:
	 * 		 - You insert subparts like this ###SUB_REQUIRED_FIELD_'.$theField.'### in the template that tells what is required for the field, if it's not correct filled in.
	 * 		 - These subparts are all removed, except if the field is listed in $failure string!
	 *
	 * 		Only fields that are found in $this->requiredArr is processed.
	 *
	 * @param	string		The template HTML code
	 * @param	string		Comma list of fields which has errors (and therefore should not be removed)
	 * @return	string		The processed template HTML code
	 */
	function removeRequired($templateCode,$failure)	{
		reset($this->requiredArr);
		while(list(,$theField)=each($this->requiredArr))	{
			if (!t3lib_div::inList($failure,$theField))	{
				$templateCode = $this->cObj->substituteSubpart($templateCode, '###SUB_REQUIRED_FIELD_'.$theField.'###', '');
			}
		}
		return $templateCode;
	}

	/**
	 * Modifies input array for passing on to tslib_cObj::getUpdateJS() which produces some JavaScript for form evaluation or the like.
	 *
	 * @param	array		The data array
	 * @return	array		The processed input array
	 * @see displayCreateScreen(), displayEditForm(), tslib_cObj::getUpdateJS()
	 */
	function modifyDataArrForFormUpdate($inputArr)	{
		if (is_array($this->conf[$this->conf['cmdKey'].'.']['evalValues.']))	{
			reset($this->conf[$this->conf['cmdKey'].'.']['evalValues.']);
			while(list($theField,$theValue)=each($this->conf[$this->conf['cmdKey'].'.']['evalValues.']))	{
				$listOfCommands = t3lib_div::trimExplode(',',$theValue,1);
				while(list(,$cmd)=each($listOfCommands))	{
					$cmdParts = split('\[|\]',$cmd);	// Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
					$theCmd = trim($cmdParts[0]);
					switch($theCmd)	{
						case 'twice':
							if (isset($inputArr[$theField]))	{
								if (!isset($inputArr[$theField.'_again']))	{
									$inputArr[$theField.'_again'] = $inputArr[$theField];
								}
								$this->additionalUpdateFields.=','.$theField.'_again';
							}
						break;
					}
				}
			}
		}
		if (is_array($this->conf['parseValues.']))	{
			reset($this->conf['parseValues.']);
			while(list($theField,$theValue)=each($this->conf['parseValues.']))	{
				$listOfCommands = t3lib_div::trimExplode(',',$theValue,1);
				while(list(,$cmd)=each($listOfCommands))	{
					$cmdParts = split('\[|\]',$cmd);	// Point is to enable parameters after each command enclosed in brackets [..]. These will be in position 1 in the array.
					$theCmd = trim($cmdParts[0]);
					switch($theCmd)	{
						case 'multiple':
							if (isset($inputArr[$theField]) && !$this->isPreview())	{
								$inputArr[$theField] = explode(',',$inputArr[$theField]);
							}
						break;
						case 'checkArray':
							if ($inputArr[$theField] && !$this->isPreview())	{
								for($a=0;$a<=30;$a++)	{
									if ($inputArr[$theField] & pow(2,$a))	{
										$alt_theField = $theField.']['.$a;
										$inputArr[$alt_theField] = 1;
										$this->additionalUpdateFields.=','.$alt_theField;
									}
								}
							}
						break;
					}
				}
			}
		}

		$inputArr = $this->userProcess_alt(
			$this->conf['userFunc_updateArray'],
			$this->conf['userFunc_updateArray.'],
			$inputArr
		);

		return $inputArr;
	}

	/*****************************************
	 *
	 * Emailing
	 *
	 *****************************************/

	/**
	 * Sends info mail to user
	 *
	 * @return	string		HTML content message
	 * @see init(),compileMail(), sendMail()
	 */
	function sendInfoMail()	{
		if ($this->conf['infomail'] && $this->conf['email.']['field'])	{
			$fetch = t3lib_div::_GP('fetch');
			if ($fetch)	{
					// Getting infomail config.
				$key= trim(t3lib_div::_GP('key'));
				if (is_array($this->conf['infomail.'][$key.'.']))		{
					$config = $this->conf['infomail.'][$key.'.'];
				} else {
					$config = $this->conf['infomail.']['default.'];
				}
				$pidLock='';
				if (!$config['dontLockPid'] && $this->thePid)	{
					$pidLock='AND pid IN ('.$this->thePid.') ';
				}

					// Getting records
				if (t3lib_div::testInt($fetch))	{
					$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField($this->theTable,$this->conf['uidField'],$fetch,$pidLock,'','','1');
				} elseif ($fetch) {	// $this->conf['email.']['field'] must be a valid field in the table!
					$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField($this->theTable,$this->conf['email.']['field'],$fetch,$pidLock,'','','100');
				}

				// Processing records
				if (is_array($DBrows))	{
					//$recipient = $DBrows[0][$this->conf['email.']['field']];
					if ($this->conf['evalFunc'])	{
						$DBrows[0] = $this->userProcess('evalFunc',$DBrows[0]);
					}
					$this->compileMail($config['label'], $DBrows, $this->getFeuserMail($DBrows[0],$this->conf), $this->conf['setfixed.']);
				} elseif ($this->cObj->checkEmail($fetch)) {
					$this->sendMail($fetch, '', '',trim($this->cObj->getSubpart($this->templateCode, '###'.$this->emailMarkPrefix.'NORECORD###')));
				}

				$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_INFOMAIL_SENT###');
			} else {
				$content = $this->metafeeditlib->getPlainTemplate($this->conf,$this->markerArray,'###TEMPLATE_INFOMAIL###');
			}
		} else $content='Error: infomail option is not available or emailField is not setup in TypoScript';
		return $content;
	}

	/**
	 * Compiles and sends a mail based on input values + template parts. Looks for a normal and an "-admin" template and may send both kinds of emails. See documentation in TSref.
	 *
	 * @param	string		A key which together with $this->emailMarkPrefix will identify the part from the template code to use for the email.
	 * @param	array		An array of records which fields are substituted in the templates
	 * @param	mixed		Mail recipient. If string then its supposed to be an email address. If integer then its a uid of a fe_users record which is looked up and the email address from here is used for sending the mail.
	 * @param	array		Additional fields to set in the markerArray used in the substitution process
	 * @return	void
	 */
 			// Notification Mails

                        // mail admin
                        // mail feuser
                        // mail datamail

                        // SetFixed Mails (moderation)...
                        // mail admin
                        // mail feuser
                        // mail datamail ?


	function compileMail($key, $DBrows, $recipient, $setFixedConfig=array())	{
		$GLOBALS['TT']->push('compileMail');
		$mailContent='';
		$key = $this->emailMarkPrefix.$key;

		$userContent['all'] = trim($this->cObj->getSubpart($this->templateCode, '###'.$key.'###'));
		$adminContent['all'] = trim($this->cObj->getSubpart($this->templateCode, '###'.$key.'-ADMIN###'));
		$adminNotifyContent['all'] = trim($this->cObj->getSubpart($this->templateCode, '###'.$key.'-ADMIN_NOTIFY###'));
		$dataContent['all'] = trim($this->cObj->getSubpart($this->templateCode, '###'.$key.'-DATA###'));
		$userContent['rec'] = $this->cObj->getSubpart($userContent['all'], '###SUB_RECORD###');
		$adminContent['rec'] = $this->cObj->getSubpart($adminContent['all'], '###SUB_RECORD###');
		$adminNotifyContent['rec'] = $this->cObj->getSubpart($adminContent['all'], '###SUB_RECORD###');
		$dataContent['rec'] = $this->cObj->getSubpart($dataContent['all'], '###SUB_RECORD###');

		reset($DBrows);
		while(list(,$r)=each($DBrows))	{
			$markerArray = $this->cObj->fillInMarkerArray($this->markerArray, $r,'',0);
			$markerArray = $this->metafeeditlib->setCObjects($this->conf,$this->markerArray,$userContent['rec'].$adminContent['rec'].$dataContent['rec'],$r,$markerArray,'ITEM_');
			$markerArray['###SYS_AUTHCODE###'] = $this->authCode($r);
			$markerArray = $this->setfixed($markerArray, $setFixedConfig, $r);

			if ($userContent['rec'])	$userContent['accum'] .=$this->cObj->substituteMarkerArray($userContent['rec'], $markerArray);
			if ($adminContent['rec'])	$adminContent['accum'].=$this->cObj->substituteMarkerArray($adminContent['rec'], $markerArray);
			if ($dataContent['rec'])	$dataContent['accum'].=$this->cObj->substituteMarkerArray($dataContent['rec'], $markerArray);
		}

		if ($userContent['all'])	$userContent['final'] .=$this->cObj->substituteSubpart($userContent['all'], '###SUB_RECORD###', $userContent['accum']);
		if ($adminContent['all'])	$adminContent['final'].=$this->cObj->substituteSubpart($adminContent['all'], '###SUB_RECORD###', $adminContent['accum']);
		if ($dataContent['all'])	$dataContent['final'].=$this->cObj->substituteSubpart($dataContent['all'], '###SUB_RECORD###', $dataContent['accum']);

		if (t3lib_div::testInt($recipient))	{
			$fe_userRec = $GLOBALS['TSFE']->sys_page->getRawRecord('fe_users',$recipient);
			$recipient=$fe_userRec['email'];
		}

		$GLOBALS['TT']->setTSlogMessage('Template key: ###'.$key.'###, userContentLength: '.strlen($userContent['final']).', adminContentLength: '.strlen($adminContent['final']));
		$dataEmail=$DBrows[0][$this->conf['email.']['dataMailField']];

		$this->sendMail($recipient, $this->conf['email.']['admin'],$dataEmail, $userContent['final'], $adminContent['final'],$dataContent['final']);
		$GLOBALS['TT']->pull();
	}

	/**
	 * Actually sends the requested mails (through $this->cObj->sendNotifyEmail)
	 *
	 * @param	string		Recipient email address (or list)
	 * @param	string		Possible "admin" email address. Will enable sending of admin emails if also $adminContent is provided
	 * @param	string		Content for the regular email to user
	 * @param	string		Content for the admin email to administrator
	 * @return	void
	 * @access private
	 * @see compileMail(), sendInfoMail()
	 */
	function sendMail($recipient, $admin, $data,$content='', $adminContent='',$dataContent='')	{
		//dataEmail
		if ($data && $dataContent && $dataContent!='NOMAIL')	{
			if (!$this->isHTMLContent($dataContent))	{
				$admMail = $this->cObj->sendNotifyEmail($dataContent,
									$data,
									'',
									$this->conf['email.']['from'],
									$this->conf['email.']['fromName'],
									$recipient
							);
			} else {
				$this->sendHTMLMail($dataContent,
									$data,
									'',
									$this->conf['email.']['from'],
									$this->conf['email.']['fromName'],
									$recipient
							);
			}
		}

		// Admin mail:
		if ($admin && $adminContent && $adminContent!='NOMAIL')	{
			if (!$this->isHTMLContent($adminContent))	{
				$admMail = $this->cObj->sendNotifyEmail($adminContent,
									$admin,
									'',
									$this->conf['email.']['from'],
									$this->conf['email.']['fromName'],
									$recipient
							);
			} else {
				$this->sendHTMLMail($adminContent,
									$admin,
									'',
									$this->conf['email.']['from'],
									$this->conf['email.']['fromName'],
									$recipient
							);
			}
		}

		//user mail:
		if ($content!='NOMAIL') {
			if (!$this->isHTMLContent($content))	{
				$this->cObj->sendNotifyEmail($content,
									$recipient,
									'',			// ($admMail ? '' : $admin), 		// If the special administration mail was not found and send, the regular is...
									$this->conf['email.']['from'],
									$this->conf['email.']['fromName'],
									$admin				//modif by CMD - add return path information
							);
			} else {
				$this->sendHTMLMail($content,
									$recipient,
									'',			// ($admMail ? '' : $admin), 		// If the special administration mail was not found and send, the regular is...
									$this->conf['email.']['from'],
									$this->conf['email.']['fromName'],
									$admin 				//modif by CMD - add return path information
							);
			}
		}
	}

	/**
	 * Detects if content is HTML (looking for <html> tag as first and last in string)
	 *
	 * @param	string		Content string to test
	 * @return	boolean		Returns true if the content begins and ends with <html></html>-tags
	 */
	function isHTMLContent($c)	{
		$c = trim($c);
		$first = strtolower(substr($c,0,6));
		$last = strtolower(substr($c,-7));
		if ($first.$last=='<html></html>')	return 1;
	}

	/**
	 * Sending HTML email, using same parameters as tslib_cObj::sendNotifyEmail()
	 * NOTICE: "t3lib_htmlmail" library must be included for this to work, otherwise an error message is outputted.
	 *
	 * @param	string		The message content. If blank, no email is sent.
	 * @param	string		Comma list of recipient email addresses
	 * @param	string		IGNORE this parameter
	 * @param	string		"From" email address
	 * @param	string		Optional "From" name
	 * @param	string		Optional "Reply-To" header email address.
	 * @return	void
	 * @access private
	 * @see sendMail(), tslib_cObj::sendNotifyEmail()
	 */
	function sendHTMLMail($content,$recipient,$dummy,$fromEmail,$fromName,$replyTo='')	{
		if (trim($recipient) && trim($content))	{
			$cls=t3lib_div::makeInstanceClassName('t3lib_htmlmail');
			if (class_exists($cls))	{	// If htmlmail lib is included, then generate a nice HTML-email
				$parts = spliti('<title>|</title>',$content,3);
				$subject = trim($parts[1]) ? trim($parts[1]) : 'TYPO3 FE Admin message';

				$Typo3_htmlmail = t3lib_div::makeInstance('t3lib_htmlmail');
				$Typo3_htmlmail->start();
				$Typo3_htmlmail->useBase64();

				$Typo3_htmlmail->subject = $subject;
				$Typo3_htmlmail->from_email = $fromEmail;
				$Typo3_htmlmail->from_name = $fromName;
				$Typo3_htmlmail->replyto_email = $replyTo ? $replyTo : $fromEmail;
				$Typo3_htmlmail->replyto_name = $replyTo ? '' : $fromName;
				//modif by CMD - add return path information
				$Typo3_htmlmail->returnPath = $replyTo ? $replyTo : $fromEmail;
				$Typo3_htmlmail->organisation = '';
				$Typo3_htmlmail->priority = 3;

					// HTML
				$Typo3_htmlmail->theParts['html']['content'] = $content;	// Fetches the content of the page
				$Typo3_htmlmail->theParts['html']['path'] = '';
				$Typo3_htmlmail->extractMediaLinks();
				$Typo3_htmlmail->extractHyperLinks();
				$Typo3_htmlmail->fetchHTMLMedia();
				$Typo3_htmlmail->substMediaNamesInHTML(0);	// 0 = relative
				$Typo3_htmlmail->substHREFsInHTML();
				$Typo3_htmlmail->setHTML($Typo3_htmlmail->encodeMsg($Typo3_htmlmail->theParts['html']['content']));

					// PLAIN
				$Typo3_htmlmail->addPlain('');

					// SET Headers and Content
				$Typo3_htmlmail->setHeaders();
				$Typo3_htmlmail->setContent();
				$Typo3_htmlmail->setRecipient($recipient);

		//		debug($Typo3_htmlmail->theParts);
				$Typo3_htmlmail->sendtheMail();
			} else {
				debug('SYSTEM ERROR: No HTML-mail library loaded. Set "page.config.incT3Lib_htmlmail = 1" is your TypoScript template.');
			}
		}
	}

	/*****************************************
	 *
	 * Various helper functions
	 *
	 *****************************************/


	/**
	 * Returns true if authentication is OK based on the "aC" code which is a GET parameter set from outside with a hash string which must match some internal hash string.
	 * This allows to authenticate editing without having a fe_users login
	 * Uses $this->authCode which is set in init() by "t3lib_div::_GP('aC');"
	 *
	 * @param	array		The data array for which to evaluate authentication
	 * @return	boolean		True if authenticated OK
	 * @see authCode(), init()
	 */
	function aCAuth($r)	{
		if ($this->authCode && !strcmp($this->authCode,$this->authCode($r)))	{
			return true;
		}
	}

	/**
	 * Creating authentication hash string based on input record and the fields listed in TypoScript property "authcodeFields"
	 *
	 * @param	array		The data record
	 * @param	string		Additional string to include in the hash
	 * @return	string		Hash string of $this->codeLength (if TypoScript "authcodeFields" was set)
	 * @see aCAuth()
	 */
	function authCode($r,$extra='')	{
		$l=$this->codeLength;
		if ($this->conf['authcodeFields'])	{
			$fieldArr = t3lib_div::trimExplode(',', $this->conf['authcodeFields'], 1);
			$value='';
			while(list(,$field)=each($fieldArr))	{
				$value.=$r[$field].'|';
			}
			$value.=$extra.'|'.$this->conf['authcodeFields.']['addKey'];
			if ($this->conf['authcodeFields.']['addDate'])	{
				$value.='|'.date($this->conf['authcodeFields.']['addDate']);
			}
			$value.=$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
			return substr(md5($value), 0,$l);
		}
	}

	/**
	 * Adding keys to the marker array with "setfixed" GET parameters
	 *
	 * @param	array		Marker-array to modify/add a key to.
	 * @param	array		TypoScript properties configuring "setfixed" for the plugin. Basically this is $this->conf['setfixed.'] passed along.
	 * @param	array		The data record
	 * @return	array		Processed $markerArray
	 * @see compileMail()
	 */
	function setfixed($markerArray, $setfixed, $r)	{
		$pluginId=$this->conf['pluginId'];
		if (is_array($setfixed))	{
			reset($setfixed);
			while(list($theKey,$data)=each($setfixed))	{
				if (!strcmp($theKey,'DELETE'))	{
					$recCopy = $r;
					$string='&cmd['.$pluginId.']=setfixed&sFK='.rawurlencode($theKey).'&rU['.$pluginId.']='.$r[$this->conf['uidField']];

					$string.='&aC='.$this->setfixedHash($recCopy,$data['_FIELDLIST']);
					$markerArray['###SYS_SETFIXED_DELETE###'] = $string;
					$markerArray['###SYS_SETFIXED_HSC_DELETE###'] = htmlspecialchars($string);
				} elseif (strstr($theKey,'.'))	{
					$theKey = substr($theKey,0,-1);
					if (is_array($data))	{
						reset($data);
						$recCopy = $r;
						$string='&cmd['.$pluginId.']=setfixed&sFK='.rawurlencode($theKey).'&rU['.$pluginId.']='.$r[$this->conf['uidField']];
						while(list($fieldName,$fieldValue)=each($data))	{
							$string.='&fD['.$fieldName.']='.rawurlencode($fieldValue);
							$recCopy[$fieldName]=$fieldValue;
						}
						$string.='&aC='.$this->setfixedHash($recCopy,$data['_FIELDLIST']);
						$markerArray['###SYS_SETFIXED_'.$theKey.'###'] = $string;
						$markerArray['###SYS_SETFIXED_HSC_'.$theKey.'###'] = htmlspecialchars($string);
					}
				}
			}
		}
		return $markerArray;
	}

	/**
	 * Creating hash string for setFixed. Much similar to authCode()
	 *
	 * @param	array		The data record
	 * @param	string		List of fields to use
	 * @return	string		Hash string of $this->codeLength (if TypoScript "authcodeFields" was set)
	 * @see setfixed(),authCode()
	 */
	function setfixedHash($recCopy,$fields='')	{

		if ($fields)	{
			$fieldArr = t3lib_div::trimExplode(',',$fields,1);
			reset($fieldArr);
			while(list($k,$v)=each($fieldArr))	{
				$recCopy_temp[$k]=$recCopy[$v];
			}
		} else {
			$recCopy_temp=$recCopy;
		}
		$rec='';
		if (is_array($recCopy)) {
			$rec=implode('|',$recCopy_temp);
		}
		$encStr = $rec.'|'.$this->conf['authcodeFields.']['addKey'].'|'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		$hash = substr(md5($encStr),0,$this->codeLength);
		return $hash;
	}


    /**
    * Returns true if preview display is on.
    *
    * @return	boolean
    */
	function isPreview()	{
		return ($this->conf[$this->conf['cmdKey'].'.']['preview'] && $this->preview);
	}

	/**
	 * Creates an instance of class "t3lib_basicFileFunctions" in $this->fileFunc (if not already done)
	 *
	 * @return	void
	 */
	function createFileFuncObj()	{
		if (!$this->fileFunc)	{
			$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		}
	}

	/**
	 * If TypoScript property clearCacheOfPages is set then all page ids in this value will have their cache cleared
	 *
	 * @return	void
	 */
	function clearCacheIfSet()	{
		if ($this->conf['clearCacheOfPages'])	{
			$cc_pidList = $GLOBALS['TYPO3_DB']->cleanIntList($this->conf['clearCacheOfPages']);
			$GLOBALS['TSFE']->clearPageCacheContent_pidList($cc_pidList);
		}
	}

	/**
	 * Returns an error message for the field/command combination inputted. The error message is looked up in the TypoScript properties (evalErrors.[fieldname].[command]) and if empty then the $label value is returned
	 *
	 * @param	string		Field name
	 * @param	string		Command identifier string
	 * @param	string		Alternative label, shown if no other error string was found
	 * @return	string		The error message string
	 */
	function getFailure($theField, $theCmd, $label)	{
		return isset($this->conf['evalErrors.'][$theField.'.'][$theCmd]) ? $this->conf['evalErrors.'][$theField.'.'][$theCmd] : $label;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/fe_adminLib.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/fe_adminLib.inc']);
}
?>
