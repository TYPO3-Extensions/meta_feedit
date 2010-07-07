<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2004 Christophe Balisky <christophe@balisky.org>
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
/**
 * Class/Function which manipulates the item-array for the FEusers listing
 *
 * @author	Christophe Balisky <christophe@balisky.org>
 */


/**
 * SELECT box processing
 *
 * @author	Kasper Skårhøj (kasper@typo3.com)
 * @package TYPO3
 * @subpackage tx_newloginbox
 */
class tx_metafeedit_flexfill {

	/**
	 * Adding fe_users field list to selector box array
	 *
	 * @param	array		Parameters, changing "items". Passed by reference.
	 * @param	object		Parent object
	 * @return	void
	 */
	function main(&$params,&$pObj)	{
		global $TCA;
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		$this->loadTCA($table);

		$FTs=explode(",",$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$params['items']=array();
		$params['items'][]=Array('', '');
		//$params['items'][]=Array( 'Unique ID','uid');
		//$params['items'][]=Array('Page ID','pid');
		$this->getFields($params,$table,'');
		if ($FTs) {
			foreach($FTs as $FTRel) {
				$FT=@$TCA[$table]['columns'][$FTRel]['config']['foreign_table'];
				$this->getFields($params,$FT,$FTRel);
			}
		};
		
		// We add sql calculated fields added by user in flexform
		
		$FTs=explode(chr(10),$flexarr['data']['sList']['lDEF']['listsqlcalcfields']['vDEF']);
		foreach($FTs as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$params['items'][]=Array($OVs[0], $OVs[0]);
			}		
		}

		// We add php calculated fields added by user in flexform

		$FTs=explode(chr(10),$flexarr['data']['sList']['lDEF']['listphpcalcfields']['vDEF']);
		foreach($FTs as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$params['items'][]=Array($OVs[0], $OVs[0]);
			}		
		}
		$this->clearTCA($table);
	}
	function clearTCA($table) {
		global $TCA;
		unset($TCA[$table]['columns']['pid']);
		unset($GLOBALS['TCA'][$table]['columns']['pid']);
	}
	
	function LoadTCA($table) {
		global $TCA;
		t3lib_div::loadTCA($table);

		/*if($conf['fe_cruser_id'])
			$GLOBALS["TCA"][$table]['ctrl']['fe_cruser_id'] = $conf['fe_cruser_id'];
		if($conf['fe_crgroup_id'] && $conf['allowedGroups']) {
  			$GLOBALS["TCA"][$table]['ctrl']['fe_crgroup_id'] = $conf['fe_crgroup_id'];
		}*/
		// if no configuration for column sortby we add one
		if($GLOBALS["TCA"][$table]['ctrl']['sortby'] && !is_array($GLOBALS["TCA"][$table]['columns'][$GLOBALS["TCA"][$table]['ctrl']['sortby']])) {
			$GLOBALS["TCA"][$table]['columns'][$GLOBALS["TCA"][$table]['ctrl']['sortby']]=array(
				'exclude'=>1,
				'label'=>'LLL:EXT:meta_feedit/locallang.xml:sorting',
				'config'=>array(
					'type'=>'input',
					'size'=>10,
					'eval'=>'int',
					'default'=>99999,
				),
			);	
		}
		// we add uid field.
		if (!is_array($GLOBALS['TCA'][$table]['columns']['uid'])) {
			$GLOBALS['TCA'][$table]['columns']['uid']=array(
				'exclude'=>1,
				'label'=>'LLL:EXT:meta_feedit/locallang.xml:uid',
				'config'=>array(
					'type'=>'input',
					'size'=>10,
					'eval'=>'int',
				),
			);	
		}
		// we add foreign join on pid field.
		if (!is_array($GLOBALS['TCA'][$table]['columns']['pid'])) {
			$GLOBALS['TCA'][$table]['columns']['pid']=array(
				'exclude'=>1,
				'label'=>'LLL:EXT:meta_feedit/locallang.xml:pid',
				'config'=>array(
					'type'=>'select',
					'size'=>1,
					'minitems'=>1,
					'maxitems'=>1,
					'foreign_table'=>'pages',
				),
			);	
		}
	}
	// ???
	function mainav(&$params,&$pObj)	{
		global $TCA;
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		$this->loadTCA($table);
		$FTs=explode(",",$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$params['items'][]=Array('[fieldset<]', '--fsb--;FSB');
		$params['items'][]=Array('[>fieldset]', '--fse--;FSE');
		//$params['items'][]=Array( 'Unique ID','uid');
		//$params['items'][]=Array('Page ID','pid');
		$this->getFields($params,$table,'');
		if ($FTs) {
			foreach($FTs as $FTRel) {
				$FT=@$TCA[$table]['columns'][$FTRel]['config']['foreign_table'];
				$this->getFields($params,$FT,$FTRel);
			}
		};
		
		// We add sql calculated fields added by user in flexform
		
		$FTs=explode(chr(10),$flexarr['data']['sList']['lDEF']['listsqlcalcfields']['vDEF']);
		foreach($FTs as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$params['items'][]=Array($OVs[0], $OVs[0]);
			}		
		}

		// We add php calculated fields added by user in flexform

		$FTs=explode(chr(10),$flexarr['data']['sList']['lDEF']['listphpcalcfields']['vDEF']);
		foreach($FTs as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$params['items'][]=Array($OVs[0], $OVs[0]);
			}		
		}
		$this->clearTCA($table);
	}
	/**
	* main_ob
	*
	* @param	[type]		$$params: ...
	* @param	[type]		$pObj: ...
	* @return	[type]		...
	*/

	function main_ob(&$params,&$pObj)	{
		global $TCA;
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		$this->loadTCA($table);
		$FTs=explode(",",@$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$this->getFields_OB($params,$table,'',$flexarr);
		if ($FTs) {
			foreach($FTs as $FTRel) {
				$FT=@$TCA[$table]['columns'][$FTRel]['config']['foreign_table'];
				$this->getFields_OB($params,$FT,$FTRel,$flexarr);
			}
		};
		$this->clearTCA($table);
	}

	/**
	* [Describe function...]
	*
	* @param	[type]		$$params: ...
	* @param	[type]		$pObj: ...
	* @return	[type]		...
	*/
	
	function main_ft(&$params,&$pObj)	{
	    global $TCA;
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		$this->loadTCA($table);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$this->getFieldsFT($params,$table,'');
		$foreignTables=@$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF'];
		$fta=explode(',',$foreignTables);
        foreach($fta as $ft) {
            $ftable=$TCA[$table]['columns'][$ft]['config']['foreign_table'];            
            $this->getFieldsFT($params,$ftable,$ft);
        }    
 		$this->clearTCA($table);
          
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$pObj: ...
	 * @return	[type]		...
	 */
	function pluginId(&$params,&$pObj)	{
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$pluginId=@$flexarr['data']['sQuickStart']['lDEF']['pluginId']['vDEF'];
		//echo $pluginId." @@@ ".$pObj->uid." uuu";
		//$params['items']=array();
		//$params['items'][]=Array('', '');
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$pObj: ...
	 * @return	[type]		...
	 */
	function tabFields(&$params,&$pObj)	{
		global $TCA;
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		$FTs=explode(",",@$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$this->loadTCA($table);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$params['items'][]=Array('[Tab]', '--div--;Tab');
		$params['items'][]=Array('[fieldset<]', '--fsb--;FSB');
		$params['items'][]=Array('[>fieldset]', '--fse--;FSE');
		//$params['items'][]=Array( 'Unique ID','uid');
		//$params['items'][]=Array('Page ID','pid');
		$this->getFields($params,$table,'');
		if ($FTs) {
			foreach($FTs as $FTRel) {
				$FT=@$TCA[$table]['columns'][$FTRel]['config']['foreign_table'];
				$this->getFields($params,$FT,$FTRel);
			}
		}
		$this->clearTCA($table);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$rel: ...
	 * @return	[type]		...
	 */
	function getFields(&$params,$table,$rel) {
		global $TCA;
		$prefix=$rel?$rel.'.':'';
		t3lib_div::loadTCA($table);
		if (is_array($TCA[$table]['columns']))  {
			foreach($TCA[$table]['columns'] as $key => $config)     {
				$label = t3lib_div::fixed_lgd(preg_replace('/:$/','',$GLOBALS['LANG']->sL($config['label'])),30).' ('.$prefix.$key.')';
				$params['items'][]=Array($label, $prefix.$key);
			}
		}
		if (@$TCA[$table]['ctrl']['tstamp']) $params['items'][]=Array($TCA[$table]['ctrl']['tstamp'].' ('.$prefix.$TCA[$table]['ctrl']['tstamp'].')', $prefix.$TCA[$table]['ctrl']['tstamp']);
		if (@$TCA[$table]['ctrl']['crdate']) $params['items'][]=Array($TCA[$table]['ctrl']['crdate'].' ('.$prefix.$TCA[$table]['ctrl']['crdate'].')', $prefix.$TCA[$table]['ctrl']['crdate']);
		if (@$TCA[$table]['ctrl']['cruser_id']) $params['items'][]=Array($TCA[$table]['ctrl']['cruser_id'].' ('.$prefix.$TCA[$table]['ctrl']['cruser_id'].')',$prefix.$TCA[$table]['ctrl']['cruser_id']);
		if (@$TCA[$table]['ctrl']['fe_cruser_id']) $params['items'][]=Array($TCA[$table]['ctrl']['fe_cruser_id'].' ('.$prefix.$TCA[$table]['ctrl']['fe_cruser_id'].')',$prefix.$TCA[$table]['ctrl']['fe_cruser_id']);    
		return $params;
	}

	/**
	 * getFields_OB
	 *
	 * @param	array		$params: ...
	 * @param	string		$table: tablename
	 * @param	string		$rel: relation name
	 * @param	array		$flexarr: flexform array data
	 * @return	array		...
	 */
	function getFields_OB(&$params,$table,$rel,&$flexarr) {
		global $TCA;
		$rel=str_replace('.','_',$rel);
		$prefix=$rel?$rel.'.':'';
		
		if (is_array($TCA[$table]['columns']))  {
			foreach($TCA[$table]['columns'] as $key => $config)     {
				$label = t3lib_div::fixed_lgd(preg_replace('/:$/','',$GLOBALS['LANG']->sL($config['label'])),30).' ASC ('.$prefix.$key.')';
				$params['items'][]=Array($label, $prefix.$key.':asc' );
				$label = t3lib_div::fixed_lgd(preg_replace('/:$/','',$GLOBALS['LANG']->sL($config['label'])),30).' DESC ('.$prefix.$key.')';
				$params['items'][]=Array($label, $prefix.$key.':desc' );
			}
		}
		if (@$TCA[$table]['ctrl']['tstamp']) $params['items'][]=Array($TCA[$table]['ctrl']['tstamp'].' ASC ('.$prefix.$TCA[$table]['ctrl']['tstamp'].')', $prefix.$TCA[$table]['ctrl']['tstamp'].':asc');
		if (@$TCA[$table]['ctrl']['crdate']) $params['items'][]=Array($TCA[$table]['ctrl']['crdate'].' ASC ('.$prefix.$TCA[$table]['ctrl']['crdate'].')', $prefix.$TCA[$table]['ctrl']['crdate'].':asc');
		if (@$TCA[$table]['ctrl']['cruser_id']) $params['items'][]=Array($TCA[$table]['ctrl']['cruser_id'].' ASC ('.$prefix.$TCA[$table]['ctrl']['cruser_id'].')',$prefix.$TCA[$table]['ctrl']['cruser_id'].':asc');
		if (@$TCA[$table]['ctrl']['fe_cruser_id']) $params['items'][]=Array($TCA[$table]['ctrl']['fe_cruser_id'].' ASC ('.$prefix.$TCA[$table]['ctrl']['fe_cruser_id'].')',$prefix.$TCA[$table]['ctrl']['fe_cruser_id'].':asc');    
		if (@$TCA[$table]['ctrl']['tstamp']) $params['items'][]=Array($TCA[$table]['ctrl']['tstamp'].' DESC ('.$prefix.$TCA[$table]['ctrl']['tstamp'].')', $prefix.$TCA[$table]['ctrl']['tstamp'].':desc');
		if (@$TCA[$table]['ctrl']['crdate']) $params['items'][]=Array($TCA[$table]['ctrl']['crdate'].' DESC ('.$prefix.$TCA[$table]['ctrl']['crdate'].')', $prefix.$TCA[$table]['ctrl']['crdate'].':desc');
		if (@$TCA[$table]['ctrl']['cruser_id']) $params['items'][]=Array($TCA[$table]['ctrl']['cruser_id'].' DESC ('.$prefix.$TCA[$table]['ctrl']['cruser_id'].')',$prefix.$TCA[$table]['ctrl']['cruser_id'].':desc');
		if (@$TCA[$table]['ctrl']['fe_cruser_id']) $params['items'][]=Array($TCA[$table]['ctrl']['fe_cruser_id'].' DESC ('.$prefix.$TCA[$table]['ctrl']['fe_cruser_id'].')',$prefix.$TCA[$table]['ctrl']['fe_cruser_id'].':desc');    
                		
		// We add sql calculated fields added by user in flexform

		$FTs=explode(chr(10),@$flexarr['data']['sList']['lDEF']['listsqlcalcfields']['vDEF']);
		foreach($FTs as $ORV) {
			$OVs=explode('=',$ORV,2);
			if (count($OVs)==2) {
				$params['items'][]=Array($OVs[0]." ASC", $OVs[0].':asc:calc');
				$params['items'][]=Array($OVs[0]." DESC", $OVs[0].':desc:calc');
			}		
		}
		return $params;
	}

	/**
	 * get foreign Table fields
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function getFieldsFT(&$params,$table,$prefix='') {

		global $TCA;
		$ta=array();
		t3lib_div::loadTCA($table);
		// we add uid field.
		if (!is_array($GLOBALS['TCA'][$table]['columns']['uid'])) {
			$GLOBALS['TCA'][$table]['columns']['uid']=array(
				'exclude'=>1,
				'label'=>'LLL:EXT:meta_feedit/locallang.xml:uid',
				'config'=>array(
					'type'=>'input',
					'size'=>10,
					'eval'=>'int',
				),
			);	
		}
        if (is_array($TCA[$table]['columns']))  {
            foreach($TCA[$table]['columns'] as $key => $config)     {
				if ($config['config']['foreign_table']) {
                    $label = t3lib_div::fixed_lgd(preg_replace('/:$/','',$GLOBALS['LANG']->sL($config['label'])),30).' ('.($prefix?$prefix.'.':'').$key.')';
                    $params['items'][]=Array($label, ($prefix?$prefix.'.':'').$key);
                    //echo ($prefix?$prefix.'.':'').$key."-$label<br>";
				}
            }
        }
		return $params;
	}

	/**
	 * getFlex
	 *
	 * @return	[type]		...
	 */
	function getFlex() {
		// we get uid  of the current editing plugin
		$t=t3lib_div::_GP('edit');
		$a=array_keys($t['tt_content']);
		$uid=$a[0];
		$where='uid='.intval($uid);
		// we get associated data
		$db=$GLOBALS['TYPO3_DB'];
		$res=$db->exec_SELECTquery('pi_flexform','tt_content',$where);
		while ($row=$db->sql_fetch_row($res))
		{
			   $flex=$row[0];
		}
		return $flex;
	}
}


// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_flexfill.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_flexfill.php']);
}

?>