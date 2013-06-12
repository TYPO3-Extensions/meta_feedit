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
* @author	  Christophe BALISKY <cbalisky@metaphore.fr>
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
			$user = $GLOBALS['TSFE']->fe_user->user[username]?$GLOBALS['TSFE']->fe_user->user[username]:$this->caller->caller->metafeeditlib->getLL('anonymous', $this->caller->conf);
			//->caller
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
	// generates printer javascript depending on parameters and config
	function generatePrintScript($print,$printer,$server) {
		$dialog=true;
		$autoprint=false;
		//We add print dialog or not ...
		switch ($print) {
			case 'print':
			case 'printnodialog':
				$dialog=false;
				$autoprint=true;
				break;
			case 'printdialog':
	
				$autoprint=true;
				break;
			default:
				break;
		}
		if ($autoprint) {
			if ($printer && $server) {
				$this->AutoPrintToNetPrinter($server, $printer, $dialog);
			} elseif ($printer) {
				$this->AutoPrintToPrinter( $printer, $dialog);
			} else {
				$this->AutoPrint($dialog);
			}
		}
	}
	
	/**
	 * We autoprint to defauklt printer
	 * @param bool $dialog
	 */
	
	function AutoPrint($dialog=false)
	{
		//Open the print dialog or start printing immediately on the standard printer
		$param=($dialog ? 'true' : 'false');
		$script=$dialog?"print(true);":"if(typeof JSSilentPrint != 'undefined') {JSSilentPrint(this);}else{print(false);};";
		//$script=$dialog?"print(true);":"if(typeof JSSilentPrint != 'undefined') {app.alert('silent');JSSilentPrint(this);}else{app.alert('notsilent');print(false);};";
		//$script=$dialog?"print(true);":"app.alert('yoi');if(typeof JSSilentPrint == 'undefined') {app.alert('notsilent');};";
		$this->IncludeJS($script);
		//$this->IncludeJS("app.alert('yop');".$script);
	}
	
	/**
	 * We autoprint to printer by printername on network
	 * @param string $server
	 * @param string $printer
	 * @param boolean $dialog
	 */
	
	function AutoPrintToNetPrinter($server, $printer, $dialog=false)
	{
		//Print on a shared printer (requires at least Acrobat 6)
		
		if($dialog) {
			$script = "var pp = getPrintParams();";
			$script .= "pp.interactive = pp.constants.interactionLevel.full;";
			$script .= "pp.printerName = '\\\\\\\\".$server."\\\\".$printer."';";
			$script .= "print(pp);";
		}else{
			$script="if(typeof JSSilentPrintOnNetPrinter != 'undefined') {JSSilentPrintOnNetPrinter(this,'".$printer."','".$server."');}else{";
			$script .= "var pp = getPrintParams();";
			$script .= "pp.interactive = pp.constants.interactionLevel.automatic;";
			$script .= "pp.printerName = '\\\\\\\\".$server."\\\\".$printer."';";
			$script .= "print(pp);}";
		}
		$this->IncludeJS($script);
	}
	
	/**
	 * We autoprint to printer by printername
	 * 
	 * @param string $printer
	 * @param bool $dialog (do we try to print with dialog or not)
	 */
	
	function AutoPrintToPrinter( $printer, $dialog=false)
	{
		//Print on a shared printer (requires at least Acrobat 6)

		if($dialog) {
			$script = "var pp = getPrintParams();";
			$script .= "pp.interactive = pp.constants.interactionLevel.full;";
			$script .= "pp.printerName = '".$printer."';";
			$script .= "print(pp);";
		} else {
			$script="if(typeof JSSilentPrintOnPrinter != 'undefined') {JSSilentPrintOnPrinter(this,'".$printer."');}else{";
			$script .= "var pp = getPrintParams();";
			$script .= "pp.interactive = pp.constants.interactionLevel.automatic;";
			$script .= "pp.printerName = '".$printer."';";
			$script .= "print(pp);}";
		}
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
	
	/*******************************************************************************
	 *																			  *
	*							   Public methods								 *
	*																			  *
	*******************************************************************************/
	function Image($file,$x,$y,$w=0,$h=0,$type='',$link='', $isMask=false, $maskImg=0)
	{
		//Put an image on the page
		if(!isset($this->images[$file]))
		{
			//First use of image, get info
			if($type=='')
			{
				$pos=strrpos($file,'.');
				if(!$pos)
					$this->Error('Image file has no extension and no type was specified: '.$file);
				$type=substr($file,$pos+1);
			}
			$type=strtolower($type);
			$mqr=get_magic_quotes_runtime();
			set_magic_quotes_runtime(0);
			if($type=='jpg' || $type=='jpeg')
				$info=$this->_parsejpg($file);
			elseif($type=='png'){
				$info=$this->_parsepng($file);
				if ($info=='alpha') return $this->ImagePngWithAlpha($file,$x,$y,$w,$h,$link);
			}
			else
			{
				//Allow for additional formats
				$mtd='_parse'.$type;
				if(!method_exists($this,$mtd))
					$this->Error('Unsupported image type: '.$type);
				$info=$this->$mtd($file);
			}
			set_magic_quotes_runtime($mqr);
	
			if ($isMask){
				$info['cs']="DeviceGray"; // try to force grayscale (instead of indexed)
			}
			$info['i']=count($this->images)+1;
			if ($maskImg>0) $info['masked'] = $maskImg;###
			$this->images[$file]=$info;
		}
		else
			$info=$this->images[$file];
		//Automatic width and height calculation if needed
		if($w==0 && $h==0)
		{
			//Put image at 72 dpi
			$w=$info['w']/$this->k;
			$h=$info['h']/$this->k;
		}
		if($w==0)
			$w=$h*$info['w']/$info['h'];
		if($h==0)
			$h=$w*$info['h']/$info['w'];
	
		// embed hidden, ouside the canvas
		if ((float)FPDF_VERSION>=1.7){
			if ($isMask) $x = ($this->CurOrientation=='P'?$this->CurPageSize[0]:$this->CurPageSize[1]) + 10;
		}else{
			if ($isMask) $x = ($this->CurOrientation=='P'?$this->CurPageFormat[0]:$this->CurPageFormat[1]) + 10;
		}
	
		$this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
		if($link)
			$this->Link($x,$y,$w,$h,$link);
	
		return $info['i'];
	}
	
	// needs GD 2.x extension
	// pixel-wise operation, not very fast
	function ImagePngWithAlpha($file,$x,$y,$w=0,$h=0,$link='')
	{
		$tmp_alpha = tempnam('.', 'mska');
		$this->tmpFiles[] = $tmp_alpha;
		$tmp_plain = tempnam('.', 'mskp');
		$this->tmpFiles[] = $tmp_plain;
	
		list($wpx, $hpx) = getimagesize($file);
		$img = imagecreatefrompng($file);
		$alpha_img = imagecreate( $wpx, $hpx );
	
		// generate gray scale pallete
		for($c=0;$c<256;$c++) ImageColorAllocate($alpha_img, $c, $c, $c);
	
		// extract alpha channel
		$xpx=0;
		while ($xpx<$wpx){
			$ypx = 0;
			while ($ypx<$hpx){
				$color_index = imagecolorat($img, $xpx, $ypx);
				$alpha = 255-($color_index>>24)*255/127; // GD alpha component: 7 bit only, 0..127!
				imagesetpixel($alpha_img, $xpx, $ypx, $alpha);
				++$ypx;
			}
			++$xpx;
		}
	
		imagepng($alpha_img, $tmp_alpha);
		imagedestroy($alpha_img);
	
		// extract image without alpha channel
		$plain_img = imagecreatetruecolor ( $wpx, $hpx );
		imagecopy ($plain_img, $img, 0, 0, 0, 0, $wpx, $hpx );
		imagepng($plain_img, $tmp_plain);
		imagedestroy($plain_img);
	
		//first embed mask image (w, h, x, will be ignored)
		$maskImg = $this->Image($tmp_alpha, 0,0,0,0, 'PNG', '', true);
	
		//embed image, masked with previously embedded mask
		$this->Image($tmp_plain,$x,$y,$w,$h,'PNG',$link, false, $maskImg);
	}
	
	function Close()
	{
		parent::Close();
		// clean up tmp files
		foreach($this->tmpFiles as $tmp) @unlink($tmp);
	}
	
	/*******************************************************************************
	 *																			  *
	*							   Private methods								*
	*																			  *
	*******************************************************************************/
	function _putimages()
	{
		$filter=($this->compress) ? '/Filter /FlateDecode ' : '';
		reset($this->images);
		while(list($file,$info)=each($this->images))
		{
			$this->_newobj();
			$this->images[$file]['n']=$this->n;
			$this->_out('<</Type /XObject');
			$this->_out('/Subtype /Image');
			$this->_out('/Width '.$info['w']);
			$this->_out('/Height '.$info['h']);
	
			if (isset($info["masked"])) $this->_out('/SMask '.($this->n-1).' 0 R'); ###
	
			if($info['cs']=='Indexed')
				$this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
			else
			{
				$this->_out('/ColorSpace /'.$info['cs']);
				if($info['cs']=='DeviceCMYK')
					$this->_out('/Decode [1 0 1 0 1 0 1 0]');
			}
			$this->_out('/BitsPerComponent '.$info['bpc']);
			if(isset($info['f']))
				$this->_out('/Filter /'.$info['f']);
			if(isset($info['parms']))
				$this->_out($info['parms']);
			if(isset($info['trns']) && is_array($info['trns']))
			{
				$trns='';
				for($i=0;$i<count($info['trns']);$i++)
					$trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
					$this->_out('/Mask ['.$trns.']');
		}
		$this->_out('/Length '.strlen($info['data']).'>>');
		$this->_putstream($info['data']);
		unset($this->images[$file]['data']);
		$this->_out('endobj');
		//Palette
		if($info['cs']=='Indexed')
		{
				$this->_newobj();
				$pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
				$this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
				$this->_putstream($pal);
				$this->_out('endobj');
	}
	}
	}
	
				// this method overwriing the original version is only needed to make the Image method support PNGs with alpha channels.
				// if you only use the ImagePngWithAlpha method for such PNGs, you can remove it from this script.
					function _parsepng($file)
					{
					//Extract info from a PNG file
					$f=fopen($file,'rb');
					if(!$f)
						$this->Error('Can\'t open image file: '.$file);
						//Check signature
						if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
						$this->Error('Not a PNG file: '.$file);
								//Read header chunk
								fread($f,4);
								if(fread($f,4)!='IHDR')
							$this->Error('Incorrect PNG file: '.$file);
							$w=$this->_readint($f);
						$h=$this->_readint($f);
						$bpc=ord(fread($f,1));
								if($bpc>8)
									$this->Error('16-bit depth not supported: '.$file);
									$ct=ord(fread($f,1));
									if($ct==0)
										$colspace='DeviceGray';
									elseif($ct==2)
										$colspace='DeviceRGB';
										elseif($ct==3)
										$colspace='Indexed';
									else {
									fclose($f);	  // the only changes are
									return 'alpha';  // made in those 2 lines
				}
				if(ord(fread($f,1))!=0)
									$this->Error('Unknown compression method: '.$file);
									if(ord(fread($f,1))!=0)
											$this->Error('Unknown filter method: '.$file);
									if(ord(fread($f,1))!=0)
										$this->Error('Interlacing not supported: '.$file);
										fread($f,4);
										$parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
										//Scan chunks looking for palette, transparency and image data
										$pal='';
										$trns='';
										$data='';
										do
										{
										$n=$this->_readint($f);
										$type=fread($f,4);
										if($type=='PLTE')
										{
											//Read palette
											$pal=fread($f,$n);
											fread($f,4);
											}
											elseif($type=='tRNS')
											{
											//Read transparency info
											$t=fread($f,$n);
											if($ct==0)
												$trns=array(ord(substr($t,1,1)));
												elseif($ct==2)
														$trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
																else
																{
																$pos=strpos($t,chr(0));
	if($pos!==false)
	$trns=array($pos);
	}
	fread($f,4);
	}
	elseif($type=='IDAT')
	{
	//Read image data block
	$data.=fread($f,$n);
	fread($f,4);
	}
	elseif($type=='IEND')
	break;
	else
	fread($f,$n+4);
	}
	while($n);
	if($colspace=='Indexed' && empty($pal))
	$this->Error('Missing palette in '.$file);
	fclose($f);
	return array('w'=>$w,'h'=>$h,'cs'=>$colspace,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns,'data'=>$data);
	}
	
}

class tx_metafeedit_export {
	var $prefixId = "tx_metafeedit";		// Same as class name
	var $conf;								// $conf array from metafeedit_lib
	var $pluginId;							// ID du plugin
	var $confTS;							// TS du plugin
	var $cObj;
	var $caller;
	var $headerFields=array();
	var $headerFieldsSize=array();
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
	 * @return	[type]	...
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

	/**
	 * We handle here PDF file generation for detail ...
	 * @param unknown_type $content
	 * @param unknown_type $caller
	 * @param unknown_type $print
	 * @param unknown_type $printer
	 * @param unknown_type $server
	 */
	function getPDFDET(&$content,&$caller,$print='',$printer='',$server='') {
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
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
		// Do we handle multiple media (default no)
		$multipleMedia=$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']?$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']:($this->confTS['default.']['list.']['multipleMedia']?$this->confTS['default.']['list.']['multipleMedia']:false);
				
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
			$pdf->Cell(0,$this->headercellsize,utf8_decode(htmlspecialchars_decode($recherche)),0,0,'R');
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
				// We handle images
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
				
				if ($col->line==1) {
					//We handle lines here
					$pdf->Line($col->line->attributes()->x1, $col->line->attributes()->y1, $col->line->attributes()->x2, $col->line->attributes()->y2);
				
				} elseif ($col->rect==1) {
					//We handle lines here
					$pdf->Line($col->rect->attributes()->x1, $col->rect->attributes()->y1, $col->rect->attributes()->x2, $col->rect->attributes()->y1);
					$pdf->Line($col->rect->attributes()->x2, $col->rect->attributes()->y1, $col->rect->attributes()->x2, $col->rect->attributes()->y2);
					$pdf->Line($col->rect->attributes()->x2, $col->rect->attributes()->y2, $col->rect->attributes()->x1, $col->rect->attributes()->y2);
					$pdf->Line($col->rect->attributes()->x1, $col->rect->attributes()->y2, $col->rect->attributes()->x1, $col->rect->attributes()->y1);
					
				} elseif ($col->img==1 && strlen($val)>0) {
					// We handle images here...
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
						// By defaullt we only handle first media
						if (!$multipleMedia) break;
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
		$pdf->generatePrintScript($print,$printer,$server);

		$pdf->Output($caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf', 'I');
		die;
	}	


	/**
	 * We handle here PDF file generation for lists ...
	 * @param unknown_type $content
	 * @param unknown_type $caller
	 * @param unknown_type $print
	 * @param unknown_type $printer
	 * @param unknown_type $server
	 */
	function getPDF(&$content,&$caller,$print='',$printer='',$server='') {
		if (!$content) {
			die(__METHOD__.': No template for pdf mode, maybe pdf export is not activated');
		}
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			die( __METHOD__.': Caught exception: '.  $e->getMessage().', maybe pdf export is not activated');
		};
		$count = 0;
		$docWidth = 0;
		$fields = explode(',', $this->conf['list.']['show_fields']); //liste des champs affiches afin de recuperer la dimension des colonnes defini en TS
		$sizeArr = array(); //tableau de la taille des cellules
		$pos=array(); // Array of positions (left,right,center)
		$x=0; //compteur des colonnes
		// Column size calculations on first row
		if($xml->tr) {
			foreach ($xml->tr->td as $cell) {
				$fields[$x]=str_replace('.','_',$fields[$x]);
				$docWidth += ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$sizeArr[$x] = ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$pos[$x]=($this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]])?$this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]]:(($this->confTS['default.']['list.']['align.'][$fields[$x]])?$this->confTS['default.']['list.']['align.'][$fields[$x]]:($this->confTS['list.']['align.'][$fields[$x]]?$this->confTS['list.']['align.'][$fields[$x]]:'left'));
				$x++;
			}
		}
		// Do we handle multiple media (default no)
		$multipleMedia=$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']?$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']:($this->confTS['default.']['list.']['multipleMedia']?$this->confTS['default.']['list.']['multipleMedia']:false);
		// Page size is 21 x 29.7- reduced to 20 to preserve margin.
		

		$format=A4; //210 × 297
		
		$unit='mm';
		$H=210;
		$W=297;
		$orientation='L';// landscape
		if ($docWidth <200) {
			$orientation='P';	// portrait
			$H=297;
			$W=210;
		} 
		// Line width
		$lw=0.3;
		$pdf = new tx_metafeedit_pdf($orientation, $unit, $format);
		$pdf->caller=&$this;

		// TODO Handle typoscript here ...
		
		$pdf->bottommargin=9;
		$pdf->leftmargin=8;
		$pdf->rightmargin=8;
		$pdf->topmargin=8;
		
		// We calculate last cell size eventually
		
		$workWidth=$W-$pdf->rightmargin-$pdf->leftmargin;
		$spaceLeft=$workWidth-$docWidth;// - ($x*$lw);
		// do this onlsy if cell size not set ...(
		$cw=$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:0;
		if (!$cw && $spaceLeft>0) {
			$sizeArr[$x-1]+=$spaceLeft;
		}
		
		
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=7;
		$this->headerFields=array();
		$this->headerFieldsSize=array();
		$pdf->AliasNbPages();
		$pdf->setMargins($pdf->leftmargin,$pdf->topmargin,$pdf->rightpmargin);
		$pdf->SetAutoPageBreak(1,$pdf->bottommargin);
		$pdf->AddPage();

		// We handle the header here 
		//
		$title =null;
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		if ($this->conf['inputvar.']['sortLetter']) $tri = '  Tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];
		$pdf->SetFont('Arial','B',11);
		
		$pdf->SetY(0);
		$pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');	
		$pdf->SetFont('Arial', '', 9);
		$pdf->Cell(0,$this->headercellsize,utf8_decode(htmlspecialchars_decode($recherche)),0,0,'R');
		$pdf->SetY(0);
		$pdf->Cell(0,$this->headercellsize,$tri,0,0,'C');
		$pdf->SetXY($pdf->leftmargin,6);
		//We set header color (headers are first row in file).
		$pdf->setFillColor(200,200,200);
		$pdf->Cell(0,$this->headercellsize,'','T',0,'C');
		$pdf->Ln(2);	
		
		// we set font and size
		// Where do we get $plice and $font ??????
		$this->getPolice($police);
		$this->getFont($font);
		$pdf->SetFont($font,'',$police);
		$alt=1;
		
		// Content 
		$pdf->setFillColor(125,125,125);
		$height = ($this->confTS[$this->pluginId.'.']['list.']['height'])?$this->confTS[$this->pluginId.'.']['list.']['height']:(($this->confTS['default.']['list.']['height'])?$this->confTS['default.']['list.']['height']:($pdf->cellsize?$pdf->cellsize:5)); // hauteur de la ligne pdf
		$r=0;
		foreach($xml->tr as $row) {
			$Y=$pdf->GetY()+$pdf->bottommargin+$height;
			if ($Y>=$H) {
				$pdf->AddPage();
			}
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
				$pdf->SetLineWidth($lw);
				$pdf->SetFont('Arial', 'B', 9);
			} else {
				$pdf->SetLineWidth($lw);
				$pdf->SetFont('Arial', '', 9);
			}				
			// We handle cell content here
			foreach($row->td as $col) {
				$size = $nbcols==1?$workWidth:$sizeArr[$x]; //taille de la cellule
				//@todo why do we do this	
				$val = str_replace('€','Eur',strip_tags($col->data));
				$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);
				// Currency handling Euro CBY should not be here !!!
				if ($this->conf['list.']['euros'] && $result) $val .= ' Eur';
				// We handle images here...
				if ($r=0) {
					// first row we have headers;
					$this->headerFieldsSize[]=$size;
					$this->headerFields[]=$val;
				}
				if ($col->img==1) {
					if (strlen($val)>0) {
						$vala=t3lib_div::trimexplode(',',$val);
					} else {
						$vala=array('');
					}
					$img='';
				 	$myx=$pdf->getX();
					$pdf->Cell($size,$height,'',1,0,'L',1);
					$pdf->setX($myx);
					$notFound=t3lib_extMgm::siteRelPath('meta_feedit').'res/noimage.jpg';
					foreach($vala as $v) {
						
						$img=$v?$PATH_site.$col->img->dir.'/'.$v:$notFound;
						$img=$caller->metafeeditlib->getIconPath($img, $this->conf, $size);
						$imginfo=getimagesize(PATH_site.$img);
						if (is_array($imginfo)) {
							$w=$imginfo[0];
							$h=$imginfo[1];
							try {
								$pdf->Image($img,$pdf->getX()+0.5,$pdf->getY()+0.5,0, $height-1);
							} catch(Exception $e) {
								$pdf->Image($notFound,$pdf->getX()+0.5,$pdf->getY()+0.5,0, $height-1);
							}
							$pdf->setX($pdf->getX()+((($height-1)/$h)*$w));
						}
						// By default we only handle first media
						if (!$multipleMedia) break;
					}
					$pdf->setX($size+$pdf->leftmargin);
					// + la marge definie plus haut pour la page => ligne 2308
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
							$pdf->Cell($workWidth,$height,utf8_decode($val),1,0,$p,1);
							$cell=true;
							$pdf->setX($myx+$size);
						} else {
							$border=1;
							$pdf->Cell($size,$height,utf8_decode($val),1,0,$p,1);
							$cell=true;
							$pdf->setX($myx+$size);
						}
					}
				}
				$x++;
			}
			//white
			$pdf->setFillColor(255,255,255);
			// We erase all text right of last cell
			//300 is maximum width  of page in A4
			$X=$pdf->GetX();
			$pdf->Cell(300,$height,"",0,0,$p,1);
			$pdf->SetX($X);
			$pdf->Cell(0.001,$height,"",1,0,$p,1);
			// Line break
			$pdf->Ln();
			$r++;
			
		}
		//Convert to PDF
		$name=$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf';
		ob_clean();
		$pdf->generatePrintScript($print,$printer,$server);
		$pdf->Output($name, 'I'); 
		die;

	}
	/**
	 * Calculate tab cell height
	 * 
	 */
	function getPDFTABRowHeight($pdf,$row) {
		/**
		 * Get Max row height
		 */
		$rowHeight=0;
		foreach($row->td as $elem) {
			if ($elem->img==1) {
				$rowHeight+=$this->imageHeight;
			} else {
				$rowHeight+= ceil( $pdf->GetStringWidth($elem->data) / $this->cellWidth )*$this->lineHeight;
			}		
		}
		return $rowHeight;
	}
	
	/**
	 * 
	 * @param unknown_type $fullPathToImage
	 */
	function PDFDisplayImage($pdf,$fullPathToImage,$displayEmptyImage=true){
		$imginfo=getimagesize($fullPathToImage);
		if (is_array($imginfo)) {
			$w=$imginfo[0];
			$h=$imginfo[1];
			$pdf->Image($fullPathToImage,$pdf->getX()+0.5,$pdf->getY()+0.5,0, $this->imageHeight-1);
			//$pdf->setX($pdf->getX()+((($height-1)/$h)*$w));
			$pdf->SetY($this->Y+0.5+$this->imageHeight-1);
		} else {
			if ($displayEmptyImage) {
				$fullPathToUnknownImage=PATH_site.'typo3conf/ext/meta_feedit/res/noimage.jpg';
				$this->PDFDisplayImage($pdf,$fullPathToUnknownImage,false);
			} else {
				// Empty image we make room for it
				$pdf->SetY($this->Y+0.5+$this->imageHeight-1);
				error_log(__METHOD__.": Could not find $fullPathToImage");
			}
		}
	}
	
	/**
	* PDF Tabular print row
	*
	*/
	function getPDFTABPrintRow($pdf,$cellData) {
		$cptCols=0;
		foreach ($cellData as $cell) {
			$X=$pdf->leftmargin+($cptCols*$this->cellWidth);
			$pdf->setXY($X,$this->Y);
			foreach($cell->td as $elem) {
				$pdf->setX($X);
				if ($elem->img==1) {
					$vala=t3lib_div::trimexplode(',',$elem->data);
					$img='';
					//$myx=$pdf->getX();
					//$pdf->Cell($size,$height,'',1,0,'L',1);
					//$pdf->setX($myx);
					if ($elem->data != '') {
						foreach($vala as $v) {
							$img=PATH_site.($v?$elem->img->dir.'/'.$v:'');
							$this->PDFDisplayImage($pdf,$img);
							// By defaullt we only handle first media
							if (!$this->multipleMedia) break;
						}
					} else {
						$this->PDFDisplayImage($pdf,'');
						// Empty image we make room for it
					}
				} else {
					$val= strip_tags($elem->data);
					$pdf->MultiCell($this->cellWidth,$this->lineHeight,utf8_decode($val),0,'L',0);
				}
			}
			$cptcols++;
			$Y2=$pdf->GetY();
			if ($this->LastY<$Y2) {
				$this->LastY=$Y2;
			}
			$cptCols++;
		}
		//Draw rectangles
		$cptCols=0;
		foreach ($cellData as $cell) {
			$rh=$this->LastY-$this->Y;
			$pdf->Rect($pdf->leftmargin+($this->cellWidth*$cptCols), $this->Y, $this->cellWidth, $rh);
			$cptCols++;
		}
		$this->Y=$this->LastY;
		//$this->lastY=0;
	
	}
	/**
	 * Tabular presentation for pdf
	 * @param unknown_type $content
	 * @param unknown_type $caller
	 * @param unknown_type $print
	 */
	function getPDFTAB(&$content,&$caller,$print='') {
		//$xml = new SimpleXMLElement($content);
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			die( 'Caught exception: '.  $e->getMessage());
		};
		$count = 0;

		$orientation = ($this->confTS[$this->pluginId.'.']['list.']['OrientationPDF'])?$this->confTS[$this->pluginId.'.']['list.']['OrientationPDF']:(($this->confTS['default.']['list.']['OrientationPDF'])?$this->confTS['default.']['list.']['OrientationPDF']:'P');
		$format=A4;
		$unit='mm';
		$H=210;
		$W=297;
		//$orientation='L';// landscape
		//$orientation='P';	// portrait
		if ($orientation=='P') {
			$H=297;
			$W=210;
		}
		// Line width
		$lw=0.3;
		// Number of columns in tabular display
		$nbCols = (int)$this->conf['list.']['nbCols'];		// Nombre de colonnes voulues par l'utilisateur
		
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=7;
		// Do we handle multiple media (default no)
		$this->multipleMedia=$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']?$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']:($this->confTS['default.']['list.']['multipleMedia']?$this->confTS['default.']['list.']['multipleMedia']:false);
		
		
		
		$pdf = new tx_metafeedit_pdf($orientation, $unit, $format);
		$pdf->caller=&$this;
		
		$pdf->bottommargin=9;
		$pdf->leftmargin=8;
		$pdf->rightmargin=8;
		$pdf->topmargin=8;
		
		// We calculate last cell size eventually
		$workWidth=$W-$pdf->rightmargin-$pdf->leftmargin-$taille - ($x*$lw);
		// do this onlsy if cell size not set ...(
		/*$cw=$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:0;
		 if (!$cw && $workWidth>0) {
		$sizeArr[$x-1]+=$workWidth;
		}*/
		//$cellWidth=$workWidth/$nbCols;
		
		
		$pdf->AliasNbPages();
		$pdf->setMargins($pdf->leftmargin,$pdf->topmargin,$pdf->rightpmargin);
		$pdf->SetAutoPageBreak(1,$pdf->bottommargin);
		$pdf->AddPage();
		// title of the page - Il est definit ici et non dans le header pour qu'il ne soit pas present sur chaque page mais seulement la 1ere
		$title =null; 
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		/*if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		$pdf->SetFont('Arial','B',11);
		$pdf->SetY(15);
		$pdf->Cell(0,8,utf8_decode($title),0,0,'C');	
		$pdf->SetFont('Arial', '', 9);
		$pdf->SetY(20);
		$pdf->Cell(0,8,utf8_decode($recherche),0,0,'C');
		$pdf->SetY(25);
		$pdf->Cell(0,8,$tri,0,0,'C');
		$pdf->Ln();*/

		if ($this->conf['inputvar.']['sortLetter']) $tri = '  Tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];
		$pdf->SetFont('Arial','B',11);
		
		$pdf->SetY(0);
		$pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');
		$pdf->SetFont('Arial', '', 9);
		$pdf->Cell(0,$this->headercellsize,utf8_decode(htmlspecialchars_decode($recherche)),0,0,'R');
		$pdf->SetY(0);
		$pdf->Cell(0,$this->headercellsize,$tri,0,0,'C');
		$pdf->SetXY($pdf->leftmargin,6);
		//$pdf->Ln();
		$pdf->setFillColor(200,200,200);
		$pdf->Cell(0,$this->headercellsize,'','T',0,'C');
		$pdf->Ln(2);		
		
		// on met la taille et la police d'impression
		// Where do we get $plice and $font ??????
		$this->getPolice($police);
		$this->getFont($font);
		$pdf->SetFont($font,'',$police);
		//$pdf->Ln();
		$alt=0;

		// Contenu
		$pdf->setFillColor(200,200,200);		// couleur de fond des cellules
		$nbx=0;		
		$cptcols = 0;
		$posy=1;
		$cpt=0;
		$this->imageHeight=20;
		

		/*if ($orientation == 'P') $workWidth = 20;
		else $workWidth = 27.7;*/

		if ($this->confTS[$this->pluginId.'.']['list.']['size']) $size = $this->confTS[$this->pluginId.'.']['list.']['size'];
		else $size = $workWidth/$nbCols;			// taille de la colonne en cm selon l'orientation du PDF
		$this->cellWidth=$size;
		
		if ($this->confTS[$this->pluginId.'.']['list.']['sizeh']) $sizeh = $this->confTS[$this->pluginId.'.']['list.']['sizeh'];
		$sizeh = 5;			// 7 => hauteur d'une ligne,  *10 => pour avoir la taille en mm
		$this->lineHeight=$sizeh;
		$this->Y=$pdf->GetY();
		$this->LastY=$this->Y;
		// We should read rows , nbcols at a time (so we can caluclate line height
		$nbCells=count($xml->tr);
		$cellHeight=array();
		$rowHeight=0;
		$cellData=array();
		foreach($xml->tr as $row) {
			
			$cellData[$cptCols]=$row;
			$cellHeight[$cptCols]=$this->getPDFTABRowHeight($pdf,$row);
			if ($cellHeight[$cptCols]>$rowHeight) $rowHeight=$cellHeight[$cptCols];
			/**
			 * End of row detection
			 */
			$cptCols++;
			$nbCells--;
			
			if ($cptCols>=$nbCols || $nbCells<=0 ) {
				// Why +5 ?
				if ($this->Y+$rowHeight+$pdf->bottommargin+5>=$H) {
					$this->Y=$pdf->topmargin;
					$this->LastY=$pdf->topmargin;
					$pdf->addPage();
				}
				// We have reached end of row or end of file ...
				$this->getPDFTABPrintRow($pdf,$cellData);
				// We reset data arrays
				$cptCols=0;
				$cellData=array();
				$cellHeight=array();
				$rowHeight=0;	
			}
		}
		/*
		//Draw rectangles
		
		while ($cptcols>0) {
			$cptcols--;
			$rh=$LastY-$Y;
			$pdf->Rect($pdf->leftmargin+($size*$cptcols), $Y, $size, $rh);
		}
		$Y=$LastY;
		$pdf->Ln();					// Nouvelle ligne
		$cptcols = 0;				// Compteur de colonnes remis a 0
		$posy=$pdf->getY();			// On recupere la position en Y actuelle pour savoir ou placer les prochaines colonnes
		$nbx=-1;					// le nombre d'elements et remis a 0 (-1 en realite car il est incremente juste apres)
		$marginh=0;
		if ($Y>$H) {
			$Y=$pdf->topmargin;
			$pdf->addPage();
		}
		// I subtracted one from column width as a kind of cell padding
		foreach($row->td as $elem) {
			$pdf->setX($X);
		
			if ($elem->img==1) {
		
				$vala=t3lib_div::trimexplode(',',$elem->data);
				$img='';
				//$myx=$pdf->getX();
				//$pdf->Cell($size,$height,'',1,0,'L',1);
				//$pdf->setX($myx);
				if ($elem->data != '') {
					foreach($vala as $v) {
						$img=PATH_site.($v?$elem->img->dir.'/'.$v:'');
						$imginfo=getimagesize($img);
						if (is_array($imginfo)) {
							$w=$imginfo[0];
							$h=$imginfo[1];
							$pdf->Image($img,$pdf->getX()+0.5,$pdf->getY()+0.5,0, $height-1);
							//$pdf->setX($pdf->getX()+((($height-1)/$h)*$w));
							$pdf->SetY($Y+0.5+$this->imageHeight-1);
						} else {
							// Empty image we make room for it
							$pdf->SetY($Y+0.5+$this->imageHeight-1);
						}
						// By defaullt we only handle first media
						if (!$multipleMedia) break;
					}
				} else {
		
					// Empty image we make room for it
					$pdf->SetY($Y+0.5+$this->imageHeight-1);
				}
				//$pdf->setX($size+$pdf->leftmargin);
			} else {
				$val= strip_tags($elem->data);
				$pdf->MultiCell($size,$this->lineHeight,utf8_decode($val),0,'L',0);
			}
		}
		
		//$pdf->setXY( ($marginl? 20 : $nbx*$size*9+20), ($marginh ? 35 : $posy+25));
		
		$cptcols++;
		$Y2=$pdf->GetY();
		if ($LastY<$Y2) {
			$LastY=$Y2;
		}
		if ($cptcols >= $nbCols)			// Si on arrive au nombre de colonnes indiquee dans le flexform, on passe a une nouvelle ligne d'elements
		{
			//Draw rectangles
			while ($cptcols>0) {
				$cptcols--;
				$rh=$LastY-$Y;
				$pdf->Rect($pdf->leftmargin+($size*$cptcols), $Y, $size, $rh);
			}
			$Y=$LastY;
			$pdf->Ln();					// Nouvelle ligne
			$cptcols = 0;				// Compteur de colonnes remis a 0
			$posy=$pdf->getY();			// On recupere la position en Y actuelle pour savoir ou placer les prochaines colonnes
			if ($Y>$H) {
				$Y=$pdf->topmargin;
				$pdf->addPage();
			}
		
		}*/
		
		ob_clean();
		$name=$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf';
		ob_clean();
		$pdf->generatePrintScript($print,$printer,$server);
		$pdf->Output($name, 'I');
		die;
	}
		
	// We handle here excel file generation
	function getEXCEL(&$content,&$caller) {
		$this->headerConf=array(
			0=>array(
				'size'=>16,
				'bgcolor'=>'9999999'
				),
			1=>array(
				'size'=>14,
				'bgcolor'=>'AAAAAAAA'
				),
			2=>array(
				'size'=>12,
				'bgcolor'=>'BBBBBBBB'
				),
			3=>array(
				'size'=>10,
				'bgcolor'=>'CCCCCCCC'),
			4=>array(
				'size'=>8,
				'bgcolor'=>'DDDDDDDD'),
			5=>array(
				'size'=>6,
				'bgcolor'=>'EEEEEEEE')
		);
		/** PHPExcel_IOFactory */
		require_once(t3lib_extMgm::extPath('meta_feedit').'/lib/PHPExcel.php'); 
		require_once(t3lib_extMgm::extPath('meta_feedit').'/lib/PHPExcel/IOFactory.php'); 
		/** PHPExcel_Cell_AdvancedValueBinder */
		require_once(t3lib_extMgm::extPath('meta_feedit').'/lib/PHPExcel/Cell/AdvancedValueBinder.php');
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
		$nbcs=0;
		$maxcol='A';
		$c='A';
		// Do we handle multiple media (default no)
		$multipleMedia=$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']?$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']:($this->confTS['default.']['list.']['multipleMedia']?$this->confTS['default.']['list.']['multipleMedia']:false);
		
		if($xml->tr) {
			$x=0;
			foreach ($xml->tr->td as $cell) {
				$fields[$x]=str_replace('.','_',$fields[$x]);
				$taille += ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$sizeArr[$x] = ($this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$fields[$x].'.']['width']:$cell->size);
				$pos[$x]=($this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]])?$this->confTS[$this->pluginId.'.']['list.']['align.'][$fields[$x]]:(($this->confTS['default.']['list.']['align.'][$fields[$x]])?$this->confTS['default.']['list.']['align.'][$fields[$x]]:($this->confTS['list.']['align.'][$fields[$x]]?$this->confTS['list.']['align.'][$fields[$x]]:'left'));
				$x++;
				$maxwidth[$c]=0;
				if ($c>$maxcol) $maxcol=$c;
				$c++;
			}
		}
		// La feuille est de dimension 21 x 29.7- cmd reduit a 20 pour conserver la marge
		if ($taille <200) $orientation=PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT;	// portrait
		else $orientation=PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE;// paysage
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
		// We handle column headers here !!
		$lastgbr=0;
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
			if (count($row->th) > 0) {
				foreach($row->th as $col) {
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->getStartColor()->setARGB("99999999");
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFont()->setBold(true);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFont()->setSize(16);
					$val = strip_tags($col->data);
					$objPHPExcel->getActiveSheet()->getCell($c.$r)->setValueExplicit("".$val, PHPExcel_Cell_DataType::TYPE_STRING);
					$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];
					if ($c>$maxcol) $maxcol=$c;
					$x++;
					$c++;
				}
			}
			if ($x>$nbcs) $nbcs=$x;
			$c='A';
			$x=0;
			if (count($row->td) > 0) {
				foreach($row->td as $col) {
					if ($x>=$nbcs) continue;
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
					// We handle images here...
					if ($col->img==1 && strlen($val)>0) {
						$vala=t3lib_div::trimexplode(',',$val);
						$img='';
						if (!$multipleMedia) $val=$vala[0];
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
							// By defaullt we only handle first media
							if (!$multipleMedia) break;
						}
						$maxoffset=$offset>$maxoffset?$offset:$maxoffset;
						//$pdf->setX($size+$pdf->leftmargin);
						// + la marge definie plus haut pour la page => ligne 2308
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
					if ($c>$maxcol) $maxcol=$c;
					$x++;
					$c++;
				}
			}
			// We handle group by headers here
			if ($row->gb || $row->sum){//$row->gb should be group by level: 1 is biggest (first)-> n is smallest
				//$highestCol = $objPHPExcel->getActiveSheet()->getHighestColumn();
				$lvlindex=(int)$row->gb;
				if ($row->gb===0) $lvlindex=0;
				$fs=$this->headerConf[$lvlindex]['size'];
				$bgc=$this->headerConf[$lvlindex]['bgcolor'];
				if ($x==1) {
					$lastgbr=$r;
					$range='A'.$r.':'.$maxcol.$r;
					$objPHPExcel->getActiveSheet()->mergeCells($range);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($bgc);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getFont()->setBold(true);
					//$objPHPExcel->getActiveSheet()->getStyle($range)->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
					$objPHPExcel->getActiveSheet()->getStyle($range)->getFont()->setSize($fs);
				} else {
					//group by footer ?
					if ($lastgbr) {
						$range='A'.$r.':'.$maxcol.$r;
						$objPHPExcel->getActiveSheet()->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB($bgc);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getFont()->setBold(true);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getFont()->setSize($fs);
						
						$range='A'.$lastgbr.':'.$maxcol.$r;
						//$objPHPExcel->getActiveSheet()->getStyle($range)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("FEFEFEFE");
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
						$objPHPExcel->getActiveSheet()->getStyle($range)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					}
				}
			}
			// We handle footer here
			$c='A';
			if (count($row->tf) > 0) {
				$r++;
				$x=0;
				foreach($row->tf as $col) {
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->getStartColor()->setARGB("99999999");
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFont()->setBold(true);
					$val = strip_tags($col->data);
					//$objPHPExcel->getActiveSheet()->mergeCells($c.$r.':'.$c.'99');
					$objPHPExcel->getActiveSheet()->getCell($c.$r)->setValueExplicit("".$val, PHPExcel_Cell_DataType::TYPE_STRING);
					$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];
					$x++;
					//$c++;
					$r++;
				}
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
		//$objPHPExcel->getActiveSheet()->setAutoFilter('A3:'.$highestCol.($highestRow-2));
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