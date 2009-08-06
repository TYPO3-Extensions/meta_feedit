<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Morten Tranberg Hansen (mth@daimi.au.dk)
*  (c) 2006 Christophe BALISKY (cbalisky@metaphore.fr)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*f
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
 * This is a API for crating and editing records in the frontend.
 * The API is build on top of fe_adminLib.
 * See documentation or extensions 'news_feedit' and 'joboffers_feedit' for examples how to use this API
 *
 * @author	Morten Tranberg Hansen <mth@daimi.au.dk>
 * @author	Christophe BALISKY <cbalisky@metaphore.fr>
 */

// Necessary includes

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_site.TYPO3_mainDir.'sysext/lang/lang.php');
require_once(PATH_t3lib.'class.t3lib_parsehtml_proc.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
define(PATH_typo3,PATH_site.TYPO3_mainDir); // used in template.php
require_once(PATH_site.TYPO3_mainDir.'template.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_treecopy.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_ajax.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_lib.php');
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_widgets.php');

if(t3lib_extmgm::isLoaded('rtehtmlarea')) require_once(t3lib_extMgm::extPath('rtehtmlarea').'pi2/class.tx_rtehtmlarea_pi2.php');
if(t3lib_extmgm::isLoaded('rlmp_dateselectlib')) require_once(t3lib_extMgm::extPath('rlmp_dateselectlib').'class.tx_rlmpdateselectlib.php');

// replace with meta_ldap later on

if(t3lib_extmgm::isLoaded('eu_ldap')) require_once(t3lib_extMgm::extPath('eu_ldap').'mod1/class.tx_euldap_div.php');
if (t3lib_extMgm::isLoaded('kb_md5fepw')) require_once(t3lib_extMgm::extPath('kb_md5fepw').'class.tx_kbmd5fepw_funcs.php');

require_once(t3lib_extMgm::extPath('meta_feedit').'fe_adminLib.inc');

// FE editing class

class tx_metafeedit extends  tslib_pibase {
    // Private fields
    var $prefixId = 'tx_metafeedit';		// Same as class name
    var $scriptRelPath = 'class.tx_metafeedit.php';	// Path to this script relative to the extension dir.
    var $extKey = 'meta_feedit';	// The extension key.
    var $conf;
    var $cObj; // contains an initialized cObj, so we can use the cObj functions whenever we want to.
    var $templateObj; // contains an initialized templateObj, so we can use the template functions whenever we want to.(ex with dynamic tabs)
    var $caller; // the caller
    var $table; // the table
    var $TCA; // contains the complete TCA of the $table
    var $cmd; // this is 'hopefully' the same value as fe_adminLib's cmd.
    var $id_field = ''; // the field from a record witch will identify the record.
    var $additionalJS_end = array(); // JS to be added after the end of the content.
    // Fields for the RTE API
    var $strEntryField;
    var $RTEObj;
    var $docLarge = 0;
    var $RTEcounter = 0;
    var $FCounter=0;
    var $formName;
    var $additionalJS_initial = '';// Initial JavaScript to be printed before the form (should be in head, but cannot due to IE6 timing bug)
    var $additionalJS_pre = array();// Additional JavaScript to be printed before the form (works in Mozilla/Firefox when included in head, but not in IE6)
    var $additionalJS_post = array();// Additional JavaScript to be printed after the form
    var $additionalJS_submit = array();// Additional JavaScript to be executed on submit
    var $PA = array(
    	  'itemFormElName' =>  '',
    	  'itemFormElValue' => '',
    	  );
    var $specConf = array();
    var $metafeeditlib;
    var $thisConfig = array();
    var $RTEtypeVal = 'text';
    var $thePidValue;
    var $TCATables=array();
    var $performanceaudit; // performance audit flag
    //var $_LOCAL_LANG = array(); // Language override

    /**
    * init : Main method ...
    *
    * @param	[object]    $caller: calaing object (instance of PI1).
    * @param	[array]		$conf: configuration array
    * @return	[string]	content to display.
    */
    
  	function init(&$caller,&$conf)	{

        $this->initialize($caller,$conf);
        if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Init done :']=$this->metafeeditlib->displaytime()." Seconds"; 
        // command specific initialisation 
        $this->initCmd($conf);
				//DRAW RTE
				/*
				if (count($conf['RTE'])) {
						$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
						$this->thisConfig = $pageTSConfig['RTE.']['default.']['FE.'];
						$this->thePidValue = $GLOBALS['TSFE']->id;
				}
				foreach($conf['RTE'] as $key=>$RTE) {
					if ($RTE['cmdmode']=='edit' || $RTE['cmdmode']=='create' ) {
		                $this->RTEcounter=$key;
		                $this->formName =$conf['RTE'][$key]['formName'];
						$this->strEntryField = $conf['RTE'][$key]['field'];
						$conf['RTE'][$key]['spec']=$this->specConf = $specialConf;                
						$this->PA=$conf['RTE'][$key]['PA'];
						$this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');//&t3lib_BEfunc::RTEgetObj();
						$conf['RTEItem'][$key] = $this->RTEObj->drawRTE($this,$conf['RTE'][$key]['table'],$conf['RTE'][$key]['field'],$row=array(), $this->PA, $this->specConf, $this->thisConfig, $this->RTEtypeVal, '', $this->thePidValue);
					}
				}
				*/
		
        $this->conf=&$conf; // to be removed !!!  Can it be removed ? CBY
        if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Init feAconf done :']=$this->metafeeditlib->displaytime()." Seconds"; 
        if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf after Init feAConf size ']=strlen(serialize($conf))." Bytes"; 

        // we call fe_adminLib.inc here ...
        $conf['caller_additionalJS_post']=$this->additionalJS_post;
        $conf['caller_additionalJS_end']=$this->additionalJS_end;
		
        if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf before USER_INT call size ']=strlen(serialize($conf))." Bytes"; 
				// In No Cache or Mixt Cache We call USER_INT object
				if ($conf['cacheMode']<2) {
				    $content = $this->cObj->cObjGetSingle('USER_INT',&$conf);
				} else {
					// In Full cache mode we use user object and count on cHASH to renew cache ..
					$feAdm=t3lib_div::makeInstance('tx_metafeedit_user_feAdmin');
					$content=$feAdm->user_init("",$conf);
				}
				//die($content);

		    if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit USER_INT call done :']=$this->metafeeditlib->displaytime()." Seconds"; 
		
				/**** ADDS THE REQUIRED JAVASCRIPTS ****/
				$content = $this->getJSBefore($conf) . $content;
		    // XAJAX form handler. Must not be generated if we are in an ajax call.
		    $onSubmit = ' onsubmit="return false;" ';
		    $form=t3lib_div::_GP('ajx')?'':'<form style="display:inline;height:10px;padding:0px;margin:0px;" '.$onSubmit.' action="#" method="post" enctype="multipart/form-data" id="xfm" name="xfm">'.
    		'<input type="hidden" id="mfdt_cmd" name="'.$this->prefixId.'[cmd]" value="" />'.
    		'<input type="hidden" id="mfdt_code" name="'.$this->prefixId.'[code]" value="" />'.
    		'<input type="hidden" id="mfdt_prefix" name="'.$this->prefixId.'[prefix]" value="" />'.
    		'<input type="hidden" id="mfdt_mode" name="'.$this->prefixId.'[mode]" value="" />'.
    		'<input type="hidden" id="mfdt_data" name="'.$this->prefixId.'[data]" value="" />'.
    		'<input type="hidden" id="mfdt_tdata" name="'.$this->prefixId.'[tdata]" value="" />'.
    		'<input type="hidden" id="mfdt_page" name="'.$this->prefixId.'[page]" value="" />'.
    		'<input type="hidden" id="mfdt_pagesize" name="'.$this->prefixId.'[pagesize]" value="" />'.
    		'<input type="hidden" id="mfdt_callbacks" name="'.$this->prefixId.'[callbacks]" value="" />'.
    		'<input type="hidden" id="mfdt_eventdata" name="'.$this->prefixId.'[eventdata]" value="" />'.
    		'<input type="hidden" id="mfdt_table" name="'.$this->prefixId.'[table]" value="" />'.
    		'<input type="hidden" id="mfdt_labelField" name="'.$this->prefixId.'[labelField]" value="" />'.
    		'<input type="hidden" id="mfdt_numField" name="'.$this->prefixId.'[numField]" value="" />'.
    		'<input type="hidden" id="mfdt_fields" name="'.$this->prefixId.'[fields]" value="" />'.
    		'<input type="hidden" id="mfdt_whereField" name="'.$this->prefixId.'[whereField]" value="" />'.
    		'<input type="hidden" id="mfdt_labels" name="'.$this->prefixId.'[labels]" value="" />'.
    		'<input type="hidden" id="mfdt_orderBy" name="'.$this->prefixId.'[orderBy]" value="" />'.
				'</form>';
		if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf size ']=strlen(serialize($conf))." Bytes"; 
		return ($conf['performanceaudit']?t3lib_div::view_array($this->caller->perfArray):'').$form.$content;
  	}    

		// for template wizards
		
  	function initTpl(&$caller,&$conf)	{      			
        //$this->initialize($caller,$conf);
        //if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Init done :']=$this->metafeeditlib->displaytime()." Seconds"; 
        // command specific initialisation 
        //$this->initCmd($conf);	
        $this->metafeeditlib=t3lib_div::makeInstance('tx_metafeedit_lib');
  	}    



    /**
    * initialize : initializes object...
    *
    * @param	[type]		$caller: ...
    * @param	[type]		$conf: ...
    * @return	[type]		...
    */
    
    function initialize(&$caller,&$conf) {
        $this->caller = $caller;
        $conf['caller']=&$caller; // 100 K
        $this->metafeeditlib=$caller->metafeeditlib;
        if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit cache Mode : '.$conf['cacheMode']]=$this->metafeeditlib->displaytime()." Seconds"; 
  	    if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf Enter size ']=strlen(serialize($conf))." Bytes"; 
        $conf['prefixId']= 'tx_metafeedit';
        $conf['$additionalJS_post'] = array();
        $conf['$additionalJS_end'] = array();

        //$conf['performanceaudit']=$caller->performanceaudit;
        
        // Cache ??
        if ($conf['cacheMode']==0) $GLOBALS['TSFE']->set_no_cache();        
        $this->cObj=$GLOBALS['TSFE']->cObj;
        $this->templateObj = t3lib_div::makeInstance('mediumDoc');
        $this->table = $conf['table'];

        //$conf['cmd'] = (string)t3lib_div::_GP('cmd') ? (string)t3lib_div::_GP('cmd') : $conf['defaultCmd'];
        //$conf['cmd'] = (string)$conf['forcedCmd'] ? $conf['forcedCmd'] : $conf['cmd'];
        
        // we check here editUnique Creation Mode
                
        if ($conf['editUnique']) {
        	$mmTable='';
        	$DBSELECT=$this->metafeeditlib->DBmayFEUserEditSelectMM($this->table,$GLOBALS['TSFE']->fe_user->user, $conf['allowedGroups'],$conf['fe_userEditSelf'],$mmTable,$conf).$GLOBALS['TSFE']->sys_page->deleteClause($this->table);
        	$thePid = intval($conf['pid']) ? intval($conf['pid']) : $GLOBALS['TSFE']->id;
            $lockPid = $conf['edit.']['menuLockPid'] ? ' AND pid='.intval($thePid) : '';
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $this->table.($mmTable?','.$mmTable:'') , '1 '.$lockPid.$DBSELECT);
            $resu=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
            // CBY I must improve this condition !!
        	if ($resu===0 &&  $this->cmd!='setfixed') $conf['inputvar.']['cmd']='create';
        }
        
        if (!$conf['inputvar.']['cmd']) $conf['inputvar.']['cmd']='edit';
        //$conf['cmd']=$this->cmd;
        //	debug("WARNING:: NO COMMAND SPECIFIED FOR THE SCRIPT","NO COMMAND");
        
    	$this->id_field = $conf['label.'][$FT]?$conf['label.'][$FT]:($conf['idField']?$conf['idField']:$GLOBALS['TCA'][$this->table]['ctrl']['label']);
    	if (!$this->id_field) $this->id_field='uid';
    	$conf["table_label"]=$this->id_field;
    
    	// init $this->RTEObj if rtehtmlarea is availiable
    	//if(t3lib_extmgm::isLoaded('rtehtmlarea') && !$this->RTEObj)
        //$this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');//&t3lib_BEfunc::RTEgetObj();//
        // TODO : move next lines tpo pi1 ...
		if ($conf['list.']['advancedSearch']) {
			$conf['keep_piVars']= $conf['keep_piVars']? $conf['keep_piVars'].',advancedSearch':'advancedSearch';
		}
		
		$this->LoadLanguageConf($conf);
 		$this->LoadTCAs($conf);
   
		/**** Init Robert Lemkes dateselectlib if it is loaded  ****/
		if(t3lib_extmgm::isLoaded('rlmp_dateselectlib')) tx_rlmpdateselectlib::includeLib();
		$this->conf=&$conf; // TODO : to be removed !!! CBY
  	}
  	
  	/**
    * LoadTCAs : Loads Tabel TCA with user overrides...
    *
    * @param	[array]		$conf: configuration array
    */
    
    function LoadTCAs(&$conf) {
        #    $GLOBALS['TSFE']->includeTCA(); // Uncomment cause extensions using this API should be able to modify TCA array before used here.
  	    if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf after Language size ']=strlen(serialize($conf))." Bytes"; 
        
        t3lib_div::loadTCA($this->table);
        //t3lib_div::loadTCA('tx_metafeedit_comments');
        
        // We handle Foreign Table TCA definitions here ...
        
        $FTRels=explode(',',$conf['foreignTables']);
        $FTs=array();
        $FTs[$this->table]=$this->table;
        foreach ($FTRels as $FTRel) {
        	if ($FTRel && $GLOBALS['TCA'][$this->table]['columns'][$FTRel]['config']['foreign_table']) $FTs[$GLOBALS['TCA'][$this->table]['columns'][$FTRel]['config']['foreign_table']]=$GLOBALS['TCA'][$this->table]['columns'][$FTRel]['config']['foreign_table'];
        }
        
        // We handle foreign tables of fields
        $lfields=t3lib_div::trimexplode(',',$conf['list.']['show_fields']);
        foreach ($lfields as $lf) {
        	if ($lf && $GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table']) $FTs[$GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table']]=$GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table'];
        }
        
       // We handle foreign tables of exta fields
        $lfields=t3lib_div::trimexplode(',',$conf['list.']['extraFields']);	
        foreach ($lfields as $lf) {
        	if ($lf && $GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table']) $FTs[$GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table']]=$GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table'];
        }
       // We handle foreign tables of fields
        $lfields=t3lib_div::trimexplode(',',$conf['list.']['advancedSearchFields']);
        foreach ($lfields as $lf) {
        	if ($lf && $GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table']) $FTs[$GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table']]=$GLOBALS['TCA'][$this->table]['columns'][$lf]['config']['foreign_table'];
        }
        
        if (!in_array('fe_users',$FTs)) $FTs['fe_users']='fe_users';
        if (!in_array('tx_metafeedit_comments',$FTs)) $FTs['tx_metafeedit_comments']='tx_metafeedit_comments';
        $conf['TCATables']=$FTs;
        //krumo($conf['TCATables']);

		//We handle here tables from other software than T3 !!
		//TODO : We should do this for every foreign table
	    $conf['uidField']=$GLOBALS["TCA"][$this->table]['ctrl']['uidField']?$GLOBALS["TCA"][$this->table]['ctrl']['uidField']:'uid';
   		if ($conf['debug']) echo t3lib_div::view_array(array('UIDFIELD'=>$conf['uidField']));

		foreach($FTs as $FTable) {
		    $this->metafeeditlib->makeTypo3TCAForTable($conf['TCAN'],$FTable);
    }

		if ($conf["extTables"]) {
  		  $extKeys=t3lib_div::trimExplode(chr(10),$conf["extTables"]);
  			$this->mergeExtendingTCAs($extKeys);
		}

		//cmd load item proc func
		foreach ($GLOBALS["TCA"][$this->table]['columns'] as $keyField => $fieldValues) {
			if ($GLOBALS["TCA"][$this->table]['columns'][$keyField]["config"]["itemsProcFunc"]) {
				$procExt = t3lib_extMgm::extPath(tx_div::guessKey($GLOBALS["TCA"][$this->table]['columns'][$keyField]["config"]["itemsProcFunc"]));
				$procExtKey = explode('->', $GLOBALS["TCA"][$this->table]['columns'][$keyField]["config"]["itemsProcFunc"]);
				if (is_array($procExtKey)) include_once($procExt.'class.'.$procExtKey[0].'.php');
			}
		}
		

		/**** CONFIGURE TCA ****/
		// here we should calculate fieldList from showFields, evalFields, and override fields..
		$GLOBALS["TCA"][$this->table]["feInterface"]["fe_admin_fieldList"] = $conf['create.']['fields'] ? $conf['create.']['fields'].($conf['edit.']['fields']?','.$conf['edit.']['fields']:'') : $conf['edit.']['fields'];
		if($conf['fe_cruser_id'])
  		$GLOBALS["TCA"][$this->table]['ctrl']['fe_cruser_id'] = $conf['fe_cruser_id'];
		if($conf['fe_crgroup_id'] && $conf['allowedGroups']) {
  			$GLOBALS["TCA"][$this->table]['ctrl']['fe_crgroup_id'] = $conf['fe_crgroup_id'];
		}
     $conf['TCAN'][$this->table]=$GLOBALS["TCA"][$this->table];//CBYTCAN
		
	    // Set private TCA var
		//$this->TCA = &$GLOBALS["TCA"][$this->table];
		//$this->TCAN = &$GLOBALS["TCA"]; // can I remove this ?
  	if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf after TCA size 0 ']=strlen(serialize($conf))." Bytes"; 
		  //krumo($conf['TCAN']);
	    //$conf['TCAN']=&$GLOBALS["TCA"]; // 400 K ...
		  //krumo($conf['TCAN']);

  	  if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf after TCA size ']=strlen(serialize($conf))." Bytes"; 
    }
    
  	
    /**
    * LoadLanguageConf : Loads Language Configuration array for translations ...
    *
    * @param	[array]		$conf: configuration array
    */
    
    function LoadLanguageConf(&$conf) {
  	  if ($conf['performanceaudit']) $this->caller->perfArray['class.tx_metafeedit Conf before Language size ']=strlen(serialize($conf))." Bytes"; 
  	    
    	// loads default locallang
    	$this->LOCAL_LANG = $GLOBALS['TSFE']->readLLfile(t3lib_extMgm::extPath($this->extKey).'locallang.php');
    	// loads callers locallang
    	$this->LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($this->LOCAL_LANG,$this->caller->LOCAL_LANG);
        
        // if we use static info table we must get language file. 
        // TOCHECK : Do we still need this ?
        
        if(t3lib_extmgm::isLoaded('sr_static_info')) {
        	$filepath=t3lib_extMgm::extPath('sr_static_info').'pi1/locallang.php';
        	if (file_exists($filepath)) {
        		$stat_lang=$GLOBALS['TSFE']->readLLfile($filepath);
        		$this->LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($this->LOCAL_LANG,$stat_lang);
            }
        }
        
        // get override language data coming from typoscript. Should handle multiple languages ...
        // TOOPTIMIZE : We should only load default and local language translations ... 
       
         if (is_array($conf['_LOCAL_LANG.'])){
            foreach($conf['_LOCAL_LANG.'] as $key=>$valarr) {
		$nkey=substr($key,0,strpos($key,'.')); 
            	foreach($valarr as $skey=>$sval) {
					//modif cmd - on ne regénère pas - met chaque info dans une clef précise - on garde au minimum table.champ, le reste est ajoute ensuite
					if (is_array($sval)) {
            	        $svala=$sval;
            	        foreach($svala as $skey2=>$sval) {
            	            $skey2=$skey.$skey2;
							while (is_array($sval)) {  // if fieldname has several '.' in it we regenrate whole name  here...
		            	        $svalb=$sval;
		            	        foreach($svalb as $skey3=>$sval) {
		            	            $skey2.=$skey3;
		            	        }
		            	    }
							$this->LOCAL_LANG[$nkey][$skey2]=$sval;
            	        }
					} else {
						$this->LOCAL_LANG[$nkey][$skey]=$sval;
					}
					//old version by CBY
					/*
					while (is_array($sval)) {  // if fieldname has several '.' in it we regenrate whole name  here...
            	        $svala=$sval;
            	        foreach($svala as $skey2=>$sval) {
            	            $skey.=$skey2;
            	        }
            	    }
            		$this->LOCAL_LANG[$nkey][$skey]=$sval;
					//*/
            	}
            }
        }
        $conf['LLkey']=$this->LLkey;       
        $conf['LOCAL_LANG']['default']=&$this->LOCAL_LANG['default'];
        $conf['LOCAL_LANG'][$conf['LLkey']]=&$this->LOCAL_LANG[$conf['LLkey']];
        unset($conf['_LOCAL_LANG.']);
 		/**** Init language object (used for translation of labels) ****/
		$GLOBALS['TSFE']->initLLvars();
    }

    /**
    * initFieldsCmd
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$cmd: ...
    * @return	[type]		...
    */
    
	function initFieldsCmd(&$conf,$cmd) {
		$table=$conf['table'];
		/**** DO ADDITIONAL REQUIRED STUFF FOR THE FIELDS ****/
		if (!$conf[$cmd.'.']['show_fields']) $conf[$cmd.'.']['show_fields']=$conf['TCAN'][$table]["interface"]["showRecordFieldList"];
		$fieldArray = explode(',',$conf[$cmd.'.']['show_fields']);
	    $farr=array();
	    $fieldstoberemoved=array();
		foreach((array)$fieldArray as $fN) { //runs through the different fields
    		$fN=trim($fN);
    		// make sure --div-- is in allowed fields list
    		$parts = explode(";",$fN);
		    if(!($conf['TCAN'][$table]['columns'][$fN]['config']['type']=='group'  && $conf['TCAN'][$table]['columns'][$fN]['config']['internal_type']=='db' && count(t3lib_div::trimexplode(',',$conf['TCAN'][$table]['columns'][$fN]['config']['allowed']))>1)) {
    			if($parts[0]=='--div--') {
    	  		    if (trim($parts[1])) {
						$conf[$cmd.'.']['fields'] = $conf[$cmd.'.']['fields'] ? $conf[$cmd.'.']['fields'].','.$parts[1] : $parts[1];
						// CBY $farr[]=$parts[1]; fixes bug on tabs not being shown ...
						$farr[]=$parts[0].';'.$parts[1];
					}
    			} else {
							if ($fN) {
    	  	  	$conf[$cmd.'.']['fields'] = $conf[$cmd.'.']['fields'] ? $conf[$cmd.'.']['fields'].','.$fN : $fN;
								$farr[]=$fN;
							}
						}
					}

    		// CBY :: here we handle the new evalValues mecanism if empty we take the default config.eval value
		  	// here whe should merge the eval arrays ....

    		if(!$conf[$cmd.'.']['evalValues.'][$fN] && $conf['TCAN'][$table]['columns'][$fN]['config']['eval']) $conf[$cmd.'.']['evalValues.'][$fN]= $conf['TCAN'][$table]['columns'][$fN]['config']['eval'];

    		// do stuff according to type from TCA
    		switch((string)$conf['TCAN'][$table]['columns'][$fN]['config']['type']) {
    		case 'group':
    		        if($conf['TCAN'][$table]['columns'][$fN]['config']['internal_type']=='file') {
    		                // CBY I removed _file handling here...
    		                //We could add folder specialisation here ...
							// modif by CMD - permet d'eviter les message d'errreur suite à la gestion des champs supplémentaire sql ou php calculé
    		                $conf['TCAN'][$table]['columns'][$fN.'_file'] = $conf['TCAN'][$table]['columns'][$fN]; // the new upload field should have the same upload folder as the original field
    		                $conf['TCAN'][$table]['columns'][$fN.'_file']['imagealiasfield']=$fN;
    		                //$conf['TCAN'][$table]['columns'][$fN.'_file']['config']['uploadfolder'] = $conf['TCAN'][$table]['columns'][$fN]['config']['uploadfolder']; // the new upload field should have the same upload folder as the original field
    		                $conf['parseValues.'][$fN.'_file'] = 'files['.ereg_replace(',',';',$conf['TCAN'][$table]['columns'][$fN]['config']['allowed']).']['.$conf['TCAN'][$table]['columns'][$fN]['config']['max_size'].']'; // adds the parse options for the new field, so it will be parsed as a file.
    		        }
    		}
		}
		$conf[$cmd.'.']['show_fields']=implode(',',array_unique($farr));
		$conf[$cmd.'.']['fields']=implode(',',array_unique(t3lib_div::trimExplode(',',$conf[$cmd.'.']['fields']))); // TODO : Why must we clean up here ?
	}

    /**
    * initCmd
    *
    * @param	[type]		$$conf: ...
    * @return	[type]		...
    */
	 
	function initCmd(&$conf) {
		$cmd=$conf['inputvar.']['cmd'];

		$this->initFieldsCmd($conf,'create');
		$this->initFieldsCmd($conf,'edit');
		$this->initFieldsCmd($conf,'list');
		$this->metafeeditlib->getFieldList($conf);

        /**** CHECK IF LOGIN IS REQUIRED ****/
        //CBY: if($conf['requireLogin'] && !$GLOBALS['TSFE']->loginUser) return $this->metafeeditlib->getLL("login_required_message",$conf);
        /**** FE ADMIN LIB ****/
        $conf["templateContent"]= $this->getDefaultTemplate($conf); // gets the default template

        // generate default template in browser if required to.
        if ($conf['generateTemplate']){
            echo  $conf["templateContent"];
            die();
        }
    
        if ($conf['fetemplate']&& $conf['useTemplate']) {
          	$conf["templateContent"]=$conf['fetemplate'];
        }
        // CBY>
       	$conf["templateContentOptions"]=$conf['templateContent']; // can this be removed ?
     
       	$conf['parentObj']=&$this;
       	$conf["templateContent"]=$this->metafeeditlib->replaceOptions($conf["templateContent"],$conf,$cmd,$this->table,'');
       	return $conf;
	  }
    
    /* mergeExtendingTCAs($ext_keys)
    *
    *  In case you wrote an extension, that extends the table "$table", then
    *  the TCA information for the additional fields will be merged with the "$table" TCA.
    *
    * @param	array		Extension TCA's that should be merged.
    * @return	[type]		...
    */
    
    function mergeExtendingTCAs($ext_keys){
        global $_EXTKEY, $TCA;
        //Merge all ext_keys
        if (is_array($ext_keys)) {
            for($i = 0; $i < sizeof($ext_keys); $i++){
                //Include the ext_table
                $_EXTKEY = $ext_keys[$i]; // added by F.Rakow
                if ($_EXTKEY) include(t3lib_extMgm::extPath($ext_keys[$i]).'ext_tables.php');
            }
        }
    }


    /**********************************************************************************************
    * TEMPLATE FUNCTIONS
    **********************************************************************************************/
    /**
    * Gets a default template made from the TCA.
    * The template there is returned depends on what $this->cmd is.
    *
    * @param	[type]		$$conf: ...
    * @return	[type]		...
    */
    
    function getDefaultTemplate(&$conf)	{
        $callerMethods = get_class_methods(get_class($this->caller));
        $template = array_search('getrequiredtemplate',$callerMethods) || array_search('getRequiredTemplate',$callerMethods)?
          $this->caller->getRequiredTemplate($conf) : $this->getRequiredTemplate($conf);
        $template .= array_search('getemailtemplate',$callerMethods) || array_search('getEmailTemplate',$callerMethods)?$this->caller->getEmailTemplate($conf) : $this->getEmailTemplate($conf);
        $nbCols = $this->piVars['nbCols'];
        
        if ($conf['generateTemplate']){
          $template .= array_search('getedittemplate',$callerMethods) || array_search('getEditTemplate',$callerMethods)?  $this->caller->getEditTemplate($conf) : $this->getEditTemplate($conf);
          $template .= array_search('getListTemplate',$callerMethods) || array_search('getListTemplate',$callerMethods)?  $this->caller->getListTemplate($conf) : $this->getListTemplate($conf);
          if ($conf['list.']['csv']) $template .= array_search('getCSVTemplate',$callerMethods) || array_search('getCSVTemplate',$callerMethods)?  $this->caller->getCSVTemplate($conf) : $this->getCSVTemplate($conf);
          if ($conf['list.']['xls']) $template .= array_search('getExcelTemplate',$callerMethods) || array_search('getExcelTemplate',$callerMethods)?  $this->caller->getExcelTemplate($conf) : $this->getExcelTemplate($conf);
          if ($conf['list.']['pdf']) $template .= array_search('getPDFTemplate',$callerMethods) || array_search('getPDFTemplate',$callerMethods)?  $this->caller->getPDFTemplate($conf) : $this->getPDFTemplate($conf);
          if ($conf['list.']['pdf'] || $conf['edit.']['pdf']) $template .= array_search('getPDFDETTemplate',$callerMethods) || array_search('getPDFDETTemplate',$callerMethods)?  $this->caller->getPDFDETTemplate($conf) : $this->getPDFDETTemplate($conf);
          if ($conf['list.']['pdf']) $template .= array_search('getPDFTABTemplate',$callerMethods) || array_search('getPDFTABTemplate',$callerMethods)?  $this->caller->getPDFTABTemplate($conf) : $this->getPDFTABTemplate($conf);
          if ($conf['grid.']['pdf']) $template .= array_search('getGridPDFTemplate',$callerMethods) || array_search('getGridPDFTemplate',$callerMethods)?  $this->caller->getGridPDFTemplate($conf) : $this->getGridPDFTemplate($conf);
          if ($conf['grid.']['csv']) $template .= array_search('getGridCSVTemplate',$callerMethods) || array_search('getGridCSVTemplate',$callerMethods)?  $this->caller->getGridCSVTemplate($conf) : $this->getGridCSVTemplate($conf);
          if ($conf['grid.']['xls']) $template .= array_search('getGridExcelTemplate',$callerMethods) || array_search('getGridExcelTemplate',$callerMethods)?  $this->caller->getGridExcelTemplate($conf) : $this->getGridExcelTemplate($conf);
          
          $template .= array_search('getcreatetemplate',$callerMethods) || array_search('getCreateTemplate',$callerMethods)?  $this->caller->getCreateTemplate($conf) : $this->getCreateTemplate($conf);
          $template .= array_search('getdeletetemplate',$callerMethods) || array_search('getDeleteTemplate',$callerMethods)?
        $this->caller->getDeleteTemplate($conf) : $this->getDeleteTemplate($conf);
          $template .= array_search('getsetfixedtemplate',$callerMethods) || array_search('getSetfixedTemplate',$callerMethods)?  $this->caller->getSetfixedTemplate($conf) : $this->getSetfixedTemplate($conf);
        } else {
        switch((string) $conf['inputvar.']['cmd']) {
        case 'edit':
          $template .= array_search('getedittemplate',$callerMethods) || array_search('getEditTemplate',$callerMethods)?  $this->caller->getEditTemplate($conf) : $this->getEditTemplate($conf);
          $template .= array_search('getListTemplate',$callerMethods) || array_search('getListTemplate',$callerMethods)?  $this->caller->getListTemplate($conf) : $this->getListTemplate($conf);
	        //Ajout Charlotte 30/04 
	        
	        if ($conf['piVars']['exporttype']) {
	          if ($conf['list.']['csv']) $template .= array_search('getCSVTemplate',$callerMethods) || array_search('getCSVTemplate',$callerMethods)?  $this->caller->getCSVTemplate($conf) : $this->getCSVTemplate($conf);
	          if ($conf['list.']['excel']) $template .= array_search('getExcelTemplate',$callerMethods) || array_search('getExcelTemplate',$callerMethods)?  $this->caller->getExcelTemplate($conf) : $this->getExcelTemplate($conf);
	          if ($conf['list.']['pdf']) $template .= array_search('getPDFTemplate',$callerMethods) || array_search('getPDFTemplate',$callerMethods)?  $this->caller->getPDFTemplate($conf) : $this->getPDFTemplate($conf);
	          if ($conf['list.']['pdf'] || $conf['edit.']['pdf']) $template .= array_search('getPDFDETTemplate',$callerMethods) || array_search('getPDFDETTemplate',$callerMethods)?  $this->caller->getPDFDETTemplate($conf) : $this->getPDFDETTemplate($conf);
	          if ($conf['list.']['pdf']) $template .= array_search('getPDFTABTemplate',$callerMethods) || array_search('getPDFTABTemplate',$callerMethods)?  $this->caller->getPDFTABTemplate($conf) : $this->getPDFTABTemplate($conf);

	          if ($conf['grid.']['pdf']) $template .= array_search('getGridPDFTemplate',$callerMethods) || array_search('getGridPDFTemplate',$callerMethods)?  $this->caller->getGridPDFTemplate($conf) : $this->getGridPDFTemplate($conf);
	          if ($conf['grid.']['csv']) $template .= array_search('getGridCSVTemplate',$callerMethods) || array_search('getGridCSVTemplate',$callerMethods)?  $this->caller->getGridCSVTemplate($conf) : $this->getGridCSVTemplate($conf);
	          if ($conf['grid.']['xls']) $template .= array_search('getGridExcelTemplate',$callerMethods) || array_search('getGridExcelTemplate',$callerMethods)?  $this->caller->getGridExcelTemplate($conf) : $this->getGridExcelTemplate($conf);
	        }  
        		$template .= array_search('getmediaplayertemplate',$callerMethods) || array_search('getMediaPlayerTemplate',$callerMethods)?  $this->caller->getMediaPlayerTemplate($conf): $this->getMediaPlayerTemplate($conf);
          break;
        case 'create':
          $template .= array_search('getcreatetemplate',$callerMethods) || array_search('getCreateTemplate',$callerMethods)?
        $this->caller->getCreateTemplate($conf) : $this->getCreateTemplate($conf);
          $template .= array_search('getedittemplate',$callerMethods) || array_search('getEditTemplate',$callerMethods)?  $this->caller->getEditTemplate($conf) : $this->getEditTemplate($conf);
          break;
        case 'delete':
          $template .= array_search('getdeletetemplate',$callerMethods) || array_search('getDeleteTemplate',$callerMethods)?
        $this->caller->getDeleteTemplate($conf) : $this->getDeleteTemplate($conf);
          break;
        case 'setfixed':
          $template .= array_search('getsetfixedtemplate',$callerMethods) || array_search('getSetfixedTemplate',$callerMethods)?
        $this->caller->getSetfixedTemplate($conf) : $this->getSetfixedTemplate($conf);
          break;
        default:
          debug('meta_feedit->getDefaultTemplate():: No template found for cmd='.$conf['inputvar.']['cmd'],'No Template');
          $template = '';
        }
        }
        return $template;
    }
    
    /**
    * Makes the form content from the TCA according to the configuration for the $cmd
    *
    * @param	string		The cmd. Should be 'edit' or 'create'.
    * @param	[type]		$conf: ...
    * @return	[type]		...
    */
    
    function makeHTMLForm($cmd,&$conf)	{
        //$fields = array_intersect( array_unique(t3lib_div::trimExplode(",",$conf[$cmd.'.']['show_fields'],1)) , array_unique(t3lib_div::trimExplode(",",$conf[$cmd.'.']['fields'],1)));
        //$reqFields = array_intersect( array_unique(t3lib_div::trimExplode(",",$conf[$cmd.'.']["required"],1)) , array_unique(t3lib_div::trimExplode(",",$conf[$cmd.'.']['show_fields'],1)));
        $fields = t3lib_div::trimExplode(",",$conf[$cmd.'.']['show_fields'],1);
        $reqFields = t3lib_div::trimExplode(",",$conf[$cmd.'.']["required"],1);
        
        $out_array = array();
        $out_sheet = 0;
        $fsc=0;
        $fsi=0;
        $tabLabels=explode(chr(10),$conf["tabLabels"]);
        
        // avoid JS conflict errors !!!
        $this->RTEcounter=0;
        while(list(,$fN)=each($fields))	{
            $parts = explode(';',$fN);
            $fN = $parts[0];     
            if($fN=='--div--') {
            	if($conf["divide2tabs"]) {
				    $out_sheet++;
				    $out_array[$out_sheet] = array();
				    $out_array[$out_sheet]['title'] = $this->metafeeditlib->getLL($parts[1]?$parts[1]:'Tab '.$out_sheet,$conf);
					//$out_array[$out_sheet]['title'] = $tabLabels[$out_sheet]?$tabLabels[$out_sheet]:'Tab '.$out_sheet;
				}
             } else {
                if($fN=='--fse--' && $fsc) {
                	$out_array[$out_sheet][]='</fieldset>';
                	$fsc--;
	             }
                if($fN=='--fsb--') {
                    if ($fsc) {
                    	$out_array[$out_sheet][]='</fieldset>';
                    	$fsc--;
                    }
    	      		$fsc++;        			
    	      		$fsclib='';
    	      		if ($conf['list.']['fieldSetNames.'][$fsi]) $fsclib='<legend>'.$conf['list.']['fieldSetNames.'][$fsi].'</legend>';
					$out_array[$out_sheet][]='<fieldset>'.$fsclib;
					$fsi++;
				}
				
				if ($fN=='--fse--' || $fN=='--fsb--') {
    				continue;
			    }
			    
				$fieldCode = $this->getFormFieldCode($cmd,$conf,$fN,0,$cmd);
				if ($fieldCode)	{
			
				  // NOTE: There are two ways to make a field required. The new way is to include 'required' in evalValues for a field. The old one is to have the the field in the required list.
				  //       The new way take precedence over the old way. So if the new field has some evalValues, it makes no different if the field is in the required list or not.
				  $feData=$conf['inputvar.']['fedata'];
				  $msg = '';
				  $reqMarker = '';
				  
				  
				  
				  if($conf[$cmd.'.']['evalValues.'][$fN]) {        // evalValues defined
				  	$reqMarker = in_array('required',t3lib_div::trimExplode(',',$conf[$cmd.'.']['evalValues.'][$fN])) ? $conf['required_marker'] : '';
				  }
			
				  if (in_array($fN,$reqFields)) {                                  // No evalValues, but field listed in required list.
				    	$msg .= '<!--###SUB_REQUIRED_FIELD_'.$fN.'###--><div'.$this->caller->pi_classParam('form-required-message').'>'.($conf['evalErrors.'][$fN.'.']['required']?$conf['evalErrors.'][$fN.'.']['required']:$this->metafeeditlib->getLL("required_message",$conf)).'</div><!--###SUB_REQUIRED_FIELD_'.$fN.'###-->';
				    	$reqMarker = $conf['required_marker'];
				  }
			
				  $helpIcon = ($conf['show_help_icons'] ? '<div'.$this->caller->pi_classParam('form-help-icon').'>'.$this->helpIcon($fN).'</div>' : '');
			 	  $res=$this->metafeeditlib->getForeignTableFromField($fN,$conf,'',array());
			 	  $table=$res['relTable'];
				  $fNiD=$res['fNiD'];
			      $label = $this->metafeeditlib->getLLFromLabel($conf['TCAN'][$table]['columns'][$fNiD]['label'],$conf);
				  $out_array[$out_sheet][]='<div  class="'.$this->caller->pi_getClassName($fsc?'fsc':'form-row').' '.$this->caller->pi_getClassName(($fsc?'fsc-':'form-row-').$fN).'">
                    <div class="'.$this->caller->pi_getClassName($fsc?'fsl':'form-label').' '.$this->caller->pi_getClassName(($fsc?'fsl-':'form-label-').$fN).'">
                    <div'.$this->caller->pi_classParam($fsc?'fsrm':'form-required-marker').'>'.$reqMarker.'</div>
                    '.$label. '
                    '.$helpIcon.'
                    </div>
                    <div'.$this->caller->pi_classParam($fsc?'fsf':'form-field').'>'.$fieldCode.'</div>
                    '.$msg.'</div>';
				}
            }
        }
        
        if ($out_sheet>0) {	 // There were --div-- dividers around. Create parts array for the tab menu:
            $parts = array();
            foreach($out_array as $idx => $sheetContent)	{
                unset($sheetContent['title']);
                    $parts[] = array(
            		 'label' => $out_array[$idx]['title'],
            		 'content' => '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td>'.
            		 implode(chr(10),$sheetContent).
            		 '</td></tr></table>'
        		 );
            }   
            $content = $this->addCallersPiVars($this->templateObj->getDynTabMenu($parts, 'TCEforms:'.$table.':'.$row[$conf['uidField']]),$conf);
        } else {	        // Only one, so just implode:
            $content = is_array($out_array[$out_sheet]) ? $this->addCallersPiVars(implode(chr(10),$out_array[$out_sheet]),$conf) : 'makeHTMLForm() :: No form generated! (Probably no fields defined in typoscript option show_fields)';
        }
        
        return $content;
    }

    /**
    * Returns a help icon for the field
    *
    * @param	string		The field to get the help icon for
    * @param	boolean		The help icon with link to javascript popup, with help in.
    * @return	[type]		...
    */
    
    function helpIcon($field) {
        if(!is_array($GLOBALS['TCA_DESCR'][$this->table]['refs'])) return '';
        foreach($GLOBALS['TCA_DESCR'][$this->table]['refs'] as $ref) {
          $fieldDescription = $GLOBALS['TSFE']->sL('LLL:'.$ref.':'.$field.'.description') .' ';
        }
        if(empty($fieldDescription)) return '';
        else {
          //      $aOnClick = 'confirm(\''.$fieldDescription.'\');return false;';
          $fieldDescription = '<html><head><title>'.$field.'</title><style type="text/css">'.preg_replace("(\r)"," ",preg_replace("(\n)"," ",$conf['help_window_style'])).'</style></head><body'.$this->caller->pi_classParam('help-body').'><div'.$this->caller->pi_classParam('help-text').'>'.$fieldDescription.'</div></body></html>';
          $aOnClick = 'top.vHWin=window.open(\'\',\'viewFieldHelpFE\',\'height=20,width=300,status=0,menubar=0,scrollbars=1\');top.vHWin.document.writeln(\''.$fieldDescription.'\');top.vHWin.document.close();top.vHWin.focus();return false;';
        
          $script =
            '<script type="text/javascript">' .
            '' .
            '</script>';
        
          require_once(PATH_t3lib . 'class.t3lib_iconworks.php');
          return
            '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
            '<img'.t3lib_iconWorks::skinImg('typo3/','gfx/helpbubble.gif','width="14" height="14"').' hspace="2" border="0" class="absmiddle"'.($GLOBALS['CLIENT']['FORMSTYLE']?' style="cursor:help;"':'').' alt="" />'.
            '</a>';
        }
    }
  
    /**
    * Makes a preview of the form content according to the configuration for the $cmd
    *
    * @param	string		The cmd. Should be 'edit' or 'create' or 'all'.
    * @param	boolean		Should the output be wrapped in html or not.
    * @param	[type]		$withHTML: ...
    * @return	[type]		...
    */
    
    function makeHTMLPreview($cmd,&$conf, $withHTML = true) {
        $fields = (string)$cmd=='all' ? array_unique(t3lib_div::trimExplode(",",($conf['create.']['show_fields'] ? $conf['create.']['show_fields'].($conf['edit.']['show_fields']?','.$conf['edit.']['show_fields']:'') : $conf['edit.']['show_fields']))) : array_unique(t3lib_div::trimExplode(",",$conf[$cmd.'.']['show_fields']));
        $result = array();
        $out_array = array();
        $out_sheet = 0;
        
        $hiddenFields = array();
        foreach((array)$fields as $fN) {
            $parts = explode(';',$fN);
            $fN = $parts[0];
			if ($fN=='--fse--' || $fN=='--fsb--') {
    				continue;
			}
            if($fN=='--div--') {
                if($conf["divide2tabs"]&&$cmd!='all') {
                $out_sheet++;
                $out_array[$out_sheet] = array();
                //DOESN'T WORK !!
            //	    $out_array[$out_sheet]['title'] = $this->metafeeditlib->getLL($parts[1],$parts[1]?$parts[1]:'Tab '.$out_sheet,$conf); //OUCH
            $out_array[$out_sheet]['title'] = $parts[1]?$parts[1]:'Tab '.$out_sheet; //OUCH
            }
              } else {
        
        	$res=$this->metafeeditlib->getForeignTableFromField($fN,$conf,'',array());
        	$table=$res['relTable'];
        	$fNiD=$res['fNiD'];
          $label = $this->metafeeditlib->getLLFromLabel($conf['TCAN'][$table]['columns'][$fNiD]['label'],$conf);
          $fieldCode = $this->getPreviewFieldCode($cmd,$conf, $fN, $withHTML);
        	$reptagbegin='<!-- ###editITEM-'.$fN.'### start -->';
        	$reptagend='<!-- ###editITEM-'.$fN.'### end -->';
            if(!$withHTML) {
                $result[] = $label.chr(10).$fieldCode.chr(10);
            } else {
                $result[] = $reptagbegin.'<div class="'.$this->caller->pi_getClassName('preview-row').' '.$this->caller->pi_getClassName('preview-row-'.$fN).'">
                     <div class="'.$this->caller->pi_getClassName('preview-label').' '.$this->caller->pi_getClassName('preview-label-'.$fN).'">
                       '.$label.'
                     </div>
                     <div class="'.$this->caller->pi_getClassName('preview-value').' '.$this->caller->pi_getClassName('preview-value-'.$fN).'">
                     '.$fieldCode.'
                     </div>
                     </div>'.$reptagend;
#       $hiddenFields[] = '<input type="hidden" name="FE['.$this->table.']['.$fN.']" />';
	}
	$out_array[$out_sheet][]=$reptagbegin.'<div'.$this->caller->pi_classParam('form-row').$this->caller->pi_classParam('form-row-'.$fN).'>
	             <div class="'.$this->caller->pi_getClassName('form-label').' '.$this->caller->pi_getClassName('form-label-'.$fN).'">
                       <div'.$this->caller->pi_classParam('form-required-marker').'>'.$reqMarker.'</div>
                       '.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$table]['columns'][$fNiD]['label'],$conf). '
                       '.$helpIcon.'
                     </div>
		     <div'.$this->caller->pi_classParam('form-field').'>'.$fieldCode.'</div>
                     '.$msg.'
		   </div>'.$reptagend;
      }
    }

#    $result[] = ''.implode(chr(10),$hiddenFields);
    if ($out_sheet>0) {	 // There were --div-- dividers around. Create parts array for the tab menu:
      	$parts = array();
      foreach($out_array as $idx => $sheetContent)	{

	unset($sheetContent['title']);
	$parts[] = array(
			 'label' => $out_array[$idx]['title'],
			 'content' => '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.
			 implode(chr(10),$sheetContent).
			 '</table>'
			 );
      }
     }


    // Keep callers piVars
    if($withHTML)
      $content= $this->addCallersPiVars(implode(chr(10),$result),$conf);
    else
      $content= implode(chr(10),$result);
	if ($out_sheet>0) {
    		$content = $this->addCallersPiVars($this->templateObj->getDynTabMenu($parts, 'TCEforms:'.$table.':'.$row[$conf['uidField']]),$conf);
	}
    return $content;
  }

  /**
 * A dummy method for making a NON HTML preview of the form content according to the configurations for the $cmd
 *
 * @param	string		The cmd. Should be 'edit' or 'create'.
 * @param	[type]		$conf: ...
 * @return	[type]		...
 */
  function makeTEXTPreview($cmd,&$conf) {
    return $this->makeHTMLPreview($cmd,$conf,false);
  }

  /**
 * Add callers piVars as hidden input fields to the result array
 *
 * @param	string		The string to add piVars as input fields to
 * @param	[type]		$conf: ...
 * @return	[type]		...
 * @result	string		The result string with added piVars input fields
 */
  function addCallersPiVars($result,&$conf) {
    $keep_piVars = t3lib_div::trimExplode(',',$conf['keep_piVars']);
    foreach($keep_piVars as $piVar) {
      if($piVar &&  is_array($this->piVars[$piVar])) {
	foreach($this->piVars[$piVar] as $key=>$val) {
	$result.='<input type="hidden" name="'.$this->prefixId.'['.$piVar.']['.$key.']" value="'.$val.'" />';
	}
      }
      if (!empty($piVar)) {
	$result.= '<input type="hidden" name="'.$this->caller->prefixId.'['.$piVar.']" value="'.$this->caller->piVars[$piVar].'" />';
	}
    }
    return $result.$res;
  }



  /**
 * Gets the PREVIEW fieldcode for field ($fN) of the form. This depends on the fields type.
 *
 * @param	string		The field to get the fieldcode for.
 * @param	boolean		Should the output be with html (input fields) or not.
 * @param	string		$FN: fieldName
 * @param	boolean		$withHTML: wether to use html dispay or plain text
 * @param	string		$Lib: By Ref languqge label
 */
  function getPreviewFieldCode($cmd,&$conf,$fN,$withHTML,&$Lib='') {
    $fN=trim($fN);
    $masterTable=$cmd=='blog'?'tx_metafeedit_comments':$conf['table'];
    $res=$this->metafeeditlib->getForeignTableFromField($fN,$conf,'',array());
    $Lib=$this->metafeeditlib->getLLFromLabel($res['fieldLabel'],$conf);
    //$fN=str_replace('.','_',$fN);
    $fN=str_replace('.','_',$res['fieldAlias']); // is the str_replace necessary ?
    $table=$res['relTable'];
    $fNiD=$res['fNiD'];
    $EVAL_ERROR_FIELD= $withHTML?'<div '.$this->caller->pi_classParam('form-error-field').'>###EVAL_ERROR_FIELD_'.$fN.'###</div>':'';
    $fieldName = 'FE['.$masterTable.']['.$fN.']';
    $type = $conf['TCAN'][$table]["columns"][$fNiD]['config']["type"];
    $feData = $conf['inputvar.']['fedata'];
    $std=$conf[$conf['cmdmode'].'.']['stdWrap.']?$conf[$conf['cmdmode'].'.']['stdWrap.']:$conf['stdWrap.'];
    switch((string)$type) {
    case "input":
      $evalValuesArr = t3lib_div::trimExplode(',',$conf[$cmd.'.']['evalValues.'][$fN]);
      $displayTwice = false;
      $isPassword = false;
      $isMD5 = false;
      foreach((array)$evalValuesArr as $eval) {
				switch((string)$eval) {
					case 'twice':
	  				$displayTwice = true;
	  				break;
					case 'password':
	  				$isPassword = true;
	  				break;
					case 'md5':
	  				$isMD5 = true;
	  			break;
				}
      }
      $values = '###FIELD_'.$fN.'###';
      //if ($std[$fN.'.']) {
      if	($std[$fNiD.'.'] || $std[$table.'.'][$fNiD.'.'] || $std[$fN.'.'] || $std[$table.'.'][$fN.'.']) {
      	$values = '###FIELD_EVAL_'.$fN.'###';
      }
      // special cases requiring presentation transformation
      if($conf['TCAN'][$table]['columns'][$fNiD]['config']["eval"]=='date'||$conf['TCAN'][$table]['columns'][$fNiD]['config']["eval"]=='datetime') $values = '###FIELD_EVAL_'.$fN.'###';
      if (in_array('wwwURL',t3lib_div::trimexplode(',',$conf[$conf['inputvar.']['cmd']."."]['evalValues.'][$fN])))  $values = '###FIELD_EVAL_'.$fN.'###';
      if (in_array('email',t3lib_div::trimexplode(',',$conf[$conf['inputvar.']['cmd']."."]['evalValues.'][$fN])))  $values = '###FIELD_EVAL_'.$fN.'###';


      $feData = t3lib_div::_POST("FE");

      // Format the values.                TODO: This only shows the date on a nice format if it is send to the page, not if it is from an overrideValue.
      if($isPassword) $values = '********';
      else if($conf['TCAN'][$table]['columns'][$fNiD]['config']["eval"]=='date' && !empty($feData[$masterTable][$fN])) {
				$values = strftime(($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%e-%Y' :'%e-%m-%Y'),$feData[$masterTable][$fN]);
      } else if($conf['TCAN'][$table]['columns'][$fNiD]['config']["eval"]=='datetime' && !empty($feData[$masterTable][$fN])) {
				$values = strftime(($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%H:%M %m-%e-%Y' :'%H:%M %e-%m-%Y'),$feData[$masterTable][$fN]);
      }

      if($displayTwice) {
				$fieldName_again = 'FE['.$masterTable.']['.$fN.'_again]';
				return $withHTML?'<input type="hidden" name="'.$fieldName.'" /><input type="hidden" name="'.$fieldName_again.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
      } else {
				return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
      }
      break;
    case 'radio':
      $values = '###FIELD_EVAL_'.$fN.'###';
      return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
      break;
    case 'check':
      $values = '###FIELD_EVAL_'.$fN.'###';
      return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;;
      break;
    case 'group':
      if($conf['TCAN'][$table]['columns'][$fNiD]['config']["internal_type"]=='file') {
      	return $withHTML?'<input type="hidden" name="'.$fieldName.'" /><input type="hidden" name="FE['.$masterTable.']['.$fN.'_file]" value="###FIELD_'.$fN.'_file###"/>###FIELD_EVAL_'.$fN.'###'.$EVAL_ERROR_FIELD:'###FIELD_'.$fN.'###'.$EVAL_ERROR_FIELD;
      } else {  // we assume internal_type = db
      	$values = '###FIELD_EVAL_'.$fN.'###';
        if($conf['TCAN'][$table]['columns'][$fNiD]['config']["allowed"]) {  // reference to elements from another table
					$FTA=t3lib_div::trimexplode(',',$conf['TCAN'][$table]['columns'][$fNiD]['config']["allowed"]);
					if (!count($FTA)) die("Bad configuration of field $fN of table ".$masterTable);
					if (count($FTA)>1) die("We don't handle multi table relations yet on field $fN of table  ".$masterTable);
					$ForeignTable=$FTA[0];
					$MMTable=$conf['TCAN'][$table]['columns'][$fNiD]['config']['MM'];
					$Prepend=$conf['TCAN'][$table]['columns'][$fNiD]['config']['prepend_tname'];
					$feData = $conf['inputvar.']['fedata'];
					$uid = $feData[$masterTable][$conf['uidField']] ? $feData[$masterTable][$conf['uidField']] : $conf['inputvar.']['rU'];
					// MM is set we
					if ($MMTable && !$Prepend) {
						if($feData[$masterTable][$fN]) {
							//$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$MMTable,'uid_local=\''.$uid.'\'','sorting');
			          			//if(mysql_num_rows($MMres)!=$feData[$masterTable][$fN]) die("Wrong number of selections reached");
				  			$uids = t3lib_div::trimExplode(',',$feData[$masterTable][$fN]);
				  			$orClause = '';
				  			foreach($uids as $uid) $orClause .= $orClause ? 'OR '.$conf['uidField'].' LIKE \''.$uid.'\'' : $conf['uidField'].' = \''.$uid.'\'';
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$ForeignTable,$orClause);
							$label= $conf['label.'][$ForeignTable]?$conf['label.'][$ForeignTable]:$GLOBALS["TCA"][$ForeignTable]['ctrl']['label'];
			          			if($GLOBALS['TYPO3_DB']->sql_error()) debug($GLOBALS['TYPO3_DB']->sql_error(),'sql error');
			          			$values = '';
			          			while($resRow = mysql_fetch_assoc($res)) {
			            				$values .= $values ? ', ' . $resRow[$label] : $resRow[$label];
			          			}
			 				mysql_free_result($res);
			        		}                                           // clean from DB
					}
					// NoMM
				}
        return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
      }
      break;
    case 'select':
      $values = '###FIELD_EVAL_'.$fN.'###';
      $FT=$conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table'];
      if($FT) {  // reference to elements from another table
        $label = $conf['label.'][$FT]?$conf['label.'][$FT]:$GLOBALS['TCA'][$FT]['ctrl']['label'];
	$feData = $conf['inputvar.']['fedata'];
	if($feData[$masterTable][$fN]) {
	  $uids = t3lib_div::trimExplode(',',$feData[$masterTable][$fN]);
	  $orClause = '';
	  foreach($uids as $uid) $orClause .= $orClause ? 'OR '.$conf['uidField'].' LIKE \''.$uid.'\'' : $conf['uidField'].' = \''.$uid.'\'';
	  $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$conf['TCAN'][$table]['columns'][$fNiD]['config']["foreign_table"],$orClause);
	  if($GLOBALS['TYPO3_DB']->sql_error()) debug($GLOBALS['TYPO3_DB']->sql_error(),'sql error');
	  $values = '';
	  //MMMMMMMMMM
	  while($resRow = mysql_fetch_assoc($res)) {
	    $values .= $values ? ', ' . $resRow[$label] : $resRow[$label];
	  }
	   mysql_free_result($res);
	}
      } elseif($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"]) {                // fixed items
	$feData = $conf['inputvar.']['fedata'];
	if($feData[$masterTable][$fN]) {
	  $vals = t3lib_div::trimExplode(',',$feData[$masterTable][$fN]);
	  $values = '';
	  foreach($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"] as $item) {
	    if(!empty($item)) {
	      list($label,$val) = $item;
	      if(in_array($val,$vals)) {
		$values .= $values ? ', ' . $label : $label;
	      }
	    }
	  }
	}
      }
      return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
      break;
    case "text":
        $values = '###FIELD_'.$fN.'###';
        $feData = $conf['inputvar.']['fedata'];
        if($feData[$masterTable]['_TRANSFORM_'.$fN]) { // if rte output, we need to process it instead of parsing it through htmlspecialchar as the other values gets.
            $dataArr = $feData[$masterTable];
            $dataArr = $this->metafeeditlib->rteProcessDataArr($dataArr, $masterTable, $fN, 'db',$conf);
            $dataArr = $this->metafeeditlib->rteProcessDataArr($dataArr, $masterTable, $fN, 'rte',$conf);
            $values = $dataArr[$fN];
        }
        return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
        break;
    default:
    	$values = '###FIELD_'.$fN.'###';
      if	($std[$fNiD.'.'] || $std[$table.'.'][$fNiD.'.'] || $std[$fN.'.'] || $std[$table.'.'][$fN.'.']) {
      	$values = '###FIELD_EVAL_'.$fN.'###';
      }
      return $withHTML?'<input type="hidden" name="'.$fieldName.'" />'.$values.$EVAL_ERROR_FIELD:$values.$EVAL_ERROR_FIELD;
      break;
    }
  }

    
    /**
    * Gets the fieldcode for field ($fN) of the form. This depends on the fields type.
    *
    * @param	string		The cmd. Should be 'edit' or 'create'.
    * @param	string		The conf array.
    * @param	string		The field to get the fieldcode for.
    * @param	string		boolean, true if we are in grid mode.
    * @param	int		number of open fieldsets ...
    * @param	string		cmd mode ...
    * @return	[type]		...
    */
    
    function getFormFieldCode($cmd,&$conf,$fN,$bgrid,$cmdmode) {
        // CBY: We handle here Read Only Fields !!!
        $readOnlyArr = t3lib_div::trimExplode(',',$conf[$cmd.'.']['readonlyFields']);
        if (($cmd=="edit" || $cmd=='create') && in_array($fN, $readOnlyArr)) {
        	return $this->getPreviewFieldCode($cmd,$conf,$fN,1);
        }
        

        // blog hack !! to be changed
        $masterTable=$cmd=='blog'?'tx_metafeedit_comments':$conf['table'];
				//echo $cmd;
				//if ($cmd=='blog') {print_r($conf[$cmd.'.']); die(uuu);}


        $gridMark=$bgrid?'###GRIDCELL###':'';
        $gridMarkAlt=$bgrid?'###GRIDCELLALT###':'';
        $fieldName = 'FE['.$masterTable.']'.$gridMark.'['.$fN.']';
        $idFieldName = 'FE.'.$masterTable.'.'.$gridMark.'.'.$fN.'.';
        $class='class="'.$this->caller->pi_getClassName('form-data').' '.$this->caller->pi_getClassName('form-data-'.$fN).'" ';
        $defaultParams = ' name="'.$fieldName.'"'.$class;
        //$EVAL_ERROR_FIELD= ($cmd=='edit')?'<div '.$this->caller->pi_classParam('form-error-field').'>###EVAL_ERROR_FIELD_'.$fN.'###</div>':'';
        $EVAL_ERROR_FIELD=$bgrid?'':'<div '.$this->caller->pi_classParam('form-error-field').'>###EVAL_ERROR_FIELD_'.$fN.'###</div>';
        $onchange = 'onchange="feedit_'.$masterTable.'_formGet('."'".$fieldName."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox."','".$checkboxVal."','".$checkbox_off."');".'"';
        $defaultParams_feVal = ' name="'.$fieldName.'_feVal" '.$onchange.$class;
        $type = $conf['TCAN'][$masterTable]["columns"][$fN]['config']["type"];
        switch((string)$type) {
            case "input":
								$onchange = 'onblur="feedit_'.$masterTable.'_formGet('."'".$fieldName."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox."','".$checkboxVal."','".$checkbox_off."');".'"'.' onchange="feedit_'.$masterTable.'_formGet('."'".$fieldName."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox."','".$checkboxVal."','".$checkbox_off."');".'"';
                $evalValuesArr = t3lib_div::trimExplode(',',$conf[$cmd.'.']['evalValues.'][$fN]);
                $displayTwice = false;
                $isPassword = false;
                $isMD5 = false;
                foreach((array)$evalValuesArr as $eval) {
                	switch((string)$eval) {
                		case 'twice':
                		$displayTwice = true;
                		break;
                		case 'password':
                		$isPassword = true;
                		break;
                		case 'md5':
                		$isMD5 = true;
                		break;
                	}
                }  
                $type = 'text';
                if($isPassword) $type = 'password';
                if($displayTwice) {
                	$fieldName_again = 'FE['.$masterTable.']'.$gridMark.'['.$fN.'_again]';
                	$onchange_again = 'onchange="feedit_'.$masterTable.'_formGet('."'".$fieldName_again."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox."','".$checkboxVal."','".$checkbox_off."');".'"';   
                	$conf['additionalJS_end']['feedit_'.$fN.'_set_data'] = 'feedit_'.$masterTable.'_formSet('."'".$fieldName."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox. "','".$checkboxVal."','".$checkbox_off."')".';';
                	$conf['additionalJS_end']['feedit_'.$fN.'_again_set_data'] = 'feedit_'.$masterTable.'_formSet('."'".$fieldName_again."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']['eval']."','".$is_in."','".$checkbox. "','".$checkboxVal."','".$checkbox_off."')".';';
                	return '<input alt="'.$gridMarkAlt.'" title="'.$gridMarkAlt.'" type="'.$type.'" name="'.$fieldName.'_feVal" '.$class.($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']?' size="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['size'].'"':'').' maxlength="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['max'].'" '.$onchange.' />
                		<input type="hidden" name="'.$fieldName.'" /><br/>Confirmation<br/><input type="'.$type.'" name="'.$fieldName_again.'_feVal" '.$class.($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']?' size="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['size'].'"':'').' maxlength="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['max'].'" '.$onchange_again.' />
                		<input type="hidden" name="'.$fieldName_again.'" />'.$EVAL_ERROR_FIELD;
                } else {
                	$conf['additionalJS_end']['feedit_'.$fN.'_set_data'] = 'feedit_'.$masterTable.'_formSet('."'".$fieldName."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox. "','".$checkboxVal."','".$checkbox_off."')".';';
                    return
                		'<input alt="'.$gridMarkAlt.'" title="'.$gridMarkAlt.'" type="'.$type.'" name="'.$fieldName.'_feVal" id="'.$fieldName.'_feVal" '.$class.($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']?' size="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['size'].'"':'').' maxlength="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['max'].'" '.$onchange.' />' .
                		'<input type="hidden" name="'.$fieldName.'" />'.($bgrid?'':'<div '.$this->caller->pi_classParam('form-button-date').' >') .
                    // inserts button for rlmp_dateselectlib
                    (t3lib_extmgm::isLoaded('rlmp_dateselectlib') && !empty($conf['TCAN'][$masterTable]['columns'][$fN]['config']['eval']) ?
                    (is_int(array_search('date',t3lib_div::trimExplode(',',$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"])))
                    ?
                    tx_rlmpdateselectlib::getInputButton($fieldName.'_feVal',array('calConf.'=>array('inputFieldDateTimeFormat'=>($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%e-%Y' :'%e-%m-%Y'))))
                    :
                    (is_int(array_search('datetime',t3lib_div::trimExplode(',',$conf['TCAN'][$masterTable]['columns'][$fN]['config']['eval'])))
                    ?
                    tx_rlmpdateselectlib::getInputButton($fieldName.'_feVal',array('calConf.'=>array('inputFieldDateTimeFormat'=> ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%H:%M %m-%e-%Y' :'%H:%M %e-%m-%Y'))))
                    : ''))
                    : '')."</div>".$EVAL_ERROR_FIELD;
                }
                break;
            case "text":
                // Get the specialConf for the field. Placed in type array.
                $specialConf = $this->metafeeditlib->getFieldSpecialConf($masterTable,$fN,$conf);
                
                /**** USE RTE OR NOT  ****/
                //if(!empty($specialConf) && is_object($this->RTEObj) && $this->RTEObj->isAvailable()) {   // use RTE    
                if(!empty($specialConf) && t3lib_extmgm::isLoaded('rtehtmlarea')) {   // use RTE    
                	$this->RTEcounter++;
                	//$conf['RTE'][$this->RTEcounter]['formName']=$this->formName = $masterTable.'_form';
                	//$conf['RTE'][$this->RTEcounter]['field']=$this->strEntryField = $fN;
                	$this->PA['itemFormElName'] = $fieldName;
                	$feData = $conf['inputvar.']['fedata'];
                	$this->PA['itemFormElValue'] = $feData[$masterTable][$fN];
                	$conf['RTE'][$this->RTEcounter]['spec']=$this->specConf = $specialConf; 
                	$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
                	$this->thisConfig = $pageTSConfig['RTE.']['default.']['FE.'];
  		   	        $this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');//&t3lib_BEfunc::RTEgetObj();
		    	    		$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
		            	$this->thisConfig = $pageTSConfig['RTE.']['default.']['FE.'];

                	$this->thePidValue = $GLOBALS['TSFE']->id;
					
                	//$RTEItem ='###RTE_'.$this->RTEcounter.'###';
               	   $conf['RTE'][$this->RTEcounter]['cmdmode']=$cmdmode;
                	//$conf['RTE'][$this->RTEcounter]['table']=$masterTable;
                	//$conf['RTE'][$this->RTEcounter]['PA']=$this->PA;
									//$conf['RTEItem'][$this->RTEcounter] = $this->RTEObj->drawRTE($this,$masterTable,$fN,$row=array(), $this->PA, $this->specConf, $this->thisConfig, $this->RTEtypeVal, '', $this->thePidValue);
									$RTEItem = $this->RTEObj->drawRTE($this,$masterTable,$fN,$row=array(), $this->PA, $this->specConf, $this->thisConfig, $this->RTEtypeVal, '', $this->thePidValue);
                  return $RTEItem . '<div'.$this->caller->pi_classParam('rte-clearer').'></div>'.$EVAL_ERROR_FIELD;
                } else {                                                                   // dont use RTE
    	            return '<textarea'.$defaultParams.' cols="'.$conf['TCAN'][$masterTable]["columns"][$fN]['config']["cols"].'" rows="'.$conf['TCAN'][$masterTable]["columns"][$fN]['config']["rows"].'" ></textarea>'.$EVAL_ERROR_FIELD; // removed wrap="VIRTUAL"
                }
                break;
            case 'check':
                if($conf['TCAN'][$masterTable]['columns'][$fN]['config']['cols']>1) debug("getFormFieldCode():: WARNING, checkbox have more cols, not implemented yet.");
                #return '<input type="hidden" '.$defaultParams.' ><input type="checkbox" '.$defaultParams_feVal.' >';
                return '<input type="checkbox" '.$defaultParams.' />'.$EVAL_ERROR_FIELD;
                break;
            case 'group':
                if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["internal_type"]=='file')	{
                	// fetch data from table
                	$feData = $conf['inputvar.']['fedata'];
                	$uid = $feData[$masterTable][$conf['uidField']] ? $feData[$masterTable][$conf['uidField']] : $conf['inputvar.']['rU'];
                	$uid = $uid ? $uid : (($conf['fe_userEditSelf'] && $masterTable=='fe_users')?$GLOBALS['TSFE']->fe_user->user['uid']:'');// check if fe_userEditSelf ??? Quid if no uid ???
                	$rec = $GLOBALS['TSFE']->sys_page->getRawRecord($masterTable,$uid);
                	// make option tags from existing data.
                	$options = "";
                	foreach(explode(",",$rec[$fN]) as $opt)
                	  $options .= '<option value="'.$opt.'">'.$opt.'</option>';
                
                	$result .= '<select id="sel_'.$masterTable.'_'.$fN.'_'.$uid.'" size="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']["size"].'" name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_select" style="width:250px;">
                                   ###FIELD_'.$fN.'_OPTIONS###
                                   </select>
                                   <input id="selh_'.$masterTable.'_'.$fN.'_'.$uid.'" type="hidden" name="'.$fieldName.'" />';
                	$size=0;
                	if ($rec[$fN]) $size=sizeof(explode(",",$rec[$fN]));
                	//if ($size > 0) $result.='<a onclick="feedit_manipulateGroup(\''.$fieldName.'\');return false;" title="'.$this->metafeeditlib->getLL("delete_image_tooltip",$conf).'"><img border="0" src="typo3/gfx/group_clear.gif" alt="" /></a>';
                	$result.='<a onclick="feedit_manipulateGroup(\''.$fieldName.'\');return false;" title="'.$this->metafeeditlib->getLL("delete_image_tooltip",$conf).'"><img border="0" src="typo3/gfx/group_clear.gif" alt="" /></a>';
                	//if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["maxitems"]>$size) {
                	 // $result .= $conf['TCAN'][$masterTable]['columns'][$fN]['config']["allowed"].', Max file size : '.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['max_size'].' Kb<br/><input type="file" name="FE['.$masterTable.']'.$gridMark.'['.$fN.'_file][]" title="'.$this->metafeeditlib->getLL("add_image_tooltip",$conf).'" />';
                	//}
                	$filetypes=t3lib_div::trimExplode(',',$conf['TCAN'][$masterTable]['columns'][$fN]['config']["allowed"]);
                	$filetype='';
                	// to handle filetype css
                	foreach ($filetypes as $ft) {
                		if ($this->metafeeditlib->type_from_file('toto.'.$ft,$conf)=='image1') {
                			$filetype=" tx_mfedt_ft_img";
                			break;
                		}
                	}
                	$result .= '<!-- ###FILE_BROWSER_'.$fN.'### -->'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']["allowed"].', Max file size : '.$conf['TCAN'][$masterTable]['columns'][$fN]['config']['max_size'].' Kb<br/><input id="'.$masterTable.'$'.$fN.'$'.$uid.'" class="tx_mfedt_file'.$filetype.'" type="file" name="FE['.$masterTable.']'.$gridMark.'['.$fN.'_file][]" title="'.$this->metafeeditlib->getLL("add_image_tooltip",$conf).'" /><!-- ###FILE_BROWSER_'.$fN.'### -->';
                	$result.='###FIELD_EVAL_'.$fN.'###<br/><div '.$this->caller->pi_classParam('form-error-field').'>###EVAL_ERROR_FIELD_'.$fN.'######EVAL_ERROR_FIELD_'.$fN.'_file###</div>';                
                	return $result;
                } else {
                	if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["allowed"]) {  // reference to elements from another table
                		$options="###FIELD_".$fN."_OPTIONS###";
                		$srow = '<select '.$size.' name="FE['.$masterTable.']'.$gridMark.'['.$fN.']">';
                  		if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["size"]) {
                    		$size = ' size="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']["size"].'" ';
            
                    		if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["maxitems"]>1) {
								$double_select=true;
								if ($double_select){
									//on vide les options normales qui ne servent pas
									//$option='';
									$srow='<table><tr><td>';
									$srow.='<select '.$size.' multiple="multiple" name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_list" class="'.$this->caller->pi_getClassName('list_table_field').'_list '.$this->caller->pi_getClassName('list_table_field_'.$fN).'_list">';
									$srow.='</select></td>';
									$srow.='<td><a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Top\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur/group_totop.gif" width="14" height="14" border="0" alt="'.$this->metafeeditlib->getLL("move_top", $conf).'" title="'.$this->metafeeditlib->getLL("move_top", $conf).'" /></a><br />';
									$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Up\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur/up.gif" width="14" height="14" border="0"  alt="'.$this->metafeeditlib->getLL("move_up", $conf).'" title="'.$this->metafeeditlib->getLL("move_up", $conf).'" /></a><br />';
									$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Down\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur/down.gif" width="14" height="14" border="0"  alt="'.$this->metafeeditlib->getLL("move_bt", $conf).'" title="'.$this->metafeeditlib->getLL("move_bt", $conf).'" /></a><br />';
									$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Bottom\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur/group_tobottom.gif" width="14" height="14" border="0"  alt="'.$this->metafeeditlib->getLL("move_down", $conf).'" title="'.$this->metafeeditlib->getLL("move_down", $conf).'" /></a><br />';
									$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Remove\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur/group_clear.png" width="18" height="20" border="0"  alt="'.$this->metafeeditlib->getLL("move_delete", $conf).'" title="'.$this->metafeeditlib->getLL("move_delete", $conf).'" /></a><br/></td>';
									$srow.='<td><select  name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_sel"  '.$size.'  onchange="setFormValueFromBrowseWin(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text,\'\'); " class="'.$this->caller->pi_getClassName('list_table_field').'_list '.$this->caller->pi_getClassName('list_table_field_'.$fN).'_sel">';
									$srow.='';
									$hr = '</td></tr></table><input type="hidden" name="'.$fieldName.'" />';
									//$options="";
						
									$conf['additionalJS_end']['feedit_'.$fN.'_again_set_data'] = 'setFormRegenerer(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']'.'\');';
								}else{
									$size .= ' multiple ';
									$onchange = ' onchange="feedit_manipulateMultipleSelect(\''.$fieldName.'\')" ';
									$srow = '<select '.$size.' '.$onchange.' name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_select">';
									$hr = '<input type="hidden" name="'.$fieldName.'" />';
								}
                    		}
                  		}
                  		$row .= $srow .$options.'</select>'.$hr;
                  		return $row.$EVAL_ERROR_FIELD;
                        // NoMM
                    }
                    debug("getFormFieldCode()::GROUP TYPE 'DB' NOT SUPPORTED YET");
                }
                break;
            case 'select':
                $feData = $conf['inputvar.']['fedata'];
                $uid = $feData[$masterTable][$conf['uidField']] ? $feData[$masterTable][$conf['uidField']] : $conf['inputvar.']['rU'];
                $rec = $GLOBALS['TSFE']->sys_page->getRawRecord($masterTable,$uid);
                if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["foreign_table"]) {               // reference to elements from another table
                    $options="###FIELD_".$fN."_OPTIONS###";
                    // gets uids of selected records.
                    $uids = array();
                    if($feData[$masterTable][$fN]) {                                // from post var
                        $uids = explode(",",$feData[$masterTable][$fN]);
                    } elseif($conf['TCAN'][$masterTable]['columns'][$fN]['config']["MM"] && $uid) {  // from mm-relation
                    		$mmTable=$conf['TCAN'][$masterTable]['columns'][$fN]['config']["MM"];
                        $MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$mmTable,$mmTable.'.uid_local=\''.$uid.'\'',$mmTable.'.sorting');
                        if (mysql_error())	debug(array(mysql_error(),$query),'getFormFieldCode()::field='.$fN);
                        
                        if(mysql_num_rows($MMres)!=$rec[$fN])
                        debug("Wrong number of selections reached");
                        while($MMrow = mysql_fetch_assoc($MMres))
                        $uids[] = $MMrow["uid_foreign"];
                    } else {                                                        // clean from DB
                        $uids = explode(",",$rec[$fN]);
                    }
                } elseif($conf['TCAN'][$masterTable]['columns'][$fN]['config']["items"]) {   // fixed items            
                    // Get selected uids.
                    $uids = array();
                    if($feData[$masterTable][$fN]) {                                // from post var
                      $uids = explode(",",$feData[$masterTable][$fN]);
                    } elseif(!is_null($rec)) {                                      // clean from DB
                      $uids = explode(",",$rec[$fN]);
                    } elseif($cmd=='create' && $conf['TCAN'][$masterTable]['columns'][$fN]['config']['default']){
                      $uids = explode(",",$conf['TCAN'][$masterTable]['columns'][$fN]['config']['default']);
                    }
                
                    $items = $conf['TCAN'][$masterTable]['columns'][$fN]['config']["items"];
                    //$options = '<option value="0">-----</option>';
                    
                    if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["itemsProcFunc"]) {     // if itemsProcFunc is set to fill the select box
                      $options = '';
                      $params = $conf['TCAN'][$masterTable]['columns'][$fN];
                      $params['items'] = &$items;
                      t3lib_div::callUserFunction($conf['TCAN'][$masterTable]['columns'][$fN]['config']["itemsProcFunc"], $params, $this);
                    }
                
					$multi_option='';
					$multi_option_actif='';
                    foreach((array)$items as $key => $item) {
                        $selected = in_array($item[1],$uids)?'selected="selected"':"";
                        //if($key!=0)
                        $options .= '<option value="'.$item[1].'"'.$selected.'>'.$this->metafeeditlib->getLLFromLabel($item[0],$conf).'</option>';
						$multi_option.='<option value="'.$item[1].'">'.$this->metafeeditlib->getLLFromLabel($item[0],$conf).'</option>';
						if (in_array($item[1],$uids)){
							$multi_option_actif.='<option value="'.$item[1].'">'.$this->metafeeditlib->getLLFromLabel($item[0],$conf).'</option>';
						}
						
                    } 
                } else {
                    // unknown TCA config
                	$options = '<option><em>Unknown TCA-configuration</em></option>';
                }
            
                $srow = '<select '.$size.' name="FE['.$masterTable.']'.$gridMark.'['.$fN.']">';
                if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["size"]) {
                    $size = ' size="'.$conf['TCAN'][$masterTable]['columns'][$fN]['config']["size"].'" ';
                    
                    if($conf['TCAN'][$masterTable]['columns'][$fN]['config']["maxitems"]>1) {
						$double_select=true;
						if ($double_select){
						
							//on vide les options normales qui ne servent pas
							//$option='';
							$srow='<table><tr><td>';
							$srow.='<select '.$size.' multiple="multiple" name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_list" class="'.$this->caller->pi_getClassName('list_table_field').'_list '.$this->caller->pi_getClassName('list_table_field_'.$fN).'_list">';
							$srow.=''.$multi_option_actif;
							$srow.='</select></td>';
							$srow.='<td><a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Top\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur/group_totop.gif" width="14" height="14" border="0" alt="'.$this->metafeeditlib->getLL("move_top", $conf).'" title="'.$this->metafeeditlib->getLL("move_top", $conf).'" /></a><br />';
							$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Up\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur//up.gif" width="14" height="14" border="0"  alt="'.$this->metafeeditlib->getLL("move_up", $conf).'" title="'.$this->metafeeditlib->getLL("move_up", $conf).'" /></a><br />';
							$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Down\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur//down.gif" width="14" height="14" border="0"  alt="'.$this->metafeeditlib->getLL("move_bt", $conf).'" title="'.$this->metafeeditlib->getLL("move_bt", $conf).'" /></a><br />';
							$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Bottom\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur//group_tobottom.gif" width="14" height="14" border="0"  alt="'.$this->metafeeditlib->getLL("move_down", $conf).'" title="'.$this->metafeeditlib->getLL("move_down", $conf).'" /></a><br />';
							$srow.='<a href="#" onclick="setFormValueManipulate(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',\'Remove\'); return false;"><img src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/selecteur//group_clear.png" width="18" height="20" border="0"  alt="'.$this->metafeeditlib->getLL("move_delete", $conf).'" title="'.$this->metafeeditlib->getLL("move_delete", $conf).'" /></a><br/></td>';
							$srow.='<td><select  name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_sel"  '.$size.' onchange="setFormValueFromBrowseWin(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text,\'\'); " class="'.$this->caller->pi_getClassName('list_table_field').'_list '.$this->caller->pi_getClassName('list_table_field_'.$fN).'_sel">';
							$srow.=$multi_option;
							$srow.='';
							$hr = '</td></tr></table><input type="hidden" name="'.$fieldName.'" />';
							
							
							//$options="";
				
							$conf['additionalJS_end']['feedit_'.$fN.'_again_set_data'] = 'setFormRegenerer(\'FE['.$masterTable.']'.$gridMark.'['.$fN.']'.'\');';
						}else{
						
							$size .= ' multiple ';
							$onchange = ' onchange="feedit_manipulateMultipleSelect(\''.$fieldName.'\')" ';
							$srow = '<select '.$size.' '.$onchange.' name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_select">';
							//$hr = '<input type="hidden" name="'.$fieldName.'" value="'.implode(",",$uids).'">';
							$hr = '<input type="hidden" name="'.$fieldName.'" />';
						}
						
                    }
                }
                //$row .= '<select '.$size.' '.$onchange.' name="FE['.$masterTable.']'.$gridMark.'['.$fN.']_select">
                //$row .= '<select '.$size.' '.$onchange.' name="FE['.$masterTable.']'.$gridMark.'['.$fN.']">
                $row .= $srow .$options.'</select>'.$hr;
                return $row.$EVAL_ERROR_FIELD;
                break;
            case 'radio':
                if($conf['TCAN'][$masterTable]['columns'][$fN]['config']['cols']>1) debug("getFormFieldCode():: WARNING, checkbox have more cols, not implemented yet.");
        
            	for ($i = 0; $i < count ($conf['TCAN'][$masterTable]['columns'][$fN]['config']['items']); ++$i) {
                    	$row .= '<input type="radio"' . $this->caller->pi_classParam('radio') . 'id="'. $this->caller->pi_getClassName($fN) . '-' . $i . '" name="FE['.$masterTable.']'.$gridMark.'['.$fN.']"'.
                            'value="'.$i.'" '.($i==0?'checked="checked"':'').' />' .
                            '<label for="' . $this->caller->pi_getClassName($fN) . '-' . $i . '">' . $this->metafeeditlib->getLLFromLabel($conf['TCAN'][$masterTable]['columns'][$fN]['config']['items'][$i][0],$conf) . '</label>';
                }
            	return $row.$EVAL_ERROR_FIELD;
                break;
            case 'user':
				$subType = ($conf['TCAN'][$masterTable]['columns'][$fN]['config']['subType'] && $conf['TCAN'][$masterTable]['columns'][$fN]['config']['subType'] == 'file')?true:false;
				$PA['itemFormElName']=$subType?'FE['.$masterTable.']'.$gridMark.'['.$fN.'_file][]':'FE['.$masterTable.']['.$fN.']';
				$PA['fieldChangeFunc']=$subType?array(''=>''):array(0 => 'feedit_'.$masterTable.'_formGet('."'".$fieldName."','".$conf['TCAN'][$masterTable]['columns'][$fN]['config']["eval"]."','".$is_in."','".$checkbox."','".$checkboxVal."','".$checkbox_off."')");
				$PA['table']=$masterTable;
				$PA['field']=$fN;
				$PA['row']=array();
				$PA['pObj']=&$this;
				return t3lib_div::callUserFunction($conf['TCAN'][$masterTable]['columns'][$fN]['config']['userFunc'], $PA, $this);
				break;
            case 'flex':
                debug("getFormFieldCode():: flex fields not implemented yet.");
                break;
            case 'passthrough':
                debug("getFormFieldCode():: passthrough fields not implemented yet.");
                break;
            default:
                debug("getFormFieldCode():: Unknown type (".$type.") with field ".$fN);
                return '<input type="text"'.$defaultParams.' />';
                break;
        }
    }


  function getSize(&$conf, $fN, $masterTable)
  {	
	$FT=$conf['TCAN'][$masterTable]['columns'][$fN]['config']['foreign_table'];
	$size=25;
	if ($FT) {
		$labelField=$conf['TCAN'][$FT]['ctrl']['label'];
		$size=$conf['TCAN'][$FT]['columns'][$labelField]['config']['size'];
			
	} else {
		if($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size'])
		{
			$size=$conf['TCAN'][$masterTable]['columns'][$fN]['config']['size'];
		}	
		// Image
		if( $conf['TCAN'][$masterTable]['columns'][$fN]['config']['type']== 'group' &&  $conf['TCAN'][$masterTable]['columns'][$fN]['config']['internal_type']=='file') {
			$size=$conf[$conf['cmdmode'].'.']['imgConf.'][$fN.'.']['maxW']?$conf[$conf['cmdmode'].'.']['imgConf.'][$fN.'.']['maxW']:15;
		}	
	}
	return $size;
  }
  
  
    /**
    * getListFields : gets fields for list mode ...
    * @param	[array]		$conf: Configuration array
    * @param	[booelan]   $textmode	: if true we output only text mode data (no input fields)....
    * @param	[string]    $type: export type ...
    * @return	[string]	$content :
    */
    
    function getListFields(&$conf,$textmode=false,$type='') {
        $fields=$conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field;
        $fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
        foreach($fieldArray as $FN) {
        	$params=t3lib_div::trimExplode(';',$FN);

	        if ($params[0]!='--div--'  && $params[0]!='--fse--' && $params[0]!='--fsb--') {
        		$masterTable = $conf['table'];
						$size = $this->getSize($conf, $FN, $masterTable);
	          $ftA=$this->metafeeditlib->getForeignTableFromField($FN,$conf,'',array());     
	        	$Lib=$this->metafeeditlib->getLLFromLabel($ftA['fieldLabel'],$conf);
	        	$href=$this->metafeeditlib->hsc($conf,$this->pi_linkTP_keepPIvars_url(array('sort' => $FN.':###SORT_DIR_'.$FN.'###'),1));
	        	if(!$textmode) {
	        			if ($this->piVars['exporttype']==EXCEL)
	        			    $ret.='<th><data>'.$Lib.'</data><size>'.$size.'</size></th>';
	        			else
	        			    $ret.=$conf['list.']['sortFields']?'<th><a class="###SORT_CLASS_'.$FN.'###" href="'.$href.'"><i>&nbsp;</i>'.$Lib.'</a></th>':'<th>'.$Lib.'</th>';
	        	} else if ($type) {
							//$img=0;
							//if( $conf['TCAN'][$masterTable]['columns'][$FN]['config']['type']== 'group' &&  $conf['TCAN'][$masterTable]['columns'][$FN]['config']['internal_type']=='file') $img=1;
							//$img=0 bug ??;
							$img=0;
							$ret.='<td><data>'.$Lib.'</data><size>'.$size.'</size><img>'.$img.'</img></td>';	
						} else {
						  $ret.=$Lib.';';	
						}
    			}
    		}	
        return $ret;
    }

    /**
    * getListDataFields : Creates template for list row according to type (html,pdf,xls,cvs...).
    *
    * @param	array		$conf: Configuration array();
    * @param	boolean     $textmode	: true we output only text mode data (no input fields), will be transmitted to getPreviewField....
    * @param    string		$type: export type : html,csv or empty ... if html no exotic tags. If csv (fields seprated by ';')
    * @return	string		$ret : html code
    */
     
    function getListDataFields(&$conf,$textmode=false,$type='',$rawimage=false) {
    	$fields=$conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field;
    	$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
    	$cols=$conf['list.']['nbCols'];
			$masterTable = $conf['table'];
    	foreach($fieldArray as $FN) {
    		$params=explode(';',$FN);
    		if ($params[0]!='--div--'&&$params[0]!='--fse--'&&$params[0]!='--fsb--') {
     			$FCode=$this->getPreviewFieldCode('edit',$conf,$FN,$textmode?0:1);
    			$FN=str_replace('.','_',$FN);
    			// do we ask for field value only ?
    			switch ($type) {
    				case 'html' : 
 						// html presentation
						$size = $this->getSize($conf, $FN, $masterTable);
						if(!$this->piVars['nbCols']) {
							$img=0;
							$dir='';
							if( $conf['TCAN'][$masterTable]['columns'][$FN]['config']['type']== 'group' &&  $conf['TCAN'][$masterTable]['columns'][$FN]['config']['internal_type']=='file') {
								$img=1;
								$dir=$conf['TCAN'][$masterTable]['columns'][$FN]['config']['uploadfolder'];
							}							
							$ret.='<td><data>'.$FCode.'</data><size>'.$size.'</size><img>'.$img.($dir?'<dir>'.$dir.'</dir>':'').'</img></td>';	
						}else {
							// Pdf presentation ???
							$ret.='<td><data>'.$FCode.'</data><size>'.$size.'</size></td>';	
						}
    			  break;
    				case 'csv' :
    				// No presentation data
    				$ret.='"'.str_replace('"','""',$FCode).'";';	
    			  break;
    				case 'xls' :
						
    				if( $conf['TCAN'][$masterTable]['columns'][$FN]['config']['type']== 'group' &&  $conf['TCAN'][$masterTable]['columns'][$FN]['config']['internal_type']=='file') {
								//$dir=$conf['TCAN'][$masterTable]['columns'][$FN]['config']['uploadfolder'];
								$FCode='###FIELD_EVAL_'.$FN.'###';

						}					
        		if ($cols) {
        			$ret.=$type?$FCode:'<div class="'.$this->caller->pi_getClassName('list_table_field').' '.$this->caller->pi_getClassName('list_table_field_'.$FN).'">'.$FCode.'</div>';
        		} else {
        			$ret.='<td '.($conf['list.']['align.'][$FN]?'align="'.$conf['list.']['align.'][$FN].'"':'').'>'.($textmode?'':'<div class="'.$this->caller->pi_getClassName('list_field').' '.$this->caller->pi_getClassName('list_field_'.$FN).'">').$FCode.($textmode?'':'</div>').'</td>';
        		}   			        
    			  break;
    			  default:
        		if ($cols) {
        			$ret.=$type?$FCode:'<div class="'.$this->caller->pi_getClassName('list_table_field').' '.$this->caller->pi_getClassName('list_table_field_'.$FN).'">'.$FCode.'</div>';
        		} else {
        			$ret.='<td '.($conf['list.']['align.'][$FN]?'align="'.$conf['list.']['align.'][$FN].'"':'').'>'.($textmode?'':'<div class="'.$this->caller->pi_getClassName('list_field').' '.$this->caller->pi_getClassName('list_field_'.$FN).'">').$FCode.($textmode?'':'</div>').'</td>';
        		}   			        
    		  }
    		  /*
    			if ($textmode) {
    				if ($cols) {
    					$ret.=$FCode.';'; // ; for csv 
    				} else {
    					if ($type) {
    						// html presentation
    						$masterTable = $conf['table'];
    						$size = $this->getSize($conf, $FN, $masterTable);
    						if(!$this->piVars['nbCols']) {
    							$img=0;
    							$dir='';
    							if( $conf['TCAN'][$masterTable]['columns'][$FN]['config']['type']== 'group' &&  $conf['TCAN'][$masterTable]['columns'][$FN]['config']['internal_type']=='file') {
    								$img=1;
    								$dir=$conf['TCAN'][$masterTable]['columns'][$FN]['config']['uploadfolder'];
    							}
    							
    							$ret.='<td><data>'.$FCode.'</data><size>'.$size.'</size><img>'.$img.($dir?'<dir>'.$dir.'</dir>':'').'</img></td>';	
    							}
    						else {
    							// Pdf presentation ???
    							$ret.='<td><data>'.$FCode.'</data><size>'.$size.'</size></td>';	
    						}
    					} else {
    							// No presentation data
    						  $ret.=$FCode.';';	
    					}
    				}
    			} else {
    				if ($cols) {
    					$ret.=$type?$FCode:'<div class="'.$this->caller->pi_getClassName('list_table_field').' '.$this->caller->pi_getClassName('list_table_field_'.$FN).'">'.$FCode.'</div>';
    				} else {
    					$ret.='<td '.($conf['list.']['align.'][$FN]?'align="'.$conf['list.']['align.'][$FN].'"':'').'>'.($type?'':'<div class="'.$this->caller->pi_getClassName('list_field').' '.$this->caller->pi_getClassName('list_field_'.$FN).'">').$FCode.($type?'':'</div>').'</td>';
    				}
    			}*/
    		}
    
    	}
    	return $ret;
    }
    /**
    * getListDataFields : Creates template for list row according to type (html,pdf,xls,cvs...).
    *
    * @param	array		$conf: Configuration array();
    * @param	boolean     $textmode	: true we output only text mode data (no input fields), will be transmitted to getPreviewField....
    * @param    string		$type: export type : html,csv or empty ... if html no exotic tags. If csv (fields seprated by ';')
    * @return	string		$ret : html code
    */
     
    function getEditDataFields(&$conf,$textmode=false,$type='') {
    	$fields=$conf['edit.']['show_fields']?$conf['edit.']['show_fields']:$this->id_field;
    	$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
    	$cols=$conf['list.']['nbCols'];
    	
    	$rowcount=0;
    	$fscount=0;
    	
    	foreach($fieldArray as $FN) {
    		$params=explode(';',$FN);
    		// If we meet tab delimiter
    		if ($params[0]=='--div--') {
    			if ($rowcount) {
    				$ret.="</tr><tr>";
    			} else {
    				$ret.="<tr>";
    				$rowcount=1;
    			}		
    		}
    		
    		// If we meet fieldset delimiter

    		if ($params[0]=='--fsb--') {
    			if ($rowcount) {
    				$ret.="</tr>";
    			} else {
    				$rowcount=1;
    			}		
    			$ret.='<tr><td><data>'.$conf['list.']['fieldSetNames.'][$fscount].'</data></td></tr><tr>'; 
    			$fscount++;
    		}
    		if ($params[0]=='--fse--') {
    			if ($rowcount) {
    				$ret.="</tr>";
    				$rowcount=0;
    			}	
    		}
    		if ($params[0]!='--div--' && $params[0]!='--fse--' && $params[0]!='--fsb--') {
    			if (!$rowcount){
    				$ret.="<tr>";
    				$rowcount=1;
    			}
     			$FCode=$this->getPreviewFieldCode('edit',$conf,$FN,$textmode?0:1,$Lib);
    			$FN=str_replace('.','_',$FN);
    			// do we ask for field value only ?
    			switch ($type) {
    				case 'html' : 
 							// html presentation
							$masterTable = $conf['table'];
							$size = $this->getSize($conf, $FN, $masterTable);
							$sizeLib=25;					
							if(!$this->piVars['nbCols']) {
								$img=0;
								$dir='';
								if( $conf['TCAN'][$masterTable]['columns'][$FN]['config']['type']== 'group' &&  $conf['TCAN'][$masterTable]['columns'][$FN]['config']['internal_type']=='file') {
									$img=1;
									$dir=$conf['TCAN'][$masterTable]['columns'][$FN]['config']['uploadfolder'];
								}							
								$ret.='<td><data>'.$Lib.'</data><size>'.$sizeLib.'</size></td><td><data>'.$FCode.'</data><size>'.$size.'</size><img>'.$img.($dir?'<dir>'.$dir.'</dir>':'').'</img></td>';	
							} else {
								// Pdf presentation ???
								$ret.='<td><data>'.$Lib.'</data><size>'.$sizeLib.'</size></td><td><data>'.$FCode.'</data><size>'.$size.'</size></td>';	
							}
    			    break;
    			  case 'csv' :
    					// No presentation data
    				  $ret.='"'.str_replace('"','""',$FCode).'";';	
    			    break;
    			  default:
        			if ($cols) {
        				$ret.=$type?$FCode:'<div class="'.$this->caller->pi_getClassName('list_table_field').' '.$this->caller->pi_getClassName('list_table_field_'.$FN).'">'.$FCode.'</div>';
        			} else {
        				$ret.='<td '.($conf['list.']['align.'][$FN]?'align="'.$conf['list.']['align.'][$FN].'"':'').'>'.($textmode?'':'<div class="'.$this->caller->pi_getClassName('list_field').' '.$this->caller->pi_getClassName('list_field_'.$FN).'">').$FCode.($textmode?'':'</div>').'</td>';
        			}    
    			}
    		    
    		}
    		if ($rowcount) {
    				$ret.="</tr>";
    				$rowcount=0;
    		}	
    
    	}
    	return $ret;
    }
	/**
	 * getEditSumFields : generates template field cells (<td>) for sums of displayed columns ....
	 *
    * @param	array		$conf: Configuration array();
    * @param	boolean     $textmode	: true we output only text mode data (no input fields)....
    * @param    string		$type: ...
    * @return	string		$ret : html code
	 */

	function getEditSumFields($prefix,&$conf,&$count, $textmode=false, $type='')
	{
		$fields=$conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field;
		$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
		$firstemptycell='###FIRSTEMPTYCELL###';
		$count=0;
		$ret=$type=='PDF'?'<gb>1</gb>':'';
		foreach($fieldArray as $FN) {
			$params=explode(';',$FN);
			if ($params[0]!='--div--' && $params[0]!='--fse--' && $params[0]!='--fsb--' ) {
				$_FN=str_replace('.','_',$FN);// Must be very careful with this replace business ...
				$masterTable = $conf['table'];
				$sumarray=t3lib_div::trimexplode(',',$conf['list.']['sumFields']);
				$ftA=$this->metafeeditlib->getForeignTableFromField($FN,$conf,'',array());               
 				$Lib='Total '.$this->metafeeditlib->getLLFromLabel($ftA['fieldLabel'],$conf).':';
				$size = $this->getSize($conf, $_FN, $masterTable);
				
				if (!(in_array($_FN,$sumarray))) {			// If field is not a sum field ...
					if ($textmode){
						if ($type)					// Empty cell for PDF
						$ret .= '<td><data>'.($firstemptycell?$firstemptycell:'').'</data><size>'.$size.'</size></td>';
						else 								// Empty cell for CSV
						$ret.= ($firstemptycell?$firstemptycell:'').';';
					}
					else	{							// Empty cell for Excel
						$ret.='<td>'.($firstemptycell?$firstemptycell:'').'</td>';	
					}
					if ($firstemptycell) $firstemptycell='';
					
				} else {   						// Field is a sum field
					$count++;
					if (!$textmode)			// Not text mode only
						$ret.='<td '.($conf['list.']['align.'][$_FN]?'align="'.$conf['list.']['align.'][$_FN].'"':'').'>'.'###'.$prefix.'_FIELD_'.$_FN.'###</td>';
						//$ret.='<td '.($conf['list.']['align.'][$_FN]?'align="'.$conf['list.']['align.'][$FN].'"':'').'>'.$Lib.'###SUM_FIELD_'.$_FN.'###</td>';
					else  {
						if ($type) 						// Fichier PDF
							//$ret.='<td><data>'.$Lib.'###'.$prefix.'_FIELD_'.$FN.'###</data><size>'.$size.'</size></td>';
							$ret.='<td><data>###'.$prefix.'_FIELD_'.$_FN.'###</data><size>'.$size.'</size></td>';
						else 
							$ret.=$Lib.'###'.$prefix.'_FIELD_'.$_FN.'###;';
					}
				}
			}
		}			
		return $ret;
	}  
	/**
	 * getSumFields : generates template fields for all sums even of undisplayed columns ....
	 *
	 * @param	[type]		$conf: ...
	 * @param	[type]		$textmode: ...
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
  
 function getSumFields(&$conf, $textmode=false, $type=''){
	$ret = '';
	$fields=$conf['list.']['sumFields'];
	$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
	foreach($fieldArray as $FN) {
 		$params=t3lib_div::trimExplode(';',$FN);
    if ($params[0]!='--div--') {
			$Lib='Total '.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]['columns'][trim($FN)]['label'],$conf).':';
			if(!$textmode) {
				$ret.='<td>'.$Lib.'###SUM_FIELD_'.$FN.'###</td>';
			} else if ($type) {
						$masterTable = $conf['table'];
						$size = $this->getSize($conf, $FN, $masterTable);
						$ret.='<td><data>'.$Lib.'###SUM_FIELD_'.$FN.'###</data><size>'.$size.'</size></td>';	
					} else {
						  $ret.=$Lib.'###SUM_FIELD_'.$FN.'###;';	
					}
			}
		}
	return $ret;
  }
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
 
  function getGridDataFields (&$conf, $textmode=false, $type='') {
		$fields=$conf['grid.']['show_fields']?$conf['grid.']['show_fields']:$this->id_field;
		$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
		$masterTable = $conf['table'];
		
		foreach($fieldArray as $FN) {
			$params=explode(';',$FN);							//$textmode?0:1
			$FCode=(!$conf['disableEdit'])?$this->getFormFieldCode('edit',$conf,$FN,1,'grid'):$this->getPreviewFieldCode('edit',$conf,$FN,1);
			$PCode=$this->getPreviewFieldCode('edit',$conf,$FN,$textmode?0:1);
			if ($params[0]!='--div--') {		
				if ($this->piVars['exporttype']== 'EXCEL')			// Excel
					$ret.=$PCode;
				else if($this->piVars['exporttype']== 'PDF') 		// PDF
						$ret.= $PCode;
					else if ($this->piVars['exporttype']== 'CSV')	   // CSV
						$ret.= $PCode;
					else 
						$ret.='<div class="'.$this->caller->pi_getClassName('grid_field').' '.$this->caller->pi_getClassName('grid_field_'.$FN).'">'.$FCode.'</div>';	
			}
		}
		return $ret;
  }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */

  function getBlogDataFields(&$conf) {
	$fields=$conf['blog.']['show_fields']?$conf['blog.']['show_fields']:'firstname,surname,email,homepage,place,crdate,entry,entrycomment';
	$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
	//$cols=$conf['blog.']['nbCols'];
	$ret='<tr><td>';
	foreach($fieldArray as $FN) {
		$params=explode(';',$FN);
			$FCode=$this->getPreviewFieldCode('blog',$conf,$FN,0);
			$Lib=$this->metafeeditlib->getLLFromLabel($conf['TCAN']['tx_metafeedit_comments']['columns'][trim($FN)]['label'],$conf);
			$ret.='<div class="'.$this->caller->pi_getClassName('blog_field').' '.$this->caller->pi_getClassName('blog_field_'.$FN).'"><div class="'.$this->caller->pi_getClassName('blog_label').'">'.$Lib.'</div>'.$FCode.'</div>';
	}
	$ret.='</td></tr>';
	return $ret;
  }

    /**
    * getBlogFormFields :
    *
    * @param	[type]		$$conf: ...
    * @return	[type]		...
    */
	 
    function getBlogFormFields(&$conf) {
        $fields=$conf['blog.']['show_fields']?$conf['blog.']['show_fields']:'firstname,surname,email,homepage,place,entry,entrycomment';
        $reqfields='email,entry,entrycomment';
        $reqfieldArray=array_unique(t3lib_div::trimExplode(",",$reqfields));
        $fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
        $ret="<table><tr><td>";
        foreach($fieldArray as $FN) {
        	$params=explode(';',$FN);
        		$FCode=$this->getFormFieldCode('blog',$conf,$FN,0,'edit');
					  $reqMarker = in_array($FN,$reqfieldArray) ? $conf['required_marker'] : '';
        		$Lib=$this->metafeeditlib->getLLFromLabel($conf['TCAN']['tx_metafeedit_comments']['columns'][trim($FN)]['label'],$conf);
        		$ret.='<div class="'.$this->caller->pi_getClassName('blog_field').' '.$this->caller->pi_getClassName('blog_field_'.$FN).'"><div class="'.$this->caller->pi_getClassName('blog_label').'"><div'.$this->caller->pi_classParam($fsc?'fsrm':'form-required-marker').'>'.$reqMarker.'</div>'.$Lib.'</div>'.$FCode.'</div>';
        }
        $ret.="</td></tr></table>";
        
        return $ret;
    }
    
    /**
    * getListTemplate : get List view templates 
    *
    * @param	[type]		$$conf: ...
    * @return	[type]		...
    */
    
    function getListTemplate(&$conf) {
        $ret=$this->getListItemTemplate($conf);
        $ret.=$this->getListNoItemTemplate($conf);
        return ($ret);
    }

    /**
    * getGroupByFields : Gets Group By Field Break templates
    *
    * @param	[type]		$$conf: ...
    * @return	[type]		...
    */
    
    function getGroupByFields(&$conf,$textmode=false,$exporttype='') {
        if ($conf['list.']['groupByFieldBreaks']) {
        	$GROUPBYFIELDS='<!-- ###GROUPBYFIELDS### begin -->';
        	$fields=$conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field;
        	$nbf=1;
        	$nbf=count(t3lib_div::trimExplode(",",$fields));
        
        	if ($conf['list.']['displayDirection']=='Down') $this->GROUPBYFIELDS.="<tr><td>";
            $fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFieldBreaks']);
            $tab="";
            foreach($fNA as $fN) {
        		$fN2=t3lib_div::trimexplode(':',$fN);
        		$fN=$fN2[0];
        		if ($conf['list.']['hiddenGroupByField.'][$fN]) continue;
        
        		//CMD - correction du nom de la class
        		$classFn = str_replace('.', '_', $fN);
        		
        		$size = $this->getSize($conf, $fN, $conf['table']);
            
                $GROUPBYFIELDS.='<!-- ###GROUPBYFIELD_'.$fN.'### start -->'.($textmode?($exporttype?'<tr><gb>1</gb><td><data>':''):'<tr><td colspan="'.($conf['list.']['nbCols']?$conf['list.']['nbCols']:$nbf).'"><div class="'.$this->caller->pi_getClassName('groupBy').' '.$this->caller->pi_getClassName('groupBy_'.$classFn).'">').$tab.'###GROUPBY_'.$fN.'###'.($textmode?($exporttype?'</data><size>'.$size.'</size></td></tr>':chr(10)):'</div></td></tr>').'<!-- ###GROUPBYFIELD_'.$fN.'### end -->';
        		$tab.="&nbsp;>&nbsp;";
            }
        	if ($conf['list.']['displayDirection']=='Down') $this->GROUPBYFIELDS.="</td></tr>";
        	$GROUPBYFIELDS.='<!-- ###GROUPBYFIELDS### end -->';
        }
        return $GROUPBYFIELDS;
    }
	
	/**
	 * getGroupByFooterFields : Gets Group By Footer Field Break templates
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
	 
  function getGroupByFooterFields(&$conf,$textmode=false,$exporttype='') {
  	if ($conf['list.']['groupByFieldBreaks']) {
  		$GROUPBYFIELDS='';
			$fields=$conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field;
			$nbf=1;
			$nbf=count(t3lib_div::trimExplode(",",$fields));

			if ($conf['list.']['displayDirection']=='Down') $this->GROUPBYFIELDS.="<tr><td>";
	    $fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFieldBreaks']);
	    $tab="";
	    foreach($fNA as $fN) {
				$fN2=t3lib_div::trimexplode(':',$fN);
				$fN=$fN2[0];
				if ($conf['list.']['hiddenGroupByField.'][$fN]) continue;
				//CMD - correction du nom de la class
				$classFn = str_replace('.', '_', $fN);
				// Total processing 
				$size = $this->getSize($conf, $fN, $conf['table']);
				$div=($textmode?'':'<div class="'.$this->caller->pi_getClassName('groupBy').' '.$this->caller->pi_getClassName('groupBy_'.$classFn).'">').$tab.'Total : ###GROUPBYFOOTER_'.$fN.'###(###FOOTERSUM_'.$fN.'_FIELD_metafeeditnbelts###)'.($textmode?'':'</div>');
				if ($conf['list.']['sumFields']) {
					$sum='<!--###FOOTERSUM_FIELDS### begin -->'.$this->getEditSumFields('FOOTERSUM_'.$fN,$conf,$count, $textmode, $exporttype);
					//$tmp.='<tr>'.$this->getSumFields($conf, false, 'html').'</tr>'; //TODO Handle undisplayed sumfields ...
					$sum.='<!--###FOOTERSUM_FIELDS### end -->';
					$sum=$this->cObj->substituteMarker($sum, '###FIRSTEMPTYCELL###',$div);

	      	$GROUPBYFIELDS='<!-- ###GROUPBYFOOTERFIELD_'.$fN.'### start -->'.($textmode?($exporttype?'<tr><gb>1</gb>':''):'<tr>').$sum.($textmode?($exporttype?'</tr>':chr(10)):'</tr>').'<!-- ###GROUPBYFOOTERFIELD_'.$fN.'### end -->'.$GROUPBYFIELDS;
				} else {
	      	$GROUPBYFIELDS='<!-- ###GROUPBYFOOTERFIELD_'.$fN.'### start -->'.($textmode?($exporttype?'<tr><gb>1</gb><td>':''):'<tr><td colspan="'.($conf['list.']['nbCols']?$conf['list.']['nbCols']:$nbf).'">').$div.($textmode?($exporttype?'</td></tr>':chr(10)):'</td></tr>').'<!-- ###GROUPBYFOOTERFIELD_'.$fN.'### end -->'.$GROUPBYFIELDS;
				}
				$tab.="&nbsp;>&nbsp;";
	    }
			if ($conf['list.']['displayDirection']=='Down') $this->GROUPBYFIELDS.="</td></tr>";
			$GROUPBYFIELDS='<!-- ###GROUPBYFOOTERFIELDS### begin -->'.$GROUPBYFIELDS.'<!-- ###GROUPBYFOOTERFIELDS### end -->';
    }
		return $GROUPBYFIELDS;
	}
	
    /**
    * getListItemTemplate : generates template for  lists , grids and calendars ...
    *
    * @param	[array]		$conf: configuration array.
    * @return	[string]	$content : template to show.
    * 
    * ACTIONS : Basically Buttons or links :
    * - Top Actions
    * - Bottom Actions ( Same as top)
    * - Navigation Actions
    * - Element Actions	 ()
    *
    * MEDIA PLAYER
    */
    
    function getListItemTemplate(&$conf) {
    	if ($conf['general.']['listMode']==1) return $this->getCalendarTemplate($conf);
    	if ($conf['general.']['listMode']==2) return $this->getGridTemplate($conf);
     	// If template is set by user in flexform get it
     	if ($conf['list.']['itemTpl']) return '<!-- ###TEMPLATE_EDITMENU### begin -->'.$conf['list.']['itemTpl'].'<!-- ###TEMPLATE_EDITMENU### end -->';
    	$actions='###ACTIONS-LIST-ELEMENT###';
    	// Begin...
    	$tmp='<!-- ###TEMPLATE_EDITMENU### begin -->';
    	// TOP ACTIONS TAG	
    	$tmp.= '<table style="width:100%"><tr><td align="left" valign="top">###ACTIONS-LIST-TOP###</td></tr></table>';
    	
    	// TODO  We get Search Filter here (MUST BE REPLACED by tag !!!!!)....
    	// TODO Put all this in function getSearchFilter ..
        //-- Should be put in fe_adminLib
    	$cont = $conf['inputvar.']['advancedSearch'];
    	$recherche='';
    	if (is_array($conf['inputvar.']['advancedSearch'])) {
    		foreach ($conf['inputvar.']['advancedSearch'] as $key => $val) {
    			if($val) {
    			    
     			    $ftA=$this->metafeeditlib->getForeignTableFromField($key,$conf,'',array());               
   				    $recherche .= ($recherche?',<br />':'').$this->metafeeditlib->getLLFromLabel($ftA['fieldLabel'], $conf).':';
    				//$recherche .= ($recherche?', ':'').$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]['columns'][$key]['label'], $conf).':';
    				if (is_array($val)) {
    				    $recherche .= $val['op'].' '.$val['val'].' , '.$val['valsup'];    				    
    			    } else {
    				    $recherche .= $val;
    			    }
    			}
    		}
    	}
    	
    	$filter='<div id="blockfiltre">';
    	$filter2="";
    	if($conf['inputvar.']['advancedSearch']) $filter2.='<tr> <td class="searchf">'.$this->metafeeditlib->getLL("filtre_recherche",$conf).'<br />'.($recherche? $recherche : $this->metafeeditlib->getLL("search_nothing",$conf)).'</td></tr>';
    	if ($conf['inputvar.']['sortLetter']) $filter2.= '<tr><td class="searchf">'.$this->metafeeditlib->getLL("filtre_lettre",$conf).$conf['inputvar.']['sortLetter'].' </td></tr>';
    	if ($filter2) $filter.='<table>'.$filter2.'</table>';
    	$filter .= '</div>';
    	
    	//$tmp.=$filter;
    	//-- end filter ...
    	
    	$tmp.='<div'.$this->caller->pi_classParam('editmenu').'>'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-editmenu').'">'.$this->metafeeditlib->getLL("edit_menu_header",$conf).'</h1><div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-editmenu').'">'.$this->metafeeditlib->getLL("edit_menu_description",$conf).'</div>').' 
    	<div'.$this->caller->pi_classParam('error').'><!-- -->###EVAL_ERROR###</div>
    	<div'.$this->caller->pi_classParam('editmenu-list').'>'.($conf['list.']['searchBox']?$this->cObj->stdWrap($this->searchBox($conf),$conf['list.']['searchBox.']):'').($conf['list.']['alphabeticalSearch']?$this->cObj->stdWrap($this->alphabeticalSearch($conf),$conf['list.']['alphabeticalSearch.']):'').($conf['list.']['advancedSearch']?$this->cObj->stdWrap($this->advancedSearch($conf,$filter),$conf['list.']['advancedSearch.']):'').($conf['list.']['calendarSearch']?$this->cObj->stdWrap($this->calendarSearch(),$conf['list.']['calendarSearch.']):'');;
    
    	$tmp.='<table '.$this->caller->pi_classParam('editmenu-list-table').' style="width: 100%;">'.($conf['list.']['nbCols']?'':'<tr'.$this->caller->pi_classParam('editmenu-list-table-header').'>###ACTIONS-LIST-LIB###'.$this->getListFields($conf).'</tr>').'<!-- ###ALLITEMS### begin -->';
    	// Group By processing
    	$GROUPBYFIELDS=$this->getGroupByFields($conf);
    	if ($conf['list.']['displayDirection']=='Down') {
    		$tmp.=$GROUPBYFIELDS;
    		//MODIF CBY
    		$tmp.='<tr><!-- ###ITEM-COL### begin --><td style="width: '.floor(100/$conf['list.']['nbCols']).'%;" valign="top"><table style="width: 100%;"><tr><td>';
    		$tmp.='<!-- ###ITEM### begin --><tr><td '.$this->caller->pi_classParam('list-row-###LIST-ROW-ALT###').' style="width: '.floor(100/$conf['list.']['nbCols']).'%;"><!-- ###ITEM-EL### begin -->'.$this->getListDataFields($conf).'<!-- ###ITEM-EL### end -->'.$actions.'</td></tr><!-- ###ITEM### end -->';
    		$tmp.='</td></tr></table></td><!-- ###ITEM-COL### end --></tr>';
    	}
    	else
    	{
    		$tmp.=$GROUPBYFIELDS.'<!-- ###ITEM### begin -->'.($conf['list.']['nbCols']?'###OPENROW###<td '.$this->caller->pi_classParam('list-row-###LIST-ROW-ALT###').' style="width: '.floor(100/$conf['list.']['nbCols']).'%;"><!-- ###ITEM-EL### begin -->'.$this->getListDataFields($conf).'<!-- ###ITEM-EL### end -->'.$actions.'</td>###CLOSEROW###':'<tr '.$this->caller->pi_classParam('list-row-###LIST-ROW-ALT###').'>'.$actions.$this->getListDataFields($conf).'</tr>').'<!-- ###ITEM### end -->';
    	}
    	$GROUPBYFOOTERFIELDS=$this->getGroupByFooterFields($conf);
    	$tmp.=$GROUPBYFOOTERFIELDS.'<!-- ###ALLITEMS### end -->';
    
    	// Total processing 
    	if ($conf['list.']['sumFields']) {
    		$sum='<!--###SUM_FIELDS### begin---><tr>'.$this->getEditSumFields('SUM',$conf, $count,false, 'html').'</tr>';
    
    		//$tmp.='<tr>'.$this->getSumFields($conf, false, 'html').'</tr>'; //TODO Handle undisplayed sumfields ...
    		$sum.='<!--###SUM_FIELDS### end--->';
    		$sum=$this->cObj->substituteMarker($sum, '###FIRSTEMPTYCELL###','Total (###SUM_FIELD_metafeeditnbelts###)');
    		$tmp.=$sum;
    	}
    	// MEDIAPLAYER TAGS
    	$tmp.='</table>###MEDIAPLAYER###'.($conf['blog.']['showComments']?'###MEDIA_ACTION_BLOG###':'').'###PAGENAV###';
    	// BOTTOM ACTION TAGS
    	$tmp.= '</div><table style="width:100%"><tr><td align="left" valign="top">###ACTIONS-LIST-BOTTOM###</td></tr></table></div>';
    if ($conf['ajax.']['ajaxOn']) $tmp.= '<div id="modalWindow" class="jqmWindow">
        <div id="jqmTitle" class="jqmTitle jqDrag">
            <button class="jqmClose">
               X
            </button>
            <span id="jqmTitleText" class="jqmTitleText">Title of modal window</span>
        </div>
        <iframe id="jqmContent" class="jqmContent">
        </iframe>
        <img src="typo3conf/ext/meta_feedit/res/resize.gif" alt="resize" class="jqResize" />
    </div>'.'<div id="modalDelWindow" class="jqmWindow">
        <div id="jqmDelTitle" class="jqmTitle jqDrag">
            <button class="jqmClose">
               X
            </button>
            <span id="jqmDelTitleText" class="jqmTitleText" >Title of modal window</span>
        </div>
        <div id="jqmDelContent" class="jqmContent">
        </div>
        <img src="typo3conf/ext/meta_feedit/res/resize.gif" alt="resize" class="jqResize" />
    </div>'.
    '<div id="modalImgWindow" class="jqmWindow">
        <div id="jqmImgTitle" class="jqmTitle jqDrag">
        	<button class="jqmClose">X</button>
           <span id="jqmImgTitleText" class="jqmTitleText" >Title of image window</span>
        </div>
        <div id="jqmImgContent" class="jqmContent"></div>
        <img src="typo3conf/ext/meta_feedit/res/resize.gif" alt="resize" class="jqResize" />
    </div>';

    	$tmp.='<!-- ###TEMPLATE_EDITMENU### end -->';
    	return $tmp;
    }

    /**
    * getListNoItemTemplate : generates template for empty lists ...
    *
    * @param	[array]		$conf: configuration array.
    * @return	[string]	$content : template to show.
    */
	 
    function getListNoItemTemplate(&$conf) {
        $pluginId=$conf['pluginId'];
        if ($conf['list.']['noItemTpl']) return '<!-- ###TEMPLATE_EDITMENU_NOITEMS### begin -->'.$conf['list.']['noItemTpl'].'<!-- ###TEMPLATE_EDITMENU_NOITEMS### end -->';
        return '<!-- ###TEMPLATE_EDITMENU_NOITEMS### begin -->
            '.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-editmenu-noitems').'">'.$this->metafeeditlib->getLL("edit_menu_noitems_header",$conf).'</h1>').'
            <div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-editmenu-noitems').'">'.$this->metafeeditlib->getLL("edit_menu_noitems_description",$conf).'</div>
            '.($conf['disableCreate']?'':($conf['noitemcreate']?'<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-create').' '.$this->caller->pi_getClassName('link-editmenu-noitems').'"><div><a href="###FORM_URL###&amp;cmd['.$pluginId.']=create&amp;backURL['.$pluginId.']=###FORM_URL_ENC###%26cmd['.$pluginId.']=edit">'.$this->metafeeditlib->getLL("edit_menu_createnew_label",$conf).'</a></div></div>':'')).'
        <!-- ###TEMPLATE_EDITMENU_NOITEMS### -->';
    }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getGridTemplate(&$conf) {
  if ($conf['grid.']['itemTpl']) return '<!-- ###TEMPLATE_GRID### begin -->'.$conf['grid.']['itemTpl'].'<!-- ###TEMPLATE_GRID### end -->';
	$actions='###ACTIONS-LIST-ELEMENT###';
	$tmp='<!-- ###TEMPLATE_GRID### begin -->';
	$tmp.='<div'.$this->caller->pi_classParam('form-wrap').'>';
	$tmp.='<form name="'.$conf['table'].'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" onsubmit="'.implode(';', $this->additionalJS_submit).'">';
	$tmp.=($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-grid').'">'.$this->metafeeditlib->getLL("grid_header",$conf).'</h1><div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-grid').'">'.$this->metafeeditlib->getLL("edit_grid_description",$conf).'</div>').'
	<div'.$this->caller->pi_classParam('error').'><!-- -->###EVAL_ERROR###</div>
	<div'.$this->caller->pi_classParam('grid').'>';
	
	$tmp.= '<table style="width:100%"><tr><td align="left">###ACTIONS-GRID-TOP###</td></tr></table>';
	$tmp.='<!-- ###GRID### begin --><table '.$this->caller->pi_classParam('grid-table').' style="width: 100%;">'; 
	// MODIF CBY GRID-ROW-ALT
  	$tmp.='<!-- ###GRID-ROW### begin --><tr '.$this->caller->pi_classParam('grid-row-alt-###GRID-ROW-ALT###').'><td '.$this->caller->pi_classParam('grid-table-row-el').'>###ROWLABEL###</td>';
	$tmp.='<!-- ###GRID-ITEM### begin --><td '.$this->caller->pi_classParam('grid-table-el').'><!-- ###GRID-EL### begin -->'.$this->getGridDataFields($conf).' ###HIDDENCELLFIELDS### ###ACTIONS-GRID-EL###<!-- ###GRID-EL### begin --></td><!-- ###GRID-ITEM### end -->';
	$tmp.='</tr><!-- ###GRID-ROW### end -->';
	$tmp.='</table><!-- ###GRID### end -->';
	$tmp.='</div>';
	$tmp.= '<table style="width:100%"><tr><td align="left"><input type="hidden" name="no_cache" value="1"><input type="hidden" id="tx_metafeedit_exporttype" name="tx_metafeedit[exporttype]" value="">###HIDDENFIELDS### ###ACTIONS-GRID-BOTTOM###</td></tr></table>';
	$tmp.='</form></div><!-- ###TEMPLATE_GRID### end -->';
	return $tmp;
}

/**
 * [Describe function...]
 *
 * @param	[type]		$$conf: ...
 * @return	[type]		...
 */
 
function getCalendarTemplate(&$conf) {
  if ($conf['cal.']['itemTpl']) return '<!-- ###TEMPLATE_CALENDAR### begin -->'.$conf['cal.']['itemTpl'].'<!-- ###TEMPLATE_CALENDAR### end -->';
	$actions='###ACTIONS-CALENDAR-ELEMENT###';
	$tmp='<!-- ###TEMPLATE_CALENDAR### begin -->';
	$tmp.=($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-calendar').'">'.$this->metafeeditlib->getLL("edit_calendar_header",$conf).'</h1><div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-grid').'">'.$this->metafeeditlib->getLL("edit_grid_description",$conf).'</div>').'
	<div'.$this->caller->pi_classParam('editmenu-calendar').'>';
	$tmp.='<!-- ###WEEKDIV### begin --><div'.$this->caller->pi_classParam('cal-week').'>';
			$tmp.='<!-- ###HOURLIBDIV### begin --><div'.$this->caller->pi_classParam('cal-hourlib-###NATTR###').'>###HOURLIB###';
			$tmp.='</div><!-- ###HOURLIBDIV### end -->';
		$tmp.='<!-- ###DAYDIV### begin --><div'.$this->caller->pi_classParam('cal-day').'><div class="cal-day-title">###DAY###</div>';
			$tmp.='<!-- ###HOURDIV### begin --><div'.$this->caller->pi_classParam('cal-hour-###NATTR###').'>###HOUR###';
			$tmp.='</div><!-- ###HOURDIV### end -->';
		$tmp.='</div><!-- ###DAYDIV### end -->';
	$tmp.='</div><!-- ###WEEKDIV### end -->';
	$tmp.='<!-- ###CATCTNRDIV### begin --><div'.$this->caller->pi_classParam('cal-catctnr').'><div '.$this->caller->pi_classParam('cal-cat-title').'>###CATTITLE###</div>';
	$tmp.='<!-- ###CATDIV### begin --><div id="txmfedtcalcat-###NCAT###" class="txmfedtccat '.$this->caller->pi_getClassName('cal-cat-###NCAT###').'"><div class="mfedtcalcatimg">&nbsp;</div>###CALCAT###';
	$tmp.='</div><!-- ###CATDIV### end -->';
	$tmp.='</div><!-- ###CATCTNRDIV### end -->';
	$tmp.='</div>';
	$tmp.='<!-- ###TEMPLATE_CALENDAR### end -->';
	return $tmp;
}

    /**
    * getEditTemplate : Generates edit screen template
    *
    * @param    array		$conf: configuration array.
    * @return   string		$tmpl : $html template
    */
     
    function getEditTemplate(&$conf) {
        if(!$conf['disableEdit']) $this->HTMLFormEdit = $this->makeHTMLForm('edit',$conf);
        if($conf['edit.']['preview'] || $conf['blogData']) $this->HTMLPreviewEdit = $this->makeHTMLPreview('edit',$conf);        
        $tmpl= $this->getEditScreenTemplate($conf);
        $tmpl.= $this->getEditPreviewTemplate($conf);
        $tmpl.= $this->getEditSavedTemplate($conf);
        return $tmpl;
    }

	/**
	 * getEditScreenTemplate :
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getEditScreenTemplate(&$conf) {
  if ($conf['edit.']['screenTpl']) return '<!-- ###TEMPLATE_EDIT### begin -->'.$conf['edit.']['screenTpl'].'<!-- ###TEMPLATE_EDIT### end-->';
    $tmp= '<!-- ###TEMPLATE_EDIT### begin -->'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-edit').'">'.$this->metafeeditlib->getLL("edit_header_prefix",$conf).' "###FIELD_'.strtolower($this->id_field).'###"</h1>').'
	'.($conf['text_in_top_of_form']?'<div'.$this->caller->pi_classParam('form-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_form'],$conf['text_in_top_of_form.']).'</div>':'').'
	<div'.$this->caller->pi_classParam('error').'><!-- -->###EVAL_ERROR###</div>
	<div'.$this->caller->pi_classParam('form-wrap').'>
	<form name="'.$this->table.'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" onsubmit="'.implode(';', $this->additionalJS_submit).'">
	'.$this->HTMLFormEdit.'
	<div'.$this->caller->pi_classParam('form-row').'>
	###HIDDENFIELDS###
	###ACTION-SAVE###
	</div>
	</form>';
	$tmp.=$this->metafeeditlib->getEditActions($conf,$this);
	$tmp.='</div>';
  // Why was this deactivated ?
  if ($conf['blog.']['showComments']) $tmp.=$this->getBlogTemplate($conf);
	$tmp.='<!-- ###TEMPLATE_EDIT### end-->';
  return $tmp;
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getEditPreviewTemplate(&$conf) {
  $pluginId=$conf['pluginId'];
  if ($conf['edit.']['previewTpl']) return '<!-- ###TEMPLATE_EDIT_PREVIEW### begin -->'.$conf['edit.']['previewTpl'].'<!-- ###TEMPLATE_EDIT_PREVIEW### end-->';
  	$tmp='<!-- ###TEMPLATE_EDIT_PREVIEW### begin -->';
	$t2='
	'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-edit-preview').'">'.$this->metafeeditlib->getLL("edit_header_prefix",$conf).' "###FIELD_'.strtolower($this->id_field).'###"</h1>').'
	'.($conf['text_in_top_of_preview']?'<div'.$this->caller->pi_classParam('preview-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_preview'],$conf['text_in_top_of_preview.']).'</div>':'').'
	<div'.$this->caller->pi_classParam('preview-wrap').'>
	<form name="'.$this->table.'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'">
	'.$this->HTMLPreviewEdit.'
	<div'.$this->caller->pi_classParam('preview-row').'>
		###HIDDENFIELDS###
		<!-- ###PREVIEWACTIONS### begin -->
			'.(!$conf['disableEdit']?'
		<input type="submit" name="doNotSave['.$pluginId.']" value="'.$this->metafeeditlib->getLL("edit_preview_donotsave_label",$conf).'"'.$this->caller->pi_classParam('preview-donotsave').' />
		<input type="submit" name="submit['.$pluginId.']" value="'.$this->metafeeditlib->getLL("edit_preview_submit_label",$conf).'"'.$this->caller->pi_classParam('preview-submit').' />
		':'
		<table style="width:100%"><tr><td align="left">'.($conf['no_action']?'':'<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></td><td align="right"><div class="'.$this->caller->pi_getClassName('actions').' '.$this->caller->pi_getClassName('preview-actions').'">'.$conf['actions.']['useractions'].'</div>').'</td></tr></table>
	').'<!-- ###PREVIEWACTIONS### end -->
	</div>
	</form>
	</div>';
	$tmp.=$this->cObj->stdWrap($t2,$conf['previewWrap.']);
  if ($conf['blog.']['showComments']) $tmp.=$this->getBlogTemplate($conf);
	$tmp.='<!-- ###TEMPLATE_EDIT_PREVIEW### end-->';
  return $tmp;
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getEditSavedTemplate(&$conf) {
  if ($conf['edit.']['savedTpl']) return '<!-- ###TEMPLATE_EDIT_SAVED### begin-->'.$conf['edit.']['savedTpl'].'<!-- ###TEMPLATE_EDIT_SAVED### end-->';
	return '<!-- ###TEMPLATE_EDIT_SAVED### begin-->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-edit-saved').'">'.$this->metafeeditlib->getLL("edit_saved_header",$conf).'</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-edit-saved').'">'.$this->metafeeditlib->getLL("edit_saved_message",$conf).'</div>
<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-edit-saved').'"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></div>
<!-- ###TEMPLATE_EDIT_SAVED### end-->
';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getBlogTemplate(&$conf) {
  	$pluginId=$conf['pluginId'];
  	if ($conf['blog.']['blogtemplate']) return '<!-- ###TEMPLATE_BLOG### begin -->'.$conf['blog.']['blogtemplate'].'<!-- ###TEMPLATE_BLOG### end-->';
  	$tmp='<!-- ###TEMPLATE_BLOG### begin -->';
		$t2=($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-blog').'">'.$this->metafeeditlib->getLL("blog_header",$conf).' "###FIELD_'.strtolower($this->id_field).'###"</h1>').'
		'.($conf['text_in_top_of_blog']?'<div'.$this->caller->pi_classParam('blog-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_blog'],$conf['text_in_top_of_blog.']).'</div>':'').'
		<div'.$this->caller->pi_classParam('blog-wrap').'><div'.$this->caller->pi_classParam('error').'>###EVAL_BLOG_ERROR###</div><table><!-- ###BLOG-COMMENTS### begin --><!-- ###BLOG-COMMENT### begin -->';
		$t2.=$this->getBlogDataFields($conf).'<!-- ###BLOG-COMMENT### end --><!-- ###BLOG-COMMENTS### end --></table>';
		if ($conf['blog.']['allowComments']) $t2.='<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-blog').'">'.$this->metafeeditlib->getLL("blog_new_comment",$conf).'</h1><div'.$this->caller->pi_classParam('blog-comment-submit-form').'><form name="tx_metafeedit_comments_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'">
		'.$this->getBlogFormFields($conf).($conf['blog.']['captcha']?'<div'.$this->caller->pi_classParam('error').'>###EVAL_BLOG_ERROR###</div>
		<!--###CAPTCHA_INSERT### this subpart is removed if CAPTCHA is not enabled! -->
		<div class="'.$this->caller->pi_getClassName('captcha').'">
		<label for="'.$this->prefixId.'-captcha_response">###SR_FREECAP_NOTICE###</label>
		###SR_FREECAP_CANT_READ###
		<br />
		<input type="text" size="15" id="'.$this->prefixId.'-captcha_response" name="'.$this->prefixId.'[captcha_response]" title="###SR_FREECAP_NOTICE###" value="" />
		###SR_FREECAP_IMAGE### ###SR_FREECAP_ACCESSIBLE###
		</div>
		<!--###CAPTCHA_INSERT###-->':'').'<div'.$this->caller->pi_classParam('blog-row').'><input type="hidden" name="cmd['.$pluginId.']" value="edit" /><input type="hidden" name="blog['.$pluginId.']" value="1" />
		<input type="hidden" name="rU['.$pluginId.']" value="###FIELD_uid###" />
		<input type="submit" name="submit['.$pluginId.']" value="'.$this->metafeeditlib->getLL("blog_submit_label",$conf).'"'.$this->caller->pi_classParam('blog-submit').' />
		</div></form></div>';
	$t2.=$conf['no_action']?'':'<table style="width:100%"><tr><td align="left"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></td><td align="right"><div class="'.$this->caller->pi_getClassName('actions').' '.$this->caller->pi_getClassName('preview-actions').'">'.$conf['actions.']['useractions'].'</div>';
	$t2.='</td></tr></table></div>';
	$tmp.=$this->cObj->stdWrap($t2,$conf['blogWrap.']);
	$tmp.='<!-- ###TEMPLATE_BLOG### end-->';
  return $tmp;
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getCreateTemplate(&$conf) {
    $this->HTMLFormCreate = $this->makeHTMLForm('create',$conf);
    $this->HTMLPreviewCreate = $this->makeHTMLPreview('create',$conf);
    return $this->getCreateSavedTemplate($conf).$this->getCreateScreenLoginTemplate($conf).$this->getCreatePreviewLoginTemplate($conf).$this->getCreateScreenTemplate($conf).$this->getCreatePreviewTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getCreateScreenLoginTemplate(&$conf) {
  	$pluginId=$conf['pluginId'];
    if ($conf['create.']['screenLoginTpl']) return '<!-- ###TEMPLATE_CREATE_LOGIN### -->'.$conf['create.']['screenLoginTpl'].'<!-- ###TEMPLATE_CREATE_LOGIN### end-->';
	return '<!-- ###TEMPLATE_CREATE_LOGIN### -->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-create-login').'">'.$this->metafeeditlib->getLL("create_header_prefix",$conf).' '.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]["ctrl"]["title"],$conf).'</h1>').'
'.($conf['text_in_top_of_form']?'<div'.$this->caller->pi_classParam('form-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_form'],$conf['text_in_top_of_form.']).'</div>':'').'
<div'.$this->caller->pi_classParam('form-wrap').'>
<form name="'.$this->table.'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" onsubmit="'.implode(';', $this->additionalJS_submit).'">
<div'.$this->caller->pi_classParam('error').'><!-- -->###EVAL_ERROR###</div>
'.$this->HTMLFormCreate.'
<div'.$this->caller->pi_classParam('form-row').'>
   ###HIDDENFIELDS###
   <input type="submit" name="submit['.$pluginId.']" value="'.($conf['create.']['preview']?$this->metafeeditlib->getLL("create_submit_label",$conf):$this->metafeeditlib->getLL("create_preview_submit_label",$conf)).'"'.$this->caller->pi_classParam('form-submit').' />
</div>
</form>

</div>
'.($conf['ajax.']['ajaxOn']?'':'<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-create-login').'"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></div>').'
<!-- ###TEMPLATE_CREATE_LOGIN### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getCreatePreviewLoginTemplate(&$conf) {
  	$pluginId=$conf['pluginId'];
    if ($conf['create.']['previewLoginTpl']) return '<!-- ###TEMPLATE_CREATE_LOGIN_PREVIEW### begin-->'.$conf['create.']['previewLoginTpl'].'<!-- ###TEMPLATE_CREATE_LOGIN_PREVIEW### end-->';
	return '<!-- ###TEMPLATE_CREATE_LOGIN_PREVIEW### begin-->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-create-login-preview').'">'.$this->metafeeditlib->getLL("create_header_prefix",$conf).' '.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]["ctrl"]["title"],$conf).'</h1>').'
'.($conf['text_in_top_of_preview']?'<div'.$this->caller->pi_classParam('preview-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_preview'],$conf['text_in_top_of_preview.']).'</div>':'').'
<div'.$this->caller->pi_classParam('preview-wrap').'>
<form name="'.$this->table.'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'">
'.$this->HTMLPreviewCreate.'
<div'.$this->caller->pi_classParam('preview-row').'>
    ###HIDDENFIELDS###
    <input type="submit" name="doNotSave['.$pluginId.']" value="'.$this->metafeeditlib->getLL("create_preview_donotsave_label",$conf).'"'.$this->caller->pi_classParam('preview-donotsave').' />
    <input type="submit" name="submit['.$pluginId.']" value="'.$this->metafeeditlib->getLL("create_preview_submit_label",$conf).'"'.$this->caller->pi_classParam('preview-submit').' />
	<table style="width:100%"><tr><td align="left"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></td><td align="right"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-edit').'"></div></td></tr></table>
</div>
</form>
</div>
<!-- ###TEMPLATE_CREATE_LOGIN_PREVIEW### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getCreateScreenTemplate(&$conf) {
  	$pluginId=$conf['pluginId'];
    if ($conf['create.']['screenTpl']) return '<!-- ###TEMPLATE_CREATE### begin -->'.$conf['create.']['screenTpl'].'<!-- ###TEMPLATE_CREATE### end-->';
	return '<!-- ###TEMPLATE_CREATE### begin -->
	'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-create').'">'.$this->metafeeditlib->getLL("create_header_prefix",$conf).' '.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]["ctrl"]["title"],$conf).'</h1>').'
	'.($conf['text_in_top_of_form']?'<div'.$this->caller->pi_classParam('form-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_form'],$conf['text_in_top_of_form.']).'</div>':'').'
	<div'.$this->caller->pi_classParam('form-wrap').'>
	<form name="'.$this->table.'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" onsubmit="'.implode(';', $this->additionalJS_submit).'">
	'.$this->HTMLFormCreate.'
	<div'.$this->caller->pi_classParam('form-row').'>
	###HIDDENFIELDS###
	<input type="submit" name="submit['.$pluginId.']" value="'.($conf['create.']['preview']?$this->metafeeditlib->getLL("create_submit_label",$conf):$this->metafeeditlib->getLL("create_preview_submit_label",$conf)).'"'.$this->caller->pi_classParam('form-submit').' />
	'.($conf['ajax.']['ajaxOn']?'':'<table style="width:100%"><tr><td align="left"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></td><td align="right"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-edit').'"></div></td></tr></table>').'
	</div>
	</form>
	</div>
	</div>
	<!-- ###TEMPLATE_CREATE### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getCreatePreviewTemplate(&$conf) {
  	$pluginID=$conf['pluginId'];
    if ($conf['create.']['previewTpl']) return '<!-- ###TEMPLATE_CREATE_PREVIEW### begin-->'.$conf['create.']['previewTpl'].'<!-- ###TEMPLATE_CREATE_PREVIEW### end-->';
	return '<!-- ###TEMPLATE_CREATE_PREVIEW### begin-->
	'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-create-preview').'">'.$this->metafeeditlib->getLL("create_header_prefix",$conf).' '.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]["ctrl"]["title"],$conf).'</h1>').'
	'.($conf['text_in_top_of_preview']?'<div'.$this->caller->pi_classParam('preview-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_preview'],$conf['text_in_top_of_preview.']).'</div>':'').'
	<div'.$this->caller->pi_classParam('preview-wrap').'>
	<form name="'.$this->table.'_form" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'">
	'.$this->HTMLPreviewCreate.'
	<div'.$this->caller->pi_classParam('preview-row').'>
		###HIDDENFIELDS###
		<input type="submit" name="doNotSave['.$pluginId.']" value="'.$this->metafeeditlib->getLL("create_preview_donotsave_label",$conf).'"'.$this->caller->pi_classParam('preview-donotsave').' />
		<input type="submit" name="submit['.$pluginId.']" value="'.$this->metafeeditlib->getLL("create_preview_submit_label",$conf).'"'.$this->caller->pi_classParam('preview-submit').' />
	</div>
	</form>
	</div>
	<!-- ###TEMPLATE_CREATE_PREVIEW### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getCreateSavedTemplate(&$conf) {
    if ($conf['create.']['savedTpl']) return '<!-- ###TEMPLATE_CREATE_SAVED### begin-->'.$conf['create.']['savedTpl'].'<!-- ###TEMPLATE_CREATE_SAVED### end-->';
	return '<!-- ###TEMPLATE_CREATE_SAVED### begin-->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-create-saved').'">'.$this->metafeeditlib->getLL("create_saved_header",$conf).'</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-create-saved').'">'.$this->metafeeditlib->getLL("create_saved_message",$conf).'</div>
<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-create-saved').'"><div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></div>
<!-- ###TEMPLATE_CREATE_SAVED### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getDeleteTemplate(&$conf) {
    return $this->getDeletePreviewTemplate($conf).$this->getDeleteSavedTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getDeletePreviewTemplate(&$conf) {
  	$pluginId=$conf['pluginId'];
    if ($conf['delete.']['previewTpl']) return '<!-- ###TEMPLATE_DELETE_PREVIEW### begin-->'.$conf['delete.']['previewTpl'].'<!-- ###TEMPLATE_DELETE_PREVIEW### end-->';
	return '<!-- ###TEMPLATE_DELETE_PREVIEW### begin-->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-delete-preview').'">'.$this->metafeeditlib->getLL("delete_preview_header",$conf).'</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-delete-preview').'">'.$this->metafeeditlib->getLL("delete_preview_message",$conf).'</div>
<div class="'.$this->caller->pi_getClassName('link-preview').' '.$this->caller->pi_getClassName('link-delete-preview').'">
<div  class="jqmClose '.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-delete-ok').'"><a href="###FORM_URL###&amp;cmd['.$pluginId.']=delete&amp;rU['.$pluginId.']=###REC_UID###&amp;backURL['.$pluginId.']=###BACK_URL_ENC###">'.$this->metafeeditlib->getLL("delete_preview_delete_label",$conf).'</a></div>
<div  class="jqmClose '.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-delete-ko').'"><a href="###BACK_URL_HSC###&amp;backURL['.$pluginId.']=###BACK_URL_ENC###" >'.$this->metafeeditlib->getLL("delete_preview_dont_delete_label",$conf).'</a></div>
</div>
<!-- ###TEMPLATE_DELETE_PREVIEW### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getDeleteSavedTemplate(&$conf) {
    if ($conf['delete.']['savedTpl']) return '<!-- ###TEMPLATE_DELETE_SAVED### begin-->'.$conf['delete.']['savedTpl'].'<!-- ###TEMPLATE_DELETE_SAVED### end-->';
	return '<!-- ###TEMPLATE_DELETE_SAVED### begin-->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-delete-saved').'">'.$this->metafeeditlib->getLL("delete_saved_header",$conf).'</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-delete-saved').'">'.$this->metafeeditlib->getLL("delete_saved_message",$conf).'</div>
<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('link-delete-saved').'"><div  class="'.$this->caller->pi_getClassName('link-back').'"><a title="'.$this->metafeeditlib->getLL("back_label",$conf).'" href="###BACK_URL_HSC###">'.$this->metafeeditlib->getLL("back_label",$conf).'</a></div></div>
<!-- ###TEMPLATE_DELETE_SAVED### end-->';
  }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getSetfixedTemplate(&$conf) {
	return $this->getSetfixedOkTemplate($conf).$this->getSetfixedOkDeleteTemplate($conf).$this->getSetfixedFailedTemplate($conf);
 }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getSetfixedOkTemplate(&$conf) {
    if ($conf['setfixed.']['setFixedOkTemplate']) return '<!-- ###TEMPLATE_SETFIXED_OK### -->'.$conf['setfixed.']['setFixedOkTemplate'].'<!-- ###TEMPLATE_SETFIXED_OK### end-->';
    return '
<!-- ###TEMPLATE_SETFIXED_OK### -->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-setfixed-ok').'">Setfixed succeeded</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-setfixed-ok').'">Record uid; ###FIELD_uid###</div>
<!-- ###TEMPLATE_SETFIXED_OK### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getSetfixedOkDeleteTemplate(&$conf) {
    if ($conf['setfixed.']['setFixedOkDeleteTemplate']) return '<!-- ###TEMPLATE_SETFIXED_OK_DELETE### -->'.$conf['setfixed.']['setFixedOkDeleteTemplate'].'<!-- ###TEMPLATE_SETFIXED_OK_DELETE### end-->';
 return '<!-- ###TEMPLATE_SETFIXED_OK_DELETE### -->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-setfixed-ok-delete').'">Setfixed delete record "###FIELD_uid###"</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-setfixed-ok').'">Record uid; ###FIELD_uid###</div>
<!-- ###TEMPLATE_SETFIXED_OK_DELETE### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getSetfixedFailedTemplate(&$conf) {
    if ($conf['setfixed.']['setFixedFailedTemplate']) return '<!-- ###TEMPLATE_SETFIXED_FAILED### -->'.$conf['setfixed.']['setFixedFailedTemplate'].'<!-- ###TEMPLATE_SETFIXED_FAILED### end-->';
	return '<!-- ###TEMPLATE_SETFIXED_FAILED### -->
'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-setfixed-failed').'">Setfixed failed!</h1>').'
<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-setfixed-failed').'">May happen if you click the setfixed link a second time (if the record has changed since the setfixed link was generated this error will happen!)</div>
<!-- ###TEMPLATE_SETFIXED_FAILED### end-->
';
  }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEmailTemplate(&$conf) {
    return $this->getCreateEmailTemplate($conf).$this->getEditEmailTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateEmailTemplate(&$conf) {
    return $this->getCreateUserEmailTemplate($conf).$this->getCreateAdminNotifyEmailTemplate($conf).$this->getCreateDataNotifyEmailTemplate($conf).$this->getCreateAdminEmailTemplate($conf).$this->getCreateSetFixedEmailTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateUserEmailTemplate(&$conf) {
    if ($conf['create.']['userNotifyTpl']) return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED### begin -->'.$conf['create.']['userNotifyTpl'].'<!-- ###EMAIL_TEMPLATE_CREATE_SAVED### end-->';
	return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED### begin -->
[Auto Generated Message] Your information has been saved.

        <!--###SUB_RECORD###-->
        You have submitted the following informations at '.t3lib_div::getIndpEnv('TYPO3_SITE_URL').':'.chr(10).'
        '.$this->makeTEXTPreview('all',$conf).'
        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_CREATE_SAVED### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateAdminNotifyEmailTemplate(&$conf) {
    if ($conf['create.']['adminNotifyTpl']) return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN_NOTIFY### begin -->'.$conf['create.']['adminNotifyTpl'].'<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN_NOTIFY### end-->';
	return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN_NOTIFY### begin -->
[Auto Generated Message] New record created.

        <!--###SUB_RECORD###-->
        '.$this->makeTEXTPreview('all',$conf).'

        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN_NOTIFY### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateDataNotifyEmailTemplate(&$conf) {
    if ($conf['create.']['dataNotifyTpl']) return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-DATA### begin -->'.$conf['create.']['dataNotifyTpl'].'<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-DATA### end-->';
	return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-DATA### begin -->
[Auto Generated Message] You have been invited.

        <!--###SUB_RECORD###-->
        '.$this->makeTEXTPreview('all',$conf).'

        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-DATA### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateAdminEmailTemplate(&$conf) {
    if ($conf['create.']['adminValidTpl']) return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN### begin -->'.$conf['create.']['adminValidTpl'].'<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN### end-->';
	return '<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN### begin -->
[Auto Generated Message] New record created.

        <!--###SUB_RECORD###-->
        '.$this->makeTEXTPreview('all',$conf).'

        Approve:
        ###THIS_URL######FORM_URL######SYS_SETFIXED_approve###

        Delete:
        ###THIS_URL######FORM_URL######SYS_SETFIXED_DELETE###
        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_CREATE_SAVED-ADMIN### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateSetFixedEmailTemplate(&$conf) {
    return $this->getCreateSetFixedKOEmailTemplate($conf).$this->getCreateSetFixedOKEmailTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateSetFixedKOEmailTemplate(&$conf) {
if ($conf['create.']['userNotifyKoTpl']) return '<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### begin -->'.$conf['create.']['userNotifyKoTpl'].'<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### end -->';
	return '<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### begin -->
Consultancy DELETED!

<!--###SUB_RECORD###-->
Record name: ###FIELD_'.$this->id_field.'###

Your entry has been deleted by the admin for some reason.

- kind regards.
<!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### end -->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getCreateSetFixedOKEmailTemplate(&$conf) {
    if ($conf['create.']['userNotifyOkTpl']) return '<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### begin -->'.$conf['create.']['userNotifyOkTpl'].'<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### end -->';
	return '<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### begin -->
Update approved

<!--###SUB_RECORD###-->

Record name: ###FIELD_'.$this->id_field.'###

Your entry has been approved!

- kind regards.
<!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### end -->
';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditEmailTemplate(&$conf) {
        return $this->getEditUserEmailTemplate($conf).$this->getEditAdminNotifyEmailTemplate($conf).$this->getEditDataNotifyEmailTemplate($conf).$this->getEditAdminEmailTemplate($conf).$this->getEditSetFixedEmailTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditUserEmailTemplate(&$conf) {
    if ($conf['edit.']['userNotifyTpl']) return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED### begin -->'.$conf['edit.']['userNotifyTpl'].'<!-- ###EMAIL_TEMPLATE_EDIT_SAVED### end-->';
	return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED### begin -->
[Auto Generated Message] Your information has been saved.

        <!--###SUB_RECORD###-->
        You have submitted the following information at '.t3lib_div::getIndpEnv('TYPO3_SITE_URL').':'.chr(10).'
        '.$this->makeTEXTPreview('all',$conf).'
        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_EDIT_SAVED### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditAdminNotifyEmailTemplate(&$conf) {
    if ($conf['edit.']['adminNotifyTpl']) return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN_NOTIFY### begin -->'.$conf['edit.']['adminNotifyTpl'].'<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN_NOTIFY### end-->';
	return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN_NOTIFY### begin -->
[Auto Generated Message] Consultancy record edited.

        <!--###SUB_RECORD###-->
        '.$this->makeTEXTPreview('all',$conf).'

        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN_NOTIFY### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditDataNotifyEmailTemplate(&$conf) {
    if ($conf['edit.']['dataNotifyTpl']) return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-DATA### begin -->'.$conf['edit.']['dataNotifyTpl'].'<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-DATA### end-->';
	return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-DATA### begin -->
[Auto Generated Message] Consultancy record edited.

        <!--###SUB_RECORD###-->
        '.$this->makeTEXTPreview('all',$conf).'

        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-DATA### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditAdminEmailTemplate(&$conf) {
    if ($conf['edit.']['adminValidTpl']) return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN### begin -->'.$conf['edit.']['adminValidTpl'].'<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN### end-->';
	return '<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN### begin -->
[Auto Generated Message] Consultancy record edited.

        <!--###SUB_RECORD###-->
        '.$this->makeTEXTPreview('all',$conf).'

        Approve:
        ###THIS_URL######FORM_URL######SYS_SETFIXED_approve###

        Delete:
        ###THIS_URL######FORM_URL######SYS_SETFIXED_DELETE###
        <!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_EDIT_SAVED-ADMIN### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditSetFixedEmailTemplate(&$conf) {
    return $this->getEditSetFixedKOEmailTemplate($conf).$this->getEditSetFixedOKEmailTemplate($conf);
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditSetFixedKOEmailTemplate(&$conf) {
    if ($conf['edit.']['userNotifyKoTpl']) return '<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### begin -->'.$conf['edit.']['userNotifyKoTpl'].'<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### end -->';
	return '<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### begin -->
Consultancy DELETED!

<!--###SUB_RECORD###-->
Record name: ###FIELD_'.$this->id_field.'###

Your entry has been deleted by the admin for some reason.

- kind regards.
<!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_SETFIXED_DELETE### end -->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getEditSetFixedOKEmailTemplate(&$conf) {
    if ($conf['edit.']['userNotifyOkTpl']) return '<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### begin -->'.$conf['edit.']['userNotifyOkTpl'].'<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### end -->';
	return '<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### begin -->
Consultancy approved

<!--###SUB_RECORD###-->

Record name: ###FIELD_'.$this->id_field.'###

Your consultancy entry has been approved!

- kind regards.
<!--###SUB_RECORD###-->
<!-- ###EMAIL_TEMPLATE_SETFIXED_approve### end -->';
}


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getRequiredTemplate(&$conf) {
    return $this->getRequiredAuthTemplate($conf).$this->getRequiredNoPermTemplate($conf);
  }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getRequiredAuthTemplate(&$conf) {
    if ($conf['general.']['authTpl']) return '<!-- ###TEMPLATE_AUTH### begin -->'.$conf['general.']['authTpl'].'<!-- ###TEMPLATE_AUTH### end-->';
	return '<!-- ###TEMPLATE_AUTH### begin -->
	'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-auth').'">Authentification failed</h1>').'
	<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-auth').'">For some reason the authentication failed. </div>
	<!-- ###TEMPLATE_AUTH### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getRequiredNoPermTemplate(&$conf) {
    if ($conf['general.']['noPermTpl']) return '<!-- ###TEMPLATE_NO_PERMISSIONS### begin -->'.$conf['general.']['noPermTpl'].'<!-- ###TEMPLATE_NO_PERMISSIONS### end-->';
	return '<!-- ###TEMPLATE_NO_PERMISSIONS### begin -->
	'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-no-permissions').'">No permissions to edit record</h3>').'
	<div class="'.$this->caller->pi_getClassName('message').' '.$this->caller->pi_getClassName('message-no-permissions').'">Sorry, you did not have permissions to edit the record.</div>
	<!-- ###TEMPLATE_NO_PERMISSIONS### end-->';
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
function getMediaPlayerTemplate(&$conf) {
   return '<!-- ###TEMPLATE_MEDIAPLAYER### begin -->'.$conf['edit.']['mediaPlayerTpl'].'<!-- ###TEMPLATE_MEDIAPLAYER### end-->';
    $tmp= '<!-- ###TEMPLATE_MEDIAPLAYER### begin -->'.($conf['no_header']?'':'<h1 class="'.$this->caller->pi_getClassName('header').' '.$this->caller->pi_getClassName('header-edit').'">'.$this->metafeeditlib->getLL("edit_header_prefix",$conf).' "###FIELD_'.strtolower($this->id_field).'###"</h1>').'
	'.($conf['text_in_top_of_form']?'<div'.$this->caller->pi_classParam('form-text').'>'.$this->cObj->stdWrap($conf['text_in_top_of_form'],$conf['text_in_top_of_form.']).'</div>':'').'
	<div'.$this->caller->pi_classParam('error').'><!-- -->###EVAL_ERROR###</div>
	<div'.$this->caller->pi_classParam('mediaplayer-wrap').'>
	<div'.$this->caller->pi_classParam('mediaplayer').'>
	###MEDIAPLAYER###
	</div>
	<table style="width:100%"><tr><td align="left">###ACTION-BACK###</td></tr></table>
	</div>
	<!-- ###TEMPLATE_MEDIAPLAYER### end-->';
  return $tmp;
}


 //----------------------------------------------------------------------------------------------------------------------------------------------------------//
  
function getPDFTemplate(&$conf)
{
	$pluginId=$conf['pluginId'];
  if ($conf['list.']['TemplatePDF']) return '<!-- ###TEMPLATE_EDITMENU_PDF### begin -->'.$conf['list.']['TemplatePDF'].'<!-- ###TEMPLATE_EDITMENU_PDF### end -->';
	$tmp='<!-- ###TEMPLATE_EDITMENU_PDF### begin --><?xml version="1.0" encoding="utf-8"?><table><tr>';
	$tmp.=($conf['list.']['nbCols']?'':$this->getListFields($conf,true,'html')).'</tr><!-- ###ALLITEMS### begin -->';
	$GROUPBYFIELDS=$this->getGroupByFields($conf,true,'PDF');
	if ($conf['list.']['displayDirection']=='Down') {
		$tmp.=$GROUPBYFIELDS;
		$tmp.='<!-- ###ITEM-COL### begin -->';
		$sum=$this->getEditSumFields('SUM',$conf,$count,true,'html');
		$sum=$this->cObj->substituteMarker($sum, '###FIRSTEMPTYCELL###','Total (###SUM_FIELD_metafeeditnbelts###)');
		$tmp.='<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin --><tr>'.$this->getListDataFields($conf,true,'html').$sum.'</tr><!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';		
		$tmp.='<!-- ###ITEM-COL### end -->';
	}
	else		
	{
		$tmp.=$GROUPBYFIELDS.'<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin --><tr>'.$this->getListDataFields($conf,true,'html').'</tr><!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';
	}
	
	if ($conf['list.']['sumFields'])
	{
		$sum=$this->getEditSumFields('SUM',$conf,$count,true,'PDF');
		$sum=$this->cObj->substituteMarker($sum, '###FIRSTEMPTYCELL###','Total (###SUM_FIELD_metafeeditnbelts###)');

		$tmp.='<!--###SUM_FIELDS### begin---><tr>'.$sum.'</tr>';
		$tmp.='<!--###SUM_FIELDS### end--->';
		//$tmp.='<tr>'.$this->getSumFields($conf, true, 'html').'</tr><!--###SUM_FIELDS### end--->';
	}
	$GROUPBYFOOTERFIELDS=$this->getGroupByFooterFields($conf,true,'PDF');
	$tmp.=$GROUPBYFOOTERFIELDS.'<!-- ###ALLITEMS### end -->';
	//$tmp.='<!-- ###ALLITEMS### end -->';	
	$tmp.='</table><!-- ###TEMPLATE_EDITMENU_PDF### end -->';
	return $tmp;
}

function getPDFDETTemplate(&$conf)
{
	$pluginId=$conf['pluginId'];
  if ($conf['list.']['TemplatePDFDet']) return '<!-- ###TEMPLATE_EDIT_PDFDET### begin -->'.$conf['list.']['TemplatePDFDet'].'<!-- ###TEMPLATE_EDIT_PDFDET### end -->';
	$tmp='<!-- ###TEMPLATE_EDIT_PDFDET### begin --><?xml version="1.0" encoding="utf-8"?><table><tr>';
	$title=$this->getPreviewFieldCode('edit',$conf,$this->id_field,0);
	$size=$this->getSize($conf, $this->id_field,$conf['table']);
	$tmp.='<td><data>'.$title.'</data><size>'.$size.'</size></td></tr><!-- ###ALLITEMS### begin -->';
	//$GROUPBYFIELDS=$this->getGroupByFields($conf,true,'PDF');
	//$tmp.=$GROUPBYFIELDS;
	$tmp.='<!-- ###ITEM-COL### begin -->';
	$tmp.='<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin -->'.$this->getEditDataFields($conf,true,'html').'<!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';		
	$tmp.='<!-- ###ITEM-COL### end -->';

	
	$tmp.='<!-- ###ALLITEMS### end -->';
	//$tmp.='<!-- ###ALLITEMS### end -->';	
	$tmp.='</table><!-- ###TEMPLATE_EDIT_PDFDET### end -->';
	//die($tmp);
	return $tmp;
}
function getPDFTABTemplate(&$conf)
{
	$pluginId=$conf['pluginId'];
  if ($conf['list.']['TemplatePDFTab']) return '<!-- ###TEMPLATE_EDITMENU_PDFTAB### begin -->'.$conf['list.']['TemplatePDFTab'].'<!-- ###TEMPLATE_EDITMENU_PDFTAB### end -->';
	$cpt=0;
	$tmp='<!-- ###TEMPLATE_EDITMENU_PDFTAB### begin --><?xml version="1.0" encoding="utf-8"?><table>';
	
	$nbCols = $conf['list.']['nbCols'];
	$cpt=0;
	$tmp .= '<!-- ###ALLITEMS### begin -->';
	$GROUPBYFIELDS=$this->getGroupByFields($conf,true,'PDF');
	
	if ($conf['list.']['displayDirection']=='Down') {
		$tmp.=$GROUPBYFIELDS;
	
		$tmp.='<!-- ###ITEM-COL### begin -->';
		$tmp.='<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin --><tr>'.$this->getListDataFields($conf,true,'html').'</tr><!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';		
		$tmp.='<!-- ###ITEM-COL### end -->';
		//$tmp.='</td><!-- ###ITEM-COL### end -->';
	}
	else {
		if ($nbCols) {
					
			$tmp.='<!-- ###ITEM### begin --><tr style="width: '.floor(100/$conf['list.']['nbCols']).'%;">';
			$tmp.='<!-- ###ITEM-EL### begin -->'.$this->getListDataFields($conf, true, 'html').'<!-- ###ITEM-EL### end --></tr>';
			
			/*
			if (($cpt%$nbCols) == 0 ){
			$tmp.= '</tr><tr>';
			$cpt++;
			}
			else{ 
			$tmp.='</tr>';
			$cpt++;
			}
			*/
			
			$tmp.='<!-- ###ITEM### end -->';
	
		}
		else {
		$tmp.=$GROUPBYFIELDS.'<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin --><tr>'.$this->getListDataFields($conf,true,'html').'</tr><!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';
		}
	}

	$tmp.='<!-- ###ALLITEMS### end -->';	
	$tmp.='</table><!-- ###TEMPLATE_EDITMENU_PDFTAB### end -->';
	return $tmp;
}

function getCSVTemplate(&$conf)
{
 	$pluginId=$conf['pluginId'];
  if ($conf['list.']['TemplateCSV']) return '<!-- ###TEMPLATE_EDITMENU_CSV### begin -->'.$conf['list.']['TemplateCSV'].'<!-- ###TEMPLATE_EDITMENU_CSV### end -->';
	$tmp='<!-- ###TEMPLATE_EDITMENU_CSV### begin -->';
	$tmp.=  $GLOBALS['TSFE']->page['title'].chr(10);
	
	// Si on a un champs de recherche ou un tri selon une certaine lettre
	$cont = $conf['inputvar.']['advancedSearch'];	
	if (is_array($conf['inputvar.']['advancedSearch'])) {		
		foreach ($conf['inputvar.']['advancedSearch'] as $key => $val) {
			if($val) {
				$recherche .= ($recherche?', ':'').$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]['columns'][$key]['label'], $conf).':';
				$recherche .= $conf['inputvar.']['advancedSearch'][$key]['val']?$conf['inputvar.']['advancedSearch'][$key]['val']:$conf['inputvar.']['advancedSearch'][$key];
			}
		}
		$tmp.=$recherche;
	}
	
	if ($conf['inputvar.']['sortLetter'])
	$tmp.= '  tri par la lettre: '.$conf['inputvar.']['sortLetter'];
	
	
	
	$tmp.=($conf['list.']['nbCols']?'':$this->getListFields($conf,true)).chr(10).'<!-- ###ALLITEMS### begin -->';
	$GROUPBYFIELDS=$this->getGroupByFields($conf,true);
	if ($conf['list.']['displayDirection']=='Down') {
		$tmp.=$GROUPBYFIELDS;
		$tmp.='<!-- ###ITEM-COL### begin -->';
		$tmp.='<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin -->'.$this->getListDataFields($conf,true,'csv').chr(10).'<!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';		
		$tmp.='<!-- ###ITEM-COL### end -->';
	}
	else	
	{
		$tmp.=$GROUPBYFIELDS.'<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin -->'.$this->getListDataFields($conf,true,'csv').chr(10).'<!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';
	}
	$GROUPBYFOOTERFIELDS=$this->getGroupByFooterFields($conf,true);
	$tmp.=$GROUPBYFOOTERFIELDS.'<!-- ###ALLITEMS### end -->';
	
	if ($conf['list.']['sumFields'])
	{
		$sum=$this->getEditSumFields('SUM',$conf, $count,true);
		$sum=$this->cObj->substituteMarker($sum, '###FIRSTEMPTYCELL###','Total (###SUM_FIELD_metafeeditnbelts###)');
		
		$tmp.='<!--###SUM_FIELDS### begin--->'.$sum.chr(10);
		$tmp.='<!--###SUM_FIELDS### end--->';
		//$tmp.=$this->getSumFields($conf, true).'<!--###SUM_FIELDS### end--->';
	}
	$tmp.='"'.date('d/m/Y').'"'; //TODO: get default date format here !!!
	$tmp.=';"   Utilisateur: '.str_replace('"','""',$GLOBALS['TSFE']->fe_user->user[username]).'";';
	$tmp.='<!-- ###TEMPLATE_EDITMENU_CSV### end -->';
	return $tmp;
}


function getExcelTemplate(&$conf)
{
	$pluginId=$conf['pluginId'];
  if ($conf['list.']['TemplateExcel']) return '<!-- ###TEMPLATE_EDITMENU_EXCEL### begin -->'.$conf['list.']['TemplateExcel'].'<!-- ###TEMPLATE_EDITMENU_EXCEL### end -->';
	$tmp='<!-- ###TEMPLATE_EDITMENU_EXCEL### begin --><?xml version="1.0" encoding="utf-8"?><table>';
	//$tmp.=  '<tr><td><data>'.$GLOBALS['TSFE']->page['title'].'</data><size>'.strlen($GLOBALS['TSFE']->page['title']).'</size></td>';
	
	$cont = $conf['inputvar.']['advancedSearch'];
	$recherche="<tf><data>";
	if (is_array($conf['inputvar.']['advancedSearch'])) {	
		foreach ($conf['inputvar.']['advancedSearch'] as $key => $val) {
			if($val) {
				$recherche .= ($recherche?', ':'').$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$conf['table']]['columns'][$key]['label'], $conf).':';
				$recherche .= $conf['inputvar.']['advancedSearch'][$key]['val']?$conf['inputvar.']['advancedSearch'][$key]['val']:$conf['inputvar.']['advancedSearch'][$key];
			}
		}
	}
	
	if ($conf['inputvar.']['sortLetter'])
	$recherche.= '  tri par la lettre: '.$conf['inputvar.']['sortLetter'];
	$recherche.='</data><size>'.strlen($recherche).'</size></tf>';
  // We generate table headers here 
	//$tmp.='<tr bgcolor="D7D7D7">'.($conf['list.']['nbCols']?'':$this->getListFields($conf)).'</tr><!-- ###ALLITEMS### begin -->';
	$tmp.='<tr>'.($conf['list.']['nbCols']?'':$this->getListFields($conf)).'</tr><!-- ###ALLITEMS### begin -->';
	$GROUPBYFIELDS=$this->getGroupByFields($conf);
	
	if ($conf['list.']['displayDirection']=='Down') {
		$tmp.=$GROUPBYFIELDS;
		$tmp.='<!-- ###ITEM-COL### begin -->';
		$tmp.='<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin --><tr>'.$this->getListDataFields($conf,true,'html').'</tr><!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';		
		$tmp.='<!-- ###ITEM-COL### end -->';
	}
	else		
	{
		$tmp.=$GROUPBYFIELDS.'<!-- ###ITEM### begin --><!-- ###ITEM-EL### begin --><tr>'.$this->getListDataFields($conf,true,'html').'</tr><!-- ###ITEM-EL### end --><!-- ###ITEM### end -->';
	}
	
	if ($conf['list.']['sumFields'])
	{
		$sum=$this->getEditSumFields('SUM',$conf,$count);
		$sum=$this->cObj->substituteMarker($sum, '###FIRSTEMPTYCELL###','Total (###SUM_FIELD_metafeeditnbelts###)');
		$tmp.='<!--###SUM_FIELDS### begin---><tr>'.$sum.'</tr>';
		//$tmp.='<tr>'.$this->getSumFields($conf).'</tr><!--###SUM_FIELDS### end--->';
		$tmp.='<!--###SUM_FIELDS### end--->';
	}
	$GROUPBYFOOTERFIELDS=$this->getGroupByFooterFields($conf);
	$tmp.=$GROUPBYFOOTERFIELDS.'<!-- ###ALLITEMS### end -->';
	
	$tmp.='<tr><tf><data>'.date('d/m/Y').'</data>';
	$tmp.='</tf><tf><data>Utilisateur: '.$GLOBALS['TSFE']->fe_user->user[username];
	$tmp.='</data></tf>'.$recherche.'</tr></table><!-- ###TEMPLATE_EDITMENU_EXCEL### end -->';
	return $tmp;
}  


function getGridPDFTemplate(&$conf) {

	$pluginId=$conf['pluginId'];
	$tmp='<!-- ###TEMPLATE_GRID_PDF### begin --><?xml version="1.0" encoding="utf-8"?><table align="center">';
	$tmp.='<!-- ###GRID### begin -->';
	
	$tmp.='<!-- ###GRID-ROW### begin --><tr>';
	$tmp.='<td><data>###ROWLABEL###</data><size>40</size></td><!-- ###GRID-ITEM### begin --><td><data><!-- ###GRID-EL### begin -->'.$this->getGridDataFields($conf,true,'html').'<!-- ###GRID-EL### begin --></data></td><!-- ###GRID-ITEM### end --></tr>';
	$tmp.='<!-- ###GRID-ROW### end -->';

	$tmp.='<!-- ###GRID### end --></table>';
	$tmp.='<!-- ###TEMPLATE_GRID_PDF### end -->';
	return $tmp;
}

function getGridCSVTemplate(&$conf) {

	$var = $this->getGridDataFields($conf, true);
	
	$pluginId=$conf['pluginId'];
	$tmp='<!-- ###TEMPLATE_GRID_CSV### begin -->';
	$tmp.=  $GLOBALS['TSFE']->page['title'].chr(10);
	
	$tmp.='<!-- ###GRID### begin -->'; 
	$tmp.='<!-- ###GRID-ROW### begin -->###ROWLABEL###';
	$tmp.='<!-- ###GRID-ITEM### begin --> ; <!-- ###GRID-EL### begin -->'.$var.'<!-- ###GRID-EL### begin --><!-- ###GRID-ITEM### end -->'.chr(10);
	
	$tmp.='<!-- ###GRID-ROW### end -->'.chr(10);
	$tmp.='<!-- ###GRID### end -->';
	
	$tmp.=date('d/m/Y');
	$tmp.='Utilisateur: '.$GLOBALS['TSFE']->fe_user->user[username];
	$tmp.='<!-- ###TEMPLATE_GRID_CSV### end -->';
	return $tmp;

}

function getGridExcelTemplate(&$conf){
	
	$var =$this->getGridDataFields($conf);
	
	$pluginId=$conf['pluginId'];
	$tmp='<!-- ###TEMPLATE_GRID_EXCEL### begin -->';
	$tmp.=  $GLOBALS['TSFE']->page['title'].'<br />';
	
	$tmp.='<table border=1><!-- ###GRID### begin -->';
	
	$tmp.='<!-- ###GRID-ROW### begin --><tr>';
	$tmp.='<td bgcolor="D7D7D7">###ROWLABEL###</td><!-- ###GRID-ITEM### begin --><td><!-- ###GRID-EL### begin -->'.$var.'<!-- ###GRID-EL### begin --></td><!-- ###GRID-ITEM### end --></tr>';
	$tmp.='<!-- ###GRID-ROW### end -->';
	
	$tmp.='<!-- ###GRID### end --></table>';
	
	$tmp.=date('d/m/Y');
	$tmp.='Utilisateur: '.$GLOBALS['TSFE']->fe_user->user[username];
	$tmp.='<!-- ###TEMPLATE_GRID_EXCEL### end -->';
	return $tmp;


}





 /**
 * *******************************************************************************************
 * JAVASCRIPT FUNCTIONS
 * *********************************************************************************************/
 /**
  * @param	[type]		$cmd: ...
  * @return	[type]		...
  */
               function getMD5Submit($cmd) {
                        $JSPart = '
                                ';
                        if ($cmd == 'edit') {
                                $JSPart .= "var pw_change = 0;
                                ";
                        }
                        $JSPart .= "function enc_form(form) {
                                        var pass = form['FE[" . $this->theTable . "][password]'].value;
                                        var pass_again = form['FE[" . $this->theTable . "][password_again]'].value;
                                        ";
                        if ($cmd != 'edit') {
                                $JSPart .= "if (pass == '') {
                                                alert('" . $this->pi_getLL('missing_password','',$this->conf['LOCAL_LANG']) . "');
                                                form['FE[" . $this->theTable . "][password]'].select();
                                                form['FE[" . $this->theTable . "][password]'].focus();
                                                return false;
                                        }
                                        ";
                        }
                        $JSPart .= "if (pass != pass_again) {
                                                alert('" . $this->pi_getLL('evalErrors_twice_password','',$this->conf['LOCAL_LANG']) . "');
                                                form['FE[" . $this->theTable . "][password]'].select();
                                                form['FE[" . $this->theTable . "][password]'].focus();
                                                return false;
                                        }
                                        ";
                        if ($cmd == 'edit') {
                                $JSPart .= "if (pw_change) {
                                                ";
                        }
                        $JSPart .= "var enc_pass = MD5(pass);
                                                form['FE[" . $this->theTable . "][password]'].value = enc_pass;
                                                form['FE[" . $this->theTable . "][password_again]'].value = enc_pass;
                                        ";
                        if ($cmd == 'edit') {
                                $JSPart .= "}
                                        ";
                        }
                        $JSPart .= "return true;
                                }";
                        return $JSPart;
                }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$formName: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
function getFormJs($formName,&$conf) {

	        $result.='

            function feedit_'.$formName.'Set(theField, evallist, is_in, checkbox, checkboxValue,checkbox_off){
	      var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
              var feValField = theField+"_feVal";
              
evalFunc.respectTimeZones =
'.($GLOBALS['TYPO3_CONF_VARS']['SYS']['respectTimeZones']?'1':'0').';
evalFunc.USmode =
'.($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?'1':'0').';


if(!(document.'.$formName.' && document.'.$formName.'[theField] && document.'.$formName.'[feValField])) return;

      theValue = document.'.$formName.'[theField].value;
/*              valField = theField.substring(0,theField.length-1)+"_hrv]";
	      document.'.$formName.'[theField].value = theValue;
alert(theValue); */
      document.'.$formName.'[feValField].value = evalFunc.outputObjValue(theFObj, theValue);
	    }

	    function feedit_'.$formName.'Get(theField, evallist, is_in, checkbox, checkboxValue,checkbox_off){
	      var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
	      
	          evalFunc.respectTimeZones =
'.($GLOBALS['TYPO3_CONF_VARS']['SYS']['respectTimeZones']?'1':'0').';
evalFunc.USmode =
'.($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?'1':'0').';

      if (checkbox_off){
		document.'.$formName.'[theField].value=checkboxValue;
	      }else{
		document.'.$formName.'[theField].value = evalFunc.evalObjValue(theFObj, document.'.$formName.'[theField+"_feVal"].value);
                  /*if(document.'.$formName.'[theField].value.length==0)
                  for(idx=1; eval = feedit_split(evallist,",",idx);idx++);
                     if(eval == "required") {
                       alert("Feltet skal udfyldes");
                }*/
	      }
	     feedit_'.$formName.'Set(theField, evallist, is_in, checkbox, checkboxValue,checkbox_off);
	    }
';
	return $result;
}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
  function getJSBefore(&$conf) {
	if (t3lib_extMgm::isLoaded('kb_md5fepw') && !t3lib_div::_GP('ajx')) $GLOBALS['TSFE']->additionalHeaderData['MD5_script'] = '<script type="text/javascript" src="typo3/md5.js"></script>';


    $formName = $this->table.'_form';

    $result .=  '<script type="text/javascript" src="t3lib/jsfunc.evalfield.js"></script>';
	   // <script type="text/javascript">
/*<![CDATA[*/
		$filepath=PATH_site.TYPO3_mainDir.'/js/tabmenu.js';
		if (file_exists($filepath)) $result .=  '<script type="text/javascript" src="typo3/js/tabmenu.js"></script>';
		$script='	
		var DTM_array = new Array();
		var DTM_currentTabs = new Array();
	        function typoSetup() {
					/* this.passwordDummy = "********";*/
					this.decimalSign = ".";
				}
		var TS = new typoSetup();
	        var evalFunc = new evalFunc();';

 $script.=$this->getFormJs($formName,$conf);
 $script.=$this->getFormJs('tx_metafeedit_comments_form',$conf);
 $script.='
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$theField: ...
	 * @return	[type]		...
	 */
            function feedit_manipulateMultipleSelect(theField) {
               selObj = document.'.$formName.'[theField+"_select"];
               val = selObj.value;
               list = document.'.$formName.'[theField].value;
               newList = "";
               for(i=0;i<selObj.length;i++) {
                  if(selObj.options[i].selected == true) {
                     newList += selObj.options[i].value+",";
                  }
               }
               if(newList.length!=0)
                 newList = newList.substring(0,newList.length-1);
               document.'.$formName.'[theField].value = newList;

            }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$theField: ...
	 * @return	[type]		...
	 */
            function feedit_manipulateGroup(theField) {
               selObj = document.'.$formName.'[theField+"_select"];
               val = selObj.value;
               list = document.'.$formName.'[theField].value;
               newList = "";
               for(i=0;i<selObj.length;i++) {
                  if(selObj.options[i].selected == false) {
                     newList += selObj.options[i].value+",";
                  } else {
                     rem_i = i;
                  }
               }
               if(newList.length!=0)
                 newList = newList.substring(0,newList.length-1);
		/*alert(newList);*/
               document.'.$formName.'[theField].value = newList;
               selObj.options[rem_i] = null;

            }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$theStr1, delim, index: ...
	 * @return	[type]		...
	 */
            function feedit_split(theStr1, delim, index) {
               var theStr = ""+theStr1;
               var lengthOfDelim = delim.length;
               sPos = -lengthOfDelim;
               if (index<1) {index=1;}
               for (var a=1; a < index; a++){
                   sPos = theStr.indexOf(delim, sPos+lengthOfDelim);
                   if (sPos==-1){return null;}
               }
               ePos = theStr.indexOf(delim, sPos+lengthOfDelim);
               if(ePos == -1) {ePos = theStr.length;}
               return (theStr.substring(sPos+lengthOfDelim,ePos));
            }
	 
	 
	 var browserWin="";

	function setFormValueOpenBrowser(mode,params) {	//
		var url = "browser.php?mode="+mode+"&bparams="+params;

		browserWin = window.open(url,"Typo3WinBrowser","height=350,width="+(mode=="db"?650:600)+",status=0,menubar=0,resizable=1,scrollbars=1");
		browserWin.focus();
	}
	function setFormValueFromBrowseWin(fName,value,label,exclusiveValues)	{	//
		var formObj = setFormValue_getFObj(fName)
		if (formObj && value!="--div--")	{
			fObj = formObj[fName+"_list"];
			var len = fObj.length;
				// Clear elements if exclusive values are found
			if (exclusiveValues)	{
				var m = new RegExp("(^|,)"+value+"($|,)");
				if (exclusiveValues.match(m))	{
						// the new value is exclusive
					for (a=len-1;a>=0;a--)	fObj[a] = null;
					len = 0;
				} else if (len == 1)	{
					m = new RegExp("(^|,)"+fObj.options[0].value+"($|,)");
					if (exclusiveValues.match(m))	{
							// the old value is exclusive
						fObj[0] = null;
						len = 0;
					}
				}
			}
				// Inserting element
			var setOK = 1;
			if (!formObj[fName+"_mul"] || formObj[fName+"_mul"].value==0)	{
				for (a=0;a<len;a++)	{
					if (fObj.options[a].value==value)	{
						setOK = 0;
					}
				}
			}
			if (setOK)	{
				fObj.length++;
				fObj.options[len].value = value;
				fObj.options[len].text = unescape(label);

					// Traversing list and set the hidden-field
				setHiddenFromList(fObj,formObj[fName]);
				
			}
		}
	}
	function setHiddenFromList(fObjSel,fObjHid)	{	//
		l=fObjSel.length;
		fObjHid.value="";
		for (a=0;a<l;a++)	{
			if (a==l-1){
				fObjHid.value+=fObjSel.options[a].value;
			}else{
				fObjHid.value+=fObjSel.options[a].value+",";
			}
		}
	}
	function setFormRegenerer(fName){
		var formObj = setFormValue_getFObj(fName);
		
		if (formObj)	{
		
			var fObjSel = formObj[fName+"_sel"];
			var fObjlist = formObj[fName+"_list"];
			var liste=fObjSel.options.length;
			for (a=0;a<liste;a++)	{
				
				if (fObjSel.options[a].selected==true)	{
					fObjSel.options[a].selected=false;
				}
				
			}
			myString = formObj[fName].value;
			myArray = myString.split(",");
			for (var num=0;   num<myArray.length;   num++)
			{	
				piece=myArray[num];
				//alert(piece);
				for (a=0;a<liste;a++)	{
					
					if (fObjSel.options[a].value==piece)	{
						setFormRegenererAddOption(fObjlist, fObjSel.options[a].text, fObjSel.options[a].value);
					}
				
				}
			}
				
				
			
			
			
		}
		
	
	}
	function setFormRegenererAddOption(selectbox,text,value )
	{
		var optn = document.createElement("OPTION");
		optn.text = text;
		optn.value = value;
		selectbox.options.add(optn);
	}

	function setFormValueManipulate(fName,type)	{	//
		var formObj = setFormValue_getFObj(fName)
		if (formObj)	{
			var localArray_V = new Array();
			var localArray_L = new Array();
			var localArray_S = new Array();
			var fObjSel = formObj[fName+"_list"];
			var l=fObjSel.length;
			var c=0;
			if (type=="Remove" || type=="Top" || type=="Bottom")	{
				if (type=="Top")	{
					for (a=0;a<l;a++)	{
						if (fObjSel.options[a].selected==1)	{
							localArray_V[c]=fObjSel.options[a].value;
							localArray_L[c]=fObjSel.options[a].text;
							localArray_S[c]=1;
							c++;
						}
					}
				}
				for (a=0;a<l;a++)	{
					if (fObjSel.options[a].selected!=1)	{
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=0;
						c++;
					}
				}
				if (type=="Bottom")	{
					for (a=0;a<l;a++)	{
						if (fObjSel.options[a].selected==1)	{
							localArray_V[c]=fObjSel.options[a].value;
							localArray_L[c]=fObjSel.options[a].text;
							localArray_S[c]=1;
							c++;
						}
					}
				}
			}
			if (type=="Down")	{
				var tC = 0;
				var tA = new Array();

				for (a=0;a<l;a++)	{
					if (fObjSel.options[a].selected!=1)	{
							// Add non-selected element:
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=0;
						c++;

							// Transfer any accumulated and reset:
						if (tA.length > 0)	{
							for (aa=0;aa<tA.length;aa++)	{
								localArray_V[c]=fObjSel.options[tA[aa]].value;
								localArray_L[c]=fObjSel.options[tA[aa]].text;
								localArray_S[c]=1;
								c++;
							}

							var tC = 0;
							var tA = new Array();
						}
					} else {
						tA[tC] = a;
						tC++;
					}
				}
					// Transfer any remaining:
				if (tA.length > 0)	{
					for (aa=0;aa<tA.length;aa++)	{
						localArray_V[c]=fObjSel.options[tA[aa]].value;
						localArray_L[c]=fObjSel.options[tA[aa]].text;
						localArray_S[c]=1;
						c++;
					}
				}
			}
			if (type=="Up")	{
				var tC = 0;
				var tA = new Array();
				var c = l-1;

				for (a=l-1;a>=0;a--)	{
					if (fObjSel.options[a].selected!=1)	{

							// Add non-selected element:
						localArray_V[c]=fObjSel.options[a].value;
						localArray_L[c]=fObjSel.options[a].text;
						localArray_S[c]=0;
						c--;

							// Transfer any accumulated and reset:
						if (tA.length > 0)	{
							for (aa=0;aa<tA.length;aa++)	{
								localArray_V[c]=fObjSel.options[tA[aa]].value;
								localArray_L[c]=fObjSel.options[tA[aa]].text;
								localArray_S[c]=1;
								c--;
							}

							var tC = 0;
							var tA = new Array();
						}
					} else {
						tA[tC] = a;
						tC++;
					}
				}
					// Transfer any remaining:
				if (tA.length > 0)	{
					for (aa=0;aa<tA.length;aa++)	{
						localArray_V[c]=fObjSel.options[tA[aa]].value;
						localArray_L[c]=fObjSel.options[tA[aa]].text;
						localArray_S[c]=1;
						c--;
					}
				}
				c=l;	// Restore length value in "c"
			}

				// Transfer items in temporary storage to list object:
			fObjSel.length = c;
			for (a=0;a<c;a++)	{
				fObjSel.options[a].value = localArray_V[a];
				fObjSel.options[a].text = localArray_L[a];
				fObjSel.options[a].selected = localArray_S[a];
			}
			setHiddenFromList(fObjSel,formObj[fName]);

			
		}
	}
	function setFormValue_getFObj(fName)	{	
		//specifier ici le nom du formulaire a la place de document.formulaire
		var formObj = document.'.$formName.';
		if (formObj)	{
			if (formObj[fName] && formObj[fName+"_list"] && formObj[fName+"_list"].type=="select-multiple")	{
				return formObj;
			} else {
				alert("Formfields missing:\n fName: "+formObj[fName]+"\n fName_list:"+formObj[fName+"_list"]+"\n type:"+formObj[fName+"_list"].type+"\n fName:"+fName);
			}
		}
		return "";
	}
	
     ';
/*]]>*/
//	    </script>
//';


   	if (!$GLOBALS['TSFE']->config['config']['removeDefaultJS']) {
  		$result.='<script type="text/javascript">'.$script.'</script>';
   		$result .= $this->additionalJS_initial;
    	if ($this->additionalJS_pre) $result.'<script type="text/javascript">'. implode('', $this->additionalJS_pre).'</script>';
  	} else {
  	 	$result.=TSpagegen::inline2TempFile($script,'js');
   		$result.= $this->additionalJS_initial;
    	if ($this->additionalJS_pre) $result.=TSpagegen::inline2TempFile( implode('', $this->additionalJS_pre),'js');
  	}
    if($conf['divide2tabs'])
		$result .= $this->templateObj->getDynTabMenuJScode();
		return $result;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
  function getJSAfter() {
  	if (!$GLOBALS['TSFE']->config['config']['removeDefaultJS']) {
   		return '<script type="text/javascript">'.implode(chr(10), $this->additionalJS_post).'</script>'.chr(10).'<script type="text/javascript">'.implode(chr(10), $this->additionalJS_end).'</script>';
		} else {
 			return TSpagegen::inline2TempFile(implode(chr(10), $this->additionalJS_post), 'js').chr(10).TSpagegen::inline2TempFile(implode(chr(10), $this->additionalJS_end), 'js');
	  }			
  }

  /**
 * array_merge_recursive2()
 *
 * Similar to array_merge_recursive but keyed-valued are always overwritten.
 * Empty values is also overwritten.
 * Priority goes to the 2nd array.
 *
 * @param	$paArray1		array
 * @param	$paArray2		array
 * @return	array
 */
  function array_merge_recursive2($paArray1, $paArray2) {
    if (!is_array($paArray1) or !is_array($paArray2)) {
      return is_null($paArray2)?$paArray1:$paArray2;
    }
    foreach ($paArray2 AS $sKey2 => $sValue2) {
      $paArray1[$sKey2] = $this->array_merge_recursive2(@$paArray1[$sKey2],
							$sValue2);
    }
    return $paArray1;
  }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
  function pi_loadLL($conf) {
   // flatten the structure of labels overrides
                        if (is_array($conf)) {
                                $done = false;
                                $i = 0;
                                while(!$done && $i < 10) {
                                        $done = true;
                                        reset($conf);
                                        while(list($k,$lA)=each($conf)) {
                                                if (is_array($lA)) {
                                                        foreach($lA as $llK => $llV)    {
                                                                if (is_array($llV))    {
                                                                        foreach ($llV as $llK2 => $llV2) {
                                                                                $conf[$k][$llK . $llK2] = $llV2;
                                                                        }
                                                                        unset($conf[$k][$llK]);
                                                                        $done = false;
                                                                        ++$i;
                                                                }
                                                        }
                                                }
                                        }
                                }
                        }
			return $conf;
                }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
	function alphabeticalSearch(&$conf) {
		$ret='<div'.$this->caller->pi_classParam('alphabeticalSearch').'>';
		for ($i="A"; $i != "AA"; $i++) $ret.='<div'.$this->caller->pi_classParam('lettersearch').'><a href="###FORM_URL###&amp;'.$this->prefixId.'['.sortLetter.']['.$conf['pluginId'].']='.$i.'">'.$i.'</a></div>'; 

		$ret.='<div'.$this->caller->pi_classParam('lettersearch').'><a href="###FORM_URL_NO_PRM###&amp;'.$this->prefixId.'['.reset.']['.$conf['pluginId'].']=1">'.$this->metafeeditlib->getLL("alphabetical_search_all",$conf).'</a></div>'; 
		$ret.='</div>';
		return $ret;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
	function searchBox(&$conf) {
		$ret='<div'.$this->caller->pi_classParam('searchbox').'>';
		$ret.='<form name="'.$conf['table'].'_ftform" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" style="margin: 0pt;">';
		$ret.='<table>
				<tbody><tr>
					<td><input id="tx_metafeedit.sword.'.$conf['pluginId'].'" name="tx_metafeedit[sword]['.$conf['pluginId'].']" value="###FTSEARCHBOXVAL###" class="tx-metafeedit-searchbox-sword" type="text" /></td>
					<td><input value="Search" class="tx-metafeedit-searchbox-button" type="submit" /><input name="no_cache" value="1" type="hidden" /><input name="tx_metafeedit[pointer]['.$conf['pluginId'].']" value="" type="hidden" /></td>
				</tr>

			</tbody></table>
			</form>';
		$ret.='</div>';
		return $ret;
	}

    /**
    * [Describe function...]
    *
    * @return	[type]		...
    */
    function calendarSearch() {
		$ret='<div'.$this->caller->pi_classParam('calendarSearch').'>###CALENDAR_SEARCH###';
		$ret.='</div>';
		return $ret;
	}

  /**
  * advancedSearch : displays advanced search fields in list mode.
  *
  * @param	array :		config array....
  * @param	string $filter :	filter content....
  * @return	string : 	html of advanced search area.
  */
	 
	function advancedSearch(&$conf,$filter) {
		$table = $conf['table'];
		$cnt='<div'.$this->caller->pi_classParam('advancedSearch').'><form name="'.$this->table.'_asform" method="post" action="###FORM_URL###" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'">';
		$fields=$conf['list.']['advancedSearchFields']?$conf['list.']['advancedSearchFields']:($conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field);
		$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
		$fsbc=0;
		$fsi=0;
		$ret='<div id="'.$this->caller->pi_getClassName('as').'_asfilter_'.$conf['pluginId'].'" class="'.$this->caller->pi_getClassName('as').' '.$this->caller->pi_getClassName('advancedSearch-text').' '.$this->caller->pi_getClassName('advancedSearch-'.$curTable['table'].'-text').' '.$this->caller->pi_getClassName('advancedSearch-'.$curTable['table'].'-text-'.$curTable['fNiD']).'">'.$filter.'</div>';
		
		if ($conf['list.']['advancedSearchAjaxSelector']) {   // RSG make advanced search tabs optional
		
		$searchTabs=array();
		$searchTabs[]='<li class="active"><a id="'.$this->caller->pi_getClassName('as').'_asfilter_'.$conf['pluginId'].'_a" href="#">'.$this->metafeeditlib->getLL("advanced_search_label",$conf).'</a></li>';
		} //
		foreach($fieldArray as $FN) {
			$params=t3lib_div::trimExplode(';',$FN);
		  if ($params[0]!='--div--') {
			// gestion des fieldset
			if ($params[0]=='--fse--' && $fsbc) {
				$ret.='</fieldset>';
				if ($fsbc) $fsbc--;
						continue;
			}
			if ($params[0]=='--fsb--') {
				if ($fsbc) {
					$ret.='</fieldset>';
					$fsbc--;
				}
				$fsbc++;
				$ret.='<fieldset class="'.$this->caller->pi_getClassName('as-fs').' '.$this->caller->pi_getClassName('as-fs-'.$params[1]).'">';
				if ($conf['list.']['asFieldSetNames.'][$fsi]) $ret.='<legend>'.$conf['list.']['asFieldSetNames.'][$fsi].'</legend>';
				$fsi++;
						continue;
			}

					//modif CMD - prise en compte des tables etrangère dans l'AS
					$curTable = $this->metafeeditlib->getForeignTableFromField($FN, $conf,'',array());
					//krumo($conf['TCAN'][$curTable['relTable']]);
					$type = $conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['type'];
					if(($conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['eval']=='date' || $conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['eval']=='datetime')) $type=date;
					//TODO : metre cette modification de type de donnée en surcharge de TCA
					// $markerArray['###FIELD_EVAL_crdate###'] = strftime(($conf['datetimeformat']?$conf['datetimeformat']:"%H:%M %e-%m-%Y"),$crow['crdate']);
					//$GLOBALS['TCA'][$this->table]['columns'][$GLOBALS['TCA'][$this->table]['ctrl']['crdate']]['config']['eval']='datetime';
					//$GLOBALS['TCA'][$this->table]['columns'][$GLOBALS['TCA'][$this->table]['ctrl']['crdate']]['config']['type']='input';
					//$GLOBALS['TCA'][$this->table]['columns'][$GLOBALS['TCA'][$this->table]['ctrl']['crdate']]['label']=$this->table.'.'.$GLOBALS['TCA'][$this->table]['ctrl']['cr
					//dans le meta_feedit.php
					//$label=($curTable['fNiD']=='crdate' && (string)$type=='')?'LLL:EXT:'.$curTable['table'].'.'.$curTable['fNiD']:$conf['TCAN'][$curTable['table']]['columns'][trim($curTable['fNiD'])]['label'];
					$label=$curTable['fieldLabel'];
					$type=($curTable['fNiD']=='crdate' && (string)$type=='')?'date':$type;
					$Lib='<div class="'.$this->caller->pi_getClassName('asl').'">'.$this->metafeeditlib->getLLFromLabel($label,$conf).'</div>';
					    //$fN=str_replace('.','_',$fN);
					    if ($conf['list.']['advancedSearchAjaxSelector']) {  // rsg
					$searchTabs[]='<li><a id="'.$this->caller->pi_getClassName('as').'_'.str_replace('.','_',$FN).'_'.$conf['pluginId'].'_a" href="#">'.$this->metafeeditlib->getLLFromLabel($label,$conf).'</a></li>';
					} //
					$div='<div id="'.$this->caller->pi_getClassName('as').'_'.str_replace('.','_',$FN).'_'.$conf['pluginId'].'" '. ($conf['list.']['advancedSearchAjaxSelector']? 'style="display:none;" ' : '').' class="'.$this->caller->pi_getClassName('as').' '.$this->caller->pi_getClassName('advancedSearch-'.$type).' '.$this->caller->pi_getClassName('advancedSearch-'.$curTable['table'].'-'.$type).' '.$this->caller->pi_getClassName('advancedSearch-'.$curTable['table'].'-'.$type.'-'.$curTable['fNiD']).'">'.$Lib;  // rsg adjust
					$value=' value="###ASFIELD_'.$FN.'_VAL###"';
					switch((string)$type) {
					case 'text':
					case 'input':
							$ret.=$div.'<input type="text" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']"'.$value.$this->caller->pi_classParam('form-asfield').' /></div>';
						break;
					case 'date':
						  $value=' value="###ASFIELD_'.$FN.'_VAL###"';
						  $valuesup=' value="###ASFIELD_'.$FN.'_VALSUP###"';
						  $ret.=$div.'<input type="radio" id="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_equal" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.'][op]" value="=" ###ASCHECKEDEQUAL### /><label for="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_equal">=</label>';
						  $ret.='<input type="radio" id="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_inf" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.'][op]" value="<" ###ASCHECKEDINF### /><label for="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_inf">&lt;</label>';
						  $ret.='<input type="radio" id="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_sup" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.'][op]" value=">" ###ASCHECKEDSUP### /><label for="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_sup">&gt;</label>';
						  $ret.='<input type="text" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.'][val]" id="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.val" value="###ASFIELD_'.$FN.'_VAL###" '.$this->caller->pi_classParam('form-asfield').' />'.(t3lib_extmgm::isLoaded('rlmp_dateselectlib')?tx_rlmpdateselectlib::getInputButton($this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.val',array('calConf.'=>array('inputFieldDateTimeFormat'=> ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%e-%Y' :'%e-%m-%Y')))):'');
						  $ret.='<input type="radio" id="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_between" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.'][op]" value="><" ###ASCHECKEDBETWEEN###/><label for="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.op_between">&gt;&lt;</label>';
						  $ret.='<input type="text" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.'][valsup]" id="'.$this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.valsup" value="###ASFIELD_'.$FN.'_VALSUP###" '.$this->caller->pi_classParam('form-asfield').'/>'.(t3lib_extmgm::isLoaded('rlmp_dateselectlib')?tx_rlmpdateselectlib::getInputButton($this->prefixId.'.advancedSearch.'.$conf['pluginId'].'.'.$FN.'.valsup',array('calConf.'=>array('inputFieldDateTimeFormat'=> ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%e-%Y' :'%e-%m-%Y')))):'');
						  $ret.='</div>';
						  break;
					  case 'group':
						  break;
					  case 'radio':
						//modif CMD on récup la val courante pour l'afficher en tant que selectionné
						$val = $conf['piVars']['advancedSearch'][$conf['pluginId']][$FN];
						$ret.=$div;
						for ($i = 0; $i < count ($conf['TCAN'][$curTable['table']]['columns'][$curTable['fNiD']]['config']['items']); ++$i) {
							$ckecked='';
							if ($this->metafeeditlib->is_extent($val)) {
								$checked=($i==$val)?'checked="checked"':'';
							}
							$ret.='<input type="radio"'.$this->caller->pi_classParam('radio').'id="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']'.'-'.$i.'" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']" value="'.$i.'" '.$checked.' /><label for="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']'.'-'.$i.'">'.$this->metafeeditlib->getLLFromLabel($conf['TCAN'][$curTable['table']]['columns'][$curTable['fNiD']]['config']['items'][$i][0],$conf).'</label>';
						}
						$ret.='</div>';
						break;
					case 'check':
						//modif CMD on récup la val courante pour l'afficher en tant que selectionné
						
						$val=is_array($conf['piVars']['advancedSearch'][$conf['pluginId']])?$conf['piVars']['advancedSearch'][$conf['pluginId']][$FN]:'';
						$sel1=($val==1)?' selected="selected" ':'';
						$sel2=($this->metafeeditlib->is_extent($val) && $val==0)?' selected="selected" ':'';
						$ret.=$div.'<select name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']"'.$value.$this->caller->pi_classParam('form-asfield').'/>';
						$SO='<option value=""></option><option value="1"'.$sel1.'>'.$this->metafeeditlib->getLL("check_yes",$conf).'</option><option value="0"'.$sel2.'>'.$this->metafeeditlib->getLL("check_no",$conf).'</option>';
						$ret.=$SO.'</select></div>';
						break;
				
					case 'select':
						// For select fields we either draw  ajax seletion widget or we relace with getselectoptions ...
						if ($conf['TCAN'][$conf['table']]['columns'][$FN]['config']['foreign_table'] && ($conf['list.']['advancedSearchAjaxSelector'] || $conf['list.']['advancedSearchAjaxSelector.'][$FN])) {
						//if ($conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['foreign_table'] && ($conf['list.']['advancedSearchAjaxSelector'] || $conf['list.']['advancedSearchAjaxSelector.'][$FN])) {
							$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'widgets'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/widgets.js"></script>';
							$ajaxWidgets = t3lib_div::makeInstance('tx_metafeedit_widgets');
							$ajaxWidgets->init($this->prefixId,$this->metafeeditlib);
							$ret.=$ajaxWidgets->comboList($this->metafeeditlib->getLLFromLabel($label,$conf),'','','handleData','setData',$this->metafeeditlib->getLLFromLabel($label,$conf),15,$conf,$FN);
						  } else {
							$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'TCE'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/jsfunc.tbe_editor.js"></script>';
							$name = ' name="'.($conf['TCAN'][$conf['table']]['columns'][$FN]['config']['size']>1?$conf['pluginId'].'['.$FN.']" id="'.$conf['pluginId'].'_'.$FN.'_sel" onchange="getSelected(\''.$conf['pluginId'].'_'.$FN.'\');" ':$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']"');
    						$ret.=$div.'<select size="1"'.$name.$this->caller->pi_classParam('form-asfield').'>';   						
							$SO='###AS_FIELD_'.$FN.'###';
							$ret.=$SO.'</select>';
							if ($conf['TCAN'][$conf['table']]['columns'][$FN]['config']['size']>1) {
								$ret.='<input type="hidden" name="'.$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']" id="'.$conf['pluginId'].'_'.$FN.'_val"'.$value.' />';
								$ret.='<script type="text/javascript">
										/*<![CDATA[*/
										setSelected(\''.$conf['pluginId'].'_'.$FN.'\');
										/*]]>*/
										</script>';
							}
							$ret.='</div>';
						}
						break;
					default:
						break;
					}
			}
		}
		if ($fsbc) {
			$ret.='</fieldset>';
			$fsbc--;
		}
		
		$ret=$cnt.($conf['list.']['advancedSearchAjaxSelector'] ? '<ul class="astabnav">'.implode("",$searchTabs)."</ul>" : '').$ret;  //RSG adjust
		
		$ret.='<div '.$this->caller->pi_classParam('advancedSearch-actions').'><div '.$this->caller->pi_classParam('advancedSearch-action').'>';
		$ret.='<div '.$this->caller->pi_classParam('advancedSearch-action').'><input type="submit" name="submit" value="'.($conf['edit.']['preview']?$this->metafeeditlib->getLL("advanced_search_label",$conf):$this->metafeeditlib->getLL("advanced_search_label",$conf)).'"'.$this->caller->pi_classParam('form-submit').' /></div>';
		$ret.='<div class="'.$this->caller->pi_getClassName('link').' '.$this->caller->pi_getClassName('advancedSearch-action').' '.$this->caller->pi_getClassName('as_reset').'"><a href="###FORM_URL_NO_PRM###&amp;'.$this->prefixId.'[reset]['.$conf['pluginId'].']=1">'.$this->metafeeditlib->getLL("advanced_search_reset",$conf).'</a></div>';
		$ret.= '</div></div></form></div>';
		return $ret;
	}

	// Dummy function for rte 4.1
	function getDynTabLevelState ($str) { return ''; }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit.php']);
}

?>
