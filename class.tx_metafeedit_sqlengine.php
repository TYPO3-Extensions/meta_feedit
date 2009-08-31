<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Christophe BALISKY (cbalisky@metaphore.fr)
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
* This is a API for handling sql requests .
* 
* @author      Christophe BALISKY <cbalisky@metaphore.fr>
*/

// Necessary includes

class tx_metafeedit_sqlengine {
	var $prefixId = 'tx_metafeedit';
	var $feadminlib;

    /**
    * iniatialising Lib Object
    *
    * @param	[type]		$title: ...
    * @param	[type]		$content: ...
    * @param	[type]		$DEBUG: ...
    * @return	[type]		...
    */
    
 	function debug($title,$content,&$DEBUG) {
 		if (is_array($content)) $content=t3lib_div::view_array($content);
 		if (is_object($content)) {
 			//$content='';
 			ob_start();
 			print_r($content);
			$content ='<pre>'. ob_get_contents().'</pre>';
			ob_clean();
		}

 		$DEBUG.=($title?"<br/><hr/><h3>$title</h3><br/>":'').$content;
	}

	/* Group By Field Break Footer ...
	*/
	function getGroupByFooterSums(&$conf,$prefix,&$GBMarkerArray,$fN,&$sql,&$row,$end,&$DEBUG) {
		if ($conf['list.']['sumFields']) {
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
				}	
				else
				{
						$sumSQLFields.=$sumSQLFields?",sum($fieldName) as sum_$fieldName":"sum($fieldName) as sum_$fieldName";

				}				
			}
			$sumSQLFields.=', count(*) as metafeeditnbelts';
			if ($sql['groupBy']) $sumSQLFields.=','.$conf['table'].'.*';
			$WHERE=$sql['where'];
		 	$fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFieldBreaks']);
		 	$i=0;
		 	$GBA=t3lib_div::trimexplode(',',$sql['gbFields']);
			$GROUPBY=$sql['groupBy'];
			$endgb=0;
	    foreach($fNA as $fNe) {
	    	
		    $fN2=t3lib_div::trimexplode(':',$fNe);
				$fNi=$fN2[0];
					if($fN2[2] || $endgb) {
				    	//$WHERE.=" and $fN2[0]=".$row[$fNi];
							/* See below
							$calcField=$conf['list.']['sqlcalcfields.'][$fNi]; // TO BE IMPROVED
							if ($calcField) {
								$sumSQLFields.=$sumSQLFields?",$calcField as $fNi":"$calcField as $fNi";
							}
							*/
							if (!$endgb) {
					    	$GROUPBY=$GROUPBY?$GROUPBY.','.$fN2[0]:$fN2[0];
					    	$HAVING=$HAVING?$HAVING." and $fN2[0]=".$row[$fNi]:" HAVING $fN2[0]=".$row[$fNi];
					    }
					} else {
				    if (strpos($fNi,'.')===false && $row[$fNi]) {
						//$table = $this->getForeignTableFromField($fNi, $conf);
				    	$WHERE.=" and $conf[table].$fNi=".$row[$fNi];
				    	$GROUPBY=$GROUPBY?$GROUPBY.','.$conf[table].'.'.$fNi:$conf[table].'.'.$fNi;
				    } else {
			      	$WHERE.=$this->makeSQLJoinWhere($conf['table'],$fNi,$conf,$row[$fNi]);
				  	}
				  }
				
				// We stop at our depth level ...
	      if ($fNi==$fN) {
	      	$endgb=1;
	    	}	      
		   }
		  $GROUPBY=strpos($GROUPBY,"GROUP BY")?$GROUPBY:($GROUPBY?" GROUP BY ".$GROUPBY:'');
		  if ($conf['list.']['havingString']) $HAVING=$HAVING?$HAVING.' AND '.$conf['list.']['havingString']:' HAVING '.$conf['list.']['havingString'];
		  
		  // Check group by fields for calculated fields  ..
			/*if (is_array($conf['list.']['sqlcalcfields.'])) foreach ($conf['list.']['sqlcalcfields.'] as $fn=>$calcField) {
					$sumSQLFields.=$sumSQLFields?",$calcField as $fn":"$calcField as $fn";
			}*/
						
						
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sumSQLFields, $sql['fromTables'], '1 '.$WHERE.$GROUPBY.$HAVING);		
	  	if ($conf['debug.']['sql']) $this->debug('Group by footer',$GLOBALS['TYPO3_DB']->SELECTquery($sumSQLFields, $sql['fromTables'], '1 '.$WHERE.$GROUPBY.$HAVING),$DEBUG);
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
		
			 $GBMarkerArray["###".$prefix."_".$fN."_FIELD_$fieldName###"]= $value['sum_'.$fieldName];
			 //$i++;
			}
			 $GBMarkerArray["###".$prefix."_".$fN."_FIELD_metafeeditnbelts###"]= $value['metafeeditnbelts'];
			
			//$sumcontent=$this->cObj->stdWrap(trim($this->cObj->substituteMarkerArray($itemSumCode, $this->markerArray)),$conf['list.']['sumWrap.']);
			//$content=$this->cObj->substituteSubpart($content,'###SUM_FIELDS###',$sumcontent);
		}
		return true;
	}
		/**
		* *******************************************************************************************
		* SQL FUNCTIONS
		* *********************************************************************************************/

		// this function handles Foreign table relations (level 1) , it allows us to get foreign table name from field
		// it returns foreigntable name and name of field in foreign table
		// Ex : if editing table fe_users, for relation usergroups.uid this function would return :
		// $ret['table']='fe_groups'
		// $ret['fNiD']='uid'

		function getForeignTableFromField($fN, &$conf,$table='') {
			$ret = array();
			$fNA = t3lib_div::trimexplode('.', $fN);
			$fNiD = $fN;
			$table = $table?$table:$conf['table'];
			if (count($fNA) == 2) {
				// Foreign Table
				$table = $conf['TCAN'][$table]['columns'][$fNA[0]]['config']['foreign_table'];
				$fNiD = $fNA[1];
				$ret['ft'] = 1;
			} elseif (count($fNA) > 2) {
				die("We don't handle more than one foreign table level !");
			}
			$ret['table'] = $table;
			$ret['fNiD'] = $fNiD;
			return $ret;
		}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$relation: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$Tables: ...
	 * @return	[type]		...
	 */
		function makeSQLJoin($table,$relation,&$conf,$Tables) {
			return "";
	  }
	  
		function makeSQLJoinWhere($table,$relation,&$conf,$val) {
			$relA=t3lib_div::trimexplode('.',$relation);
			$c=count($relA);
			$c--;
			foreach($relA as $rel) {
				if ($c<=0) break;
				$c--;
				$table=$conf['TCAN'][$table]['columns'][$rel]["config"]["foreign_table"];
			}
			return " and $table.$rel=$val";
	  }

    /**
    * DBmayFEUserEditSelectMM
    *
    * @param	[type]		$table: ...
    * @param	[type]		$fe_user: ...
    * @param	[type]		$allowedGroups: ...
    * @param	[type]		$fe_userEditSelf: ...
    * @param	[type]		$mmTable: ...
    * @return	[type]		...
    */
	function DBmayFEUserEditSelectMM($table,$fe_user,$allowedGroups,$fe_userEditSelf, &$mmTable,&$conf) {
		$ret='';
		if ($conf['debug']) echo t3lib_div::view_array(array('checkT3Rights'=>$conf['checkT3Rights']));

		if ($conf['checkT3Rights']) {

			$ret=$this->MetaDBmayFEUserEditSelect($table,$fe_user,$allowedGroups,$fe_userEditSelf,$mmTable,$conf);
		} elseif ($conf['enableColumns']) {

			$ret= $GLOBALS['TSFE']->sys_page->enableFields($table,$show_hidden?$show_hidden:($table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
		}
		// multi_language
        if ($conf['TCAN'][$table]['ctrl']['languageField'] && $conf['TCAN'][$table]['ctrl']['transOrigPointerField']) {
								       $ret .= ' AND '.$conf['TCAN'][$table]['ctrl']['transOrigPointerField'].'=0';
	    }
		return $ret;
	}


		/**
		* MetaDBmayFEUserEditSelect
		*
		* @param	[type]		$table: ...
		* @param	[type]		$feUserRow: ...
		* @param	[type]		$allowedGroups: ...
		* @param	[type]		$feEditSelf: ...
		* @param	[type]		$mmTable: ...
		* @param	[type]		$conf: ...
		* @return	[type]		...
		*/
		function MetaDBmayFEUserEditSelect($table,$feUserRow,$allowedGroups='',$feEditSelf=0, &$mmTable,&$conf)       {
                // Returns where-definition that selects user-editable records.
                $groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$feUserRow['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $feUserRow['usergroup'];
                $OR_arr=array();

                // points to the field (integer) that holds the fe_users-id of the creator fe_user

		  if ($conf['debug']) echo t3lib_div::view_array(array('fe_cruser_id'=>$conf['TCAN'][$table]['ctrl']['fe_cruser_id']));

                if ($conf['TCAN'][$table]['ctrl']['fe_cruser_id'])    {
			$mmTable=$conf['TCAN'][$table]['columns'][$conf['TCAN'][$table]['ctrl']['fe_cruser_id']]['config']['MM'];

                	if ($mmTable) {
                		$OR_arr[]=$mmTable.'.uid_local='.$table.'.'.$conf['uidField'].' and '.$mmTable.'.uid_foreign='.$feUserRow['uid'];
                				} else {
                          $OR_arr[]=$table.'.'.$conf['TCAN'][$table]['ctrl']['fe_cruser_id'].'='.$feUserRow['uid'];
                        }
                }

                // points to the field (integer) that holds the fe_group-id of the creator fe_user's first group
                if ($conf['TCAN'][$table]['ctrl']['fe_crgroup_id'])   {
                        $values = t3lib_div::intExplode(',',$groupList);
                        while(list(,$theGroupUid)=each($values))        {
                                if ($theGroupUid)       {$OR_arr[]=$table.'.'.$conf['TCAN'][$table]['ctrl']['fe_crgroup_id'].'='.$theGroupUid;}
                        }
                }

		  if ($conf['debug']) echo t3lib_div::view_array(array('feEditSelf '=>$feEditSelf ));

                // If $feEditSelf is set, fe_users may always edit them selves...
                if ($feEditSelf && $table=='fe_users')  {
                        $OR_arr[]=$table.'.uid='.intval($feUserRow['uid']);
                }

                //$whereDef=' AND 1=0';
                if (count($OR_arr))     {
                        $whereDef=' AND ('.implode(' OR ',$OR_arr).')';
                        if ($conf['TCAN'][$table]['ctrl']['fe_admin_lock'])   {
                                $whereDef.=' AND '.$conf['TCAN'][$table]['ctrl']['fe_admin_lock'].'=0';
                        }
                }
								// here we handle enable columns activation

								if ($conf['enableColumns']) $whereDef.= $GLOBALS['TSFE']->sys_page->enableFields($table,$show_hidden?$show_hidden:($table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));

                return $whereDef;
        }

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$fields: ...
	 * @param	[type]		$noWSOL: ...
	 * @return	[type]		...
	 */
		function getRawRecord($table,$uid,&$conf,$fields='*',$noWSOL=FALSE)    {

               $uid = intval($uid);
               if (is_array($conf['TCAN'][$table]) || $table=='pages') {        // Excluding pages here so we can ask the function BEFORE TCA gets initialized. Support for this is followed up in deleteClause()...
                       $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $conf['uidField'].'='.intval($uid).$GLOBALS['TSFE']->sys_page->deleteClause($table));
                       if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                                if (!$noWSOL)   {
                                       $GLOBALS['TSFE']->sys_page->versionOL($table,$row);
									   }
                         }
                }
        					if ($conf['TCAN'][$table]['ctrl']['languageField'] && $conf['TCAN'][$table]['ctrl']['transOrigPointerField'] && $GLOBALS['TSFE']->sys_language_uid ) {
                	$row=$GLOBALS['TSFE']->sys_page->getRecordOverlay($table,$row, $GLOBALS['TSFE']->sys_language_content,$GLOBALS['TSFE']->sys_language_contentOL);
								}
                if (is_array($row))     return $row;

         }


	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$fe_user: ...
	 * @param	[type]		$allowedGroups: ...
	 * @param	[type]		$fe_userEditSelf: ...
	 * @param	[type]		$mmTable: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function DBmayFEUserEditSelect($table,$fe_user,$allowedGroups,$fe_userEditSelf, &$mmTable,&$conf) {

                $ret='';

                if (!$conf['checkT3Rights'])  return $ret;

                //        $ret=$this->cObj->DBmayFEUserEditSelect($table,$fe_user,$allowedGroups,$fe_userEditSelf);
                // Returns where-definition that selects user-editable records.
                $groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$feUserRow['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $feUserRow['usergroup'];
                $OR_arr=array();

                // points to the field (integer) that holds the fe_users-id of the creator fe_user
                if ($conf['TCAN'][$table]['ctrl']['fe_cruser_id'])    {
                        $OR_arr[]=$conf['TCAN'][$table]['ctrl']['fe_cruser_id'].'='.$feUserRow['uid'];
                }

                // points to the field (integer) that holds the fe_group-id of the creator fe_user's first group
                if ($conf['TCAN'][$table]['ctrl']['fe_crgroup_id'])   {
                        $values = t3lib_div::intExplode(',',$groupList);
                        while(list(,$theGroupUid)=each($values))        {
                                 if ($theGroupUid)       {$OR_arr[]=$conf['TCAN'][$table]['ctrl']['fe_crgroup_id'].'='.$theGroupUid;}
                         }
                }
                // If $feEditSelf is set, fe_users may always edit them selves...
                if ($feEditSelf && $table=='fe_users')  {
                         $OR_arr[]='uid='.intval($feUserRow['uid']);
               }

               //$whereDef=' AND 1=0';
               if (count($OR_arr))     {
                      $whereDef=' AND ('.implode(' OR ',$OR_arr).')';
                      if ($conf['TCAN'][$table]['ctrl']['fe_admin_lock'])   {
                                $whereDef.=' AND '.$conf['TCAN'][$table]['ctrl']['fe_admin_lock'].'=0';
                      }
               }
               return $whereDef;
         }

	/**
	 * DBmayFEUserEdit
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$origArr: ...
	 * @param	[type]		$fe_user: ...
	 * @param	[type]		$allowedGroups: ...
	 * @param	[type]		$fe_userEditSelf: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function DBmayFEUserEdit($table,$origArr,$fe_user,$allowedGroups,$fe_userEditSelf,&$conf) {
		$ok=1;
		if ($conf['checkT3Rights']) {
			//$ret=$this->cObj->DBmayFEUserEdit($table,$origArr,$fe_user,$allowedGroups,$fe_userEditSelf);
			$groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$fe_user['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $fe_user['usergroup'];
			$ok=0;
  			// points to the field that allows further editing from frontend if not set. If set the record is locked.
			 if (!$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock'] || !$origArr[$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock']])       {
							 // points to the field (integer) that holds the fe_users-id of the creator fe_user
					 if ($GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id'])    {
						$MMT = $GLOBALS['TCA'][$table]['columns'][$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id']]['config']['MM'];
					 	if ($MMT) { //si on est dans une MM faut d'abord récup les id de la table MM
							$FTUid=$fe_user['uid'];
							$LTUid=$origArr['uid'];
							$MMTreq = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$MMT,$MMT.'.uid_local='.$LTUid.' and '.$MMT.'.uid_foreign='.$FTUid);
							$resu=$GLOBALS['TYPO3_DB']->sql_num_rows($MMTreq);
							if ($resu>=1) {
								$ok=1;
							}
						} else { // not a MM
							 $rowFEUser = intval($origArr[$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id']]);
							 if ($rowFEUser && $rowFEUser==$fe_user['uid'])        {
									 $ok=1;
							 }
						}
					 }
					// If $fe_userEditSelf is set, fe_users may always edit themselves...
					 if ($fe_userEditSelf && $table=='fe_users' && !strcmp($fe_user['uid'],$origArr['uid']))        {
							 $ok=1;
					 }

					 // points to the field (integer) that holds the fe_group-id of the creator fe_user's first group
					 if ($GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id'])   {
							 $rowFEUser = intval($row[$GLOBALS['TCA'][$table]['ctrl']['fe_crgroup_id']]);
							 if ($rowFEUser) {
									 if (t3lib_div::inList($groupList, $rowFEUser))  {
											 $ok=1;
									 }
							 }
					 }
			 }
		}
		return $ok;
	}


	// SQL Functions

    /**
    * getMMUids
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$sql: ...
    * @return	[type]		...
    */
    
	function getMMUids(&$conf,$table,$fN,$dataArr=0) {
		$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $conf['TCAN'][$table]['columns'][$fNiD]["config"]["MM"], 'uid_local=\''.$dataArr[$conf['uidField']].'\'', '');
		if (mysql_error()) debug(array(mysql_error(), $query), 'processDataArray()::field='.$fN);
		if (mysql_num_rows($MMres) != $dataArr[$fN]) debug("Wrong number of selections reached");
		while ($MMrow = mysql_fetch_assoc($MMres)) $uids[] = $MMrow["uid_foreign"];
		return $uids;
	}

    /**
    * getExtraFields
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$sql: ...
    * @return	[type]		...
    */
    
	function getExtraFields(&$conf,&$sql) {
		if ($conf['list.']['extraFields']) {
			$FTA=t3lib_div::trimexplode(',',$conf['list.']['extraFields']);
			foreach($FTA as $FTi) {
				if (strpos($FTi,'.')>0)
				{
				    // foreign relations
					$FTAA=t3lib_div::trimexplode('.',$FTi);
					$FT=$FTAA[0];
					$FN=$FTAA[1];
					$FTT=$conf['TCAN'][$conf['table']]['columns'][$FT]['config']['foreign_table'];
					$FTT=$this->getTableAlias($sql,$FTT,$FTi);
					//$sql['fromTables'].=','.$FTT;
					$sql['joinTables'][]=$FTT;
					$sql['fields'].=','.$FTT.'.'.$FN;
					$sql['fieldArray'][]=$FTT.'.'.$FN;
				} else {
				    // master table fields  ...
				    
					$sql['fields'].=','.$conf['table'].'.'.$FTi;
					$sql['fieldArray'][]=$conf['table'].'.'.$FTi;
			    }
			}
			//$sql['extraFields']
		}
	}

    /**
    * getFieldJoin
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$sql: ...
    * @param	[type]		$masterTable: ...
    * @param	[type]		$fN: ...
    * @return	[type]		...
    */
	 
	function getFieldJoin(&$conf,&$sql,$masterTable,$fN) {
		$FT=$conf['TCAN'][$masterTable]['columns'][$fN]['config']['foreign_table'];
		$ret=$fN;
		if ($FT) {
			$MM=$conf['TCAN'][$masterTable]['columns'][$fN]["config"]["MM"];
			if (!$MM) {
				$this->getTableAlias($sql,$FT,$fN);
				if ($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']>1) {
					$sql['where'].=' AND FIND_IN_SET('.$FT.'.uid,'.$masterTable.'.'.$fN.')>0 ';
				} else {
				    $sql['where'].=' AND '.$FT.'.uid='.$masterTable.'.'.$fN.' ';
				}
				//$sql['fromTables'].=','.$FT;
				$sql['joinTables'][]=$FT;
		 		$this->getParentJoin($conf,$sql,$FT); //TOBEREMOVED ???
				
			} else {
					$this->getTableAlias($sql,$MM,$fN);
					$uidLocal = isset($conf['TCAN'][$masterTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$masterTable]['ctrl']['uidLocalField']:'uid';
					$sql['where'].=" AND ".$masterTable.'.'.$uidLocal;
					//$sql['where'].= ($uidLocal == "uid")?$fN:$uidLocal;
					$sql['where'].= '='.$MM.'.uid_local';
					//$sql['fromTables'].=','.$MM;
					$sql['joinTables'][]=$MM;
					$sql['addFields'].=$sql['addFields']?','.$MM.'.uid_foreign as '.$fN.'_uid_foreign':$MM.'.uid_foreign as '.$fN.'_uid_foreign';
					$ret=$fN.'_uid_foreign';
		 }
		}
		return $ret;
	}

    /**
    * Field Value Join : locks column value ????
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$sql: ...
    * @param	[type]		$masterTable: ...
    * @param	[type]		$fN: ...
    * @param	[type]		$val: ...
    * @return	[type]		...
    */
    
	function getFieldValJoin(&$conf,&$sql,$masterTable,$fN,$val) {
		$FT=$conf['TCAN'][$masterTable]['columns'][$fN]['config']['foreign_table'];
		$ret=$fN;
		if ($FT) {
			$MM=$conf['TCAN'][$masterTable]['columns'][$fN]["config"]["MM"];
			if (!$MM) { //modif by CMD - ajout du $
				$this->getTableAlias($sql,$FT,$fN);
				if ($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']>1) {
					$sql['where'].=' AND FIND_IN_SET('.$FT.'.uid,'.$masterTable.'.'.$fN.')>0 ';
				} else {
					$sql['where'].=' AND '.$FT.'.uid='.$masterTable.'.'.$fN.' ';
				}
			} else {
				$this->getTableAlias($sql,$MM,$fN);
				$uidLocal = isset($conf['TCAN'][$masterTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$masterTable]['ctrl']['uidLocalField']:'uid';
				$sql['where'].=" AND ".$masterTable.'.'.$uidLocal;
				//$sql['where'].= ($uidLocal == "uid")?$fN:$uidLocal;
				$sql['where'].= '='.$MM.'.uid_local';
				//$sql['fromTables'].=','.$MM;
				$sql['addFields'].=$sql['addFields']?','.$MM.'.uid_foreign as '.$fN.'_uid_foreign':$MM.'.uid_foreign as '.$fN.'_uid_foreign';
				$ret=$fN.'_uid_foreign';
		 }
		}
		return $ret;
	}

    /**
    * Makes foreign table join for sql request
    *
    * @param	[array]		$conf: configuration array
    * @param	[array]		$sql: sql array
    */
    
	function getForeignJoin(&$conf,&$sql) {
		if ($conf['foreignTables']) {
			$table=$conf['table'];
			$FTA=t3lib_div::trimexplode(',',$conf['foreignTables']);
			foreach($FTA as $FT) {
				$FTT=$conf['TCAN'][$table]['columns'][$FT]['config']['foreign_table'];
				//$sql['fromTables'].= ','.$FTT;
				$alias=$this->getTableAlias($sql,$FTT,$FT);

				foreach(t3lib_div::trimexplode(',',$conf['list.'][show_fields]) as $sF) {
					$rA=t3lib_div::trimexplode('.',$sF);
					//modif by CMD - pour gérer le champ dans la table s'il nest pas la table étrangère
					if ($rA[0]==$FT && isset($rA[1])) {
						$sql['fields'].= ','.$alias.'.'.$rA[1]." as '$FT.$rA[1]'";
						$sql['fieldArray'][]=$alias.'.'.$rA[1]." as '$FT.$rA[1]'";
					}
				}
				
				if (!$conf['TCAN'][$table]['columns'][$FT]['config']['MM']) {
					if ($conf['TCAN'][$table]['columns'][$FT]['config']['size']>1) {
						$sql['where'].=' AND FIND_IN_SET('.$FTT.'.uid,'.$table.'.'.$FT.')>0 ';
						//$sql['fromTables'].=$sql['fromTables']?','.$FTT:$FTT;
						$sql['joinTables'][]=$FTT;
					} else {
					//$sql['where'].=' AND '.$FTT.'.uid='.$table.'.'.$FT.' ';
					$sql['join'].=' LEFT JOIN '.$FTT.($sql['fields.'][$FT.'.']['alias']?' as '.$sql['fields.'][$FT.'.']['alias']:'').' ON '.$alias.'.uid='.$table.'.'.$FT.' ';
					$sql['joinTables'][]=$FTT;

				 }
				} else {
					//ancienne version du lien
					//$AND_arr.=" AND ".$table.'.'.$FT.'='.$FTT.'.uid';
					//modif par CMD
					//rajout de liaison pour les tables de meta_oscommerce
					//on lie les tables en fonction du champ d'id de liaison

					$MMTable=$conf['TCAN'][$table]['columns'][$FT]['config']['MM'];
					$this->getTableAlias($sql,$MMTable,$FT);
					$uidLocal = isset($conf['TCAN'][$FTT]['ctrl']['uidLocalField'])?$conf['TCAN'][$FTT]['ctrl']['uidLocalField']:'uid';
					$sql['join'].=' JOIN '.$MMTable.' ON '.$MMTable.'.uid_local ='.$table.'.uid JOIN '.$FTT.' ON '.$FTT.'.uid='.$MMTable.'.uid_foreign';
					$sql['joinTables'][]=$MMTable;
				}
			}
			//TODO
			//$sql['fromTables']=implode(',',array_diff(t3lib_div::trimexplode(',',$sql['fromTables']),t3lib_div::trimexplode(',',$sql['joinTables'])));
			//$sql['fromTables'].= $sql['join'];
			$sql['foreignWhere']=$sql['where'];
		}
	}
	
    /**
    * getTableAlias : get alias for foreign table of field
    *
    * @param	[array]		$sql: sql array
    * @param	[string]	$table: name of master table
    * @param	[string]	$field: field name as selected by user in flexform ..., filedname1.fieldname2 and so on ...
    * @return	[string]	$alias : name of foreign table alias ...
    */
    
	function getTableAlias(&$sql,$table,$field) {
		$alias='';
		// We replace '.' with '_' to generate alias name
		// If Table already is in Table Array we must generate an alias for it ...
		// Hmm normally we should only generate an alias if we haven't already used the table ...
		// We look for foreign table (field name with a '.').
		// get link id, link is relation to table ...
	
		$FT='';
		if (strpos($field,'.')>0) {
		    $FTAA=t3lib_div::trimexplode('.',$field);
        	$FT=$FTAA[0];
	    }
	    
	    $link=$table.($FT?'_'.$FT:'');
	    
	    // Table aliases 
		/*
		if (!in_array($link,$sql['tablejoins'])) {			
			$sql['tablejoins'][]=$link;
			$sql['tableArray.'][$link]['table']=$table;			
			$sql['tableArray.'][$link]['alias']=$link;
		}
		*/
		
	    $fieldalias=str_replace('.','_',$field).'_'.$link;	    
		$sql['tableArray'][]=$link;
		// field aliases		
		$sql['fields.'][$field.'.']['table']=$table;
		$sql['fields.'][$field.'.']['tablealias']=$link;
		$sql['fields.'][$field.'.']['fieldalias']=$fieldalias;

		//$alias=$table;
				
		return $link;
	}
	
	/**
    * getSQLFields : deduct sql  fields to select from conf array for request
    *
    * @param	[array]		$conf: configuration array
    * @param	[array]	    $sql: sql array
    */

	function getSQLFields(&$conf,&$sql) {
	    // get fields shown in list mode ...
    	if ($conf['list.']['show_fields']) {
            $FTA=t3lib_div::trimexplode(',',$conf['list.']['show_fields']);
            //print_r($FTA);
        	foreach($FTA as $FTi) {
        	    // check if field is a user calc field, if so it will be handled later ...
        	    if (@array_key_exists($FTi,$conf['list.']['sqlcalcfields.'])) continue;
        	    // check if field is a relation to a foreign table (it has a '.' in it's name).
        	    if (strpos($FTi,'.')>0 )
        		{
        		    // We get foreign table name ...
        			$FTAA=t3lib_div::trimexplode('.',$FTi);
        			$FT=$FTAA[0];
        			$FN=$FTAA[1];
        			$FTT=$conf['TCAN'][$conf['table']]['columns'][$FT]['config']['foreign_table'];
        			if (!$FTT) die ("ext:meta_feedit:class.txmetafeedit_lib.php:getSQLFields no foreugn table definition of relation !");
        			$FTT=$this->getTableAlias($sql,$FTT,$FTi);
        			//$sql['fromTables'].=','.$FTT;
        			$sql['joinTables'][]=$FTT;
        			//$sql['fields'].=','.$FTT.'.'.$FN;
        			$sql['fieldArray'][]=$FTT.'.'.$FN.' as '.$sql['fields.'][$FN.'.']['fieldalias'];
    			} else {
    			    // These fields are form master table ($conf['table'])...
        			//$sql['fields'].=','.$conf['table'].'.'.$FTi;
        			$sql['fields.'][$FTi.'.']['table']=$conf['table'];
        			$sql['fieldArray'][]=$conf['table'].'.'.$FTi;
    		    }
			}
			
			// special fields like uid field of master table ($conf['table']) must always be here ...
			
			if (!in_array($conf['table'].'.'.$conf['uidField'],$sql['fieldArray'])) {
        	    $sql['fields'].=','.$conf['table'].'.'.$conf['uidField'];
        		$sql['fields.'][$FTi.'.']['table']=$conf['table'];
			    $sql['fieldArray'][]=$conf['table'].'.'.$conf['uidField'];
		    }
		   

        }
        //print_r($sql['fields.']);
    }


    /**
    * Builds SQL Request for list displays ...
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$sql: ...
    * @return	[type]		...
    */

	function getListSQL($TABLES,$DBSELECT,&$conf,&$markerArray,&$DEBUG)	
		$sql=array();
		die($TABLES);
		$sql['fromTables']=$TABLES;
		$sql['tableArray']=t3lib_div::trimexplode(',',$TABLES);
		$sql['joinTables']=array();
		$sql['joinTables'][]=$TABLES;
		$sql['DBSELECT']=$DBSELECT;
		$sql['where']=$sql['DBSELECT'];
		$this->getSQLFields($conf,$sql);
		// Default is *
		if (!$sql['fields']) {
			$sql['fields']=$conf['table'].'.*';   // Field list (is * a field ???)
			$sql['fieldArray'][]=$conf['table'].'.*';
		    $sql['fields.']['*.']['table']=$conf['table'];
	    }
		
		/*
		* $sql['tablearray'] : list of used tables 
		* $sql['tablejoinarray.'][$TABLE] : list of table joins
		* $sql['fields.'][$FIELD.]['table'] : Table of data field
		* $sql['fields.'][$FIELD.]['alias'] : Alias of field
		* 
		* $sql['fields.']['FIELD.'][''] : 
		*/
		
		//$sql['fieldArray'][]=$conf['table'].'.*';
		//$sql['fields.']['*.']['table']=$conf['table'];
		
		$this->getLockPidJoin($conf,$sql);
		$this->getExtraFields($conf,$sql);		
		$this->getForeignJoin($conf,$sql);		
		$this->getOUJoin($conf,$sql);		
		$this->getFUJoin($conf,$sql);		
		$this->getRUJoin($conf,$sql);		
		$this->getParentJoin($conf,$sql);
		$this->getCalcFields($conf,$sql);
		
        if ($conf['list.']['searchBox']) $this->getFullTextSearchWhere($conf,$sql,$markerArray);
        if ($conf['list.']['alphabeticalSearch']) $this->getAlphabeticalSearchWhere($conf,$sql);
        if ($conf['list.']['advancedSearch']) $this->getAdvancedSearchWhere($conf,$sql,$markerArray);
        if ($conf['list.']['calendarSearch']) $this->getCalendarSearchWhere($conf,$sql);

        $this->getUserWhereString($conf,$sql);
        // MODIF CBY
        $this->getGroupBy($conf,$sql);
       	$this->getOrderBy($conf,$sql);
        $this->getSum($conf,$sql);

		// Clean up ..
		$this->cleanSQL($conf,$sql);
		
		$gbarr=t3lib_div::trimexplode(',',$sql['gbFields']);
		$farr=$sql['fieldArray'];	
		
		foreach ($gbarr as $gb) {
			if ( !@in_array($gb,$sql['calcfields']) && $gb) $farr[]=$gb;
		}
				
		//$farr=array_unique($farr);
		$sql['fields']=implode(',',$farr);
		// we make fromtable sql :
		$sql['fromTables']=$conf['table']; // we add master table.

		foreach($sql['joinTables'] as $jT) {
		    $sql['fromTables'].=$sql['join.'][$jT];
	  }
	  
    $sql['fields']=implode(',',$sql['fieldArray']);	    
		$conf['list.']['sql']=&$sql;
		//krumo($sql)
		//die('proout');

 		if ($conf['debug.']['sql']) $DEBUG.="<br/>LIST 2SQL ARRAY <br/>".t3lib_div::view_array($sql);   
		return $sql;
	}

	/**
	 * Calculation fields : allows insertion of user defined sql fields (may creat ebugs as we have no control on whate the user outs in here)...
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$sql: ...
	 * @return	[type]		...
	 */
	 
	function getCalcFields(&$conf,&$sql,$table='') {  
		if (is_array($conf['list.']['sqlcalcfields.'])) foreach($conf['list.']['sqlcalcfields.'] as $field=>$calcsql) {
			$sql['fields'].=",$calcsql as $field";
			$sql['fieldArray'][]="$calcsql as $field";
			$sql['calcFieldsSql'].=",$calcsql as $field"; // to be removed
			$sql['calcfields'][]=$field; // to be removed
		}
	}
	
    /**
    * Value join on column 
    *
    * @param	[type]		$$conf: ...
    * @param	[type]		$sql: ...
    * @return	[type]		...
    */
    
	function getParentJoin(&$conf,&$sql,$table='') {  
		//TODO put plugin Id in here	
		if ($table=='') $table=$conf['table'];
		$A=t3lib_div::_GP($table);
		if  (is_array($A) && $A['lV'] && $A['lField']) {
			$lField=$conf['inputvar.']['lField.'][$table]=$A['lField'];
			$lV=$conf['inputvar.']['lV.'][$table]=$A['lV'];
			$GLOBALS["TSFE"]->fe_user->fetchSessionData();
			$metafeeditvars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
			array_merge($metafeeditvars[$GLOBALS['TSFE']->id][$conf['pluginId']],$conf['inputvar.']);
			// We make the join persistent by storing it in session.
			$GLOBALS["TSFE"]->fe_user->setKey('ses','metafeeditvars',$metafeeditvars);
			$GLOBALS["TSFE"]->fe_user->storeSessionData();
		} else {
			$lVa=$this->getMetaFeeditVar($conf,'lV.',true);
			$lV=$conf['inputvar.']['lV.'][$table]=$lVa[$table];
			$lFielda=$this->getMetaFeeditVar($conf,'lField.',true);
			$lField=$conf['inputvar.']['lField.'][$table]=$lFielda[$table];
		}

		if  ($lV && $lField) {	
			$mmTable=$conf['TCAN'][$table]['columns'][$lField]['config']['MM'];
			if ($mmTable) {			
				$this->getTableAlias($sql,$mmTable,$lFIELD);
				$AND_arr.=" AND ".$mmTable.'.uid_local='.$table.'.uid and '.$mmTable.'.uid_foreign=\''.$lV.'\'';
				//TODO
				$sql['fromTables'].=','.$mmTable;
				$sql['joinTables'][]=$mmTable;
			} 
			else {                          
				$AND_arr.=" AND `".$table."`.`".$lField."`='".$lV."'";
			}
			$sql['parentWhere'].=$AND_arr;
			$sql['where'].= $AND_arr;
		} 
	}
	
	// Front End User Join, Joins table Fields on Front End User Fields
	
	function getFUJoin(&$conf,&$sql,$table='') {  	
		$fUField = $conf['fUField']?$conf['fUField']:t3lib_div::_GP('fUField['.$conf['pluginId'].']');
        $fUKeyField = $conf['fUKeyField']?$conf['fUKeyField']:t3lib_div::_GP('fUKeyField['.$conf['pluginId'].']');
		$fU = $conf['fU']?$conf['fU']:t3lib_div::_GP('fU['.$conf['pluginId'].']');
		//CBY MODIF
		if (!$table) $table=$conf['table'];
		$OR_arr='';
		if ($fUField && $fUKeyField && ($GLOBALS['TSFE']->fe_user->user['uid'] || $fU)) {
			$feUid=$fU?$fU:$GLOBALS['TSFE']->fe_user->user['uid'];
			$mmTable=$conf['TCAN']['fe_users']['columns'][$fUField]['config']['MM'];
			$mmTable=$conf['TCAN']['fe_users']['columns'][$fUField]['config']['MM'];
			t3lib_div::devLog($feUid,"feUid2");
  			if ($mmTable) {
			    $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					$mmTable,
					'uid_local='.$GLOBALS['TYPO3_DB']->fullQuoteStr($feUid, 'fe_users').$GLOBALS['TSFE']->sys_page->deleteClause($mmTable)
				);


				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField.'='.$row['uid_foreign']:$table.'.'.$fUKeyField.'='.$row['uid_foreign'];
				}
				if (!$OR_arr) $OR_arr="1=0";

    		} else {
    			 $feVals=$GLOBALS['TSFE']->fe_user->user[$fUField];
    			if ($conf['TCAN']['fe_users']['columns'][$fUField]['config']['foreigntable']) {
    				foreach(t3lib_div::trimexplode(',',$feVals) as $feVal) {
    					if ($feVal) $OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField.'='.$feVal:$table.'.'.$fUKeyField.'='.$feVal;
    				}
    			} else {
    					if ($feVals) $OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField.'='.$feVals:$table.'.'.$fUKeyField.'='.$feVals;
    		    }
    		    //if (!$OR_arr) $OR_arr="1=0";

			}
			$sql['fUWhere']=$OR_arr;
			$sql['where'].= $OR_arr?' AND (' . $OR_arr . ')':'';
		}
	}

	// OUJoin Outer Table Join

	function getOUJoin(&$conf,&$sql) {
		$table=$conf['table'];
		if ($conf['originUid']) {
			$mmTable=$conf['TCAN'][$conf['originTable']]['columns'][$conf['originUidsField']]['config']['MM'];
			if ($mmTable) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$mmTable,
						'uid_local='.$GLOBALS['TYPO3_DB']->fullQuoteStr($conf['originUid'], $conf['originTable']).$GLOBALS['TSFE']->sys_page->deleteClause($table)
					);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$OR_arr.= $OR_arr?' OR '.$table.'.'.$conf['uidField'].'='.$row['uid_foreign']:$table.'.'.$conf['uidField'].'='.$row['uid_foreign'];
				}

			} else {
				$oUids="";
				$origArr=array();
				if ($conf['originKeyField']) {
					$origArr = $GLOBALS['TSFE']->sys_page->getRecordsByField($conf['originTable'], $conf['originKeyField'], $conf['originUid']);
					if (count($origArr)) {
						foreach($origArr as $oRec) {
							$oUids=$oUids?$oUids.','.$oRec[$conf['originUidsField']]:$oRec[$conf['originUidsField']];
						}
					};
				} else {
					$origArr = $GLOBALS['TSFE']->sys_page->getRawRecord($conf['originTable'],  $conf['originUid']);
					$oUids=$origArr[$conf['originUidsField']];
				}
				foreach(t3lib_div::trimexplode(',',$oUids) as $oUid) {
					if ($oUid) $OR_arr.= $OR_arr?' OR '.$table.'.'.$conf['uidField'].'='.$oUid:$table.'.'.$conf['uidField'].'='.$oUid;
				}
				if (!$OR_arr) $OR_arr="1=0";

			}
			$sql['oUWhere']=$OR_arr?' AND ('.$OR_arr.')':'';
			$sql['where'].= $sql['oUWhere'];
		}
	}

	// rUJoin

	function getRUJoin(&$conf,&$sql) {
 		$table=$conf['table'];
  		if ( ($conf['inputvar.']['rU']|| t3lib_div::_GP($table.'-rU')) && $conf['list.']['rUJoinField']) {
			$rF=$conf['list.']['rUJoinField'];
			$mmTable=$conf['TCAN'][$table]['columns'][$rF]['config']['MM'];
			$ruid=t3lib_div::_GP($table.'-rU')?t3lib_div::_GP($table.'-rU'):$conf['inputvar.']['rU'];
			if ($mmTable) {
				$this->getTableAlias($sql,$mmTable,$rF);
				$sql['rUWhere'].=" AND ".$mmTable.'.uid_local='.$table.'.uid and '.$mmTable.'.uid_foreign='.$ruid;
				//TODO
				$sql['fromTables'].=','.$mmTable;
				$sql['joinTables'][]=$mmTable;
			} else {
				$sql['rUWhere'].=" AND ".$table.'.'.$rF."='".$ruid."'";
			}

			//$sql['rUWhere']=$AND_arr;
			$sql['where'].= $sql['rUWhere'];
		}
	}

	// lockPidJoin

	function getLockPidJoin(&$conf,&$sql) {
 		$table=$conf['table'];
 		// Initial variables
		if ($conf['debug']) echo t3lib_div::view_array(array('pid'=>$conf['pid']));

		$lockPid = ($conf['edit.']['menuLockPid'] && $conf['pid'])? ' AND '.$table.'.pid='.intval($conf['pid']) : '';
		if ($conf['recursive']) {
				 $pid_list = $this->feadminlib->pi_getPidList($conf['pid'], $conf['recursive']);
				 $lockPid = ($conf['edit.']['menuLockPid'] && $conf['pid']) ? ' AND '.$table.'.pid in ('.$pid_list.')' : '';
		}

		$sql['lockPidWhere']=$lockPid;
		$sql['where'].= $sql['lockPidWhere'];
	}
	// User Where String

	function getUserWhereString(&$conf,&$sql) {
		$conf['parentObj']=&$this->feadminlib;
		if ($conf['list.']['userFunc_afterWhere']) t3lib_div::callUserFunction($conf['list.']['userFunc_afterWhere'],$conf,$this->feadminlib);
		if ($conf [$conf['cmdmode'] . '.']['whereString'])	$sql['userWhereString']=' AND '.$conf [$conf['cmdmode'] . '.']['whereString'];
		$sql['where'].= $sql['userWhereString'];
    }

	// Alphabetical Search Functions
	function getAlphabeticalSearchWhere(&$conf,&$sql) {
		$point=strpos($conf['table_label'],'.');
		if ($point) {
			$relArr=t3lib_div::trimexplode('.',$conf['table_label']);
			if (count($relArr)>2) die ("PLUGIN METAFEEDIT: GETALPHABETICALSEARCH: We don't handle multrelations yet!");
			$FT=$conf['TCAN'][$conf['table']]['columns'][$relArr[0]]['config']['foreign_table'];
			$Field=$FT.'.'.$relArr[1];
		} else {
			$Field=$conf['table'].'.'.$conf['table_label'];
		}
		//$sql['alphabeticalWhere']=$conf['piVars']['sortLetter'][$conf['pluginId']]?' AND '.$Field." like '".$conf['piVars']['sortLetter'][$conf['pluginId']]."%' ":'';
		$sql['alphabeticalWhere']=$conf['inputvar.']['sortLetter']?' AND '.$Field." like '".$conf['inputvar.']['sortLetter']."%' ":'';
		$sql['where'].=$sql['alphabeticalWhere'];
	}
    // Full Text Search where
	function getFullTextSearchWhere(&$conf,&$sql,&$markerArray) {
		//MODIF CBY
		$this->feadminlib->internal['searchFieldList']='';	
		$table=$conf['table'];
		// find search & order by Fields
		$Arr=explode(',',$conf['fieldList']);
        foreach($Arr as $fieldName) {
        	if (!$fieldName) continue;
    			switch( $conf['TCAN'][$table]['columns'][$fieldName]['config']['type']) {
    			case 'text':
    			case 'input':
    				$this->feadminlib->internal['searchFieldList'].=$this->feadminlib->internal['searchFieldList']?','.$fieldName:$fieldName;
    				break;
    			default:
    				break;
			}
		}

		$markerArray['###FTSEARCHBOXVAL###']=$conf['inputvar.']['sword'];
		$sql['fullTextWhere']=$this->cObj->searchWhere($conf['inputvar.']['sword'],$this->feadminlib->internal['searchFieldList'],$table);
		$sql['where'].=$sql['fullTextWhere'];
	}

	// GroupBys
	function getGroupBy(&$conf,&$sql) {
 		$table=$conf['table'];
		$SORT='';
		$dir=' asc';
		if ($conf['list.']['groupByFieldBreaks']) {		
			$fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFieldBreaks']);
		  // we handle here multi-table group bys ...
		  $Join='';
		  foreach($fNA as $fN) {
				$c=0;
				// fN2[0] : fieldName
				// fN2[1] : sort direction (ASC,DESC)
				// fN2[2] : caluclate field (not attached to table)
				$fN2=t3lib_div::trimexplode(':',$fN);
				$fNR=t3lib_div::trimexplode('.',$fN2[0]);
				$dir=" ".$fN2[1];
				if ($fN2[2]) {
						// calculated field groupBy
						$SORT=$SORT?$SORT.','.$fN2[0]:$fN2[0];
						//$GBFields.=','.$fN2[0];
						$calcField=$conf['list.']['sqlcalcfields.'][$fN2[0]]; // TO BE IMPROVED
						if ($calcField) {
							$GBFields.=",$calcField as $fN2[0]";
						}

						/*$GBFieldLabel=$fN2[0];
						$GrpByField[$fN2[0]]=$GBFieldLabel;
						$GroupBy=$GroupBy?$GroupBy.','.$fN2[0]:' GROUP BY '.$fN2[0]; 
						$GBCalcFields.=','.$fN2[0];
						*/
			  } else {
					$gbtable=$table;
					$c=count($fNR);
					$i=$c;
					$gbfN=$fNR[0];
					$lasttable=$gbtable;
					while ($i>1)  {
						$i--;
						if ($conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table']) {
							$lasttable=$gbtable;
							$gbtable2=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];
							if (!$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
								$Join.=' AND FIND_IN_SET('.$gbtable2.'.uid,'.$lasttable.'.'.$gbfN.')>0 ';
							}
							$gbtable=$gbtable2;
							$gbfN=$fNR[$c-$i];
						}
					}
					
					if ($table != $gbtable) t3lib_div::loadTCA($gbtable);
					
					if ( $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['type']=='select' && $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'] && !$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
						$fT=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];						
						if (!in_array($fT,$sql['joinTables'])) {
							$alias=$this->getTableAlias($sql,$fT,$gbfN);
							//$sql['fromTables'].=' LEFT JOIN '.$fT.($sql['fields.'][$gbfN.'.']['alias']?' as '.$sql['fields.'][$gbfN.'.']['alias']:'').' ON '.$gbtable.'.'.$gbfN.'='.$alias.'.uid';
              $sql['join.'][$alias]=' LEFT JOIN '.$alias.($sql['fields.'][$gbfN.'.']['alias']?' as '.$sql['fields.'][$gbfN.'.']['alias']:'').' ON '.$gbtable.'.'.$gbfN.'='.$alias.'.uid';
						}
						$sql['joinTables'][]=$alias;
						$label=$conf['TCAN'][$fT]['ctrl']['label'];
						$sql['fields'].=','.$alias.'.'.$label.' as '.$alias.'_'.strtoupper($label);
						$sql['fieldArray'][]=$alias.'.'.$label.' as '.$alias.'_'.strtoupper($label);
						$GBFields.=','.$alias.'.'.$label.' as '.$alias.'_'.strtoupper($label);
						$GrpByField[$fN]=$alias.'.'.strtoupper($label);
						$SORT=$SORT?$SORT.','.$GrpByField[$fN]:$GrpByField[$fN];
			  	} else {
					 	$SORT=$SORT?$SORT.','.$gbtable.'.'.$gbfN:$gbtable.'.'.$gbfN;
						$GBFields.=','.$gbtable.'.'.$gbfN.' as '.$gbtable.'_'.$gbfN;
					}
				}
	    }

	    $sql['breakOrderBy']=$SORT?$SORT.$dir:'';
			//$sql['breakGroupBy']=$GroupBy;
			$sql['breakGbFields']=$GBFields;
			//$sql['gbCalcFields']=$GBCalcFields;

	  }
	 	$SORT='';
	 	$GBFields='';
	  if ($conf['list.']['groupByFields']) {
		  $fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFields']);
		  // we handle here multi-table group bys ...
		  $Join='';
		  foreach($fNA as $fN) {
				$c=0;
				// fN2[0] : fieldName
				// fN2[1] : sort direction (ASC,DESC)
				// fN2[2] : caluclate field (not attached to table)
				$fN2=t3lib_div::trimexplode(':',$fN);
				$fNR=t3lib_div::trimexplode('.',$fN2[0]);
				$dir=" ".$fN2[1];
				if ($fN2[2]) {
						// calculated field groupBy
						$SORT=$SORT?$SORT.','.$fN2[0]:$fN2[0];
						//$GBFields.=','.$fN2[0];
						$calcField=$conf['list.']['sqlcalcfields.'][$fN2[0]]; // TO BE IMPROVED
						if ($calcField) {
							$GBFields.=",$calcField as $fN2[0]";
						}

						$GBFieldLabel=$fN2[0];
						$GrpByField[$fN2[0]]=$GBFieldLabel;
						$GroupBy=$GroupBy?$GroupBy.','.$fN2[0]:' GROUP BY '.$fN2[0]; 
						$GBCalcFields.=','.$fN2[0];

			  } else {
					$gbtable=$table;
					$c=count($fNR);
					$i=$c;
					$gbfN=$fNR[0];
					$lasttable=$gbtable;
					while ($i>1)  {
						$i--;
						if ($conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table']) {
							$lasttable=$gbtable;
							$gbtable2=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];
							if (!$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
								$Join.=' AND FIND_IN_SET('.$gbtable2.'.uid,'.$lasttable.'.'.$gbfN.')>0 ';
							}
							$gbtable=$gbtable2;
							$gbfN=$fNR[$c-$i];
						}
					}
					
					if ($table != $gbtable) t3lib_div::loadTCA($gbtable);
					
					if ( $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['type']=='select' && $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'] && !$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
						$fT=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];
						//TODO
						//$sql['fromTables'].=','.$fT;
						
						if (!in_array($fT,$sql['joinTables'])) {
							$alias=$this->getTableAlias($sql,$fT,$gbfN);
							//$sql['fromTables'].
							$sql['join.'][$alias]=' LEFT JOIN '.$fT.($sql['fields.'][$gbfN.'.']['fieldalias']?' as '.$sql['fields.'][$gbfN.'.']['fieldalias']:'').' ON '.$gbtable.'.'.$gbfN.'='.$alias.'.uid';
						}

						$sql['joinTables'][]=$fT;
						$label=$conf['TCAN'][$fT]['ctrl']['label'];
						$sql['fields'].=','.$fT.'.'.$label.' as '.$fT.'_'.strtoupper($label);
						$sql['fieldArray'][]=$fT.'.'.$label.' as '.$fT.'_'.strtoupper($label);
						$GBFields.=','.$fT.'.'.$label.' as '.$fT.'_'.strtoupper($label);
						//$GrpByField[$fN]=$fT.'_'.strtoupper($label);
						$GrpByField[$fN]=$fT.'.'.strtoupper($label);
						$SORT=$SORT?$SORT.','.$GrpByField[$fN]:$GrpByField[$fN];
		    		    $GroupBy=$GroupBy?$GroupBy.','.$GrpByField[$fN]:' GROUP BY '.$GrpByField[$fN];
						$GBFieldLabel=$fT.'_'.strtoupper($label);
					
			  	} else {
					 	$SORT=$SORT?$SORT.','.$gbtable.'.'.$gbfN:$gbtable.'.'.$gbfN;
						$GBFields.=','.$gbtable.'.'.$gbfN.' as '.$gbtable.'_'.$gbfN;
						$GBFieldLabel=$gbtable.'_'.$gbfN;
						$GrpByField[$fN2[0]]=$GBFieldLabel;
						$GroupBy=$GroupBy?$GroupBy.','.$gbtable.'.'.$gbfN:' GROUP BY '.$gbtable.'.'.$gbfN; 
					}
				}
	    }
	    $SORT=$SORT?$SORT.$dir:'';
			$sql['groupBy']=$GroupBy;
			$sql['orderBy']=$SORT;
			$sql['gbFields']=$GBFields;
			$sql['gbCalcFields']=$GBCalcFields;
			if ($conf['list.']['preOrderByString']) {
				$sql['preOrderBy']=$conf['list.']['preOrderByString'];
			}
			if ($conf['list.']['havingString']) $sql['having']=' HAVING '.$conf['list.']['havingString'];
		}
	}
		// We handle Group By Field Breaks


	// Sums
	function getSum(&$conf,&$sql) {
  }
  
 	// OrderBys
	function getOrderBy(&$conf,&$sql) {
		$table=$conf['table'];
		if ($conf['list.']['sortFields']){
			//MODIF CBY list($this->internal['orderBy'], $this->internal['descFlag']) = explode(':', $this->piVars['sort']);
			
			//$conf['debug.']['debugString'].="<br> sort ### :".$conf['inputvar.']['sort'];
			
			list($this->feadminlib->internal['orderBy'], $this->feadminlib->internal['descFlag']) = explode(':', $conf['inputvar.']['sort']);
			
			//$conf['debug.']['debugString'].="<br> internal 0 ### :".$this->feadminlib->internal['descFlag']." ob :".$this->feadminlib->internal['orderBy'];

			if ($this->feadminlib->internal['orderBy'])    {
	  			$sql['orderBy'] = $sql['orderBy']?$sql['orderBy'].','.$table.'.'.$this->feadminlib->internal['orderBy'].($this->feadminlib->internal['descFlag']?' DESC':' ASC') : ' ORDER BY '.$table.'.'.$this->feadminlib->internal['orderBy'].($this->feadminlib->internal['descFlag']?' DESC':' ASC');
			}
    }

		//MODIF CBY if ($conf['list.']['orderByFields'] && !$this->piVars['sort']){
		if ($conf['list.']['orderByFields'] && !$conf['inputvar.']['sort']){
			$orderByFields = explode(',', $conf['list.']['orderByFields']);
			foreach($orderByFields as $fieldName) {
				$fN2=t3lib_div::trimexplode(':',$fieldName);
				$fieldName=$fN2[0];
				$dir=" ".$fN2[1];
				if ($fN2[2]) {
					$sql['orderBy'] = $sql['orderBy']?$sql['orderBy'].','.$fieldName.$dir :$fieldName.$dir;
				} else {
					$sql['orderBy'] = $sql['orderBy']?$sql['orderBy'].','.$table.'.'.$fieldName.$dir :$table.'.'.$fieldName.$dir;
				}
			}
		}
		if ($conf['list.']['orderByString']) {
			$sql['orderBy']=$sql['orderBy']?$sql['orderBy'].','.$conf['list.']['orderByString']:$conf['list.']['orderByString'];	
		}
		$sql['breakOrderBy']=$sql['preOrderBy'] && $sql['breakOrderBy']?','.$sql['breakOrderBy']:$sql['breakOrderBy'];
		$sql['orderBy']=($sql['preOrderBy'] || $sql['breakOrderBy']) && $sql['orderBy'] ?','.$sql['orderBy']:$sql['orderBy'];
		
 		$sql['orderBySql']=$sql['preOrderBy'].$sql['breakOrderBy'].$sql['orderBy']?" ORDER BY ".$sql['preOrderBy'].$sql['breakOrderBy'].$sql['orderBy']:'';
		//hack by CMD - suite
		//$sql['orderBy'] = ' ORDER BY '.$sql['orderBy'];
  }

	// advancedSearch
	function getAdvancedSearchWhere(&$conf,&$sql,&$markerArray) {
		$markerArray['###ASCHECKEDBETWEEN###']='';
		$markerArray['###ASCHECKEDEQUAL###']='';
		$markerArray['###ASCHECKEDINF###']='';
		$markerArray['###ASCHECKEDSUP###']='';
		$fields=$conf['list.']['advancedSearchFields']?$conf['list.']['advancedSearchFields']:($conf['list.']['show_fields']?$conf['list.']['show_fields']:'');
		$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
		foreach($fieldArray as $FN) {
			$markerArray['###ASFIELD_'.$FN.'_VAL###']='';
			$markerArray['###ASFIELD_'.$FN.'_VALSUP###']='';
		}
		$table=$conf['table'];
		//$advancedSearch=$conf['piVars']['advancedSearch'][$conf['pluginId']];			
		$advancedSearch=$conf['inputvar.']['advancedSearch'];		
		//TODO a fiabiliser	
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();
		$metafeeditvars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
		$metafeeditvars[$GLOBALS['TSFE']->id][$conf['pluginId']]['advancedSearch']=$advancedSearch;
		$GLOBALS["TSFE"]->fe_user->setKey('ses','metafeeditvars',$metafeeditvars);
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
		//if (!is_array($advancedSearch)) $advancedSearch=$conf['piVars']['advancedSearch'];	
		//modif CMD - récup du typoscript
		$pluginId=$conf['pluginId'];
		if ($conf['typoscript.'][$pluginId.'.']['advancedSearch.']) $advancedSearchDefault = $conf['typoscript.'][$pluginId.'.']['advancedSearch.'];
		if (is_array($advancedSearch) && is_array($advancedSearchDefault)) {
			foreach($advancedSearch as $key=>$value) {
				if ($advancedSearchDefault[$key.'.'] && is_array($advancedSearch[$key])) $advancedSearch[$key] = array_merge($advancedSearch[$key], $advancedSearchDefault[$key.'.']);
				elseif ($advancedSearchDefault[$key.'.'] && !$value) $advancedSearch[$key] = $advancedSearchDefault[$key.'.'];
			}
		}
		elseif($advancedSearchDefault) {
			$advancedSearch=array();
			foreach($advancedSearchDefault as $key=>$value) {
				$key = substr($key, 0, -1);
				$value['default.']['val']=$this->getData($value['default.']['val'],0,$this->cObj);
				//if (!$value['val']) $value['val']=$value['default.']['val'];
				//if (!$value['val'] && $value['default.']['val'] && $value['default.']['op']) $value['val']=$value['default.']['val'];
				//if (!$value['op'] && $value['default.']['op']) $value['op']=$value['default.']['op'];
				if (!$value['default.']['op'] && $value['default.']['val']) {
					$value=$value['default.']['val'];
					$conf['inputvar.']['advancedSearch'][$key]=$value;
				}
				$advancedSearch[$key] = $value;
			}
		}
		if (is_array($advancedSearch)) {
			foreach($advancedSearch as $key=>$value) {
				//modif CMD - ajout des tables etrangère à l'AS
				$curTable = $this->getForeignTableFromField($key, $conf);
				//modif CMD - recup du TS
				$valeur='';
				if ($this->is_extent($value) && !is_array($value)) $valeur=$value;
				elseif ($this->is_extent($value['default'])  && !is_array($value['default.'])) $valeur=$value['default'];
				if ($this->is_extent($valeur)) {
					if (!$conf['TCAN'][$curTable['table']]['columns'][$key]['config']['MM']) {
						$sql['advancedWhere'].=" AND ".$curTable['table'].".".$curTable['fNiD']." LIKE '$valeur' ";
					} else {
						$mmTable=$conf['TCAN'][$curTable['table']]['columns'][$key]['config']['MM'];
						$uidLocal=isset($conf['TCAN'][$mmTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$mmTable]['ctrl']['uidLocalField']:'uid';
					 	$sql['advancedWhere'].= 'AND '.$mmTable.'.uid_foreign=\''.$valeur.'\'';
				  	}
					$markerArray['###ASFIELD_'.$key.'_VAL###']=$valeur;
				}
				if ($value['op'] && is_array($value) ) {
					$my_op = $value['op'];
					$my_val = $value['val'];
					$my_valsup = $value['valsup'];
				}elseif(is_array($value['default.'])){
					$my_op = $value['default.']['op'];
					//TODO format date chercher le format par défaut
					$my_val = ($value['default.']['val'] == 'now')?date('d-m-Y'):$value['default.']['val'];
					$my_valsup = $value['default.']['valsup'];
				}
				// dates !				
				if ($my_op && is_array($value) ) {
					$valdate=$my_val;
					$markerArray['###ASFIELD_'.$key.'_VAL###']=$valdate;
					$valdatesup=$my_valsup;
					$markerArray['###ASFIELD_'.$key.'_VALSUP###']=$valdatesup;
					if ($valdate){
						$d=explode('-',$valdate);
						// Careful here format is Month/Day/Year !!!
						//$val = strtotime($d[2].'/'.$d[1].'/'.$d[0]);
						$val=mktime(0,0,0,$d[1],$d[0],$d[2]);
						if ($my_val){
							if ($my_op=='><' &&  $valdatesup) {
				  			$markerArray['###ASCHECKEDBETWEEN###']='checked="checked"';

								$d=explode('-',$valdatesup);
								//$valsup = strtotime($d[2].'/'.$d[1].'/'.$d[0]);
								$valsup=mktime(0,0,0,$d[1],$d[0],$d[2]);
								//$sql['advancedWhere'].=" AND $table.$key between '$val' and '$valsup' ";
								$sql['advancedWhere'].=" AND ".$curTable['table'].".".$curTable['fNiD']." between '$val' and '$valsup' ";
							} elseif ($my_op=='>ts<' &&  $valdatesup) {
				  				$markerArray['###ASCHECKEDBETWEEN###']='checked="checked"';
								//$sql['advancedWhere'].=" AND $table.$key >= '$valdate' and  $table.$key < '$valdatesup' ";
								$sql['advancedWhere'].=" AND ".$curTable['table'].".".$curTable['fNiD']." >= '$valdate' and ".$curTable['table'].".".$curTable['fNiD']." < '$valdatesup' ";
							} else {
								if ($my_op=='=') {
									$d=explode('-',$valdate);
									//$valsup = strtotime($d[2].'/'.$d[1].'/'.(int)($d[0]+1));
									$valsup=mktime(0,0,0,$d[1],$d[0]+1,$d[2]);
									//$sql['advancedWhere'].=" AND $table.$key >= '$val' and $table.$key < '$valsup' ";
									$sql['advancedWhere'].=" AND ".$curTable['table'].".".$curTable['fNiD']." >= '$val' and ".$curTable['table'].".".$curTable['fNiD']." < '$valsup' ";
									$markerArray['###ASCHECKEDEQUAL###']='checked="checked"';
								}elseif ($my_op=='<') {
									//$sql['advancedWhere'].=" AND $table.$key ".$my_op." '$val' ";
									$sql['advancedWhere'].=" AND ".$curTable['table'].".".$curTable['fNiD']." ".$my_op." '$val' ";
									$markerArray['###ASCHECKEDINF###']='checked="checked"';
								} elseif ($my_op=='>') {
									//$sql['advancedWhere'].=" AND $table.$key ".$my_op." '$val' ";
									$sql['advancedWhere'].=" AND ".$curTable['table'].".".$curTable['fNiD']." ".$my_op." '$val' ";
									$markerArray['###ASCHECKEDSUP###']='checked="checked"';
								}
							}
						}
					}
				}
			}
		}
		
    if (!$markerArray['###ASCHECKEDINF###'] && !$markerArray['###ASCHECKEDSUP###'] && !$markerArray['###ASCHECKEDBETWEEN###']) $markerArray['###ASCHECKEDEQUAL###']='checked="checked"';
		$sql['where'].=$sql['advancedWhere'];
		//$conf['debug.']['debugString'].=$sql['where'];
	}		
	
	// calendarSearch
	function getCalendarSearchWhere(&$conf,&$sql) {
		$table=$conf['table'];
		$calendarSearch=$conf['piVars']['calendarSearch'][$conf['pluginId']];
		if (is_array($calendarSearch)) {
			foreach($calendarSearch as $key=>$value) {
				if ($value['op'] && is_array($value) ) {
					$valdate=$value['val'];
					$valdatesup=$value['valsup'];
					if ($valdate){
						$d=explode('-',$valdate);
						$val = strtotime($d[2].'/'.$d[1].'/'.$d[0]);
						if ($value['val']){
							if ($value['op']=='><' &&  $valdatesup) {
								$d=explode('-',$valdatesup);
								$valsup = strtotime($d[2].'/'.$d[1].'/'.$d[0]);
								$sql['calendarWhere'].=" AND $key between '$val' and '$valsup' ";
							} elseif ($value['op']=='>ts<' &&  $valdatesup) {
								$sql['calendarWhere'].=" AND $key >= '$valdate' and  $key < '$valdatesup' ";
							} else {
								$sql['calendarWhere'].=" AND $key ".$value['op']." '$val' ";
							}
						}
					}
				}
			}
			if ($this->is_extent($calendarSearch['year']) && $this->is_extent($calendarSearch['month']) && !$this->is_extent($calendarSearch['day'])) {
					if (!$conf['TCAN'][$table]['columns'][$conf['list.']['beginDateField']]['config']['MM']) {
						//$sql['calendarWhere'].=" AND $key = '$value' ";
						$year=$calendarSearch['year'];
						$month=$calendarSearch['month'];
						$ts=mktime(0,0,0,$month,0,$year);
						//$ts=strtotime(
					  $ts2=mktime(0,0,0,$month+1,0,$year);

				    $sql['calendarWhere'].=" AND (".$table.'.'.$conf['list.']['beginDateField'].">='$ts' and ".$table.'.'.$conf['list.']['beginDateField']."<'$ts2' )";
					} else {
						// BUG CBY!!!
						$mmTable=$conf['TCAN'][$table]['columns'][$key]['config']['MM'];
						$uidLocal=isset($conf['TCAN'][$mmTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$mmTable]['ctrl']['uidLocalField']:'uid';
					  $sql['calendarWhere'].= 'AND '.$mmTable.'.uid_foreign=\''.$value.'\'';
				  }
				}

		}
		$sql['where'].=$sql['calendarWhere'];

	}
	
	// Clean SQL (check unicity of tables and fields ...)
	function cleanSql(&$conf,&$sql) {
		$sql['joinTables']=array_unique($sql['joinTables']);
		$sql['gbFields']=array_unique($sql['gbFields']);
		$sql['fieldArray']=array_unique($sql['fieldArray']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_sqlengine.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_sqlengine.php']);
}

?>
