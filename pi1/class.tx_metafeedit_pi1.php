<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Christophe Balisky <christophe@balisky.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License,or
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
/**
* Plugin 'Meta FE edit' for the 'meta_feedit' extension.
*
* @author Christophe Balisky <christophe@balisky.org>
*/


require_once(PATH_tslib."class.tslib_pibase.php");
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_ajax.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit.php');

class tx_metafeedit_pi1 extends tslib_pibase {
	var $prefixId="tx_metafeedit";// Same as class name
	var $scriptRelPath="pi1/class.tx_metafeedit_pi1.php";	// Path to this script relative to the extension dir.
	var $extKey="meta_feedit";// The extension key.
	var $metafeeditlib;
	var $perfArray=array();
	//var $pi_checkCHash=TRUE;		// use of cHash for url parameters & cache ???
	var $pi_checkCHash=False;		// use of cHash for url parameters & cache ???
	
	/**
	* Main
	* New typoscript rules :
	* plugin.tx_metafeedit.default. is root typoscript for everything that should be applied to all plugins .. (that means you cannot have a plugin with an id called default... TODO must be checked ...).
	* plugin.tx_metafeedit.[pluginid]. is root typoscript for all plugin specific typoscript...
	* plugin.tx_metafeedit.default. field.[fieldName] or plugin.tx_metafeedit.[pluginid]. field.[fieldName]  is root typoscript for all field specific typoscript.
	* plugin.tx_metafeedit.default. table.[tableName] or plugin.tx_metafeedit.[pluginid]. table.[tableName]  is root typoscript for all table specific typoscript. (is this still useful ?).
	* plugin.tx_metafeedit.default. lang.[llanguage] or plugin.tx_metafeedit.[pluginid].  lang.[llanguage]  is root typoscript for all language  specific typoscript. field and table specific roots may follow

	* @param [type]  $content: ...
	* @param [type]  $conf: ...
	* @return [type]  ...
	*/
	
	function main($content='',$conf='',$configurationFile=''){
		$TTA[]=  "<br/>main Elapsed Time :".(microtime(true)-$GLOBALS['g_TT']).' s';
		$DEBUG='';
		//global $PAGES_TYPES;		
		if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');	 
		// Meta feedit library init
		$this->metafeeditlib=t3lib_div::makeInstance('tx_metafeedit_lib');
		$GLOBALS['TSFE']->includeTCA();
		$this->pi_setPiVarDefaults();
		$this->pi_initPIflexForm(); // Init FlexForm configuration for plugin
		$this->pi_loadLL();
		$this->lconf=array(); // Setup our storage array...
		// We try by default in fileadmin/reports
		if ($configurationFile && file_exists('fileadmin/reports/'.$configurationFile)) {
			
			if (!class_exists('Tx_MetaFeedit_Lib_PidHandler') )require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/Lib/PidHandler.php');
			$pidHandler=t3lib_div::makeInstance('Tx_MetaFeedit_Lib_PidHandler');
			$configstore=json_decode(str_replace(array("\n","\t"),"",file_get_contents('fileadmin/reports/'.$configurationFile)),true);
			$conf=$configstore['tsconf'];
			$piFlexForm=$configstore['flexForm'];
			$piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF'];
			$pid=intval($piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
			if ($pid==0 && $piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']) $pid=$pidHandler->getPid($piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
			if ($pid) $piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']=$pid;
						
			//@todo use pidhandler here if necessary
			// we try otherwise the default file ....

		} elseif ($configurationFile && file_exists($configurationFile)) {
			
			if (!class_exists('Tx_MetaFeedit_Lib_PidHandler') ) require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/Lib/PidHandler.php');
			$pidHandler=t3lib_div::makeInstance('Tx_MetaFeedit_Lib_PidHandler');
			$configstore=json_decode(str_replace(array("\n","\t"),"",file_get_contents($configurationFile)),true);
			$conf=$configstore['tsconf'];
			$piFlexForm=$configstore['flexForm'];
			$piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF'];
			$pid=intval($piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
			if ($pid==0 && $piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']) $pid=$pidHandler->getPid($piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
			if ($pid) $piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']=$pid;
						
			//@todo use pidhandler here if necessary
			//echo 'page : '.$piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF'];
		} else {
			if ($configurationFile) die ('Configuration file '.$configurationFile.' does not exist.');
			// Assign the flexform data to a local variable for easier access
			$piFlexForm=$flexForm=$this->cObj->data['pi_flexform'];
		}
		
		$versionArray=explode('.',phpversion());
		if ($versionArray[0]<5) die('Extension meta_feedit requires php5 !');
		// Hack for php4 compatibility
		if (!function_exists('htmlspecialchars_decode')) {				 
			/**
			* htmlspecialchars_decode
			*
			* @param [type]  $str: ...
			* @param [type]  $quote_style: ...
			* @return [type]  ...
			*/

			function htmlspecialchars_decode ($str,$quote_style=ENT_COMPAT) {
				return strtr($str,array_flip(get_html_translation_table(HTML_SPECIALCHARS,$quote_style)));
			}
		}

		// Traverse the entire array based on the language...
		// and assign each configuration option to $this->lConf array...
		foreach ($piFlexForm['data'] as $sheet => $data )
			foreach ($data as $lang => $value )
				foreach ($value as $key => $val )
					$this->lconf[$key]=$this->pi_getFFvalue($piFlexForm,$key,$sheet);

		// $lconf array : configuration from BE flexform
		$lconf=$this->lconf;
		
		//@todo Why on earth do I have to do this ?
		if (!$lconf['fetable']) return '';

		$mfconf=$conf['metafeedit.'];
		$mfconf['pageType']=$GLOBALS['TSFE']->type;
		
		$mfconf['performanceaudit']=$lconf['debugPerformances'];

		if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 Start ']=$this->metafeeditlib->displaytime()." Seconds"; 
	    if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 Conf size ']=strlen(serialize($conf))." Bytes"; 
	    if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 Conf metafeedit size ']=strlen(serialize($mfconf))." Bytes"; 
		
		if (($lconf['referenceMetafeedit'])||($lconf['referenceMetafeeditText'])){

		if ($lconf['referenceExcludeDbRelation']){
			$lconf['referenceKeyExclude'].=',page,referenceMetafeedit,clearCacheOfPages,listPid,backPagePid,gridPid,gridBackPagePid,createPid,T3SourceTreePid,T3TreeTargetPid,T3AdminUid,T3FEGroupsPID,editPid,editBackPagePid,allowedGroups';
		}

		$excludedKeys=t3lib_div::trimexplode(',',$lconf['referenceKeyExclude']);
		$temp_uid=$lconf['referenceMetafeedit']?$lconf['referenceMetafeedit']:$lconf['referenceMetafeeditText'];
		$res=$GLOBALS['TYPO3_DB']->exec_SELECTquery('pi_flexform','tt_content','uid='.$temp_uid);
		if ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
		//	unset($this->lconf);
		
		$piFlexForm=$row['pi_flexform'];
		$piFlexForm=t3lib_div::xml2array($piFlexForm);	

			// Traverse the entire array based on the language...
			// and assign each configuration option to $this->lConf array...
			foreach ($piFlexForm['data'] as $sheet => $data ){
					foreach ($data as $lang => $value ){
						foreach ($value as $key => $val ){
							if (!in_array($key,$excludedKeys)){
								$this->lconf[$key]=$this->pi_getFFvalue($piFlexForm,$key,$sheet);

							}
						}
					}
				}
				// $lconf array : configuration from BE flexform

				$lconf=$this->lconf;
			}

		}
				
		// We handle cookies !		
		if (is_array($this->piVars['cookies'])) foreach($this->piVars['cookies'] as $key=>$value) {
			setcookie($key,$value, time()+3600*24*100,'/');
			//echo "$key => $value";
		}
		// Config priorities :
		/*		
		
		0) FlexForm data
		
		1) default TS config :  
		
		plugin.tx_metafeedit_pi1.0
		
		2) specific TS config
		
		plugin.tx_metafeedit_pi1.{pluginId}

		3) Session data 
		
		*/
		
		
		$lconf['fetable']=trim($lconf['fetable']);
		if (!$lconf['feeditshowfields']) $lconf['feeditshowfields']=$GLOBALS["TCA"][$lconf['fetable']]["interface"]["showRecordFieldList"];
		if (!$lconf['feshowfields']) $lconf['feshowfields']=$GLOBALS["TCA"][$lconf['fetable']]["interface"]["showRecordFieldList"];
		if (!$lconf['feeditshowfields']) $lconf['feeditshowfields']=$GLOBALS["TCA"][$lconf['fetable']]["feInterface"]["fe_admin_fieldList"];
		if (!$lconf['feshowfields']) $lconf['feshowfields']=$GLOBALS["TCA"][$lconf['fetable']]["feIinterface"]["fe_admin_fieldList"];
		if (!$lconf['fecreatefields']) $lconf['fecreatefields']=$GLOBALS["TCA"][$lconf['fetable']]["feInterface"]["fe_admin_fieldList"];
		if (!$lconf['feeditfields']) $lconf['feeditfields']=$GLOBALS["TCA"][$lconf['fetable']]["feInterface"]["fe_admin_fieldList"];
		if (!$lconf['listFields']) $lconf['listFields']=$lconf['feshowfields'];

		$lconf['feshowfields']=$this->correctDivs($lconf['feshowfields'],$lconf['tabLabels']);
		$lconf['feeditshowfields']=$this->correctDivs($lconf['feeditshowfields'],$lconf['tabLabels']);

		// We set flexform array && typoscript array so that we can use it elsewhere;
				
		//$mfconf['flexform.']=$lconf;
		$mfconf['typoscript.']=$conf;
		$mfconf['piVars']=$this->piVars;
		$mfconf['table']=$lconf['fetable'];
		$mfconf['userFunc']='tx_metafeedit_user_feAdmin->user_init';
		$mfconf['includeLibs']='typo3conf/ext/meta_feedit/fe_adminLib.php';
		$mfconf['userFunc_updateArray']='tx_metafeedit_lib->user_updateArray';
		$mfconf['evalFunc']='tx_metafeedit_lib->user_processDataArray';
		$mfconf['required_marker']='*';
		$mfconf['infomail']=0;
		$mfconf['defaultCmd']='edit';//TODO NEW
		$mfconf['keep_piVars']='';
		$mfconf['fe_cruser_id']=$lconf['fecruser_field'];
		$mfconf['fe_crgroup']=$lconf['fecrgroup_field'];
		
		// CBY : pluginId !!! must add flexformupdate here ...
		$mfconf['general.']['pluginUid']=$this->cObj->data['uid'];
		$pluginId=$mfconf['pluginId']=$lconf['pluginId']?$lconf['pluginId']:$this->cObj->data['uid'];	
		
		//echo json_encode($flexForm);
		//$storeConf['lConf']=$lconf;
		
		if (!file_exists('fileadmin/reports')) mkdir('fileadmin/reports');
		$file='fileadmin/reports/'.$pluginId.'.json';
		if (!$configurationFile && t3lib_div::_GP('tx_metafeedit_save')) {

			require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/Lib/PidHandler.php');
			$pidHandler=t3lib_div::makeInstance('Tx_MetaFeedit_Lib_PidHandler');
			$pid=intval($flexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
			$path= $pidHandler->getPath($pid);
			if ($path) $flexForm['data']['sQuickStart']['lDEF']['page']['vDEF']=$path;
			$storeConf['tsconf']=$conf;
		    $storeConf['flexForm']=$flexForm;

			$f = fopen($file, "w");
			if($f) {
				// We update localconf.php
				//echo "\$json".str_replace( '}', "}\n", $ob_out );
				//$w=fwrite($f,json_encode($storeConf));
				$w=fwrite($f,$this->prettyPrint(json_encode($storeConf)));
				if (!$w) echo "Can't write to $file";
				
				$rf=fflush($f);
				if (!$rf) echo "Can't flush to $file";
				$c=fclose($f);	
				if (!$c) echo "Can't close $file";
			}
		}				
		
		$mfconf['general.']['authTpl']=$lconf['generalAuthTemplate'];
		$mfconf['general.']['noPermTpl']=$lconf['generalNoPermTemplate'];
		$mfconf['general.']['fe_cruser_id']=$lconf['fecruser_field'];
		$mfconf['general.']['fe_crgroup']=$lconf['fecrgroup_field'];
		$mfconf['general.']['noUserCheck']=$lconf['noUserCheck'];
		$mfconf['general.']['listMode']=$lconf['defaultListMode'];
		$mfconf['general.']['fieldSize']=8;
		$mfconf['general.']['xhtml']=$lconf['xhtml'];
		$mfconf['general.']['labels.']=$this->metafeeditlib->getMetaFeeditVar2($mfconf,'labels.');		
		$mfconf['general.']['tsOverride.']=$conf['tsOverride.'];
		$mfconf['general.']['useDistinct']=$lconf['useDistinct'];
		// Ajax settings
		$mfconf['ajax.']['ajaxOn']=$lconf['ajaxOn'];
		$mfconf['ajax.']['jqueryCompatMode']=$lconf['jqueryCompatMode'];
		$mfconf['ajax.']['domReady']=$lconf['ajaxDomReady'];
		$mfconf['ajax.']['libs.']=t3lib_div::trimexplode(chr(10),$lconf['ajaxLibs']);
		// Must handle date formats according to editmode and pluginId
		$mfconf['dateformat']=$conf['dateformat']?$conf['dateformat']:($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?"%m-%d-%Y":"%d-%m-%Y");
		$mfconf['timeformat']=$conf['timeformat']?$conf['timeformat']:"%H:%M";
		$mfconf['datetimeformat']=$conf['datetimeformat']?$conf['datetimeformat']:($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?"%H:%M %m-%d-%Y":"%H:%M %d-%m-%Y");
		
		$mfconf['fUField']=$conf[$lconf['pluginId'].'.']['fUField']?$conf[$lconf['pluginId'].'.']['fUField']:$conf['default.']['fUField'];
		$mfconf['fU']=$conf[$lconf['pluginId'].'.']['fU']?$conf[$lconf['pluginId'].'.']['fU']:$conf['default.']['fU'];
		$mfconf['fUKeyField']=$conf[$lconf['pluginId'].'.']['fUKeyField']?$conf[$lconf['pluginId'].'.']['fUKeyField']:$conf['default.']['fUKeyField'];
		  	
		// GRID CONF
	  	
		$mfconf['grid.']['row.']['fUField']=$conf[$lconf['pluginId'].'.']['grid.']['row.']['fUField'];
		$mfconf['grid.']['row.']['fU']=$conf[$lconf['pluginId'].'.']['grid.']['row.']['fU'];
		$mfconf['grid.']['row.']['fUKeyField']=$conf[$lconf['pluginId'].'.']['grid.']['row.']['fUKeyField'];
		$mfconf['grid.']['col.']['fUField']=$conf[$lconf['pluginId'].'.']['grid.']['col.']['fUField'];
		$mfconf['grid.']['col.']['fU']=$conf[$lconf['pluginId'].'.']['grid.']['col.']['fU'];
		$mfconf['grid.']['col.']['fUKeyField']=$conf[$lconf['pluginId'].'.']['grid.']['col.']['fUKeyField'];
		$mfconf['grid.']['langmarks']=$lconf['gridLangmarks'];
  	    $mfconf['grid.']['secondcols.']=$conf[$lconf['pluginId'].'.']['grid.']['secondcols.'];
		$mfconf['grid.']['nbAltRows']=$lconf['gridAltRows'];
		
		// CALENDAR CONF
	  	
		$mfconf['cal.']['row.']['fUField']=$conf[$lconf['pluginId'].'.']['cal.']['row.']['fUField'];
		$mfconf['cal.']['row.']['fU']=$conf[$lconf['pluginId'].'.']['cal.']['row.']['fU'];
		$mfconf['cal.']['row.']['fUKeyField']=$conf[$lconf['pluginId'].'.']['cal.']['row.']['fUKeyField'];
		$mfconf['cal.']['col.']['fUField']=$conf[$lconf['pluginId'].'.']['cal.']['col.']['fUField'];
		$mfconf['cal.']['col.']['fU']=$conf[$lconf['pluginId'].'.']['cal.']['col.']['fU'];
		$mfconf['cal.']['col.']['fUKeyField']=$conf[$lconf['pluginId'].'.']['cal.']['col.']['fUKeyField'];
		$mfconf['cal.']['langmarks']=$lconf['calLangmarks'];
  	    $mfconf['cal.']['secondcols.']=$conf[$lconf['pluginId'].'.']['cal.']['secondcols.'];
		$mfconf['cal.']['nbAltRows']=$lconf['calAltRows'];
		
		//$mfconf['fUKeyField'][$lconf['pluginId']]=$conf[$lconf['pluginId'].'.']['fUKeyField'];
		//$mfconf['fUField'][$lconf['pluginId']]=$conf[$lconf['pluginId'].'.']['fUField'];
		
		$mfconf['list.']['rowField']=$lconf['rowField'];
		$mfconf['list.']['nbAltRows']=$lconf['listAltRows'];
		$mfconf['list.']['nobackbutton']=$conf['list.']['nobackbutton']?$conf['list.']['nobackbutton']:$lconf['listnobackbutton'];
		$mfconf['list.']['colField']=$lconf['colField'];
		$mfconf['list.']['beginDateField']=$lconf['beginDateField'];
		$mfconf['list.']['endDateField']=$lconf['endDateField'];
		$mfconf['list']=1;
		$mfconf['list.']['no_detail']=$lconf['listnodetail'];
		$mfconf['list.']['langmarks']=$lconf['listlangmarks'];
		$mfconf['list.']['show_fields']=$lconf['listFields'];
		$mfconf['list.']['extraFields']=$lconf['listExtraFields'];
		$mfconf['list.']['whereString']=$lconf['listWhereString'];
		$mfconf['list.']['orderByString']=$lconf['listOrderByString'];
		$mfconf['list.']['preOrderByString']=$lconf['listPreOrderByString'];
		$mfconf['list.']['havingString']=$lconf['listHavingString'];
		$mfconf['list.']['jumpPageOnGroupBy']=$lconf['listJumpPageOnGroupBy'];
		$mfconf['list.']['rUJoinField']=$lconf['rUJoinField']?$lconf['rUJoinField']:'uid';
		$mfconf['list.']['groupByFields']=$lconf['groupByFields'];
		$mfconf['list.']['groupByFieldBreaks']=$lconf['groupByFieldBreaks'];
		$mfconf['list.']['hiddenGroupByField.']=$conf['list.']['hiddenGroupByField.'];
		$mfconf['list.']['recordactions']=$lconf['listrecordactions'];
		$mfconf['list.']['orderByFields']=$lconf['orderByFields'];
		$mfconf['list.']['showResultCount']=$this->metafeeditlib->is_extent($conf['showResultCount'])?$conf['showResultCount']:$lconf['showResultCount'];
		$mfconf['list.']['pagefloat']=$this->metafeeditlib->is_extent($conf['pagefloat'])?$conf['pagefloat']:$lconf['pagefloat'];
		$mfconf['list.']['showRange']=$this->metafeeditlib->is_extent($conf['showRange'])?$conf['showRange']:$lconf['showRange'];
		$mfconf['list.']['showFirstLast']=$this->metafeeditlib->is_extent($conf['showFirstLast'])?$conf['showFirstLast']:$lconf['showFirstLast'];
		$mfconf['list.']['dontLinkActivePage']=$this->metafeeditlib->is_extent($conf['dontLinkActivePage'])?$conf['dontLinkActivePage']:$lconf['dontLinkActivePage'];
		$mfconf['list.']['browseBoxWrap']=$this->metafeeditlib->is_extent($conf['browseBoxWrap'])?$conf['browseBoxWrap']:$lconf['browseBoxWrap'];
		$mfconf['list.']['disabledLinkWrap']=$this->metafeeditlib->is_extent($conf['disabledLinkWrap'])?$conf['disabledLinkWrap']:$lconf['disabledLinkWrap'];
		$mfconf['list.']['inactiveLinkWrap']=$this->metafeeditlib->is_extent($conf['inactiveLinkWrap'])?$conf['inactiveLinkWrap']:$lconf['inactiveLinkWrap'];
		$mfconf['list.']['activeLinkWrap']=$this->metafeeditlib->is_extent($conf['activeLinkWrap'])?$conf['activeLinkWrap']:$lconf['activeLinkWrap'];
		$mfconf['list.']['showResultsWrap']=$this->metafeeditlib->is_extent($conf['showResultsWrap'])?$conf['showResultsWrap']:$lconf['showResultsWrap'];
		$mfconf['list.']['browseLinksWrap']=$this->metafeeditlib->is_extent($conf['browseLinksWrap'])?$conf['browseLinksWrap']:$lconf['browseLinksWrap'];
		$mfconf['list.']['prevLinkWrap']=$this->metafeeditlib->is_extent($conf['prevLinkWrap'])?$conf['prevLinkWrap']:$lconf['prevLinkWrap'];
		$mfconf['list.']['nextLinkWrap']=$this->metafeeditlib->is_extent($conf['nextLinkWrap'])?$conf['nextLinkWrap']:$lconf['nextLinkWrap'];
		$mfconf['list.']['firstLinkWrap']=$this->metafeeditlib->is_extent($conf['firstLinkWrap'])?$conf['firstLinkWrap']:$lconf['firstLinkWrap'];
		$mfconf['list.']['lastLinkWrap']=$this->metafeeditlib->is_extent($conf['lastLinkWrap'])?$conf['lastLinkWrap']:$lconf['lastLinkWrap'];
		$mfconf['list.']['searchBox']=$lconf['searchBox'];
		$mfconf['list.']['align.']=$conf[$pluginId.'.']['list.']['align.']?$conf[$pluginId.'.']['list.']['align.']:$conf['list.']['align.'];
		$mfconf['list.']['searchBox.']=$conf['searchBox.'];	
		// advancedSearch
		$mfconf['list.']['advancedSearchFields']=$this->correctFieldSets($lconf['advancedSearchFields']);
		$mfconf['list.']['advancedSearch']=$lconf['advancedSearch'];
		$mfconf['list.']['advancedSearch.']=$conf['advancedSearch.'];
		$mfconf['list.']['advancedSearchConfig.']=$conf['advancedSearchConfig.'];
		$mfconf['list.']['advancedSearchAjaxSelector']=$lconf['advancedSearchAjaxSelector'];
		$mfconf['list.']['advancedSearchAjaxSelector.']=$conf[$lconf['pluginId'].'.']['list.']['advancedSearchAjaxSelector.'];
		$mfconf['list.']['alphabeticalSearch']=$lconf['alphabeticalSearch'];
		$mfconf['list.']['alphabeticalSearch.']=$conf['alphabeticalSearch.'];
		$mfconf['list.']['calendarSearch']=$lconf['calendarSearch'];
		$mfconf['list.']['calendarSearch.']=$conf['calendarSearch.'];
		$mfconf['list.']['pagination']=$lconf['pagination'];
		$mfconf['list.']['pageSize']=$lconf['pageSize'];
		$mfconf['list.']['nbCols']=$lconf['nbCols'];
		$mfconf['list.']['maxPages']=$lconf['maxPages'];
		$mfconf['list.']['sortFields']=$lconf['sortFields'];
		$mfconf['list.']['groupBySize']=$lconf['groupBySize'];
		$mfconf['list.']['displayDirection']=$lconf['displaydirection'];
		$mfconf['list.']['itemTpl']=$lconf['listItemTemplate'];
		$mfconf['list.']['noItemTpl']=$lconf['listNoItemTemplate'];
		$mfconf['list.']['mediaPlayer']=$lconf['listMediaPlayer'];
        $mfconf['list.']['mediaplayerWrap.']=$conf[$lconf['pluginId'].'.']['list.']['mediaplayerWrap.']?$conf[$lconf['pluginId'].'.']['list.']['mediaplayerWrap.']:$conf['default.']['list.']['mediaplayerWrap.'];
		$mfconf['list.']['weekdayNameLength']=3;
		
		// Exports
		$mfconf['list.']['pdf']=$lconf['pdf'];			// R�cup�re l'info de si la case est coch�e
		$mfconf['list.']['csv']=$lconf['csv'];			
		$mfconf['list.']['excel']=$lconf['excel'];
		$mfconf['list.']['euros']=$lconf['euros']; // Deprecated
		$mfconf['list.']['sumFields']=$lconf['sumFields'];
		$mfconf['list.']['TemplatePDF']=$lconf['TemplatePDF'];
		$mfconf['list.']['TemplatePDFDet']=$lconf['TemplatePDFDet'];
		$mfconf['list.']['TemplatePDFTab']=$lconf['TemplatePDFTab'];
		$mfconf['list.']['TemplateCSV']=$lconf['TemplateCSV'];
		$mfconf['list.']['TemplateExcel']=$lconf['TemplateExcel'];
		$mfconf['edit.']['pdf']=$lconf['editpdf']?$lconf['editpdf']:0;			// R�cup�re l'info de si la case est coch�e		
		
		// --------------------------------------------------------------------------------------------------------------------- //

		// GRID
		$mfconf['grid']=1;
		$mfconf['grid.']['ajax']=$lconf['gridAjax'];
		$mfconf['grid.']['edit']=$lconf['gridEdit'];
		$mfconf['grid.']['nbRows']=$lconf['gridRows'];
		$mfconf['grid.']['nbCols']=$lconf['gridCols'];
		$mfconf['grid.']['pdf']=$mfconf['grid.']['gridExportPDF']=$lconf['gridExportPDF'];
		$mfconf['grid.']['csv']=$mfconf['grid.']['gridExportCSV']=$lconf['gridExportCSV'];
		$mfconf['grid.']['xls']=$mfconf['grid.']['gridExportExcel']=$lconf['gridExportExcel'];
		$mfconf['grid.']['gridEuros']=$lconf['gridEuros']; // to be removed ...

		$mfconf['grid.']['rowField']=$lconf['gridRowField'];
		$mfconf['grid.']['colField']=$lconf['gridColField'];
		$mfconf['grid.']['secondaryColFields']=$lconf['secondaryColFields'];
		$mfconf['grid.']['show_fields']=$lconf['gridFields'];
		//$mfconf['grid.']['fields']=$lconf['gridFields'];
		//MODIF CBY
		$mfconf['grid.']['extraFields']=$lconf['gridExtraFields'];
		$mfconf['grid.']['itemTpl']=$lconf['gridItemTemplate'];

		// CALENDAR
		$mfconf['cal']=1;
		$mfconf['cal.']['ajax']=$lconf['calAjax'];
		$mfconf['cal.']['edit']=$lconf['calEdit'];
		$mfconf['cal.']['nbRows']=$lconf['calRows'];
		$mfconf['cal.']['nbCols']=$lconf['calCols'];
		$mfconf['cal.']['calExportPDF']=$lconf['calExportPDF'];
		$mfconf['cal.']['pdf']=$mfconf['cal.']['calExportPDF']=$lconf['calExportPDF'];
		$mfconf['cal.']['csv']=$mfconf['cal.']['calExportCSV']=$lconf['calExportCSV'];
		$mfconf['cal.']['xls']=$mfconf['cal.']['calExportExcel']=$lconf['calExportExcel'];
		$mfconf['cal.']['calEuros']=$lconf['calEuros'];

		$mfconf['cal.']['rowField']=$lconf['calRowField'];
		$mfconf['cal.']['colField']=$lconf['calColField'];
		$mfconf['cal.']['secondaryColFields']=$lconf['secondaryColFields'];
		$mfconf['cal.']['show_fields']=$lconf['calFields'];
		//$mfconf['cal.']['fields']=$lconf['calFields'];
		//MODIF CBY
		$mfconf['cal.']['extraFields']=$lconf['calExtraFields'];
		$mfconf['cal.']['itemTpl']=$lconf['calItemTemplate'];

		$mfconf['blog']=1;
		$mfconf['blog.']['showComments']=$lconf['showComments'];
		$mfconf['blog.']['allowComments']=$lconf['allowComments'];
		$mfconf['blog.']['blogtemplate']=$lconf['blogtemplate'];
		$mfconf['blog.']['captcha']=$lconf['blogCaptcha'];		
		
		$mfconf['create']=1;
		$mfconf['create.']['langmarks']=$lconf['createlangmarks'];
		$mfconf['create.']['fields']=$lconf['fecreatefields'];
		$mfconf['create.']['show_fields']=$lconf['feshowfields'];
		$mfconf['create.']['required']=$lconf['ferequiredfields'];
		$mfconf['create.']['preview']=$lconf['createPreview'];
		$mfconf['create.']['statusScreen']=$lconf['createStatusScreen'];
		$mfconf['create.']['readonlyFields']=$lconf['fecreatereadonlyfields'];
		$mfconf['create.']['screenLoginTpl']=$lconf['createScreenLoginTemplate'];
		$mfconf['create.']['previewLoginTpl']=$lconf['createPreviewLoginTemplate'];
		$mfconf['create.']['screenTpl']=$lconf['createScreenTemplate'];
		$mfconf['create.']['previewTpl']=$lconf['createPreviewTemplate'];
		$mfconf['create.']['savedTpl']=$lconf['createSavedTemplate'];
		$mfconf['create.']['userNotifyTpl']=$lconf['createUserNotifyMailTemplate'];
		$mfconf['create.']['adminNotifyTpl']=$lconf['createAdminNotifyMailTemplate'];
		$mfconf['create.']['dataNotifyTpl']=$lconf['createDataNotifyMailTemplate'];
		$mfconf['create.']['adminValidTpl']=$lconf['createAdminValidMailTemplate'];
		$mfconf['create.']['userNotifyOkTpl']=$lconf['createUserNotifyOkMailTemplate'];
		$mfconf['create.']['userNotifyKoTpl']=$lconf['createUserNotifyKoMailTemplate'];
		$mfconf['create.']['formWrap.']=$conf['createFormWrap.'];
		$mfconf['create.']['userFunc_afterSave']='tx_metafeedit_lib->user_afterSave';
		$mfconf['create.']['hide']=$lconf['hideCreate'];

		$mfconf['edit']=1;
		$mfconf['edit.']['recordactions']=$lconf['editrecordactions'];
		$mfconf['edit.']['userFunc_afterSave']='tx_metafeedit_lib->user_afterSave';
		$mfconf['edit.']['langmarks']=$lconf['editlangmarks'];
		$mfconf['edit.']['formWrap.']=$conf['editFormWrap.'];
		$mfconf['edit.']['fields']=$lconf['feeditfields'];
		$mfconf['edit.']['show_fields']=$lconf['feeditshowfields'];
		$mfconf['edit.']['readonlyFields']=$lconf['feeditreadonlyfields'];
		$mfconf['edit.']['required']=$lconf['ferequiredfields'];
		$mfconf['edit.']['preview']=$lconf['editPreview'];
		$mfconf['edit.']['dontUseHidden']=$lconf['editDontUseHidden'];
		$mfconf['edit.']['dontUseDate']=$lconf['editDontUseDate'];
		$mfconf['edit.']['statusScreen']=$lconf['editStatusScreen'];
		$mfconf['edit.']['screenTpl']=$lconf['editScreenTemplate'];
		$mfconf['edit.']['previewTpl']=$lconf['editPreviewTemplate'];
		$mfconf['edit.']['savedTpl']=$lconf['editSavedTemplate'];
		$mfconf['edit.']['userNotifyTpl']=$lconf['editUserNotifyMailTemplate'];
		$mfconf['edit.']['adminNotifyTpl']=$lconf['editAdminNotifyMailTemplate'];
		$mfconf['edit.']['dataNotifyTpl']=$lconf['editDataNotifyMailTemplate'];
		$mfconf['edit.']['adminValidTpl']=$lconf['editAdminValidMailTemplate'];
		$mfconf['edit.']['userNotifyOkTpl']=$lconf['editUserNotifyOkMailTemplate'];
		$mfconf['edit.']['userNotifyKoTpl']=$lconf['editUserNotifyKoMailTemplate'];
		$mfconf['edit.']['backPagePid']=$lconf['editBackPagePid'];
		$mfconf['edit.']['userCheck']=$lconf['editUserCheck'];
		$mfconf['edit.']['menuLockPid']=1;
		$mfconf['edit.']['hide']=$lconf['hideEdit'];

		$mfconf['delete']=1;
		$mfconf['delete.']['preview']=1;
		$mfconf['delete.']['userCheck']=$lconf['deleteUserCheck'];
		$mfconf['delete.']['previewTpl']=$lconf['deletePreviewTemplate'];
		$mfconf['delete.']['statusScreen']=$lconf['deleteStatusScreen'];
		$mfconf['delete.']['savedTpl']=$lconf['deleteSavedTemplate'];
		$mfconf['delete.']['userNotifyTpl']=$lconf['deleteUserNotifyMailTemplate'];
		$mfconf['delete.']['adminNotifyTpl']=$lconf['deleteAdminNotifyMailTemplate'];
		$mfconf['delete.']['hide']=$lconf['hideDelete'];

		$mfconf['preview.']['fields']=$lconf['fepreviewfields'];
		$mfconf['email.']['from']=$lconf['emailFrom'];
		$mfconf['email.']['fromName']=$lconf['emailFromName'];
		$mfconf['email.']['admin']=$lconf['emailAdmin'];
		$mfconf['email.']['field']=$lconf['dataMailField'];
		$mfconf['email.']['sendAdminMail']=$lconf['sendAdminMail'];
		$mfconf['email.']['sendDataMail']=$lconf['sendDataMail'];
		$mfconf['email.']['dataMailField']=$lconf['dataMailField'];
		$mfconf['email.']['sendFEUserMail']=$lconf['sendFEUserMail'];
		$mfconf['email.']['sendAdminInfoMail']=$lconf['sendAdminInfoMail'];
		$mfconf['email.']['sendDataInfoMail']=$lconf['sendDataInfoMail'];
		$mfconf['email.']['sendFEUserInfoMail']=$lconf['sendFEUserInfoMail'];
		$mfconf['setfixed.']['approve.']['_FIELDLIST']=$lconf['feapprovefields']?$lconf['feapprovefields']:'uid,pid';
		$mfconf['setfixed.']['DELETE.']['_FIELDLIST']=$lconf['fedeletefields']?$lconf['fedeletefields']:'uid,pid';
		$mfconf['setfixed']=1;
		$setfixOverrides=t3lib_div::trimexplode(chr(10),$lconf['fesetfixoverridevalues']);
		foreach($setfixOverrides as $setfixOverride) {
			$setfixOverrideVals=t3lib_div::trimexplode('=',$setfixOverride);
			if (count($setfixOverrideVals)==2) {
			
				$mfconf['setfixed.']['approve.'][$setfixOverrideVals[0]]=$setfixOverrideVals[1];
			}
		}
		//$mfconf['setfixed.']['approve.']['hidden']=0;
		//$mfconf['setfixed.']['approve.']['_FIELDLIST']='uid,pid';
		$mfconf['setfixed.']['DELETE']=1;
		$mfconf['setfixed.']['DELETE.']['_FIELDLIST']='uid,pid';
		if ($conf['setfixed.']) $mfconf['setfixed.']=$conf['setfixed.'];
		$mfconf['setfixed.']['setFixedOkTemplate']=$lconf['setFixedOkTemplate'];
		$mfconf['setfixed.']['setFixedOkDeleteTemplate']=$lconf['setFixedOkDeleteTemplate'];
		$mfconf['setfixed.']['setFixedFailedTemplate']=$lconf['setFixedFailedTemplate'];
		$mfconf['allowedGroups']=$lconf['allowedGroups'];
		$mfconf['editUnique']=$lconf['editUnique'];
		$mfconf['fe_userEditSelf']=$lconf['fe_userEditSelf'];
		$mfconf['generateTemplate']=$lconf['generateTemplate'];
		$mfconf['fetemplate']=$lconf['fetemplate'];
		$mfconf['show_help_icons']=$lconf['showHelpIcons'];
		$mfconf['template_file']=$lconf['template_file'];
		$mfconf['editPid']=$lconf['editPid'];
		$mfconf['listPid']=$lconf['listPid'];
		$mfconf['createPid']=$lconf['createPid'];
		$mfconf['extTables']=$lconf['extTables'];
		$mfconf['authcodeFields.']['addKey']='uid';
		$mfconf['checkT3Rights']=$lconf['checkT3Rights'];
		$mfconf['defaultCmd']=$lconf['defaultCmd']; //TODO NEW
		$mfconf['divide2tabs']=$lconf['divide2tabs'];
		
		$mfconf['debug']=$lconf['debug'];
		$mfconf['debug.']['krumo']=$lconf['debugKrumo'];
		$mfconf['debug.']['sql']=$lconf['debugSQL'];
		$mfconf['debug.']['markerArray']=$lconf['debugMarkerArray'];
		$mfconf['debug.']['langArray']=$lconf['debugLangArray'];
		$mfconf['debug.']['conf']=$lconf['debugConf'];
		$mfconf['debug.']['template']=$lconf['debugTemplate'];
		$mfconf['debug.']['vars']=$lconf['debugVars'];
		$mfconf['debug.']['tsfe']=$lconf['debugTSFE'];
		
		//$mfconf['cacheMode']=$lconf['cacheMode'];
		$mfconf['cacheMode']=$lconf['no_cache']?0:($lconf['cacheMode']?$lconf['cacheMode']:0); // No cache by default
		$mfconf['no_header']=$lconf['noHeader'];
		$mfconf['enableColumns']=$lconf['enableColumns'];
		$mfconf['no_action']=$lconf['noActions'];
		$mfconf['disableDelete']=$lconf['disableDelete'];
		$mfconf['disableEditDelete']=$lconf['disableEditDelete'];
		$mfconf['disableCreate']=$lconf['disableCreate'];
		$mfconf['disableEdit']=$lconf['disableEdit'];
		$mfconf['useTemplate']=$lconf['useTemplate'];
		$mfconf['backPagePid']=$lconf['backPagePid'];
		$mfconf['T3SourceTreePid']=$lconf['T3SourceTreePid'];
		$mfconf['T3TreeTargetPid']=$lconf['T3TreeTargetPid'];
		$mfconf['T3AdminUid']=$lconf['T3AdminUid'];
		$mfconf['T3FEGroupsPID']=$lconf['T3FEGroupsPID'];
		$mfconf['T3TableHomePidField']=$lconf['T3TableHomePidField'];
		$mfconf['T3GroupUids']=$conf['T3GroupUids'];
		$mfconf['foreignTables']=$lconf['foreignTables'];
		$mfconf['idField']=$lconf['idField'];
		$mfconf['recursive']=$lconf['recursive'];
		$mfconf['parseValues']=$lconf['feparsevalues'];
		$EArr=t3lib_div::trimExplode(chr(10),$lconf['feparsevalues']);
		foreach($EArr as $ORV) {
			$OVs=t3lib_div::trimExplode('=',$ORV);
			if (count($OVs)==2) $mfconf['parseValues.'][$OVs[0]]=$OVs[1];
		}
		$mfconf['preview.']['noemptyfields']=$lconf['previewnoemptyfields'];
		$mfconf['pid']=$lconf['page'];
			
		// ts config from plugin meta_feedit_pi1

		$mfconf['whereClause.']=$this->metafeeditlib->getMetaFeeditVar2($mfconf,'whereClause.');
		$mfconf['select.']=$this->metafeeditlib->getMetaFeeditVar2($mfconf,'select.');
		//$conf[$pluginId.'.']['whereClause.'];
		$mfconf['whereString.']=$conf[$pluginId.'.']['whereString.'];
		$mfconf['orderBy.']=$conf['orderBy.'];
		$mfconf['edit.']['whereString.']=$conf['edit.']['whereString.'];
		$mfconf['edit.']['orderBy.']=$conf['edit.']['orderBy.'];
		$mfconf['edit.']['defaultValues.']=$conf[$pluginId.'.']['edit.']['defaultValues.']?$conf[$pluginId.'.']['edit.']['defaultValues.']:$conf['default.']['edit.']['defaultValues.'];
		$mfconf['create.']['whereString.']=$conf['create.']['whereString.'];
		$mfconf['create.']['orderBy.']=$conf['create.']['orderBy.'];
		$mfconf['create.']['defaultValues.']=$conf[$pluginId.'.']['create.']['defaultValues.']?$conf[$pluginId.'.']['create.']['defaultValues.']:$conf['default.']['create.']['defaultValues.'];
		$mfconf['list.']['whereString.']=$conf['list.']['whereString.'];
		$mfconf['list.']['orderBy.']=$conf['list.']['orderBy.'];		
		$mfconf['list.']['header']=$conf['list.']['header'];
		$mfconf['list.']['footer']=$conf['list.']['footer'];
		$mfconf['evalLastSep.']=$conf['evalLastSep.'];
		$mfconf['list.']['groupby.']=$conf[$pluginId.'.']['list.']['groupby.']?$conf[$pluginId.'.']['list.']['groupby.']:$conf['list.']['groupby.'];
		$mfconf['evalSep.']=$conf['evalSep.'];
		$mfconf['evalWrap.']=$conf['evalWrap.'];
		$mfconf['previewWrap.']=$conf['previewWrap.'];
		$mfconf['originUid']=$conf['originUid'];
		$mfconf['originTable']=$conf['originTable'];
		$mfconf['originUidsField']=$conf['originUidsField'];
		$mfconf['label.']=$conf['label.'];

		//stdWraps ...
		
		$mfconf['stdWrap.']=$conf['stdWrap.'];
		$mfconf['fileWrap.']=$conf['fileWrap.'];
		$mfconf['list.']['formWrap.']=$conf['listFormWrap.'];
		$mfconf['list.']['stdWrap.']=$conf['list.']['stdWrap.'];
		$mfconf['list.']['actionStdWrap.']=$conf['list.']['actionStdWrap.'];
		$mfconf['list.']['item_stdWrap.']=$conf['list_item_stdWrap.']?$conf['list_item_stdWrap.']:$conf['list.']['item_stdWrap.']; //deprecated use ['list.']['stdWrap.'] instead
		$mfconf['list.']['groupByFields.']['stdWrap.']=$conf['list_groupByFields_stdWrap.'];
		$mfconf['list.']['groupByCount']=$conf['list_groupByCount'];
		$mfconf['list.']['totalCount']=$conf['list_totalCount'];
		$mfconf['list.']['icon_thumbSize.']=$conf['list.']['icon_thumbSize.'];
		$mfconf['list.']['asFieldSetNames.']=$conf[$pluginId.'.']['list.']['asFieldSetNames.']?$conf[$pluginId.'.']['list.']['asFieldSetNames.']:$conf['default.']['list.']['asFieldSetNames.'];
		$mfconf['list.']['fieldSetNames.']=$conf[$pluginId.'.']['list.']['fieldSetNames.'];
		$mfconf['list.']['imgConf.']=$conf['list.']['imgConf.'];
		$mfconf['create.']['icon_thumbSize.']=$conf['create.']['icon_thumbSize.'];
		$mfconf['create.']['imgConf.']=$conf['create.']['imgConf.'];
		$mfconf['create.']['stdWrap.']=$conf['create.']['stdWrap.'];
		$mfconf['edit.']['stdWrap.']=$conf['edit.']['stdWrap.'];
		$mfconf['edit.']['icon_thumbSize.']=$conf['edit.']['icon_thumbSize.'];
		$mfconf['edit.']['item_stdWrap.']=$conf['edit.']['item_stdWrap.'];
		$mfconf['edit.']['field_stdWrap.']=$conf['edit.']['field_stdWrap.'];
		$mfconf['edit.']['imgConf.']=$conf['edit.']['imgConf.'];
		$mfconf['evalErrors.']=$conf['evalErrors.'];
		$mfconf['_LOCAL_LANG.']=$conf['_LOCAL_LANG.'];
		$mfconf['imgConf.']=$conf['imgConf.'];
	 	$mfconf['mediaImgConf.']=$conf['mediaImgConf.'];
	 	$mfconf['text_in_top_of_form']=$conf['text_in_top_of_form'];

		// CBY special functions
		   
		// Must rename this one or put in backward compatibility!!!
		$mfconf['userFunc_afterSave']=$conf[$pluginId.'.']['userFunc_afterSave']; // Ok
		$mfconf['userFunc_afterSaveAndBeforeStatus']=$conf[$pluginId.'.']['userFunc_afterSaveAndBeforeStatus']; // Ok
		if ($conf[$pluginId.'.']['meta_feedit_afterSave']) $mfconf['userFunc_afterSave']=$conf[$pluginId.'.']['meta_feedit_afterSave']; // Ok
		$mfconf['userFunc_afterInitConf']=$conf[$pluginId.'.']['userFunc_afterInitConf']; // Ok
		$mfconf['userFunc_afterParse']=$conf[$pluginId.'.']['userFunc_afterParse']; // Ok
		$mfconf['userFunc_afterOverride']=$conf[$pluginId.'.']['userFunc_afterOverride']?$conf[$pluginId.'.']['userFunc_afterOverride']:$conf['default.']['userFunc_afterOverride']; // Ok
		$mfconf['create.']['userFunc_afterOverride']=$conf[$pluginId.'.']['create.']['userFunc_afterOverride']?$conf[$pluginId.'.']['create.']['userFunc_afterOverride']:$conf['default.']['create.']['userFunc_afterOverride']; // Ok
		$mfconf['edit.']['userFunc_afterOverride']=$conf[$pluginId.'.']['edit.']['userFunc_afterOverride']?$conf[$pluginId.'.']['edit.']['userFunc_afterOverride']:$conf['default.']['edit.']['userFunc_afterOverride']; // Ok
		$mfconf['userFunc_afterEval']=$conf[$pluginId.'.']['userFunc_afterEval']; // Ok		
		$mfconf['list.']['userFunc_afterWhere']=$conf[$pluginId.'.']['list.']['userFunc_afterWhere'];
		$mfconf['list.']['userFunc_afterMark']=$conf[$pluginId.'.']['list.']['userFunc_afterMark'];
		$mfconf['list.']['userFunc_afterItemMark']=$conf[$pluginId.'.']['list.']['userFunc_afterItemMark'];
		$mfconf['list.']['userFunc_alterSortTabs']=$conf[$pluginId.'.']['list.']['userFunc_alterSortTabs'];
		$mfconf['list.']['userFunc_alterSortOptions']=$conf[$pluginId.'.']['list.']['userFunc_alterSortOptions'];
		$mfconf['grid.']['userFunc_afterRowWhere']=$conf[$pluginId.'.']['grid.']['userFunc_afterRowWhere']; 
		$mfconf['grid.']['userFunc_afterColWhere']=$conf[$pluginId.'.']['grid.']['userFunc_afterColWhere']; 
		$mfconf['grid.']['userFunc_afterSecondaryColWhere']=$conf[$pluginId.'.']['grid.']['userFunc_afterSecondaryColWhere']; 

		$mfconf['list.']['sqlcalcfieldstransforms.']=$conf[$pluginId.'.']['list.']['sqlcalcfieldstransforms.']?$conf[$pluginId.'.']['list.']['sqlcalcfieldstransforms.']:$conf['list.']['sqlcalcfieldstransforms.'];
		$mfconf['create.']['itemsProcFunc.']=$conf[$pluginId.'.']['create.']['itemsProcFunc.'];
		$mfconf['edit.']['itemsProcFunc.']=$conf[$pluginId.'.']['edit.']['itemsProcFunc.'];
		
		
		// We handle override values here

		$OArr=t3lib_div::trimExplode(chr(10),$lconf['listsqlcalcfields']);
		foreach($OArr as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)>=2) {
				$calcField=array_shift($OVs);
				$mfconf['list.']['sqlcalcfields.'][trim($calcField)]=implode('=',$OVs);
				if ($calcField) $CVArr[]=$OVs[0];
			}		
		}
		$OArr=t3lib_div::trimExplode(chr(10),$lconf['listphpcalcfields']);
		foreach($OArr as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$mfconf['list.']['phpcalcfields.'][trim($OVs[0])]=trim($OVs[1]);
				if ($OVs[0]) $CVArr[]=$OVs[0];
			}		
		}

		//Modif CBY
		$CVArr=array();
		$EVArr=array();
		$OArr=t3lib_div::trimExplode(chr(10),$lconf['fecreateoverridevalues']);
		foreach($OArr as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$mfconf['create.']['overrideValues.'][trim($OVs[0])]=trim($OVs[1]);
				if ($OVs[0]) $CVArr[]=$OVs[0];
			}		
		}

		$OArr=t3lib_div::trimExplode(chr(10),$lconf['feeditoverridevalues']);
		foreach($OArr as $ORV) {
			$OVs=t3lib_div::trimExplode('=',$ORV);
			if (count($OVs)==2) {
				$mfconf['edit.']['overrideValues.'][$OVs[0]]=$OVs[1];
				if ($OVs[0]) $EVArr[]=$OVs[0];
			}
		}
		$EArr=t3lib_div::trimExplode(chr(10),$lconf['fecreateevalvalues']);
		foreach($EArr as $ORV) {
			$OVs=t3lib_div::trimExplode('=',$ORV);
			if (count($OVs)==2){
				$mfconf['create.']['evalValues.'][$OVs[0]]=$OVs[1];
				if ($OVs[0]) $CVArr[]=$OVs[0];
			}
		}
		$EArr=t3lib_div::trimExplode(chr(10),$lconf['feeditevalvalues']);
		foreach($EArr as $ORV) {
			$OVs=t3lib_div::trimExplode('=',$ORV);
			if (count($OVs)==2){
				$mfconf['edit.']['evalValues.'][$OVs[0]]=$OVs[1];
				if ($OVs[0]) $EVArr[]=$OVs[0];
			}
		}
		// MODIF CBY
		// here we deduct missing fields from showFields,readonlyFields,overridValues,evalValues and processValues
		$mfconf['edit.']['show_fields']=implode(',',$this->metafeeditlib->clean_array(array_unique(array_merge(t3lib_div::trimExplode(',',$this->correctFieldSets($mfconf['edit.']['show_fields'])),t3lib_div::trimExplode(',',$mfconf['edit.']['readonlyFields'])))));
		$mfconf['edit.']['fields']=implode(',',$this->metafeeditlib->clean_array(array_unique(array_merge($EVArr,t3lib_div::trimExplode(',',$mfconf['edit.']['fields']),t3lib_div::trimExplode(',',$this->correctFieldSets($mfconf['edit.']['show_fields']))))));
		$mfconf['create.']['show_fields']=implode(',',$this->metafeeditlib->clean_array(array_unique(array_merge(t3lib_div::trimExplode(',',$this->correctFieldSets($mfconf['create.']['show_fields'])),t3lib_div::trimExplode(',',$mfconf['create.']['readonlyFields'])))));
		$mfconf['create.']['fields']=implode(',',$this->metafeeditlib->clean_array(array_unique(array_merge($CVArr,t3lib_div::trimExplode(',',$mfconf['create.']['fields']),t3lib_div::trimExplode(',',$this->correctFieldSets($mfconf['create.']['show_fields']))))));

		/*** GLOBAL TCA overrides ... ***/
		
		$GLOBALS['TCA'][$lconf['fetable']]['ctrl']['fe_cruser_id']=$lconf['fecruser_field'];
		
		/**** SET VARIABLES FROM FLEXFORM ****/
		
		$pid=$lconf['page']? $lconf['page'] :$mfconf['pid'];
		$mfconf['pid']=$pid ? $pid :'';

		// Set the noSpecialLoginForm
		if($lconf['noSpecialLoginForm']) {
			$mfconf['create.']['noSpecialLoginForm']=1;
		}
		// Set if frontend login is required
		$mfconf['requireLogin']=$lconf['requireLogin'] ? 1 :$mfconf['requireLogin'];
		//$mfconf['no_header']=$lconf['noHeader'] ? 1 : $mfconf['no_header'];
		//$mfconf['show_help_icons']=$lconf['showHelpIcons'] ? 1 : $mfconf['show_help_icons'];
		// Sets the allowed groups for frontend editing
		//$mfconf['allowedGroups']=$lconf['allowedGroups'] ? $lconf['allowedGroups'] : $mfconf['allowedGroups'];
		// Sets the pages to clear catch of
		$mfconf['clearCacheOfPages']=$lconf['clearCacheOfPages'] ? $lconf['clearCacheOfPages'] :($mfconf['clearCacheOfPages']?$mfconf['clearCacheOfPages']:$GLOBALS['TSFE']->id);
		
		//forced value for CMD (to avoid malicious visitor to change the CMD value in the URL
		$mfconf['forcedCmd']=$conf['forcedCmd'];
		// Set the default cmd
		$mfconf['defaultCmd']='edit';
		$mfconf['defaultCmd']=$lconf['defaultCmd'];
		$cmdInt=$lconf['defaultCmd'];
		switch($cmdInt) {
			case 0:
				$mfconf['defaultCmd']='list';
				break;
			case 1:
				$mfconf['defaultCmd']='create';
				break;
			case -1: // ???? CBY What is this ?
				$mfconf['defaultCmd']='edit';
				break;
			case 2:
				$mfconf['defaultCmd']='edit';
				break;		
			default :
				$mfconf['defaultCmd']='edit';
		}
		
		// We prepare session variables
		//CBY07
		// is this necessary ?
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();
		$metafeeditvars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
		if ($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['referer']) $mfconf['piVars']['referer'][$pluginId]=$metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['referer'];
				
		// We handle here all transmitted variables ....
		// _GP Vars
			 
		//==================================================================================
		// Handling INCOMING VARIABLES !!!
    	// All variables must be indexed on pluginID !!! to avoid problems whend 2 plugins are put in same page.
		// Variable priorites are :
		// Typoscript
		// Flexform
		// POST
		// GET
				
		// rU is edited Uid :
		
		//$mfconf['inputvar.']['fedata']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'FE');
    
    	// We should handle keep var ...
		$mfconf['inputvar.']=array();
		$mfconf['inputvar.']['fedata']=t3lib_div::_GP('FE');
		$mfconf['inputvar.']['BACK']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'BACK');
		$mfconf['inputvar.']['ajx']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'ajx');
		$mfconf['inputvar.']['cameFromBlog']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'cameFromBlog');
		$mfconf['inputvar.']['lV']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'lV',true);
		$mfconf['inputvar.']['lField']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'lField',true);	
		$mfconf['inputvar.']['rU']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'rU');
		$mfconf['inputvar.']['rU.']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'rU.');
		$mfconf['inputvar.']['cmd']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'cmd',true);
		//$mfconf['inputvar.']['backURL']=htmlspecialchars_decode((string)$this->metafeeditlib->getMetaFeeditVar($mfconf,'backURL',true));
		// backUrl is now also indexed on pageType (for ajax).
		$mfconf['inputvar.']['backURL']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'backURL',true);
		if (is_array($mfconf['inputvar.']['backURL'])) foreach($mfconf['inputvar.']['backURL'] as $type=>$val) {
			$mfconf['inputvar.']['backURL'][$type]=htmlspecialchars_decode((string)$val);
		}
		$mfconf['inputvar.']['preview']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'preview');
		$mfconf['inputvar.']['blog']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'blog');
		$mfconf['inputvar.']['doNotSave']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'doNotSave');
		$mfconf['inputvar.']['submit']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'submit');
		$mfconf['inputvar.']['advancedSearch']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'advancedSearch',true);
		$mfconf['inputvar.']['pointer']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'pointer',false);
		$mfconf['inputvar.']['sword']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'sword',true);
		$mfconf['inputvar.']['sort']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'sort',true);
		$mfconf['inputvar.']['sortLetter']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'sortLetter',true);
		//direction dans lequel on change l'ordre de tri de l'enregistrement
		$mfconf['inputvar.']['orderDir']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'orderDir')?1:0;
		$mfconf['inputvar.']['orderDir.']['rU']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'orderU');
		$mfconf['inputvar.']['orderDir.']['dir']=$this->metafeeditlib->getMetaFeeditVar($mfconf,'orderDir');
		$resetsearch=$this->metafeeditlib->getMetaFeeditVar($mfconf,'reset');
		$resetorderby=$this->metafeeditlib->getMetaFeeditVar($mfconf,'resetorderby');
				
		if ($resetsearch) {
			  unset ( $mfconf['inputvar.']['advancedSearch']);
			  unset ( $mfconf['inputvar.']['sword']);
			  unset ( $mfconf['inputvar.']['sortLetter']);
			  unset ( $_GET['tx_metafeedit']['reset']);
			  unset ( $_POST['tx_metafeedit']['reset']);
			  unset ( $_GET['tx_metafeedit']['advancedSearch']);
			  unset ( $_POST['tx_metafeedit']['advancedSearch']);
			  unset ( $this->piVars['advancedSearch']);
			  unset ($mfconf['piVars']['advancedSearch']);
			  unset ( $metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['sword']);
			  unset ( $metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['sortLetter']);
			  unset ( $metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['advancedSearch']);
		}
		if ($resetorderby) {
			  unset ( $mfconf['inputvar.']['sort']);
			  unset ( $metafeeditvars[$pluginId]['sort']);
			  if (is_array($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId])) unset ($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['sort']);
			  unset ( $_GET['tx_metafeedit']['resetorderby']);
		}
		// persistent variables :		

		$mfconf['blogData']=$mfconf['inputvar.']['blog'];
		
		// cmd : Display mode
		
		$cmd=$mfconf['inputvar.']['cmd'];	
		$mfconf['inputvar.']['cmd']=$cmd ? $cmd :$mfconf['defaultCmd'];
		$mfconf['inputvar.']['cmd']=$conf['forcedCmd'] ? $conf['forcedCmd'] :$mfconf['inputvar.']['cmd'];
		if (!$mfconf['inputvar.']['cmd']) $mfconf['inputvar.']['cmd']=$conf['defaultCmd'];
		if (!$mfconf['inputvar.']['cmd']) $mfconf['inputvar.']['cmd']='edit';
		//if ($mfconf['inputvar.']['cmd']=='create') 
		// we check here editUnique Creation Mode (must optimize this);
        
		if ($conf['editUnique']) {
			//@todo why do i have to do this ?
			$mfconf['inputvar.']['cmd']='edit';
			
			$mmTable='';
			$DBSELECT=$this->metafeeditlib->DBmayFEUserEditSelectMM($this->theTable,$GLOBALS['TSFE']->fe_user->user, $mfconf['allowedGroups'],$mfconf['fe_userEditSelf'],$mmTable,$mfconf).$GLOBALS['TSFE']->sys_page->deleteClause($this->theTable);
			$thePid = intval($mfconf['pid']) ? intval($mfconf['pid']) : $GLOBALS['TSFE']->id;
			if ($thePid) {
				$lockPid = $mfconf['edit.']['menuLockPid'] ? ' AND pid='.intval($thePid) : '';
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->theTable.($mmTable?','.$mmTable:'') , '1 '.$lockPid.$DBSELECT);
				$resu=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
				if ($resu===0) {
					$mfconf['inputvar.']['cmd']='create';
					//TODO If we have no record we allow creation .... (!this must be put in Pi1) otherwise templates won't be loaded 
				}
			}
        }
		
		// we handle back url here by calculating referer..

		/*if (!$mfconf['inputvar.']['BACK']) {
			if (!$this->piVars['referer'][$pluginId]) {
				$backURL=$_SERVER['HTTP_REFERER'];
				$this->piVars['referer'][$pluginId]=$_SERVER['HTTP_REFERER'];
			} else {
				$backURL=$this->piVars['referer'][$pluginId];
			}
			$patha=parse_url($backURL);
			$path=$patha['path'];
			if (substr($path,0,1)=='/') $path=str_replace('/','',$path);
			$tlconf['parameter']=$GLOBALS['TSFE']->id;
			$tl=$this->cObj->typoLink_URL($tlconf);
			
			if (trim($tl)!=$path) {
				$backURL=str_replace("&BACK[".$pluginId."]=1","",$backURL);
				if (!strpos($backURL,'?')) $backURL.='?';
				$backURL.="&BACK[".$pluginId."]=1";
				$mfconf['inputvar.']['backURL']=$backURL;
			}
		}*/
        
		// we handle back url here by calculating referer..
		//9002 is page type if called through ajax
		//
		if (!is_array($mfconf['inputvar.']['backURL'])) $mfconf['inputvar.']['backURL']=array();
		if (!$mfconf['inputvar.']['BACK'] && !t3lib_div::_GP('ajxcb')) {
			if (!$this->piVars['referer'][$pluginId]) {
				$mfconf['inputvar.']['backURL'][$mfconf['pageType']]=$_SERVER['HTTP_REFERER'];
				$this->piVars['referer'][$pluginId]=$_SERVER['HTTP_REFERER'];
			} else {
				$mfconf['inputvar.']['backURL'][$mfconf['pageType']]=$this->piVars['referer'][$pluginId];
			}
			$patha=parse_url($mfconf['inputvar.']['backURL'][$mfconf['pageType']]);
			$path=$patha['path'];
			if (substr($path,0,1)=='/') $path=str_replace('/','',$path);
			$tlconf['parameter']=$GLOBALS['TSFE']->id;
			$tl=$this->cObj->typoLink_URL($tlconf);
			
			if (trim($tl)!=$path) {
				$mfconf['inputvar.']['backURL'][$mfconf['pageType']]=str_replace("&BACK[".$pluginId."]=1","",$mfconf['inputvar.']['backURL'][$mfconf['pageType']]);
				if (!strpos($mfconf['inputvar.']['backURL'][$mfconf['pageType']],'?')) $mfconf['inputvar.']['backURL'][$mfconf['pageType']].='?';
				$mfconf['inputvar.']['backURL'][$mfconf['pageType']].="&BACK[".$pluginId."]=1";
				
				//$mfconf['inputvar.']['backURL']=$backURL;
			}
		}
		
		// we set session variables we want to remember 
		//=============================================
		//MODIF CBY
		
		if ($this->piVars['referer'][$pluginId]) $metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]['referer']=$this->piVars['referer'][$pluginId];		
		if (is_array($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId])) {
			$metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]=array_merge($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId],$mfconf['inputvar.']);
		} elseif (is_array($mfconf['inputvar.'])) {
			$metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]=array();
			$metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]=array_merge($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId],$mfconf['inputvar.']);
		}
		
		if (!is_array($metafeeditvars[$GLOBALS['TSFE']->id][$pluginId])) $metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]=$mfconf['inputvar.'];
		$GLOBALS["TSFE"]->fe_user->setKey('ses','metafeeditvars',$metafeeditvars);
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
 		// We update backURL depending on configuration parameters =========================
		$mfconf['inputvar.']['backURL']=$this->metafeeditlib->makeBackURLTypoLink($mfconf,$mfconf['inputvar.']['backURL']);	
		//==================================================================================
		// JAVASCRIPT LIBRARIES ...
		if ($mfconf['ajax.']['ajaxOn'] && !t3lib_div::_GP('ajx')) {

			//$GLOBALS['TSFE']->pSetup['headerData.']=array('1'=>'TEXT','1.'=>array('value'=>'<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/jquery.js"></script>'))+$GLOBALS['TSFE']->pSetup['headerData.'];
			$GLOBALS['TSFE']->additionalHeaderData['meta_feedit_jquery'] = '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/jquery.js"></script>';
			$GLOBALS['TSFE']->additionalHeaderData['meta_feedit_jqmodal'] = '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/jqModal.js"></script>';
			$GLOBALS['TSFE']->additionalHeaderData['meta_feedit_ajax'] = '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/meta_feedit_ajax.js"></script>';
			$GLOBALS['TSFE']->additionalHeaderData['meta_feedit_jqDnR'] = '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/jqDnR.js"></script>';
			$GLOBALS['TSFE']->additionalHeaderData['meta_feedit_AC_RunActiveContent'] = '<script type="text/javascript" src="/'.t3lib_extMgm::siteRelPath($this->extKey).'res/AC_RunActiveContent.js"></script>';
			if ($mfconf['ajax.']['domReady']) $GLOBALS['TSFE']->additionalHeaderData['meta_feedit_jQueryDomReady_'.$pluginId] = "<script type='text/javascript' >jQuery().ready(function() {".$mfconf['ajax.']['domReady']."});</script>";		
			if (is_array($mfconf['ajax.']['libs.'])) {
				foreach($mfconf['ajax.']['libs.'] as $lib) {
					if ($lib) $GLOBALS['TSFE']->additionalHeaderData['meta_feedit_libs'] .='<script type="text/javascript" src="'.$lib.'"></script>';
				}
			}

			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'windows'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/window.js"></script>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'effects'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/effects.js"></script>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'debug'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/debug.js"></script>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'extdebug'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/extended_debug.js"></script>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'effects'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/effects.js"></script>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'windowscss'] = '<link href="'.t3lib_extMgm::siteRelPath($this->extKey).'res/themes/default.css" rel="stylesheet" type="text/css"/>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'windowscss2'] = '<link href="'.t3lib_extMgm::siteRelPath($this->extKey).'res/themes/mac_os_x.css" rel="stylesheet" type="text/css"/>';
			//$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'windowscssdbg'] = '<link href="'.t3lib_extMgm::siteRelPath($this->extKey).'res/themes/debug.css" rel="stylesheet" type="text/css"/>';
			$GLOBALS['TSFE']->additionalHeaderData['meta_feedit_jqModalcss'] = '<link href="'.t3lib_extMgm::siteRelPath($this->extKey).'res/jqModal.css" rel="stylesheet" type="text/css"/>';
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['meta_feedit'] = '$().ready(function() {$(\'#modalWindow\').jqm({overlay: 70,modal: true,trigger: \'a\',target: \'#jqmContent\'});});$(\'#modalWindow\').jqmShow();';
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['meta_feedit'] = 'jQuery().ready(function() {jQuery(\'#modalWindow\').jqm({overlay: 70,modal: true,trigger: \'.jqModal\',target: \'#jqmContent\',onHide: closeModal,onShow: openInFrame});jQuery(\'#modalWindow\').jqmShow();});';
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['meta_feedit'] = 'jQuery().ready(function() {jQuery(\'#modalWindow\').jqm();});';
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['meta_feedit'] = 'jQuery().ready(function() {jQuery(\'#modalWindow\').jqm({overlay: 70,modal: true,trigger: \'.tx-metafeedit-link-edit a\',target: \'#jqmContent\',onHide: closeModal,onShow: openInFrame});});jQuery(\'#modalWindow\').jqm();jQuery(\'#modalWindow\').jqmShow();';
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['initLightbox()'].= "showWindowSpectateurs('<table><tr><td>".addslashes($this->ajaxWidgets->comboList('spectateurs','','','handlePersondata','setPersonRecap','Recherche Spectateurs',25))."<a href=\'#\' ".addslashes($emptyPersonsClick).">&nbsp;Vider la liste</a></td></tr><tr><td><div id=\'selectedPersons\' class=\'selectedpersons\'>".addslashes($this->metabookinglib->getSelectablePersons())."</div></td></tr><tr><td><a href=\'#\' ".addslashes($createClick)." >Nouveau spectateur</a></td></tr><tr><td><a href=\'#\' ".addslashes($lastMinuteClick)." >Last Minute (Surbooking)</a></td></tr><tr><td>".addslashes($this->ajaxWidgets->comboList('reglements','','','handlePaymentData','setPayment','Recherche Reglement par num ou spectateur ou tiers payant',15))."</td></tr><tr><td><a href=\'#\' ".addslashes($editResteDuClick).">Restes Dus</a>, <a href=\'#\' ".addslashes($editResteDuDetClick).">Restes Dus Detailles</a>, <a href=\'#\' ".addslashes($caisseJourClick).">Caisse Jour</a></td></tr></table>');";
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['initLightbox()'].= "showWindowTickets('<div id=\'selectedRelay\' class=\'selectedrelay\'>".addslashes($this->metabookinglib->getRelay($idrelais))."</div><a href=\'#\' ".addslashes($emptyTransactionsClick).">&nbsp;Vider la liste (sauf r�glements en cours)</a>, <a href=\'#\' ".addslashes($onClickPaiement).">&nbsp;R�glement en cours</a>, <a href=\'#\' ".addslashes($refreshTransactionsClick).">&nbsp;Actualiser</a><div id=\'selectedTickets\' class=\'selectedtickets\'>".addslashes($this->metabookinglib->getSelectedTickets(array()))."</div>');";
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['initLightbox()'].= "showWindowTarifs('<div id=\'selectedTarifs\' class=\'selectedtarifs\'>".addslashes($this->metabookinglib->getSelectableTarifs())."</div>');";
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['initLightbox()'].= "showWindowPrereservations('".addslashes($this->ajaxWidgets->comboList('seance','','','handleSeanceData','setSelectedSeance','Recherche S&eacute;ance',20))."<a href=\'#\' ".addslashes($selectAllPrereservationsClick).">&nbsp;Tout S�lectionner</a><a href=\'#\' ".addslashes($deselectAllPrereservationsClick).">&nbsp;Tout d�selectionner</a><a href=\'#\' ".addslashes($emptyPrereservationsClick).">&nbsp;Vider la liste</a><a href=\'#\' ".addslashes($refreshPrereservationsClick).">&nbsp;Actualiser</a><div id=\'selectedPlaces\' class=\'selectedplaces\'>".addslashes($this->metabookinglib->getSelectablePlaces())."</div>');";
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['initLightbox()'].= "showWindowSeances('<div id=\'planSeance\' class=\'planseance\'></div>');";
			//$GLOBALS['TSFE']->JSeventFuncCalls['onload']['initLightbox()'].= "showWindowCaisse('<div id=\'billetterie\' class=\'billetterie\'></div>');";
	 	}
	 	//CBY
		$mfconf['parentObj']=&$this;
		if ($mfconf['userFunc_afterInitConf']) t3lib_div::callUserFunction($mfconf['userFunc_afterInitConf'],$mfconf,$this);

		/**** INIT mthfeedit ****/
		// Performance audit
		if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 Loaded ']=$this->metafeeditlib->displaytime()." Seconds"; 
		//xdebug_stop_trace();
		//xdebug_dump_function_trace();
		$mthfeedit=t3lib_div::makeInstance('tx_metafeedit');
		// Performance Audit
		if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 Meta feedit initialise done:']=$this->metafeeditlib->displaytime()." Seconds"; 
		if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 Conf metafeedit size Fin ']=strlen(serialize($mfconf))." Bytes"; 
		$content=$mthfeedit->init($this,$mfconf);
		// Performance audit
		if ($mfconf['performanceaudit']) $this->perfArray['Perf Pi1 End ']=$this->metafeeditlib->displaytime(); 
		$mfconf['disablePrefixComment']=$GLOBALS['TSFE']->config['config']['disablePrefixComment'];
		return $this->metafeeditlib->pi_wrapInBaseClass($content,$mfconf);
	}

	//MODIF CBY
	/**
	* setGlobalValues
	*
	* @param [type]  $fL: ...
	* @param [type]  $tabLabels: ...
	* @return [type]  ...
	*/	
		
	function setGlobalValues(&$conf,$metafeeditvars) {	
		$pluginId=$conf['metafeedit.']['pluginId'];
		$table=$conf['metafeedit.']['table'];
		/*$A=t3lib_div::_GP($table);
		if  (is_array($A) && $A['lV'] && $A['lField']) {
			$conf['metafeedit.']['inputvar.']['lField']=$A['lField'];
			$conf['metafeedit.']['inputvar.']['lV']=$A['lV'];
		} else {
			$conf['metafeedit.']['inputvar.']['lV']=$this->metafeeditlib->getMetaFeeditVar($conf['metafeedit.'],'lV');
			$conf['metafeedit.']['inputvar.']['lField']=$this->metafeeditlib->getMetaFeeditVar($conf['metafeedit.'],'lField');
		}*/
	}
	/**
     * Pretty-print JSON string
     *
     * Use 'format' option to select output format - currently html and txt supported, txt is default
     * Use 'indent' option to override the indentation string set in the format - by default for the 'txt' format it's a tab
     *
     * @param string $json Original JSON string
     * @param array $options Encoding options
     * @return string
     */
    public  function prettyPrint($json, $options = array())
    {
        $tokens = preg_split('|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = "";
        $indent = 0;

        $format= "txt";

        $ind = "\t";

        if(isset($options['format'])) {
            $format = $options['format'];
        }

        switch ($format):
            case 'html':
                $line_break = "<br />";
                $ind = "\$nbsp;\$nbsp;\$nbsp;\$nbsp;";
                break;
            default:
            case 'txt':
                $line_break = "\n";
                $ind = "\t";
                break;
        endswitch;

        //override the defined indent setting with the supplied option
        if(isset($options['indent'])) {
            $ind = $options['indent'];
        }

        foreach($tokens as $token) {
            if($token == "") continue;

            $prefix = str_repeat($ind, $indent);
            if($token == "{" || $token == "[") {
                $indent++;
                if($result != "" && $result[strlen($result)-1] == $line_break) {
                    $result .= $prefix;
                }
                $result .= "$token$line_break";
            } else if($token == "}" || $token == "]") {
                $indent--;
                $prefix = str_repeat($ind, $indent);
                $result .= "$line_break$prefix$token";
            } else if($token == ",") {
                $result .= "$token$line_break" ;
            } else {
                $result .= $prefix.$token;
            }
        }
        return $result;
   }
	
	/**
	* correctDivs
	*
	* @param [type]  $fL: ...
	* @param [type]  $tabLabels: ...
	* @return [type]  ...
	*/
		
	function correctDivs($fL,$tabLabels){
		$fLA=explode(',',$fL);
		$tLA=explode(chr(10),$tabLabels);
		$fLA2=array();
		$o=0;
		foreach ($fLA as $fN) {
			$param=explode(';',$fN);
			if ($param[0]=='--div--') {
				$fLA2[]="--div--;".($tLA[$o]?str_replace ("'","`",$tLA[$o]):"Tab $o");
				$o++;
			} else {
				$fLA2[]=$fN;
			}
		}
		$fL2=implode(',',$fLA2);
		return $fL2;
	}
	
	/**
	* correctFieldSets
	*
	* @param [type]  $fL: ...
	* @param [type]  $tabLabels: ...
	* @return [type]  ...
	*/
		
	function correctFieldSets($fL){
		$fLA=explode(',',$fL);
		$fsbc=0;
		$fsec=0;
		foreach ($fLA as $fN) {
			$param=explode(';',$fN);
			if ($param[0]=='--fsb--') {
				$fLA2[]="--fsb--;FSB$fsbc";
				$fsbc++;
				continue;
			} 
			if ($param[0]=='--fse--') {
				$fLA2[]="--fse--;FSE$fsec";
				$fsec++;
				continue;
			}
			$fLA2[]=$fN;
		}
		$fL2=implode(',',$fLA2);
		return $fL2;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/meta_feedit/pi1/class.tx_metafeedit_pi1.php"]){
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/meta_feedit/pi1/class.tx_metafeedit_pi1.php"]);
}

?>