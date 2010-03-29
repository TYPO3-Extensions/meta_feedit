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
	* This is a Class for editing multiple records in datagrid in the frontend.
	* called by fe_adminLib.inc
	* @author      Christophe BALISKY <cbalisky@metaphore.fr>
	*/
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_lib.php');
if (t3lib_extMgm::isLoaded('fpdf')) require_once(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');

	
class tx_metafeedit_grid {
	var $exporttype='';
	var $content_pdf='';
	var	$content_csv='';
	var	$content_excel='';
	var $prefixId = "tx_metafeedit"; 
	var $cObj;
    var $conf;
    var $xajax;
    var $mode;
    var $metafeeditlib;
    var $feadminlib;
    
    function init(&$metafeeditlib,&$feadminlib) {
		$this->metafeeditlib = &$metafeeditlib;
		$this->feadminlib = &$feadminlib;
	}
	
	function tx_metafeedit_grid() {
		$this->cObj = &$GLOBALS['TSFE']->cObj;
	}

	// Data grid
	// Row Field
	// Col Field

	
	function displayGridScreen($TABLES,$DBSELECT,&$conf)	{	
	
	/*
		$this->markerArray['###GLOBALPARAMS###']='';
		$this->markerArray['###GLOBALPARAMS###'].=$this->piVars['referer'][$this->conf['pluginId']]?'&tx_metafeedit[referer]['.$this->conf['pluginId'].']='.rawurlencode($this->piVars['referer'][$this->conf['pluginId']]):'';
		$this->conf['GLOBALPARAMS']=$this->markerArray['###GLOBALPARAMS###'];
	    */
		//$this->metafeeditlib=t3lib_div::makeInstance('tx_metafeedit_lib');
		$conf['cmdmode']='grid';
		$this->conf=&$conf;
		$table=$conf['table'];
		$nbRows=$conf['grid.']['nbRows'];
		$nbCols=$conf['grid.']['nbCols'];
		$rowField=$conf['grid.']['rowField'];
		$colField=$conf['grid.']['colField'];
		$secondaryColFields=$conf['grid.']['secondaryColFields'];
		if (!$rowField) die ("Plugin META FEEDIT , GRID MODE : Row field not set !");
		if (!$colField) die ("Plugin META FEEDIT , GRID MODE : Col field not set !");
		if (!$nbRows && $conf['TCAN'][$table]['columns'][$rowField]['config']['type']!='select') die ("Plugin META FEEDIT , DYNAMIC GRID MODE : nbrows not set !");
		if (!$nbCols && $conf['TCAN'][$table]['columns'][$colField]['config']['type']!='select') die ("Plugin META FEEDIT , DYNAMIC GRID MODE : nbcols not set !");
		//$this->makeXajaxInstance();
		//$metafeeditlib=$conf['metafeeditlib'];
		//$feAdminLib=&$this->conf['feAdminLib'];
		$conf['markerArray']=$this->feadminlib->markerArray;
		
		//-----------------------------------------------------------------------------------------------------------------//
		// R�cup�ration de la variable exporttype
		
		// Modif CMD - unused configuration -> see getGridTopActions in class.tx_metafeedit_lib.php
		//$content_pdf.=$conf['grid.']['gridExportPDF']?$this->metafeeditlib->CreatePDF($content_pdf,$conf):'';
		//$content_csv.=$conf['grid.']['gridExportCSV']?$this->metafeeditlib->CreateCSV($content_csv,$conf):'';
		//$content_excel.=$conf['grid.']['gridExportExcel']?$this->metafeeditlib->CreateExcel($content_excel,$conf):'';
		
		//echo "yop";
		//echo $conf['piVars'];
		$exporttype=$conf['piVars']['exporttype'];
	
	 	//if ($conf['performanceaudit']) 
		//	t3lib_div::devLog($conf['cmdmode']." displayGrid start :".$metafeeditlib->displaytime(), $this->extKey );

		$sql=array();
		$sql['joinTables']=array();
	    $sql['fieldArray']=array();
	    $sql['breakOrderBy']=array();
	    $sql['preOrderBy']=array();
	    $sql['orderBy']=array();

		$sql['joinTables'][]=$conf['table'];
		$sql['DBSELECT']=$DBSELECT;
		$sql['where']=$sql['DBSELECT'];
		$this->metafeeditlib->getSQLFields($conf,$sql);
		// Default is *
		if (!count($sql['fieldArray'])) {
			$sql['fields']=$conf['table'].'.*';   // Field list (is * a field ???)
			$sql['fieldArray'][]=$conf['table'].'.*';
		    $sql['fields.']['*.']['table']=$conf['table'];
	    }
		
		if ($conf['enableColumns']) {
			$sql['where'].= $GLOBALS['TSFE']->sys_page->enableFields($table,$show_hidden?$show_hidden:($table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
		}
		$this->metafeeditlib->getLockPidJoin($conf,$sql);
		$this->metafeeditlib->getExtraFields($conf,$sql);
		//$this->metafeeditlib->getForeignJoin($conf,$sql);
		$this->metafeeditlib->getOUJoin($conf,$sql);
		$this->metafeeditlib->getFUJoin($conf,$sql,$table);
		$this->metafeeditlib->getRUJoin($conf,$sql);
		$this->metafeeditlib->getParentJoin($conf,$sql);
		if ($conf['grid.']['searchBox'])
			$this->metafeeditlib->getFullTextSearchWhere($conf,$sql,$this->markerArray);
		if ($conf['grid.']['alphabeticalSearch'])
			$this->metafeeditlib->getAlphabeticalSearchWhere($conf,$sql);
		if ($conf['grid.']['advancedSearch'])
			$this->metafeeditlib>getAdvancedSearchWhere($conf,$sql,$this->markerArray);
		if ($conf['grid.']['calendarSearch'])
			$this->metafeeditlib->getCalendarSearchWhere($conf,$sql);
		$this->metafeeditlib->getUserWhereString($conf,$sql);
		$jRowField=$this->metafeeditlib->getFieldJoin($conf,$sql,$conf['table'],$rowField);
		$jColField=$this->metafeeditlib->getFieldJoin($conf,$sql,$conf['table'],$colField);		
		if ($conf['debug.']['sql']) 
			$DEBUG.="<br/>GRID SQL ARRAY <br/>".t3lib_div::view_array($sql);   	
		
		// Clean up ..
		
		$this->metafeeditlib->cleanSQL($conf,$sql);
		
		$gbarr=t3lib_div::trimexplode(',',$sql['gbFields']);
		$farr=$sql['fieldArray'];	
		
		foreach ($gbarr as $gb) {
			if ( !@in_array($gb,$sql['calcfields']) && $gb) $farr[]=$gb;
		}
				
		$farr=array_unique($farr);
		$sql['fields']=implode(',',$farr);
		// we make fromtable sql :
		$sql['fromTables']=$conf['table']; // we add master table.
		foreach($sql['joinTables'] as $jT) {
		    $sql['fromTables'].=$sql['join.'][$jT];
	    }		
		$WHERE.=$sql['where'];
		$FromTables=$sql['fromTables'];
		$ReqFields=$sql['fields'];
		$conf['grid.']['sql']=&$sql;

		// CBY>

		// call getBackUrl ...
		$conf['markerArray']['###BACK_URL###'] = "";
		if (!$conf['no_action']) {
			$conf['backURL']=$this->metafeeditlib->makeBackURLTypoLink($conf,$conf['backURL']);
			$conf['markerArray']['###BACK_URL###'] = $conf['backURL'];
		}			
		$content='';		
        $ReqFields=$conf['uidField'].','.$rowField.','.$colField;
        if ($conf['grid.']['show_fields']) 
    		$ReqFields=$ReqFields.','.$conf['grid.']['show_fields'];
        if ($sql['addFields']) 
    		$ReqFields=$ReqFields.','.$sql['addFields'];
		//single tables !
	    $sql['fromTables']=implode(',',array_unique(explode(',',$sql['fromTables'])));
		
		//echo $GLOBALS['TYPO3_DB']->SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where']);;
		//die(sql);
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where']);
		if ($conf['debug.']['sql']) $this->metafeeditlib->debug('displayGrid row count ',$GLOBALS['TYPO3_DB']->SELECTquery(" distinct ".$sql['fields'], $sql['fromTables'], '1 '.$sql['where']),$DEBUG);
		while($item = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$cella[$item[$jRowField]][$item[$jColField]]=$item;
			if ($secondaryColFields) {
				$cella2[$item[$jRowField]][$item[$jColField]][$item[$secondaryColFields]]=$item;
			}
			$cella[$item[$jRowField]][$item[$jColField]][$secondaryColFields]=0;
		}
		
		//print_r($cella);
		//die(cell);
		//MODIF CBY
			
			// Grid Row Data loop
			
			if (!$nbRows) {
				$rowTable=$conf['TCAN'][$table]['columns'][$rowField]['config']['foreign_table'];
				$rowWhere=$conf['grid.']['rowfield.']['whereString'];
				$sqlrow=array();
				$sqlrow['where']=' 1 ';
				$sqlrow['fromTables']=$rowTable;
				$sqlrow['fields']=$rowTable.'.uid';
				if ($conf['grid.']['row.']['fUField'] || $conf['grid.']['row.']['fUKeyField']) {			
					
					// CBY MODIF

					if ($conf['grid.']['row.']['fUField']) $conf['fUField']=$conf['grid.']['row.']['fUField'];
					if ($conf['grid.']['row.']['fUKeyField']) $conf['fUKeyField']=$conf['grid.']['row.']['fUKeyField'];
					/*if ($conf['enableColumns']) {
						$sqlrow['where'].= $GLOBALS['TSFE']->sys_page->enableFields($rowTable,$show_hidden?$show_hidden:($rowTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
					}*/

					$this->metafeeditlib->getFUJoin($conf,$sqlrow,$rowTable);		
				}
				if ($conf['enableColumns']) {
					$sqlrow['where'].= $GLOBALS['TSFE']->sys_page->enableFields($rowTable,$show_hidden?$show_hidden:($rowTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
				}

				
				//MODIF CBY
				//if ($conf['enableColumns']) {
				//	$sqlrow['where'].= $GLOBALS['TSFE']->sys_page->enableFields($rowTable,$show_hidden?$show_hidden:($rowTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
				//}
				
				//MODIF CBY

	  		    $this->metafeeditlib->getParentJoin($conf,$sqlrow,$rowTable);
				$conf['parentObj']=&$this->feadminlib;
				if ($conf['grid.']['userFunc_afterRowWhere']) t3lib_div::callUserFunction($conf['grid.']['userFunc_afterRowWhere'],$conf,$conf['parentObj']);
				
				if ($conf['parentObj']->conf['grid.']['row.']['whereString']) $sqlrow['where'].=$conf['parentObj']->conf['grid.']['row.']['whereString'];
				
				if ($conf['debug.']['sql']) $DEBUG.="<br/>Row Field SQL 1 <br/>".t3lib_div::view_array($sqlrow);   

				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sqlrow['fields'].','.$conf['TCAN'][$rowTable]['ctrl']['label'],$sqlrow['fromTables'],$sqlrow['where']);

				if ($conf['debug.']['sql']) $DEBUG.="<br/>Row Field SQL 2 <br/>".$GLOBALS['TYPO3_DB']->SELECTquery($sqlrow['fields'].','.$conf['TCAN'][$rowTable]['ctrl']['label'],$sqlrow['fromTables'],$sqlrow['where']);   
				
				while ($item=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { 
				    //print_r($row);
					$nbRows++;
					$rowId[$nbRows]=$item['uid'];
					$rowLabel[$nbRows]=$item[$conf['TCAN'][$rowTable]['ctrl']['label']];
				}
			}

			// Grid Col Data loop
			if (!$nbCols) {
				$colTable=$conf['TCAN'][$table]['columns'][$colField]['config']['foreign_table'];
				//CBY MODIF
				$secondaryTable=$conf['TCAN'][$table]['columns'][$secondaryColFields]['config']['foreign_table'];
				$colWhere=$conf['grid.']['colfield.']['whereString'];
				$sqlcol=array();
				$sqlcol['where']=' 1 ';
				$sqlcol['fromTables']=$colTable;
				$sqlcol['fields']=$colTable.'.uid'.(array_key_exists('series',$conf['TCAN'][$colTable]['columns'])?','.$colTable.'.series':'');
				if ($conf['grid.']['col.']['fUField'] || $conf['grid.']['col.']['fUKeyField']) {			
					if ($conf['grid.']['col.']['fUField']) $conf['fUField']=$conf['grid.']['col.']['fUField'];
					if ($conf['grid.']['col.']['fUKeyField']) $conf['fUKeyField']=$conf['grid.']['col.']['fUKeyField'];
					/*if ($conf['enableColumns']) {
						$sqlcol['where'].= $GLOBALS['TSFE']->sys_page->enableFields($colTable,$show_hidden?$show_hidden:($colTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
					}*/
					$this->metafeeditlib->getFUJoin($conf,$sqlcol,$colTable);		
					
				}
				if ($conf['enableColumns']) {
					$sqlcol['where'].= $GLOBALS['TSFE']->sys_page->enableFields($colTable,$show_hidden?$show_hidden:($colTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
				}

				//MODIF CBY
				//if ($conf['enableColumns']) {
				//	$sqlcol['where'].= $GLOBALS['TSFE']->sys_page->enableFields($colTable,$show_hidden?$show_hidden:($colTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
				//}
			  $this->metafeeditlib->getParentJoin($conf,$sqlcol,$colTable);
				$secondaryColField=$secondaryColFields; // TODO : Handle multiple fields here ...

				//MODIF CBY

				if ($secondaryTable) {
					$sql2=array();

					$sql2['where']=' 1 ';
					$sql2['fromTables']=$secondaryTable;
					$sql2['fields']=$secondaryTable.'.uid';
					if ($conf['grid.']['secondcols.'][$secondaryColField.'.']['fUField'] || $conf['grid.']['secondcols.'][$secondaryColField.'.']['fUKeyField']) {			
						if ($conf['grid.']['secondcols.'][$secondaryColField.'.']['fUField']) $conf['fUField']=$conf['grid.']['secondcols.'][$secondaryColField.'.']['fUField'];
						if ($conf['grid.']['secondcols.'][$secondaryColField.'.']['fUKeyField']) $conf['fUKeyField']=$conf['grid.']['secondcols.'][$secondaryColField.'.']['fUKeyField'];
						if ($conf['enableColumns']) {
							$sql2['where'].= $GLOBALS['TSFE']->sys_page->enableFields($secondaryTable,$show_hidden?$show_hidden:($colTable=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords));
						}
						$this->metafeeditlib->getFUJoin($conf,$sql2,$secondaryTable);					
					}
					//MODIF CBY
			  	$this->metafeeditlib->getParentJoin($conf,$sql2,$secondaryTable);
					if ($conf['grid.']['userFunc_afterSecondaryColWhere']) t3lib_div::callUserFunction($conf['grid.']['userFunc_afterSecondaryColWhere'],$conf,$conf['parentObj']);
					if ($conf['parentObj']->conf['grid.']['col.']['secondaryWhereString']) $sql2['where'].=$conf['parentObj']->conf['grid.']['col.']['secondaryWhereString'];
					//print_r($sql2);
				}
				$conf['parentObj']=&$this->feadminlib;
				//print_r($conf['grid.']);
				if ($conf['grid.']['userFunc_afterColWhere']) t3lib_div::callUserFunction($conf['grid.']['userFunc_afterColWhere'],$conf,$conf['parentObj']);
				if ($conf['parentObj']->conf['grid.']['col.']['whereString']) $sqlcol['where'].=$conf['parentObj']->conf['grid.']['col.']['whereString'];
				if ($conf['debug.']['sql']) $DEBUG.="<br/>Col Field SQL <br/>".t3lib_div::view_array($sqlcol);   
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sqlcol['fields'].','.$conf['TCAN'][$colTable]['ctrl']['label'],$sqlcol['fromTables'],$sqlcol['where']);
				if ($conf['debug.']['sql']) $DEBUG.="<br/>Col Field SQL <br/>".$GLOBALS['TYPO3_DB']->SELECTquery($sqlcol['fields'].','.$conf['TCAN'][$colTable]['ctrl']['label'],$sqlcol['fromTables'],$sqlcol['where']);   
				if (!$secondaryTable) {				
					while ($item=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { 
						$nbCols++;
						$colId[$nbCols]=$item['uid'];
						$colLabel[$nbCols]=$item[$conf['TCAN'][$colTable]['ctrl']['label']];
					}
				} else {
					if ($conf['debug.']['sql']) $DEBUG.="<br/>Secondary Col Fields SQL <br/>".t3lib_div::view_array($sql2);   
					$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sql2['fields'].','.$conf['TCAN'][$secondaryTable]['ctrl']['label'],$sql2['fromTables'],$sql2['where']);
					$nb2=$GLOBALS['TYPO3_DB']->sql_num_rows($res2);
					if ($nb2>0) {
						$nosec=0; //flag for secondary table procesisng
						while ($item=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { 
							$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery($sql2['fields'].','.$conf['TCAN'][$secondaryTable]['ctrl']['label'],$sql2['fromTables'],$sql2['where']);
							while ($item2=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) { 
								//TODO Replace by TS ...
								if ($nosec) continue;
								if ($item['series'] ) {
									$nbCols++;
									$colId[$nbCols]=$item['uid'];
									$colId2[$nbCols]=$item2['uid'];
									$colLabel[$nbCols]=$item[$conf['TCAN'][$colTable]['ctrl']['label']].'<br/>'.$item2[$conf['TCAN'][$secondaryTable]['ctrl']['label']];
								} else {
									$nosec=1;
									$colsec[$nbcols]=0;
									$nbCols++;
									$colId[$nbCols]=$item['uid'];
									$colId2[$nbCols]=0;
									$colLabel[$nbCols]=$item[$conf['TCAN'][$colTable]['ctrl']['label']];
								}
							}
							//echo "nbcols : $nbCols colid $colId[$nbCols]<br>";

							$nosec=0;
						}
					} else {
						while ($item=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) { 
							$nbCols++;
							$colId[$nbCols]=$item['uid'];
							$colLabel[$nbCols]=$item[$conf['TCAN'][$colTable]['ctrl']['label']];
						}
					}
				}
			}
				
			$templateCode = $this->metafeeditlib->getPlainTemplate($conf,$this->feadminlib->markerArray,'###TEMPLATE_GRID'.($exporttype?'_'.$exporttype:'').'###');
			
			$allItemsCode = $this->cObj->getSubpart($templateCode, '###GRID###');
			$itemRowCode = $this->cObj->getSubpart($allItemsCode, '###GRID-ROW###');
			$itemCode = $this->cObj->getSubpart($itemRowCode, '###GRID-ITEM###');
			$elCode = $this->cObj->getSubpart($itemCode, '###GRID-EL###');
			
			$i=0;
			$j=0;
			$cMarkerArray=array();
			$row='';
			while ($j <$nbCols) {
		  	$j++;
		  	$cLabel=$j;
			  if ($colLabel[$j]) $cLabel=$colLabel[$j];
				$row.=$this->cObj->substituteSubpart($itemCode, '###GRID-EL###', $cLabel);
			}
			// HEADER MODIF CBY
			$rMarkerArray['###GRID-ROW-ALT###']=0;
			$rMarkerArray['###ROWLABEL###']=$this->metafeeditlib->getLL("grid_rows_cols",$conf); //'Rows\Cols';
			
			$rows.=$this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($itemRowCode, '###GRID-ITEM###', $row),$rMarkerArray);

			foreach($conf['additionalJS_end'] as $key=>$value) {
				$additionalJS_end[$key]=$value;
				unset($conf['additionalJS_end'][$key]);
			}
			
			$nbAltRow=$conf['grid.']['nbAltRows'];
			$nar=1;
			while ($i <$nbRows) {
				$i++;
				$j=0;
				$row='';
				$rLabel=$i;
				$rData=$i;

				if ($rowLabel[$i])
					$rLabel=$rowLabel[$i];

				if ($rowId[$i])
					$rData=$rowId[$i];
					
				$rMarkerArray['###ROWLABEL###']=$rLabel;
			
				while ($j <$nbCols) {
					unset ($cMarkerArray);
					$j++;
					//$cData=$j;
					// Colonne simple
					if ($colId[$j] && !$colId2[$j]) {
						$cData=$colId[$j];
						$cell='cell-'.$rData.'-'.$cData;
						$cellMarker="[grid][$rData][$cData]";
						$cMarkerArray['###GRIDCELL###']=$cellMarker;						
						$style="";
						$color='';
						$codeJS='';
						$cMarkerArray['###GRIDCELLALT###']="$colLabel[$j]";
						if ($cella[$rData][$cData][$conf['uidField']]) {		
							$cMarkerArray['###HIDDENCELLFIELDS###']='<input type="hidden" name="FE['.$table.'][grid]['.$rData.']['.$cData.']['.$conf['uidField'].']" value="'.$cella[$rData][$cData][$conf['uidField']].'">';
							$cMarkerArray=$this->cObj->fillInMarkerArray($cMarkerArray, $cella[$rData][$cData], '', TRUE, 'FIELD_', $conf['recInMarkersHSC']);
							$codeJS=$this->cObj->getUpdateJS($this->feadminlib->modifyDataArrForFormUpdate($cella[$rData][$cData]), $conf['table'].'_form',  'FE['.$conf['table'].'][grid]['.$rData.']['.$cData.']',$conf['grid.']['show_fields']);
							if (is_array($additionalJS_end)) foreach($additionalJS_end as $key=>$value) {
								$conf['additionalJS_end'][$key.'-'.$rData.'-'.$cData]=$this->cObj->substituteMarker($value,'###GRIDCELL###',$cellMarker);
							}
							$sqlMode='<input type="hidden" name="FE['.$table.'][grid-sqlmode]['.$rData.']['.$cData.']" value="update">';
						} 
						else {
							$cMarkerArray['###HIDDENCELLFIELDS###']='';
							$sqlMode='<input type="hidden" name="FE['.$table.'][grid-sqlmode]['.$rData.']['.$cData.']" value="insert">';
						}
					} else if ($colId[$j] && $colId2[$j]) {
						$cData=$colId[$j];
						$cData2=$colId2[$j];
						$cell='cell-'.$rData.'-'.$cData.'-'.$cData2;
						$cellMarker="[grid][$rData][$cData][$cData2]";
						$cMarkerArray['###GRIDCELL###']=$cellMarker;						
						$style="";
						$color='';
						$codeJS='';
						$cMarkerArray['###GRIDCELLALT###']="$colLabel[$j]";
						//echo "<br>$rData, $cData,$cData2"; print_r($cella2[$rData][$cData][$cData2]);
						if ($cella2[$rData][$cData][$cData2]) {		
							$cMarkerArray['###HIDDENCELLFIELDS###']='<input type="hidden" name="FE['.$table.'][grid]['.$rData.']['.$cData.']['.$cData2.']['.$conf['uidField'].']" value="'.$cella2[$rData][$cData][$cData2][$conf['uidField']].'">';
							$cMarkerArray=$this->cObj->fillInMarkerArray($cMarkerArray, $cella2[$rData][$cData][$cData2], '', TRUE, 'FIELD_', $conf['recInMarkersHSC']);
							$codeJS=$this->cObj->getUpdateJS($this->feadminlib->modifyDataArrForFormUpdate($cella2[$rData][$cData][$cData2]), $conf['table'].'_form',  'FE['.$conf['table'].'][grid]['.$rData.']['.$cData.']['.$cData2.']',$conf['grid.']['show_fields']);
							foreach($additionalJS_end as $key=>$value) {
								$conf['additionalJS_end'][$key.'-'.$rData.'-'.$cData.'-'.$cData2]=$this->cObj->substituteMarker($value,'###GRIDCELL###',$cellMarker);
							}
							$sqlMode='<input type="hidden" name="FE['.$table.'][grid-sqlmode]['.$rData.']['.$cData.']['.$cData2.']" value="update"/><input type="hidden" name="FE['.$table.'][nbcols]" value="2" />';
						} 
						else {
							$cMarkerArray['###HIDDENCELLFIELDS###']='';
							$sqlMode='<input type="hidden" name="FE['.$table.'][grid-sqlmode]['.$rData.']['.$cData.']['.$cData2.']" value="insert"/><input type="hidden" name="FE['.$table.'][nbcols]" value="2"/>';
						}
					}
					// we handle cell data here ...
					$arr=t3lib_div::trimexplode(',',$conf['grid.']['show_fields']);
					foreach($arr as $key) {
						if (!$cMarkerArray['###EVAL_ERROR_FIELD_'.$key.'###']) $cMarkerArray['###EVAL_ERROR_FIELD_'.$key.'###']='';
					}

					$style='style="background:'.$color.';"';
					if ($exporttype)
					$r=$elCode; //.$codeJS;  //$sqlMode.
					else
					$r='<div id="'.$cell.'" class="cell" '.$style.'>'.$elCode.$sqlMode.$codeJS.'</div>';
					
					$row.=$this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($itemCode, '###GRID-EL###', $r), $cMarkerArray);					
				}
				
				// alternate template marker ...
				$rMarkerArray['###GRID-ROW-ALT###']=$nar;
				$nar++;
				if ($nar>$nbAltRow)$nar=1;
				$rows.=$this->cObj->substituteMarkerArray($this->cObj->substituteSubpart($itemRowCode, '###GRID-ITEM###', $row),$rMarkerArray);
			}
	 	    $content=$this->cObj->substituteSubpart($allItemsCode, '###GRID-ROW###', $rows);
			$content=$this->cObj->substituteSubpart($templateCode, '###GRID###', $content);
			$content=$this->cObj->substituteMarker($content,'###ACTIONS-GRID-EL###','');
			//$content=$this->cObj->substituteMarker($content,'###ACTIONS-GRID-EL###',$this->metafeeditlib->getGridItemActions($conf,$this->feadminlib,$cMarkerArray));
			$content=$this->cObj->substituteMarker($content,'###ACTIONS-GRID-TOP###',$this->metafeeditlib->getGridTopActions($conf,$this->feadminlib));
  		$content=$this->cObj->substituteMarker($content,'###ACTIONS-GRID-BOTTOM###',$this->metafeeditlib->getGridBottomActions($conf,$this->feadminlib));
  		$conf['markerArray']['###HIDDENFIELDS###']='';
  		$content=$this->cObj->substituteMarkerArray($content,$conf['markerArray']);
		
	
		switch ($exporttype)
		{
			case "CSV":
			{	
				header("Content-Type: application/csv; charEncoding=utf-8");
				header("Content-disposition: filename=table.csv");
				echo utf8_decode($content);
				die;
			}
			
			case "PDF":
			{
				$xml = new SimpleXMLElement($content);
				$count = 0;
				$taille = 0;
				if($xml->tr) {
					foreach ($xml->tr->td as $pouet) {
					$taille = $taille + $pouet->size;
					}
				}
				$taille = $taille / 10;   // pour avoir la taille totale des colonnes en millim�tre

				if ($taille <21)		// La feuille est de dimension 21 x 29.7
				$orientation='P';		// portrait
				else
				$orientation='L';		// paysage
				
				$format=A4;
				$unit='mm';
				$pdf = new MyPDF($orientation, $unit, $format);
				$pdf->AliasNbPages();
				$pdf->setMargins(8,12,8);
				$pdf->AddPage();
				
				// titre de la page - Il est d�finit ici et non dans le header pour qu'il ne soit pas pr�sent sur chaque page mais seulement la 1�re
				$titre =''; 
				$titre = $GLOBALS['TSFE']->page['title'];
				
				$pdf->SetFont('Arial','B',11);
				$pdf->SetY(15);
				$pdf->Cell(0,15,utf8_decode($titre),0,0,'C');	
				$pdf->SetFont('Arial','',8);
				$pdf->Ln();
				$alt=0;
			
				// Contenu
				$pdf->setFillColor(125,125,125);
				$value ='';
				foreach($xml->tr as $row) {
				   
				   if ($alt>1) {							// changement de couleur 1 ligne sur 2
					 $alt=0;
					 $pdf->setFillColor(200,200,200);
				   }
				   $alt++;
		
				
					foreach($row->td as $col) {
						$val = $col->data;
						$result = preg("/(^[0-9]+([\.0-9]*))$/" , $val);
						
						if ($conf['grid.']['gridEuros'] && $result) {  
						$value = $val.' �';
						}
						else {
						$value = $col->data;
						}
						$pdf->Cell($col->size?$col->size:18,11,utf8_decode($value),1,0,'C',1);
					} 				
					$pdf->Ln();
					$pdf->setFillColor(255,255,255);
				}
				
				
				$pdf->Output();
				//Convert to PDF
				$content = $pdf->Output('test.pdf', 'S');
				echo $content;
				die;
			}
			
			case "EXCEL":
			{
				header("Content-Type: apllication/xls");
				header("Content-disposition: filename=table.xls");
				echo utf8_decode($content);
				die;
			}
		}

		
			return $content.$conf['debug.']['debugString'].$DEBUG;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_grid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_grid.php']);
}

?>
