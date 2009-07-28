<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2004 Kasper Skårhøj (kasper@typo3.com)
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
 * @author	Kasper Skårhøj <kasper@typo3.com>
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
		t3lib_div::loadTCA($table);
		$FTs=explode(",",$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$params['items'][]=Array( 'Unique ID','uid');
		$params['items'][]=Array('Page ID','pid');
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
		
	}
	
	function mainav(&$params,&$pObj)	{
		global $TCA;
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		t3lib_div::loadTCA($table);
		$FTs=explode(",",$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$params['items'][]=Array('[fieldset<]', '--fsb--;FSB');
		$params['items'][]=Array('[>fieldset]', '--fse--;FSE');
		$params['items'][]=Array( 'Unique ID','uid');
		$params['items'][]=Array('Page ID','pid');
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
		t3lib_div::loadTCA($table);
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
		t3lib_div::loadTCA($table);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$this->getFieldsFT($params,$table,'');
		$foreignTables=@$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF'];
		$fta=explode(',',$foreignTables);
        foreach($fta as $ft) {
            $ftable=$TCA[$table]['columns'][$ft]['config']['foreign_table'];            
            $this->getFieldsFT($params,$ftable,$ft);
        }    
           
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
		t3lib_div::loadTCA($table);
		$flex=$this->getFlex();
		if (!$flex) return;
		$flexarr=t3lib_div::xml2array($flex);
		$table=@$flexarr['data']['sQuickStart']['lDEF']['fetable']['vDEF'];
		$FTs=explode(",",@$flexarr['data']['sDEF']['lDEF']['foreignTables']['vDEF']);
		$params['items']=array();
		$params['items'][]=Array('', '');
		$params['items'][]=Array('[Tab]', '--div--;Tab');
		$params['items'][]=Array('[fieldset<]', '--fsb--;FSB');
		$params['items'][]=Array('[>fieldset]', '--fse--;FSE');
		$params['items'][]=Array( 'Unique ID','uid');
		$params['items'][]=Array('Page ID','pid');
		$this->getFields($params,$table,'');
		if ($FTs) {
			foreach($FTs as $FTRel) {
				$FT=@$TCA[$table]['columns'][$FTRel]['config']['foreign_table'];
				$this->getFields($params,$FT,$FTRel);
			}
		}
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
                                        		$label = t3lib_div::fixed_lgd(ereg_replace(':$','',$GLOBALS['LANG']->sL($config['label'])),30).' ('.$prefix.$key.')';
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
	 * [Describe function...]
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$rel: ...
	 * @return	[type]		...
	 */
	function getFields_OB(&$params,$table,$rel,&$flexarr) {
		global $TCA;
		$prefix=$rel?$rel.'.':'';
		t3lib_div::loadTCA($table);
		$params['items'][]=Array( 'Unique ID : ASC',$prefix.'uid:asc');
		$params['items'][]=Array( 'Unique ID : DESC',$prefix.'uid:desc');
		$params['items'][]=Array('Page ID : ASC',$prefix.'pid:asc');
		$params['items'][]=Array('Page ID : DESC',$prefix.'pid:desc');

		if (is_array($TCA[$table]['columns']))  {
			foreach($TCA[$table]['columns'] as $key => $config)     {
                                        		$label = t3lib_div::fixed_lgd(ereg_replace(':$','',$GLOBALS['LANG']->sL($config['label'])),30).' ASC ('.$prefix.$key.')';
                                        		$params['items'][]=Array($label, $prefix.$key.':asc' );
                                        		$label = t3lib_div::fixed_lgd(ereg_replace(':$','',$GLOBALS['LANG']->sL($config['label'])),30).' DESC ('.$prefix.$key.')';
                                        		$params['items'][]=Array($label, $prefix.$key.':desc' );
                        		}
                		}
                		
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
	 * getFieldsFT
	 *
	 * @param	[type]		$$params: ...
	 * @param	[type]		$table: ...
	 * @return	[type]		...
	 */
	function getFieldsFT(&$params,$table,$prefix='') {

		global $TCA;
		$ta=array();
        t3lib_div::loadTCA($table);
        if (is_array($TCA[$table]['columns']))  {
            foreach($TCA[$table]['columns'] as $key => $config)     {
				if ($config['config']['foreign_table']) {
                    $label = t3lib_div::fixed_lgd(ereg_replace(':$','',$GLOBALS['LANG']->sL($config['label'])),30).' ('.($prefix?$prefix.'.':'').$key.')';
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
               	// on récupère le uid  du plugin courant que l'on édite
                		$t=t3lib_div::_GP('edit');
                		$a=array_keys($t['tt_content']);
                		$uid=$a[0];
                		$where='uid='.intval($uid);
                		// on récupère les données du flexform associé
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
