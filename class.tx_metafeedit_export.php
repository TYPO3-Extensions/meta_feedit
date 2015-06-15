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
require_once(dirname(__FILE__).'/lib/tcpdf/tcpdf_barcodes_2d.php');
require_once(dirname(__FILE__).'/lib/tcpdf/tcpdf_barcodes_1d.php');
class tx_metafeedit_2DBarcode extends TCPDF2DBarcode {
	
	
	/**
	 * 
	 * @param unknown_type $w
	 * @param unknown_type $h
	 * @param unknown_type $color
	 */
	public function getBarcodeImage($w=3, $h=3, $color=array(0,0,0)) {
		// calculate image size
		//error_log(__METHOD__);
		$width = ($this->barcode_array['num_cols'] * $w);
		$height = ($this->barcode_array['num_rows'] * $h);
		if (function_exists('imagecreate')) {
			// GD library
			$imagick = false;
			$png = imagecreate($width, $height);
			$bgcol = imagecolorallocate($png, 255, 255, 255);
			imagecolortransparent($png, $bgcol);
			$fgcol = imagecolorallocate($png, $color[0], $color[1], $color[2]);
		} elseif (extension_loaded('imagick')) {
			$imagick = true;
			$bgcol = new imagickpixel('rgb(255,255,255');
			$fgcol = new imagickpixel('rgb('.$color[0].','.$color[1].','.$color[2].')');
			$png = new Imagick();
			$png->newImage($width, $height, 'none', 'png');
			$bar = new imagickdraw();
			$bar->setfillcolor($fgcol);
		} else {
			return false;
		}
		// print barcode elements
		$y = 0;
		// for each row
		for ($r = 0; $r < $this->barcode_array['num_rows']; ++$r) {
			$x = 0;
			// for each column
			for ($c = 0; $c < $this->barcode_array['num_cols']; ++$c) {
				if ($this->barcode_array['bcode'][$r][$c] == 1) {
					// draw a single barcode cell
					if ($imagick) {
						$bar->rectangle($x, $y, ($x + $w - 1), ($y + $h - 1));
					} else {
						imagefilledrectangle($png, $x, $y, ($x + $w - 1), ($y + $h - 1), $fgcol);
					}
				}
				$x += $w;
			}
			$y += $h;
		}
		$filename =  'typo3temp/Cache/Barcodes/2D' .uniqid() .'.png';
		//error_log(__METHOD__.":$filename");
		if ($imagick) {
			$png->drawimage($bar);
		} else {
			imagepng($png,PATH_site .$filename);
			imagedestroy($png);
			return $filename;
		}
	}
}
class tx_metafeedit_1DBarcode extends TCPDFBarcode {


	/**
	 * Return a PNG image representation of barcode (requires GD or Imagick library).
	 * @param $w (int) Width of a single bar element in pixels.
	 * @param $h (int) Height of a single bar element in pixels.
	 * @param $color (array) RGB (0-255) foreground color for bar elements (background is transparent).
 	 * @return image or false in case of error.
 	 * @public
	 */
	public function getBarcodeImage($w=2, $h=30, $color=array(0,0,0)) {
		// calculate image size
		$width = ($this->barcode_array['maxw'] * $w);
		$height = $h;
		if (function_exists('imagecreate')) {
			// GD library
			$imagick = false;
			$png = imagecreate($width, $height);
			$bgcol = imagecolorallocate($png, 255, 255, 255);
			imagecolortransparent($png, $bgcol);
			$fgcol = imagecolorallocate($png, $color[0], $color[1], $color[2]);
		} elseif (extension_loaded('imagick')) {
			$imagick = true;
			$bgcol = new imagickpixel('rgb(255,255,255');
			$fgcol = new imagickpixel('rgb('.$color[0].','.$color[1].','.$color[2].')');
			$png = new Imagick();
			$png->newImage($width, $height, 'none', 'png');
			$bar = new imagickdraw();
			$bar->setfillcolor($fgcol);
		} else {
			return false;
		}
		// print bars
		$x = 0;
		foreach ($this->barcode_array['bcode'] as $k => $v) {
			$bw = round(($v['w'] * $w), 3);
			$bh = round(($v['h'] * $h / $this->barcode_array['maxh']), 3);
			if ($v['t']) {
				$y = round(($v['p'] * $h / $this->barcode_array['maxh']), 3);
				// draw a vertical bar
				if ($imagick) {
					$bar->rectangle($x, $y, ($x + $bw - 1), ($y + $bh - 1));
				} else {
					imagefilledrectangle($png, $x, $y, ($x + $bw - 1), ($y + $bh - 1), $fgcol);
				}
			}
			$x += $bw;
		}
		$filename =  'typo3temp/Cache/Barcodes/1D' .uniqid() .'.png';
		//error_log(__METHOD__.":$filename");
		if ($imagick) {
			$png->drawimage($bar);
		} else {
			imagepng($png,PATH_site .$filename);
			imagedestroy($png);
			return $filename;
		}
	}
}

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
			$this->SetFont('Helvetica','',10);
			$this->setFillColor(200,200,200);
			//$this->SetY(200); //TODO		
			//$this->SetY(-$this->bottommargin); //TODO		
			$date=date('d/m/Y H:i:s');
			$this->SetX($this->leftmargin);
			$this->SetY(-$this->bottommargin);
			
			//We draw line
			$this->Cell(0,1,'','T',0,'C',0);
			//$this->SetY(-$this->bottommargin);
			$this->SetY(-$this->bottommargin+4);
			$this->SetX($this->leftmargin);
			$this->Cell(0,$this->footercellsize, $this->confTS[$this->pluginId.'.']['list.']['footer'], 0,0,'C',0);
			$this->SetX($this->leftmargin);
			
			$this->Cell(0,$this->footercellsize, $date, 0,0,'L');
			$this->SetX($this->leftmargin);
			$this->Cell(0,$this->footercellsize,'Page '.$this->PageNo().'/{nb}',0,0,'C');
			$this->SetX($this->leftmargin);			
			$user = '';
			$user = $GLOBALS['TSFE']->fe_user->user[username]?$GLOBALS['TSFE']->fe_user->user[username]:$this->caller->caller->metafeeditlib->getLL('anonymous', $this->caller->conf);
			
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
		$dbg=false;//$dbg=(substr($txt,0,5)=="Adler");
		if ($dbg) error_log('Nblines '.$txt." - $w");
		//Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
		$cw=&$this->CurrentFont['cw'];
		//error_log(__METHOD__.":".print_r($this->CurrentFont,true));
		//error_log(__METHOD__.":  ".print_r(array_values($cw),true));
		if($w==0)
			$w=$this->w-$this->rMargin-$this->x;
		
		//$wmax=$w*1400/$this->FontSize;
		//$wmax=$w*1000*$this->k;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		//$wmax=($w-2*$this->cMargin)/$this->k;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		//Removes trailing carriage return
		if($nb>0 and $s[$nb-1]=="\n")
			$nb--;
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$nl=1;
		//error_log(__METHOD__.":  $w,$this->cMargin,$this->FontSize, $wmax");
		while($i<$nb)
		{
			$c=$s[$i];
			//Cariage return we add line
			if($c=="\n")
			{
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$nl++;
				//error_log('NewLine');
				continue;
			}
			//Space seperator
			if($c==' ') $sep=$i;
			$l+=$cw[$c];
			if ($dbg) error_log(__METHOD__.":  $c,$l,$wmax");
			if($l>$wmax)
			{
				if ($dbg) error_log('$l > $wmax');
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
			if(version_compare(PHP_VERSION, '5.3.0', '<')){
				$mqr=get_magic_quotes_runtime();
				set_magic_quotes_runtime(0);
			}
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
			if(version_compare(PHP_VERSION, '5.3.0', '<')){
				set_magic_quotes_runtime($mqr);
			}
	
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
		if (is_array($this->tmpFiles)) foreach($this->tmpFiles as $tmp) @unlink($tmp);
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
	
	function ClippingText($x, $y, $txt, $outline=false)
	{
		$op=$outline ? 5 : 7;
		$this->_out(sprintf('q BT %.2f %.2f Td %d Tr (%s) Tj 0 Tr ET',
				$x*$this->k,
				($this->h-$y)*$this->k,
				$op,
				$this->_escape($txt)));
	}
	
	function ClippingRect($x, $y, $w, $h, $outline=false)
	{
		$op=$outline ? 'S' : 'n';
		$this->_out(sprintf('q %.2f %.2f %.2f %.2f re W %s',
				$x*$this->k,
				($this->h-$y)*$this->k,
				$w*$this->k, -$h*$this->k,
				$op));
	}
	
	function ClippingEllipse($x, $y, $rx, $ry, $outline=false)
	{
		$op=$outline ? 'S' : 'n';
		$lx=4/3*(M_SQRT2-1)*$rx;
		$ly=4/3*(M_SQRT2-1)*$ry;
		$k=$this->k;
		$h=$this->h;
		$this->_out(sprintf('q %.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c',
				($x+$rx)*$k, ($h-$y)*$k,
				($x+$rx)*$k, ($h-($y-$ly))*$k,
				($x+$lx)*$k, ($h-($y-$ry))*$k,
				$x*$k, ($h-($y-$ry))*$k));
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
				($x-$lx)*$k, ($h-($y-$ry))*$k,
				($x-$rx)*$k, ($h-($y-$ly))*$k,
				($x-$rx)*$k, ($h-$y)*$k));
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
				($x-$rx)*$k, ($h-($y+$ly))*$k,
				($x-$lx)*$k, ($h-($y+$ry))*$k,
				$x*$k, ($h-($y+$ry))*$k));
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c W %s',
				($x+$lx)*$k, ($h-($y+$ry))*$k,
				($x+$rx)*$k, ($h-($y+$ly))*$k,
				($x+$rx)*$k, ($h-$y)*$k,
				$op));
	}
	
	function UnsetClipping()
	{
		$this->_out('Q');
	}
	
	function ClippedCell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='')
	{
		if($border || $fill || $this->y+$h>$this->PageBreakTrigger)
		{
			$this->Cell($w, $h, '', $border, 0, '', $fill);
			$this->x-=$w;
		}
		$this->ClippingRect($this->x, $this->y, $w, $h);
		$this->Cell($w, $h, $txt, '', $ln, $align,0, $link);
		$this->UnsetClipping();
	}
	
	function ClippedMultiCell($w, $h=0,$lh=0, $txt='', $border=0,  $align='', $fill=false) {
		if($border || $fill || $this->y+$h>$this->PageBreakTrigger)
		{
			$this->Cell($w, $h, '', $border, 0, '', $fill);
			$this->x-=$w;
		}
		$this->ClippingRect($this->x, $this->y, $w, $h);
		//MultiCell($this->cellWidth,$this->height,$val,0,$p,false);
		$this->MultiCell($w, $lh, $txt, 0, $align,$fill);
		$this->UnsetClipping();
	}
	
	//**************************************************************************
	// This extension adds support for windows bitmaps (*.bmp, *.rle)
	//
	//**************************************************************************
	
	function _parsebmp ($file){
		$fsize = filesize($file);
		$f = fopen($file, 'rb');
	
		# bmpfileheader (0..13)
		$bfOffBits = $this->_fread_long_le_at($f, 10);
	
		# bmpinfoheader (14..53)
		$width    = $this->_fread_long_le_at($f, 18);
		$height = $this->_fread_long_le_at($f, 22);
		$flip = ($height<0);
		if ($flip) $height =-$height;
		$biBitCount    = $this->_fread_short_le_at($f, 28);
		$biCompression = $this->_fread_long_le_at($f, 30); # BI_RGB = 0, BI_RLE8 = 1, BI_RLE4 = 2
	
		$info = array('w'=>$width, 'h'=>$height);
	
		fseek($f, 54);
	
		if ($biBitCount<16){
			$info['cs'] = 'Indexed';
			$info['bpc'] = $biBitCount;
			$palStr = fread($f, $bfOffBits-54); # palette
			$pal = '';
			$cnt = strlen($palStr)/4;
			for ($i=0;$i<$cnt;$i++){
				$n = 4*$i;
				$pal .= $palStr{$n+2}.$palStr{$n+1}.$palStr{$n};
			}
			$info['pal'] = $pal;
		}else{
			$info['cs'] = 'DeviceRGB';
			$info['bpc'] = 8;
		}
	
		# image data
		switch ($biCompression){
		case 0:
		$str = fread($f, $fsize-$bfOffBits);
		break;
		case 1: # BI_RLE8
		$str = $this->rle8_decode(fread($f, $fsize-$bfOffBits), $width);
		break;
		case 2: # BI_RLE4
		$str = $this->rle4_decode(fread($f, $fsize-$bfOffBits), $width);
		break;
	}
	
		$data = '';
		$padCnt = (4-ceil(($width/(8/$biBitCount)))%4)%4;
	
		switch ($biBitCount){
		case 1:
		case 4:
		case 8:
		$w = floor($width/(8/$biBitCount)) + ($width%(8/$biBitCount)?1:0);
		$w_row = $w + $padCnt;
		if ($flip){
		for ($y=0;$y<$height;$y++){
		$y0 = $y*$w_row;
		for ($x=0;$x<$w;$x++)
			$data .= $str{$y0+$x};
		}
		}else{
		for ($y=$height-1;$y>=0;$y--){
		$y0 = $y*$w_row;
		for ($x=0;$x<$w;$x++)
			$data .= $str{$y0+$x};
	}
	}
	break;
	
	case 16:
	$w_row = $width*2 + $padCnt;
	if ($flip){
		for ($y=0;$y<$height;$y++){
		$y0 = $y*$w_row;
		for ($x=0;$x<$width;$x++){
		$n = (ord( $str{$y0 + 2*$x + 1})*256 +    ord( $str{$y0 + 2*$x}));
		$b = ($n & 31)<<3; $g = ($n & 992)>>2; $r = ($n & 31744)>>7128;
		$data .= chr($r) . chr($g) . chr($b);
		}
	}
	}else{
	for ($y=$height-1;$y>=0;$y--){
	$y0 = $y*$w_row;
	for ($x=0;$x<$width;$x++){
	$n = (ord( $str{$y0 + 2*$x + 1})*256 +    ord( $str{$y0 + 2*$x}));
	$b = ($n & 31)<<3; $g = ($n & 992)>>2; $r = ($n & 31744)>>7;
	$data .= chr($r) . chr($g) . chr($b);
	}
	}
	}
	break;
	
	case 24:
	case 32:
	##$padCnt = $width % 4;
	$byteCnt = $biBitCount/8;
	$w_row = $width*$byteCnt + $padCnt;
	
	if ($flip){
	for ($y=0;$y<$height;$y++){
	$y0 = $y*$w_row;
	for ($x=0;$x<$width;$x++){
	$i = $y0 + $x*$byteCnt ; # + 1
	$data .= $str{$i+2}.$str{$i+1}.$str{$i};
	}
	}
	}else{
	for ($y=$height-1;$y>=0;$y--){
	$y0 = $y*$w_row;
	for ($x=0;$x<$width;$x++){
	$i = $y0 + $x*$byteCnt ; # + 1
	$data .= $str{$i+2}.$str{$i+1}.$str{$i};
	}
	}
	}
	break;
	
	default:
	$this->Error('Unsupported image biBitCount: '.$file);
	}
	
	# compress data
	$info['f'] = 'FlateDecode';
	$info['data'] = gzcompress($data);
	return $info;
	}
	
	
	function _fread_long_le_at($f, $pos){
	fseek($f, $pos);
	$a=unpack('Vi', fread($f, 4));
	return $a['i'];
	}
	
	function _fread_short_le_at($f, $pos){
	fseek($f, $pos);
	$a=unpack('vi', fread($f, 2));
	return $a['i'];
	}
	
	# Decoder for RLE8 compression in windows bitmaps
	# see http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
	function rle8_decode ($str, $width){
	$lineWidth = $width + (3 - ($width-1) % 4);
	$out = '';
	$cnt = strlen($str);
	for ($i=0;$i<$cnt;$i++){
	$o = ord($str{$i});
	switch ($o){
	case 0: # ESCAPE
	$i++;
	switch (ord($str{$i})){
	case 0: # NEW LINE
	$padCnt = $lineWidth - strlen($out)%$lineWidth;
	if ($padCnt<$lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
	break;
	case 1: # END OF FILE
	$padCnt = $lineWidth - strlen($out)%$lineWidth;
	if ($padCnt<$lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
	break 3;
	case 2: # DELTA
	$i += 2;
	break;
	default: # ABSOLUTE MODE
	$num = ord($str{$i});
	for ($j=0;$j<$num;$j++)
		$out .= $str{++$i};
		if ($num % 2) $i++;
	}
	break;
	default:
	$out .= str_repeat($str{++$i}, $o);
	}
	}
	return $out;
	}
	
	# Decoder for RLE4 compression in windows bitmaps
	# see http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
		function rle4_decode ($str, $width){
		$w = floor($width/2) + ($width % 2);
		$lineWidth = $w + (3 - ( ($width-1) / 2) % 4);
		$pixels = array();
		$cnt = strlen($str);
		for ($i=0;$i<$cnt;$i++){
		$o = ord($str{$i});
		switch ($o){
		case 0: # ESCAPE
		$i++;
		switch (ord($str{$i})){
		case 0: # NEW LINE
		while (count($pixels)%$lineWidth!=0)
		$pixels[]=0;
		break;
		case 1: # END OF FILE
		while (count($pixels)%$lineWidth!=0)
		$pixels[]=0;
		break 3;
		case 2: # DELTA
		$i += 2;
		break;
		default: # ABSOLUTE MODE
		$num = ord($str{$i});
		for ($j=0;$j<$num;$j++){
		if ($j%2==0){
		$c = ord($str{++$i});
		$pixels[] = ($c & 240)>>4;
		} else
			$pixels[] = $c & 15;
		}
		if ($num % 2) $i++;
		}
		break;
		default:
		$c = ord($str{++$i});
		for ($j=0;$j<$o;$j++)
			$pixels[] = ($j%2==0 ? ($c & 240)>>4 : $c & 15);
	}
	}
	
	$out = '';
	if (count($pixels)%2) $pixels[]=0;
	$cnt = count($pixels)/2;
	for ($i=0;$i<$cnt;$i++)
	$out .= chr(16*$pixels[2*$i] + $pixels[2*$i+1]);
	return $out;
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
	 *	@var int $dpi dots per inch for pixel to mm conversion;
	 */
	protected $dpi=96;
	/**
	 * Initialisation function called by fe_adminlib.php allow this class to acces $conf array
	 *
	 * @return	nothing
	 */
	function init(&$caller) {
		$this->caller=$caller;
		$this->conf = &$caller->conf;
		$this->pluginId = $caller->conf['pluginId'];
		//why go in metafeedit.typoscript
		$this->confTS = &$caller->conf['typoscript.'];
		$this->cObj=&$caller->cObj;
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
		$markerArray=array();
		$markerArray['###EXPORT_TITLE###']=$title;
		$markerArray['###SEARCH_FILTER###']=$recherche;
		$markerArray['###EXPORT_TITLE_SIZE###']=strlen($title);
		$markerArray['###SEARCH_FILTER_SIZE###']=strlen($recherche);
		$content=$caller->cObj->substituteMarkerArray($content,$markerArray);
		header("Content-Type: application/csv; charEncoding=utf-8");
		//header("Content-Encoding:utf-8");
		//header("Content-Length: ".strlen($content);
		//header("Content-type: application/force-download");
		//header("Content-Transfer-Encoding: Binary");
		//header("Content-Disposition: attachment; filename=somefilename.extension");
		//error_log(__METHOD__.':'.str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		
		$content= utf8_decode(str_replace('&euro;','Eur',str_replace('&nbsp;',' ',strip_tags($caller->metafeeditlib->T3StripComments($content)))));
		//header("Content-length: ".strlen($content);
		header('Content-disposition: attachment; filename="'.$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.csv"');		
		echo $content;
		die;
	}
	/**
	 * Cleans data value for display
	 * @param unknown_type $cell
	 */
	function getData(SimpleXMLElement $cell) {
		//$utf8val=utf8_decode($val);
		//$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);
		// Affichage du signe Euro sur l'export PDF //CBY nothing to do here !!
		//if ($this->conf[$cmd.'.']['euros'] && $result) $val .= ' Eur';
		//$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);
		// Currency handling Euro CBY should not be here !!!
		//if ($this->conf['list.']['euros'] && $result) $val .= ' Eur';
		//$val=utf8_decode($val);
		//$result = preg_match("/(^[0-9]+([\.0-9]*))$/" , $val);
		// Currency handling Euro CBY should not be here !!!
		//if ($this->conf['list.']['euros'] && $result) $val .= ' Eur';
		//error_log(__METHOD__.":".$cell->data."/".utf8_decode(str_replace('€','Eur',strip_tags($cell->data))));
		return utf8_decode(str_replace('€','Eur',strip_tags($cell->data)));
	}
	/**
	 * Check's if we must add a new page
	 */
	function checkForPageBreak() {
		$ChkY=$this->pdf->GetY()+$this->pdf->bottommargin+$this->rowHeight;
		if ($ChkY>=$this->documentHeight) {
			$this->pdf->AddPage();
			$this->cellY=$this->pdf->GetY();
		}
	}
	/**
	 * 
	 * @param unknown_type $row
	 * @return Ambigous <string, multitype:multitype: number >
	 */
	function calculateRowCellSizes($row) {
		$x=0;
		$this->nbFreeCells=0;
		//$freeCellIndexes=array();
		unset($this->rowCellWidth);
		$this->rowCellWidth=array();
		$this->rowPos=array();
		$this->rowFreeCellWidth=0;
		//$this->rowHeight=$this->height;
		$this->rowHeight=@$row->spec['rh']?(float)@$row->spec['rh']:$this->height;
		$this->rowWidth=0;
		$cellWidth=0;
		$cellHeight=0;
		$nblines=1;
		if (!is_array($this->fields)) $this->fields=array();
		
		//bug https://bugs.php.net/bug.php?id=38604
		$r=$row->td;
		foreach ($r as $cell) {
			$this->rowCellWidth[$x] =(float)(($this->confTS[$this->pluginId.'.']['list.'][$this->fields[$x].'.']['width'])?$this->confTS[$this->pluginId.'.']['list.'][$this->fields[$x].'.']['width']:(($this->confTS['default.']['list.'][$this->fields[$x].'.']['width'])?$this->confTS['default.']['list.'][$this->fields[$x].'.']['width']:$cell->size));
			//error_log(__METHOD__.":".print_r($cell->img,true));
			if ((float)$cell->img['w']>(float)$this->rowCellWidth[$x]) {
				//error_log(__METHOD__.": img w ".$cell->img['w']);
				$this->rowCellWidth[$x]=$cell->img['w'];
			}
			
			if (!$this->rowCellWidth[$x] || $this->rowCellWidth[$x]=='' || $this->rowCellWidth[$x]=='*') {
				$this->nbFreeCells++;
				$this->rowCellWidth[$x]='*';
			}
			$this->rowWidth += (int)$this->rowCellWidth[$x];
			$this->rowPos[$x]=($this->confTS[$this->pluginId.'.']['list.']['align.'][$this->fields[$x]])?$this->confTS[$this->pluginId.'.']['list.']['align.'][$this->fields[$x]]:(($this->confTS['default.']['list.']['align.'][$this->fields[$x]])?$this->confTS['default.']['list.']['align.'][$this->fields[$x]]:($this->confTS['list.']['align.'][$this->fields[$x]]?$this->confTS['list.']['align.'][$this->fields[$x]]:'left'));;
			$x++;
		}
		unset($r);
		unset($cell);
		if ($this->nbFreeCells>0 && $this->workWidth) {
			$this->rowFreeCellWidth=($this->workWidth-$this->rowWidth)/$this->nbFreeCells;
		}
		
		// If pdf object is not created we cannot calulate rowheights
		if (!is_object($this->pdf)) return;
		// We calculate row height
		$x=0;
		$r=$row->td;
		foreach($r as $cell) {
			// We handle images
			if ($cell->img==1) {
				$h=$this->processImageData($cell);
				if ($h>$this->rowHeight) $this->rowHeight=$h;
			} else {
				if (isset($cell->spec['h'])) {
					$cellHeight=$cell->spec['h'];
					if ($cellHeight>$this->rowHeight) $this->rowHeight=$cellHeight;
					unset($cellHeight);
				} else {
					$val = $this->getData($cell);
					$cellWidth = ($this->rowCellWidth[$x]=='*')?$this->rowFreeCellWidth:$this->rowCellWidth[$x];
					if (isset($cell->spec['w'])) $cellWidth=$cell->spec['w'];
					$nblines=$this->pdf->NbLines($cellWidth, $val);
					if ($nblines*$this->height>$this->rowHeight) $this->rowHeight=$nblines*$this->height;
					unset($cellWidth);
				}
			}
			$x++;
		}
		//error_log(__METHOD__.":".$this->rowHeight);
		unset($r);
		unset($cell);
		
	}
	
	/**
	 * Adds fonts to PDF
	 */
	function addFonts() {
		$this->addFont('3OF9','','3OF9.php');
		$this->addFont('verdana','','verdana.php');
		$this->addFont('verdana','B','verdanab.php');
		$this->addFont('verdana','I','verdanai.php');
		$this->addFont('verdana','U','verdanaz.php');
		$this->addFont('baskerville','','baskerville.php');
		$this->addFont('baskerville','B','baskervilleb.php');
		$this->addFont('baskerville','I','baskervillei.php');
		$this->addFont('cursive','','cursive.php');
		$this->addFont('cursive','B','cursiveb.php');
		$this->addFont('monospace','','monospace.php');
		$this->addFont('monospace','B','monospaceb.php');
		$this->addFont('serif','','serif.php');
		$this->addFont('serif','B','serifb.php');
		$this->addFont('serif','I','serifi.php');
		$this->addFont('serif','BI','serifbi.php');
		$this->addFont('sans-serif','','sans-serif.php');
		$this->addFont('sans-serif','B','sans-serifb.php');
		$this->addFont('sans-serif','I','sans-serifi.php');
		$this->addFont('sans-serif','BI','sans-serifbi.php');
		$this->addFont('times','','times.php');
		$this->addFont('times','B','timesb.php');
		$this->addFont('times','I','timesi.php');
		$this->addFont('times','BI','timesbi.php');
		$this->addFont('tahoma','','tahoma.php');
		$this->addFont('tahoma','B','tahomab.php');
	}
	
	function addFont($font, $fontStyle, $fontFile)  {
		try {
			$this->pdf->AddFont($font,$fontStyle,$fontFile);
		} catch (Exception $e) {
			error_log(__METHOD__.":$font:$fontStyle:$fontFile :".$e->getMessage());
		}
	}
	
	function Rect($x1, $y1, $rectWidth, $rectHeight, $rectStyle, $sw) {
		//error_log(__METHOD__.": $rectStyle, $sw");
		if (strpos($rectStyle,'F')!==false) $this->pdf->Rect($x1, $y1, $rectWidth, $rectHeight, "F");
		if (strpos($rectStyle,'D')!==false && $sw) {
			$this->pdf->SetLineWidth($sw);
			$x2 = $x1 + $rectWidth;
			$y2 = $y1 + $rectHeight;
			$this->pdf->Line($x1, $y1, $x2, $y1);
			$this->pdf->Line($x2, $y1, $x2, $y2);
			$this->pdf->Line($x2, $y2, $x1, $y2);
			$this->pdf->Line($x1, $y2, $x1, $y1);
		}
	}
	/**
	 *
	 * @param int $pixel
	 */
	
	public function convertPixelsToMm($pixel) {
		$mm=($pixel* 25.4)/$this->dpi;
		return $mm;
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
			$cmd=$this->conf['inputvar.']['cmd'];
			$prePDFDETXMLFunction=$this->conf[$cmd.'.']['prePDFDETXMLFunction'];
			if ($prePDFDETXMLFunction) {
				eval($prePDFDETXMLFunction);
			}
	
			$postPDFDETXMLFunction=$this->conf[$cmd.'.']['postPDFDETXMLFunction'];
			if ($postPDFDETXMLFunction) {
				eval($postPDFDETXMLFunction);
			}
		} catch (Exception $e) {
			echo 'PDF Detail Template error : '.$e->getMessage().'<br>';
			echo "============================<br>";
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			echo "============================<br>";
			die();
		};
		//error_log(__METHOD__.":".str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		// Do we handle multiple media (default no)
		$this->multipleMedia=$this->confTS[$this->pluginId.'.'][$cmd.'.']['multipleMedia']?$this->confTS[$this->pluginId.'.'][$cmd.'.']['multipleMedia']:($this->confTS['default.'][$cmd.'.']['multipleMedia']?$this->confTS['default.'][$cmd.'.']['multipleMedia']:false);
	
		$this->documentFormat=A4; //210 × 297
		$this->documentUnit='mm';
		$this->documentOrientation='P';	// portrait
		$this->documentHeight=297;
		$this->documentWidth=210;
		$this->lineHeight=0.0;
		$noheader=false;
		$nofooter=false;
	
		// We handle special settings here
		foreach($xml->spec as $s) {
			$noheader=$s['nh'];
			$nofooter=$s['nf'];
			$margintop=$s['mt'];
			$marginleft=$s['ml'];
			$marginright=$s['mr'];
			if (isset($s['o'])) {
				$this->documentOrientation=$s['o'];
			}
			$marginbottom=$s['mb'];
			$this->documentFormat=array($s['w'],$s['h']);
		}
		if (orientation=='L') {
			$this->documentHeight=210;
			$this->documentWidth=297;
		}
	
		$this->documentUnit='mm';
		$this->pdf = new tx_metafeedit_pdf($this->documentOrientation, $this->documentUnit, $this->documentFormat);
		$this->pdf->caller=&$this;
	
		$this->addFonts();
		$this->pdf->nofooter=$nofooter;
		// TODO Handle typoscript here ...
	
		$this->pdf->bottommargin=$marginbottom?$marginbottom:8;
		$this->pdf->leftmargin=$marginleft?$marginleft:8;
		$this->pdf->rightmargin=$marginright?$marginright:8;
		$this->pdf->topmargin=$margintop?$margintop:8;
		//$docWidth=$this->documentWidth-$this->pdf->leftmargin-$this->pdf->rightmargin;
		// We calculate last cell size eventually
	
		$this->workWidth=$this->documentWidth-$this->pdf->rightmargin-$this->pdf->leftmargin;
		//$spaceLeft=$this->workWidth-$docWidth;
	
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=7;
	
		$this->pdf->AliasNbPages();
		$this->pdf->setMargins($this->pdf->leftmargin,$this->pdf->topmargin,$this->pdf->rightpmargin);
		$this->pdf->SetAutoPageBreak(1,$this->pdf->bottommargin);
		$this->pdf->AddPage();
		$this->pdf->SetDisplayMode('real','single');
	
		// We handle the header here
		//
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
	
		//if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];
		$pageHeight=$this->pdf->topmargin;
		// We set grey color by default
		$this->pdf->setFillColor(200,200,200);
		if (!$noheader) {
			$this->pdf->SetFont('Helvetica','B',11);
			$this->pdf->SetY(0);
			$this->pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');
			$this->pdf->SetXY($this->pdf->leftmargin,6);
			//We draw line under report title
			$this->pdf->Cell(0,1,'','T',0,'C');
			$this->pdf->Ln(2);
		}
	
		$fs=9;
		$cell=false;
		// We set height and font
		$this->getPolice($police);
		$this->getFont($font);
		$this->pdf->SetFont($font,'',$police);
		$alt=1;
		// Content
		$this->pdf->setFillColor(255,255,255);
		$this->height = ($this->confTS[$this->pluginId.'.'][$cmd.'.']['height'])?$this->confTS[$this->pluginId.'.'][$cmd.'.']['height']:(($this->confTS['default.'][$cmd.'.']['height'])?$this->confTS['default.'][$cmd.'.']['height']:($this->pdf->cellsize?$this->pdf->cellsize:5)); // hauteur de la ligne pdf
		$this->r=0;
		// We print rows...
		foreach($xml->tr as $row) {
			$this->h=$this->rowHeight=@$row->spec['rh']?(float)@$row->spec['rh']:$this->height;
			if (@$row->spec['ap']) {
				$this->pdf->addPage();
			}
			// we change color
			$x=0; //compteur des colonnes
			if ($alt>1) {							// changement de couleur 1 ligne sur 2
				$alt=0;
				$this->pdf->setFillColor(200,200,200);
			}
			$alt++;
			$nbcols=count($row->td);
			$this->rowYOffset=0;
	
			if ($row->gb) {
				$this->pdf->SetLineWidth(0.3);
				$this->pdf->SetFont('Helvetica', 'B', 9);
			} else {
				$this->pdf->SetLineWidth(0.2);
				$this->pdf->SetFont('Helvetica', '', 9);
			}
			
			//We detect sizes
			$this->calculateRowCellSizes($row);
			$this->checkForPageBreak();
	
			// We print row cells ...
			$x=0;
			foreach($row->td as $cell) {
				$this->cellWidth=(float)(($this->rowCellWidth[$x]=='*')?$this->rowFreeCellWidth:$this->rowCellWidth[$x]); //taille de la cellule
				$val = $this->getData($cell);
				//background color
				if (isset($cell->spec['bc']) && $cell->spec['bc'] != '') {
					$bca=t3lib_div::trimexplode(',',$cell->spec['bc']);
					$this->pdf->setFillColor((int)$bca[0],(int)$bca[1],(int)$bca[2]);
				}
				//foreground color
				if (isset($cell->spec['fc']) && $cell->spec['fc'] != '') {
					$fca=t3lib_div::trimexplode(',',$cell->spec['fc']);
					$this->pdf->setDrawColor($fca[0],$fca[1],$fca[2]);
				}
				//tc :text color
				if (isset($cell->spec['tc']) && $cell->spec['tc'] != '') {
					$tca=t3lib_div::trimexplode(',',$cell->spec['tc']);
					$this->pdf->setTextColor($tca[0],$tca[1],$tca[2]);
				}
		
				if ($cell->line==1) {
					//We handle lines here
					$this->pdf->Line($cell->line['x1'], $cell->line['y1'], $cell->line['x2'], $cell->line['y2']);
				} elseif ($cell->rect==1) {
					//We handle rectangles here
					$rectWidth = $cell->rect['x2'] - $cell->rect['x1'];
					$rectHeight =  $cell->rect['y2'] - $cell->rect['y1'];
					$sw = isset($cell->rect['sw']) ? $cell->rect['sw'] : 0;
					//bc : background color
					//fc : foreground color
					//sw : stroke width
					$rectStyle = '';
					if (isset($cell->rect['bc'])) {
						$bca=t3lib_div::trimexplode(',',$cell->rect['bc']);
						$this->pdf->setFillColor((int)$bca[0],(int)$bca[1],(int)$bca[2]);
						$rectStyle .= 'F';
					}
					if (isset($cell->rect['fc']) && $sw) {
						$sca=t3lib_div::trimexplode(',',$cell->rect['fc']);
						$this->pdf->setDrawColor($sca[0],$sca[1],$sca[2]);
						$rectStyle .= 'D';
					}
					$this->Rect($cell->rect['x1'], $cell->rect['y1'], $rectWidth, $rectHeight, $rectStyle, $sw);
					//$this->pdf->Rect($cell->rect['x1'], $cell->rect['y1'], $rectWidth, $rectHeight, $rectStyle);
				} elseif ($cell->img==1) {
					//We handle image cells here
					$this->PDFDisplayImage($cell);
				} elseif ($cell->spec['bct'] && $cell->spec['bct']!='none') {
					//Bar codes
					//bct = bar code type
					$this->PDFDisplayBarcode($cell);
				} else {
					$this->PDFDisplayText($cell,$val);
				}
				$x++;
			}
			$aln=0;
			if (@$row->spec['aln']) {
				$this->rowYOffset=$this->rowYOffset+$row->spec['aln'];
				//$aln=@$row->spec['aln'];
			}
			$this->pdf->setY($this->rowYOffset);
			//$this->pdf->setY($this->cellY+$this->rowHeight+$aln);
			$this->pdf->setFillColor(255,255,255);
			$this->r++;
		}
		ob_clean();
		$this->pdf->generatePrintScript($print,$printer,$server);
		try {
			$this->pdf->Output($caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf', 'I');
		} catch (Exception $e) {
			error_log(__METHOD.":$e");
		}
		die;
	}
	/**
	 * Create the final cache directory if it does not exist. This method
	 * exists in TYPO3 v4 only.
	 *
	 * @param string $finalCacheDirectory Absolute path to final cache directory
	 * @return void
	 * @throws \t3lib_cache_Exception If directory is not writable after creation
	 */
	protected function createFinalCacheDirectory($finalCacheDirectory) {
		try {
			t3lib_div::mkdir_deep($finalCacheDirectory);
		} catch (\RuntimeException $e) {
			throw new \t3lib_cache_Exception(
					'The directory "' . $finalCacheDirectory . '" can not be created.',
					1303669848,
					$e
			);
		}
		if (!is_writable($finalCacheDirectory)) {
			throw new \t3lib_cache_Exception(
					'The directory "' . $finalCacheDirectory . '" is not writable.',
					1203965200
			);
		}
	}
	/**
	 * We handle here PDF file generation for lists ...
	 * @param string $content xml description of data to prints
	 * @param object $caller
	 * @param string $print
	 * @param string $printer
	 * @param string $server
	 */
	function getPDF(&$content,&$caller,$print='',$printer='',$server='') {
		//error_log(__METHOD__." Mem 0: ".$caller->metafeeditlib->getMemoryUsage());
		if (!$content) {
			die(__METHOD__.': No template for pdf mode, maybe pdf export is not activated');
		}

		if (true) {
			// for debug ...
			$finalCacheDirectory = PATH_site . 'typo3temp/Cache/Reports/Data/' . $this->conf['pluginId'] . '/';
			if (!is_dir($finalCacheDirectory)) {
				$this->createFinalCacheDirectory($finalCacheDirectory);
			}
			$this->cacheDirectory = $finalCacheDirectory;
			$file=$this->cacheDirectory.$this->conf['pluginId'].".".$this->conf['LLKey'].".xml";
			file_put_contents($file,str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		}
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
			$cmd=$this->conf['inputvar.']['cmd'];
			//error_log(__METHOD__.$cmd.'yeaakjjj'.print_r($this->conf[$cmd.'.'],true));
			$prePDFXMLFunction=$this->conf[$cmd.'.']['prePDFXMLFunction'];
			if ($prePDFXMLFunction) {
				eval($prePDFXMLFunction);
			}
			
			$postPDFXMLFunction=$this->conf[$cmd.'.']['postPDFXMLFunction'];
			if ($postPDFXMLFunction) {
				eval($postPDFXMLFunction);
			}
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			die( __METHOD__.': Caught exception: '.  $e->getMessage().', maybe pdf export is not activated');
		};
		$this->fields = explode(',', $this->conf['list.']['show_fields']); //liste des champs affiches afin de recuperer la dimension des colonnes defini en TS
		$x=0;
		foreach($this->fields as $field) {
			$this->fields[$x]=str_replace('.','_',$this->fields[$x]);
			$x++;
		}
		
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=5;
		
		// basic line height
		$this->height = ($this->confTS[$this->pluginId.'.']['list.']['height'])?$this->confTS[$this->pluginId.'.']['list.']['height']:(($this->confTS['default.']['list.']['height'])?$this->confTS['default.']['list.']['height']:($this->cellsize?$this->cellsize:5)); // hauteur de la ligne pdf
		// Column size calculations on first row
		if($xml->tr) {
			$this->calculateRowCellSizes($xml->tr[0]);
		}
		
		// Do we handle multiple media (default no)
		$multipleMedia=$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']?$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']:($this->confTS['default.']['list.']['multipleMedia']?$this->confTS['default.']['list.']['multipleMedia']:false);
		// Page size is 21 x 29.7- reduced to 20 to preserve margin.
		
		$this->workWidth=0;
		$this->documentFormat=A4; //210 × 297
		$this->documentUnit='mm';
		$this->documentHeight=210;
		$this->documentWidth=297;
		$this->documentOrientation='L';// landscape
		
		if (($this->rowWidth + ($this->nbFreeCells*10) )<200 && $this->confTS[$this->pluginId.'.']['list.']['orientation']!=='L') {
			$this->documentOrientation='P';	// portrait
			$this->documentHeight=297;
			$this->documentWidth=210;
		} 
		// Line width
		$this->lineWidth=0.3;
		$this->pdf= new tx_metafeedit_pdf($this->documentOrientation, $this->documentUnit, $this->documentFormat);
		$this->pdf->caller=&$this;
		$this->addFonts();
		//@TODO Handle typoscript here ...
		
		$this->pdf->bottommargin=9;
		$this->pdf->leftmargin=8;
		$this->pdf->rightmargin=8;
		$this->pdf->topmargin=8;
		
		// We calculate last cell size eventually
		
		$this->workWidth=$this->documentWidth-$this->pdf->rightmargin-$this->pdf->leftmargin;
		if ($this->nbFreeCells>0) {
			$this->rowFreeCellWidth=(float)(($this->workWidth-$this->rowWidth)/$this->nbFreeCells);
		}
		
		// do this only if cell size not set ...(
		/*$cw=$this->confTS[$this->pluginId.'.']['list.'][$this->fields[$x-1].'.']['width']?$this->confTS[$this->pluginId.'.']['list.'][$this->fields[$x-1].'.']['width']:$this->rowCellWidth[$x-1];
		if (!$cw && $this->rowFreeCellWidth>0) {
			$this->rowCellWidth[$x-1]+=$this->rowFreeCellWidth;
		}*/
		
		$this->pdf->AliasNbPages();
		$this->pdf->setMargins($this->pdf->leftmargin,$this->pdf->topmargin,$this->pdf->rightpmargin);
		$this->pdf->SetAutoPageBreak(1,$this->pdf->bottommargin);
		$this->pdf->AddPage();

		// We handle the header here 
		//
		$title =null;
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		//error_log(__METHOD__.":$title, $recherche");
		if ($this->conf['inputvar.']['sortLetter']) $tri = '  Tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];
		$this->pdf->SetFont('Helvetica','B',11);
		
		$this->pdf->SetY(0);
		$this->pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');	
		$this->pdf->SetFont('Helvetica', '', 9);
		$this->pdf->Cell(0,$this->headercellsize,utf8_decode(htmlspecialchars_decode($recherche)),0,0,'R');
		$this->pdf->SetY(0);
		$this->pdf->Cell(0,$this->headercellsize,$tri,0,0,'C');
		$this->pdf->SetXY($this->pdf->leftmargin,6);
		//We set header color (headers are first row in file).
		$this->pdf->setFillColor(200,200,200);
		$this->pdf->Cell(0,$this->headercellsize,'','T',0,'C');
		$this->pdf->Ln(2);	
		
		// we set font and size
		// Where do we get $plice and $font ??????
		$this->getPolice($police);
		$this->getFont($font);
		$this->pdf->SetFont('Helvetica','',$police);
		$alt=0;
		// Content
		// Grey for headers 
		$this->pdf->setFillColor(125,125,125);
		$this->r=0;
		$this->cellY=$this->pdf->GetY();
		foreach($xml->tr as $row) {
			$this->rowHeight=$this->height;
			if ($row->gb) {
				$this->pdf->SetLineWidth($this->lineWidth);
				$this->pdf->SetFont('Helvetica', 'B', 9);
			} else {
				$this->pdf->SetLineWidth($this->lineWidth);
				$this->pdf->SetFont('Helvetica', '', 9);
			}
			//We calculate row height :
			$this->calculateRowCellSizes($row);
			$this->checkForPageBreak();
			// line color alernating every 2 lines
			if ($alt>1) { 
				$alt=0;
				$this->pdf->setFillColor(200,200,200);
			}
			$cell=false;
			$alt++;
			// We handle cell content here
			$x=0; //column counter
			foreach($row->td as $cell) {
				$this->cellWidth=(float) (($this->rowCellWidth[$x]=='*')?$this->rowFreeCellWidth:$this->rowCellWidth[$x]);
				if ($cell->img==1) {
					$this->cellX=$this->pdf->getX();
					$this->cellY=$this->pdf->GetY();
					// We handle image cells here...
					$this->pdf->Rect($this->cellX, $this->cellY, $this->cellWidth, $this->rowHeight,'FD');
					$this->PDFDisplayimage($cell);
				} else {
					// We handle text cells here..
					$val = $this->getData($cell);
					if (strpos($cell->data,'<img')!==false) {
						//We handle special case where cell data is image source (such as checkboxes)
						$this->PDFDisplayimage($cell);
					} else {
						$this->cellX=$this->pdf->getX();
						switch($this->rowPos[$x]) {
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
					 	if (!$this->r) $p='L'; // So that column headers are always aligned left. 
					 	
					 	if ($row->gb && !strlen($val)) {
					 		$this->pdf->setX($this->cellX+$this->cellWidth);
					 	} else {
					 		if ($row->gb && $x==0) { 
					 			$this->pdf->Cell($this->workWidth,$this->height,$val,1,0,$p,1);
								$this->pdf->setX($this->cellX+$this->cellWidth);
							} else {
								$border=1;							
								$this->cellY=$this->pdf->GetY();
								$this->pdf->Rect($this->cellX, $this->cellY, $this->cellWidth, $this->rowHeight,'FD');
								$this->pdf->MultiCell($this->cellWidth,$this->height,$val,0,$p);
								$this->pdf->setXY($this->cellX+$this->cellWidth,$this->cellY);
							}
						}
					}
				}
				$x++;
			}
			
			$this->pdf->setY($this->cellY+$this->rowHeight);
			//White
			$this->pdf->setFillColor(255,255,255);
			$this->r++;
			
		}
		//Convert to PDF
		$name=$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf';
		ob_clean();
		$this->pdf->generatePrintScript($print,$printer,$server);
		$this->pdf->Output($name, 'I'); 
		die;

	}
	/**
	 * Calculate tab cell height
	 * 
	 */
	function getPDFTABRowHeight($row) {
		/**
		 * Get Max row height
		 */
		$rowHeight=0;
		foreach($row->td as $elem) {
			if ($elem->img==1) {
				$rowHeight+=$this->imageHeight;
			} else {
				$rowHeight+= ceil( $this->pdf->GetStringWidth($elem->data) / $this->cellWidth )*$this->lineHeight;
			}
		}
		return $rowHeight;
	}
	/**
	 * Returns image information
	 * @param SimpleXMLElement $cell
	 * @return int returns image Height ...
	 */
	function processImageData($cell) {
		// We handle images here...
		$val =  $this->getData($cell);
		$vala=t3lib_div::trimexplode(',',$val);
		//Default height for images
		$imgHeight=0;
		//if (isset($cell->img['h'])) $imgHeight=(float)$cell->img['h']; //height override
		
		if (!count($vala)) $vala=array("");
		$img='';
		//Standard image ratio is 4/3
		foreach($vala as $v) {
			if (isset($cell->img['h'])) {
				$imgHeight+=(float)$cell->img['h']; //height override
			} else {
				//@todo what do we do with this ???
				if ($cell->img['gh'] || $cell->img['gw'] || $cell->img['mh'] || $cell->img['mw'] ) {
			
					if ($cell->img['gw'] ) $fileA['file.']['width']=(string)$cell->img['gw'] ;
					if ($cell->img['gh'] ) $fileA['file.']['height']=(string)$cell->img['gh'];
					if ($cell->img['mw'] ) $fileA['file.']['maxW']=(string)$cell->img['mw'] ;
					if ($cell->img['mh'] ) $fileA['file.']['maxH']=(string)$$cell->img['mh'];
					//$imgi=$caller->cObj->getImgResource($cell->img->dir.'/'.$v,$fileA['file.']);
					//if ($imgi[3]) $img=$imgi[3];
				}

				$imgHeight+=$this->height;

				
			}
			// By default we only handle first media
			if (!$this->multipleMedia) break;
		}
		//$imgHeight=120;
		return $imgHeight;
	}
	/**
	 * 
	 *  @param string $fullPathToImage
	 *  @param bool $displayEmptyImage
	 */
	function PDFTabDisplayImage($fullPathToImage,$displayEmptyImage=true){
		$imginfo=getimagesize($fullPathToImage);
		if (is_array($imginfo)) {
			$w=$imginfo[0];
			$h=$imginfo[1];
			$this->pdf->Image($fullPathToImage,$this->pdf->getX()+0.5,$this->pdf->getY()+0.5,0, $this->imageHeight-1);
			$this->pdf->SetY($this->Y+0.5+$this->imageHeight-1);
		} else {
			if ($displayEmptyImage) {
				$fullPathToUnknownImage=PATH_site.'typo3conf/ext/meta_feedit/res/noimage.jpg';
				$this->PDFTabDisplayImage($fullPathToUnknownImage,false);
			} else {
				// Empty image we make room for it
				$this->pdf->SetY($this->Y+0.5+$this->imageHeight-1);
				error_log(__METHOD__.": Could not find $fullPathToImage");
			}
		}
	}
	/**
	 * Displays text in cell
	 * ta : text alignment
	 * b : border width (in mm)
	 * h : height in mm
	 * w : width in mm
	 * bc : background color
	 * fc : foreground color
	 * fs : font size
	 * fb : font boldness
	 * f : font
	 * aln : after line jump in mm
	 * bln : before line jump in mm
	 * l : ??? in mm
	 * fit : how text fits in cell
	 * x : x position in mm
	 * y : y position in mm
	 * t : table cell (inherits default table style : border 0.1 with current draw color, background with alternating colors) 
	 * 
	 * @param SimpleXMLElement $cell
	 * @param string
	 */
	function PDFDisplayText(SimpleXMLElement $cell,$val) {
		//We handle text cells here
		//ta : text alignment
		if ($cell->spec['ta']) $this->rowPos[$x] = $cell->spec['ta'];
		switch($this->rowPos[$x]) {
			case 'right' :
			case 'R' :
				$p = 'R';
				break;
			case 'justify' :
			case 'J' :
				$p = 'J';
				break;
			case 'center';
			case 'C' :
				$p = 'C';
				break;
			default :
				$p = 'L';
			break;
		}
		if (!$this->r) $p = 'L'; // So that column headers are always aligned left.
		
		$this->cellX=$this->pdf->GetX();
		
		if (isset($cell->spec['b'])) $b=(float)$cell->spec['b'];
		if (isset($cell->spec['h'])) $this->h=(float)$cell->spec['h'];
		if ($this->h>$this->rowHeight) $this->rowHeight=$this->h;
		
		if (isset($cell->spec['w'])) $w=$cell->spec['w'];
		
		// We handle transparent cell
		$fillStyle=isset($cell->spec['bc'])?($cell->spec['bc'] == ''?'':'F'):'';
		$fillStyle.=$b?(isset($cell->spec['fc'])?($cell->spec['fc'] == ''?'':'D'):''):'';
		
		
		if (isset($cell['t']) && $cell['t']) {
			if (!isset($cell->spec['b'])) $b=0.1;
			if (!$fillStyle) $fillStyle="FD";
		}
		//font size
		$fs='';
		if (isset($cell->spec['fs'])) {
			$fs=(int)$cell->spec['fs'];
			$this->pdf->SetFontSize($fs);
		}
		//font weight
		$fb='';
		if (isset($cell->spec['fb'])) {
			$fb=$cell->spec['fb'];
		}
		
		//before line
		$bln=0;
		if (isset($cell->spec['bln'])) {
			$bln=(int)$cell->spec['bln'];
			$this->pdf->Ln($bln);
		}
		//after line
		$aln=0;
		if (isset($cell->spec['aln'])) {
			$aln=(int)$cell->spec['aln'];
		}
		$l=0;
		if (isset($cell->spec['l'])) {
			$l=(int)$cell->spec['l'];
		}
		//font
		$f='';
		if (isset($cell->spec['f'])) $f=$cell->spec['f'];
		$this->pdf->SetFont($f,$fb,$fs);
		$fit='fit';
		if (isset($cell->spec['fit'])) $fit=$cell->spec['fit'];
		if (isset($cell->spec['x']) && isset($cell->spec['y'])) {
			
			$this->pdf->SetXY((float)$cell->spec['x'],(float)$cell->spec['y']+$bln);
			//$this->pdf->SetXY((float)$cell->spec['x'],(float)$cell->spec['y']);
			$this->cellY = $this->pdf->getY();
			$this->cellX = $this->pdf->getX();
			//error_log(__METHOD__."rect:$this->cellX, $this->cellY, $this->cellWidth, $this->rowHeight,$fillStyle,$b");
			$this->Rect($this->cellX, $this->cellY, $this->cellWidth, $this->rowHeight,$fillStyle,$b);
			switch($fit){
				case 'fit2width':
					//error_log(__METHOD__.": 0.2 >".$val);
					$this->TextFit2Width($this->cellWidth, $val, $fs);
					$this->pdf->ClippedMultiCell($this->cellWidth,$this->h,$this->h,$val,0,$p,false);
					break;
				case 'fit2box': 
					//error_log(__METHOD__.": 0.3 >".$val);
					$this->TextFit2Box($this->cellWidth,$this->h,$this->h, $val, $fs);
					$this->pdf->ClippedMultiCell($this->cellWidth,$this->h,$this->h,$val,0,$p,false);
					//$this->pdf->MultiCell($this->cellWidth,$this->height,$val,0,$p,false);
					break;
				default://	'fix':
					//error_log(__METHOD__.":$this->cellWidth,$this->h,$this->height,$val,0,$p,false");
					$this->pdf->ClippedMultiCell($this->cellWidth,$this->h,$this->h,$val,0,$p,false);
					break;
					
			}
			
			$this->pdf->SetXY($this->cellX+$this->cellWidth,$this->cellY);
			$this->rowYOffset = $this->pdf->getY()>$this->rowYOffset?$this->pdf->getY():$this->rowYOffset;
		} else if (isset($cell->spec['x'])) {
			//error_log(__METHOD__.":1 >".$val);
			$this->pdf->SetX((float)$cell->spec['x']);
			$this->cellY = $this->pdf->getY();
			$this->cellX = $this->pdf->getX();
			$this->Rect($this->cellX, $this->cellY, $this->cellWidth, $this->rowHeight,$fillStyle,$b);
			$this->pdf->MultiCell($this->cellWidth,$this->height,$val,0,$p,0);
			$this->pdf->SetXY($this->cellX+$this->cellWidth,$this->cellY);
			$this->rowYOffset = $this->pdf->getY()>$this->rowYOffset?$this->pdf->getY():$this->rowYOffset;
		} else {
			$this->cellX = $this->pdf->getX();
			$this->cellY = $this->pdf->getY();
			//error_log(__METHOD__.":$b, $bln,$fillStyle >".$val);
			$this->Rect($this->cellX, $this->cellY, $this->cellWidth, $this->rowHeight,$fillStyle,$b);
			$this->pdf->MultiCell($this->cellWidth,$this->height,$val,0,$p,0);
			if ($l) {
				//We draw line
				$this->pdf->Cell(0,$l,'','T',0,'C',0);
			}
			// We handle bigger cells !!!
			$this->rowYOffset = $this->pdf->getY()>$this->rowYOffset?$this->pdf->getY():$this->rowYOffset;
			$this->pdf->SetXY(($this->cellX+$this->cellWidth),$this->cellY);
		}
		
		if ($aln) {
			$this->pdf->Ln($aln);
			$this->rowYOffset = $this->pdf->getY()>$this->rowYOffset?$this->pdf->getY():$this->rowYOffset;
		}
	}
	/**
	 * Displays image in cell
	 * @param unknown_type $cell
	 */
	function PDFDisplayImage(SimpleXMLElement $cell) {
		
		$val = $this->getData($cell);
		if (strpos($cell->data,'<img')!==false) {
			try {
				$img = new SimpleXMLElement($cell->data);
			} catch (Exception $e) {
				error_log(__METHOD__.":".$e->getMessage());
			};
			$vala=array();
			if ($img['src']) $vala[]=$img['src'];
			unset($xml);
		} else {
			$vala=t3lib_div::trimexplode(',',$val);
		}
		$img='';
	 	$this->cellX=isset($cell->spec['x'])?(float)$cell->spec['x']:$this->pdf->getX();
	 	$this->cellY=$this->pdf->GetY();
	 	if (isset($cell->spec['y'])) {
	 		$this->pdf->SetY((float)$cell->spec['y']);
	 		$this->cellY=(float)$cell->spec['y'];
	 	}
	 	//Image border
	 	$ib = 0;
	 	if (isset($cell->img['b'])) $ib = (float)$cell->img['b'];
	 	if ($ib) {
			//$this->pdf->Rect($this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight,'FD');
			$this->Rect($this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight,'FD',$ib);
	 	}
	 	//error_log(__METHOD__.": -".print_r($cell->img,true));
	 	$this->pdf->setX($this->cellX);
	 	//error_log(__METHOD__.": bf $this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight");
		foreach($vala as $v) {
			$imgData=$this->getDisplayImage($cell->img->dir?$cell->img->dir.'/'.$v:$v);
			$img=$imgData['path'];
			$imgInfo=$imgData['imginfo'];
			//error_log(__METHOD__.": -".print_r($imgData,true));
			if ($cell->img['gh'] || $cell->img['gw'] || $cell->img['mh'] || $cell->img['mw'] ) {
				if ($cell->img['gw'] ) $fileA['file.']['width']=(string)$cell->img['gw'] ;
				if ($cell->img['gh'] ) $fileA['file.']['height']=(string)$cell->img['gh'];
				if ($cell->img['mw'] ) $fileA['file.']['maxW']=(string)$cell->img['mw'] ;
				if ($cell->img['mh'] ) $fileA['file.']['maxH']=(string)$cell->img['mh'];
				$imgi=$caller->cObj->getImgResource($cell->img->dir.'/'.$v,$fileA['file.']);
				if ($imgi[3]) $img=$imgi[3];
			}
			//if files on linux / nt utf8 encoded 
			$imgw=0.0;
			$imgh=0.0;
			$imgx=0.0;
			$imgy=0.0;
			if (is_array($imgInfo)) {
				//Real image height and width
				$w=$imgInfo[0];
				$h=$imgInfo[1];
				$ro=$w/$h;
				
				$imgh=(float)$this->height-(2*$this->lineWidth);
				$imgx=(float)$this->pdf->GetX()+$this->lineWidth;
				$imgy=(float)$this->pdf->GetY()+$this->lineWidth;
				$imgw=(float)$this->cellWidth;
				//error_log(__METHOD__.": bf2 $imgx,$imgy, $imgw, $imgh");
				//$imgw=0;//We do not stretch image
				if (isset($cell->img['h'])) $imgh=(float)$cell->img['h']; //height override
				if (isset($cell->img['w'])) $imgw=(float)$cell->img['w']; //width override
				if (isset($cell->img['x'])) $imgx=(float)$cell->img['x']; //x override
				if (isset($cell->img['y'])) $imgy=(float)$cell->img['y']; //y override
				//error_log(__METHOD__.": bf3 $imgx,$imgy, $imgw, $imgh");
				
				$fit='fit';
				if (isset($cell->img['fit'])) $fit=$cell->img['fit'];
				$rd=$imgw/$imgh;
				$px=$imgx;
				$py=$imgy;
				$ph=$imgh;
				$pw=$imgw;
				//$this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight
				$this->pdf->ClippingRect($px,$py,$pw,$ph);
				//error_log(__METHOD__."fit: $px,$py,$pw,$ph, $fit");
				switch($fit) {
					case 'fit':
						if ($ro<$rd) {
							//height piority
							$ph=$imgh;
							$pw=$ro*$ph;
							$px+=($imgw-$pw)/2;
						} else {
							// Width Priority
							$pw=$imgw;
							$ph=$pw/$ro;
							$py+=($imgh-$ph)/2;
						}
						//error_log(__METHOD__.": fit $img,$px,$py,$pw,$ph");
						$this->pdf->Image($img,$px,$py,$pw,$ph);
						//We calculate image width based on picture width/height ratio);
						if ($imgh && !$imgw) {
							$imgw=$imgh*($w/$h);
						}
						break;
					case 'stretch':
						//error_log(__METHOD__.": stretch $img,$px,$py,$pw,$ph");
						$this->pdf->Image($img,$px,$py,$pw,$ph);
						break;
					case 'fix':
						//error_log(__METHOD__.": fix $img,$px,$py,".$this->convertPixelsToMm($w).",",$this->convertPixelsToMm($h));
						$this->pdf->Image($img,$px,$py,$this->convertPixelsToMm($w),$this->convertPixelsToMm($h));
						break;
				}
				$this->pdf->UnsetClipping();
				$w=$size;
				//@todo why do we have two types of borders based on $ib ?
				if (isset($cell->spec['w'])) $w=$cell->spec['w'];
				if ($ib) {
					//error_log(__METHOD__.":img $imgx, $imgy,w: $imgw, $imgh");
					$this->Rect($imgx,$imgy,$imgw,$imgh,"D",$ib);
				}
				
				$this->rowYOffset = ($imgy+$imgh)>$this->rowYOffset?($imgy+$imgh):$this->rowYOffset;
			}
			// By defaullt we only handle first media
			if (!$multipleMedia) break;
		}
		$this->pdf->setX($this->cellX+$this->cellWidth);
	}
	/**
	 * 
	 * @param unknown_type $cell
	 */
	function PDFDisplayBarcode(SimpleXMLElement $cell) {
		
		$val = $this->getData($cell);
		//error_log(__METHOD__.":$val");
		$bool1D=false;
		$bool2D=false;
		$imgPath='';
		switch($cell->spec['bct']) {
			/**
			 * 2D : RAW,RAW2, 'QRCODE,L','QRCODE,M','QRCODE,Q','QRCODE,H','DATAMATRIX','PDF417'
			 * 1D : 'C39','C39E','C39E+','C93','C128','C128A','C128B','C128C','EAN2','EAN5','EAN8','EAN13','CODABAR','I25','I25+','MSI','MSI+','S25','S25+','CODE11','KIX','RMS4CC','PLANET','POSTNET','UPCA','UPCE','IMB','PHARMA','PHARMA2T'
			 */
			
			case 'QRH':
				$bool2D=true;
				// include 2D barcode class (search for installation path)
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_2DBarcode($val, 'QRCODE,H');
				break;
			case 'QRL':
				$bool2D=true;
				
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_2DBarcode($val, 'QRCODE,L');
				break;
			case 'QRM':
				$bool2D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_2DBarcode($val, 'QRCODE,M');
				break;
			case 'DM':
				$bool2D=true;
				$barcodeobj = new tx_metafeedit_2DBarcode($val, 'DATAMATRIX');
				
				break;
			case 'PDF417':
				$barcodeobj = new tx_metafeedit_2DBarcode($val, 'PDF417');
				$imgPath=$barcodeobj->getBarcodeImage(4, 4, array(0,0,0));
				break;
				
			case 'C128':	
				$bool1D=true;	
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C128');
				break;
			case 'C128A':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C128A');
				break;
			case 'C128B':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C128B');
				break;
			case 'C128C':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C128C');
				break;
			case 'C39':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C39');
				break;
			case 'C39E':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C39E');
				break;
			case 'C39E+':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C39E+');
				break;
			case 'C93':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'C39E+');
				break;
			case 'EAN2':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'EAN2');
				break;
			case 'EAN5':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'EAN5');
				break;
			case 'EAN8':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'EAN8');
				break;
			case 'EAN13':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'EAN13');
				break;
			case 'CODABAR':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'CODABAR');
				break;
			case 'I25':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'I25');
				break;
			case 'I25+':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'I25+');
				break;
			case 'MSI':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'MSI');
				break;
			case 'MSI+':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'MSI+');
				break;
			case 'S25':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'S25');
				break;
			case 'S25+':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'S25+');
				break;
			case 'CODE11':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'CODE11');
				break;
			case 'KIX':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'KIX');
				break;
			case 'RMS4CC':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'RMS4CC');
				break;
			case 'PLANET':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'PLANET');
				break;
			case 'POSTNET':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'POSTNET');
				break;
			case 'UPCA':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'UPCA');
				break;
			case 'UPCE':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'UPCE');
				break;
			case 'IMB':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'IMB');
				break;
			case 'PHARMA':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'PHARMA');
				break;
			case 'PHARMA2T':
				$bool1D=true;
				// set the barcode content and type
				$barcodeobj = new tx_metafeedit_1DBarcode($val, 'PHARMA2T');
				break;
			default :
				error_log(__METHOD__.": unknown barcode ".$cell->spec['bct']);
		}
		// output the barcode as PNG image
		if ($bool2D && !$imgPath) $imgPath=$barcodeobj->getBarcodeImage(6, 6, array(0,0,0));
		if ($bool1D && !$imgPath) $imgPath=$barcodeobj->getBarcodeImage(2, 30, array(0,0,0));
		
		$img='';
		$this->cellX=isset($cell->spec['x'])?(float)$cell->spec['x']:$this->pdf->getX();
		$this->cellY=isset($cell->spec['y'])?(float)$cell->spec['y']:$this->pdf->GetY();
		if (isset($cell->spec['y'])) {
			$this->pdf->SetY((float)$cell->spec['y']);
		}
		//Image border
		$ib=0;
		if (isset($cell->spec['b'])) $ib=(int)$cell->spec['b'];
		if (isset($cell['t'])) {
			if (!isset($cell->spec['b'])) $ib=0.1;
			//if (!$fillStyle) $fillStyle="FD";
		}
		if ($ib) {
			//$this->pdf->setX($this->cellX);
			//error_log(__METHOD__."x:$this->cellX  y:$this->cellY");
			//$this->pdf->Rect($this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight,'FD');
			$this->Rect($this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight,'FD',$ib);
		} else {
			$this->pdf->Rect($this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight,'F');
		}
		
		
		//$this->pdf->Rect($this->cellX,$this->cellY, $this->cellWidth, $this->rowHeight,'FD');
		$this->pdf->setX($this->cellX);
		
		$imgData=$this->getDisplayImage($imgPath);
		$img=$imgData['path'];
		$imgInfo=$imgData['imginfo'];

		if (is_array($imgInfo)) {
			$w=$imgInfo[0];
			$h=$imgInfo[1];
			$ro=$w/$h;

			$imgh=$this->rowHeight-(2*$this->lineWidth);
			$imgx=$this->pdf->GetX()+$this->lineWidth;
			$imgy=$this->pdf->GetY()+$this->lineWidth;
			$imgw=$this->cellWidth;
			$rd=$imgw/$imgh;
			$px=$imgx;
			$py=$imgy;
			if ($ro<$rd) {
				//height piority
				$ph=$imgh;
				$pw=$ro*$ph;
				$px+=($imgw-$pw)/2;
			} else {
				// Width Priority
				$pw=$imgw;
				$ph=$pw/$ro;
				$py+=($imgh-$ph)/2;
			}
			$this->pdf->Image($img,$px,$py,$pw,$ph);
			//We calculate image width based on picture width/height ratio);
			if ($imgh && !$imgw) {
				$imgw=$imgh*($w/$h);
			}
			/*if ($ib) {
				$this->pdf->Rect($imgx,$imgy,$imgw,$imgh);
			}*/
			// We erase barcode from disk
			if (!$imgData['notfound']) unlink($imgData['path']);
			$this->rowYOffset = ($imgy+$imgh)>$this->rowYOffset?($imgy+$imgh):$this->rowYOffset;
		}

		$this->pdf->setX($this->cellX+$this->cellWidth);
	}
	/**
	 * Returns Image to be displayed and associated information
	 * @param string $fullPathToImage
	 * @return array
	 */
	
	function getDisplayImage($relativePathToImage){
		$imgData=array();
		// Image file names must be encoded in UTF-8 on disk.
		//@todo what if photo original filenames are from windows ?
		
		$encoding = mb_detect_encoding($relativePathToImage, array('UTF-8','ISO-8859-1','windows-1252'), true);
		if ($encoding !== 'UTF-8') {
			$fullPathToImage=PATH_site.utf8_encode($relativePathToImage);
		} else {
			$fullPathToImage=PATH_site.$relativePathToImage;
		}
		$imgData['imginfo']=getimagesize($fullPathToImage);
		
		if (is_array($imgData['imginfo'])) {
			$imgData['path']=$fullPathToImage;
		} else {
			//@todo handle broken image
			$imgData['path']= PATH_site.'typo3conf/ext/meta_feedit/Resources/Public/Images/no_user_picture.png';
			//$imgData['path']= PATH_site.'typo3conf/ext/meta_feedit/res/noimage.jpg';
			$imgData['notfound']=true;
			$imgData['imginfo']=getimagesize($imgData['path']);
		}
		return $imgData;
	}
	/**
	* PDF Tabular print row
	*
	*/
	function getPDFTABPrintRow($cellData) {
		$cptCols=0;
		foreach ($cellData as $cell) {
			$X=$this->pdf->leftmargin+($cptCols*$this->cellWidth);
			$this->pdf->setXY($X,$this->Y);
			foreach($cell->td as $elem) {
				$this->pdf->setX($X);
				if ($elem->img==1) {
					$vala=t3lib_div::trimexplode(',',$elem->data);
					$img='';
					//$this->cellX=$this->pdf->getX();
					//$this->pdf->Cell($size,$this->height,'',1,0,'L',1);
					//$this->pdf->setX($this->cellX);
					if ($elem->data != '') {
						foreach($vala as $v) {
							$img=PATH_site.($v?$elem->img->dir.'/'.$v:'');
							$this->PDFTabDisplayImage($img);
							// By defaullt we only handle first media
							if (!$this->multipleMedia) break;
						}
					} else {
						$this->PDFTabDisplayImage('');
						// Empty image we make room for it
					}
				} else {
					$val= $this->getData($elem);
					$this->pdf->MultiCell($this->cellWidth,$this->lineHeight,$val,0,'L',0);
				}
			}
			$cptcols++;
			$Y2=$this->pdf->GetY();
			if ($this->LastY<$Y2) {
				$this->LastY=$Y2;
			}
			$cptCols++;
		}
		//Draw rectangles
		$cptCols=0;
		foreach ($cellData as $cell) {
			$rh=$this->LastY-$this->Y;
			$this->pdf->Rect($this->pdf->leftmargin+($this->cellWidth*$cptCols), $this->Y, $this->cellWidth, $rh);
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
		//error_log(__METHOD__);
		//$xml = new SimpleXMLElement($content);
		try {
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			die( 'Caught exception: '.  $e->getMessage());
		};
		
		$this->documentOrientation = ($this->confTS[$this->pluginId.'.']['list.']['OrientationPDF'])?$this->confTS[$this->pluginId.'.']['list.']['OrientationPDF']:(($this->confTS['default.']['list.']['OrientationPDF'])?$this->confTS['default.']['list.']['OrientationPDF']:'P');
		$this->documentFormat=A4;
		$this->documentUnit='mm';
		$this->documentHeight=210;
		$this->documentWidth=297;
		//$this->documentOrientation='L';// landscape
		//$this->documentOrientation='P';	// portrait
		if ($this->documentOrientation=='P') {
			$this->documentHeight=297;
			$this->documentWidth=210;
		}
		// Line width
		$this->lineWidth=0.3;
		// Number of columns in tabular display
		$nbCols = (int)$this->conf['list.']['nbCols'];		// Nombre de colonnes voulues par l'utilisateur
		
		$this->footercellsize=7;
		$this->headercellsize=7;
		$this->cellsize=7;
		// Do we handle multiple media (default no)
		$this->multipleMedia=$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']?$this->confTS[$this->pluginId.'.']['list.']['multipleMedia']:($this->confTS['default.']['list.']['multipleMedia']?$this->confTS['default.']['list.']['multipleMedia']:false);
		
		
		$this->pdf = new tx_metafeedit_pdf($this->documentOrientation, $this->documentUnit, $this->documentFormat);
		$this->pdf->caller=&$this;
		$this->addFonts();
		$this->pdf->bottommargin=9;
		$this->pdf->leftmargin=8;
		$this->pdf->rightmargin=8;
		$this->pdf->topmargin=8;
		
		$this->workWidth=$this->documentWidth-$this->pdf->rightmargin-$this->pdf->leftmargin;
		// We calculate last cell size eventually
		// do this onlsy if cell size not set ...(
		/*$cw=$this->confTS[$this->pluginId.'.']['list.'][$this->fields[$x].'.']['width']?$this->confTS[$this->pluginId.'.']['list.'][$this->fields[$x].'.']['width']:0;
		 if (!$cw && $this->workWidth>0) {
		$sizeArr[$x-1]+=$this->workWidth;
		}*/
		//$cellWidth=$this->workWidth/$nbCols;
		
		
		$this->pdf->AliasNbPages();
		$this->pdf->setMargins($this->pdf->leftmargin,$this->pdf->topmargin,$this->pdf->rightpmargin);
		$this->pdf->SetAutoPageBreak(1,$this->pdf->bottommargin);
		$this->pdf->AddPage();
		// title of the page - Il est definit ici et non dans le header pour qu'il ne soit pas present sur chaque page mais seulement la 1ere
		$title =null; 
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		/*if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		$this->pdf->SetFont('Helvetica','B',11);
		$this->pdf->SetY(15);
		$this->pdf->Cell(0,8,utf8_decode($title),0,0,'C');	
		$this->pdf->SetFont('Helvetica', '', 9);
		$this->pdf->SetY(20);
		$this->pdf->Cell(0,8,utf8_decode($recherche),0,0,'C');
		$this->pdf->SetY(25);
		$this->pdf->Cell(0,8,$tri,0,0,'C');
		$this->pdf->Ln();*/

		if ($this->conf['inputvar.']['sortLetter']) $tri = '  Tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];
		$this->pdf->SetFont('Helvetica','B',11);
		
		$this->pdf->SetY(0);
		$this->pdf->Cell(0,$this->headercellsize,utf8_decode($title),0,0,'L');
		$this->pdf->SetFont('Helvetica', '', 9);
		$this->pdf->Cell(0,$this->headercellsize,utf8_decode(htmlspecialchars_decode($recherche)),0,0,'R');
		$this->pdf->SetY(0);
		$this->pdf->Cell(0,$this->headercellsize,$tri,0,0,'C');
		$this->pdf->SetXY($this->pdf->leftmargin,6);
		//$this->pdf->Ln();
		$this->pdf->setFillColor(200,200,200);
		$this->pdf->Cell(0,$this->headercellsize,'','T',0,'C');
		$this->pdf->Ln(2);		
		
		// on met la taille et la police d'impression
		// Where do we get $plice and $font ??????
		$this->getPolice($police);
		$this->getFont($font);
		$this->pdf->SetFont($font,'',$police);
		//$this->pdf->Ln();
		$alt=0;

		// Contenu
		$this->pdf->setFillColor(200,200,200);		// couleur de fond des cellules
		$nbx=0;		
		$cptcols = 0;
		$posy=1;
		$cpt=0;
		$this->imageHeight=20;
		

		/*if ($this->documentOrientation == 'P') $this->workWidth = 20;
		else $this->workWidth = 27.7;*/

		if ($this->confTS[$this->pluginId.'.']['list.']['size']) $size = $this->confTS[$this->pluginId.'.']['list.']['size'];
		else $size = $this->workWidth/$nbCols;			// taille de la colonne en cm selon l'orientation du PDF
		$this->cellWidth=(float)$size;
		
		if ($this->confTS[$this->pluginId.'.']['list.']['sizeh']) $sizeh = $this->confTS[$this->pluginId.'.']['list.']['sizeh'];
		$sizeh = 5;			// 7 => hauteur d'une ligne,  *10 => pour avoir la taille en mm
		$this->lineHeight=$sizeh;
		$this->Y=$this->pdf->GetY();
		$this->LastY=$this->Y;
		// We should read rows , nbcols at a time (so we can caluclate line height
		$nbCells=count($xml->tr);
		$cellHeight=array();
		$this->rowHeight=0;
		$cellData=array();
		foreach($xml->tr as $row) {
			
			$cellData[$cptCols]=$row;
			$cellHeight[$cptCols]=$this->getPDFTABRowHeight($row);
			if ($cellHeight[$cptCols]>$this->rowHeight) $this->rowHeight=$cellHeight[$cptCols];
			/**
			 * End of row detection
			 */
			$cptCols++;
			$nbCells--;
			
			if ($cptCols>=$nbCols || $nbCells<=0 ) {
				// Why +5 ?
				if ($this->Y+$this->rowHeight+$this->pdf->bottommargin+5>=$this->documentHeight) {
					$this->Y=$this->pdf->topmargin;
					$this->LastY=$this->pdf->topmargin;
					$this->pdf->addPage();
				}
				// We have reached end of row or end of file ...
				$this->getPDFTABPrintRow($cellData);
				// We reset data arrays
				$cptCols=0;
				$cellData=array();
				$cellHeight=array();
				$this->rowHeight=0;	
			}
		}
		/*
		//Draw rectangles
		
		while ($cptcols>0) {
			$cptcols--;
			$rh=$LastY-$Y;
			$this->pdf->Rect($this->pdf->leftmargin+($size*$cptcols), $Y, $size, $rh);
		}
		$Y=$LastY;
		$this->pdf->Ln();					// Nouvelle ligne
		$cptcols = 0;				// Compteur de colonnes remis a 0
		$posy=$this->pdf->getY();			// On recupere la position en Y actuelle pour savoir ou placer les prochaines colonnes
		$nbx=-1;					// le nombre d'elements et remis a 0 (-1 en realite car il est incremente juste apres)
		$marginh=0;
		if ($Y>$this->documentHeight) {
			$Y=$this->pdf->topmargin;
			$this->pdf->addPage();
		}
		// I subtracted one from column width as a kind of cell padding
		foreach($row->td as $elem) {
			$this->pdf->setX($X);
		
			if ($elem->img==1) {
		
				$vala=t3lib_div::trimexplode(',',$elem->data);
				$img='';
				//$this->cellX=$this->pdf->getX();
				//$this->pdf->Cell($size,$this->height,'',1,0,'L',1);
				//$this->pdf->setX($this->cellX);
				if ($elem->data != '') {
					foreach($vala as $v) {
						$img=PATH_site.($v?$elem->img->dir.'/'.$v:'');
						$imginfo=getimagesize($img);
						if (is_array($imginfo)) {
							$w=$imginfo[0];
							$h=$imginfo[1];
							$this->pdf->Image($img,$this->pdf->getX()+0.5,$this->pdf->getY()+0.5,0, $this->height-1);
							//$this->pdf->setX($this->pdf->getX()+((($this->height-1)/$h)*$w));
							$this->pdf->SetY($Y+0.5+$this->imageHeight-1);
						} else {
							// Empty image we make room for it
							$this->pdf->SetY($Y+0.5+$this->imageHeight-1);
						}
						// By defaullt we only handle first media
						if (!$multipleMedia) break;
					}
				} else {
		
					// Empty image we make room for it
					$this->pdf->SetY($Y+0.5+$this->imageHeight-1);
				}
				//$this->pdf->setX($size+$this->pdf->leftmargin);
			} else {
				$val= strip_tags($elem->data);
				$this->pdf->MultiCell($size,$this->lineHeight,utf8_decode($val),0,'L',0);
			}
		}
		
		//$this->pdf->setXY( ($marginl? 20 : $nbx*$size*9+20), ($marginh ? 35 : $posy+25));
		
		$cptcols++;
		$Y2=$this->pdf->GetY();
		if ($LastY<$Y2) {
			$LastY=$Y2;
		}
		if ($cptcols >= $nbCols)			// Si on arrive au nombre de colonnes indiquee dans le flexform, on passe a une nouvelle ligne d'elements
		{
			//Draw rectangles
			while ($cptcols>0) {
				$cptcols--;
				$rh=$LastY-$Y;
				$this->pdf->Rect($this->pdf->leftmargin+($size*$cptcols), $Y, $size, $rh);
			}
			$Y=$LastY;
			$this->pdf->Ln();					// Nouvelle ligne
			$cptcols = 0;				// Compteur de colonnes remis a 0
			$posy=$this->pdf->getY();			// On recupere la position en Y actuelle pour savoir ou placer les prochaines colonnes
			if ($Y>$this->documentHeight) {
				$Y=$this->pdf->topmargin;
				$this->pdf->addPage();
			}
		
		}*/
		
		ob_clean();
		$name=$caller->metafeeditlib->enleveaccentsetespaces(date("Ymdhms-").$title).'.pdf';
		ob_clean();
		$this->pdf->generatePrintScript($print,$printer,$server);
		$this->pdf->Output($name, 'I');
		die;
	}
		
	// We handle here excel file generation
	function getEXCEL(&$content,&$caller) {
		//error_log(__METHOD__.':'.$content);
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
		$caller->metafeeditlib->getHeader($title, $recherche, $this->conf);
		$markerArray=array();
		$markerArray['###EXPORT_TITLE###']=$title;
		$markerArray['###SEARCH_FILTER###']=str_replace('\'','"',$recherche);
		$markerArray['###EXPORT_TITLE_SIZE###']=strlen($title);
		$markerArray['###SEARCH_FILTER_SIZE###']=2;//strlen($recherche);
		$content=$caller->cObj->substituteMarkerArray($content,$markerArray);
		try {
			//error_log(__METHOD__.':'.str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
			$xml = new SimpleXMLElement(str_replace('</data>',']]></data>',str_replace('<data>','<data><![CDATA[',str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))))));
		} catch (Exception $e) {
			echo str_replace("'","\'",str_replace('&euro;','E',str_replace('&nbsp;',' ',$caller->metafeeditlib->T3StripComments($content))));
			die( 'Caught exception: '.  $e->getMessage());
		};
		$taille = 0;
		$fields = explode(',', $this->conf['list.']['show_fields']);
		
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
		if ($taille <200) $this->documentOrientation=PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT;	// portrait
		else $this->documentOrientation=PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE;// paysage
		// We handle the header here 
		//
		
		if (strlen($title)>31) $title=substr($title,0,31);
		$user = '';
		$user = $GLOBALS['TSFE']->fe_user->user[username];

		if ($this->conf['inputvar.']['sortLetter']) $tri = '  tri par la lettre: '.$this->conf['inputvar.']['sortLetter'];

		$this->height = $this->confTS[$this->pluginId.'.']['list.']['height']?$this->confTS[$this->pluginId.'.']['list.']['height']:($this->confTS['default.']['list.']['height']?$this->confTS['default.']['list.']['height']:30); // hauteur de la ligne pdf



		// set headers to redirect output to client browser as a file download
		$objPHPExcel=new PHPExcel();
		
		// Set value binder
		//PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

		
		//Set Print properties

		$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader($title); //Set print header
		$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation($this->documentOrientation); //set printing orientation
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
				//$this->pdf->setFillColor(200,200,200);
				$bgcolor="FEFEFEFE";

			}
			$alt++;
			$nbcols=count($row->td);
			if ($row->gb) {
				//$this->pdf->SetFont('Helvetica', 'B', 9);
			} else {
				//$this->pdf->SetFont('Helvetica', '', 9);
			}				
			$objPHPExcel->getActiveSheet()->getRowDimension($r)->setRowHeight($this->height);
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
								$objDrawing->setHeight($this->height);
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
						//$this->pdf->setX($size+$this->pdf->leftmargin);
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
					 		//$this->pdf->setX($this->cellX+$size);
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
					/*$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFill()->getStartColor()->setARGB("99999999");
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getBorders()->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
					*/
					$objPHPExcel->getActiveSheet()->getStyle($c.$r)->getFont()->setBold(true);
					$val = strip_tags($col->data);
					$objPHPExcel->getActiveSheet()->mergeCells($c.$r.':Z'.$r);
					$objPHPExcel->getActiveSheet()->getCell($c.$r)->setValueExplicit("".$val, PHPExcel_Cell_DataType::TYPE_STRING);
					//$maxwidth[$c]=strlen("".$val)*10>$maxwidth[$c]?strlen("".$val)*10:$maxwidth[$c];
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
		else $font = 'Helvetica';
	}
	
	/**
	 * Reduce font size for text show on one single line
	 * @param int $widthBox
	 * @param string $text
	 * @param int $fontSize
	 */
	protected function TextFit2Width($widthBox, $text, $fontSize) {
		//$widthBox = floor($widthBox);
		while ($this->pdf->NbLines($widthBox, $text)>1 && $fontSize>1) {
			//error_log(__METHOD__.":$wText, $widthBox,$fontSize");
			$fontSize=$fontSize-1;	
			$this->pdf->SetFontSize($fontSize);
		}
		//error_log(__METHOD__.":2 $wText, $widthBox,$fontSize");
	}
	/**
	 * Reduce font size for text show in one box
	 * @param int $widthBox
	 * @param string $text
	 * @param int $fontSize
	 */
	protected function TextFit2Box($widthBox,$heightBox, $lineHeight, $text, $fontSize) {
		//$widthBox = floor($widthBox);
		$rowHeight= $this->pdf->NbLines($widthBox, $text)*$lineHeight;
		while ($rowHeight > $heightBox && $fontSize>1) {
			//error_log(__METHOD__.":$rowHeight, $heightBox,$fontSize");
			$fontSize=$fontSize-1;
			
			$this->pdf->SetFontSize($fontSize);
			$rowHeight= $this->pdf->NbLines($widthBox, $text)*$lineHeight;
		}
		//error_log(__METHOD__.":2 $rowHeight, $heightBox,$fontSize");
	}
	

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_export.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_export.php']);
}

?>