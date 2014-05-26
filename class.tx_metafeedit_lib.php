<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Christophe Balisky <christophe@balisky.org>
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
* This is a API for crating and editing records in the frontend.
* The API is built on top of fe_adminLib.
*
* @author	 Christophe Balisky <christophe@balisky.org>
*/

// Necessary includes

class tx_metafeedit_lib implements t3lib_singleton {
	var $st = 0;
	var $et = 0;
	var $RTEObj;
	var $cObj;
	var $cache=0;
	var $prefixId = 'tx_metafeedit';
	var $freeCap;
	var $feadminlib;
	var $returnvalue;
	var $t3lib_TCEforms;
	var $langHandler=null;
	var $extKey = 'meta_feedit';

	/**
	* debug
	*
	* @param	[type]		$title: ...
	* @param	[type]		$content: ...
	* @param	[type]		$DEBUG: ...
	* @return	[type]		...
	*/
	
 	function debug($title,$content,&$DEBUG) {
 		if (is_array($content)) $content=Tx_MetaFeedit_Lib_ViewArray::viewArray($content);
 		if (is_object($content)) {
 			//$content='';
 			ob_start();
 			print_r($content);
			$content ='<pre>'. ob_get_contents().'</pre>';
			ob_clean();
		}
		$DEBUG.=($title?"<br/><hr/><h3>$title</h3><br/>":'').$content;
	}

	/**
	* iniatialising Lib Object
	*
	* @return	[type]		...
	*/
	function tx_metafeedit_lib() {
	
		$this->cObj = &$GLOBALS['TSFE']->cObj;
		if (t3lib_extMgm::isLoaded('sr_freecap')) { //@todo  addd check if captcha requested in flexform
			require_once(t3lib_extMgm::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php');
			$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
		}
		if (!is_object($GLOBALS['BE_USER'])) $GLOBALS['BE_USER'] = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth');
		$this->t3lib_TCEforms = t3lib_div::makeInstance('t3lib_TCEforms');
		if (t3lib_extmgm::isLoaded('ard_mcm') ) {
			//error_log(__METHOD__.":WE create languageHandler");
			$this->langHandler=t3lib_div::makeInstance('Tx_ArdMcm_Core_LanguageHandler');
		} else {
			$this->langHandler=null;
		}
		$this->starttime();
	}

	/**
	* getJSAfter
	*
	* @param	[type]		$feadminlib: ...
	* @param	[type]		$conf: ...
	* @return	[type]		...
	*/
	 /*
	function getJSAfter(&$feadminlib,&$conf) {
		$JS= $conf['noJS']?'':
		(count($conf['caller_additionalJS_post'])?'<script rel="yo11" type="text/javascript">'.implode(chr(10), $conf['caller_additionalJS_post']).'</script>'.chr(10):'').
		(count($conf['caller_additionalJS_end'])?'<script rel="yo9" type="text/javascript">'.implode(chr(10), $conf['caller_additionalJS_end']).'</script>':'');
		return $JS.(count($conf['additionalJS_post'])?'<script rel="yo6" type="text/javascript">'.implode(chr(10), $conf['additionalJS_post']).'</script>'.chr(10):'').(count($conf['additionalJS_end'])?'<script rel="yo5" type="text/javascript">'.implode(chr(10), $conf['additionalJS_end']).'</script>':'');
	}*/
	/**
	* inline2TempFile : compatibility version since version 4.3 of typo3 has changed 
	* in new version only gives uri of temp file
	* in old version gave script tag.
	* @param	string		$script: js our css code
	* @param	string		$ext: 'js' or 'cs'
	* @return	string		...
	*/
	function inline2TempFile($script,$ext) {
		$ret=TSpagegen::inline2TempFile($script,$ext);
		//TODO better test on typo3 version
		if (!strpos($ret,'</script>')) $ret='<script type="text/javascript" src="/'.$ret.'"></script>';
		return $ret;
	}
	
	function T3StripComments($document){
		$search = array('@<script[^>]*?>.*?</script>@si',  // Strip out javascript
			   '@<style[^>]*?>.*?</style>@siU',	// Strip style tags properly
			   '@<![\s\S]*?--[ \t\n\r]*>@',
			   '@<!DOCTYPE[\s\S]*?[ \t\n\r]*>@'		 // Strip multi-line comments including CDATA
				);
		$text = preg_replace($search, '', $document);
		return $text;
		}

		// Performance audit functions
		/**
		* Gets time for avaluating ellapsed time
		*
		* @return time[float]:  ...
		*/
		
		function getmicrotime() {
			list($usec, $sec) = explode(" ", microtime());
			return ((float)$usec + (float)$sec);
		}

		/**
		* Iniatilizes starttime for performance audit.
		*
		* does not return anything
		*/
		function starttime() {
			$this->st = $this->getmicrotime();
		}

		/**
		* [Describe function...]
		*
		* @return [type]  ...
		*/
		function displaytime() {
			$this->et = $this->getmicrotime();
			return round(($this->et - $this->st), 3);
		}

		/**
		* [Describe function...]
		*
		* @param [type]  $cmd: ...
		* @param [type]  $conf: ...
		* @return [type]  ...
		*/
		function getOverrideFields($cmd, &$conf) {
			$ret = array();
			if (is_array($conf[$cmd.'.']['overrideValues.'])) {
				reset($conf[$cmd.'.']['overrideValues.']);
				foreach($conf[$cmd.'.']['overrideValues.'] as $theField=>$theValue) {
					$ret[] = $theField;
				}
			}
			//TODO  handle user function on overrides ?
			return $ret;
		}

		/**
		* getOverrideValue
		*
		* @param [type]  $fN: ...
		* @param [type]  $cmd: ...
		* @param [type]  $conf: ...
		* @param [type]  $cObj: ...
		* @return [type]  ...
		*/
		
		function getOverrideValue($fN, $cmd, &$conf, &$cObj) {
			$data = "";
			if (is_array($conf[$cmd.'.']['overrideValues.'])) {
				$theValue = $conf[$cmd.'.']['overrideValues.'][$fN];
				$data = $theValue;	
				if (strpos($theValue, ':') > 0) {
					$data = $this->getData($theValue, 0, $cObj);
					return $data;
				}
				elseif (strpos($theValue, '.') > 0) {
					//here we handle special values ...
					$fieldArr = explode('.', $theValue);
					$data = "";
					$c = count($fieldArr);
					if ($c > 1) {
						$data = $this->getData($fieldArr[0], 0, $cObj);
						$i = 1;
						while ($i <= $c) {
							if (is_object($data)) {
								$data = get_object_vars($data);
							}
							if (is_array($data)) {
								$key = $fieldArr[$i];
								$data = $data[$fieldArr[$i]];
							}
							$i++;
						}
					}
					return $data;
				} else {
					if (strpos($theValue, ':') > 0) $data = $this->getData($theValue, 0, $cObj);
					return $data;
				}
			}
			return $data;
		}

		/**
		* getData Function
		*
		* @param string  $val: value of getData path
		* @param [type]  $P: ...
		* @param object  $cObj: ...
		* @return mixed  getData's evaluated path value
		*/
		
		function getData($val, $P, &$cObj) {
			//example of val : links=text:<LINK [-]TS_var:metaFE*tags*METAFEEDIT_PAGE_blog [-] text:>[-]TS_var:metaFE*tags*METAFEEDIT_LIEN_VOIR_news [-] text:</LINK>

			//on regarde si on a du multi bloc en splittant avec [-]
			$parts = explode('[-]', $val);
			//si on a du multipart
			if (count($parts) > 1) {
				//on appel pour toutes les parties en recursif
				foreach($parts as $part) {

					$ret .= tx_metafeedit_lib::getData( $part, $P, $cObj);
				}
				return $ret;
			} else {
				//si pas de multi part

				$parts = explode(':', $val, 2);
				$key = trim($parts[1]);
				if ((string)$key != '') {
					switch(strtolower(trim($parts[0]))) {
						//EXT:meta_booking/class.tx_metabooking_userfunc.php:&tx_metabooking_userfunc->PaymentEntityAfterWhereNoAnd
						//dans le cas de ts_var (recuperation de variable definie dans le setup evalue a partie des constants->pas directement les constantes)
						case 'ext':
						  $arr=array();
						  $arr['parentObj']=$this;
						  $this->returnvalue='';
							t3lib_div::callUserFunction($val,$arr,$this);
							return $this->returnvalue;
							break;
						
						case 'ts_var':
							//on charge le parseur de TS
							$cF = t3lib_div::makeInstance('t3lib_TSparser');
							//petite manip temporaire pour passer le decoupage par les points, on remplace les * par des points dont on a besoin
							$key = str_replace('*', '.', $key);

							//on demande au parseur de recupere la chaine TS desiree
							$var_tmp_val = $cF->getVal($key, $GLOBALS['TSFE']->tmpl->setup);
							return $var_tmp_val[0];
							break;

						case 'feuser':
							return $GLOBALS['TSFE']->fe_user->user[$key];
							break;
							
						case 'dbf':
							$selectParts = t3lib_div::trimExplode('|', $key);
							//$db_rec = $GLOBALS['TSFE']->sys_page->getRawRecord($selectParts[0],$selectParts[1]);
							// On evalue le champ id
	
							$id = $this->getData($selectParts[2], $P, $cObj);
	
							// only one cv for the moment
	
							$db_rec = $GLOBALS['TSFE']->sys_page->getRecordsByField($selectParts[0], $selectParts[1], $id, $selectParts[3], $selectParts[4], $selectParts[5], $selectParts[6]);
							if (is_array($db_rec) && $id) $retVal = $db_rec[0][$selectParts[7]];
							return $retVal;
							break;
						//We get data from session array
						//si type session alors on retourne la clef trouv� dans la session
						case 'ses':
							list($pluginId, $field) = t3lib_div::trimExplode('|', $key);
							if ($field=='') {
								$field = $pluginId;
								$pluginId = $this->conf['pluginId'];
							}
							$defaultArr = $GLOBALS["TSFE"]->fe_user->getKey('ses', $pluginId);
							return $defaultArr[$field];
							break;
						//si type texte alors on retourne la cle sans modif
						case 'text':
							return $key. ' ';

						default:
							return $cObj->getData($val, $cObj->data);
							break;
					}
				} else {
					return $val;
			  }
			}
		}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$path: ...
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function keepVars($path,$arr) {
		if (is_array($arr)) {
			$res='';
			foreach($arr as $key=>$val) {
				$res.=$this->keepVars($path."[$key]",$val);
			}
			return $res;
		} else {

			return $this->is_extent($arr)?$path."=".htmlspecialchars($arr):'';
		}
	}

	// LINKS
	/**
	* makeBackURLTypoLink : makes Back button URL Link. 
	* If in List Mode we go back to BackPID if specified, to passed Link
	* If in Edit, Preview or Create mode we should go ack to list mode first if configuration allows it, otherwise to referer
	* If in Edit Preview or Create preview, back should bring us to the Edit or Create Screen if possible, to referer otherwise
	* If in Save confirmation or Create confirmation or Delete Confirmation, back should bring us to the calling Edit or List Screen if possible, to referer otherwise
	* @param	array		$conf: configuration array
	* @param	string		$referer: default link
	* @return	string		backURL link
	*/
	function makeBackURLTypoLink(&$conf,&$referer) {
		switch ($conf['inputvar.']['cmd']) {
			case 'create' :	
				if ($conf['defaultCmd']=='create') { 					
					$referer[$conf['pageType']]=$this->makeFormTypoLink($conf,'&BACK['.$conf[pluginId].']=1&cmd['.$conf[pluginId].']=create',false);
					break;
				}
				//if($conf['create'] && !$conf['disableCreate'] && !$conf['create.']['hide']) {
				//$backurl=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$conf['GLOBALPARAMS']);
			case 'delete' :			
				//if($conf['create'] && !$conf['disableCreate'] && !$conf['create.']['hide']) {
				//$backurl=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$conf['GLOBALPARAMS']);
			case 'edit' :
				/*if (!$conf['disableDelete'] && !$conf['disableEditDelete'] && !$conf['delete.']['hide']) {
					$backurl=$this->makeFormTypoLink($conf,"&rU[$pluginId]=".$conf['recUid']);
				}
				if (($conf['disableEdit'] && $conf['edit.']['preview']) || !$conf['disableEdit'] ) {
					$backurl=$this->makeFormTypoLink($conf,"");
				};
				if ($conf['blog.']['showComments'] ) {
				//$backurl=$this->makeFormTypoLink($conf,"");
				}
				if ($conf['TCAN'][$conf['table']]['ctrl']['sortby'] && !$conf['disableEdit']) {
				//$backurl=$this->makeFormTypoLink($conf,"&rU[$pluginId]=".$conf['recUid']);
				}*/
				$referer[$conf['pageType']]=$this->makeFormTypoLink($conf,'&BACK['.$conf[pluginId].']=1&cmd['.$conf[pluginId].']=list',false);
				break;
			case 'list':
			default :
				if ($conf['backPagePid'] ) {
					$tlconf['parameter']=$conf['backPagePid'];
					if ($conf['no_cache'] || $conf['cacheMode']==0) $tlconf['no_cache']=1;
					$tlconf['useCacheHash']=1;
					$referer[$conf['pageType']]=$this->cObj->typoLink_URL($tlconf);
				} else {
					if (!$referer[$conf['pageType']]) {
						$referer[$conf['pageType']]="javascript:history.back();";
					}
				}
		}
		return $referer;
	}

	/**
	 * makeFormTypoLink
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$params: ...
	 * @return	[type]		...
	 */
		function makeFormTypoLink(&$conf,$params,$hsc=true) {
			$formid=$GLOBALS['TSFE']->id;
			if ($conf['createPid']) {$formid=$conf['createPid']; }
			if ($conf['editPid'] && $conf['inputvar.']['cmd']=='edit') { $formid=$conf['editPid']; }
			$prma=$param;
			$tlconf['parameter']=$formid;
			if ($conf['no_cache'] || $conf['cacheMode']==0) $tlconf['no_cache']=1;
			$tlconf['useCacheHash']=1;
			$tlconf['additionalParams']=$params;
			$pl=$this->cObj->typoLink_URL($tlconf);
			if (!strpos($pl,'?')) $pl.='?';
			$pl=$hsc?$this->hsc($conf,$pl):$pl;
			return $pl;
		}

		function makeErrorMarker(&$conf,$errorMsg) {
			return '<div'.$conf['caller']->pi_classParam('error-cnt').'>'.$errorMsg.'</div>';
		}

		Function enleveaccents($chaine) {
			$string = strtr($chaine, "àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ", "aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY");
			return $string;
		}

		Function enleveaccentsetespaces($chaine) {
			$string = $this->enleveaccents($chaine);
			$string = str_replace(' ', '', $string);
			return $string;
		}

		/**
		* [Describe function...]
		*
		* @param [type]  $player: ...
		* @param [type]  $file: ...
		* @return [type]  ...
		*/
		function showMediaPlayer($player, $file,&$conf) {
			switch($player) {
				case 'flvplayer':
					if (t3lib_extmgm::isLoaded('flvplayer')) $ret = $this->buildFlvPlayerFlashCode($file,$conf);
					return $ret;
				break;
				case 'fe_mp3player':
					if (t3lib_extmgm::isLoaded('fe_mp3player')) $ret = $this->buildMp3PlayerFlashCode($file,$conf);
					return $ret;
					break;
				case 'wildside_flash_mp3_player':
					if (t3lib_extmgm::isLoaded('wildside_flash_mp3_player')) $ret = $this->buildMp3PlayerFlashCode2($file);
					return $ret;
					break;
				case 'image':
					$imgA['file.']['maxW'] = 300;
					$imgA['file.']['maxH'] = 200;
					if ($conf[$conf['cmdmode'].'.']['mediaImgConf.'] || $conf['mediaImgConf.']) $imgA=$conf[$conf['cmdmode'].'.']['mediaImgConf.']?$conf[$conf['cmdmode'].'.']['mediaImgConf.']:$conf['mediaImgConf.'];
					$imgA['file'] = $file;
					$imgA['altText']=$file;
					$imgA['titleText']=trim(basename($file));
 					return $this->cObj->IMAGE($imgA);
				default :
					break;
			}

		}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$file: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
		function buildMp3PlayerFlashCode($file,&$conf) {

			// Add movie param to TS
			$conf['swfParams.']['movie'] = $swfPath;
			$conf['swfParams.']['swliveconnect']='false';
			$conf['swfParams.']['quality']='best';
			$conf['swfParams.']['menu']='false';
			$conf['swfParams.']['scale'] = 'showall';
			$conf['swfParams.']['loop'] = 'false';
			$conf['swfParams.']['salign'] = '1';
			$conf['swfParams.']['wmode'] = 'window';
			$conf['height'] = 300;
			$conf['width'] = 300;
			$conf['swfParams.']['bgcolor'] = "#B4B5B8";
			$conf['playerParams.']['useSkin']='default';
			$conf['playerParams.']['gskinColor']='1';
			$conf['playerParams.']['showDisplay']='1';
			$conf['playerParams.']['showPlaylist']='1';
			$conf['playerParams.']['autoStart']='1';
			$conf['version']=6;
			$conf['xmlPageId']=9001;

				// Create XML file location
				$xmlFile = 'index.php?id=' . $GLOBALS['TSFE']->id . '&type=' . $conf['xmlPageId'] . '&mediafile=' . $file . '&autoStart=' . $conf['playerParams.']['autoStart'] . '&showPlaylist=' . $conf['playerParams.']['showPlaylist'] . '&showDisplay=' . $conf['playerParams.']['showDisplay'] . '&gskinColor=' . $conf['playerParams.']['gskinColor'];
				// Add FlashVars param to TS
				$conf['swfParams.']['FlashVars'] = 'playlist=' . urlencode($xmlFile);

				// Add movie param to TS
				$conf['swfParams.']['movie'] = $swfPath;

				// Storage
				$htmlCode = array();

				// Replacement code
				//$noFlash = $this->createLinks();

				// Flash code
				$htmlCode[] = '
					<!-- URL\'s used in the movie-->
					<!-- text used in the movie-->
					<script type="text/javascript" language="Javascript" charset="iso-8859-1">
						<!--
						var MM_contentVersion = ' . $conf['version'] . ';
						var plugin = (navigator.mimeTypes && navigator.mimeTypes[\'application/x-shockwave-flash\']) ? navigator.mimeTypes[\'application/x-shockwave-flash\'].enabledPlugin : 0;
						if (plugin) {
							var words = navigator.plugins[\'Shockwave Flash\'].description.split(\' \');
							for (i = 0; i < words.length; i++) {
								if (isNaN(parseInt(words[i]))) {
									continue;
								}
								var MM_PluginVersion = words[i];
							}
							var MM_FlashCanPlay = MM_PluginVersion >= MM_contentVersion;
						}
						else if (navigator.userAgent && navigator.userAgent.indexOf(\'MSIE\') >=0 && (navigator.appVersion.indexOf(\'Win\') != -1)) {
							document.write(\'<script type="text\/vbscript" language="VBScript" charset="iso-8859-1">\n\');
							document.write(\'on error resume next \n\');
							document.write(\'MM_FlashCanPlay = (IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash." & MM_contentVersion)))\n\');
							document.write(\'<\/script>\n\');
						}
						if (MM_FlashCanPlay) {
							document.write(\'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http:\/\/download.macromedia.com\/pub\/shockwave\/cabs\/flash\/swflash.cab#version=' . $conf['version'] . ',0,0,0" id="' . $this->prefixId . '" width="' . $conf['width'] . '" height="' . $conf['height'] . '" align="center">\n\');
							' . $this->writeFlashObjectParams($conf) . '
							document.write(\'<embed src="' . $swfPath . '" FlashVars="' . $conf['swfParams.']['FlashVars'] . '" swliveconnect="' . $conf['swfParams.']['swliveconnect'] . '" loop="' . $conf['swfParams.']['loop'] . '" menu="' . $conf['swfParams.']['menu'] . '" quality="' . $conf['swfParams.']['quality'] . '" scale="' . $conf['swfParams.']['scale'] . '" salign="' . $conf['swfParams.']['salign'] . '" wmode="' . $conf['swfParams.']['wmode'] . '" bgcolor="' . $conf['swfParams.']['bgcolor'] . '" width="' . $conf['width'] . '" height="' . $conf['height'] . '" name="' . $this->prefixId . '" align="top" type="application\/x-shockwave-flash" pluginspage="http:\/\/www.macromedia.com\/go\/getflashplayer">\n\');
							document.write(\'<\/embed>\n\');
							document.write(\'<\/object>\n\');
						}
						else {
							document.write(\'' . $noFlash . '\');
						}
					//-->
					</script>';

				// Return content
				return implode(chr(10),$htmlCode);
			}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
		 	function writeFlashObjectParams(&$conf) {

			// Storage
			$params = array();

			// Build HTML <param> tags from TS setup
			foreach($conf['swfParams.'] as $name => $value) {
				$params[] = 'document.write(\'<param name="' . $name . '" value="' . $value . '">\');';
			}
			// Return tags
			return implode(chr(10),$params);
		}

	/**
	 * buildMp3PlayerFlashCode2
	 *
	 * @param	[type]		$file: ...
	 * @return	[type]		...
	 */
	function buildMp3PlayerFlashCode2($file) {
		return '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0" width="100" height="20">
  <param name="movie" value="'.t3lib_extMgm::siteRelPath("wildside_flash_mp3_player").'pi1/mp3_stor.swf?url='. rawurlencode($file).'">
  <param name="quality" value="high">
  <embed src="'.t3lib_extMgm::siteRelPath("wildside_flash_mp3_player").'pi1/mp3_stor.swf?url='. rawurlencode($file).'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="100" height="20"></embed></object>';
	}

		// FLV PLAYER ...

	function buildFlvPlayerFlashCode($file,&$conf) {

		// Creating valid pathes for the FLV player
		$swfPath = str_replace(PATH_site, '', t3lib_div::getFileAbsFileName('EXT:flvplayer/pi1/flvplayer.swf'));

		// Autostart (WARNING - Settings looks inverted in the Flash file)
		$conf['playerParams.']['autoStart'] = 'true';
		$autoStart = ($conf['playerParams.']['autoStart']) ? '' :
		 '&autoStart=true';
		$conf['playerParams.']['fullScreen'] = 'true';
		// Allow fullscreen mode
		$fullScreen = ($conf['playerParams.']['fullScreen']) ? '&amp;showFs=true' :
		 '';

		// File path
		$filePath = str_replace(PATH_site, '/', t3lib_div::getFileAbsFileName($file));


		// Add FlashVars param to TS
		$conf['swfParams.']['FlashVars'] = 'file=' . $filePath . $autoStart . $fullScreen;

		// Add movie param to TS
		$conf['swfParams.']['movie'] = $swfPath;
		//$conf['swfParams.']['swliveconnect'];
		//$conf['swfParams.']['loop'];
		//$conf['swfParams.']['menu'];
		$conf['swfParams.']['scale'] = 'true';
		$conf['swfParams.']['loop'] = 'true';
		$conf['height'] = 300;
		$conf['width'] = 400;
		$conf['swfParams.']['bgcolor'] = "#231D1F";
		$conf['version']=6;
		// Storage
		$htmlCode = array();

		// Flash code
		/*$htmlCode[] = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=' . $conf['version'] . ',0,0,0" id="' . $this->prefixId . '" width="' . $conf['width'] . '" height="' . $conf['height'] . '" align="center">';
		$htmlCode[] = $this->writePlayerFlashObjectParams($conf);
		$htmlCode[] = '<embed src="' . $swfPath . '" FlashVars="' . $conf['swfParams.']['FlashVars'] . '" swliveconnect="' . $conf['swfParams.']['swliveconnect'] . '" loop="' . $conf['swfParams.']['loop'] . '" menu="' . $conf['swfParams.']['menu'] . '" quality="' . $conf['swfParams.']['quality'] . '" scale="' . $conf['swfParams.']['scale'] . '" bgcolor="' . $conf['swfParams.']['bgcolor'] . '" width="' . $conf['width'] . '" height="' . $conf['height'] . '" name="' . $this->prefixId . '" align="top" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">';
		$htmlCode[] = '</embed>';
		$htmlCode[] = '</object>';*/
		$htmlCode[] = '
							<!-- URL\'s used in the movie-->
				<!-- text used in the movie-->
				<script type="text/javascript" language="Javascript" charset="iso-8859-1">
					<!--
					var MM_contentVersion = ' . $conf['version'] . ';
					var plugin = (navigator.mimeTypes && navigator.mimeTypes[\'application/x-shockwave-flash\']) ? navigator.mimeTypes[\'application/x-shockwave-flash\'].enabledPlugin : 0;
					if (plugin) {
						var words = navigator.plugins[\'Shockwave Flash\'].description.split(\' \');
						for (i = 0; i < words.length; i++) {
							if (isNaN(parseInt(words[i]))) {
								continue;
							}
							var MM_PluginVersion = words[i];
						}
						var MM_FlashCanPlay = MM_PluginVersion >= MM_contentVersion;
					}
					else if (navigator.userAgent && navigator.userAgent.indexOf(\'MSIE\') >=0 && (navigator.appVersion.indexOf(\'Win\') != -1)) {
						document.write(\'<script type="text\/vbscript" language="VBScript" charset="iso-8859-1">\n\');
						document.write(\'on error resume next \n\');
						document.write(\'MM_FlashCanPlay = (IsObject(CreateObject("ShockwaveFlash.ShockwaveFlash." & MM_contentVersion)))\n\');
						document.write(\'<\/script>\n\');
					}
					if (MM_FlashCanPlay) {
						document.write(\'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http:\/\/download.macromedia.com\/pub\/shockwave\/cabs\/flash\/swflash.cab#version=' . $conf['version'] . ',0,0,0" id="' . $this->prefixId . '" width="' . $conf['width'] . '" height="' . $conf['height'] . '" align="center">\n\');
						' . $this->writePlayerFlashObjectParams($conf) . '
						document.write(\'<embed src="' . $swfPath . '" FlashVars="' . $conf['swfParams.']['FlashVars'] . '" swliveconnect="' . $conf['swfParams.']['swliveconnect'] . '" loop="' . $conf['swfParams.']['loop'] . '" menu="' . $conf['swfParams.']['menu'] . '" quality="' . $conf['swfParams.']['quality'] . '" scale="' . $conf['swfParams.']['scale'] . '" salign="' . $conf['swfParams.']['salign'] . '" wmode="' . $conf['swfParams.']['wmode'] . '" bgcolor="' . $conf['swfParams.']['bgcolor'] . '" width="' . $conf['width'] . '" height="' . $conf['height'] . '" name="' . $this->prefixId . '" align="top" type="application\/x-shockwave-flash" pluginspage="http:\/\/www.macromedia.com\/go\/getflashplayer">\n\');
						document.write(\'<\/embed>\n\');
						document.write(\'<\/object>\n\');
					}
					else {
						document.write(\'' . $noFlash . '\');
					}
				//-->
				</script>';




		// Return content
		return implode(chr(10), $htmlCode);
	}
	
	/* Group By Field Break Footer ...
	*/
	function getGroupByFooterSums(&$conf,$prefix,&$GBMarkerArray,$fN,&$sql,&$row,$end,&$DEBUG) {
		$sumFields = '';
		$sumSQLFields = '';
		$somme = 0;
		$sumFields = explode(',', $conf['list.']['sumFields']);
		if ($conf['list.']['sumFields']) {
			foreach($sumFields as $fieldName) {
				if ($conf['list.']['sqlcalcfields.'][$fieldName]) {
					$calcField=$conf['list.']['sqlcalcfields.'][$fieldName]; // TO BE IMPROVED
					if ($calcField) {				
						if (preg_match("/min\(|max\(|count\(|sum\(|avg\(/i",$calcField)) {
							// we test for group by functions
							$sumSQLFields.=$sumSQLFields?",$calcField as 'sum_$fieldName'":"$calcField as 'sum_$fieldName'";
						} else {
							$sumSQLFields.=$sumSQLFields?",sum($calcField) as 'sum_$fieldName'":"sum($calcField) as 'sum_$fieldName'";
						}
					}
				}	
				else
				{
					// CMD - we must handle here sum of relation table (actualy fieldName is like : foreignField.field in place of foreignTable_foreignField.field)
					$fieldAlias=$this->makeFieldAlias($table,$fieldName,$conf);
					$sumSQLFields.=$sumSQLFields?",sum($fieldAlias) as 'sum_$fieldName'":"sum($fieldAlias) as 'sum_$fieldName'";

				}				
			}
		}
		$sumSQLFields.=$sumSQLFields?', count(*) as metafeeditnbelts':' count(*) as metafeeditnbelts';
		if ($sql['groupBy']) $sumSQLFields.=','.$conf['table'].'.*';
		$WHERE=$sql['where'];
		$fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFieldBreaks']);
		$i=0;
		//$GBA=t3lib_div::trimexplode(',',$sql['gbFields']);
		$GROUPBY=$sql['groupBy'];
		$endgb=0;
		foreach($fNA as $fNe) {
			$fN2=t3lib_div::trimexplode(':',$fNe);
			$fNi=$fN2[0];
			if($fN2[2] || $endgb) {
				if (!$endgb) {
					$GROUPBY=$GROUPBY?$GROUPBY.','.$fN2[0]:$fN2[0];
					$HAVING.=$HAVING?" and $fN2[0]='".$row[$fNi]."'":" HAVING $fN2[0]='".$row[$fNi]."'";
				}
			} else {
				if (strpos($fNi,'.')===false && $row[$fNi]) {
					//$table = $this->getForeignTableFromField($fNi, $conf,'',&$sql);
					$WHERE.=" and $conf[table].$fNi='".$row[$fNi]."'";
					$GROUPBY=$GROUPBY?$GROUPBY.','.$conf[table].'.'.$fNi:$conf[table].'.'.$fNi;
					$HAVING.=$HAVING?" and $fN2[0]='".$row[$fNi]."'":" HAVING $fN2[0]='".$row[$fNi]."'";
				} else {
					$WHERE.=$this->makeSQLJoinWhere($fNi,$conf,$row[$fNi]);
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
			if (is_array($conf['list.']['sqlcalcfields.'])) foreach ($conf['list.']['sqlcalcfields.'] as $fn=>$calcField) {
					$sumSQLFields.=$sumSQLFields?",$calcField as '$fn'":"$calcField as '$fn'";
			}

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
			$fns=t3lib_div::trimexplode('.',$fieldName);
			$fnn=array_pop($fns);
			$std=$conf[$conf['cmdmode'].'.']['stdWrap.']?$conf[$conf['cmdmode'].'.']['stdWrap.']:$conf['stdWrap.'];
			if ($std[$fnn.'.'] || $std[$table.'.'][$fnn.'.']) {
				if ($std[$fnn.'.']) $stdConf = $std[$fnn.'.'];
				if ($std[$table.'.'][$fnn.'.']) $stdConf = $std[$table.'.'][$fnn.'.'];
				$value['sum_'.$fieldName]=$this->cObj->stdWrap($value['sum_'.$fieldName], $stdConf);
			}
	
			$GBMarkerArray["###".$prefix."_".$fN."_FIELD_$fieldName###"]= $value['sum_'.$fieldName];
		 //$i++;
		}
			
		$GBMarkerArray["###".$prefix."_".$fN."_FIELD_metafeeditnbelts###"]= $value['metafeeditnbelts'];
			
		return true;
	}



		/**
		* Returns param tags
		*
		* This function creates a param tag for each parameter specified in the
		* setup field.
		*
		* @return A  param tag for each parameter.
		*/
		function writePlayerFlashObjectParams(&$conf) {

			// Storage
			$params = array();

			// Build HTML <param> tags from TS setup
			foreach($conf['swfParams.'] as $name => $value) {
				//$params[] = '<param name="' . $name . '" value="' . $value . '" />';
				$params[] ='document.write(\'<param name="' . $name . '" value="' . $value . '">\');';
			}

			// Return tags
			return implode(chr(10), $params);
		}


			/**
 * Will render TypoScript cObjects (configured in $this->conf['cObjects.']) and add their content to keys in a markerArray, either the array passed to the function or the internal one ($this->markerArray) if the input $markerArray is not set.
 *
 * @param	string		The current template code string. Is used to check if the marker string is found and if not, the content object is not rendered!
 * @param	array		An alternative data record array (if empty then $this->dataArr is used)
 * @param	mixed		An alternative markerArray to fill in (instead of $this->markerArray). If you want to set the cobjects in the internal $this->markerArray, then just set this to non-array value.
 * @param	string		Optional prefix to set for the marker strings.
 * @param	[type]		$rmarkerArray: ...
 * @param	[type]		$specialPrefix: ...
 * @return	array		The processed $markerArray (if given).
 */
	function setCObjects(&$conf,&$markerArray,$templateCode,$currentArr=array(),$rmarkerArray='',$specialPrefix='')	{
		if (is_array($conf['cObjects.']))	{
			reset($conf['cObjects.']);

			while(list($theKey,$theConf)=each($conf['cObjects.']))	{
				if (strpos($theKey,'.')===false)	{
					if (strpos($templateCode,'###'.$specialPrefix.'CE_'.$theKey.'###')!==false)	{
						$cObjCode = $this->cObj->cObjGetSingle($conf['cObjects.'][$theKey], $conf['cObjects.'][$theKey.'.'], 'cObjects.'.$theKey);

						if (!is_array($rmarkerArray))	{
							$markerArray['###'.$specialPrefix.'CE_'.$theKey.'###'] = $cObjCode;
						} else {
							$rmarkerArray['###'.$specialPrefix.'CE_'.$theKey.'###'] = $cObjCode;
						}
					}
					if (strpos($templateCode,'###'.$specialPrefix.'PCE_'.$theKey.'###')!==false)	{
						$local_cObj =t3lib_div::makeInstance('tslib_cObj');
						$local_cObj->start(count($currentArr)?$currentArr:$conf['dataArr'],$conf['table']);
						$cObjCode = $local_cObj->cObjGetSingle($conf['cObjects.'][$theKey], $conf['cObjects.'][$theKey.'.'], 'cObjects.'.$theKey);

						if (!is_array($rmarkerArray))	{
							$markerArray['###'.$specialPrefix.'PCE_'.$theKey.'###'] = $cObjCode;
						} else {
							$rmarkerArray['###'.$specialPrefix.'PCE_'.$theKey.'###'] = $cObjCode;
						}
					}
				}
			}
		}
		return $rmarkerArray;
	}

	/**
	* Returns template subpart HTML code for the key given
	*
	* @param	string		Subpart marker to return subpart for.
	* @param	array		Optional data record array. If set, then all fields herein will also be substituted if found as markers in the template
	* @param	[type]		$key: ...
	* @param	[type]		$r: ...
	* @return	string		The subpart with all markers found in current $this->markerArray substituted.
	* @see tslib_cObj::fillInMarkerArray()
	*/
 
	function getPlainTemplate(&$conf,&$markerArray,$key,$r='')	{
		if ($conf['debug'])	debug('getPlainTemplate(): '.$key,1);
		$templateCode = $this->cObj->getSubpart($conf['templateContent'], $key);
		$this->setCObjects($conf,$markerArray,$templateCode,is_array($r)?$r:array());
		return  is_array($r)?$this->cObj->substituteMarkerArray($templateCode,is_array($r) ? $this->cObj->fillInMarkerArray($markerArray, $r, '', TRUE, 'FIELD_', $conf['general.']['xhtml']) : $markerArray):$templateCode;
	}

	/**
	* getForeignTableFromField
	*
	* @param	string		$fN: full field name path from $table ...(with '.' as seperators).  Must not be empty
	* @param	array		$conf : configuration array();
	* @param	string		$table: table to start search from (field name path is asusmed to start on this table ...). if empty we take flexform masterTable ..
	* @param	array		$sql: $sql Array to get alias from ...
	* @return	array		The subpart with all markers found in current $this->markerArray substituted.
	* this function handles Foreign table relations (level 1) , it allows us to get foreign table name from field
	* it returns foreigntable name and name of field in foreign table
	* Ex : if editing table fe_users, for relation usergroups.uid this function would return :
	* $ret['table']='fe_groups'
	* $ret['relTable']='fe_users'
	* $ret['relTableAlias']='fe_groups_usergroups'
	* $ret['tableAlias']='fe_groups_usergroups'
	* $ret['fieldLabel']='??'
	* $ret['fieldAlias']='????'
	* $ret['fNiD']='uid'
	*/
	
	function getForeignTableFromField($fN, &$conf,$table='',&$sql=array()) {
	  if (!$fN) echo "<br>ext:tx_meta_feedit:class.tx_metafeedit_lib.php:getForeignTableFromField : empty field given for $table!";
		$ret = array();
		$fNA = t3lib_div::trimexplode('.', $fN);
		$fNiD = end($fNA);
		$ftable = $table?$table:$conf['table'];
		$relTable=$ftable;
		foreach ($fNA as $f) {
			if ((strpos($f,'--fse--')!==false) || (strpos($f,'--fsb--')!==false)) continue;
			$relTable=$ftable;
			//ugly hack by CMD
			if ($f!="sorting") {
				if (!is_array($conf['TCAN'][$ftable]['columns'][$f]) && !$conf['list.']['sqlcalcfields.'][$fN] ) {
					if ($conf['debug']) echo "<br>ext:tx_meta_feedit:class.tx_metafeedit_lib.php:getForeignTableFromField : field  $f / $fN given does not exist in table $ftable ... InTable : $table! orig table :".$conf['table'];
				}
				if ($conf['TCAN'][$ftable]['columns'][$f]['config']['foreign_table']) $ftable = $conf['TCAN'][$ftable]['columns'][$f]['config']['foreign_table'];		   
			}
		}
		
		$ret['table'] = $ftable?$ftable:$relTable; // if we found a foreign table we return it otherwhise table is main table..
		$ret['relTable'] = $relTable; // if we found a foreign table we return otherwhise table is main table..
		$ret['relTableAlias'] = $relTable; // TODO add alias calc here !!!
		$ret['tableAlias']=($fNiD!=$fN?$sql['tableAliases'][$ret['table']][str_replace('.','_',str_replace('.'.$fNiD,'',$fN))]:$relTable);//$ret['table']);
		$ret['fieldLabel']=$conf['TCAN'][$ret['relTable']]['columns'][trim($fNiD)]['label']?$conf['TCAN'][$ret['relTable']]['columns'][trim($fNiD)]['label']:$fN;
		$ret['fieldAlias']=$fN; //call makeFieldalias here ...
		$ret['fNiD'] = $fNiD;
		return $ret;
	}
	
	/**
	* getForeignTableFromField2
	*
	* @param	string		$fN: full field name path from $table ...(with '.' as seperators).  Must not be empty
	* @param	array		$conf : configuration array();
	 * @return	array		The subpart with all markers found in current $this->markerArray substituted.
	* this function handles Foreign table relations (level 1) , it allows us to get foreign table name from field
	* it returns foreigntable name and name of field in foreign table
 	* it also loads TCA of unloaded tables :
 	* 
	*/
	
	function getForeignTableFromField2($fN,$ftable, &$confTcan) {
	  if (!$fN && $conf['debug']) {
	  	echo "<br>ext:tx_meta_feedit:class.tx_metafeedit_lib.php:getForeignTableFromField2 : empty field given for $table!";
	  	return '';
	  }
		$fNA = t3lib_div::trimexplode('.', $fN);
		$fNiD = end($fNA);
		//$ftable = $conf['table'];
		$relTable=$ftable;
		foreach ($fNA as $f) {
		if ((strpos($f,'--fse--')!==false) || (strpos($f,'--fsb--')!==false)) continue;
			$relTable=$ftable;
			if ($f!="sorting") {
				if (!is_array($confTcan[$ftable])) {
					$this->makeTypo3TCAForTable($confTcan,$ftable);
				}
				if (!is_array($confTcan[$ftable]['columns'][$f]) && !$conf['list.']['sqlcalcfields.'][$fN] ) {
					if ($conf['debug']) echo "<br>ext:tx_meta_feedit:class.tx_metafeedit_lib.php:getForeignTableFromField : field  $f / $fN given does not exist in table $ftable ... InTable : $table! orig table :".$conf['table'];
				}

				if ($confTcan[$ftable]['columns'][$f]['config']['foreign_table']) $ftable =$confTcan[$ftable]['columns'][$f]['config']['foreign_table'];	
				if (!is_array($confTcan[$ftable])) {
					$this->makeTypo3TCAForTable($confTcan,$ftable);
				}			
			}
		}
		
		$ret = $ftable?$ftable:$relTable; // if we found a foreign table we return otherwhise table is main table..

		return $ret;
	}
	

	/**
	* makeSQLJoin
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
	/**
	* makeSQLJoinWhere
	*
	* @param	[type]		$table: ...
	* @param	[type]		$relation: full field path from mastertable
	* @param	[type]		$conf: ...
	* @param	[type]		$Tables: ...
	* @return	[type]		...
	*/	  
	function makeSQLJoinWhere($relation,&$conf,$val) {
		$relA=t3lib_div::trimexplode('.',$relation);
		$c=count($relA);
		$c--;
		$link='';
		$table=$conf['table'];
		foreach($relA as $rel) {
			if ($c<=0) break;
			$c--;
			$table=$conf['TCAN'][$table]['columns'][$rel]['config']['foreign_table'];
			$link.='_'.$rel;
		}
		return " and $table$link.$rel='$val'";
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
		if ($conf['debug']) echo Tx_MetaFeedit_Lib_ViewArray::viewArray(array('checkT3Rights'=>$conf['checkT3Rights']));

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
	function MetaDBmayFEUserEditSelect($table,$feUserRow,$allowedGroups='',$feEditSelf=0, &$mmTable,&$conf)	   {
		// Returns where-definition that selects user-editable records.
		$groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$feUserRow['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $feUserRow['usergroup'];
		$OR_arr=array();

		// points to the field (integer) that holds the fe_users-id of the creator fe_user
	
		if ($conf['debug']) echo Tx_MetaFeedit_Lib_ViewArray::viewArray(array('fe_cruser_id'=>$conf['TCAN'][$table]['ctrl']['fe_cruser_id']));
	
		if ($conf['TCAN'][$table]['ctrl']['fe_cruser_id'])	{
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
			while(list(,$theGroupUid)=each($values))		{
				if ($theGroupUid) {$OR_arr[]=$table.'.'.$conf['TCAN'][$table]['ctrl']['fe_crgroup_id'].'='.$theGroupUid;}
			}
		}
	
		if ($conf['debug']) echo Tx_MetaFeedit_Lib_ViewArray::viewArray(array('feEditSelf '=>$feEditSelf ));
	
			// If $feEditSelf is set, fe_users may always edit them selves...
			if ($feEditSelf && $table=='fe_users')  {
					$OR_arr[]=$table.'.uid='.intval($feUserRow['uid']);
			}
	
			//$whereDef=' AND 1=0';
			if (count($OR_arr))	 {
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
	 * getRawRecord : as Replacement for normal typo3 method ...
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$conf: ...
	 * @param	[type]		$fields: ...
	 * @param	[type]		$noWSOL: ...
	 * @return	[type]		...
	 */
		function getRawRecord($table,$uid,&$conf,$fields='*',$noWSOL=FALSE)	{

			   $uid = intval($uid);
			   if (is_array($conf['TCAN'][$table]) || $table=='pages') {		// Excluding pages here so we can ask the function BEFORE TCA gets initialized. Support for this is followed up in deleteClause()...
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
				if (is_array($row))	 return $row;

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

				//		$ret=$this->cObj->DBmayFEUserEditSelect($table,$fe_user,$allowedGroups,$fe_userEditSelf);
				// Returns where-definition that selects user-editable records.
				$groupList = $allowedGroups ? implode(',',array_intersect(t3lib_div::trimExplode(',',$feUserRow['usergroup'],1),t3lib_div::trimExplode(',',$allowedGroups,1))) : $feUserRow['usergroup'];
				$OR_arr=array();

				// points to the field (integer) that holds the fe_users-id of the creator fe_user
				if ($conf['TCAN'][$table]['ctrl']['fe_cruser_id'])	{
						$OR_arr[]=$conf['TCAN'][$table]['ctrl']['fe_cruser_id'].'='.$feUserRow['uid'];
				}

				// points to the field (integer) that holds the fe_group-id of the creator fe_user's first group
				if ($conf['TCAN'][$table]['ctrl']['fe_crgroup_id'])   {
						$values = t3lib_div::intExplode(',',$groupList);
						while(list(,$theGroupUid)=each($values))		{
								 if ($theGroupUid)	   {$OR_arr[]=$conf['TCAN'][$table]['ctrl']['fe_crgroup_id'].'='.$theGroupUid;}
						 }
				}
				// If $feEditSelf is set, fe_users may always edit them selves...
				if ($feEditSelf && $table=='fe_users')  {
						 $OR_arr[]='uid='.intval($feUserRow['uid']);
			   }

			   //$whereDef=' AND 1=0';
			   if (count($OR_arr))	 {
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
			 if (!$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock'] || !$origArr[$GLOBALS['TCA'][$table]['ctrl']['fe_admin_lock']])	   {
							 // points to the field (integer) that holds the fe_users-id of the creator fe_user
					 if ($GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id'])	{
						$MMT = $GLOBALS['TCA'][$table]['columns'][$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id']]['config']['MM'];
					 	if ($MMT) { //if MM we must get ID of the MM table
							$FTUid=$fe_user['uid'];
							$LTUid=$origArr['uid'];
							$MMTreq = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$MMT,$MMT.'.uid_local='.$LTUid.' and '.$MMT.'.uid_foreign='.$FTUid);
							$resu=$GLOBALS['TYPO3_DB']->sql_num_rows($MMTreq);
							if ($resu>=1) {
								$ok=1;
							}
						} else { // not a MM
							 $rowFEUser = intval($origArr[$GLOBALS['TCA'][$table]['ctrl']['fe_cruser_id']]);
							 if ($rowFEUser && $rowFEUser==$fe_user['uid'])		{
									 $ok=1;
							 }
						}
					 }
					// If $fe_userEditSelf is set, fe_users may always edit themselves...
					 if ($fe_userEditSelf && $table=='fe_users' && !strcmp($fe_user['uid'],$origArr['uid']))		{
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

	/**
	* *******************************************************************************************
	* BLOG FUNCTIONS
	* *******************************************************************************************
	*
	* These functions handle blogging
	**/

	/**
	 * Blog comment count
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$origArr: ...
	 * @param	[type]		$fe_user: ...
	 * @param	[type]		$allowedGroups: ...
	 * @param	[type]		$fe_userEditSelf: ...
	 * @return	[type]		...
	 */
	 
	function blogCommentCount($table,$uid) {
		$ret=0;
		$cres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*) as c', 'tx_metafeedit_comments', 'deleted=0 and hidden=0 and linked_row=\''.$table.'_'.$uid.'\'', '');
		while ($crow = mysql_fetch_assoc($cres)) $ret = $crow["c"];
		return $ret;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$template: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	 
	function getBlogComments($template,$table,$uid,&$conf) {
		$ret='';
		$blogTpl = $this->cObj->getSubpart($template, '###TEMPLATE_BLOG###');
		$blogCommentsTpl = $this->cObj->getSubpart($blogTpl, '###BLOG-COMMENTS###');
		$blogCommentTpl = $this->cObj->getSubpart($blogCommentsTpl, '###BLOG-COMMENT###');

		$cres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tx_metafeedit_comments', 'deleted=0 and hidden=0 and linked_row=\''.$table.'_'.$uid.'\'', 'crdate asc');
		while ($crow = mysql_fetch_assoc($cres)) {
			  $markerArray=array();
				$markerArray = $this->cObj->fillInMarkerArray($markerArray, $crow, '', TRUE, 'FIELD_', FALSE);
				$markerArray['###FIELD_EVAL_crdate###'] = strftime(($conf['datetimeformat']?$conf['datetimeformat']:"%H:%M %d-%m-%Y"),$crow['crdate']);
				// we must unset uid so that we point on uid of calling object
				unset($markerArray['###FIELD_uid###']);
				$ret.=$this->cObj->substituteMarkerArray($blogCommentTpl, $markerArray);
		}
		$ret=$this->cObj->substituteSubpart($blogCommentsTpl,'###BLOG-COMMENT###',$ret);
		$ret=$this->cObj->substituteSubpart($blogTpl,'###BLOG-COMMENTS###',$ret);
		if ($conf['blog.']['captcha'] && !is_object($this->freeCap)) die ("Plugin Meta Feedit, Blog, Captcha : you have selected captcha spam protection but sr_freecap extention is not loaded !");
		if (is_object($this->freeCap) && $conf['blog.']['captcha']) {
					$markerArray = array_merge(array(), $this->freeCap->makeCaptcha());
				  $ret=$this->cObj->substituteMarkerArray($ret, $markerArray);
				} else {
					$ret = $this->cObj->substituteSubpart($ret,'###CAPTCHA_INSERT###','');
		}
		return $ret;
	}

	
	/**
	* makeTypo3TCAForTable : Loads Table TCA and Overrides TCA for sepecial Typo3 Fields (there must be another way to do this ...).
	*
	* @param	string		$FTable: Table name of table to generate an load TCA ...
	*/
	 
	
	function makeTypo3TCAForTable(&$confTCAN,$FTable) {
		if (!is_array($GLOBALS['TCA'][$FTable]['columns'])) t3lib_div::loadTCA($FTable);
		$uidField=$GLOBALS["TCA"][$FTable]['ctrl']['uidField']?$GLOBALS["TCA"][$FTable]['ctrl']['uidField']:'uid';

 		if ($GLOBALS['TCA'][$FTable]['ctrl']['tstamp']) {
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['tstamp']]['config']['eval']='datetime';
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['tstamp']]['config']['type']='input';
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['tstamp']]['label']=$FTable.'.'.$GLOBALS['TCA'][$FTable]['ctrl']['tstamp'];
		}
		if ($GLOBALS['TCA'][$FTable]['ctrl']['crdate']) {
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['crdate']]['config']['eval']='datetime';
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['crdate']]['config']['type']='input';
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['crdate']]['label']=$FTable.'.'.$GLOBALS['TCA'][$FTable]['ctrl']['crdate'];
		}

		if ($GLOBALS['TCA'][$FTable]['ctrl']['cruser_id']) $GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['cruser_id']]['config']['type']='input';
		if ($GLOBALS['TCA'][$FTable]['ctrl']['delete']) $GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['delete']]['config']['type']='input';
		
		//$GLOBALS['TCA'][$FTable]["feInterface"]["fe_admin_fieldList"] = $conf['create.']['fields'] ? $conf['create.']['fields'].($conf['edit.']['fields']?','.$conf['edit.']['fields']:'') : $conf['edit.']['fields'];
		
		/*if($conf['fe_cruser_id'])
			$GLOBALS['TCA'][$this->table]['ctrl']['fe_cruser_id'] = $conf['fe_cruser_id'];
		if($conf['fe_crgroup_id'] && $conf['allowedGroups']) {
  			$GLOBALS['TCA'][$this->table]['ctrl']['fe_crgroup_id'] = $conf['fe_crgroup_id'];
		}*/
		// if no configuration for column sortby we add one
		
		if($GLOBALS['TCA'][$FTable]['ctrl']['sortby'] && !is_array($GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['sortby']])) {
			$GLOBALS['TCA'][$FTable]['columns'][$GLOBALS['TCA'][$FTable]['ctrl']['sortby']]=array(
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
		if (!is_array($GLOBALS['TCA'][$FTable]['columns'][$uidField])) {
			$GLOBALS['TCA'][$FTable]['columns'][$uidField]=array(
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
		if (!is_array($GLOBALS['TCA'][$FTable]['columns']['pid'])) {
			$GLOBALS['TCA'][$FTable]['columns']['pid']=array(
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

		$confTCAN[$FTable]=$GLOBALS['TCA'][$FTable];//CBYTCAN
	}
	
	/**
	* getFieldList
	*
	* @param	[type]		$$conf: ...
	* @return	[type]		...
	*/
	function getFieldList(&$conf) {
		switch($conf['inputvar.']['cmd']) {
		case 'edit':
			$conf['fieldList']=implode(',',t3lib_div::trimExplode(',',$conf['edit.']['fields'],1));
			break;
		default:
			$conf['fieldList']=implode(',',t3lib_div::trimExplode(',',$conf['create.']['fields'],1));
			break;
		 }
		 // <CBY>
		 if (!$conf['fieldList']) {
		   $conf['fieldList']=implode(',',t3lib_div::trimExplode(',',$conf['TCAN'][$conf['table']]['feInterface']['fe_admin_fieldList'],1));
		 }
		 if (!$conf['list.']['show_fields']) {
		   $conf['list.']['show_fields']=$conf['fieldList'];						
		 }
		 
		 // in case the feInterface is not set we try the showRecordFieldList
		 if (!$conf['fieldList']) {
		   $conf['fieldList']=implode(',',t3lib_div::trimExplode(',',$conf['TCAN'][$conf['table']]['interface']['showRecordFieldList'],1));
		 }
		 $conf['blogFieldList']=$this->getBlogFieldList($conf) ;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
	function getBlogFieldList(&$conf) {
		$fields=$conf['blog.']['show_fields']?$conf['blog.']['show_fields']:'firstname,surname,email,homepage,place,crdate,entry,entrycomment';
		return $fields;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
	function getBlogEvalValues(&$conf) {
		$fields=$this->getBlogFieldList($conf);
		$fa=t3lib_div::trimexplode(',',$fields);
		foreach($fa as $fN) { //runs through the different fields
			$fN=trim($fN);
			if ($conf['TCAN']['tx_metafeedit_comments']['columns'][$fN]['config']['eval']) $ret[$fN]= $conf['TCAN']['tx_metafeedit_comments']['columns'][$fN]['config']['eval'];
		}
		return $ret;
	}
	/**
	* getItemLabelFromValue($items,$val)
	**/
	public function getItemLabelFromValue($items,$val,$conf) {
			foreach($items as $item) {
				
				if (count($item)==2){
					if ($val==$item[1]) {
						$lib=$this->getLLFromLabel($item[0],$conf);
						return $lib;
					}
				}
			}
			return '';
	}
	
	/* *******************************************************************************************
	* FUNCTIONS CALLED FROM fe_adminLib
	* *******************************************************************************************
	*
	* This function has the data array passed to it in $content.
	* Must return the data array again.
	* The function runs through the fields from fe_adminLibs dataArr and does some required processing for the different fields types.
	*
	* @param	array		$content: incoming data array
	* @param	array		$inconf: configuration array
	* @param	string	  $intable: name of alternate master table (if not using plugin master table), this is mainly used by replaceOptions().
	* @return	array		processed data array
	*/
	//function user_processDataArray($content, &$inconf,$intable='',$forceInConf=FALSE) {
	
	function user_processDataArray($content, $inconf,$intable='',$forceInConf=FALSE) {
		
		$fe_adminLib = &$inconf['parentObj'];
		$conf = $forceInConf?$inconf:$fe_adminLib->conf;
		
		$dataArr = $content;
		$table = $intable?$intable:$fe_adminLib->theTable;
		// if stdWraps are defined we iniatialize the data array for dataWrap ...
		if ($conf['stdWrap.'] || $conf[$conf['cmdmode'].'.']['stdWrap.'] || $conf[$conf['cmdmode'].'.']['item_stdWrap.'] || $conf['fileWrap.'] || $conf['evalWrap.']) {
			$this->cObj->start(count($dataArr)?$dataArr:array(), $table);
		}
		foreach((array)$dataArr as $fN => $value) {
			if (in_array(substr($fN,0,11),array('--div--;Tab','--fsb--;FSB','--fse--;FSE'))) continue;
			//special fields not to handle 
			if ($fN=='tx_metafeedit_dont_ctrl_checkboxes') continue;
			//ugly hack by CMD
			//if ($fN=='sorting') continue;
			if ($GLOBALS['TCA'][$FTable]['ctrl']['sortby'] && $fN==$GLOBALS['TCA'][$FTable]['ctrl']['sortby']) {
				$fe_adminLib->markerArray['###EVAL_ERROR_FIELD_'.$GLOBALS['TCA'][$FTable]['ctrl']['sortby'].'###'] = '';
				continue;
			}
			$tab=array();
			$res = $intable?$this->getForeignTableFromField($fN, $conf,$intable,$tab):$this->getForeignTableFromField($fN, $conf,'',$tab);			
			$_fN=str_replace('.','_',$fN);
			$dataArr[$_fN]=$dataArr[$fN]; // Why do we still do this, field names should have no '.'?
			$table = $res['relTable']; //we get field sourcetable...
			$fNiD = $res['fNiD'];
			$values = '';
			if (!$fe_adminLib->markerArray['###EVAL_ERROR_FIELD_'.$_fN.'###']) $fe_adminLib->markerArray['###EVAL_ERROR_FIELD_'.$_fN.'###'] = '';
			//@TODO this should not be necessary
			if (!$fe_adminLib->markerArray['###EVAL_ERROR_FIELD_'.$fN.'###']) $fe_adminLib->markerArray['###EVAL_ERROR_FIELD_'.$fN.'###'] = '';
			
			$type=$conf['TCAN'][$table]['columns'][$fNiD]['config']['type'];
			if (!$type && !$conf['list.']['sqlcalcfields.'][$fN]) {
				 if ($conf['debug']) echo "<br>NO TCA definition for masterTable : ".$fe_adminLib->theTable.", table : $table, in table : $intable, field $fNiD, orig field  : $fN";
				continue;
			}
			switch($type) {
				case 'input':
					// if evaltype is date or datetime and overrideValue is 'now' we transform it into the current timestamp + the int following 'now'.
					// Example: if override value is now+30 we transform it into a timestamp representing the day 30 days from today
					$evals=t3lib_div::trimexplode(',',$conf['TCAN'][$table]['columns'][$fNiD]['config']['eval']);
	 				if ((in_array('date',$evals) || in_array('datetime',$evals)) && substr($conf[$fe_adminLib->conf['cmdKey']."."]["overrideValues."][$fN], 0, 3) == 'now') {
						$dataArr[$_fN] = time() + 24 * 60 * 60 * intval(substr($conf[$fe_adminLib->conf['cmdKey']."."]["overrideValues."][$fN], 3));
					}
					if (in_array('date',$evals) && !empty($dataArr[$fN])) {
						$values = strftime(($conf['dateformat']?$conf['dateformat']:($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%e-%Y' :'%e-%m-%Y')),$dataArr[$fN]);

					}
					if (in_array('time',$evals) && !empty($dataArr[$fN])) {
						$values = strftime('%H:%M',$dataArr[$fN]);

					}
					if(in_array('datetime',$evals) && !empty($dataArr[$fN])) {
						$values = strftime(($conf['datetimeformat']?$conf['datetimeformat']: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%H:%M %m-%e-%Y' :'%H:%M %e-%m-%Y')),$dataArr[$fN]);
					}
					// wwwURL check
					if (in_array('wwwURL', t3lib_div::trimexplode(',', $conf[$fe_adminLib->cmd."."]['evalValues.'][$fN]))) {
						$wwwurl = 'http://'.str_replace('http://', '', $dataArr[$fN]);
						$values = '<a href="'.$wwwurl.'">'.$dataArr[$fN]."</a>";
					}
					// email check
					if (in_array('email', t3lib_div::trimexplode(',', $conf[$fe_adminLib->cmd."."]['evalValues.'][$fN]))) {
						$mailto = 'mailto:'.$dataArr[$fN];
						$values = '<a href="'.$mailto.'">'.$dataArr[$fN]."</a>";
					}
					$dataArr['EVAL_'.$_fN] = $values;
	 				// we add here fields to search filed list if they are not of date type . We should be more precise here

					if (!in_array('date',$evals) && !in_array('datetime',$evals)) $fe_adminLib->internal['searchFieldList'] .= ",".$fN;
					break;
				case 'text':
					//$dataArr = $this->rteProcessDataArr($dataArr, $table, $fN, 'db',$conf,"user_processDataArray");
					$dataArr = $this->rteProcessDataArr($dataArr, $table, $fN, 'rte',$conf,"user_processDataArray");
					$fe_adminLib->internal['searchFieldList'] .= ",".$fN;
					break;
				case 'radio' :
					$items = $conf['TCAN'][$table]['columns'][$fNiD]['config']["items"];
 					$index=-1;
					for ($i = 0; $i < count ($items); ++$i) {
						if ($items[$i][1]==$dataArr[$fN]) {
							$index=$i;
							break;
						}
					}

					$dataArr['EVAL_'.$_fN] = ($index===-1)?'':$this->getLLFromLabel($items[$index][0],$conf);
					break;
				case 'check':
					$cols=count($conf['TCAN'][$table]['columns'][$fNiD]['config']['items'])>0;
					//echo "<br>$cols, $fNiD, $_fN, $value";
					$invert = 0;   
					if ($cols) {
							
							$dataArr[$_fN] = (int)$value;
							$dataArr['EVAL_'.$_fN] = ($conf['cmdmode']=='list')?'':(int)$value;
					} else {
						if ($value === 'on' || $value === 1 || $value === '1') {
							$dataArr[$_fN] = 1;
							$dataArr['EVAL_'.$_fN] = ($conf['cmdmode']=='list')?'<img src="'.t3lib_extMgm::siteRelPath('meta_feedit').'res/checked.png" alt="checked"/>':$this->getLLFromLabel('check_yes',$conf);
						} else {
							// No value, what should we do ?
		
							$dataArr[$_fN] = 0;
							$dataArr['EVAL_'.$_fN] = ($conf['cmdmode']=='list')?'':$this->getLLFromLabel('check_no',$conf);
						}
					}
					break;
				case 'group':
					if ($conf['TCAN'][$table]['columns'][$fNiD]['config']['internal_type'] == 'file') {
	
						$uf = $conf['TCAN'][$table]['columns'][$fNiD]['config']['uploadfolder'];
						$uf2 = "typo3temp";
	
						//$BACKPATH is different from cache than with no_cache ???
						//$BACK_PATH = '../../../';
	
						$newImgs = '';
						$dbImgs = '';
	
						$imgs = t3lib_div::trimexplode(',', $dataArr[$fN]);
	
						$size = "";
						$size = $conf[$fe_adminLib->conf['cmdKey'].'.']['icon_thumbSize.'][$fN];
						if (!count($imgs)) {
							$imgs=array('$$$$NOIMAGE$$$$');
						}
						// Do we handle multiple media (default no)
						$multipleMedia=$conf[$this->pluginId.'.']['list.']['multipleMedia']?$conf[$this->pluginId.'.']['list.']['multipleMedia']:($conf['default.']['list.']['multipleMedia']?$conf['default.']['list.']['multipleMedia']:false);
						
						foreach ($imgs as $img) {
							if (!$img) {
								$img=t3lib_extMgm::siteRelPath('meta_feedit').'res/noimage.jpg';
							} else {
								if ($img!='$$$$NOIMAGE$$$$') {
									$iimg = explode('|', $img);
									if (count($iimg) > 1) {
										$newImgs .= $newImgs?",".$img:$img;
										$img = $iimg[0];
										$img = $uf2.'/'.$img;
									} else {
										$dbImgs .= $dbImgs?",".$img:$img; // what is this ???
										$img = $uf.'/'.$img;
									}
								} else {
									$img=t3lib_extMgm::siteRelPath('meta_feedit').'res/noimage.jpg';
								}
							}
							//We handle image wrap here ... Must do something better here if user wrap defined we disable default link

							$std=$conf[$conf['cmdmode'].'.']['stdWrap.']?$conf[$conf['cmdmode'].'.']['stdWrap.']:$conf['stdWrap.'];// what about item_stdWrap here ?
							if ($conf[$conf['cmdmode'].'.']['item_stdWrap.'][$table.'.'] || $std[$table.'.'][$fNiD.'.'] || $std[$fNiD.'.']) {
								$ATageB = '';
								$ATagE = '';
							} else {
								// is there a media player linked to this file type ?
								if ($conf[$conf['cmdmode'].'.']['mediaPlayer']) $player = $this->getMediaPlayer($img, $conf);

								$imgi = $img;
								if ($player) {
									$conf_pointeur=t3lib_div::_GP("tx_metafeedit");
									$prm="&".$this->prefixId."[mediafile]=".$img."&".$this->prefixId."[mediaplayer]=".$player."&".$this->prefixId."[mediaru]=".$dataArr['uid']."&".$this->prefixId."[pointer]=".$conf_pointeur['pointer'];
									$link=$this->makeFormTypoLink($conf,$prm);
									$imgi = $link;
								}
								$ATagB = '<a href="'.$imgi.'" target="_blank">';
								$ATagE = '</a>';
							}

							$imgT = $this->getIconPath($img, $conf, $size);

							if ($size == "0") {
								$values .= $ATagB.$this->cObj->stdWrap('<img src="'.$imgT.'" title="'.trim(basename($img)).'" alt="'.$img.'" />', $conf['fileWrap.'][$fN.'.']).$ATagE;
							} else {
								// here must use cObject IMAGE
								$imgA=array();
								//$imgA['file.']['maxW'] = 100;
								$imgA['file.']['maxH'] = 50;
								//$imgA['file.']['width'] = 100;
								$imgA['file.']['height'] = '50c';


								if ($conf[$conf['cmdmode'].'.']['imgConf.'][$fN.'.'] || $conf['imgConf.'][$fN.'.'])	$imgA=$conf[$conf['cmdmode'].'.']['imgConf.'][$fN.'.']?$conf[$conf['cmdmode'].'.']['imgConf.'][$fN.'.']:$conf['imgConf.'][$fN.'.'];
								if (!$imgA['file'] ) $imgA['file'] = $imgT;
								$imgA['altText']=$imgT;
								$imgA['titleText']=trim(basename($imgT));
								$values .= $ATagB.$this->cObj->stdWrap($this->cObj->IMAGE($imgA), $conf['fileWrap.'][$fN.'.']).$ATagE;
							}
							// By defaullt we only handle first media
							if (!$multipleMedia) break;
						}
	
							// We handle here files which are not yet saved in DB (preview mode!)
		
							$imgs = explode(',', $dataArr[$fN.'_file']);
							foreach ($imgs as $imga) {
								$img = explode('|', $imga);
								$newImgs .= $newImgs?",".$imga:
								$imga;
								if (count($img) > 1) {
									$im = PATH_site.$uf2.'/'.$img[0];
								} else {
									$im = PATH_site.$uf.'/'.$img[0];
								}
								if ($img[0]) {
									$img = $this->getIconPath($im, $conf, $size);
									if ($size == "0") {
										$values .= $this->cObj->stdWrap('<img src="'.$img.'"  title="'.trim(basename($im)).'" alt="'.$im.'" />', $conf['fileWrap.'][$fN.'.']);
									} else {
										$values .= $this->cObj->stdWrap(t3lib_BEfunc::getThumbNail($BACK_PATH.'/typo3/thumbs.php', $img, '', $size), $conf['fileWrap.'][$fN.'.']);
									}
								}
							}
						
							$dataArr['EVAL_'.$_fN] = $values;
							$dataArr[$_fN] = $dataArr[$fN.'_file']?($dataArr[$fN]?$dataArr[$fN].','.$dataArr[$fN.'_file']:$dataArr[$fN.'_file']):$dataArr[$fN];
		
							if (!$fe_adminLib->failure) {
								$dataArr[$_fN.'_file'] = $newImgs;
								$dataArr[$_fN] = $dbImgs;
							}
						}
						break;
				case 'select':
					// field is of select type
					$values = '';
					$uids = array();
					if ($conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table']) {
						// reference to elements from another table
						$FT = $conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table'];
						$this->makeTypo3TCAForTable($conf['TCAN'],$FT);
						$label = $conf['label.'][$FT]?$conf['label.'][$FT]:$conf['TCAN'][$FT]['ctrl']['label'];
						$label_alt = $conf['label_alt.'][$FT]?$conf['label_alt.'][$FT]:$conf['TCAN'][$FT]['ctrl']['label_alt'];
	
						$label_alt_force = $conf['label_alt_force.'][$FT]?$conf['label_alt_force.'][$FT]: $conf['TCAN'][$FT]['ctrl']['label_alt_force'];
							
						if ($dataArr[$fN]) {
							if ($conf['TCAN'][$table]['columns'][$fNiD]['config']["MM"] && $dataArr[$conf['uidField']]) {
								// from mm-relation
								$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $conf['TCAN'][$table]['columns'][$fNiD]['config']["MM"], 'uid_local=\''.$dataArr[$conf['uidField']].'\'', '');
								if (mysql_error()) debug(array(mysql_error(), $query), 'processDataArray()::field='.$fN);
									if ($conf['debug'] && mysql_num_rows($MMres) != $dataArr[$fN]) debug("class.tx_metafeedit_lib.php::tx_metafeedit_lib->user_processDataArray(): Wrong number ($cnt<>".$dataArr[$fN].") of selections reached for  field $fN of table $table, mm:  $mmTable");
								while ($MMrow = mysql_fetch_assoc($MMres)) $uids[] = $MMrow["uid_foreign"];
							} else {
								// clean from DB
								$uids = t3lib_div::trimExplode(',', $dataArr[$fN]);
							}
							$orClause = '';
	
	
							$whereClause = " 1 ";
							foreach($uids as $uid) $orClause .= $orClause ? ' OR '.$conf['uidField'].' = \''.$uid.'\'' : $conf['uidField'].' = \''.$uid.'\'';
							$TCAFT = $conf['TCAN'][$FT];
							$statictable = 0;
							if (strpos($FT, 'static_') === 0) {
								$statictable = 1;
							}
							if ($FT && $TCAFT['ctrl']['languageField'] && $TCAFT['ctrl']['transOrigPointerField']) {
								//		$whereClause .= ' AND '.$TCAFT['ctrl']['transOrigPointerField'].'=0';
							}
							$orClause = $orClause?" and (".$orClause.") ":"";
	
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table'], $whereClause.' '.$orClause);
							if ($GLOBALS['TYPO3_DB']->sql_error()) debug($GLOBALS['TYPO3_DB']->sql_error(), 'sql error');
							$values = '';
							$vals = array();
							$d = $this->cObj->data;
							while ($resRow = mysql_fetch_assoc($res)) {
								$this->cObj->start($resRow, $conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table']);
								$resLabel = $resRow[$label];
								$resLabel_alt = $resRow[$label_alt];
								if ($statictable) {
									$code = $resRow['lg_iso_2'].($resRrow['lg_country_iso_2']?'_'.$resRow['lg_country_iso_2']:'');
									$resLabel = $this->getLL('language_'.$code, $conf);
									if (!$resLabel ) {
										$resLabel = $GLOBALS['TSFE']->csConv($resRow['lg_name_en'], $this->staticInfoCharset);
									}
								}
								$resLabel = $this->cObj->stdWrap($resLabel, $conf['evalWrap.'][$_fN.'.']);
								$resLabel_alt = $this->cObj->stdWrap($resLabel_alt, $conf['evalWrap.'][$_fN.'.']);
								$tempLabel = $label_alt_force ? $resLabel.', '.$resLabel_alt : $resLabel;
								$tempLabel = $tempLabel ? $tempLabel : $resLabel_alt;
								$vals[] = $tempLabel;
							}
							$this->cObj->start($d, $table);
							$cc = count($vals);
							$i = 0;
							foreach($vals as $v) {
								$i++;
								if ($i == $cc && $cc > 1) {
									$sep = $conf['evalLastSep.'][$fN]?$conf['evalLastSep.'][$fN]:',';
								} else {
									$sep = $conf['evalSep.'][$fN]?$conf['evalSep.'][$fN]:',';
								}
								$values .= $values ? $sep . $v :$v;
							}
						}
					} elseif(count($conf['TCAN'][$table]['columns'][$fNiD]['config']['items'])) {
					
						// fixed items
						if (isset($dataArr[$fN])) {
							$vals = t3lib_div::trimExplode(',', $dataArr[$fN]);
							foreach($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"] as $item) {
								if (!empty($item)) {
									list($label, $val) = $item;
									$label = $this->getLLFromLabel($label,$conf);
									if (in_array($val, $vals)) {
										$values .= $values ? ', ' . $label :
										 $label;
									}
								}
							}
						}
						if($conf['TCAN'][$table]['columns'][$fNiD]['config']["itemsProcFunc"]) {
							$this->t3lib_TCEforms->procItems($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"],$conf,$conf['TCAN'][$table]['columns'][$fNiD]['config'],$table,$dataArr,$fNiD);
							//t3lib_div::callUserFunction($conf['TCAN'][$table]['columns'][$fNiD]['config']["itemsProcFunc"], $newArr, $this);
							
							if (is_array($newArr['items'])) foreach($newArr['items'] as $item) {
								if (!empty($item)) {
									list($label, $val) = $item;
									$label = $this->getLLFromLabel($label,$conf);
									$values .= $values ? ', ' . $label : $label;
								}
							}
						}
					}
					$dataArr['EVAL_'.$_fN] = $values;
					break;
				case 'inline':
					// field is of inline type
					$values = '';
					$uids = array();
					if ($conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table']) {
					
						$FF=$conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_field'];
					
						// reference to elements from another table
						$FT = $conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table'];
						$label = $conf['label.'][$FT]?$conf['label.'][$FT]:
						$conf['TCAN'][$FT]['ctrl']['label'];
											
						$label_alt = $conf['label_alt.'][$FT]?$conf['label_alt.'][$FT]: 
							$conf['TCAN'][$FT]['ctrl']['label_alt'];
	
						$label_alt_force = $conf['label_alt_force.'][$FT]?$conf['label_alt_force.'][$FT]: 
							$conf['TCAN'][$FT]['ctrl']['label_alt_force'];
							
						if ($dataArr[$fN]) {
							if ($conf['TCAN'][$table]['columns'][$fNiD]['config']["MM"] && $dataArr[$conf['uidField']]) {
								// from mm-relation
								$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $conf['TCAN'][$table]['columns'][$fNiD]['config']["MM"], 'uid_local=\''.$dataArr[$conf['uidField']].'\'', '');
								if (mysql_error()) debug(array(mysql_error(), $query), 'processDataArray()::field='.$fN);
									if ($conf['debug'] && mysql_num_rows($MMres) != $dataArr[$fN]) debug("class.tx_metafeedit_lib.php::tx_metafeedit_lib->user_processDataArray(): Wrong number ($cnt<>".$dataArr[$fN].") of selections reached for  field $fN of table $table, mm:  $mmTable");
								while ($MMrow = mysql_fetch_assoc($MMres)) $uids[] = $MMrow["uid_foreign"];
							} else {
								// clean from DB
								$uids = t3lib_div::trimExplode(',', $dataArr[$fN]);
							}
							$orClause = '';
	
	
							$whereClause = " 1 ";
							foreach($uids as $uid) $orClause .= $orClause ? ' OR '.$conf['uidField'].' LIKE \''.$uid.'\'' :
							 $conf['uidField'].' = \''.$uid.'\'';
							$TCAFT = $conf['TCAN'][$FT];
							$statictable = 0;
							if (strpos($FT, 'static_') === 0) {
								$statictable = 1;
							}
							if ($FT && $TCAFT['ctrl']['languageField'] && $TCAFT['ctrl']['transOrigPointerField']) {
								//		$whereClause .= ' AND '.$TCAFT['ctrl']['transOrigPointerField'].'=0';
							}
							$orClause = $orClause?" and (".$orClause.") ":
							"";
	
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table'], $whereClause.' '.$orClause);
							if ($GLOBALS['TYPO3_DB']->sql_error()) debug($GLOBALS['TYPO3_DB']->sql_error(), 'sql error');
							$values = '';
							$vals = array();
							$d = $this->cObj->data;
							while ($resRow = mysql_fetch_assoc($res)) {
								$this->cObj->start($resRow, $conf['TCAN'][$table]['columns'][$fNiD]['config']['foreign_table']);
								$resLabel = $resRow[$label];
								$resLabel_alt = $resRow[$label_alt];
								if ($statictable) {
									$code = $resRow['lg_iso_2'].($resRrow['lg_country_iso_2']?'_'.$resRow['lg_country_iso_2']:'');
									$resLabel = $this->getLL('language_'.$code, $conf);
									if (!$resLabel ) {
										$resLabel = $GLOBALS['TSFE']->csConv($resRow['lg_name_en'], $this->staticInfoCharset);
									}
								}
								$resLabel = $this->cObj->stdWrap($resLabel, $conf['evalWrap.'][$fN.'.']);
								$resLabel_alt = $this->cObj->stdWrap($resLabel_alt, $conf['evalWrap.'][$fN.'.']);
								$tempLabel = $label_alt_force ? $resLabel.', '.$resLabel_alt : $resLabel;
								$tempLabel = $tempLabel ? $tempLabel : $resLabel_alt;
								$vals[] = $tempLabel;
							}
							$this->cObj->start($d, $table);
							$cc = count($vals);
							$i = 0;
							foreach($vals as $v) {
								$i++;
								if ($i == $cc && $cc > 1) {
									$sep = $conf['evalLastSep.'][$fN]?$conf['evalLastSep.'][$fN]:
									',';
								} else {
									$sep = $conf['evalSep.'][$fN]?$conf['evalSep.'][$fN]:
									',';
								}
								$values .= $values ? $sep . $v :
								 $v;
							}
						}
					} elseif($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"]) {
						// fixed items
						if (isset($dataArr[$fN])) {
							$vals = t3lib_div::trimExplode(',', $dataArr[$fN]);
							foreach($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"] as $item) {
								if (!empty($item)) {
									list($label, $val) = $item;
									$label = $this->getLLFromLabel($label,$conf);
									if (in_array($val, $vals)) {
										$values .= $values ? ', ' . $label :
										 $label;
									}
								}
							}
						}
						if($conf['TCAN'][$table]['columns'][$fNiD]['config']["itemsProcFunc"]) {
							//$newArr=array();
							//t3lib_div::callUserFunction($conf['TCAN'][$table]['columns'][$fNiD]['config']["itemsProcFunc"], $newArr, $this);
							$this->t3lib_TCEforms->procItems($conf['TCAN'][$table]['columns'][$fNiD]['config']["items"],$conf['TCAN'][$table]['columns'][$fNiD],$conf['TCAN'][$table]['columns'][$fNiD]['config'],$table,$dataArr,$fNiD);
							if (is_array($newArr['items'])) foreach($newArr['items'] as $item) {
								if (!empty($item)) {
									list($label, $val) = $item;
									$label = $this->getLLFromLabel($label,$conf);
									$values .= $values ? ', ' . $label : $label;
								}
							}
						}
					}
					$dataArr['EVAL_'.$_fN] = $values;
					break;			
				default:
					if ($conf['list.']['sqlcalcfields.'][$fN]) {
						$dataArr['EVAL_'.$fN]=$this->transformSqlCalcField($fN,$dataArr[$fN],$conf);
					} 
					break;
				
			}

			// we handle stdWrap on the Data..
			$std=$conf[$conf['cmdmode'].'.']['stdWrap.']?$conf[$conf['cmdmode'].'.']['stdWrap.']:$conf['stdWrap.'];

			if ($std[$fNiD.'.'] || $std[$table.'.'][$fNiD.'.'] || $std[$_fN.'.'] || $std[$table.'.'][$_fN.'.']) {
				if ($std[$fNiD.'.']) $stdConf = $std[$fNiD.'.'];
				if ($std[$table.'.'][$fNiD.'.']) $stdConf = $std[$table.'.'][$fNiD.'.'];
				if ($std[$_fN.'.']) $stdConf = $std[$_fN.'.'];
				if ($std[$table.'.'][$_fN.'.']) $stdConf = $std[$table.'.'][$_fN.'.'];
				if ($dataArr['EVAL_'.$_fN]) {
					$dataArr['EVAL_'.$_fN] = $this->cObj->stdWrap($dataArr['EVAL_'.$_fN], $stdConf);
				} else {
					$dataArr['EVAL_'.$_fN] = $this->cObj->stdWrap($dataArr[$fN], $stdConf);
				}
			}
			
			// FIXME this should not be necessary ...
			$dataArr['EVAL_'.$fN]=$dataArr['EVAL_'.$_fN];
			
		}

		if (!$dataArr['tx_metafeedit_dont_ctrl_checkboxes']) {
			// We handle here the value of non selected check boxes.
			$fieldArray = array_unique(t3lib_div::trimExplode(",", $fe_adminLib->conf['fieldList']));

			foreach ($fieldArray as $F) {
				switch((string)$conf['TCAN'][$table]['columns'][$F]['config']['type']) {
				case 'check':
					$invert = 0;
					//if (in_array('invert',t3lib_div::trimexplode(',',$conf[$fe_adminLib->cmd."."]['evalValues.'][$F]))) $invert=1;
					if (count($conf['TCAN'][$table]['columns'][$F]['config']['items'])) {
					} else {
						if ($dataArr[$F] != 1) {
							$dataArr[$F] = 0;
							$dataArr['EVAL_'.$F] = ($conf['cmdmode']=='list')?'':$this->getLLFromLabel('check_no',$conf);
							if ($invert) {
								$dataArr[$F] = 1;
								$dataArr['EVAL_'.$F] = ($conf['cmdmode']=='list')?'<img src="'.t3lib_extMgm::siteRelPath('meta_feedit').'res/checked.png" alt="checked" />':$this->getLLFromLabel('check_yes',$conf);
							}
						} else {
							if ($invert) {
								$dataArr[$F] = 0;
								$dataArr['EVAL_'.$F] = ($conf['cmdmode']=='list')?'':$this->getLLFromLabel('check_no',$conf);
							}
						}
					}
					break;
				}
			}
			unset($dataArr['tx_metafeedit_dont_ctrl_checkboxes']);
		}
		return $dataArr;
	}

		/*
		* user_updateArray : This function has the value-array passed to it before the value array is used to construct the update-JavaScript statements in fe_adminLib.
		* Must return the value-array again.
		* The function runs through the fields from fe_adminLibs dataArr and does some required stuff for the different fields types
		*/

		//function user_updateArray($content, &$conf) {

		function user_updateArray($content, $conf) {
			$fe_adminLib = &$conf['parentObj'];
			$conf = $fe_adminLib->conf;
			$dataArr = $content;
			$table = $fe_adminLib->theTable;
			//We handle override values here ..
			/*echo "COntent";
			$fNA=tx_metafeedit_lib::getOverrideFields($fe_adminLib->cmd,$fe_adminLib->conf);
			foreach($fNA as $fN) {
			$overrideuids=tx_metafeedit_lib::getOverrideValue($fN,$fe_adminLib->cmd,$fe_adminLib->conf,$fe_adminLib->cObj);
			if ($overrideuids) $dataArr[$fN]=$overrideuids;
			}*/
			foreach((array)$dataArr as $fN => $value) {
				if (in_array(substr($fN,0,11),array('--div--;Tab','--fsb--;FSB','--fse--;FSE'))) {
					unset($dataArr[$fN]);
					continue;
				}
				switch((string)$conf['TCAN'][$table]['columns'][$fN]['config']['type']) {
					case 'group':
						// we need to update the additional field $fN.'_file'.
	
						$fieldArray = array_unique(t3lib_div::trimExplode(",", $fe_adminLib->conf[$fe_adminLib->conf['cmdKey'].'.']['fields']));
						if (in_array($fN, $fieldArray) && !in_array($fN.'_file', $fieldArray)) {
							// CBY I removed this
							//$fe_adminLib->additionalUpdateFields .= ','.$fN.'_file';
						}
						break;
					case 'select':
						if ($conf['TCAN'][$table]['columns'][$fN]['config']['MM']) {
							//its a MM relation
							$uid = $content[$conf['uidField']];
							//modif OSR 2008 09 14
							$uids ='';
							$mmTable = $conf['TCAN'][$table]['columns'][$fN]['config']['MM'];
							$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $mmTable, $mmTable.'.uid_local=\''.$uid.'\'', '', ' '.$mmTable.'.sorting ');
							if (mysql_error()) debug(array(mysql_error(), $query), 'getFormFieldCode()::field='.$fN);
							$cnt=mysql_num_rows($MMres);
							if ($conf['debug'] && $cnt!= $rec[$fN]) debug("class.tx_metafeedit_lib.php::tx_metafeedit_lib->user_updateArray(): Wrong number ($cnt<>".$rec[$fN].") of selections reached for  field $fN of table $table, mm:  $mmTable");
							while ($MMrow = mysql_fetch_assoc($MMres)) {
								if ($uids) {
									$uids .= ','. $MMrow["uid_foreign"];
								} else {
									$uids .= $MMrow["uid_foreign"];
								}
	
							}
							$dataArr[$fN] = $uids;
						}
	
						break;
					case 'inline':
						if ($conf['TCAN'][$table]['columns'][$fN]['config']['MM']) {
							//its a MM relation
							$uid = $content[$conf['uidField']];
							//modif OSR 2008 09 14
							$uids ='';
							$mmTable = $conf['TCAN'][$table]['columns'][$fN]['config']['MM'];
							$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $mmTable, $mmTable.'.uid_local=\''.$uid.'\'', '', ' '.$mmTable.'.sorting ');
							if (mysql_error()) debug(array(mysql_error(), $query), 'getFormFieldCode()::field='.$fN);
							$cnt=mysql_num_rows($MMres);
							if ($cnt!= $rec[$fN]) debug("class.tx_metafeedit_lib.php::tx_metafeedit_lib->user_updateArray(): Wrong number ($cnt<>".$rec[$fN].") of selections reached for  field $fN of table $table, mm:  $mmTable");
							while ($MMrow = mysql_fetch_assoc($MMres)) {
								if ($uids) {
									$uids .= ','. $MMrow["uid_foreign"];
								} else {
									$uids .= $MMrow["uid_foreign"];
								}
	
							}
							$dataArr[$fN] = $uids;
						}
	
						break;
					case 'input':
						// if evaltype is date or datetime and defaultValue is 'now' we transform it into the current timestamp + the int following 'now'.
						// Example: if default value is now+30 we transform it into a timestamp representing the day 30 days from today
						$evals=t3lib_div::trimexplode(',',$conf['TCAN'][$table]['columns'][$fN]['config']['eval']);
						
						if ((in_array('date',$evals) || in_array('datetime',$evals) || in_array('time',$evals)) && substr($fe_adminLib->conf[$fe_adminLib->conf['cmdKey']."."]["defaultValues."][$fN], 0, 3) == 'now'/* && empty($dataArr[$fN])*/) {
							$dataArr[$fN] = time() + 24 * 60 * 60 * intval(substr($fe_adminLib->conf[$fe_adminLib->conf['cmdKey']."."]["defaultValues."][$fN], 3));
						}
						break;
					case 'check':
						// here we can invert boolean functionnality on screen...
						$invert = 0;
						if (in_array('invert', t3lib_div::trimexplode(',', $fe_adminLib->conf[$fe_adminLib->cmd."."]['evalValues.'][$fN]))) $invert = 1;
						if ($invert) $dataArr[$fN] = $dataArr[$fN]?0:1;
						break;
					case 'radio':
						break;
					case 'text':
						//$dataArr = $this->rteProcessDataArr($dataArr, $table, $fN, 'rte',$conf,'user_updateArray');
						break;
			}
			//MODIF CBY ....
			$dataArr[$fN]=stripslashes($dataArr[$fN]);
		}
		return $dataArr;
	}
	/**
	* rteProcessDataArr : Processes the field $fN in $dataArr be the rte mode $mode,
	* according to the Page TS RTE.default.FE
	*
	* @param	$dataArr		array the dataArray
	* @param	$table		string the table currently working on
	* @param	$fN		string the fieldname of the table
	* @param	$mode		string the transformation direction: either 'rte' or 'db'
	* @param	[type]		$conf: ...
	* @return	array		the modified dataArr
	*/
	function rteProcessDataArr(&$dataArr, $table, $fN, $mode,&$conf,$source='') {
		if ($conf['cmdmode']!='list') {
			if (t3lib_extmgm::isLoaded('rtehtmlarea') && !$this->RTEObj)
				$this->RTEObj = t3lib_div::makeInstance('tx_rtehtmlarea_pi2');			
				/*if (!empty($dataArr['_TRANSFORM_'.$fN]) && is_object($this->RTEObj) && $this->RTEObj->isAvailable()) {
					$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
					$this->thisConfig = $pageTSConfig['RTE.']['default.']['FE.'];
					$this->thePidValue = $GLOBALS['TSFE']->id;
					#   $this->specConf = array('richtext' => 1, 'rte_transform' => array('parameters' => array('mode=ts_css','flag=rte_enabled')));
					//TODO put specialConf in $confindexed on $fN and Type.
					$this->specConf = $this->getFieldSpecialConf($table, $fN,$conf,$dataArr);
					$dataArr[$fN] = $this->RTEObj->transformContent($mode, $dataArr[$fN], $table, $fN, $dataArr, $this->specConf, $this->thisConfig, '', $this->thePidValue);
				}*/
			if (is_object($this->RTEObj) && $this->RTEObj->isAvailable()) {
				$this->specConf = $this->getFieldSpecialConf($table, $fN,$conf,$dataArr);
				if (count($this->specConf)) {
					$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
					$this->thisConfig = $pageTSConfig['RTE.']['default.']['FE.'];//array();//
					$this->thePidValue = $GLOBALS['TSFE']->id;
					$dataArr[$fN] = $this->RTEObj->transformContent($mode, $dataArr[$fN], $table, $fN, $dataArr, $this->specConf, $this->thisConfig, '', $this->thePidValue);		
				}
			}
		}
		return $dataArr;
	}

	/**********************************************************************************************
	* HELPER FUNCTIONS
	**********************************************************************************************/
	
	/**
	* getFieldSpecialConf Gets the special RTE configurations for a field. The configurations placed in the type array.
	*
	* @param	$table		string the current table
	* @param	$fN		string the fieldname to get the configurations for
	* @param	[type]		$conf: ...
	* @return	array		the specialconf array
	* @todo check if functions works on sublevel table fields...
	*/
	 function getFieldSpecialConf($table,$fN,&$conf,&$dataArr) {
		$TCA = $conf['TCAN'][$table];
		// Get the type value
		$type = 0; // default value
		$typeField = $TCA['ctrl']['type'];
		$uid=$conf['inputvar.']['rU']?$conf['inputvar.']['rU']:$dataArr[$conf['uidField']];
		if($typeField && $uid) { // get the type from the database else use default value
		  $rec = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$uid);
		  $type = intval($rec[$typeField]);
		}
		// if we know the type we get the right special conf.
		if (is_array($TCA['types'][$type])) {
			$specialConf=$this->getFieldSpecialConfFromType($TCA,$type,$fN);
		} else {
			//otherwise we scan TCA types.
			foreach($TCA['types'] as $type=>$typeConfArray) {
				// get the special configurations and check for an existing richtext configuration
				$specialConf=$this->getFieldSpecialConfFromType($TCA,$type,$fN);
				if (count($specialConf)>0) break; // TODO : do a better test than this (in particular
			}
		}
		return $specialConf;
	}
	/**
	 * We create list where from external filter definition
	 * @param array $conf
	 * @param array $sql
	 */
	function getFilter(&$conf,&$sql) {
		$json=$conf['piVars']['filter'];
		$filter=json_decode($json);
		$externalFilter='';
		if (count($filter)) {
			$externalFilter.=' and (';
			$first=true;
			foreach($filter as $filterCriteria) {
				$w=($filterCriteria->t?$filterCriteria->t.'.':'').$filterCriteria->f.' '.$filterCriteria->o.' '.$filterCriteria->v;
				$externalFilter.=$first?$w:' and '.$w;
				$first=false;
			}
			$externalFilter.=')';
			$sql['where'].=$externalFilter;
			$sql['externalFilter']=$externalFilter;
			
		}
	}
	/**
	 * We create list where from external filter definition
	 * @param array $conf
	 * @param array $sql
	 */
	function getAdvancedSearchFromFilter(&$conf) {
		$json=$conf['piVars']['filter'];
		$filter=json_decode($json);
		//error_log(__METHOD__.':f '.print_r($filter,true));
		$advSearch=array();
		if (count($filter)) {
			$first=true;
			foreach($filter as $filterCriteria) {
				switch ($filterCriteria->o) {
					case '=':
					case 'like':
						$advSearch[$filterCriteria->f]=str_replace(array("'","%"),array("",""), $filterCriteria->v);
						break;
					default:
						//$w=($filterCriteria->t?$filterCriteria->t.'.':'').$filterCriteria->f.' '.$filterCriteria->o.' '.$filterCriteria->v;
						if (is_array($advSearch[$filterCriteria->f])) {
							$advSearch[$filterCriteria->f]['op']='>ts<';
							$advSearch[$filterCriteria->f]['valsup']=$filterCriteria->v;
						} else {
							$advSearch[$filterCriteria->f]=array();
							$advSearch[$filterCriteria->f]['op']=$filterCriteria->o;
							$advSearch[$filterCriteria->f]['val']=$filterCriteria->v;
						}
						break;
				}
				$first=false;
			}
		}
		//error_log(__METHOD__.':a '.print_r($advSearch,true));
		return $advSearch;
	}
	/**
	* getFieldSpecialConfFromType Gets the special RTE configurations in TCA  for a field of a record knowing it's type. The configuration is placed in the type array.
	*
	* @param	array $TCA	TCA configuration array of edited table
	* @param 	string $fN		string the fieldname to get the configurations for
	* @param	string $type type value of record
	* @return	array		the specialconf array
	* @author christophe Balisky christophe@balisky.org
	* @todo check if functions works on sublevel table fields...
	*/
	
	function getFieldSpecialConfFromType($TCA,$type,$fN) {
		// get the special configurations and check for an existing richtext configuration
		$specialConf=array();
		$showitem = $TCA['types'][$type]['showitem'] ? explode(',',$TCA['types'][$type]['showitem']) : explode(',',$TCA['types'][1]['showitem']); 
		foreach((array)$showitem as $fieldConfig) {
			$fC = explode(';',$fieldConfig);
			if(trim($fC[0])==$fN) {					  // if field is $fN
				foreach(explode(':',$fC[3]) as $sC) {
					if(substr($sC,0,8)=='richtext') {		// if there is a richtext configuration we found what we were looking for
						$buttons = substr(trim($sC),9,strlen(trim($sC))-10);
						$specialConf['richtext']['parameters'] = t3lib_div::trimExplode('|',$buttons);
					} else if(substr($sC,0,13)=='rte_transform') {
						$transConf = substr(trim($sC),14,strlen(trim($sC))-15);
						$specialConf['rte_transform']['parameters'] = t3lib_div::trimExplode('|',$transConf);
					}
				}
			}
		}
		return $specialConf;
	}
	
	// Handling INCOMING VARIABLES !!!
	// All variables must be indexed on pluginID !!! to avoid problems when 2 plugins are put in same page.
	// Variable priorites are :
	// pluginid Typoscript
	// default typoscript
	// piVars
	// POST //dangerous ...
	// GET  // dangerous ...
	// session (only if $keepvar true).
	
	function getMetaFeeditVar(&$conf,$varname,$keepvar=false) {
		$pluginId=$conf['pluginId'];
		$piVars=$conf['piVars'];
		$typoscript=$conf['typoscript.'];
		$res=Null;
		if ($keepvar) {
			// we look into session vars
			$vars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
			$vars=$vars[$GLOBALS['TSFE']->id];
			if (is_array($vars[$pluginId])) $res[$pluginId]=$vars[$pluginId][$varname];
		}
		// we look in the Get & post
		$gp=t3lib_div::_GP($varname);
		if ($gp) {
			if (is_array($res[$pluginId]) && is_array($gp)) {
				$res[$pluginId]=array_merge($res[$pluginId],$gp);
			} else {
				$res=$gp;
			}
		}
		// We look into the piVars
	
		if ($piVars[$varname]) {
			if (is_array($res[$pluginId]) && is_array($piVars[$varname][$pluginId])) {
				$res[$pluginId]=array_merge($res[$pluginId],$piVars[$varname][$pluginId]);
			} else {
				$res=$piVars[$varname];
			}
		}
		// we check if typoscript override is present
		if ($typoscript[$varname.'.']) $res=$typoscript[$varname.'.'];
		// we check variable against pluginId
		if (is_array($res)) {
			if (array_key_exists($pluginId,$res)) {
				$res=$res[$pluginId];
				//if (!$res && isset($res)) unset($res);
			} else {
					//if (isset($res)) unset($res);
		  }
		} else {
				//if (isset($res)) unset($res);
		}
		return $res;
	}
 
/*
 function getMetaFeeditGetPluginArrayVar(&$conf,&$var,$name,&$res){
 	$pluginId=$conf['pluginId'].'.';
 	
 	if (is_array($var) && array_key_exists($pluginId,$var) && array_key_exists($name,$var[$pluginId])) {
 		$res=$var[$pluginId][$name];
 		return true;
 	}
 	
 	// if not in pliginId we try default ...
 	
 	if (is_array($var) && array_key_exists('default.',$var) && array_key_exists($name,$var['default.'])) {
 		$res=$var['default.'][$name];
 		return true;
 	}
 	return false;
 
 }*/
 
	 function getMetaFeeditGetPluginArrayVar(&$conf,&$var,$name,&$res){
		$pluginId=$conf['pluginId'].'.';
		$return = false;

		if (is_array($var) && array_key_exists($pluginId,$var) && array_key_exists($name,$var[$pluginId])) {
			$plugidRes=$var[$pluginId][$name];
			$return = true;
		}
		
		if (is_array($var) && array_key_exists('default.',$var) && array_key_exists($name,$var['default.'])) {
			$defRes=$var['default.'][$name];
			$return = true;
		}
		
		if ($return) $res = $this->ts_array_merge($defRes, $plugidRes);
		return $return;
	 }
 
	/**
	 * Cette fonction merge les 2 tableau de typoscript en prenant soins de supprimer les clef qui ont un '.'
	 * pour les userFunc par exemple: 
	 * pluginid.champ = blablabla
	 * default.champ.userfunc = bliblibli
	 * supprime la valeur par defaut (car surclasse dans le pluginid)
	 * marche aussi dans l'autre sens
	 *
	 * @param arr	premier tableau (dans lequel on supprime les doublons
	 * @param arr	second tableau (dans lequel on trouve les valeur a chercher)
	 * @return arr	tableau fusionne des 2 autres
	 **/
	 function ts_array_merge($arr1, $arr2) {
		//on test si on a bien des tableaux
		if (is_array($arr1) && is_array($arr2)) {
			//tableau des clef par default, necessaire a la comparaison avec le tableau du plugid
			$defClef = array_keys($arr1);

			//on parcour les tableaux afin de faire le trie des clef existant
			foreach ($arr2 as $key => $value) {
				$chaine = (strpos($key, '.')===false)?$key:substr($key, 0, -1); // on recupere la chaine a comparer dans l'autre tableau sans le point final
				if (in_array($chaine, $defClef)) unset($arr1[$chaine]);
				elseif(in_array($chaine.'.', $defClef)) unset($arr1[$chaine.'.']);
			}
			
			// on assemble les 2 tableaux obtenu
			return array_merge($arr1, $arr2);
		}else return is_array($arr1)?$arr1:$arr2;
	}

	function getMetaFeeditVar2(&$conf,$varname,$keepvar=false) {
		$pluginId=$conf['pluginId'];
		$piVars=$conf['piVars'];
		$typoscript=$conf['typoscript.'];
  
		if ($keepvar) {
		  // we look into session vars
		  $vars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
		  $vars=$vars[$GLOBALS['TSFE']->id];
			if (is_array($vars[$pluginId])) $res=$vars[$pluginId][$varname];
		}

		// we look in the Get & post
		if (t3lib_div::_GP($varname)) {
			$res=t3lib_div::_GP($varname);
		}
  
		// We look into the piVars

		if ($this->getMetaFeeditGetPluginArrayVar($conf,$piVars,$varname,$tsres)) $res=$tsres;

		// we check if typoscript override is present
		if ($this->getMetaFeeditGetPluginArrayVar($conf,$typoscript,$varname,$tsres)) $res=$tsres;
		return $res;
	}

	/*
	* This function is called after a record is saved in fe_adminLib.
	* The function runs through the fields from fe_adminLibs dataArr and does some required stuff for the different fields types
	*/
	//function user_afterSave($content, &$conf) {	
	function user_afterSave($content, $conf) {
		$fe_adminLib = &$conf['parentObj'];
		$conf = $fe_adminLib->conf;
		$dataArr = &$fe_adminLib->dataArr;
		$table = $fe_adminLib->theTable;
		 
		foreach((array)$dataArr as $fN => $value) {
			switch((string)$conf['TCAN'][$table]['columns'][$fN]['config']['type']) {
				case 'group':
				if ($conf['TCAN'][$table]['columns'][$fN]['config']['internal_type'] == 'file') {
					//internal_type=file
					/**** DELETED FILES ****/
					// if files are deleted in the field, we also want to make sure they are deleted on the server
					if ($content['origRec']) {
						$diff = array_diff(explode(',', $content['origRec'][$fN]), explode(',', $content['rec'][$fN]) );
						foreach((array)$diff as $file) {
							$uploadPath = $conf['TCAN'][$table]['columns'][$fN]['config']['uploadfolder'];
							@unlink(PATH_site.$uploadPath.'/'.$file);
						}
					}
					/**** UPLOADED FILES ****/
					// if a new file is uploaded, we need to add to the database.

					$file = $dataArr[$fN.'_file'];

					if ($file) {

						$fV = $content['rec'][$fN] ? $content['rec'][$fN].','.$file : $file;
						$this->cObj->DBgetUpdate($table, $content['rec'][$conf['uidField']], array($fN => $fV), $fN, TRUE);
						// very important we set the data field (passed bu Ref) so that we have the data in the form..
						$dataArr[$fN]=$fV;
					}
				} else {
					if ($conf['TCAN'][$table]['columns'][$fN]['config']['MM']) {
						//its a MM relation
						$uids = explode(',', $dataArr[$fN]);
						$uid = $content['rec'][$conf['uidField']];
						$mmTable = $conf['TCAN'][$table]['columns'][$fN]['config']['MM'];
						// update the $fN in $table
						$this->cObj->DBgetUpdate($table, $uid, array($fN => count($uids)), $fN, TRUE);
						// update the MM table
						$GLOBALS['TYPO3_DB']->exec_DELETEquery($mmTable, 'uid_local='.intval($uid));
						$tempSorting=1;
						foreach((array)$uids as $foreign_uid) {
							$GLOBALS['TYPO3_DB']->exec_INSERTquery($mmTable, array('uid_local' => intval($uid), 'uid_foreign' => intval($foreign_uid), 'sorting'=>$tempSorting));
							$tempSorting++;
						}
					}
				}
				break;
			case 'select':

				if ($conf['TCAN'][$table]['columns'][$fN]['config']['MM']) {
					//its a MM relation
					$uids = explode(',', $dataArr[$fN]);
					// we handle empty relations here ...
					if (count($uids) == 1) {
						if (!$uids[0]) $uids = array();
						}
					$uid = $content['rec'][$conf['uidField']];
					$mmTable = $conf['TCAN'][$table]['columns'][$fN]['config']['MM'];
					// update the $fN in $table
					//$cObj = t3lib_div::makeInstance('tslib_cObj');
					$this->cObj->DBgetUpdate($table, $uid, array($fN => count($uids)), $fN, TRUE);
					// update the MM table
					$GLOBALS['TYPO3_DB']->exec_DELETEquery($mmTable, 'uid_local='.intval($uid));
					$tempSorting=1;
					foreach((array)$uids as $foreign_uid) {
						$GLOBALS['TYPO3_DB']->exec_INSERTquery($mmTable, array('uid_local' => intval($uid), 'uid_foreign' => intval($foreign_uid),  'sorting'=>$tempSorting));
						$tempSorting++;
					}
				}
				break;
			case 'inline':

				if ($conf['TCAN'][$table]['columns'][$fN]['config']['MM']) {
					//its a MM relation
					$uids = explode(',', $dataArr[$fN]);
					// we handle empty relations here ...
					if (count($uids) == 1) {
						if (!$uids[0]) $uids = array();
						}
					$uid = $content['rec'][$conf['uidField']];
					$mmTable = $conf['TCAN'][$table]['columns'][$fN]['config']['MM'];
					// update the $fN in $table
					//$cObj = t3lib_div::makeInstance('tslib_cObj');
					$this->cObj->DBgetUpdate($table, $uid, array($fN => count($uids)), $fN, TRUE);
					// update the MM table
					$GLOBALS['TYPO3_DB']->exec_DELETEquery($mmTable, 'uid_local='.intval($uid));
					$tempSorting=1;
					foreach((array)$uids as $foreign_uid) {
						$GLOBALS['TYPO3_DB']->exec_INSERTquery($mmTable, array('uid_local' => intval($uid), 'uid_foreign' => intval($foreign_uid),  'sorting'=>$tempSorting));
						$tempSorting++;
					}
				}
				break;
			}
		}

		// appeller fonction utilisateur avant mise a jour du template si elle est presente ...
		$var_temp_array = array($fe_adminLib, $treeArray);
		if ($fe_adminLib->conf['userFunc_afterSaveAndBeforeStatus']) t3lib_div::callUserFunction($fe_adminLib->conf['userFunc_afterSaveAndBeforeStatus'], $var_temp_array, $fe_adminLib);

		// this is here to handle update of template after save before status screen is shown !
		if ($fe_adminLib->cmd == 'create' || $fe_adminLib->cmd == 'edit') {
			$pluginId=$conf['pluginId'];
			$fe_adminLib->templateCode = $fe_adminLib->conf['templateContentOptions'];
			$fe_adminLib->templateCode = $this->replaceOptions($fe_adminLib->templateCode, $fe_adminLib->conf, 'edit', $fe_adminLib->conf['table'], $content['rec'][$conf['uidField']]);
			$fe_adminLib->markerArray['###HIDDENFIELDS###'] = '<input type="hidden" name="cmd['.$pluginId.']" value="'.htmlspecialchars('edit').'" />'. ($fe_adminLib->authCode?'<input type="hidden" name="aC['.$pluginId.']" value="'.htmlspecialchars($fe_adminLib->authCode).'" />':'').($fe_adminLib->conf['blogData']?'<input type="hidden" name="cameFromBlog['.$pluginId.']" value="1" />':'');
			//$fe_adminLib->markerArray['###HIDDENFIELDS###'] = '<input type="hidden" name="cmd['.$pluginId.']" value="'.htmlspecialchars('edit').'" />'. ($fe_adminLib->authCode?'<input type="hidden" name="aC['.$pluginId.']" value="'.htmlspecialchars($fe_adminLib->authCode).'" />':''). ($fe_adminLib->backURL?'<input type="hidden" name="backURL['.$pluginId.']" value="'.htmlspecialchars($fe_adminLib->backURL).'" />':'').($fe_adminLib->conf['blogData']?'<input type="hidden" name="cameFromBlog['.$pluginId.']" value="1" />':'');
		}

		// We handle T3 tree copy here :

		if ($fe_adminLib->cmd == 'create') {
			if ($fe_adminLib->conf['T3SourceTreePid'] && $fe_adminLib->conf['T3TreeTargetPid']) {
				$copyObj = t3lib_div::makeInstance('tx_metafeedit_treecopy');
				$treeArray = $copyObj->copyTree($content['rec'], $fe_adminLib->conf);
				if ($fe_adminLib->conf['T3TableHomePidField'] && $treeArray['rootId']) {
					$cObj = t3lib_div::makeInstance('tslib_cObj');
					$cObj->DBgetUpdate($table, $content['rec'][$conf['uidField']], array($fe_adminLib->conf['T3TableHomePidField'] => $treeArray['rootId']), $fe_adminLib->conf['T3TableHomePidField'], TRUE);
				}
			}

			// We handle group if we are editing a fe_user...

			if ($table == 'fe_users') {
				$uid = $content['rec'][$conf['uidField']];
				$grpId = $treeArray['grpId'];
				$grpOverride=$conf['create.']['overrideValues.']['usergroup'];

				//rajout pour prendre en compte override en ajout du usergroupe
				$grpId = $grpOverride?$grpOverride.','.$grpId:$grpId;

				$grp = $content['rec']['usergroup'];
				if ($grpId != '') $grp = $grp?$grp.','.$grpId:$grpId;
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, $conf['uidField'].'='.intval($uid), array('usergroup' => $grp));
			};
		}
		// we handle ldap syncro here

		/*if ($table == 'fe_users' && t3lib_extmgm::isLoaded('eu_ldap')) {



			$im = $dataArr['image'];
			$im .= $im?($dataArr['image_file']?','.$dataArr['image_file']:''):
			$dataArr['image_file'];

			if ($im) {
				$ima = t3lib_div::trimexplode(',', $im);
				$im = $ima[0];
				$uf = $conf['TCAN'][$table]['columns']['image_file']['config']['uploadfolder'];
				$jpegFile = PATH_site.$uf.'/'.$im;
				$fd = fopen ($jpegFile, "r");
				$fsize = filesize ($jpegFile);
				$jpegStr = fread ($fd, $fsize);
				fclose ($fd);
				$dataArr['ldapJpegPhoto'] = $jpegStr;
			}
			tx_euldap_div::initChar('');
			$servArr = tx_euldap_div::getLdapServers($fe_adminLib->thePid);
			tx_euldap_div::export_user($servArr, $dataArr, $fe_adminLib->thePid, true);

		} */
		// appeller fonction utilisateur si elle est presente ...
		$var_temp_array = array($fe_adminLib, $treeArray);
		if ($fe_adminLib->conf['userFunc_afterSave']) t3lib_div::callUserFunction($fe_adminLib->conf['userFunc_afterSave'], $var_temp_array, $fe_adminLib);
		// TODO CBY MERGE updated DATAARR with DATARR
		//$fe_adminLib->logErrors('User AfterSave : End');
		return $dataArr;
	}
	
	/**
	* getIconPath : return the type for $fn
	* file type depends on the file extension
	*
	* @param	[type]		$img: ...
	* @param	[type]		$conf: ...
	* @param	[type]		$size: ...
	* @return	[type]		...
	*/
	
	function getIconPath($img, &$conf, &$size) {
		$type = $this->type_from_file($img, $conf);
		if (!file_exists($img)) return t3lib_extMgm::siteRelPath('meta_feedit').'res/imagenotfound.jpg';
		switch ($type) {
			case 'image1' :
				return $img;
				break;
			case 'unknown' :
				$size = 0;
				return t3lib_extMgm::siteRelPath('meta_feedit').'res/disconnect.jpg';
				break;
			default :
				$size = 0;
				return $this->icon_from_type($type, $conf);
		}
	}

	/**
	* getMediaPlayer
	*
	* @param	[type]		$fn: ...
	* @param	[type]		$conf: ...
	* @return	[type]		...
	*/
	
	function getMediaPlayer($fn, &$conf) {
		if ($conf["map_player_to_ext."]) {
			foreach($conf["map_player_to_ext."] as $player => $ext) {
				$ext = explode("|", $ext);
				for ($i = 0; $i < count($ext); $i++) {
					$ext[$i] = str_replace(".", "\.", $ext[$i]);
					$ext[$i] = str_replace("+", "\+", $ext[$i]);
					if (preg_match('/'.$ext[$i].'$/i', $fn)) return $player;
				}
			}
		}
	}

	/**
	* type_from_file
	*
	* @param	[type]		$fn: ...
	* @param	[type]		$conf: ...
	* @return	[type]		...
	*/
	
	function type_from_file($fn, &$conf) {
		$rettype="unknown";
		if ($conf["map_type_to_ext."]) {
			foreach($conf["map_type_to_ext."] as $type => $ext) {
				$ext = explode("|", $ext);
				for ($i = 0; $i < count($ext); $i++) {
					$ext[$i] = str_replace(".", "\.", $ext[$i]);
					$ext[$i] = str_replace("+", "\+", $ext[$i]);
					if (preg_match('/'.$ext[$i].'$/i', $fn)) {
						$rettype= $type;
						break 2;
					}
				}
			}
		}
		return $rettype;
	}

	/**
	* icon_from_type :return the icon for $type
	*
	* @param	string		$type: ...
	* @param	array		$conf: configuration array
	* @return	array		$ret :
	*/
	
	function icon_from_type($type, &$conf) {
		if (array_key_exists($type, $conf["map_type_to_icon."]))
			return t3lib_extMgm::siteRelPath('meta_feedit').'res/'.$conf["map_type_to_icon."][$type];
		else
			return t3lib_extMgm::siteRelPath('meta_feedit').'res/disconnect.jpg';
	}
  
	// Cleans array of empty keys
	//MODIF CBY
	
	function clean_array($Array) {
		$ret=array();
		if (is_array($Array)) {
			foreach($Array as $el) {
				if ($el) $ret[]=$el;
			}
		}
		return $ret;
	}

	// We take care of the options of the MM selects and file selects  so that the selects content stays dynamic
	// this should be in fe_adminLib...
	
	function replaceOptions($templ,&$conf,$cmd,$table,$recuid) {
		$fieldArray = explode(',',$conf[$cmd.'.']['fields']);
		foreach((array)$fieldArray as $fN) { //runs through the different fields
			$fN=trim($fN);
			$type=(string)$conf['TCAN'][$table]['columns'][$fN]['config']['type'];
	  		switch($type) {
				case 'group':
			  		if($conf['TCAN'][$table]['columns'][$fN]['config']["internal_type"]=='file')	{
	
						// fetch data from table
						$feData =  $conf['inputvar.']['fedata'];
						$uid = $feData[$table][$conf['uidField']] ? $feData[$table][$conf['uidField']] : $conf['inputvar.']['rU'];
		 
						if (!$uid) $uid=$recuid;
						$uid = $uid ? $uid : (($conf['fe_userEditSelf'] && $table=='fe_users')?$GLOBALS['TSFE']->fe_user->user['uid']:''); // fe_userEditSelf ??
						$rec = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$uid);
		
						// we handle here file browser display accourding to TCA and field size...
						$size=0;
						if ($rec[$fN]) $size=sizeof(explode(",",$rec[$fN]));
		
						if($conf['TCAN'][$table]['columns'][$fN]['config']["maxitems"]<=$size) {
							$templ = $this->cObj->substituteSubpart($templ,'###FILE_BROWSER_'. $fN.'###','');
						}
		
						// make option tags from existing data DATABASE.
						$options = "";
						foreach(explode(",",$rec[$fN]) as $opt) $options .= $opt?'<option value="'.$opt.'">'.$opt.'</option>':'';
		
						// Data comes from data field (preview or error)
						if ($cmd=='create') {
							if ($feData[$table][$fN]) {
								foreach(explode(",",$feData[$table][$fN]) as $opt) {
									$opt2=explode('|',$opt);
									//$optsb=$opt;
									if (count($opt2)>1)
									{
									  $optsb=$opt2[1];
									  $options .= $opt?'<option value="'.$opt.'">'.$optsb.'</option>':'';
									} else {
									   $options .= $opt?'<option value="'.$opt.'">'.$opt.'</option>':'';
									}
								}
							}
						}
		
						// Data comes from upload  field (preview or error) and not saved !!
						if ($cmd=='create') {
		
							if ($_FILES['FE']['name'][$table][$fN.'_file'][0]) {
								$opt='###FIELD_'.$fN.'_file###';
								$options .= '<option value="'.$opt.'">'.$_FILES['FE']['name'][$table][$fN.'_file'][0].'</option>';
							}
						}
						$templ=$this->cObj->substituteMarker($templ,'###FIELD_'. $fN.'_OPTIONS###',$options);
					} else {
						$foreignTable = $conf['TCAN'][$table]['columns'][$fN]['config']["allowed"];
						if ($foreignTable) {
							$templ = $this->cObj->substituteMarker($templ,'###FIELD_'. $fN.'_OPTIONS###',$this->getSelectOptions($fN,$table,$conf));
						}
					}
					break;
	
		  		case 'select':
					$foreignTable = $conf['TCAN'][$table]['columns'][$fN]['config']['foreign_table'];
					if ($foreignTable) {
						if (!in_array($foreignTable,$conf['TCATables'])) {
							$this->makeTypo3TCAForTable($conf['TCAN'],$foreignTable);
							$conf['TCATables'][]=$foreignTable;
						}
						$templ = $this->cObj->substituteMarker($templ,'###FIELD_'. $fN.'_OPTIONS###',$this->getSelectOptions($fN,$table,$conf));
					}
					break;
				case 'inline':
					$foreignTable = $conf['TCAN'][$table]['columns'][$fN]['config']['foreign_table'];
					if ($foreignTable) {
						if (!in_array($foreignTable,$conf['TCATables'])) {
							$this->makeTypo3TCAForTable($conf['TCAN'],$foreignTable);
							$conf['TCATables'][]=$foreignTable;
						}
						$templ = $this->cObj->substituteMarker($templ,'###FIELD_'. $fN.'_OPTIONS###',$this->getSelectOptions($fN,$table,$conf));
					}
					break;
	  		}
		}
		return $templ;
	}
	/**
	 * We add php calculated fields to list row array
	 * @param unknown_type $menuRow
	 * @param unknown_type $conf
	 */
	function addPhpCalcFields(&$menuRow,$conf) {
		if (is_array($conf['list.']['phpcalcfields.'])) foreach($conf['list.']['phpcalcfields.'] as $field=>$calcsqlconf) {
			$res=t3lib_div::callUserFunction($calcsqlconf, $menuRow, $conf);
			$menuRow[$field]=$res;
		}
	}
	/**
	* getSelectOptions fills select options for <select tag> in relation fields.
	*
	* @param	string		$fN: field name for which we want to get select options
	* @param	string		$table: ...
	* @param	array		$conf: ...
	* @param	string		$forceEmptyOption: ...
	* @param	string		$forceVal: value of field wich determines selected value.
	* @return	[type]		...
	*/
	 
	function getSelectOptions($fN,$table,&$conf,$forceEmptyOption='',$forceVal=Null) {
		$feData = $conf['inputvar.']['fedata'];
		$uid = $feData[$table][$conf['uidField']] ? $feData[$table][$conf['uidField']] : $conf['inputvar.']['rU'];
		  //$uid=$uid?$uid:$conf['rU'];
		 // If we are editing front end user ...
		if ($conf['fe_userEditSelf'] && $table=='fe_users') $uid=$GLOBALS['TSFE']->fe_user->user['uid'];
		// here we handle the editUnique case ...
		$mmTable=$conf['TCAN'][$table]['columns'][$fN]['config']["MM"];
		if (!$uid && $conf['editUnique'] && $conf['inputvar.']['cmd']=='edit' && $mmTable) {
			$mmTable=$conf['TCAN'][$table]['columns'][$fN]['config']["MM"];
			$DBSELECT=$this->DBmayFEUserEditSelectMM($table,$GLOBALS['TSFE']->fe_user->user, $conf['allowedGroups'],$conf['fe_userEditSelf'],$mmTable,$conf).$GLOBALS['TSFE']->sys_page->deleteClause($table);
			$TABLES=$mmTable?$table.','.$mmTable:$table;
			// ajouter filtres sur relation
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $TABLES, '1 '.$lockPid.$DBSELECT, '', ' '.$mmTable.'.sorting ');
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)==1)		{
				while($menuRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))   {
					$uid=$menuRow[$conf['uidField']];
				}
			}
		}

		$rec = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$uid);
		$foreignTable = $conf['TCAN'][$table]['columns'][$fN]['config']["allowed"]?$conf['TCAN'][$table]['columns'][$fN]['config']["allowed"]:$conf['TCAN'][$table]['columns'][$fN]['config']['foreign_table'];
		if (count(t3lib_div::trimexplode(',',$foreignTable))>1) unset($foreignTable); // multiple Relations not handled CBY???
		if (!$foreignTable) {
			// fixed items
			if($conf['TCAN'][$table]['columns'][$fN]['config']["items"]) {
				// Get selected uids.
				$uids = array();
				if($feData[$table][$fN]) {								// from post var
	  				$uids = explode(",",$feData[$table][$fN]);
				} elseif(!is_null($rec)) {								// clean from DB
	  				$uids = explode(",",$rec[$fN]);
				} elseif($cmd=='create' && $conf['TCAN'][$table]['columns'][$fN]['config']['default']){
	  				$uids = explode(",",$conf['TCAN'][$table]['columns'][$fN]['config']['default']);
				}
				$items = $conf['TCAN'][$table]['columns'][$fN]['config']["items"];
				$selected='';
				if (!$this->is_extent($forceVal)) $selected='selected="selected"';
				$options = '<option value="" '.$selected.' >&nbsp;</option>';

				if($conf['TCAN'][$table]['columns'][$fN]['config']["itemsProcFunc"]) {	 // if itemsProcFunc is set to fill the select box
					$options = '';
					//$params = $conf['TCAN'][$table]['columns'][$fN];
					//$params['items'] = &$items;		  				
					//t3lib_div::callUserFunction($conf['TCAN'][$table]['columns'][$fN]['config']["itemsProcFunc"], $params, $this);
					$this->t3lib_TCEforms->procItems($items,$conf['TCAN'][$table]['columns'][$fN],$conf['TCAN'][$table]['columns'][$fN]['config'],$table,$feData[$table],$fN);
				}
				foreach((array)$items as $key => $item) {
					$selected = ((string)$item[1]===$forceVal)?'selected="selected"':"";
					$options .= '<option value="'.$item[1].'"'.$selected.'>'.$this->getLLFromLabel($item[0],$conf).'</option>';
				}
			} else {// unknown TCA config
				$options = '<option><em>Unknown TCA-configuration</em></option>';
			}

		} else { // We handle foreign table
			$label = $conf['label.'][$foreignTable]?$conf['label.'][$foreignTable]:$conf['TCAN'][$foreignTable]['ctrl']['label'];
			$label_alt = $conf['label_alt.'][$foreignTable]?$conf['label_alt.'][$foreignTable]:$conf['TCAN'][$foreignTable]['ctrl']['label_alt'];
			$label_alt_force = $conf['label_alt_force.'][$foreignTable]?$conf['label_alt_force.'][$foreignTable]:$conf['TCAN'][$foreignTable]['ctrl']['label_alt_force'];
			$whereClause='';

			/*if ($uid) {
					$whereClause .= $conf['TCAN'][$table]['whereClause.'][$fN]?$conf['TCAN'][$table]['whereClause.'][$fN]:$conf['TCAN'][$table]['columns'][$fN]['config']["foreign_table_where"];
			}
		  	else
			{
					  	$whereClause .= $conf['TCAN'][$table]['columns'][$fN]['config']["foreign_table_where"];
			}*/
			$whereClause=$this->getWhereClause($uid,$table,$fN,$rec,$conf);
	  
			// gets uids of selected records.
			$uids = array();
			if($feData[$table][$fN]) {								// from post var
				$uids = explode(",",$feData[$table][$fN]);
			} elseif($conf['TCAN'][$table]['columns'][$fN]['config']["MM"] && $uid) {  // from mm-relation
				$mmTable = $conf['TCAN'][$table]['columns'][$fN]['config']['MM'];
				$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$mmTable,$mmTable.'.uid_local=\''.$uid.'\'','', ' '.$mmTable.'.sorting ');
				if (mysql_error())	debug(array(mysql_error(),$query),'getFormFieldCode()::field='.$fN);
				$cnt=mysql_num_rows($MMres);
				if($conf['debug'] && $cnt!=$rec[$fN]) debug("class.tx_metafeedit_lib.php::tx_metafeedit_lib->getSelectOptions(): Wrong number ($cnt<>".$rec[$fN].") of selections reached for  field $fN of table $table, mm:  $mmTable");
				while($MMrow = mysql_fetch_assoc($MMres)) $uids[] = $MMrow["uid_foreign"];
			} else {														// clean from DB
				$uids = explode(",",$rec[$fN]);
			}
			if (function_exists($this->getOverrideValue)){
				$overrideuids=$this->getOverrideValue($fN,$conf['inputvar.']['cmd'],$conf,$this->cObj);
			}
			if ($overrideuids) $uids=$this->array_merge_recursive2($uids,t3lib_div::trimexplode(",",$overrideuids));
			
			$options=$conf['TCAN'][$table]['columns'][$fN]['config']['emptylabel']?'<option value="" '.($conf['TCAN'][$table]['columns'][$fN]['config']['emptylabeldisabled']?'disabled="disabled"':'').'>'.$this->getLLFromLabel($conf['TCAN'][$table]['columns'][$fN]['config']['emptylabel']).'</option>':'';

			if (($conf['TCAN'][$table]['columns'][$fN]['config']["minitems"]==0 && $conf['TCAN'][$table]['columns'][$fN]['config']["maxitems"]<2) || $forceEmptyOption) $options.='<option value="">&nbsp;</option>';

			$statictable=0;
			if (strpos($foreignTable,'static_')===0) {
				$statictable=1;
			}

			// ajouter filtres sur relation
			// ajouter gestion des donnees liees...
			//$ef=$this->cObj->enableFields($foreignTable);
			//Modif by CMD - parametrage manuelle des champ gï¿½rï¿½ par la rï¿½cupï¿½ration des MM
			$ignorArr = array();
			if ($conf['edit.']['dontUseHidden']) {
				$show_hidden = 1;
			}
			if ($conf['edit.']['dontUseDate']) {
				$ignorArr['starttime'] = 1;
				$ignorArr['endtime'] = 1;
			}
			$ef = $GLOBALS['TSFE']->sys_page->enableFields($foreignTable, $show_hidden?$show_hidden:($table=='pages' ? $GLOBALS['TSFE']->showHiddenPage : $GLOBALS['TSFE']->showHiddenRecords), $ignorArr);

			//permet d'appeller une fonction pour trier le tableau de resultat
			if ($conf['select.'][$fN.'.']['userFunc_selectQuery'] && ($conf['inputvar.']['cmd']=='edit' || $conf['inputvar.']['cmd']=='create')) {
				$temp = array('ef' => $ef, 'whereClause' => $whereClause,'rec'=>$rec);
				t3lib_div::callUserFunction($conf['select.'][$fN.'.']['userFunc_selectQuery'], $res, $temp);
			} else {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$foreignTable,'1=1 '.$ef.' '.$whereClause);
				if (mysql_error()) {
					debug(array(mysql_error()),'getSelectOptions:field='.$fN);
				}
			}
			
			// here we should have a valid mysql result 
			$sortAux=array();
			$sortTab=array();
			$n=0;
			$max=400;
			// MODIF CBY
			while($resRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

				if ($n>$max) break;
					$n++;
					if ($conf['general.']['labels.'][$fN.'.']['userFunc_alterRow']) {
						t3lib_div::callUserFunction($conf['general.']['labels.'][$fN.'.']['userFunc_alterRow'], $resRow, $conf);
					}
					$resLabel = $resRow[$label];
					$resLabel_alt = $resRow[$label_alt];

					// MODIF  CBY for TS configured labels of combos ...
					if ($conf['general.']['labels.'][$fN]) {
						$resLabel='';
						$tslabels=t3lib_div::trimexplode(',',$conf['general.']['labels.'][$fN]);	
						$resRow=$this->user_processDataArray($resRow,$conf,$foreignTable?$foreignTable:$table);
						foreach ($tslabels as $tslabel) {
								//$resRow=$this->processDataArray($resRow,$conf);
								$resLabel.=$resLabel?" ".($resRow["EVAL_".$tslabel]?$resRow["EVAL_".$tslabel]:$resRow[$tslabel]):($resRow["EVAL_".$tslabel]?$resRow["EVAL_".$tslabel]:$resRow[$tslabel]);
								$resLabel_alt.=$resLabel_alt ?" ".($resRow["EVAL_".$tslabel]?$resRow["EVAL_".$tslabel]:$resRow[$tslabel]):($resRow["EVAL_".$tslabel]?$resRow["EVAL_".$tslabel]:$resRow[$tslabel]);
						}
					}
					if ($statictable) {
						$code = $resRow['lg_iso_2'].($resRrow['lg_country_iso_2']?'_'.$resRow['lg_country_iso_2']:'');
						$resLabel = $this->getLL('language_'.$code,$conf);
						if( !$resLabel ) { $resLabel = $GLOBALS['TSFE']->csConv($resRow['lg_name_en'], $this->staticInfoCharset); } // CBY  what is this static Info charset ???
					}					
					$tempLabel = $label_alt_force ? $resLabel.', '.$resLabel_alt : $resLabel;
					$tempLabel = $tempLabel ? $tempLabel : $resLabel_alt;
					$resRow['tx_metafeedit_resLabel']=$tempLabel;
					$sortAux[]=$tempLabel;
					$sortTab[]=$resRow;
			}
			mysql_free_result($res);

			if ($conf['general.']['list.']['userFunc_alterSortTabs']) {
				//TODO  test t3lib_div::callUserFunction($conf['general.']['list.']['userFunc_alterSortTabs'], $sortTab, $conf);
				$_params = array ('sortTab' => &$sortTab, 'sortAux' => &$sortAux, 'table' => $table, 'fN' => $fN , 'forceEmptyOption'=> $forceEmptyOption ,'forceVal' => $forceVal ); 
				t3lib_div::callUserFunction($conf['general.']['list.']['userFunc_alterSortTabs'],  $_params , $conf);
			}
			
			/*
			if ($statictable) {
				//array_multisort($sortAux, SORT_ASC, $sortTab);

			}
			*/
			
			if (!$conf['select.'][$fN.'.']['dontDoOrder'] && ($conf['inputvar.']['cmd']=='edit' || $conf['inputvar.']['cmd']=='create' || $conf['inputvar.']['cmd']=='list')) {
				array_multisort($sortAux, SORT_ASC, $sortTab);
			}
			foreach($sortTab as $resRow) {	
				
				$selected = in_array($resRow[$conf['uidField']],$uids)?"selected":"";
				$selected=$selected?$selected:($resRow[$conf['uidField']]==$forceVal?'selected':'');
				$options .= '<option value="'.$resRow[$conf['uidField']].'" '.$selected.'>'.$resRow['tx_metafeedit_resLabel'].'</option>';
			}
			if ($conf['general.']['list.']['userFunc_alterSortOptions']) {
					t3lib_div::callUserFunction($conf['general.']['list.']['userFunc_alterSortOptions'], $sortTab,$options, $conf);
			}
		}
		return $options;
	}

	/**
	 * getWhereClause : Retruns whereclause of field from TCA and Typoscript specialisation ...
	 *
	 * @param	string :uid de l'enregistrement ???
	 * @param	string : table name 
	 * @param	string : fieldName
	 * @return	array ; data array ...		...
	 * @return	array ; configuration array ...		...
	 */
	 
	function getWhereClause($uid,$table,$fN,$rec,&$conf) {
		if ($uid) {
			$whereClause = $conf['TCAN'][$table]['whereClause.'][$fN]?$conf['TCAN'][$table]['whereClause.'][$fN]:$conf['TCAN'][$table]['columns'][$fN]['config']['foreign_table_where'];
		} else {
			$whereClause = $conf['TCAN'][$table]['columns'][$fN]['config']["foreign_table_where"];
		}
		
		// here must check if i should use local PID ???
		// here must decide storagePid Strategy !!!
		
	  	//$whereClause = $whereClause ? $whereClause : (intval($storageAndSiteroot["_STORAGE_PID"])?" AND pid = ###STORAGE_PID### ":"");
		
		if ($conf['whereClause.'][$fN] && (($uid && is_array($rec)) || $uid==0 || is_array($GLOBALS['TSFE']->fe_user->user))) $whereClause =$conf['whereClause.'][$fN]; 
		$storageAndSiteroot = $GLOBALS["TSFE"]->getStorageSiterootPids();
		
		// Handle PidHandler Paths here
		
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['ARDDESKTOP']['extensionpids']) && t3lib_extmgm::isLoaded('ard_mcm') ) {
   			require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/Lib/PidHandler.php');
			
			$pidHandler=t3lib_div::makeInstance('Tx_MetaFeedit_Lib_PidHandler');
			foreach($GLOBALS['TYPO3_CONF_VARS']['ARDDESKTOP']['extensionpids'] as $extension=>$extensionconf) {
				$defaultpid=0;
				//$root=$extensionconf['rootpid']?$extensionconf['rootpid']:$defaultpid;
				if (is_array($extensionconf['pidpaths'])) foreach($extensionconf['pidpaths'] as $path) {
			 		$pid=$pidHandler->getPid($path);
			 		//echo '###gv_'.$extension.'_'.implode('_',explode('/',$path)).'### ---'.$pid;
			 		$whereClause=str_replace('###gv_'.$extension.'_'.implode('_',explode('/',$path)).'###',intval($pid),$whereClause);
				}
				if (is_array($extensionconf['grouppaths'])) foreach($extensionconf['grouppaths'] as $path) {
					$uid=$pidHandler->getGroupUid($path);
					$path=str_replace(' ','_',$path);
			 		$whereClause=str_replace('###gv_gr_'.$extension.'_'.implode('_',explode('/',$path)).'###',intval($pid),$whereClause);
				}
			}
		}
		$whereClause = str_replace('###CURRENT_PID###',intval($storageAndSiteroot["_STORAGE_PID"]),$whereClause); // replaced with STORAGE_PID cause it makes more sense ;)
		$whereClause = str_replace('###META_PID###',intval($conf['pid']),$whereClause);
		$whereClause = str_replace('###STORAGE_PID###',intval($storageAndSiteroot["_STORAGE_PID"]),$whereClause);
		$whereClause = str_replace('###THIS_UID###',intval($uid),$whereClause);
		$whereClause = str_replace('###SITEROOT###',intval($storageAndSiteroot['_SITEROOT']),$whereClause);
		if ($uid && is_array($rec)) $whereClause=$this->cObj->substituteMarkerArray($whereClause,$this->cObj->fillInMarkerArray(array(), $rec, '', TRUE, 'FIELD_', $this->recInMarkersHSC));
		if (is_array($GLOBALS['TSFE']->fe_user->user)) $whereClause=$this->cObj->substituteMarkerArray($whereClause,$this->cObj->fillInMarkerArray(array(), $GLOBALS['TSFE']->fe_user->user, '', TRUE, 'FEUSER_FIELD_', $this->recInMarkersHSC));
		
		//hook to call user function modyfying where string
		if ($conf['whereClause.'][$fN.'.']['userFunc_selectWhere'] && ($conf['inputvar.']['cmd']=='edit' ||$conf['inputvar.']['cmd']=='create')) {
			$conf['list.']['gsowhereString']=$whereClause;
			t3lib_div::callUserFunction($conf['whereClause.'][$fN.'.']['userFunc_selectWhere'],$conf,$conf['parentObj']);
			$whereClause=$conf['list.']['gsowhereString'];
		}
		
		return $whereClause;
	}

	/**
	* Removes all trailing dots recursively from TS settings array
	* TODO Explain why we remove the dots.
	*
	* @param array $setup The settings array
	* @return void
	*/
	public function postProcessSettings(array $settings) {
		$processedSettings = array();
		// TODO Check if the t3lib_div::removeDotsFromTS() fits for this purpose (using rtrim() for removing trailing dots)
		foreach ($settings as $key => $value) {
			if (substr($key, -1) === '.') {
				$keyWithoutDot = substr($key, 0, -1);
				$processedSettings[$keyWithoutDot] = self::postProcessSettings($value);
				if (array_key_exists($keyWithoutDot, $settings)) {
					$processedSettings[$keyWithoutDot]['_typoScriptNodeValue'] = $settings[$keyWithoutDot];
					unset($settings[$keyWithoutDot]);
				}
			} else {
				$keyWithDot = $key . '.';
				if (array_key_exists($keyWithDot, $settings)) {
					$processedSettings[$key] = self::postProcessSettings($settings[$keyWithDot]);
					$processedSettings[$key]['_typoScriptNodeValue'] = $value;
					unset($settings[$keyWithDot]);
				} else {
					$processedSettings[$key] = $value;
				}
			}
		}
		return $processedSettings;
	}

	/**
		*  Store the setfixed vars and return a replacement hash
		*/
	function setShortUrl($vars) {
		global $TYPO3_DB;

			// create a unique hash value
		$regHash_array = t3lib_div::cHashParams(t3lib_div::implodeArrayForUrl('',$vars));
		$regHash_calc = t3lib_div::shortMD5(serialize($regHash_array),20);
			// and store it with a serialized version of the array in the DB
		$res = $TYPO3_DB->exec_SELECTquery('md5hash','cache_md5params','md5hash='.$TYPO3_DB->fullQuoteStr($regHash_calc,'cache_md5params'));
		if (!$TYPO3_DB->sql_num_rows($res))  {
			$insertFields = array (
				'md5hash' => $regHash_calc,
				'tstamp' => time(),
				'type' => 99,
				'params' => serialize($vars)
			);
			$TYPO3_DB->exec_INSERTquery('cache_md5params',$insertFields);
		}
		$TYPO3_DB->sql_free_result($res);
		return $regHash_calc;
	}
	
	/**
	*  Get the stored variables using the hash value to access the database
	*/
	function getShortUrl($regHash) {
		global $TYPO3_DB;

			// get the serialised array from the DB based on the passed hash value
		$varArray = array();
		$res = $TYPO3_DB->exec_SELECTquery('params','cache_md5params','md5hash='.$TYPO3_DB->fullQuoteStr($regHash,'cache_md5params'));
		while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			$varArray = unserialize($row['params']);
		}
		$TYPO3_DB->sql_free_result($res);

			// convert the array to one that will be properly incorporated into the GET global array.
		$retArray = array();
		foreach($varArray as $key => $val)	{
			$search = array('[%5D]', '[%5B]');
			$replace = array('\']', '\'][\'');
			$newkey = "['" . preg_replace($search, $replace, $key);
			eval("\$retArray" . $newkey . "='$val';");
		}
		return $retArray;
	}	// getShortUrl


	/**
	*  Get the stored variables using the hash value to access the database
	*/
	function deleteShortUrl ($regHash) {
		global $TYPO3_DB;

		if ($regHash != '')	{
			// get the serialised array from the DB based on the passed hash value
			$TYPO3_DB->exec_DELETEquery('cache_md5params','md5hash='.$TYPO3_DB->fullQuoteStr($regHash,'cache_md5params'));
		}
	}


	/**
	*  Clears obsolete hashes used for short url's
	*/
	function cleanShortUrlCache () {
		global $TYPO3_DB;

		$shortUrlLife = intval($this->conf['shortUrlLife']) ? strval(intval($this->conf['shortUrlLife'])) : '30';
		$max_life = time() - (86400 * intval($shortUrlLife));
		$res = $TYPO3_DB->exec_DELETEquery('cache_md5params', 'tstamp<' . $max_life . ' AND type=99');
	}	// cleanShortUrlCache
	
	
	/**
	* Language override functions ...
	* getLLFromLabel : Gets Language label for key...
	*
	* @param	[string]		$key: key for which we are looking for translation ...
	* @param	[array]			$conf: configuration array
	* @return	[string]		$label: language translation found, if not found we return the key;
	*/

	function getLLFromLabel($label,&$conf) {
		if($conf['debug.']['langArray']) return "?$label?";
		$labela=explode(':',$label);
		$key=end($labela);
		$label2=str_replace('.','_',end($labela));
		if ($conf['LOCAL_LANG']['langoverride'][$conf['LLkey']][$key]) {
			//error_log(__METHOD__.":OVERRIDE1!");
			return $conf['LOCAL_LANG']['langoverride'][$conf['LLkey']][$key];
		}
		// user override of language labels
		if (isset($conf['LOCAL_LANG'][$conf['LLkey']][$label2])) {
			//error_log(__METHOD__.":OVERRIDE2L!!");
			$ret=$conf['LOCAL_LANG'][$conf['LLkey']][$label2];
		} else {
			if ($this->langHandler) {
				//error_log(__METHOD__.":LLHANDLER sL!!");
				$ret=$this->langHandler->sL($label);
			} else {
				$ret=$GLOBALS['TSFE']->sL($label);
			}
			$ret=$ret?$ret:"$label";
			if ($ret==$label) {
				// We didn't find label...
				$labela=explode('.',$label);
				$label=end($labela);
				if ($this->langHandler) {
					//error_log(__METHOD__.":LLHANDLER gll !!");
					$ret=$this->langHandler->gll($label,$this->extKey);
				} else {
					$ret=$GLOBALS['TSFE']->getLLL($label,$conf['LOCAL_LANG']);
				}
				//$GLOBALS['TSFE']->sL($label)?$GLOBALS['TSFE']->sL($label):"$label";
			}
		}
		//error_log(__METHOD__.":".$label." ".$conf['LLkey']." ".$GLOBALS['TSFE']->lang." , $ret");
		if (version_compare($GLOBALS['TYPO_VERSION'], '4.5.0', '>') && is_array($ret)) {
			$ret=$ret[0]['target'];
		}
		return $ret;
	}

	/**
	* getLL : Gets Language label for key...
	*
	* @param	[string]		$key: key for which we are looking for translation ...
	* @param	[array]			$conf: configuration array
	* @return	[string]		$label: language translation found, if not found we return the key;
	*/
	
	function getLL($key,&$conf)	{
		if($conf['debug.']['langArray']) return "?*$key*?:".$GLOBALS['TSFE']->getLLL($key,$conf['LOCAL_LANG']); // if we are debugging language array we show keys of all fields enveloped in "?".
		//error_log(__METHOD__.":$key".print_r($conf['LOCAL_LANG']['langoverride'][$conf['LOCAL_LANG']],true));
		
		if ($this->langHandler) {
			$label=$this->langHandler->gll($key,$this->extKey);
		} else {
			if ($conf['LOCAL_LANG']['langoverride'][$conf['LOCAL_LANG']][$key]) {
				return $conf['LOCAL_LANG']['langoverride'][$conf['LOCAL_LANG']][$key];
			}
			$label=$GLOBALS['TSFE']->getLLL($key,$conf['LOCAL_LANG']);
		}
		
		return isset($label) ? $label : "$key";
	}

	/**
	* is_extent : tests if $var exists...
	*
	* @param	[unkown]	$var: $variable to test
	* @return	[boolean]	$var is set or not.
	*/
	
	function is_extent($var){
		if (isset($var)){
			if ( empty($var) && ($var !== 0) && ($var !== '0') ) return FALSE;
			else return TRUE;
		} else return FALSE;
	}

	//TO DO  WORKFLOW ENGINE

	// htmpspecialchars for XHTML support
	
	function hsc(&$conf,$val) {
		if ($conf['general.']['xhtml']) $val=htmlspecialchars($val);
		return $val;
	}

	//update session vars :
	
	function updateSessionVars(&$conf) {
		$metafeeditvars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
		$metafeeditvars[$GLOBALS['TSFE']->id][$conf['pluginId']]=array_merge($metafeeditvars[$GLOBALS['TSFE']->id][$conf['pluginId']],$conf['inputvar.']);		
		//if (!is_array($metafeeditvars[$GLOBALS['TSFE']->id][$conf['pluginId']])) $metafeeditvars[$GLOBALS['TSFE']->id][$pluginId]=$conf['inputvar.'];
		$GLOBALS["TSFE"]->fe_user->setKey('ses','metafeeditvars',$metafeeditvars);
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
	}
	/**
	 * hasActions, can only be called from fe_adminLib
	 *
	 * @param	array		$conf: ...
	 * @return	boolean		...
	 */	
	function hasListActions($conf) {
		return (!$conf['no_action'] && ((($conf['disableEdit'] && $conf['edit.']['preview']) || !$conf['disableEdit']) || $conf['list.']['recordactions'] || $conf['list.']['batchactions']));
	}
	//ACTIONS-LIST-LIB
	/**
	 * getListItemActionsLib, can only be called from fe_adminLib
	 *
	 * @param	array		$conf: ...
	 * @param	[type]		$caller: ...
	 * @return	[type]		...
	 */
	function getListItemActionsLib(&$conf) {
		$ret='';
		$obs=t3lib_div::trimexplode(':',$conf['inputvar.']['sort']);
		// we only show reset order by link if order bys have been set
		if ($this->hasListActions($conf)) {
			$ret='<th class="mfdt-actions">'.$this->getLL('actions',$conf). ($conf['list.']['sortFields']&&$obs[0] ? '<div class="tx-metafeedit-link tx-metafeedit-link-resetob"><a class="mtf-rstob" href="###FORM_URL_NO_PRM###&amp;'.$this->prefixId.'[resetorderby]['.$conf['pluginId'].']=1">'. $this->getLL('order_by_reset',$conf) .'</a></div>' : '').'</th>';  // rsg
		}
		return $ret;
	}
	
	/**
	 * getASResetAction
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @return	[type]		...
	 */
	function getListASResetAction(&$conf,&$caller) {
		//$ret='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('advancedSearch-action').' '.$caller->pi_getClassName('as_reset').'"><a href="###FORM_URL_NO_PRM###&amp;'.$this->prefixId.'[reset]['.$conf['pluginId'].']=1">'.$this->getLL("advanced_search_reset",$conf).'</a></div>';
		$ret='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('advancedSearch-action').' '.$caller->pi_getClassName('as_reset').'"><button name="'.$this->prefixId.'[reset]['.$conf['pluginId'].']" type="submit" value="1">'.$this->getLL("advanced_search_reset",$conf).'</button></div>';
		return $ret;
	}
	/**
	 * getListItemActions
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @param	[type]		$markerArray: ...
	 * @return	[type]		...
	 */
	function getListItemActions(&$conf,&$caller, &$markerArray) {
		$pluginId=$conf['pluginId'];
		$ret='';
		$editLinkId="link-edit";
		// General ACTION FLAG
		if (!$conf['no_action']) {
			// Check boxes for batch mode
			if ($conf['list.']['batchactions']) {
				$ret.='<input type="checkbox" class="mfcheck" title="'.$this->getLL('listBatchSel',$conf).'" name="mfcheck['.$pluginId.'][]" value="'.$conf['recUid'].'" />';
			}
			// DELETE ACTION
			if (!$conf['disableDelete'] && !$conf['disableEditDelete'] && !$conf['delete.']['hide']) {
				//$backurl=$this->makeFormTypoLink($conf,"&rU[$pluginId]=".$conf['recUid']);
				//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=delete&preview[$pluginId]=1&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
				$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=delete&preview[$pluginId]=1&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']);
				$act=' <div  class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-delete').' '.$caller->pi_getClassName('link-delete-list').'"><a class="jqMFDelModal" href="'.$url.'">'.$this->getLL("edit_delete_label",$conf).'</a></div>';
				$markerArray['###ACTION_DELETE###']=$act;
				$ret.=$act;
				$editLinkId="link-edit";
			}

			if ($conf['edit.']['preview'] && $conf['disableEdit']) {
				$preview="&preview[$pluginId]=1";
				$editLinkId="link-consult";
			}

			// EDIT ACTION
 			if (($conf['disableEdit'] && $conf['edit.']['preview']) || !$conf['disableEdit'] ) {
				//$backurl=$this->makeFormTypoLink($conf,"");
				//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$preview."&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
 				$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$preview."&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']);
				$act='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName($editLinkId).'"><a class="jqMFModal" href="'.$url.'" title="'.$this->getLL($editLinkId,$conf).'">'.$this->getLL($editLinkId,$conf).'</a></div>';
				$markerArray['###ACTION_EDIT###']=$act;
				$ret.=$act;
			}

			// MANUAL ORDER ACTION
			if ($conf['TCAN'][$conf['table']]['ctrl']['sortby'] && !$conf['disableEdit']) {
				//$backurl=$this->makeFormTypoLink($conf,"&rU[$pluginId]=".$conf['recUid']);
				//$url_up=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&orderDir[$pluginId]=up&orderU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
				//$url_down=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&orderDir[$pluginId]=down&orderU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
				$url_up=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&orderDir[$pluginId]=up&orderU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']); //."&backURL[$pluginId]=".rawurlencode($backurl));
				$url_down=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&orderDir[$pluginId]=down&orderU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']);//."&backURL[$pluginId]=".rawurlencode($backurl));
				$act=' <div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-order-up').'"><a href="'.$url_up.'">'.$this->getLL("edit_order_up_label",$conf).'</a></div>';
				$act.=' <div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-order-down').'"><a href="'.$url_down.'">'.$this->getLL("edit_order_down_label",$conf).'</a></div>';
				$markerArray['###ACTION_SORTING###']=$act;
				$ret.=$act;
			}

			// USER ACTIONS
			if ($conf['list.']['recordactions']) {

				$ActionArr=t3lib_div::trimexplode(chr(10),$conf['list.']['recordactions']);
				foreach($ActionArr as $action) {
					$cmdarr=t3lib_div::trimexplode('|',$action);
					$actionLib=$cmdarr[0];
					$actionUrl=$cmdarr[1];
					$extraParams='';
					if (isset($cmdarr[2])) {
						$extraParams=$cmdarr[2];
					}
					$astdconf=$conf[$conf['cmdmode'].'.']['actionStdWrap.'][$cmdarr[0].'.'];
					$act='<a href="'.$actionUrl.'" '.$extraParams.'>'.$this->getLL($actionLib,$conf).'</a>';
					if (is_array($astdconf)) $act=$this->cObj->stdWrap($this->getLL($actionLib,$conf), $astdconf);
					//$act='<br/><div  class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-'.$actionLib).' '.$caller->pi_getClassName('link-'.$actionLib.'-list').'"><a href="'.$actionUrl.'">'.$this->getLL($actionLib,$conf).$actionLib.'</a></div>';
					$act='<div  class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-'.$actionLib).' '.$caller->pi_getClassName('link-'.$actionLib.'-list').'">'.$act.'</div>';
					$markerArray['###ACTION_'.$actionLib.'###']=$act;
					$ret.=$act;
				}
			}

			$markerArray['###ACTION_BLOG###']=$this->getBlogActions($conf,$caller,$conf['recUid']);
			$ret.=$markerArray['###ACTION_BLOG###'];
			$ret=$ret?'<div class="'.$caller->pi_getClassName('actions').'">'.$ret.'</div>':'';
			if (!$conf['list.']['nbCols']) $ret=$ret?'<td>'.$ret.'</td>':'';

		}
		return $ret;
	}

	/**
	 * getGridItemActions
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @param	[type]		$markerArray: ...
	 * @return	[type]		...
	 */
		function getGridItemActions(&$conf,&$caller, &$markerArray) {
		$pluginId=$conf['pluginId'];
		$ret='';
		$editLinkId="link-edit";
		// General ACTION FLAG
		if (!$conf['no_action']) {
			// DELETE ACTION
			if (!$conf['disableDelete'] && !$conf['disableEditDelete']) {
				//$backurl=$this->makeFormTypoLink($conf,"&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']);
				//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=delete&preview[$pluginId]=1&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
				$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=delete&preview[$pluginId]=1&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']);

				$act=' <div  class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-delete').' '.$caller->pi_getClassName('link-delete-list').'"><a href="'.$url.'">'.$this->getLL("edit_delete_label",$conf).'</a></div>';
				$markerArray['###ACTION_DELETE###']=$act;
				$ret.=$act;
				$editLinkId="link-edit";
			}

			if ($conf['edit.']['preview'] && $conf['disableEdit']) {
				$preview="&preview[$pluginId]=1";
				$editLinkId="link-consult";
			}

			// EDIT ACTION
 			if (($conf['disableEdit'] && $conf['edit.']['preview']) || !$conf['disableEdit'] ) {
				//$backurl=$this->makeFormTypoLink($conf,$conf['GLOBALPARAMS']);
				//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$preview."&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
				$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$preview."&rU[$pluginId]=".$conf['recUid'].$conf['GLOBALPARAMS']); //."&backURL[$pluginId]=".rawurlencode($backurl));
 				$act='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName($editLinkId).'"><a href="'.$url.'">'.$this->getLL($editLinkId,$conf).'</a></div>';
				$markerArray['###ACTION_EDIT###']=$act;
				$ret.=$act;
			}

			// USER ACTIONS
			if ($conf['list.']['recordactions']) {

				$ActionArr=t3lib_div::trimexplode(chr(10),$conf['list.']['recordactions']);
				foreach($ActionArr as $action) {
					$cmdarr=t3lib_div::trimexplode('|',$action);
					$actionLib=$cmdarr[0];
					$actionUrl=$cmdarr[1];
					$act='<br/><div  class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-'.$actionLib).' '.$caller->pi_getClassName('link-'.$actionLib.'-list').'"><a href="'.$actionUrl.'">'.$this->getLL($actionLib,$conf).$actionLib.'</a></div>';
					$markerArray['###ACTION_'.$actionLib.'###']=$act;
					$ret.=$act;
				}
			}

			$markerArray['###ACTION_BLOG###']=$this->getBlogActions($conf,$caller,$conf['recUid']);
			$ret.=$markerArray['###ACTION_BLOG###'];
			$ret=$ret?'<div class="'.$caller->pi_getClassName('actions').'">'.$ret.'</div>':'';
			if (!$conf['list.']['nbCols']) $ret=$ret?'<td>'.$ret.'</td>':'';

		}
		return $ret;
	}

	/**
	 * getBlogActions
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @param	[type]		$rU: ...
	 * @return	[type]		...
	 */
	function getBlogActions(&$conf,&$caller,$rU ) {
		$pluginId=$conf['pluginId'];
		// Blog ACTION
		$act='';
		if ($conf['blog.']['showComments'] ) {
			//$backurl=$this->makeFormTypoLink($conf,"");
			//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&preview[$pluginId]=1&rU[$pluginId]=".$rU.$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
			//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&blog[$pluginId]=1&rU[$pluginId]=".$rU.$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
			$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit&blog[$pluginId]=1&rU[$pluginId]=".$rU.$conf['GLOBALPARAMS']);
			$act='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('blog').'"><a href="'.$url.'">'.$this->blogCommentCount($conf['table'],$rU).'&nbsp;'.$this->getLL('blogcomments',$conf).'</a></div>';
		}
		return $act;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @return	[type]		...
	 */
	function getListTopActions(&$conf,&$caller) {
		return $this->getListBottomActions($conf,$caller,1);
	}

	/**
	 * getGridTopActions
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @return	[type]		...
	 */
	function getGridTopActions(&$conf,&$caller) {
		return '';
	}

	/**
	* getListBottomActions
	*
	* @param	[type]		$$conf: ...
	* @param	[type]		$caller: ...
	* @return	[type]		...
	*/
	function getListBottomActions(&$conf,&$caller,$id=0) {
		$pluginId=$conf['pluginId'];
		$ret='';
		$url='###BACK_URL_HSC###';
		/*if ($conf['backPagePid'] ) {
			$url=$this->makeBackURLTypoLink($conf,'');
		}*/
		$ret.='<div class="'.$caller->pi_getClassName('actions').'"><div class="'.$caller->pi_getClassName('navigation-actions').'">';
		//modif by cmd - gestion global du lien de retour
		$back_lnk=$conf['typoscript.'][$pluginId.'.']['list.']['nobackbutton']?false:($conf['typoscript.']['default.']['list.']['nobackbutton']?false:true);
		$back_lnk=$conf['list.']['nobackbutton']?false:$back_lnk;
		if($back_lnk) {
			$ret.='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-back').'"><a title="'.$this->getLL("back_label",$conf).'" href="'.$url.'">'.$this->getLL("back_label",$conf).'</a></div>';
		}
		$ret.='</div><div class="'.$caller->pi_getClassName('general-actions').'">';
		if(!$conf['no_action']) {
			// We handle Export Actions Here
			$ret.=$conf['list.']['pdf']?$caller->metafeeditexport->CreatePDFButton($conf,$caller,true,$id):'';
			$ret.=$conf['list.']['csv']?$caller->metafeeditexport->CreateCSVButton($conf,$caller,true,$id):'';
			$ret.=$conf['list.']['excel']?$caller->metafeeditexport->CreateExcelButton($conf,$caller,true,$id):'';
			
			// New Button
			
			if($conf['create'] && !$conf['disableCreate'] && !$conf['create.']['hide']) {
				//$backurl=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$conf['GLOBALPARAMS']);
				//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=create".$conf['GLOBALPARAMS'].($conf['ajax.']['ajaxOn']?'':"&backURL[$pluginId]=".rawurlencode($backurl)));
				$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=create".$conf['GLOBALPARAMS']);
				$ret.='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-create').'"><a href="'.$url.'" class="jqMFModal" title="'.$this->getLL("edit_menu_createnew_label",$conf).'">'.$this->getLL("edit_menu_createnew_label",$conf).'</a></div>';
			}
		}
		$ret.='</div></div>';
		return $ret;
	}
	
	/**
	 * [Make link for 
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @return	[type]		...
	 */

	 function getDateInputButton() {
	 }
	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$caller: ...
	 * @return	[type]		...
	 */
	function getGridBottomActions(&$conf,&$caller) {
		$pluginId=$conf['pluginId'];
		$ret='';
		$url='###BACK_URL_HSC###';
		/*if ($conf['backPagePid'] ) {
			$url=$this->makeBackURLTypoLink($conf,'');
		}*/
		$ret.='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-back').'"><a title="'.$this->getLL("back_label",$conf).'" href="'.$url.'">'.$this->getLL("back_label",$conf).'</a></div></td><td align="right">';

		if(!$conf['no_action']) {
			//modif CMD - Add export buttons
   			// We handle Export Actions Here
			$ret.=$conf['grid.']['gridExportPDF']?$caller->metafeeditexport->CreatePDFButton($conf,$caller,false,0):'';
			$ret.=$conf['grid.']['gridExportCSV']?$caller->metafeeditexport->CreateCSVButton($conf,$caller,false,0):'';
			$ret.=$conf['grid.']['gridExportExcel']?$caller->metafeeditexport->CreateExcelButton($conf,$caller,false,0):'';									
			if($conf['create'] && !$conf['disableCreate'] && !$conf['create.']['hide']) {
				//$backurl=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=edit".$conf['GLOBALPARAMS']);
				//$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=create".$conf['GLOBALPARAMS']."&backURL[$pluginId]=".rawurlencode($backurl));
				$url=$this->makeFormTypoLink($conf,"&cmd[$pluginId]=create".$conf['GLOBALPARAMS']);
				$ret.='<div class="'.$caller->pi_getClassName('link').' '.$caller->pi_getClassName('link-create').'"><a href="'.$url.'">'.$this->getLL("edit_menu_createnew_label",$conf).'</a></div>';
			}
		}
		//($conf['edit.']['preview']?$this->getLL("edit_submit_label",$conf):$this->getLL("edit_preview_submit_label",$conf))
		$ret.=$conf['disableEdit']?'':'<div class="'.$caller->pi_getClassName('action-SAVE').'" ><form action="###FORM_URL###" method="POST"><button type="submit" name="submit" value="edit"'.'"'.$caller->pi_classParam('form-submit').'>'.($conf['edit.']['preview']?$this->getLL("edit_submit_label",$conf):$this->getLL("edit_preview_submit_label",$conf)).'</button></form></div>';
		return $ret;
	}

	function getEditActions(&$conf,&$caller) {
		$tmp='<div'.$caller->pi_classParam('form-row').'><table class="tx-metafeedit-edit-actions" style="width:100%"><tr><td>###ACTION-SAVE###</td>';
		//if ($conf['list.']['recordactions'] && !$conf['ajax.']['ajaxOn']) {
		
	   
		if ($conf['edit.']['recordactions']) {
			$ActionArr=t3lib_div::trimexplode(chr(10),$conf['edit.']['recordactions']);
	  		foreach($ActionArr as $action) {
	  			$cmdarr=t3lib_div::trimexplode('|',$action);
				if (count($cmdarr)>2) {
					$actionId=$cmdarr[0];
				} else {
					$actionId=$this->enleveaccentsetespaces($cmdarr[0]);
				}
	   			$conf['actions.']['useractions'].='###ACTION-'.strtoupper($actionId).'### ';
	  		}
		}
		
		$conf['actions.']['delete']='';
		if($conf['delete'] && !$conf['ajax.']['ajaxOn'] && (($conf['delete.']['preview'] && !$conf['disableDelete'] && !$conf['disableEditDelete']) || (!$conf['disableDelete']  && !$conf['disableEditDelete']))) $conf['actions.']['delete']= '###ACTION-DELETE###';

		if (!$conf['ajax.']['ajaxOn'])	$tmp.='<td align="left">###ACTION-BACK###</td>'; //TODO NEW action does not work in this context <td>###ACTION-NEW###</td>';
		$tmp.='<td align="right"><div class="'.$caller->pi_getClassName('actions').' '.$caller->pi_getClassName('edit-actions').'">'.$conf['actions.']['useractions'].$conf['actions.']['delete'].'</div></td><td>###ACTION-PDF###</td>';
		$tmp.='</tr></table></div>';
		return $tmp;
	}

	function getCreateActions(&$conf,&$caller) {
		$tmp='<div'.$caller->pi_classParam('form-row').'><table class="tx-metafeedit-edit-actions" style="width:100%"><tr><td>###ACTION-SAVE###</td>';
	 	$conf['actions.']['delete']='';
 		if (!$conf['ajax.']['ajaxOn'])	$tmp.='<td align="left">###ACTION-BACK###</td>';
		$tmp.='</tr></table></div>';
		return $tmp;
	}
	function getCreatePreviewActions(&$conf,&$caller) {
		$tmp='<div'.$caller->pi_classParam('form-row').'><table class="tx-metafeedit-edit-actions" style="width:100%"><tr><td>###ACTION-DONOTSAVE###</td><td>###ACTION-PREVIEWSAVE###</td>';
	 	$conf['actions.']['delete']='';
 		if (!$conf['ajax.']['ajaxOn'])	$tmp.='<td align="left">###ACTION-BACK###</td>';
		$tmp.='</tr></table></div>';
		return $tmp;
	}
	/**
	* Page selector
	**/
	function pageSelector($nbpages,$conf)	{
		$href=$this->hsc($conf,$this->feadminlib->pi_linkTP_keepPIvars_url(array(),1));
		$ret='<form method="post" action="'.$href.'"><input type="submit" value="'.$this->getLL('gotopage',$conf).'"/><select name="'.$this->prefixId.'[pointer]">';
		for($i=1;$i<=$nbpages;$i++) {
			$select=$conf['piVars']['pointer']==($i-1)?'selected="selected"':'';
			$ret.="<option $select value=\"".($i-1)."\">$i</option>";
		}
		$ret.='</select></form>';
		
		return $ret;
	}
	/**
	* [Describe function...]
	*
	* @return	[type]		...
	*/
	/*  
	function getJSAfter(&$conf) {
		return (is_array($conf['additionalJS_post'])?'<script type="text/javascript">'.implode(chr(10), $conf['additionalJS_post']).'</script>'.chr(10):'').(is_array($conf['additionalJS_end'])?'<script type="text/javascript">'.implode(chr(10), $conf['additionalJS_end']).'</script>':'');
	}
	*/


	// SQL Functions

	/**
	* getMMUids
	*
	* @param	[type]		$$conf: ...
	* @param	[type]		$sql: ...
	* @return	[type]		...
	*/
	
	function getMMUids(&$conf,$table,$fN,$dataArr=0) {
		$MMres = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $conf['TCAN'][$table]['columns'][$fNiD]['config']["MM"], 'uid_local=\''.$dataArr[$conf['uidField']].'\'', '');
		if (mysql_error()) debug(array(mysql_error(), $query), 'processDataArray()::field='.$fN);
		$cnt=mysql_num_rows($MMres) ;
		if ($conf['debug'] && $cnt!= $dataArr[$fN]) debug("class.tx_metafeedit_lib.php::tx_metafeedit_lib->getMMUids(): Wrong number ($cnt<>".$dataArr[$fN].") of selections reached for  field $fN of table $table, mm:  $mmTable");
		while ($MMrow = mysql_fetch_assoc($MMres)) $uids[] = $MMrow["uid_foreign"];
		return $uids;
	}

	/**
	* getExtraFields : gets extra fields from flex form configuration ...
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
					$FTTA=$this->getTableAlias($sql,$FTi,$conf);
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
	* getFieldJoin (not used ?) ...
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
			$MM=$conf['TCAN'][$masterTable]['columns'][$fN]['config']["MM"];
			if (!$MM) {
				//$aliasA=$this->getTableAlias($sql,$masterTable,$masterTable,$FT,$fN,$conf);

				$aliasA=$this->getTableAlias($sql,$fN.'.uid',$conf);//PB
				/*
				if ($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']>1) {
					$sql['where'].=' AND FIND_IN_SET('.$FT.'.uid,'.$masterTable.'.'.$fN.')>0 ';
				} else {
					$sql['where'].=' AND '.$FT.'.uid='.$masterTable.'.'.$fN.' ';
				}*/
				
				//$sql['fromTables'].=','.$FT;
				//$sql['joinTables'][]=$FT;
		 		//$this->getParentJoin($conf,$sql,$FT); //TOBEREMOVED ???
				
			} else {
					$aliasA=$this->getTableAlias($sql,$fN,$conf);//PB
					$uidLocal = isset($conf['TCAN'][$masterTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$masterTable]['ctrl']['uidLocalField']:'uid';
					$sql['where'].=" AND ".$masterTable.'.'.$uidLocal;
					//$sql['where'].= ($uidLocal == "uid")?$fN:$uidLocal;
					$sql['where'].= '='.$MM.'.uid_local';
					//$sql['fromTables'].=','.$MM;
					//$sql['joinTables'][]=$MM;
					$sql['addFields'].=$sql['addFields']?','.$MM.".uid_foreign as '".$fN."_uid_foreign'":$MM.".uid_foreign as '".$fN."_uid_foreign'";
					$ret=$fN.'_uid_foreign';
		 }
		}
		return $ret;
	}

	/**
	* Field Value Join : locks column value ????
	*
	* @param	array		$$conf: ...
	* @param	array		$sql: ...
	* @param	string		$masterTable: ...
	* @param	string		$fN: ...
	* @param	mixed		$val: ...
	* @return	string		...
	*/
	
	function getFieldValJoin(&$conf,&$sql,$masterTable,$fN,$val) {
		$FT=$conf['TCAN'][$masterTable]['columns'][$fN]['config']['foreign_table'];
		$ret=$fN;
		if ($FT) {
			$MM=$conf['TCAN'][$masterTable]['columns'][$fN]['config']["MM"];
			if (!MM) {
				//$this->getTableAlias($sql,$masterTable,$masterTable,$FT,$fN,$conf);
				$this->getTableAlias($sql,$fN,$conf);
				if ($conf['TCAN'][$masterTable]['columns'][$fN]['config']['size']>1) {
					$sql['where'].=' AND FIND_IN_SET('.$FT.'.uid,'.$masterTable.'.'.$fN.')>0 ';
				} else {
					$sql['where'].=' AND '.$FT.'.uid='.$masterTable.'.'.$fN.' ';
				}
			} else {
				//$this->getTableAlias($sql,$masterTable,$masterTable,$MM,$fN,$conf);
				$this->getTableAlias($sql,$fN,$conf);//PB
				$uidLocal = isset($conf['TCAN'][$masterTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$masterTable]['ctrl']['uidLocalField']:'uid';
				$sql['where'].=" AND ".$masterTable.'.'.$uidLocal;
				//$sql['where'].= ($uidLocal == "uid")?$fN:$uidLocal;
				$sql['where'].= '='.$MM.'.uid_local';
				//$sql['fromTables'].=','.$MM;
				$sql['addFields'].=$sql['addFields']?','.$MM.".uid_foreign as '".$fN."_uid_foreign'":$MM.".uid_foreign as '".$fN."_uid_foreign'";
				$ret=$fN.'_uid_foreign';
		 }
		}

		return $ret;
	}

	/**
	* getForeignJoin : Makes foreign table join for sql request
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	*/
	
	function getForeignJoin(&$conf,&$sql,$field) {
		if ($conf['foreignTables']) {
			$table=$conf['table'];
			$FTA=t3lib_div::trimexplode(',',$conf['foreignTables']);
			foreach($FTA as $FT) {
				$FTT=$conf['TCAN'][$table]['columns'][$FT]['config']['foreign_table'];
				//$sql['fromTables'].= ','.$FTT;
				//$aliasA=$this->getTableAlias($sql,$conf['table'],$conf['table'],$FTT,$FT,$conf);
				$aliasA=$this->getTableAlias($sql,$FT,$conf);//PB

				foreach(t3lib_div::trimexplode(',',$conf['list.'][show_fields]) as $sF) {
					$rA=t3lib_div::trimexplode('.',$sF);
					//modif by CMD - pour gerer le champ dans la table s'il nest pas la table etrangere
					if ($rA[0]==$FT && isset($rA[1])) {
						$sql['fields'].= ','.$aliasA['tableAlias'].'.'.$rA[1]." as `$FT.$rA[1]`";
						$sql['fieldArray'][]=$aliasA['tableAlias'].'.'.$rA[1]." as `$FT.$rA[1]`";
					}
				}
				
				if (!$conf['TCAN'][$table]['columns'][$FT]['config']['MM']) {
					if ($conf['TCAN'][$table]['columns'][$FT]['config']['size']>1) {
						$sql['where'].=' AND FIND_IN_SET('.$FTT.'.uid,'.$table.'.'.$FT.')>0 ';
						//$sql['fromTables'].=$sql['fromTables']?','.$FTT:$FTT;
						$sql['joinTables'][]=$FTT;
		
					} else {
						$sql['join'].=' LEFT JOIN '.$FTT.($sql['fields.'][$FT.'.']['alias']?" as `".$sql['fields.'][$FT.'.']['alias']."`":'').' ON '.$alias.'.uid='.$table.'.'.$FT.' ';
						$sql['joinTables'][]=$FTT;
					}
				} else {
					//ancienne version du lien
					//$AND_arr.=" AND ".$table.'.'.$FT.'='.$FTT.'.uid';
					//modif par CMD
					//rajout de liaison pour les tables de meta_oscommerce
					//on lie les tables en fonction du champ d'id de liaison

					$MMTable=$conf['TCAN'][$table]['columns'][$FT]['config']['MM'];
					//$this->getTableAlias($sql,$conf['table'],$conf['table'],$MMTable,$FT,$conf);
					$this->getTableAlias($sql,$FT,$conf);
					$uidLocal = isset($conf['TCAN'][$FTT]['ctrl']['uidLocalField'])?$conf['TCAN'][$FTT]['ctrl']['uidLocalField']:'uid';
					$sql['join'].=' JOIN '.$MMTable.' ON '.$MMTable.'.uid_local ='.$table.'.uid JOIN '.$FTT.' ON '.$FTT.'.uid='.$MMTable.'.uid_foreign';
					//$sql['joinTables'][]=$MMTable;
				}
			}
			//TODO
			//$sql['fromTables']=implode(',',array_diff(t3lib_div::trimexplode(',',$sql['fromTables']),t3lib_div::trimexplode(',',$sql['joinTables'])));
			//$sql['fromTables'].= $sql['join'];
			$sql['foreignWhere']=$sql['where'];
		}
	}
	
	/**
	* Makes foreign table join for sql request between two tables :
	*
	* @param	array		$conf: configuration array
	* @param	array		$sql: sql array
	* @param	string		$table: left side table of join 
	* @param	string		$tableAlias: Alias of left side table of join 
	* @param	string		$link: $table field on  which join is made ... must be last field join of field path ...Ex :  consumer.father.name => father 
	* @param	string		$foreignTtable: Right side table of join 
	* @param	string		$tableAlias: table alias for which we are joining
	* @param	string		$field: data field for which we are joining (name with no '.'); if field name is 'consumer.address' $link='consumer', $field='address'
	* @param	string		$fieldAlias: Field Alias ..
	* @todo optimize 'inline' 'select' selection ..
	*/
	
	function makeForeignJoin(&$conf,&$sql,$table,$tableAlias,$link,$foreignTable,$foreignTableAlias,$field,$fieldAlias) {
		//switch ($conf['TCAN'][$table]['columns'][$link]['config']['type']) 
		if ($conf['TCAN'][$table]['columns'][$link]['config']['type']=='inline') {
			$ff=$conf['TCAN'][$table]['columns'][$link]['config']['foreign_field']?$conf['TCAN'][$table]['columns'][$link]['config']['foreign_field']:'uid';
			if (!$conf['TCAN'][$table]['columns'][$link]['config']['MM']) {
					
				// We handle inline relation based on ',' seperated list of uids
				if ($conf['TCAN'][$table]['columns'][$link]['config']['size']>1 && !$conf['TCAN'][$table]['columns'][$link]['config']['foreign_field'] ) {
					$sql['joinTables'][]=$foreignTableAlias;		
					$sql['join.'][$foreignTableAlias]=' LEFT JOIN '.$foreignTable." as `".$foreignTableAlias."` on FIND_IN_SET(".$foreignTableAlias.'.'.$ff.','.$tableAlias.'.uid)>0 ';
				} else {
					// Normal 1/n relation ...
					$sql['join.'][$foreignTableAlias]=' LEFT JOIN '.$foreignTable.($foreignTableAlias?" as `".$foreignTableAlias."`":'').' ON '.$foreignTableAlias.'.'.$ff.'='.$tableAlias.'.uid ';
					$sql['joinTables'][]=$foreignTableAlias;
				}
			} else {
				// MM Relations.
				$MMTable=$conf['TCAN'][$table]['columns'][$link]['config']['MM'];
				$uidLocal = isset($conf['TCAN'][$foreignTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$foreignTable]['ctrl']['uidLocalField']:'uid';
				//$this->getTableAlias($sql,$table,$tableAlias,$MMTable,$uidLocal,$conf);
				$sql['join.'][$foreignTableAlias]=' JOIN '.$MMTable.' ON '.$MMTable.'.uid_local ='.$tableAlias.'.uid JOIN '.$foreignTable." as `".$foreignTableAlias."` ON ".$foreignTableAlias.'.'.$ff.'='.$MMTable.'.uid_foreign';
				$sql['joinTables'][]=$foreignTableAlias;
			}			
		} else { //select ??			
			if (!$conf['TCAN'][$table]['columns'][$link]['config']['MM']) {
			// 
				if ($conf['TCAN'][$table]['columns'][$link]['config']['size']>1) {
					$sql['joinTables'][]=$foreignTableAlias;		
					$sql['join.'][$foreignTableAlias]=' LEFT JOIN '.$foreignTable." as `".$foreignTableAlias."` on FIND_IN_SET(".$foreignTableAlias.'.uid,'.$tableAlias.'.'.$link.')>0 ';
				} else {
					// Normal 1/1 relation ...
					$sql['join.'][$foreignTableAlias]=' LEFT JOIN '.$foreignTable.($foreignTableAlias?" as `".$foreignTableAlias."`":'').' ON '.$foreignTableAlias.'.uid='.$tableAlias.'.'.$link.' ';
					$sql['joinTables'][]=$foreignTableAlias;
				}
			} else {
				// MM Relations.
				$MMTable=$conf['TCAN'][$table]['columns'][$link]['config']['MM'];
				$uidLocal = isset($conf['TCAN'][$foreignTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$foreignTable]['ctrl']['uidLocalField']:'uid';
				//$this->getTableAlias($sql,$table,$tableAlias,$MMTable,$uidLocal,$conf);
				$sql['join.'][$foreignTableAlias]=' JOIN '.$MMTable.' ON '.$MMTable.'.uid_local ='.$tableAlias.'.uid JOIN '.$foreignTable." as `".$foreignTableAlias."` ON ".$foreignTableAlias.'.uid='.$MMTable.'.uid_foreign';
				$sql['joinTables'][]=$foreignTableAlias;
			}
		}			
	}
	
	/**
	* getTableAlias : get alias for foreign table of field
	*
	* @param	[array]		$sql: sql array
	* @param	[string]	$table: name of master table
	* @param	[string]	$field: field name as selected by user in flexform ..., filedname1.fieldname2 and so on ... path must begin on master table ...
	* @return	[array]		$aliasArray : array('tableAlias'=>$tableAlias,'fieldAlias'=>$fieldAlias).
	*/
	//function getTableAlias(&$sql,$table,$tableAlias,$foreignTable,$field,&$conf) {  
	  
	function getTableAlias(&$sql,$field,&$conf) {
		$alias='';
		// We replace '.' with '_' to generate alias name

		// We look for foreign table (field name with a '.').
		// get link id, link is relation to table ...
	
		$FT='';
		$relLink=$link='';
		$relField='';
		$FN=$field;
		$p=strpos($FN,'.');
		
		//--Join Field Search
		
		$FTAA=t3lib_div::trimexplode('.',$FN);
		$c=count($FTAA);
		$c--;
		$c--;
		$joinField=$FTAA[$c];
		$relTable=$conf['table']; //masterTable;
		if (!$joinField) die('ext:meta_feedit:class.tx_metafeedit_lib.php:getTableAlias: No Join Field on '.$relTable.' :'.$field);
		//--
		while ($p>0) {
			$FTAA=t3lib_div::trimexplode('.',$FN);
			if ($relField) {
				// We check here if intermediate links have alrady been joined ...
				$rfA=t3lib_div::trimexplode('.',$relField);
				$newRelTable = $conf['TCAN'][$relTable]['columns'][end($rfA)]['config']['foreign_table'];
				if (!$sql['tableAliases'][$relTable][$link]) {
					$this->getTableAlias($sql,$relField.'.'.$FTAA[0],$conf);
				}
				$relLink=$link;
				$relTable=$newRelTable;
			}
			$link.=$link?'_'.$FTAA[0]:$FTAA[0]; // for multi relations link should be full path whithout last data field : for example a link  of field "consumer.parent.name" would be "consumer.parent."
			$relField.=$relField?'.'.$FTAA[0]:$FTAA[0]; 
			$FN=substr($FN,$p+1);
			$p=strpos($FN,'.');
		}
		$foreignTable = $conf['TCAN'][$relTable]['columns'][$joinField]['config']['foreign_table'];
		$foreignTableAlias=$foreignTable.($link?'_'.$link:'');
		$table=$relTable;
		$tableAlias=$relTable.($relLink?'_'.$relLink:'');
		$sql['tableAliases'][$foreignTable][$link]=$foreignTableAlias;
		$fieldAlias=$field;
		
		// We make foreign join ...	
		
		$this->makeForeignJoin($conf,$sql,$table,$tableAlias,$joinField,$foreignTable,$foreignTableAlias,$FN,$fieldAlias);
		
		// field aliases		
		
		$sql['fields.'][$field.'.']['table']=$foreignTable;
		$sql['fields.'][$field.'.']['tableAlias']=$foreignTableAlias;
		$sql['fields.'][$field.'.']['fieldAlias']=$fieldAlias;
		$sql['fieldArray'][]=$foreignTableAlias.'.'.$FN.' as \''.$fieldAlias.'\'';
		//$sql['fieldAliases'][$fieldAlias]=$field;
				
		return array('tableAlias'=>$foreignTableAlias,'fieldAlias'=>$fieldAlias);
	}
	
	//TODO
	function makeFieldAlias($table,$field,$conf) {
		$alias='';
		// We replace '.' with '_' to generate alias name

		// We look for foreign table (field name with a '.').
		// get link id, link is relation to table ...
	
		$FT='';
		$relLink=$link='';
		$relField='';
		$FN=$field;
		$p=strpos($FN,'.');
		//--Join Field Search
		$FTAA=t3lib_div::trimexplode('.',$FN);
		$c=count($FTAA);
		$c--;
		$c--;
		$joinField=$FTAA[$c];
		$relTable=$conf['table']; //masterTable;
		if (!$joinField) return $field;
		while ($p>0) {
			$FTAA=t3lib_div::trimexplode('.',$FN);
			if ($conf['TCAN'][$relTable]['columns'][$FTAA[0]]['config']['foreign_table']) {
				// We check here if intermediate links have already been joined ...
				$newRelTable = $conf['TCAN'][$relTable]['columns'][$FTAA[0]]['config']['foreign_table'];
				$relTable=$newRelTable;
			}
			$relField.=$relField?'.'.$FTAA[0]:$FTAA[0]; 
			//$field=$FTAA[1];
			$FN=substr($FN,$p+1);
			$p=strpos($FN,'.');
		}
		
		$fieldAlias=$relTable.'_'.$field;	
		return $fieldAlias;

	}
	
	/**
	* getSQLFields : deduct sql  fields to select from conf array for request
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	*/

	function getSQLFields(&$conf,&$sql) {
		// get fields shown in list mode ...
		if ($conf['list.']['show_fields']) {
		  $FTA=t3lib_div::trimexplode(',',$conf['list.']['show_fields']);
			foreach($FTA as $FTi) {
				if (in_array(substr($FTi,0,11),array('--div--;Tab','--fsb--;FSB','--fse--;FSE'))) continue;
				// check if field is a user calc field, if so it will be handled later ...
				if (@array_key_exists($FTi,$conf['list.']['sqlcalcfields.'])) continue;
				if (@array_key_exists($FTi,$conf['list.']['phpcalcfields.'])) continue;
				// check if field is a relation to a foreign table (it has a '.' in it's name).
				if (strpos($FTi,'.')>0 )
				{
					// We get foreign table name ... (can we have more than one '.' in name ?
					$FTAA=t3lib_div::trimexplode('.',$FTi);
					$link=$FTAA[0];
					$FN=$FTAA[1];
					$FTTA=$this->getTableAlias($sql,$FTi,$conf);
				} else {
					// These fields are form master table ($conf['table'])...
					$sql['fields.'][$FTi.'.']['table']=$conf['table'];
					$sql['fieldArray'][]=$conf['table'].'.'.$FTi;
				}
			}
			
			// special fields like uid field of master table ($conf['table']) must always be here ...
			
			if (!in_array($conf['table'].'.'.$conf['uidField'],$sql['fieldArray'])) {
				//$sql['fields'].=','.$conf['table'].'.'.$conf['uidField'];
				$sql['fields.'][$FTi.'.']['table']=$conf['table'];
				$sql['fieldArray'][]=$conf['table'].'.'.$conf['uidField'];
			}
		   

		}
	}


	/**
	* getListSQL : Builds SQL Request for list displays ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[type]		$sql: ...
	* @return	[type]		...
	*/

	function getListSQL($TABLES,$DBSELECT,&$conf,&$markerArray,&$DEBUG)	{
		$sql=array();
		//$sql['fromTables']=$TABLES;
		//$sql['tableArray']=t3lib_div::trimexplode(',',$TABLES);
		$sql['joinTables']=array();
		$sql['fieldArray']=array();
		$sql['breakOrderBy']=array();
		$sql['preOrderBy']=array();
		$sql['orderBy']=array();

		$sql['joinTables'][]=$conf['table'];
		$sql['DBSELECT']=$DBSELECT;
		$sql['where']=$sql['DBSELECT'];
		$this->getSQLFields($conf,$sql);
		// Default is *
		if (!count($sql['fieldArray'])) {
			$sql['fields']=$conf['table'].'.*';   // Field list (is * a field ???)
			$sql['fieldArray'][]=$conf['table'].'.*';
			$sql['fields.']['*.']['table']=$conf['table'];
		}
		
		$this->getLockPidJoin($conf,$sql);
		$this->getExtraFields($conf,$sql);		
		$this->getOUJoin($conf,$sql);		
		$this->getFUJoin($conf,$sql);		
		$this->getRUJoin($conf,$sql);		
		$this->getParentJoin($conf,$sql);
		$this->getCalcFields($conf,$sql);
		// Filters
		
		if ($conf['piVars']['filter']) {
			// Filter coming from extjs interface
			$this->getFilter($conf,$sql);
			if ($conf['list.']['searchBox']) $this->getFullTextSearchWhere($conf,$sql,$markerArray,false);
			if ($conf['list.']['alphabeticalSearch']) $this->getAlphabeticalSearchWhere($conf,$sql,false);
			if ($conf['list.']['advancedSearch']) $this->getAdvancedSearchWhere($conf,$sql,$markerArray,false);
			if ($conf['list.']['calendarSearch']) $this->getCalendarSearchWhere($conf,$sql,false);
		} else {
	
			if ($conf['list.']['searchBox']) $this->getFullTextSearchWhere($conf,$sql,$markerArray);
			if ($conf['list.']['alphabeticalSearch']) $this->getAlphabeticalSearchWhere($conf,$sql);
			if ($conf['list.']['advancedSearch']) $this->getAdvancedSearchWhere($conf,$sql,$markerArray);
			if ($conf['list.']['calendarSearch']) $this->getCalendarSearchWhere($conf,$sql);
		}
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
				
		$farr=array_unique($farr);
		$sql['fields']=implode(',',$farr);
		// we make fromtable sql :
		$sql['fromTables']=$conf['table']; // we add master table.
		foreach($sql['joinTables'] as $jT) {
			$sql['fromTables'].=$sql['join.'][$jT];
		}
		$conf['list.']['sql']=&$sql;
 		if ($conf['debug.']['sql']) $DEBUG.="<br/>LIST SQL ARRAY <br/><pre>".print_r($sql,true)."</pre>";   
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
			$sql['fieldArray'][]="$calcsql as '$field'";
			$sql['calcFieldsSql'].=",$calcsql as '$field'"; // to be removed
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
		$ParentWhere='';
		if ($table=='') $table=$conf['table'];
		/*$A=t3lib_div::_GP($table);
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
		}*/
		$lV=$conf['inputvar.']['lV'];
		$lField=$conf['inputvar.']['lField'];
		if  ($lV && $lField) {	
			$FT=$conf['TCAN'][$table]['columns'][$lField]['config']['foreign_table'];
			if ($FT) {
				$mmTable=$conf['TCAN'][$table]['columns'][$lField]['config']['MM'];
				if ($mmTable) {								
					$ParentWhere.=" AND ".$mmTable.'.uid_local=\''.$lV.'\'';
					//TODO
					//$sql['fromTables'].=','.$mmTable;
					//$sql['joinTables'][]=$mmTable;
				} elseif ($conf['TCAN'][$table]['columns'][$lField]['config']['type']=='inline' && $conf['TCAN'][$table]['columns'][$lField]['config']['foreign_field']) {
					 //$ParentWhere.=" AND $FT.".$conf['TCAN'][$table]['columns'][$lField]['config']['foreign_field'].'='.$lV;
					 $ParentWhere.=" AND $table.uid=$lV";

				 } else { // old "," seperated list field
					$ParentWhere.=' AND FIND_IN_SET('.$table.'.'.$lField.','.$lV.')>0 ';
				}
			} else  {
				$ParentWhere.=" AND `".$table."`.`".$lField."`='".$lV."'";
			}
				
			$sql['parentWhere'].=$ParentWhere;
			$sql['where'].= $ParentWhere;
		} 
	}
	
	/**
	* getFUJoin : Front End User Join, Joins table Fields on Front End User Fields
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	
	function getFUJoin(&$conf,&$sql,$table='') {  	
		$fUField = $conf['fUField']?$conf['fUField']:t3lib_div::_GP('fUField['.$conf['pluginId'].']');
		$fUKeyField = $conf['fUKeyField']?$conf['fUKeyField']:t3lib_div::_GP('fUKeyField['.$conf['pluginId'].']');
		$fU = $conf['fU']?$conf['fU']:t3lib_div::_GP('fU['.$conf['pluginId'].']');
		if (!$table) $table=$conf['table'];
		$OR_arr='';
		if ($fUField && $fUKeyField && ($GLOBALS['TSFE']->fe_user->user['uid'] || $fU)) {
			$feUid=$fU?$fU:$GLOBALS['TSFE']->fe_user->user['uid'];
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
				if ($conf['TCAN']['fe_users']['columns'][$fUField]['config']['foreign_table']) {
					foreach(t3lib_div::trimexplode(',',$feVals) as $feVal) {
						if ($feVal) $OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField.'='.$feVal:$table.'.'.$fUKeyField.'='.$feVal;
					}
					// condition pour afficher les enregistrement affecte a "toutes les connexions"
   					$OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField."='-2'":$table.'.'.$fUKeyField."='-2'";
				} else {
   					if ($feVals) $OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField.'='.$feVals:$table.'.'.$fUKeyField.'='.$feVals;
					// condition pour afficher les enregistrement affecte a "toutes les connexions"
   					$OR_arr.= $OR_arr?' OR '.$table.'.'.$fUKeyField."='-2'":$table.'.'.$fUKeyField."='-2'";
				}
 			}
			$sql['fUWhere']=$OR_arr;
			$sql['where'].= $OR_arr?' AND (' . $OR_arr . ')':'';
		}
	}
	/**
	* getOUJoin :OUJoin Outer Table Join
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	
	function getOUJoin(&$conf,&$sql) {
		$table=$conf['table'];
		if ($conf['originUid']) {
			$mmTable=$conf['TCAN'][$conf['originTable']]['columns'][$conf['originUidsField']]['config']['MM'];
			// MM Relation
			if ($mmTable) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$mmTable,
						'uid_local='.$GLOBALS['TYPO3_DB']->fullQuoteStr($conf['originUid'], $conf['originTable']).$GLOBALS['TSFE']->sys_page->deleteClause($table)
					);
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$OR_arr.= $OR_arr?' OR '.$table.'.'.$conf['uidField'].'='.$row['uid_foreign']:$table.'.'.$conf['uidField'].'='.$row['uid_foreign'];
				}

			} else { // multiple ',' relation >>> find_in set...
				$oUids="";
				$origArr=array();
				if ($conf['originKeyField']) { // we get uid list from parent table field
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

	/**
	* getRUJoin : Table Join on rU (uid ) value ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	
	function getRUJoin(&$conf,&$sql) {
 		$table=$conf['table'];
  		if ( ($conf['inputvar.']['rU']|| t3lib_div::_GP($table.'-rU')) && $conf['list.']['rUJoinField'] &&  strtolower($conf['list.']['rUJoinField'])!='null') {
			$rF=$conf['list.']['rUJoinField'];
			$mmTable=$conf['TCAN'][$table]['columns'][$rF]['config']['MM'];
			$ruid=t3lib_div::_GP($table.'-rU')?t3lib_div::_GP($table.'-rU'):$conf['inputvar.']['rU'];
			if ($mmTable) {
				$this->getTableAlias($sql,$rF,$conf);//PB
				$sql['rUWhere'].=" AND ".$mmTable.'.uid_local='.$table.'.uid and '.$mmTable.'.uid_foreign='.$ruid;
				//TODO
				//$sql['fromTables'].=','.$mmTable;
				//$sql['joinTables'][]=$mmTable;
			} else {
				$sql['rUWhere'].=" AND ".$table.'.'.$rF."='".$ruid."'";
			}

			//$sql['rUWhere']=$AND_arr;
			$sql['where'].= $sql['rUWhere'];
		}
	}

	/**
	* getLockPidJoin : Table Join on pid value ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	
	function getLockPidJoin(&$conf,&$sql) {
 		$table=$conf['table'];
 		// Initial variables
		if ($conf['debug']) echo Tx_MetaFeedit_Lib_ViewArray::viewArray(array('pid'=>$conf['pid']));

		$lockPid = ($conf['edit.']['menuLockPid'] && $conf['pid'])? ' AND '.$table.'.pid='.intval($conf['pid']) : '';
		if ($conf['recursive']) {
				 $pid_list = $this->feadminlib->pi_getPidList($conf['pid'], $conf['recursive']);
				 $lockPid = ($conf['edit.']['menuLockPid'] && $conf['pid']) ? ' AND '.$table.'.pid in ('.$pid_list.')' : '';
		}

		$sql['lockPidWhere']=$lockPid;
		$sql['where'].= $sql['lockPidWhere'];
	}

	/**User Where StringTable Join on pid value ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	
	function getUserWhereString(&$conf,&$sql) {
		$conf['parentObj']=&$this->feadminlib;
		if ($conf['list.']['userFunc_afterWhere']) t3lib_div::callUserFunction($conf['list.']['userFunc_afterWhere'],$conf,$this->feadminlib);
		//error_log(__METHOD__.":conf[".$conf['cmdmode'] . "][whereString]:".$conf[$conf['cmdmode'] . '.']['whereString']);
		if ($conf[$conf['cmdmode'] . '.']['whereString'])	$sql['userWhereString']=' AND '.$conf[$conf['cmdmode'] . '.']['whereString'];
		$sql['where'].= $sql['userWhereString'];
	}
	
	/**
	 * Returns current memory usage of php script
	 * @return string
	 */
	function getMemoryUsage() {
		$mem_usage = memory_get_usage(true);
	
		if ($mem_usage < 1024)
			return $mem_usage." bytes";
		elseif ($mem_usage < 1048576)
			return round($mem_usage/1024,2)." kilobytes";
		else
			return round($mem_usage/1048576,2)." megabytes";
	
	}
	
	/**
	* getAlphabeticalSearchWhere : Alphabetical Search Functions
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	
	function getAlphabeticalSearchWhere(&$conf,&$sql,$updateWhere=true) {
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
		if ($updateWhere) $sql['where'].=$sql['alphabeticalWhere'];
	}
	
	/**
	* getFullTextSearchWhere : Table Join on pid value ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/
	   
	function getFullTextSearchWhere(&$conf,&$sql,&$markerArray,$updateWhere=true) {
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
		if ($updateWhere) $sql['where'].=$sql['fullTextWhere'];
	}
	/**
	* transformGroupByData : transforms data for groupByBreak Tests through ts configuration
	*
	* @param	string		$fN: field to apply transformation to.
	* @param	mixed		$val: $value to transform
	* @param	array		$conf: configuration array
	* @return	mixed		transformed value
	*/
	function transformGroupByData($fN,$val,$conf) {
		switch ($conf['list.']['groupby.'][$fN.'.']['transform']) {
		case 'date':
			//$evals=t3lib_div::trimexplode(',',$conf['TCAN'][$conf['table']]['columns'][$fN]['config']['eval']);			
			//if ((in_array('date',$evals) || in_array('datetime',$evals) || in_array('time',$evals)) && substr($fe_adminLib->conf[$fe_adminLib->conf['cmdKey']."."]["defaultValues."][$fN], 0, 3) == 'now'/* && empty($dataArr[$fN])*/) {
			$val=strftime($conf['dateformat']?$conf['dateformat']: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%d-%Y' :'%d-%m-%Y'),$val);
			break;
		case 'datetime':
			$val=strftime($conf['datetimeformat']?$conf['datetimeformat']: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%H:%M %m-%d-%Y' :'%H:%M %d-%m-%Y'),$val);
			break;
		case 'time':
			$val=strftime(($conf['timeformat']?$conf['timeformat']:'%H:%M'),$val);
			break;
		default:
		}
		return $val;
	}
	
	/**
	* transformSqlCalcField : transforms data for groupByBreak Tests through ts configuration
	*
	* @param	string		$fN: field to apply transformation to.
	* @param	mixed		$val: $value to transform
	* @param	array		$conf: configuration array
	* @return	mixed		transformed value
	*/
	function transformSqlCalcField($fN,$val,$conf) {
		switch ($conf['list.']['sqlcalcfieldstransforms.'][$fN]) {
		case 'date':
			//$evals=t3lib_div::trimexplode(',',$conf['TCAN'][$conf['table']]['columns'][$fN]['config']['eval']);			
			//if ((in_array('date',$evals) || in_array('datetime',$evals) || in_array('time',$evals)) && substr($fe_adminLib->conf[$fe_adminLib->conf['cmdKey']."."]["defaultValues."][$fN], 0, 3) == 'now'/* && empty($dataArr[$fN])*/) {
			$val=strftime($conf['dateformat']?$conf['dateformat']: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%d-%Y' :'%d-%m-%Y'),$val);
			break;
		case 'datetime':
			$val=strftime($conf['datetimeformat']?$conf['datetimeformat']: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%H:%M %m-%d-%Y' :'%H:%M %d-%m-%Y'),$val);
			break;
		case 'time':
			$val=strftime(($conf['timeformat']?$conf['timeformat']:'%H:%M'),$val);
			break;
		default:
		}
		return $val;
	}
	
	/**
	* getGroupBy : generates group bys from flexform definition ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/	
	
	function getGroupBy(&$conf,&$sql) {
 		$table=$conf['table'];
		//$SORT='';
		$dir=' asc';
		//We handle group by line breaks
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
					//$SORT=$SORT?$SORT.','.$fN2[0]:$fN2[0];
					$sql['breakOrderBy'][]=$fN2[0].$dir;
					//$GBFields.=','.$fN2[0];
					$calcField=$conf['list.']['sqlcalcfields.'][$fN2[0]]; // TO BE IMPROVED
					if ($calcField) {
						$GBFields.=",$calcField as '$fN2[0]'";
					}
				} else {
					$gbtable=$table;
					$c=count($fNR);
					$i=$c;
					$gbfN=$fNR[0];
					$lasttable=$gbtable;
					//
					while ($i>1)  {
						$i--;
						if ($conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table']) {
							$lasttable=$gbtable;
							$gbtable2=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];
							if (!$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
								$Join.=' AND FIND_IN_SET('.$gbtable2.'.uid,'.$lasttable.'.'.$gbfN.')>0 '; // What is this ???
							}
							$gbtable=$gbtable2;
							$gbfN=$fNR[$c-$i];
						}
					}
					
					if ($table != $gbtable) t3lib_div::loadTCA($gbtable);
		  			$gbtableAlias=$sql['tableAliases'][$gbtable][str_replace('.','_',str_replace('.'.$gbfN,'',$fN2[0]))];
					if (!$gbtableAlias) $gbtableAlias=$gbtable;
					
					// If group by field points on a foreign table ...
					if ( $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['type']=='select' && $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'] && !$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
						$fT=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];						
						$label=$conf['TCAN'][$fT]['ctrl']['label'];
						$aliasA=$this->getTableAlias($sql,$fN2[0].'.'.$label,$conf);
						$GBFields.=','.$aliasA['tableAlias'].'.'.$label." as `".$aliasA['tableAlias'].'_'.strtoupper($label)."`";
						$sql['fieldArray'][]=$aliasA['tableAlias'].'.'.$label.' as \''.$fN2[0].'.'.$label.'\'';
						$GrpByField[$fN]=$aliasA['tableAlias'].'.'.strtoupper($label);
						$sql['breakOrderBy'][]=$gbtableAlias.'.'.$gbfN.$dir;//$GrpByField[$fN].$dir;
			  		} elseif ( $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['type']=='inline' && $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'] && !$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
						$fT=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];						
						$label=$conf['TCAN'][$fT]['ctrl']['label'];
						$aliasA=$this->getTableAlias($sql,$fN2[0].'.'.$label,$conf);
						$GBFields.=','.$aliasA['tableAlias'].'.'.$label." as `".$aliasA['tableAlias'].'_'.strtoupper($label)."`";
						$sql['fieldArray'][]=$aliasA['tableAlias'].'.'.$label.' as `'.$fN2[0].'.'.$label.'`';
						$GrpByField[$fN]=$aliasA['tableAlias'].'.'.strtoupper($label);
						$sql['breakOrderBy'][]=$gbtableAlias.'.'.$gbfN.$dir;//$GrpByField[$fN].$dir;
			  		}else {
					 	$sql['breakOrderBy'][]=$gbtableAlias.'.'.$gbfN.$dir;
						$GBFields.=','.$gbtableAlias.'.'.$gbfN.' as \''.$fN2[0].'\'';
						$sql['fieldArray'][]=$gbtableAlias.'.'.$gbfN.' as \''.$fN2[0].'\'';
						//$sql['fieldAliases'][$fN2[0].'.'.$label]=$fN2[0].'.'.$label;
					}
				}
			}
			$sql['breakGbFields']=$GBFields;
		}
		//We handle group by's
		
		$GBFields='';
		if ($conf['list.']['groupByFields']) {
			$fNA=t3lib_div::trimexplode(',',$conf['list.']['groupByFields']);
			// we handle here multi-table group bys ...
			$Join='';
			foreach($fNA as $fN) {
				$c=0;
				// fN2[0] : fieldName
				// fN2[1] : sort direction (ASC,DESC)
				// fN2[2] : calculate field (not attached to table)
				$fN2=t3lib_div::trimexplode(':',$fN);
				$fNR=t3lib_div::trimexplode('.',$fN2[0]);
				$dir=" ".$fN2[1];
				if ($fN2[2]) {
					// calculated field groupBy
					
					$sql['orderBy'][]=$fN2[0];
					//$GBFields.=','.$fN2[0];
					$calcField=$conf['list.']['sqlcalcfields.'][$fN2[0]]; // TO BE IMPROVED
					if ($calcField) {
						$GBFields.=",$calcField as '$fN2[0]'";
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
		  			$gbtableAlias=$sql['tableAliases'][$gbtable][str_replace('.','_',str_replace('.'.$gbfN,'',$fN2[0]))];
					if (!$gbtableAlias) $gbtableAlias=$gbtable;
					
					if ( $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['type']=='select' && $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'] && !$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
						$fT=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];
						$label=$conf['TCAN'][$fT]['ctrl']['label'];
						//TODO
						//$sql['fromTables'].=','.$fT;
						//if (!in_array($fT,$sql['joinTables'])) {
						//$aliasA=$this->getTableAlias($sql,$gbtable,$gbtableAlias?$gbtableAlias:$gbtable,$fT,$fN2[0].'.'.$label,$conf);

						$aliasA=$this->getTableAlias($sql,$fN2[0].'.'.$label,$conf);
						$GBFields.=','.$aliasA['tableAlias'].'.'.$label.' as \''.$fN2[0].'.'.$label.'\'';
						$sql['fieldArray'][]=$aliasA['tableAlias'].'.'.$label.' as \''.$fN2[0].'.'.$label.'\'';
						$GrpByField[$fN]=$aliasA['tableAlias'].'.'.strtoupper($label);
						$SORT=$SORT?$SORT.','.$GrpByField[$fN]:$GrpByField[$fN];
						$sql['orderBy'][]=$GrpByField[$fN];
						$GroupBy=$GroupBy?$GroupBy.','.$GrpByField[$fN]:' GROUP BY '.$GrpByField[$fN];
						$GBFieldLabel=$aliasA['tableAlias'].'_'.strtoupper($label);
			  		} elseif ( $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['type']=='inline' && $conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'] && !$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['MM']) {
						$fT=$conf['TCAN'][$gbtable]['columns'][$gbfN]['config']['foreign_table'];
						$label=$conf['TCAN'][$fT]['ctrl']['label'];
						//TODO
						//$sql['fromTables'].=','.$fT;
						//if (!in_array($fT,$sql['joinTables'])) {
						//$aliasA=$this->getTableAlias($sql,$gbtable,$gbtableAlias?$gbtableAlias:$gbtable,$fT,$fN2[0].'.'.$label,$conf);

						$aliasA=$this->getTableAlias($sql,$fN2[0].'.'.$label,$conf);
						//$sql['fromTables'].
						//$sql['join.'][$aliasA['tableAlias']]=' LEFT JOIN '.$fT.($sql['fields.'][$gbfN.'.']['fieldAlias']?' as '.$sql['fields.'][$gbfN.'.']['fieldAlias']:'').' ON '.$gbtable.'.'.$gbfN.'='.$aliasA['tableAlias'].'.uid';
						//}
						//$sql['joinTables'][]=$fT;
						//$label=$conf['TCAN'][$fT]['ctrl']['label'];
						//$sql['fieldArray'][]=$fT.'.'.$label.' as '.$fT.'_'.strtoupper($label);
						$GBFields.=','.$aliasA['tableAlias'].'.'.$label.' as \''.$fN2[0].'.'.$label.'\'';
						$sql['fieldArray'][]=$aliasA['tableAlias'].'.'.$label.' as \''.$fN2[0].'.'.$label.'\'';
						//$GrpByField[$fN]=$fT.'_'.strtoupper($label);
						$GrpByField[$fN]=$aliasA['tableAlias'].'.'.strtoupper($label);
						$SORT=$SORT?$SORT.','.$GrpByField[$fN]:$GrpByField[$fN];
						$sql['orderBy'][]=$GrpByField[$fN];
						$GroupBy=$GroupBy?$GroupBy.','.$GrpByField[$fN]:' GROUP BY '.$GrpByField[$fN];
						$GBFieldLabel=$aliasA['tableAlias'].'_'.strtoupper($label);
						//$sql['fieldAliases'][$fN2[0].'.'.$label]=$fN2[0].'.'.$label;			  			
					} else {
					 	//$SORT=$SORT?$SORT.','.$gbtableAlias.'.'.$gbfN:$gbtableAlias.'.'.$gbfN;
					 	$sql['orderBy'][]=$gbtableAlias.'.'.$gbfN.$dir;
						$GBFields.=','.$gbtableAlias.'.'.$gbfN.' as  \''.$fN2[0].'\'';
						$GBFieldLabel=$gbtableAlias.'_'.$gbfN;
						$GrpByField[$fN2[0]]=$GBFieldLabel;
						$GroupBy=$GroupBy?$GroupBy.','.$gbtableAlias.'.'.$gbfN:' GROUP BY '.$gbtableAlias.'.'.$gbfN; 
						$sql['fieldArray'][]=$gbtableAlias.'.'.$gbfN.' as \''.$fN2[0].'\'';
						//$sql['fieldAliases'][$fN2[0].'.'.$label]=$fN2[0].'.'.$label;
					}
				}
			}
			$sql['groupBy']=$GroupBy;
			//$sql['orderBy']=$SORT;
			$sql['gbFields']=$GBFields;
			$sql['gbCalcFields']=$GBCalcFields;
			if ($conf['list.']['havingString']) $sql['having']=' HAVING '.$conf['list.']['havingString'];
		}
	}
		// We handle Group By Field Breaks


	// Sums
	function getSum(&$conf,&$sql) {
  }
  
 
	/**
	* getOrderBy : generates order bys from flexform definition ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/	
	
	function getOrderBy(&$conf,&$sql) {
		$table=$conf['table'];
		//error_log(__METHOD__.":".print_r($conf['list.'],true));
		if ($conf['list.']['sortFields']){
			list($this->feadminlib->internal['orderBy'], $this->feadminlib->internal['descFlag']) = explode(':', $conf['inputvar.']['sort']);
			
			if ($this->feadminlib->internal['orderBy'])	{
	  			$sql['orderBy'][] = $this->makeFieldAlias($conf['table'],$this->feadminlib->internal['orderBy'],$conf).($this->feadminlib->internal['descFlag']?' DESC':' ASC');
			}
		}
		//error_log(__METHOD__.":1".print_r($sql['orderBy'],true));
		if ($conf['list.']['orderByFields'] && (!$conf['inputvar.']['sort'] || !$conf['list.']['sortFields'])){
			$orderByFields = explode(',', $conf['list.']['orderByFields']);
			foreach($orderByFields as $fieldName) {
				$fN2=t3lib_div::trimexplode(':',$fieldName);
				$fieldName=$fN2[0];
				$dir=" ".$fN2[1];
				$fieldAlias = $this->makeFieldAlias($conf['table'],$fieldName,$conf);
				 $sql['orderBy'][]= $fieldAlias.$dir;
			}
		}
		//error_log(__METHOD__.":2".print_r($sql['orderBy'],true));
		if ($conf['list.']['orderByString']) {
			$obsA=t3lib_div::trimexplode(',',$conf['list.']['orderByString']);
			foreach($obsA as $ob) {
				$sql['orderBy'][]=$ob;	
			}
		} 
		
		//we handle default sorting of table if empty and exist
		if (empty($sql['orderBy']) && $conf['TCAN'][$table]['ctrl']['sortby']) {
			$sql['orderBy'][] = $table.'.'.$conf['TCAN'][$table]['ctrl']['sortby'];
		}
		
		if ($conf['list.']['preOrderByString']) {
			$obsA=t3lib_div::trimexplode(',',$conf['list.']['preOrderByString']);
			foreach($obsA as $ob) {
				$sql['preOrderBy'][]=$ob;	
			}
		};
		//$sql['breakOrderBy']=$sql['preOrderBy'] && $sql['breakOrderBy']?','.$sql['breakOrderBy']:$sql['breakOrderBy'];
		//$sql['orderBy']=($sql['preOrderBy'] || $sql['breakOrderBy']) && $sql['orderBy'] ?','.$sql['orderBy']:$sql['orderBy'];
		if (is_array($sql['preOrderBy'])) $sql['preOrderBy']=array_unique($sql['preOrderBy']);
		if (is_array($sql['breakOrderBy'])) $sql['breakOrderBy']=array_unique($sql['breakOrderBy']);
		if (is_array($sql['orderBy'])) $sql['orderBy']=array_unique($sql['orderBy']);
		
 		$sql['orderBySql']=implode(',',array_unique(array_merge($sql['preOrderBy'],$sql['breakOrderBy'],$sql['orderBy'])));
 		if ($sql['orderBySql']) $sql['orderBySql']=' ORDER BY '.$sql['orderBySql'];
	}
	/**
	 * Creates combo data for advancedSearch select types
	 * @param array $conf
	 * @param array $markerArray
	 */
	function getAdvancedSearchComboMarkerArray($conf,&$markerArray) {
		// AS Search replace
		if ($conf['list.']['advancedSearch']) {
			$fields=$conf['list.']['advancedSearchFields']?$conf['list.']['advancedSearchFields']:($conf['list.']['show_fields']?$conf['list.']['show_fields']:$this->id_field);
			$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
			foreach($fieldArray as $FN) {
				$params=t3lib_div::trimExplode(";",$FN);
				if ($params[0]!='--div--' && $params[0]!='--fse--' && $params[0]!='--fsb--') {
					$curTable = $this->getForeignTableFromField($FN, $conf);
					$type = $conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['type'];
					switch((string)$type) {
						case 'select':
						case 'inline':
						case 'group' :
							$val=$conf['inputvar.']['advancedSearch'][$FN];
							//TODO check if ajax widget has been generated ..
							$markerArray['###AS_FIELD_'.$FN.'###']=$this->getSelectOptions($curTable['fNiD'],$curTable['relTable'],$conf,1,$val);
							break;
					}
				}
			}
			
		}
		// AS Search end
	}
	/**
	 * Transforms value into human readable format depending on TCA.
	 * @param unknown_type $key
	 * @param unknown_type $val
	 * @param unknown_type $conf
	 * @return string
	 */
	function getHumanReadableValue($key,$val,&$conf) {
		$evals=t3lib_div::trimexplode(',',$conf['TCAN'][$conf['table']]['columns'][$key]['config']['eval']);
		/*if ((in_array('date',$evals) || in_array('datetime',$evals)) && substr($conf[$fe_adminLib->conf['cmdKey']."."]["overrideValues."][$fN], 0, 3) == 'now') {
			$dataArr[$_fN] = time() + 24 * 60 * 60 * intval(substr($conf[$fe_adminLib->conf['cmdKey']."."]["overrideValues."][$fN], 3));
		}*/
		$ret=$val;
		if (in_array('date',$evals) ) {
			$ret = strftime(($conf['dateformat']?$conf['dateformat']:($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%m-%e-%Y' :'%e-%m-%Y')),$val);
		
		}
		if (in_array('time',$evals) ) {
			$ret = strftime('%H:%M',$dataArr[$fN]);
		
		}
		if(in_array('datetime',$evals)) {
			$ret = strftime(($conf['datetimeformat']?$conf['datetimeformat']: ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']? '%H:%M %m-%e-%Y' :'%H:%M %e-%m-%Y')),$val);
		}
		return $ret;
	}
	/**
	 * Returns human readable search filter
	 * @param array $conf
	 */
	function getHumanReadableSearchFilter(&$conf) {
		// TODO  We get Search Filter here (MUST BE REPLACED by tag !!!!!)....
		// TODO Put all this in function getSearchFilter ..
		//-- Should be put in fe_adminLib
		$fulltext=$conf['inputvar.']['sword']?$this->getLL("fulltext_search",$conf).' = "'.$conf['inputvar.']['sword'].'"':'';
		$obs=t3lib_div::trimexplode(':',$conf['inputvar.']['sort']);
		//error_log(__METHOD__.":".print_r($obs,true));
		$orderby=$obs[0]?$this->getLL("order_by",$conf).' '.$this->getLL($conf['TCAN'][$conf['table']]['columns'][$obs[0]]['label'],$conf).' '.($obs[1]?$this->getLL("ascending",$conf):$this->getLL("descending",$conf)):'';
		$filterArray=array();
		$recherche='';
		if ($fulltext) $filterArray[]=$fulltext;
		if ($conf['inputvar.']['sortLetter'])  $filterArray[]=$this->getLL("filtre_lettre",$conf).' = "'.$conf['inputvar.']['sortLetter'].'"';
		if (is_array($conf['inputvar.']['advancedSearch'])) {
			foreach ($conf['inputvar.']['advancedSearch'] as $key => $val) {
				if($this->is_extent($val)) {
					$tab=array();
					$ftA=$this->getForeignTableFromField($key,$conf,'',$tab);
					$recherche='';
					if (is_array($val)) {
						$isset=($this->is_extent($val['op']) && ($this->is_extent($val['val'])||$this->is_extent($val['valsup'])));
						$v1=$this->getHumanReadableValue($key,$val['val'],$conf);
						$v2=$this->getHumanReadableValue($key,$val['valsup'],$conf);
						
						//error_log(__METHOD__.": $key, $v1, $v2".print_r($conf['TCAN'][$conf['table']]['columns'][$key],true));
						if ($isset) {
							switch($val['op']) {
								case '><' :
								case '>ts<' :
									$recherche .= ($this->is_extent($val['val'])&&$this->is_extent($val['valsup']))?$v1.' &lt; '.$this->getLLFromLabel($ftA['fieldLabel'], $conf).' &gt; '.$v2:'';
									break;								
								default:
									$recherche .= $this->getLLFromLabel($ftA['fieldLabel'], $conf).' '.($this->is_extent($val)?$val['op'].' '.$v1:'');
							}
						}
					} else {
						$curTable = $this->getForeignTableFromField($key, $conf,'',$sql);
						$type=$conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['type'];
						switch ($type) {
							//TODO handle multiple values...
							case 'select' :
								if ($conf['TCAN'][$conf['table']]['columns'][$key]['config']['foreign_table']) {
									if (!$conf['TCAN'][$conf['table']]['columns'][$key]['config']['MM']) {
										$vals=explode(',',$val);
										$vs=array();
										foreach($vals as $v) {
											$rec = $GLOBALS['TSFE']->sys_page->getRawRecord($conf['TCAN'][$conf['table']]['columns'][$key]['config']['foreign_table'],$v);
											$vs[]=$rec[$conf['TCAN'][$conf['TCAN'][$conf['table']]['columns'][$key]['config']['foreign_table']]['ctrl']['label']];
										}
										$val=implode(', ',$vs);
									}
								} else if (is_array($conf['TCAN'][$conf['table']]['columns'][$key]['config']['items'])) {
									foreach($conf['TCAN'][$conf['table']]['columns'][$key]['config']['items'] as $item) {
										if ($item[1]==$val) {
											$val = $this->getLLFromLabel($item[0], $conf);
											break;
										}
									}
								}
								break;
							case 'inline' :
								if ($conf['TCAN'][$conf['table']]['columns'][$key]['config']['foreign_table']) {
									if (!$conf['TCAN'][$conf['table']]['columns'][$key]['config']['MM']) {
										$vals=explode(',',$val);
										$vs=array();
										foreach($vals as $v) {
											$rec = $GLOBALS['TSFE']->sys_page->getRawRecord($conf['TCAN'][$conf['table']]['columns'][$key]['config']['foreign_table'],$v);
											$vs[]=$rec[$conf['TCAN'][$conf['TCAN'][$conf['table']]['columns'][$key]['config']['foreign_table']]['ctrl']['label']];
										}
										$val=implode(', ',$vs);
									}
								} else if (is_array($conf['TCAN'][$conf['table']]['columns'][$key]['config']['items'])) {
									$val = $this->getLLFromLabel($conf['TCAN'][$conf['table']]['columns'][$key]['config']['items'][$val][0], $conf);
								}
								break;
							case 'radio' :
								$items=$conf['TCAN'][$conf['table']]['columns'][$key]['config']['items'];
								$index=-1;
								for ($i = 0; $i < count ($items); ++$i) {
									if ($items[$i][1]==$val) {
										$index=$i;
										break;
									}
								}
								$val = ($index===-1)?'':$this->getLLFromLabel($conf['TCAN'][$conf['table']]['columns'][$key]['config']['items'][$index][0], $conf);
								break;
	
							case 'check' :
								$val = $this->getLLFromLabel(($val?'check_yes': 'check_no'), $conf);
								break;
							case 'input' :
								break;
							default:
								$val=$val.'('.$type.')';
						}
						$recherche .= $this->getLLFromLabel($ftA['fieldLabel'], $conf).' = '.$val;
					}
					if ($recherche) $filterArray[]=$recherche;
				}
			}
		}
		$filtercnt=count($filterArray);
		if ($orderby) $filterArray[]=$orderby;
	
		// Should all be replaceD by marker and evaluation should be done in metafeedit_lib called from feadminlib.php
		/*((
		 * $filter='<div id="blockfiltre">';
		$filter2=$this->getLL("filtre_recherche",$conf).'<br />';
		if ($fulltext) $filter2.= '<tr><td class="searchf">'.$fulltext.' </td></tr>';
		if($conf['inputvar.']['advancedSearch']) $filter2.='<tr><td class="searchf">'.($recherche? $recherche : $this->getLL("search_nothing",$conf)).'</td></tr>';
		if ($conf['inputvar.']['sortLetter']) $filter2.= '<tr><td class="searchf">'.$this->getLL("filtre_lettre",$conf).$conf['inputvar.']['sortLetter'].' </td></tr>';
		if ($this->piVars['sort']) $filter2.= '<tr><td class="searchf">'.$orderby.' </td></tr>';
		if ($filter2) $filter.='<table>'.$filter2.'</table>';
		$filter .= '</div>';
		*/
			
		$ret=implode(', ',$filterArray);
		return $ret;
	}
	/**
	* getAdvancedSearchWhere : generates advancedsearch where from input vars ...
	*
	* @param	[array]		$conf: configuration array
	* @param	[array]		$sql: sql array
	* @return	[type]		...
	*/

	function getAdvancedSearchWhere(&$conf,&$sql,&$markerArray,$updateWhere=true) {
		$fields=$conf['list.']['advancedSearchFields']?$conf['list.']['advancedSearchFields']:($conf['list.']['show_fields']?$conf['list.']['show_fields']:'');
		$fieldArray=array_unique(t3lib_div::trimExplode(",",$fields));
		foreach($fieldArray as $FN) {
			$markerArray['###ASCHECKEDBETWEEN_'.$FN.'###']='';
			$markerArray['###ASCHECKEDEQUAL_'.$FN.'###']='';
			$markerArray['###ASCHECKEDINF_'.$FN.'###']='';
			$markerArray['###ASCHECKEDSUP_'.$FN.'###']='';
			$markerArray['###ASFIELD_'.$FN.'_VAL###']='';
			$markerArray['###ASFIELD_'.$FN.'_VALSUP###']='';
		}
		$table=$conf['table'];
		$advancedSearch=$conf['inputvar.']['advancedSearch'];
		
		//@TODO a fiabiliser	
		
		$GLOBALS["TSFE"]->fe_user->fetchSessionData();
		$metafeeditvars=$GLOBALS["TSFE"]->fe_user->getKey('ses','metafeeditvars');
		$metafeeditvars[$GLOBALS['TSFE']->id][$conf['pluginId']]['advancedSearch']=$advancedSearch;
		$GLOBALS["TSFE"]->fe_user->setKey('ses','metafeeditvars',$metafeeditvars);
		$GLOBALS["TSFE"]->fe_user->storeSessionData();
		
		
		$pluginId=$conf['pluginId'];
		if ($conf['typoscript.'][$pluginId.'.']['advancedSearch.']) $advancedSearchDefault = $conf['typoscript.'][$pluginId.'.']['advancedSearch.'];
		//error_log("_____________________1__________________________________");
		//error_log(print_r($advancedSearchDefault,true));
		//error_log(__METHOD__.':'.print_r($conf['inputvar.']['advancedSearch'],true));
		if (is_array($advancedSearch) && is_array($advancedSearchDefault)) {
			foreach($advancedSearch as $key=>$value) {
				if ($advancedSearchDefault[$key.'.'] && is_array($advancedSearch[$key])) $advancedSearch[$key] = array_merge($advancedSearch[$key], $advancedSearchDefault[$key.'.']);
				elseif ($advancedSearchDefault[$key.'.'] && !$value) $advancedSearch[$key] = $advancedSearchDefault[$key.'.'];
			}
		}
		elseif($advancedSearchDefault) {
			//We handle search default values
			$advancedSearch=array();
			foreach($advancedSearchDefault as $key=>$value) {
				$key = substr($key, 0, -1);
				$value['default.']['val']=$this->getData($value['default.']['val'],0,$this->cObj);
				if (!$value['default.']['op'] && $value['default.']['val']) {
					$value=$value['default.']['val'];
					$conf['inputvar.']['advancedSearch'][$key]=$value;
				}
				$advancedSearch[$key] = $value;
			}
		}
		if (is_array($advancedSearch)) {
			foreach($advancedSearch as $key=>$value) {
				//add foreign table fields to advanced search
				$curTable = $this->getForeignTableFromField($key, $conf,'',$sql);
				//Typoscript retrieval
				$valeur='';
				if ($this->is_extent($value) && !is_array($value)) $valeur=$value;
				elseif ($this->is_extent($value['default'])  && !is_array($value['default.'])) $valeur=$value['default'];
				if ($this->is_extent($valeur)) {
					//if (!$conf['TCAN'][$curTable['table']]['columns'][$key]['config']['MM']) {
					if (!$conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['MM']) {
						//$champ=($curTable['tableAlias'])?$curTable['relTableAlias'].".".$curTable['fNiD']:$curTable['relTableAlias']."_".$curTable['fieldAlias'];
						// Bug correction :
						
						$champ=($curTable['tableAlias'])?$curTable['tableAlias'].".".$curTable['fNiD']:$curTable['relTableAlias']."_".$curTable['fieldAlias'];
						$fids=t3lib_div::trimexplode(',',$valeur);
						//@todo test if input/select, ....
						switch ($conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['type']) {
							case 'input' :
								$sql['advancedWhere'].=" AND $champ like '".addslashes($valeur)."%'"; 
								break;
							case 'select' :
							default :
								foreach($fids as $fid) {
									$sql['advancedWhere'].=" AND FIND_IN_SET('$fid',$champ)"; 
								}
						}
					} else {
						//$mmTable=$conf['TCAN'][$curTable['table']]['columns'][$key]['config']['MM'];
						$mmTable=$conf['TCAN'][$curTable['relTable']]['columns'][$curTable['fNiD']]['config']['MM'];
						//$uidLocal=isset($conf['TCAN'][$mmTable]['ctrl']['uidLocalField'])?$conf['TCAN'][$mmTable]['ctrl']['uidLocalField']:'uid';
						$uidLocal=isset($conf['TCAN'][$curTable['relTable']]['ctrl']['uidLocalField'])?$conf['TCAN'][$curTable['relTable']]['ctrl']['uidLocalField']:'uid';
					 	$sql['advancedWhere'].= 'AND '.$mmTable.'.uid_local='.$curTable['relTable'].'.'.$uidLocal;
					 	$sql['advancedWhere'].= ' AND '.$mmTable.'.uid_foreign IN ('.$valeur.')';
					 	$sql['joinTables'][] = $mmTable;
				  	}
					$markerArray['###ASFIELD_'.$key.'_VAL###']=$valeur;
				}
				$my_op='';
				$my_val='';
				$my_valsup='';
				if ($value['op'] && is_array($value) ) {
					$my_op = $value['op'];
					$my_val = $value['val'];
					$my_valsup = $value['valsup'];
				} elseif (is_array($value['default.'])){
					$my_op = $value['default.']['op'];
					//TODO format date get default format
					
					if (substr($value['default.']['val'],0,3) == 'now') {
						$complement = (int)substr(str_replace(' ','',$value['default.']['val']),3);
						$my_val = ($complement) ? date('d-m-Y', time()+$complement) : date('d-m-Y');
					} else {
						$my_val = $value['default.']['val'];
					}
					
					if (substr($value['default.']['valsup'],0,3) == 'now') {
						$complement = (int)substr(str_replace(' ','',$value['default.']['valsup']),3);
						$my_valsup = ($complement) ? date('d-m-Y', time()+$complement) : date('d-m-Y');
					} else {
						$my_valsup = $value['default.']['valsup'];
					}
				}
				// dates !				
				if ($my_op && is_array($value) ) {
					$valdate=$my_val;
					$markerArray['###ASFIELD_'.$key.'_VAL###']=$valdate;
					$valdatesup=$my_valsup;
					$markerArray['###ASFIELD_'.$key.'_VALSUP###']=$valdatesup;
					if ($this->is_extent($valdate)){
						$d=explode('-',$valdate);
						// Careful here format is Month/Day/Year !!!
						//$val = strtotime($d[2].'/'.$d[1].'/'.$d[0]);
						$val=mktime(0,0,0,$d[1],$d[0],$d[2]);
						if ($this->is_extent($my_val)){
							if ($my_op=='><' &&  $this->is_extent($valdatesup)) {
				  			 $markerArray['###ASCHECKEDBETWEEN_'.$key.'###']='checked="checked"';

								$d=explode('-',$valdatesup);
								$valsup=mktime(0,0,0,$d[1],$d[0],$d[2]);
								//$sql['advancedWhere'].=" AND $table.$key between '$val' and '$valsup' ";
								$sql['advancedWhere'].=" AND (".$curTable['tableAlias'].".".$curTable['fNiD']." between '$val' and '$valsup') ";
							} elseif ($my_op=='>ts<' &&  $valdatesup) {
				  				$markerArray['###ASCHECKEDBETWEEN_'.$key.'###']='checked="checked"';
								$sql['advancedWhere'].=" AND (".$curTable['tableAlias'].".".$curTable['fNiD']." >= '$valdate' and ".$curTable['tableAlias'].".".$curTable['fNiD']." < '$valdatesup') ";
							} else {
								if ($my_op=='=') {
									$d=explode('-',$valdate);
									//$valsup = strtotime($d[2].'/'.$d[1].'/'.(int)($d[0]+1));
									$valsup=mktime(0,0,0,$d[1],$d[0]+1,$d[2]);
									$sql['advancedWhere'].=" AND (".$curTable['tableAlias'].".".$curTable['fNiD']." >= '$val' and ".$curTable['tableAlias'].".".$curTable['fNiD']." < '$valsup') ";
									$markerArray['###ASCHECKEDEQUAL_'.$key.'###']='checked="checked"';
								}elseif ($my_op=='<') {
									$sql['advancedWhere'].=" AND (".$curTable['tableAlias'].".".$curTable['fNiD']." ".$my_op." '$val') ";
									$markerArray['###ASCHECKEDINF_'.$key.'###']='checked="checked"';
								} elseif ($my_op=='>') {
									$sql['advancedWhere'].=" AND (".$curTable['tableAlias'].".".$curTable['fNiD']." ".$my_op." '$val') ";
									$markerArray['###ASCHECKEDSUP_'.$key.'###']='checked="checked"';
								} elseif ($my_op=='in') {
									$sql['advancedWhere'].=" AND (".$curTable['tableAlias'].".".$curTable['fNiD']." ".$my_op." $my_val) ";
									//$markerArray['###ASCHECKEDSUP_'.$key.'###']='checked="checked"';
								}
							}
						}
					}
				}
				if (!$markerArray['###ASCHECKEDINF_'.$key.'###'] && !$markerArray['###ASCHECKEDSUP_'.$key.'###'] && !$markerArray['###ASCHECKEDBETWEEN_'.$key.'###']) $markerArray['###ASCHECKEDEQUAL_'.$key.'###']='checked="checked"';
			}//end foreach..
		}
		if ($updateWhere) $sql['where'].=$sql['advancedWhere'];
	}		
	
	// calendarSearch
	function getCalendarSearchWhere(&$conf,&$sql,$updateWhere=true) {
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
								$sql['calendarWhere'].=" AND ($key between '$val' and '$valsup') ";
							} elseif ($value['op']=='>ts<' &&  $valdatesup) {
								$sql['calendarWhere'].=" AND ($key >= '$valdate' and  $key < '$valdatesup') ";
							} else {
								$sql['calendarWhere'].=" AND ($key ".$value['op']." '$val') ";
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
		if ($updateWhere) $sql['where'].=$sql['calendarWhere'];

	}
	
	// Clean SQL (check unicity of tables and fields ...)
	function cleanSql(&$conf,&$sql) {
		if (is_array($sql['joinTables'])) $sql['joinTables']=array_unique($sql['joinTables']);
		if (is_array($sql['gbFields'])) $sql['gbFields']=array_unique($sql['gbFields']);
		if (is_array($sql['fieldArray'])) $sql['fieldArray']=array_unique($sql['fieldArray']);
	}
		
	/**
	 * This function generates the header of the list view
	 *
	 * @alter title
	 * @alter recherche
	 * @need caller
	 */
	 
	function getHeader(&$title, &$recherche, &$conf,$data=array()) {
		//error_log(__METHOD__.":-------- ".$conf['inputvar.']['cmd']);
		//error_log(__METHOD__."0:$title - $recherche".print_r($_GET,true));
		$cmd=$conf['inputvar.']['cmd'];
		if ($conf[$cmd.'.']['title']) {
			$title=$this->getLLFromLabel($conf[$cmd.'.']['title'], $conf);
			//error_log("oooooooo".$conf['list.']['title']."/".$title);
		}
		elseif($conf['typoscript.'][$conf['pluginId'].'.'][$cmd.'.']['titre']) {
			//error_log("nnnn");
			$title = $conf['typoscript.'][$conf['pluginId'].'.'][$cmd.'.']['titre'];
		} else if  (!isset($title) && $this->feadminlib->piVars['title']) {
			//error_log("jjjj");
			$title=$this->feadminlib->piVars['title'];
		} else if (!isset($title)) {
			//error_log("ttt");
			$title = $GLOBALS['TSFE']->page['title'];
		} else {
			//error_log("No title");
			$title="";
		}
		$markerArray=array();
		
		/*
		$uid=$conf['inputvar.']['rU']?$conf['inputvar.']['rU']:$dataArr[$conf['uidField']];
		if($typeField && $uid) { // get the type from the database else use default value
		  $rec = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$uid);
		  $type = intval($rec[$typeField]);
		}
		*/
		$table=$conf['table'];
		$lV=$conf['inputvar.']['lV'];
		$lField=$conf['inputvar.']['lField'];
		if  ($lV && $lField) {	
			$FT=$conf['TCAN'][$table]['columns'][$lField]['config']['foreign_table'];
			if ($FT) {
				$mmTable=$this->conf['TCAN'][$table]['columns'][$lField]['config']['MM'];
				/*if ($mmTable || ($conf['TCAN'][$table]['columns'][$lField]['config']['size']>1)) {								
					$data = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$lV);
				} 
				else {
					$data = $GLOBALS['TSFE']->sys_page->getRawRecord($FT,$lV);
				}
				$data=$this->user_processDataArray($data, $conf,$table,TRUE);*/
				if ($mmTable || ($conf['TCAN'][$table]['columns'][$lField]['config']['size']>1)) {								
					$data = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$lV);
					$t=$table;
				} 
				else {
					$this->makeTypo3TCAForTable($conf['TCAN'],$FT);
					$data = $GLOBALS['TSFE']->sys_page->getRawRecord($FT,$lV);
					$t=$FT;
				}
				$data=$this->user_processDataArray($data, $conf,$t,TRUE);
			} else  {
				//$data = $GLOBALS['TSFE']->sys_page->getRawRecord($table,$lV);
				//$data=$this->user_processDataArray($data, $conf,$table,TRUE);
				
			}
		} 
		
		$data['SYSDATE']=date('j/m/y');
		$headerUserFunction=$conf[$cmd.'.']['headerUserFunction'];
		if ($headerUserFunction) {
			eval($headerUserFunction);
		}
		$markerArray=$this->cObj->fillInMarkerArray($markerArray, $data, '', TRUE, 'FIELD_', $conf['general.']['xhtml']);
		
		
		//error_log(__METHOD__.":MA ".$title." " .print_r($markerArray,true));
		
		
		$title=$this->cObj->substituteMarkerArray($title,$markerArray);
		
		if ($this->confTS[$this->pluginId.'.'][$cmd.'.']['soustitre']) $recherche = $this->confTS[$this->pluginId.'.'][$cmd.'.']['soustitre'];
		//error_log(__METHOD__."0:$title - $recherche".print_r($conf['inputvar.'],true));
		if (is_array($conf['inputvar.']['advancedSearch'])) {
			//error_log(__METHOD__."1:$title - $recherche");
			$recherche=$this->getHumanReadableSearchFilter($conf);
		}
		//error_log(__METHOD__."2:$title - $recherche");
		return $title;

	}
	
	function pi_wrapInBaseClass($str,$conf)	   {
		$content = '<div id="'.str_replace('_','-',$this->prefixId).'-'.$conf['pluginId'].'" class="'.str_replace('_','-',$this->prefixId).'">'.$str.'</div>';
		if($conf['disablePrefixComment']) {
			$content = '<!--BEGIN: Content of extension "'.$this->extKey.'", plugin "'.$this->prefixId.'"-->'.$content.'<!-- END: Content of extension "'.$this->extKey.'", plugin "'.$this->prefixId.'" -->';
		}
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_lib.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_lib.php']);
}
?>