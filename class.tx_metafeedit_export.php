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
* This is a Class for generating records in some files like CSV, PDF EXCEL, it generated buttons too.
* called by fe_adminLib.php
* @author      Christophe BALISKY <cbalisky@metaphore.fr>
*/
//define('FPDF_FONTPATH',t3lib_extMgm::extPath('meta_feedit').'res/fonts/');
define('EURO',chr(128));

if (t3lib_extMgm::isLoaded('fpdf')) require_once(t3lib_extMgm::extPath('fpdf').'class.tx_fpdf.php');

class tx_metafeedit_pdf extends FPDF {
	var $cellsize;
	var $headercellsize;
	var $footercellsize;
	var $leftmargin;
	var $topmargin;
	var $rightmargin;
	var $bottommargin;
	var $nofooter=false;
	var $caller;
	var $conf;
	var $javascript;
	var $n_js;
		
	function Header()
	{
		// Logo - present sur toutes les pages du pdf
		//$logo = PATH_site.($xml->tr->td->img->dir).'logo.png';
		//$this->Image($logo,1,1,17,8);
	}
	
	function Footer()
	{
		if (!$this->nofooter) {
			$this->SetFont('Arial','',10);
			$this->setFillColor(200,200,200);
			//$this->SetY(200); //TODO		
			$this->SetY(-$this->bottommargin); //TODO		
			$date=date('d/m/Y H:i:s');
			$this->SetX($this->leftmargin);
			$this->SetY(-$this->bottommargin-3);
			$this->Cell(0,1,'','T',0,'C',0);
			$this->SetY(-$this->bottommargin);
			$this->SetX($this->leftmargin);
			//$this->Cell(0,$this->footercellsize,'YOOOO', 0,0,'C',0);
			$this->Cell(0,$this->footercellsize, $this->confTS[$this->pluginId.'.']['list.']['footer'], 0,0,'C',0);
			$this->SetX($this->leftmargin);
			
			$this->Cell(0,$this->footercellsize, $date, 0,0,'L');
			$this->SetX($this->leftmargin);
			$this->Cell(0,$this->footercellsize,'Page '.$this->PageNo().'/{nb}',0,0,'C');
			$this->SetX($this->leftmargin);			
			$user = '';
			$user = $GLOBALS['TSFE']->fe_user->user[username];
			$this->Cell(0,$this->footercellsize, utf8_decode($this->caller->caller->metafeeditlib->getLL('printedby', $this->caller->conf)).$user,0,0,'R');
		}
	}
	
	function IncludeJS($script) {
		$this->javascript=$script;
	}
	
	function _putjavascript() {
		$this->_newobj();
		$this->n_js=$this->n;
		$this->_out('<<');
		$this->_out('/Names [(EmbeddedJS) '.($this->n+1).' 0 R]');
		$this->_out('>>');
		$this->_out('endobj');
		$this->_newobj();
		$this->_out('<<');
		$this->_out('/S /JavaScript');
		$this->_out('/JS '.$this->_textstring($this->javascript));
		$this->_out('>>');
		$this->_out('endobj');
	}
	
	function _putresources() {
		parent::_putresources();
		if (!empty($this->javascript)) {
			$this->_putjavascript();
		}
	}
	
	function _putcatalog() {
		parent::_putcatalog();
		if (!empty($this->javascript)) {
			$this->_out('/Names <</JavaScript '.($this->n_js).' 0 R>>');
		}
	}

	function AutoPrint($dialog=false)
	{
		//Open the print dialog or start printing immediately on the standard printer
		$param=($dialog ? 'true' : 'false');
		$script="print($param);";
		$this->IncludeJS($script);
	}
	
	function AutoPrintToPrinter($server, $printer, $dialog=false)
	{
		//Print on a shared printer (requires at least Acrobat 6)
		$script = "var pp = getPrintParams();";
		if($dialog)
		$script .= "pp.interactive = pp.constants.interactionLevel.full;";
		else
		$script .= "pp.interactive = pp.constants.interactionLevel.automatic;";
		$script .= "pp.printerName = '\\\\\\\\".$server."\\\\".$printer."';";
		$script .= "print(pp);";
		$this->IncludeJS($script);
	}
	/**
	 * 
	 * Counts line numbers for multi-cell
	 * @param width $w
	 * @param string $txt
	 * @return number
	 */
	function NbLines($w,$txt)
	{
		//Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
		$cw=&$this->CurrentFont['cw'];
		if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 and $s[$nb-1]=="\n")
		$nb--;
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$nl=1;
		while($i<$nb)
		{
			$c=$s[$i];
			if($c=="\n")
			{
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
				continue;
			}
			if($c==' ')
			$sep=$i;
			$l+=$cw[$c];
			if($l>$wmax)
			{
				if($sep==-1)
				{
					if($i==$j)
					$i++;
				}
				else
				$i=$sep+1;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
			}
			else
			$i++;
		}
		return $nl;
	}	
}

class tx_metafeedit_export {
	var $prefixId = "tx_metafeedit";		// Same as class name
	var $conf;								// $conf array from metafeedit_lib
	var $pluginId;							// ID du plugin
	var $confTS;							// TS du plugin
	var $cObj;
	var $caller;
	/**
	 * Initialisation function called by fe_adminlib.php allow this class to acces $conf array
	 *
	 * @return	nothing
	 */
	function init(&$caller) {
		$this->caller=$caller;
		$this->conf = $caller->conf;
		$this->pluginId = $caller->conf['pluginId'];
		//$this->confTS = $caller->conf['typoscript.']['metafeedit.']['typoscript.'];
		//why go in metafeedit.typoscript
		$this->confTS = $caller->conf['typoscript.'];
		$this->cObj=$caller->cObj;
	}

	/**
	 * CreatePDFButton
	 *
	 * @return	[type]		...
	 */
	//------------------------------------------------------- PDF / CSV / EXCEL Buttons (should be in template generating File) ------------------------------------------------ //
	function CreatePDFButton(&$conf,&$caller,$form=true,$id='') {
		$href=$caller->metafeeditlib->hsc($conf,$caller->pi_linkTP_keepPIvars_url(array(),1));
		// traitement du champ PDF
		$onclick=($form?'':' onclick="document.'.$conf['table'].'_form.tx_metafeedit_exporttype.value=\'PDF\'"');
			return '<div class="'.$caller->pi_getClassName('action').' '.$caller->pi_getClassName('action-pdf').'">'.
			($form?'<form action="'.$href.'?###GLOBALPARAMS###" method="post" target="_blank">
				<input type="hidden" name="no_cache" value="1"/>
				<input type="hidden" id="PDF'.$id.'" name="tx_metafeedit[exporttype]" value="PDF"/>':'').
				'<input type="submit" class="btnPDF" name="'.$this->prefixId.'[submit_button]"'.$onclick.' value="Impression PDF" id="submitPDF'.$id.'" title="Impression PDF"/><br/>'.
			($form?'</form>':'').
			'</div>';
	}
	
	function CreatePDFButtonDetail(&$conf,&$caller,$form=true,$id='') {
		$href=$caller->metafeeditlib->hsc($conf,$caller->pi_linkTP_keepPIvars_url(array(),1));
		// traitement du champ PDF
			$onclick=($form?'':' onclick="document.'.$this->pluginId.'_form.PDF'.$this->pluginId.'_rU.value='.$id.';document.'.$this->pluginId.'_form.submit();return false;"');
			return '<div class="'.$caller->pi_getClassName('action').' '.$caller->pi_getClassName('action-pdf').'">'.
			($form?'<form action="'.$href.'?###GLOBALPARAMS###" method="post" target="_blank">
				<input type="hidden" name="no_cache" value="1"/>
				<input type="hidden" id="PDF'.$this->pluginId.'_et" name="tx_metafeedit[exporttype]" value="PDF"/>'.
				'<input type="hidden" id="PDF'.$this->pluginId.'_cmd" name="cmd['.$this->pluginId.']" value="edit"/>'.
				'<input type="hidden" id="PDF'.$this->pluginId.'_rU" name="rU['.$this->pluginId.']" value="'.$id.'"/>':'').
				'<input type="submit" class="btnPDF" name="'.$this->prefixId.'[submit_button]"'.$onclick.' value="Impression PDF" id="submitPDF'.$id.'" title="Impression PDF"/><br/>'.
			($form?'</form>':'').
			'</div>';
	}
	//cmd[spectateurs]=edit&rU[spectateurs]=43105
	
	function CreateCSVButton(&$conf,&$caller,$form=true,$id='') {	
		$href=$caller->metafeeditlib->hsc($conf,$caller->pi_linkTP_keepPIvars_url(array(),1));

		$onclick=($form?'':' onclick="document.'.$conf['table'].'_form.tx_metafeedit_exporttype.value=\'CSV\'"');
			return '<div class="'.$caller->pi_getClassName('action').' '.$caller->pi_getClassName('action-csv').'">'.
			($form?'<form action="'.$href.'?###GLOBALPARAMS###" method="post">
				<input type="hidden" name="no_cache" value="1"/>
				<input type="hidden" id="CSV'.$id.'" name="tx_metafeedit[exporttype]" value="CSV"/>':'').
				'<input type="submit" class="btnCSV" name="'.$this->prefixId.'[submit_button]"'.$onclick.' value="Export CSV" id="submitCSV'.$id.'" title="Export CSV"/><br/>'.
			($form?'</form>':'').
			'</div>';
	}
	
	function CreateExcelButton(&$conf,&$caller,$form=true,$id='') {	
		$href=$caller->metafeeditlib->hsc($conf,$caller->pi_linkTP_keepPIvars_url(array(),1));
		$onclick=($form?'':' onclick="document.'.$conf['table'].'_form.tx_metafeedit_exporttype.value=\'EXCEL\'"');
		return '<div class="'.$caller->pi_getClassName('action').' '.$caller->pi_getClassName('action-excel').'">'.
		($form?'<form action="'.$href.'?###GLOBALPARAMS###" method="post">
			<input type="hidden" name="no_cache" value="1"/>
			<input type="hidden" id="XLS'.$id.'" name="tx_metafeedit[exporttype]" value="EXCEL"/>':'').
			'<input type="submit" class="btnXLS" name="'.$this->prefixId.'[submit_button]"'.$onclick.' value="Export Excel" id="submitXLS'.$id.'" title="Export Excel"/><br/>'.
		($form?'</form>':'').
		'</div>';
	}


	// We handle here CSV file generation ...
	function getCSV(&$content,&$caller) {
		// We handle the header here 
		ob_clean();
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);		
		
		header("Content-Type: application/csv; charEncoding=utf-8");
		//header("Content-Encoding:utf-8");
		//header("Content-Length: ".strlen($content);
		//header("Content-type: application/force-download");
		//header("Content-Transfer-Encoding: Binary");
		//header("Content-Disposition: attachment; filename=somefilename.extention");
		$content= utf8_decode(str_replace('&euro;','Eur',str_replace('&nbsp;',' ',strip_tags($caller->metafeeditlib->T3StripComments($content)))));
		//header("Content-length: ".strlen($content);
		header('Content-disposition: attachment; filename="'.$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.csv"');		
		echo $content;
		die;
	}


	// We handle here PDF file generation for detail ...
	function getPDFDET(&$content,&$caller,$print='') {
		//die($content);

		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
			//print_r($xml);
			//die(yoo);
		} catch (Exception $e) {
			echo 'PDF Detail Template error : '.$e->getMessage().'<br>';
			echo "============================<br>";
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			echo "============================<br>";
			die();
		};
		$count = 0;
		$taille = 0;
		$taillemax = 0;
		$fields = explode(',', $this->conf['list.']['show_fields']); //liste des champ affiches afin de recuperer la dimension des colonnes defini en TS
		$sizeArr = array(); //tableau de la taille des cellules
		$pos=array(); // Array of positions (left,right,center)
		$x=0; //compteur des colonnes
		if($xml->tr) {
			$taille = 0;
			if ($taille>$taillemax) $tailemax=$taille;			
			foreach ($xml->tr->td as $cell) {
				$fields[$x]=str_replace('.','_',$fields[$x]);
				$taille += ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$sizeArr[$x] = ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$pos[$x]=($this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]])?$this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]]:(($this->confTS['default.']['list.']['align.'][$fields[$x]])?$this->confTS['default.']['list.']['align.'][$fields[$x]]:($this->confTS['list.']['align.'][$fields[$x]]?$this->confTS['list.']['align.'][$fields[$x]]:'left'));
				$x++;
			}
		}
		
		// La feuille est de dimension 21 x 29.7- cmd reduit a 20 pour conserver la marge
		if ($taillemax <200) $orientation='P';	// portrait
		else $orientation='L';				// paysage

		$format=A4;
		$noheader=false;
		$nofooter=false;
		
		// We handle special settings here
		
		foreach($xml->spec as $s) {
				$noheader=$s->attributes()->nh;
				$nofooter=$s->attributes()->nf;
				$margintop=$s->attributes()->mt;		
				$marginleft=$s->attributes()->ml;		
				$marginright=$s->attributes()->mr;		
				$marginbottom=$s->attributes()->mb;		
				$format=array($s->attributes()->w,$s->attributes()->h);
		}
		$unit='mm';
		$pdf = new tx_metafeedit_pdf($orientation, $unit, $format);
		$pdf->caller=&$this;
		$pdf->AddFont('3OF9','','3OF9.php');
		$pdf->nofooter=$nofooter;
		// TODO Handle typoscript here ...
		
		
		$pdf->bottommargin=$marginbottom?$marginbottom:8;
		$pdf->leftmargin=$marginleft?$marginleft:8;
		$pdf->rightmargin=$marginright?$marginright:8;
		$pdf->topmargin=$margintop?$margintop:8;
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=7;
				
		$pdf->AliasNbPages();
		$pdf->setMargins($pdf->leftmargin,$pdf->topmargin,$pdf->rightpmargin);
		$pdf->SetAutoPageBreak(1,$pdf->bottommargin);
		$pdf->AddPage();
		
		$pdf->SetDisplayMode('real','single');

		// We handle the header here 
		//
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);

		//if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		if (!$noheader) {
			$pdf->SetFont('Arial','B',11);
			$pdf->SetY(0);
			$pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');	
			$pdf->SetFont('Arial', '', 9);
			$pdf->Cell(0,$this->headercellsize,utf8_decode($recherche),0,0,'R');
			$pdf->SetY(5);
			$pdf->Cell(0,$this->headercellsize,$tri,0,0,'C');
			$pdf->SetXY($pdf->leftmargin,6);
			//$pdf->Ln();
			$pdf->setFillColor(200,200,200);
			$pdf->Cell(0,$this->headercellsize,'','T',0,'C');
			$pdf->Ln(2);	
		}
		
		$fs=9;
		$cell=false;
		// We set height and font
		
		$this->getPolice($police);
		$this->getFont($font);
		$pdf->SetFont($font,'',$police);
		$alt=0;

		// Content
		$pdf->setFillColor(125,125,125);
		$height = ($this->confTS[$this->pluginId.'.']['list.']['height'])?$this->confTS[$this->pluginId.'.']['list.']['height']:(($this->confTS['default.']['list.']['height'])?$this->confTS['default.']['list.']['height']:($pdf->cellsize?$pdf->cellsize:5)); // hauteur de la ligne pdf
		$r=0;
		// We print rows...
		foreach($xml->tr as $row) {
			if (@$row->spec->attributes()->ap) $pdf->addPage();
			// we change color 
			$x=0; //compteur des colonnes
			if ($alt>1) {							// changement de couleur 1 ligne sur 2
				$alt=0;
				$pdf->setFillColor(200,200,200);
			}
			$alt++;
			$nbcols=count($row->td);
			$csize=0;
			$ey=0;
			if ($row->gb) {
				$pdf->SetLineWidth(0.3);
				$pdf->SetFont('Arial', 'B', 9);
			} else {
				$pdf->SetLineWidth(0.2);
				$pdf->SetFont('Arial', '', 9);
			}
			$ih=0;
			$nblines=1;
			//We detect height
			foreach($row->td as $col) {
				$val = strip_tags($col->data);
				if ($col->img==1) {
					$imgh=30;
					if (isset($col->img->attributes()->h)) $imgh=(float)$col->img->attributes()->h; //height override
					$nblines=5;
				} else {
					$size = $nbcols==1?$taille:$sizeArr[$x]; //taille de la cellule
					$w=$size=40;
					if (isset($col->spec->attributes()->h)) $h=$col->spec->attributes()->h;
					if (isset($col->spec->attributes()->w)) $w=$col->spec->attributes()->w;
					$nblines=$pdf->NbLines($w, utf8_decode($val));
				}
			}
			// We print row cells ...
			foreach($row->td as $col) {
				$size = $nbcols==1?$taille:$sizeArr[$x]; //taille de la cellule 
				$size=40;
				$val = strip_tags($col->data);
				$utf8val=utf8_decode($val);
				$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);
				// Affichage du signe Euro sur l'export PDF //CBY nothing to do here !!
				if ($this->conf['list.']['euros'] && $result) $val .= ' Eur';
				//if we have an image
				if ($col->img==1 && strlen($val)>0) {	 				// We handle images here...
					$vala=t3lib_div::trimexplode(',',$val);
					$img='';
				 	$myx=isset($col->spec->attributes()->x)?(float)$col->spec->attributes()->x:$pdf->getX();
				 	$oldy=$pdf->GetY();
				 	if (isset($col->spec->attributes()->y)) {				 		
				 		$pdf->SetY((float)$col->spec->attributes()->y);
				 	}
				 	//Image border
				 	$ib=0;
				 	if ($col->img->attributes()->b) $ib=1;
					if (!is_object($col->spec)) $pdf->Cell($taille,$height,'',1,0,'L',1);
					$pdf->SetX($myx);
					foreach($vala as $v) {
						$img=PATH_site.($v?$col->img->dir.'/'.$v:'');
						if ($col->img->attributes()->gh || $col->img->attributes()->gw || $col->img->attributes()->mh || $col->img->attributes()->mw ) {
						
							if ($col->img->attributes()->gw ) $fileA['file.']['width']=(string)$col->img->attributes()->gw ;
							if ($col->img->attributes()->gh ) $fileA['file.']['height']=(string)$col->img->attributes()->gh;
							if ($col->img->attributes()->mw ) $fileA['file.']['maxW']=(string)$col->img->attributes()->mw ;
							if ($col->img->attributes()->mh ) $fileA['file.']['maxH']=(string)$col->img->attributes()->mh;
							$imgi=$caller->cObj->getImgResource($col->img->dir.'/'.$v,$fileA['file.']);
							if ($imgi[3]) $img=$imgi[3];
						}
						//if files on linux / nt utf8 encoded 
						$img=utf8_decode($img);
						$imginfo=getimagesize($img);
						if (is_array($imginfo)) {
							$w=$imginfo[0];
							$h=$imginfo[1];
							//@todo resize or crop if image too big
							$imgh=$nblines*$height;
							$imgx=$pdf->GetX();
							$imgy=$pdf->GetY();
							$imgw=0;//We do not stretch image
							if (isset($col->img->attributes()->h)) $imgh=(float)$col->img->attributes()->h; //height override
							if (isset($col->img->attributes()->w)) $imgw=(float)$col->img->attributes()->w; //width override
							if (isset($col->img->attributes()->x)) $imgx=(float)$col->img->attributes()->x; //x override
							if (isset($col->img->attributes()->y)) $imgy=(float)$col->img->attributes()->y; //x override
							$pdf->Image($img,$imgx,$imgy,$imgw,$imgh);
							//We calculate image width based on piction width/height ratio);
							if ($imgh && !$imgw) {
								$imgw=$imgh*($w/$h);
							}
							$w=$size;
							if (isset($col->spec->attributes()->w)) $w=$col->spec->attributes()->w;
							if ($ib) {
								$pdf->Rect($imgx,$imgy,$imgw,$imgh);
							}
							
							$ey = ($imgy+$imgh)>$ey?($imgy+$imgh):$ey;
						}
					}
				} else {
					switch($pos[$x]) {
						case 'left' :
							$p='L';
							break;
						case 'right' :
						  $p='R';
							break;
						case 'center' :
						  $p='C';
							break;
						default :
							$p='L';
							break;
				 	}
				 	if (!$r) $p='L'; // So that column headers are always aligned left. 
				 	
				 	$myx=$pdf->GetX();				 	
					$w=$size;
					$h=$height;
					$b=1;
					if (isset($col->spec->attributes()->b)) $b=$col->spec->attributes()->b;
					if (isset($col->spec->attributes()->h)) $h=$col->spec->attributes()->h;
					//$h=;
					
					if (isset($col->spec->attributes()->w)) $w=$col->spec->attributes()->w;
					
					if (isset($col->spec->attributes()->bc)) {
							$bca=t3lib_div::trimexplode(',',$col->spec->attributes()->bc);
							$pdf->setFillColor((int)$bca[0],(int)$bca[1],(int)$bca[2]);
					}
					
					if (isset($col->spec->attributes()->fc)) {
							$fca=t3lib_div::trimexplode(',',$col->spec->attributes()->fc);
							$pdf->setDrawColor($fca[0],$fca[1],$fca[2]);
					}
					if (isset($col->spec->attributes()->tc)) {
							$tca=t3lib_div::trimexplode(',',$col->spec->attributes()->tc);
							$pdf->setTextColor($tca[0],$tca[1],$tca[2]);
					}
					if (isset($col->spec->attributes()->fs)) {
						$fs=$col->spec->attributes()->fs;
						$pdf->SetFontSize($fs);
					}
					if (isset($col->spec->attributes()->f)) $pdf->SetFont($col->spec->attributes()->f,'',$fs);
					if (isset($col->spec->attributes()->x) || isset($col->spec->attributes()->y)) {
						$pdf->SetXY((float)$col->spec->attributes()->x,(float)$col->spec->attributes()->y);
						//$h=$h*$nblines;
						if (isset($col->spec->attributes()->b)  || isset($col->spec->attributes()->bc) ) {
							$pdf->Cell($w,$h,$utf8val,$b,0,$p,1);
							 $cell=true;
						} else {
							$pdf->Write($h,$utf8val);
						}
						$newX=(float)$col->spec->attributes()->x+(isset($col->spec->attributes()->w)?(float)$col->spec->attributes()->w:$pdf->GetStringWidth($val));
						$pdf->SetXY((float)floor($newX),(float)$col->spec->attributes()->y);
						//$pdf->Text($col->spec->attributes()->x,$col->spec->attributes()->y,utf8_decode($val));
					} else {
						$x = $pdf->getX();
						$y = $pdf->getY();
						$pdf->MultiCell($w,$h,$utf8val,0,0,$p,1);
						if ($b) $pdf->Rect($x,$y,$w,$h*$nblines);
						// We handle bigger cells !!!
						$ey = $pdf->getY()>$ey?$pdf->getY():$ey;
						$pdf->SetXY(($x+$w),$y);
						$cell=true;
					}
				}
				$x++;
			}
			if ($cell) {
				//$pdf->setFillColor(200,200,200);
				$pdf->setFillColor(255,255,255);
				//$pdf->Cell(0,$h,'',0,0,'',1);
				if ($ey) $pdf->SetXY($pdf->leftmargin,$ey);
				$ey=0;
				$cell=false;
			}else {
				$pdf->Ln();
			}
			$pdf->setFillColor(255,255,255);
			$r++;
		}
		ob_clean();
		//We add print dialog or not ...
		switch ($print) {
			case 'print':
			case 'printnodialog':
				$pdf->AutoPrint(false);
				break;
			case 'printdialog':
				$pdf->AutoPrint(true);
				break;
			default:
				break;
		}
		$pdf->Output($caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf', 'I');
		die;
	}	
	
	// We handle here PDF file generation for lists ...
	function getPDF(&$content,&$caller,$print='') {
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			die( 'Caught exception: '.  $e->getMessage());
		};
		$count = 0;
		$taille = 0;
		$fields = explode(',', $this->conf['list.']['show_fields']); //liste des champs affiches afin de recuperer la dimension des colonnes defini en TS
		$sizeArr = array(); //tableau de la taille des cellules
		$pos=array(); // Array of positions (left,right,center)
		$x=0; //compteur des colonnes
		if($xml->tr) {
			foreach ($xml->tr->td as $cell) {
				$fields[$x]=str_replace('.','_',$fields[$x]);
				$taille += ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$sizeArr[$x] = ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$pos[$x]=($this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]])?$this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]]:(($this->confTS['default.']['list.']['align.'][$fields[$x]])?$this->confTS['default.']['list.']['align.'][$fields[$x]]:($this->confTS['list.']['align.'][$fields[$x]]?$this->confTS['list.']['align.'][$fields[$x]]:'left'));
				$x++;
			}
		}

		// Page size is 21 x 29.7- reduced to 20 to preserve margin.
		if ($taille <200) $orientation='P';	// portrait
		else $orientation='L';				// landscape

		$format=A4;
		$unit='mm';
		$pdf = new tx_metafeedit_pdf($orientation, $unit, $format);
		$pdf->caller=&$this;

		// TODO Handle typoscript here ...
		
		$pdf->bottommargin=9;
		$pdf->leftmargin=8;
		$pdf->rightmargin=8;
		$pdf->topmargin=8;
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=7;
				
		$pdf->AliasNbPages();
		$pdf->setMargins($pdf->leftmargin,$pdf->topmargin,$pdf->rightpmargin);
		$pdf->SetAutoPageBreak(1,$pdf->bottommargin);
		$pdf->AddPage();

		// We handle the header here 
		//
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		$pdf->SetFont('Arial','B',11);
		$pdf->SetY(0);
		$pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');	
		$pdf->SetFont('Arial', '', 9);
		$pdf->Cell(0,$this->headercellsize,utf8_decode($recherche),0,0,'R');
		$pdf->SetY(5);
		$pdf->Cell(0,$this->headercellsize,$tri,0,0,'C');
		$pdf->SetXY($pdf->leftmargin,6);
		//$pdf->Ln();
		$pdf->setFillColor(200,200,200);
		$pdf->Cell(0,$this->headercellsize,'','T',0,'C');
		$pdf->Ln(2);	
		
		
		// we set font and size
		
		$this->getPolice($police);
		$this->getFont($font);
		$pdf->SetFont($font,'',$police);
		$alt=1;
		
		// Content 
		$pdf->setFillColor(125,125,125);
		$height = ($this->confTS[$this->pluginId.'.']['list.']['height'])?$this->confTS[$this->pluginId.'.']['list.']['height']:(($this->confTS['default.']['list.']['height'])?$this->confTS['default.']['list.']['height']:($pdf->cellsize?$pdf->cellsize:5)); // hauteur de la ligne pdf
		$r=0;
		foreach($xml->tr as $row) {
			$x=0; //column counter 
			if ($alt>1) {							// changement de couleur 1 ligne sur 2
				$alt=0;
				$pdf->setFillColor(200,200,200);
			}
			$cell=false;
			$alt++;
			$nbcols=count($row->td);
			$csize=0;
			if ($row->gb) {
				$pdf->SetLineWidth(0.3);
				$pdf->SetFont('Arial', 'B', 9);
			} else {
				$pdf->SetLineWidth(0.2);
				$pdf->SetFont('Arial', '', 9);
			}				

			foreach($row->td as $col) {
				$size = $nbcols==1?$taille:$sizeArr[$x]; //taille de la cellule			
				$val = str_replace('€','Eur',strip_tags($col->data));
				$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);

				// Currency handling Euro CBY should not be here !!!
				if ($this->conf['list.']['euros'] && $result) $val .= ' Eur';

				if ($col->img==1 && strlen($val)>0) {	 				// We handle images here...
					$vala=t3lib_div::trimexplode(',',$val);
					$img='';
				 	$myx=$pdf->getX();
					$pdf->Cell($size,$height,'',1,0,'L',1);
					$pdf->setX($myx);

					foreach($vala as $v) {
						$img=PATH_site.($v?$col->img->dir.'/'.$v:'');
						$imginfo=getimagesize($img);
						if (is_array($imginfo)) {
							$w=$imginfo[0];
							$h=$imginfo[1];
						
							$pdf->Image($img,$pdf->getX()+0.5,$pdf->getY()+0.5,0, $height-1);
							$pdf->setX($pdf->getX()+((($height-1)/$h)*$w));
						}
					}
					$pdf->setX($size+$pdf->leftmargin);									// + la marge definie plus haut pour la page => ligne 2308
				} else {
					switch($pos[$x]) {
						case 'left' :
							$p='L';
							break;
						case 'right' :
						  $p='R';
							break;
						case 'center' :
						  $p='C';
							break;
						default :
							$p='L';
							break;
				 	}
				 	if (!$r) $p='L'; // So that column headers are always aligned left. 
				 	$myx=$pdf->getX();
				 	if ($row->gb && !strlen($val)) {
				 		$pdf->setX($myx+$size);
				 	} else {
				 		if ($row->gb && $x==0) { 
							$pdf->Cell($taille,$height,utf8_decode($val),1,0,$p,1);
							$cell=true;
							$pdf->setX($myx+$size);
						} else {
							$border=1;
							//if ($row->gb) $border=0;
							$pdf->Cell($size,$height,utf8_decode($val),1,0,$p,1);
							$cell=true;
						}
					}
				}
				$x++;
			}
			$pdf->setFillColor(255,255,255);
			//$pdf->setX($pdf->getX()+0.1);			$pdf->setX(213.33);
			//$pdf->Cell(100000,$height,'',0,0,'L',1);
			/*if ($cell) {
				//$pdf->setFillColor(200,200,200);
				$pdf->setFillColor(255,255,255);
				$pdf->Cell(0,$h,'',0,0,'',1);
				if ($ey) $pdf->SetXY($pdf->leftmargin,$ey);
				$ey=0;
				$cell=false;
			}else {*/
				$pdf->Ln();
			//}
			//$pdf->Ln();
			$r++;
		}
		//Convert to PDF
		$name=$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf';
		ob_clean();
		//echo "#### $print";
		switch ($print) {
			case 'print':
			case 'printnodialog':
				$pdf->AutoPrint(false);
				break;
			case 'printdialog':
				$pdf->AutoPrint(true);
				break;
			default:
				break;
		}
		$pdf->Output($name, 'I'); 
		die;

	}
	
	// tabular presentation for pdf
	
	function getPDFTAB(&$content,&$caller,$print='') {
		$xml = new SimpleXMLElement($content);
		$count = 0;

		$orientation = ($this->confTS[$this->pluginId.'.']['list.']['OrientationPDF'])?$this->confTS[$this->pluginId.'.']['list.']['OrientationPDF']:(($this->confTS['default.']['list.']['OrientationPDF'])?$this->confTS['default.']['list.']['OrientationPDF']:'P');
		$format=A4;
		$unit='mm';
		$pdf = new tx_metafeedit_pdf($orientation, $unit, $format);
		$pdf->AliasNbPages();
		$pdf->setMargins(5,5,8);
		$pdf->AddPage();

		// title of the page - Il est definit ici et non dans le header pour qu'il ne soit pas present sur chaque page mais seulement la 1ere
		$title =''; 
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);

		if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		$pdf->SetFont('Arial','B',11);
		$pdf->SetY(15);
		$pdf->Cell(0,8,utf8_decode($title),0,0,'C');	
		$pdf->SetFont('Arial', '', 9);
		$pdf->SetY(20);
		$pdf->Cell(0,8,utf8_decode($recherche),0,0,'C');
		$pdf->SetY(25);
		$pdf->Cell(0,8,$tri,0,0,'C');
		$pdf->Ln();

		// on met la taille et la police d'impression
		$this->getPolice($police);
		$this->getFont($font);

		$pdf->SetFont($font,'',$police);
		$pdf->Ln();
		$alt=0;

		// Contenu
		$pdf->setFillColor(200,200,200);		// couleur de fond des cellules
		$nbx=0;		
		$cptcols = 1;
		$posy=1;
		$marginl=1;
		$marginh=1;
		$cpt=0;
		$nbcol = $this->conf['list.']['nbCols'];		// Nombre de colonnes voulues par l'utilisateur

		if ($orientation == 'P') $taillepage = 20;
		else $taillepage = 27.7;

		if ($this->confTS[$this->pluginId.'.']['list.']['size']) $size = $this->confTS[$this->pluginId.'.']['list.']['size'];
		else $size = ($taillepage/$nbcol);			// taille de la colonne en cm selon l'orientation du PDF

		if ($this->confTS[$this->pluginId.'.']['list.']['sizeh']) $sizeh = $this->confTS[$this->pluginId.'.']['list.']['sizeh'];
		$sizeh = $sizeh/7*10;			// 7 => hauteur d'une ligne,  *10 => pour avoir la taille en mm

		foreach($xml->tr as $row) {
			foreach($row->td as $elem) {
				if ($elem->data != '') {
					$val .= strip_tags($elem->data);
					$val .= "\n";
				} else {
					$cpt++;
				}
			}
			while ($cpt>0) {
				$val .= "\n";
				$cpt--;
			}
			$cpt=0;   		// just in case
			
			$pdf->setXY( ($marginl? 20 : $nbx*$size*9+20), ($marginh ? 35 : $posy+25));
			$marginl=0;

			$pdf->MultiCell($size*8,($sizeh? $sizeh : 7),utf8_decode($val),1,'L',1);

			if ($cptcols == $nbcol)			// Si on arrive au nombre de colonnes indiquee dans le flexform, on passe a une nouvelle ligne d'elements
			{
				$pdf->Ln();					// Nouvelle ligne
				$cptcols = 0;				// Compteur de colonnes remis a 0
				$posy=$pdf->getY();			// On recupere la position en Y actuelle pour savoir ou placer les prochaines colonnes
				$nbx=-1;					// le nombre d'elements et remis a 0 (-1 en realite car il est incremente juste apres)
				$marginl=1;					// La marge de gauche est reinitialisee
				$marginh=0;
			}
	
		$val='';
		$cptcols++;	
		$nbx++;
		}
		ob_clean();
		//$pdf->Output();
		switch ($print) {
			case 'print':
			case 'printnodialog':
				$pdf->AutoPrint(false);
				break;
			case 'printdialog':
				$pdf->AutoPrint(true);
				break;
			default:
				break;
		}
		$content = $pdf->Output('test.pdf', 'S');
		echo $content;
		die;
	}
		
	// We handle here excel file generation
/*	function getEXCEL(&$content,&$caller) {
		//[HTTP_REFERER] => http://cric.ard.fr/44.0.html
		header("Content-Type: apllication/xls");
		header('Content-disposition: filename="'.$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.xls"');
		$www=t3lib_div::trimexplode(':',$_SERVER['HTTP_REFERER']);
		echo '<head><base href="'.$www[0].'://'.$_SERVER['SERVER_NAME'].'/" /><base target="_blank" /></head>';

		echo utf8_decode($caller->metafeeditlib->T3StripComments($content));
		die;
	}
*/
	function getEXCEL(&$content,&$caller) {
		/** PHPExcel_IOFactory */
		require_once(t3lib_extMgm::extPath('meta_feedit').'/lib/PHPExcel.php'); 
		require_once(t3lib_extMgm::extPath('meta_feedit').'/lib/PHPExcel/IOFactory.php'); 
		/** PHPExcel_Cell_AdvancedValueBinder */
		require_once(t3lib_extMgm::extPath('meta_feedit').'/lib/PHPExcel/Cell/AdvancedValueBinder.php');
		//echo $content;die(t);
		// Prepare content
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
    	die( 'Caught exception: '.  $e->getMessage());
		};
		$count = 0;
		$taille = 0;
		$fields = explode(',', $this->conf['list.']['show_fields']);
		$sizeArr = array(); //tableau de la taille des cellules
		$pos=array(); // Array of positions (left,right,center)
		$x=0; //compteur des colonnes
		$maxwidth=array();
		$c='A';

		if($xml->tr) {
			foreach ($xml->tr->td as $cell) {
				$fields[$x]=str_replace('.','_',$fields[$x]);
				$taille += ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$sizeArr[$x] = ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$pos[$x]=($this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]])?$this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]]:(($this->confTS['default.']['list.']['align.'][$fields[$x]])?$this->confTS['default.']['list.']['align.'][$fields[$x]]:($this->confTS['list.']['align.'][$fields[$x]]?$this->confTS['list.']['align.'][$fields[$x]]:'left'));
				$x++;
				$maxwidth[$c]=0;
				$c++;
			}
		}

		// La feuille est de dimension 21 x 29.7- cmd reduit a 20 pour conserver la marge
		if ($taille <200) $orientation=PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT;	// portrait
		else $orientation=PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE;				// paysage

		// We handle the header here 
		//
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		if (strlen($title)>31) $title=substr($title,0,31);
		$user = '';
		$user = $GLOBALS['TSFE']->fe_user->user[username];

		if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		$height = $this->confTS[$this->pluginId.'.']['list.']['height']?$this->confTS[$this->pluginId.'.']['list.']['height']:($this->confTS['default.']['list.']['height']?$this->confTS['default.']['list.']['height']:30); // hauteur de la ligne pdf



		// set headers to redirect output to client browser as a file download
		$objPHPExcel=new PHPExcel();
		
		// Set value binder
		//PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		
		//Set Print properties

		$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader($title); //Set print header
		$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation($orientation); //set printing orientation
		$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

		//Set Metadata
		
		$objPHPExcel->getProperties()->setCreator($user);
		$objPHPExcel->getProperties()->setLastModifiedBy($user);
		$objPHPExcel->getProperties()->setTitle($title);
		$objPHPExcel->getProperties()->setSubject($title);
		$objPHPExcel->getProperties()->setDescription($title);
		$objPHPExcel->getProperties()->setKeywords($title);
		$objPHPExcel->getProperties()->setCategory("Reporting");
		$objPHPExcel->getActiveSheet()->setTitle($title);
		
		// Handle Title
		
		//$objPHPExcel->getActiveSheet()->insertNewRowBefore(1, 2);//Some empty rows for space
		$objPHPExcel->getActiveSheet()->setCellValue('A1', $title);
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
		$objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(18);
		// Handle Rows
		$r=3;
		$bgcolor="FFFFFFFF";
		
		
		foreach($xml->tr as $row) {
			$x=0; // col counter
			$c='A'; //compteur des colonnes
			if ($alt>1) {							// changement de couleur 1 ligne sur 2
				$alt=0;
				//$pdf->setFillColor(200,200,200);
				$bgcolor="FEFEFEFE";

			}
			$alt++;
			$nbcols=count($row->td);
			$csize=0;
			if ($row->gb) {
				//$pdf->SetFont('Arial', 'B', 9);
			} else {
				//$pdf->SetFont('Arial', '', 9);
			}				
			$objPHPExcel->getActiveSheet()->getRowDimension($r)->setRowHeight($height);
			foreach($row->th as $col) {
			

				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->getStartColor()->setARGB("DDDDDDDD");
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFont()->setBold(true);
				$val = strip_tags($col->data);
				$objPHPExcel->getActiveSheet()->getCell($c.$r)->setValueExplicit("".$val, PHPExcel_Cell_DataType::TYPE_STRING);
				$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];

				$x++;
				$c++;
			}
			$c='A';
			foreach($row->td as $col) {
			

				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->getStartColor()->setARGB($bgcolor);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);


				$size = $nbcols==1?$taille:$sizeArr[$x]; //taille de la cellule  une valeur se transformer en une autre du coup tenais plus sur la page			
				$val = strip_tags($col->data);
				$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);

				// Affichage du signe Euro sur l'export PDF CBY : a virer !!!
				if ($this->conf['list.']['euros'] && $result) $val .= ' Eur';

				if ($col->img==1 && strlen($val)>0) {	 				// We handle images here...
					$vala=t3lib_div::trimexplode(',',$val);
					$img='';
					$objPHPExcel->getActiveSheet()->setCellValue($c.$r, "".$val);
					$offset=10;
					foreach($vala as $v) {
						$img=PATH_site.($v?$col->img->dir.'/'.$v:'');
						$objCommentRichText = $objPHPExcel->getActiveSheet()->getComment($c.$r)->getText()->createTextRun($img);
						$imginfo=getimagesize($img);
						if (is_array( $imginfo)) {
							$w=$imginfo[0];
							$h=$imginfo[1];
							
							$objDrawing = new PHPExcel_Worksheet_Drawing();
							$objDrawing->setName($v);
							$objDrawing->setDescription($img);
							$objDrawing->setPath($img);
							$objDrawing->setHeight($height);
							$objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
							$objDrawing->setCoordinates($c.$r);
							$objDrawing->setOffsetX($offset);
							$objDrawing->setRotation(15);
							$objDrawing->getShadow()->setVisible(true);
							$objDrawing->getShadow()->setDirection(45);
							$offset+=$objDrawing->getWidth()+10;
							$maxwidth[$c]=$offset>$maxwidth[$c]?$offset:$maxwidth[$c];
						}
					}
					$maxoffset=$offset>$maxoffset?$offset:$maxoffset;
					//$pdf->setX($size+$pdf->leftmargin);									// + la marge definie plus haut pour la page => ligne 2308
				} else {
					switch($pos[$x]) {
						case 'left' :
							$p='L';
							break;
						case 'right' :
						  $p='R';
							break;
						case 'center' :
						  $p='C';
							break;
						default :
							$p='L';
							break;
				 	}
				 	if (!$r) $p='L'; // So that column headers are always aligned left. 
				 	//$myx=$pdf->getX();
				 	if ($row->gb && !strlen($val)) {
				 		//$pdf->setX($myx+$size);
				 	} else {
				 		if ($row->gb && $x==0) { 
							$objPHPExcel->getActiveSheet()->setCellValue($c.$r, "".$val);
							$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];
						} else {
							$border=1;
							//if ($row->gb) $border=0;
							$objPHPExcel->getActiveSheet()->setCellValue($c.$r, "".$val);
							$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];
						}
					}
				}
				$x++;
				$c++;
			}
			// We handle footer here
			$c='A';
			if (count($row->tf) > 0) $r++;
			foreach($row->tf as $col) {
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->getStartColor()->setARGB("DDDDDDDD");
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
				$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFont()->setBold(true);
				
				$val = strip_tags($col->data);
				$objPHPExcel->getActiveSheet()->getCell($c.$r)->setValueExplicit("".$val, PHPExcel_Cell_DataType::TYPE_STRING);
				$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];
				$x++;
				$c++;
			}
			$bgcolor="FFFFFFFF";
			
			
			$r++;
		}
		
		// handle column widths

		foreach ($maxwidth as $col=>$width) {
			//$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setWidth($width);
			$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		}

		$highestRow = $objPHPExcel->getActiveSheet()->getHighestRow();
		$highestCol = $objPHPExcel->getActiveSheet()->getHighestColumn();
		$objPHPExcel->getActiveSheet()->getPageSetup()->setPrintArea('A1:'.$highestCol.$highestRow);
		$objPHPExcel->getActiveSheet()->setAutoFilter('A3:'.$highestCol.($highestRow-2));
		
		//-----Create a Writer and output the file to the browser-----
		$objWriter2007 = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		ob_clean();
		header('Content-Type: application/vnd.openXMLformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.xlsx"');
		header('Cache-Control: max-age=0');

		$objWriter2007->save('php://output'); 
		die;
	}

	
	/**
	 * This function get the police size of the pdf file
	 *
	 * @alter police
	 */
	function getPolice(&$police) {
		if ($this->confTS[$this->pluginId.'.']['list.']['police']) $police = $this->confTS[$this->pluginId.'.']['list.']['police'];
		elseif ($this->confTS['default.']['list.']['police']) $police = $this->confTS['default.']['list.']['police'];
		else $police = 8;
	}

	/**
	 * This function get the police font of the pdf file
	 *
	 * @alter font
	 */
	function getFont(&$font) {
		if ($this->confTS[$this->pluginId.'.']['list.']['font']) $font = $this->confTS[$this->pluginId.'.']['list.']['font'];
		elseif ($this->confTS['default.']['list.']['font']) $font = $this->confTS['default.']['list.']['font'];
		else $font = 'Arial';
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_export.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_export.php']);
}

?>