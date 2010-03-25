<?php
/**
* This file defines reusable ajax libraries
*
* @author	Christophe BALISKY <cbalisky@metaphore.fr>
* @package TYPO3
* @subpackage	tx_metafeeditajaxlib
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
* This file defines reusable ajax library
*
* @author	Christophe BALISKY <cbalisky@metaphore.fr>
* @package TYPO3
* @subpackage	tx_metafeedit_ajaxlib
**/

class tx_metafeedit_ajaxlib {
	var $prefixId='tx_metafeedit';
	/**
	 * Cette fonction renvoie les resultat de la recherche
	 *
	 * @param	[type]		$idperson: ...
	 * @return	[type]		...
	 */
	
	function handleData($search,$page,$pagesize,$table,$labelField,$numField,$fields='',$whereField='',$orderby='',$labels='') {
		$html='';
		if ($table=='undefined') return array();
		if (!$labelField) die("No label field for table $table check your combolist call");
		//permet de savoir si on a l'uid ou non, uid : false, pas d'uid: true
		$sameField=$numField==$labelField?true:false;
		//texte de vidage de recherche
		$empty='<effacer>';
		$search=$search==$empty?'':$search;
		// On concatène les champs, et on en verifie l'unicité !
		$sqlfields=$fields?$numField.','.$fields:"$numField,$labelField";
		$datafields=$fields?$fields:$labelField;
		$fieldsArray=array_unique(t3lib_div::trimexplode(',',$sqlfields));
		$sqlfields=implode(',',$fieldsArray);
		$comboData=array();
		$where=" $table.deleted=0 AND $table.";
		$where.=$table=='fe_users'?"disable":"hidden";
		$where.='=0';

		//calculer comment ajouter un userfunc ici afin de récupérer les entité juridique partenaire par exemple.
		//if ($fe_adminLib->conf['userFunc_ajaxAfterWhere']) t3lib_div::callUserFunction($fe_adminLib->conf['userFunc_afterSave'], $var_temp_array, $fe_adminLib);

		if ($search) { // Modif by CMD - gestion multichamp de recherche
			if (intval($search) > 0) {
				$where.=" AND $table.$numField like '$search%'";
				$orderby="$table.$numField asc";
			} else {
				$labelArray=array_unique(t3lib_div::trimexplode(',',$labelField));
				$first=true;
				foreach ($labelArray as $label) {
					$where.=(!$first?' OR ':' AND (')."$table.$label like '$search%'";
					$orderby.=(!$first?', ':'')." $table.$label asc";
					$first=false;
				}
				$where.=')';
			}
		} else {
			$labelArray=array_unique(t3lib_div::trimexplode(',',$labelField));
			$firstOrder=true;
			foreach ($labelArray as $label) {
				$orderby.=(!$firstOrder?', ':'')." $table.$label asc";
				$firstOrder=false;
			}
		}
		$start = ($page)?($page-1)*$pagesize:0;
		/*repère*/
		$distinct=$sameField?'DISTINCT ':'';
		if ($where)		{
			//on fait sauter l'ordre by existant pour mettre en place le nouveau
			if (strpos(strtoupper($whereField), 'ORDER BY')!==false) {
				$pos = strpos($whereField, 'ORDER BY');
				$whereField = $pos==0?'':substr($whereField, 0, $pos-1);
			}
			$where.=" $whereField";
			//$where="";
			//echo $GLOBALS['TYPO3_DB']->SELECTquery($distinct.$sqlfields, $table , $where, '', $orderby);
			$res  	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($distinct.$sqlfields, $table , $where, '', $orderby);
			$nbrows = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
			//echo "###$nbrows";

			$comboData['nbrows']=$nbrows;
			$rows  	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($distinct.$sqlfields, $table , $where, '', $orderby, "$start,$pagesize");
			//echo $GLOBALS['TYPO3_DB']->SELECTquery($distinct.$sqlfields, $table , $where, '', $orderby, "$start,$pagesize");
			if ($nbrows > 0) {
				//ajout d'une ligne vide pour supprimer la recherche
				$emptyArr=array();
				if (!$sameField) $emptyArr[]='';
				foreach(array_unique(t3lib_div::trimexplode(',',$datafields)) as $tmp) $emptyArr[]=$empty;
				$item['row']=$emptyArr;
				$item['id']='';
				$item['data']=$empty;
				$comboData['rows'][]=$item;
				//ensuite on ajoute la liste
				$comboData['labels']=t3lib_div::trimexplode(',',$sqlfields);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($rows)){
					$item=array();
					if ($datafields) {
						$data='';
						$fieldA=array_unique(t3lib_div::trimexplode(',',$datafields));
						$i=$sameField?0:1;
						foreach ($fieldA as $elt) {
							$data.=$data?' '.$row[$i]:$row[$i]; //&amp;nbsp;
							$i++;
						}
					} else $data=$row[($sameField?0:1)];
					$item['row']=$row;
					$item['id']=$row[0];
					$item['data']=$data;
					//how to add format hook here ?
					$comboData['rows'][]=$item;
				}
			}
		}
		return $comboData;
	}

	function old_handleData($search,$page,$pagesize,$table,$labelField,$numField,$fields='',$whereField='',$orderby='',$labels='') {
		$html='';
		if ($table=='undefined') return array();
		
		// On concatène les champs, et on en verifie l'unicité !
		$sqlfields=$fields?$numField.','.$fields:"$numField,$labelField";
		$datafields=$fields?$fields:$labelField;
		//$fields="$numField,$labelField";
		$fieldsArray=array_unique(t3lib_div::trimexplode(',',$sqlfields));
		$sqlfields=implode(',',$fieldsArray);
		//$fields="$numField,$labelField";
		//echo ">mm $fields<";
		$comboData=array();
		$where=" $table.deleted=0 AND $table.";
		$where.=$table=='fe_users'?"disable":"hidden";
		$where.='=0';
		if ($search) { // Modif by CMD - gestion multichamp de recherche
			if (intval($search) > 0) {
				$where.=" AND $table.$numField like '$search%'";
				$orderby="$table.$numField asc";
			} else {
				$labelArray=array_unique(t3lib_div::trimexplode(',',$labelField));
				$first=true;
				foreach ($labelArray as $label) {
					$where.=(!$first?' OR ':' AND (')."$table.$label like '$search%'";
					$orderby.=(!$first?', ':'')." $table.$label asc";
					$first=false;
				}
				$where.=')';
			}
		}
		$start = ($page)?($page-1)*$pagesize:0;
		if ($where)		{
			if (strpos($whereField, 'ORDER BY')!==false) {
				$pos = strpos($whereField, 'ORDER BY');
				$whereField = $pos==0?'':substr($whereField, 0, $pos-1);
			}
			$where.=" $whereField";
			$rows  	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($sqlfields, $table , $where, '', '');
			$nbrows=$GLOBALS['TYPO3_DB']->sql_num_rows($rows);
			$comboData['nbrows']=$nbrows;
			$rows  	= $GLOBALS['TYPO3_DB']->exec_SELECTquery($sqlfields, $table , $where, '', $orderby, "$start,$pagesize");
			if ($nbrows > 0) {
				$comboData['labels']=t3lib_div::trimexplode(',',$sqlfields);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($rows)){
					$item=array();
					//echo $row;
					
					if ($datafields) {
						$data='';
						$fieldA=array_unique(t3lib_div::trimexplode(',',$datafields));
						$i=1;
						foreach ($fieldA as $elt) {
							$data.=$data?' '.$row[$i]:$row[$i]; //&amp;nbsp;
							$i++;
						}
					} else $data=$row[1];
					$item['row']=$row;
					$item['id']=$row[0];
					$item['data']=$data;
					$comboData['rows'][]=$item;
				}
			}
		}
		return $comboData;
	}
	
  /**
    * on choisit une donnée
    *
    * @param	array		...
    * @param	object		...
    * @return	void		...
    */

    function setData($data,$table,$labelField,$numField) {
		$idwidget=trim($data[$this->prefixId]['code']);
		$operateur=$GLOBALS['TSFE']->fe_user->user['uid'];
		$id=$data[$this->prefixId]['data'];
		//print_r($data);
		$tdata=$data[$this->prefixId]['tdata'];
		$prefix=trim($data[$this->prefixId]['prefix']);
		$ret=array();
		$ret['fieldid']=prefix+idwidget;
		$ret['data']=$id;
		$ret['tdata']=$tdata;
		return $ret;
  }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_ajaxlib.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_ajaxlib.php']);
}
php?>