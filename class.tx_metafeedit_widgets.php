<?php
/**
* This file defines reusable ajax widgets
*
* @author	Christophe BALISKY <cbalisky@metaphore.fr>
* @package TYPO3
* @subpackage	tx_metaajaxwidgets
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
/**
* This file defines reusable ajax widgets
*
* @author	Christophe BALISKY <cbalisky@metaphore.fr>
* @package TYPO3
* @subpackage	tx_metaajaxwidgets
**/

class tx_metafeedit_widgets {
	var $prefixId;
	var $metafeeditLib;
	private $conf;
	
	function init($prefixId,$metafeeditLib) {
		$this->prefixId=$prefixId;
		$this->metafeeditLib=$metafeeditLib;
	}
	function htmlfriendly($var){
		$chars = array(
        128 => '&#8364;',
        130 => '&#8218;',
        131 => '&#402;',
        132 => '&#8222;',
        133 => '&#8230;',
        134 => '&#8224;',
        135 => '&#8225;',
        136 => '&#710;',
        137 => '&#8240;',
        138 => '&#352;',
        139 => '&#8249;',
        140 => '&#338;',
        142 => '&#381;',
        145 => '&#8216;',
        146 => '&#8217;',
        147 => '&#8220;',
        148 => '&#8221;',
        149 => '&#8226;',
        150 => '&#8211;',
        151 => '&#8212;',
        152 => '&#732;',
        153 => '&#8482;',
        154 => '&#353;',
        155 => '&#8250;',
        156 => '&#339;',
        158 => '&#382;',
        159 => '&#376;');
		$var = str_replace(array_map('chr', array_keys($chars)), $chars, htmlentities(stripslashes($var),ENT_QUOTES));

		return $var;

	}
	function htmlfriendly2($var){
		$chars = array(
 		10 => '',
		39 => '');//\u0027
		return str_replace(array_map('chr', array_keys($chars)), $chars,$var);
	}

	function comboList($prefix='',$id='',$val='',$onChange='',$onSelect='',$label='',$pagesize=10,&$conf,$FN,$onData='') {
		$advancedSearch=$conf['inputvar.']['advancedSearch'];	
		$prefix=$prefix?$prefix:'combolist-';
	    $id=$id?$id:$conf['pluginId'].$FN;
		if(strpos($FN, '.') !== false) {			
			$tab=array();
			$curTable = $this->metafeeditLib->getForeignTableFromField($FN, $conf,'',$tab);
			$table=$curTable['relTable'];
			$FieldN=$curTable['fNiD'];
		} else {
			$table=$conf['table'];
			$FieldN=$FN;
		}
		$val=$advancedSearch[$FN];
		$name=$this->prefixId.'[advancedSearch]['.$conf['pluginId'].']['.$FN.']';
		$callBacks=$onChange.','.$onSelect;
		if ($onData) $callBacks.=','.$onData;
		$onMouseOver=' onmouseover="combolistmouseover(\''.$prefix.$id.'\');"';
		$onMouseOut =' onmouseout="combolistmouseout(\''.$prefix.$id.'\');"';
		$onFocus=' onmouseover="combolistmouseover(\''.$prefix.$id.'\');"';
		$onBlur =' onmouseout="combolistmouseout(\''.$prefix.$id.'\');"';
		$whereClause= $this->metafeeditLib->getWhereClause(0,$table,$FieldN,array(),$conf);
		$foreignTable = $conf['TCAN'][$table]['columns'][$FieldN]["config"]["allowed"]?$conf['TCAN'][$table]['columns'][$FieldN]["config"]["allowed"]:$conf['TCAN'][$table]['columns'][$FieldN]["config"]["foreign_table"];
		$fields=$conf['list.']['advancedSearchAjaxSelector.'][$FN.'.']['dataFields']?$conf['list.']['advancedSearchAjaxSelector.'][$FN.'.']['dataFields']:''; //TODO
		$keyField=''; //TODO
		$labelField=$conf['list.']['advancedSearchAjaxSelector.'][$FN.'.']['labelField']?$conf['list.']['advancedSearchAjaxSelector.'][$FN.'.']['labelField']:$conf['TCAN'][$foreignTable]['ctrl']['label'];
		if ($val) {
			$rec=$GLOBALS['TSFE']->sys_page->getRawRecord($foreignTable,$val);
			//echo serialize($rec);
			$datafields=$fields?$fields:$labelField;
			if ($datafields) {
				$data='';
				$fieldA=array_unique(t3lib_div::trimexplode(',',$datafields));
				foreach ($fieldA as $elt) {
					//echo "$elt : ".serialize($datafields);
					$data.=$data?' '.$rec[$elt]:$rec[$elt]; //&amp;nbsp;
				}
			} else $data=$rec[$labelField];
			$tval=$data;
			//$tval=$rec[$conf['TCAN'][$foreignTable]['ctrl']['label']];
		}
		//$orderBy=" order By $labelField asc ";
		$orderBy="";//TODO
		//echo '###'.$whereClause;
		$onKeyUp =  '"combolistkeyup('.$pagesize.',\''.$id.'\',\''.$prefix.'\',\''.$callBacks.'\',event.keyCode,\''.$foreignTable.'\',\''.$labelField.'\',\'uid\',\''.addSlashes($whereClause).'\',\''.addSlashes($orderBy).'\',1,\''.$fields.'\');" ';
		$onArrowClick = ' onclick="arrowclick(\''.$prefix.$id.'\','.$pagesize.',\''.$id.'\',\''.$prefix.'\',\''.$callBacks.'\',0,\''.$foreignTable.'\',\''.$labelField.'\',\'uid\',\''.addSlashes($whereClause).'\',\''.addSlashes($orderBy).'\',\''.$fields.'\');" ';
		// A remplacer par du JSON...
		$html="<div id=\"cl_$prefix$id\" class=\"meta_cl\">
		    <div class=\"cl_label\">$label</div>
		        <div id=\"cl_inp_$prefix$id\" class=\"meta_cli\">
		            <table>
		                <tr>
		                    <td>
		                        <input type=\"input\" id=\"cl_i$prefix$id\" name=\"".$this->prefixId."[data]\" value=\"$tval\" onkeyup=$onKeyUp $onFocus $onBlur $onMouseOver $onMouseOut class=\"wdgt_input\" autocomplete=\"off\"/>
		                        <input type=\"hidden\" id=\"cl_k$prefix$id\" name=\"".$this->prefixId."[kdata]\"value=\"$val\"/>
		                        <input type=\"hidden\" id=\"cl_n$prefix$id\" name=\"$name\" value=\"$val\"/>
		                    </td>
		                    <td>		  
		                        <span id=\"cl_arrow_$prefix$id\" class=\"wdgt_arr\" $onArrowClick $onMouseOut><i>&nbsp;</i></span>
		                        <span id=\"cl_logo_$prefix$id\" class=\"wdgt_logo\"><i>&nbsp;</i></span>
		                    </td>
		                 </tr>
		              </table>
		        </div>
		        <div id=\"cl_res_$prefix$id\" name=\"cl_res_$prefix$id\" class=\"meta_clr\" $onMouseOver $onMouseOut style=\"display:none;\"></div>
		    </div>"; 
		return $html;
	}
	
	// Cette fonction est le callback principal de notre widget..
	/** Called from xajax handler
	* @param array $data array pf form (post?) data
	* @param tx_xajax_response $objResponse instance of tx_xajax_response
	* @param tx_metafeedit_ajaxlib $handler instance of tx_metafeedit_ajaxlib
	**/
	function handleComboList($data,&$objResponse,&$handler) {
		//print_r($data);
		$idwidget=$data[$this->prefixId]['code'];
		$prefix=$data[$this->prefixId]['prefix'];
		$idcombo=$prefix.$idwidget;
		$search=$data[$this->prefixId]['data'];
		$mode=$data[$this->prefixId]['mode'];
		$pagesize=$data[$this->prefixId]['pagesize'];
		$eventdata=$data[$this->prefixId]['eventdata'];
		$callbacks=$data[$this->prefixId]['callbacks'];
		$table=$data[$this->prefixId]['table'];
		$fields=$data[$this->prefixId]['fields'];
		$labelField=$data[$this->prefixId]['labelField'];
		$numField=$data[$this->prefixId]['numField'];
		$whereField=$data[$this->prefixId]['whereField'];
		$orderBy=$data[$this->prefixId]['orderBy'];
		$labels=$data[$this->prefixId]['labels'];
		// If we pressed enter we select the data...

		if ($eventdata==13) $mode=3;
		$page=($data[$this->prefixId]['page'])?$data[$this->prefixId]['page']:1;
		$callBacksArr=t3lib_div::trimexplode(',',$callbacks);
		
		// We get the rows here
		//$json='{';
		$json=array();
		if ($callBacksArr[0] && $mode!=3) {
			$comboData=call_user_func_array(array($handler,$callBacksArr[0]),array($search,$page,$pagesize,$table,$labelField,$numField,$fields,$whereField));
			if (is_array($comboData))		{
				$nbpages=ceil($comboData['nbrows']/$pagesize);
				$c=count($callBacksArr);
				$json['cbs']=array();
				foreach($callBacksArr as $cb) {
					$c--;
					$json['cbs'][]=array('id'=>$cb);
				}
				
				//print_r($json);
				//how to add format hook here ?

				$json['prefix']=$prefix;
				$json['idwidget']=$idwidget;
				$json['pagesize']=($pagesize?$pagesize:0);
				$json['nbpages']=($nbpages?$nbpages:0);
				$json['page']=($page?$page:0);
				$json['ls']=array();
				if (is_array($comboData['labels'])){
					$c=count($comboData['labels']);
					foreach($comboData['labels'] as $label) {
						$c--;
						$json['ls'][]=array('l'=>$label);
					}
				}
				$json['rs']=array();
				
				//contenu des données
				if (is_array($comboData['rows'])) {
					$c=count($comboData['rows']);
					foreach($comboData['rows'] as $i) {
						$row=$i['row'];
						$cf = count($row);
						$rs=array();
						$rs['id']=$i['id'];
						$rs['d']=$this->htmlfriendly2($i['data']);
						//$rs['d']=str_replace("\n",'',$this->htmlfriendly2($i['data']));
						
						//$rs['d']=addslashes($this->htmlfriendly($i['data']));
						
						foreach($row as $key=>$field) {
							$cf--;
							//$rs['i'.$key]=str_replace('&','&amp;',$this->htmlfriendly2($field));
							$rs['i'.$key]=$this->htmlfriendly2($field);
							//$json.='"i'.$key.'":"'.addslashes($field).'"'.($cf?',':'');
						
						}
						if ($callBacksArr[2]) {
							//echo $callBacksArr[2];
							t3lib_div::callUserFunction($callBacksArr[2],$rs,$this);
						}
						//print_r($rs);
						$json['rs'][]=$rs;
						
						$c--;
					}	
				}
			}
			//$json=json_encode($json);
			$json=json_encode($json);
		}
		
		// We call the setData ...We have hit enter ...
		
		if ($callBacksArr[1] && $mode==3) {
			$ret=call_user_func_array(array($handler,$callBacksArr[1]),array($data,$table,$labelField,$numField));
			$f=$ret['fieldid'];
			$id=$ret['data'];
			$tdata=$ret['tdata'];
			$json='';
			$objResponse->addAssign('cl_i'.$idcombo, 'value', $tdata);
			$objResponse->addAssign('cl_k'.$idcombo, 'value', $id);
			$objResponse->addAssign('cl_n'.$idcombo, 'value', $id);
			$objResponse->addAssign('cl_logo_'.$idcombo, 'className', 'wdgt_logo');
			$objResponse->addScript("document.getElementById('cl_res_$idcombo').style.display='none';");
		} else {
			$fireback =  "combolistfireback('$idcombo', $pagesize,'$idwidget','$prefix','$callbacks',$eventdata,'$table','$labelField','$numField','$whereField','$orderBy','$fields');";
			$objResponse->addScript('combolistdraw(\''.$json.'\');');
			$objResponse->addScript($fireback);
			$objResponse->addScript("document.getElementById('cl_logo_$idcombo').className='wdgt_logo';");
		}
	}
}
 
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_widgets.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_widgets.php']);
}

php?>