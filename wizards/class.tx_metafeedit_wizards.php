<?php 
// DO NOT REMOVE OR CHANGE THESE 3 LINES:
define('TYPO3_MOD_PATH', '../typo3conf/ext/meta_feedit/wizards/');
$BACK_PATH='../../../../typo3/';
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
define('PATH_tslib',PATH_site.'typo3/sysext/cms/tslib/');
require('../class.tx_metafeedit.php');

/*require(PATH_site.'typo3/init.php');
require(PATH_site.'typo3/template.php');
require_once(PATH_t3lib.'class.t3lib_div.php');
*/

class tx_metafeedit_wizards {
	// GET vars:
	var $P;				// Wizard parameters, coming from TCEforms linking to the wizard.
	var $fieldValue;	// Value of the current field.
	var $fieldChangeFunc;	// Serialized functions for changing the field... Necessary to call when the value is transferred to the TCEform since the form might need to do internal processing. Otherwise the value is simply not be saved.
	var $fieldName;		// Form name (from opener script)
	var $formName;		// Field name (from opener script)
	var $md5ID;			// ID of element in opener script for which to set color.
	var $showPicker;	// Internal: If false, a frameset is rendered, if true the content of the picker script.

		// Static:
	var $HTMLcolorList = "aqua,black,blue,fuchsia,gray,green,lime,maroon,navy,olive,purple,red,silver,teal,yellow,white";

		// Internal:
	var $pickerImage = '';
	var $imageError = '';		// Error message if image not found.
	var $doc;					// Template Object
	var $content;				// Accumulated content.


	/**
	 * Returnes a frameset so our JavaScript Reference isn't lost
	 * Took some brains to figure this one out ;-)
	 * If Peter wouldn't have been I would've gone insane...
	 *
	 * @return	void
	 */
	function frameSet() {
		global $LANG;

			// Set doktype:
		$GLOBALS['TBE_TEMPLATE']->docType = 'xhtml_frames';
		$GLOBALS['TBE_TEMPLATE']->JScode = $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
				if (!window.opener)	{
					alert("ERROR: Sorry, no link to main window... Closing");
					close();
				}
		');

		$this->content = $GLOBALS['TBE_TEMPLATE']->startPage('hmm');

			// URL for the inner main frame:
				$url = '?showPicker=1'.
				'&colorValue='.rawurlencode($this->P['currentValue']).
				'&fieldName='.rawurlencode($this->P['itemName']).
				'&formName='.rawurlencode($this->P['formName']).
				'&exampleImg='.rawurlencode($this->P['exampleImg']).
				'&md5ID='.rawurlencode($this->P['md5ID']).
				'&cmdtpl='.t3lib_div::_GP('cmdtpl').
				'&fieldChangeFunc='.rawurlencode(serialize($this->P['fieldChangeFunc']));

		$this->content.='
			<frameset rows="*,1" framespacing="0" frameborder="0" border="0">
				<frame name="content" src="'.htmlspecialchars($url).'" marginwidth="0" marginheight="0" frameborder="0" scrolling="auto" noresize="noresize" />
				<frame name="menu" src="dummy.php" marginwidth="0" marginheight="0" frameborder="0" scrolling="no" noresize="noresize" />
			</frameset>
		';

		$this->content.='
</html>';
}
	/**
	 * Pretty-print JSON string
	 *
	 * Use 'format' option to select output format - currently html and txt supported, txt is default
	 * Use 'indent' option to override the indentation string set in the format - by default for the 'txt' format it's a tab
	 *
	 * @param string $json Original JSON string
	 * @param array $options Encoding options
	 * @return string
	 */

	public  function prettyPrint($json, $options = array())
	{
		$tokens = preg_split('|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE);
		$result = "";
		$indent = 0;
	
		$format= "txt";
	
		$ind = "\t";
	
		if(isset($options['format'])) {
			$format = $options['format'];
		}
	
		switch ($format):
		case 'html':
			$line_break = "<br />";
		$ind = "\$nbsp;\$nbsp;\$nbsp;\$nbsp;";
		break;
		default:
		case 'txt':
			$line_break = "\n";
		$ind = "\t";
		break;
		endswitch;
	
		//override the defined indent setting with the supplied option
		if(isset($options['indent'])) {
			$ind = $options['indent'];
		}
	
		foreach($tokens as $token) {
			if($token == "") continue;
	
			$prefix = str_repeat($ind, $indent);
			if($token == "{" || $token == "[") {
				$indent++;
				if($result != "" && $result[strlen($result)-1] == $line_break) {
					$result .= $prefix;
				}
				$result .= "$token$line_break";
			} else if($token == "}" || $token == "]") {
				$indent--;
				$prefix = str_repeat($ind, $indent);
				$result .= "$line_break$prefix$token";
			} else if($token == ",") {
				$result .= "$token$line_break" ;
			} else {
				$result .= $prefix.$token;
			}
		}
		return $result;
	}

	function saveReport($ttContentUid) {
		if ($ttContentUid) {
			$where='uid='.intval($ttContentUid);
			// we get associated data
			$db=$GLOBALS['TYPO3_DB'];
			$res=$db->exec_SELECTquery('pi_flexform,pid','tt_content',$where);
			while ($row=$db->sql_fetch_row($res))
			{
				$flex=$row[0];
				$tspid=$row[1];
			}
			require_once(PATH_t3lib . 'class.t3lib_timetracknull.php');
			$TT = new t3lib_timeTrackNull();
			$GLOBALS['TT']=$TT;
			$fe=t3lib_div::makeInstance('tslib_fe',$GLOBALS['TYPO3_CONF_VARS'],$tspid,0);
			$fe->initFEuser();
			$fe->fetch_the_id();
			$fe->getPageAndRootline();
			$fe->initTemplate();
			$ts=$fe->getConfigArray();
			
			//error_log(__METHOD__.":>".print_r($fe->tmpl->setup['plugin.']['tx_metafeedit_pi1.'],true));
			$flexForm=t3lib_div::xml2array($flex);
			//return $flex;
			$conf=$fe->tmpl->setup['plugin.']['tx_metafeedit_pi1.'];
			if (!class_exists('Tx_MetaFeedit_Lib_PidHandler') )  require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/Lib/PidHandler.php');
			$pidHandler=t3lib_div::makeInstance('Tx_MetaFeedit_Lib_PidHandler');
			$pluginId=$flexForm['data']['sQuickStart']['lDEF']['pluginId']['vDEF']?$flexForm['data']['sQuickStart']['lDEF']['pluginId']['vDEF']:$ttContentUid;
			$file=PATH_site."fileadmin/reports/$pluginId.json";
			$pid=intval($flexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
			$path= $pidHandler->getPath($pid);
			if ($path) $flexForm['data']['sQuickStart']['lDEF']['page']['vDEF']=$path;
			
			$storeConf['tsconf']=$conf;
			$storeConf['flexForm']=$flexForm;
			$f = fopen($file, "w");
			if($f) {
				// We update localconf.php
				//echo "\$json".str_replace( '}', "}\n", $ob_out );
				//$w=fwrite($f,json_encode($storeConf));
				$w=fwrite($f,$this->prettyPrint(json_encode($storeConf)));
				if (!$w) echo "Can't write to $file";
		
				$rf=fflush($f);
				if (!$rf) echo "Can't flush to $file";
				$c=fclose($f);
				if (!$c) echo "Can't close $file";
			}
			return $file;
		}
		return '';
	}
	
	function loadReport($ttContentUid) {
		if ($ttContentUid) {
			$where='uid='.intval($ttContentUid);
			// we get associated data
			$db=$GLOBALS['TYPO3_DB'];
			$res=$db->exec_SELECTquery('pi_flexform,pid','tt_content',$where);
			while ($row=$db->sql_fetch_row($res))
			{
				$flex=$row[0];
				$tspid=$row[1];
			}
			$flexForm=t3lib_div::xml2array($flex);
			//return $flex;
			$conf=array();
			$fileA=explode(',',$this->fieldValue);
			$file=$fileA[0];
			if ($file) {
				if (!class_exists('Tx_MetaFeedit_Lib_PidHandler') ) require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/Lib/PidHandler.php');
				$pidHandler=t3lib_div::makeInstance('Tx_MetaFeedit_Lib_PidHandler');
				$configstore=json_decode(str_replace(array("\n","\t"),"",file_get_contents($file)),true);
				$conf=$configstore['tsconf'];
				$TS='';
				$this->tsArrayToTs("plugin.tx_metafeedit_pi1.",$conf,$TS);
				//error_log(__METHOD__.":TS $TS");
				$this->updateTSTemplate($tspid,$TS);
				$piFlexForm=$configstore['flexForm'];
				$this->updateReportFlexform($ttContentUid,$piFlexForm);
				//$piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF'];
				//$pid=intval($piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
				//if ($pid==0 && $piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']) $pid=$pidHandler->getPid($piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']);
				//if ($pid) $piFlexForm['data']['sQuickStart']['lDEF']['page']['vDEF']=$pid;
				return $file;
			}
		}
		return '';
	}
	function updateTSTemplate($tspid,$TS) {
		$db=$GLOBALS['TYPO3_DB'];
		$where="pid=$tspid";
		$res=$db->exec_SELECTquery('uid','sys_template',$where);
		$cnt=$db->sql_num_rows($res);
		$data=array();
		$data['tstamp']=time();
		$data['config']=$TS;
		if (!$cnt)  {
			$data['pid']=$tspid;
			$data['crdate']=time();
			$data['title']='+afe reports';
			$res=$db->exec_INSERTquery('sys_template',$data);
		} else {
			$res=$db->exec_UPDATEquery('sys_template',$where,$data);
		}
		
	}
	function array2Xml($dataArray,&$xml) {
		//error_log(__METHOD__.":".print_r($dataArray,true));
		if (is_array($dataArray)) foreach($dataArray as $key=>$val) {
			//error_log(__METHOD__.":$key >>>");
			$xml.="<$key>";
			$this->array2Xml($val,$xml);
			$xml.="</$key>";
			//error_log(__METHOD__.":$key, $xml");
		} else {
			$xml.=htmlspecialchars($dataArray);
			//error_log(__METHOD__.":leaf");
		}
	}
	
	function updateReportFlexform($ttContentUid,$flexform) {
		$db=$GLOBALS['TYPO3_DB'];
		$where="uid=$ttContentUid";
		$data=array();
		$data['tstamp']=time();
		$xml='<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms>';
		//error_log(__METHOD__.":".print_r($flexform, true));
		$this->array2Xml($flexform,$xml);
		$xml.='</T3FlexForms>';
		$data['pi_flexform']=$xml;
		//$data['title']='+afe reports';
		$res=$db->exec_UPDATEquery('tt_content',$where,$data);
		//error_log(__METHOD__.":".$db->UPDATEquery('tt_content',$where,$data));	
	}
	
	function tsArrayToTs($tsPath,$tsArray,&$ret) {
		if (is_array($tsArray)) foreach($tsArray as $key=>$val) {
			$this->tsArrayToTs($tsPath.$key,$val,$ret);
		} else {
			$ret.="$tsPath=$tsArray".chr(10);
		}
	}
	/**
	 * 
	 */
	function init() {
		// Setting GET vars (used in frameset script):
		$this->P = t3lib_div::_GP('P',1);
		// Setting GET vars (used in colorpicker script):
		$this->fieldValue = t3lib_div::_GP('colorValue');
		$this->fieldChangeFunc = t3lib_div::_GP('fieldChangeFunc');
		$this->fieldName = t3lib_div::_GP('fieldName');
		$this->formName = t3lib_div::_GP('formName');
		$this->md5ID = t3lib_div::_GP('md5ID');
		$this->exampleImg = t3lib_div::_GP('exampleImg');
		preg_match("/[0-9]+/",$this->fieldName, $matches);
		$this->ttContentUid=$matches[0]?$matches[0]:0;
		$cmd=t3lib_div::_GP('cmdtpl');	


		//print_r($this);

			// Initialize document object:
		$this->doc = t3lib_div::makeInstance('smallDoc');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function checkReference()	{	//
				if (parent.opener && parent.opener.document && parent.opener.document.'.$this->formName.' && parent.opener.document.'.$this->formName.'["'.$this->fieldName.'"])	{
					return parent.opener.document.'.$this->formName.'["'.$this->fieldName.'"];
				} else {
					close();
				}
			}
			function changeBGcolor(color) {	// Changes the color in the table sample back in the TCEform.
			    if (parent.opener.document.layers)	{
			        parent.opener.document.layers["'.$this->md5ID.'"].bgColor = color;
			    } else if (parent.opener.document.all)	{
			        parent.opener.document.all["'.$this->md5ID.'"].style.background = color;
				} else if (parent.opener.document.getElementById && parent.opener.document.getElementById("'.$this->md5ID.'"))	{
					parent.opener.document.getElementById("'.$this->md5ID.'").bgColor = color;
				}
			}
			function setValue(input)	{	//
				var field = checkReference();
				if (field)	{
					field.value = input;
					'.$update.'
					changeBGcolor(input);
				}
			}
			function getValue()	{	//
				var field = checkReference();
				return field.value;
			}
		');
		$content='';

			// Start page:
		$this->content.=$this->doc->startPage('test');
		
		
		if(!t3lib_div::_GP('showPicker')) {	// Show frameset by default:
			$this->frameSet();
		} else {
			// If the save/close button is clicked, then close:
			if(t3lib_div::_GP('close')) {
				$content.=$this->doc->wrapScriptTags('
					parent.close();
				');
				$this->content.=$this->doc->section('Goodbye !', $content, 0,1);
			}elseif(t3lib_div::_GP('save_close')) {
				$content.=$this->doc->wrapScriptTags('
					setValue(\''.t3lib_div::_GP('tpl').'\');
					parent.close();
				');
				$this->content.=$this->doc->section('Goodbye !', $content, 0,1);
			} else {
				//error_log(__METHOD__.":".$cmd);
				switch($cmd) {
					case 'save':
						$fs=$this->saveReport($this->ttContentUid);
						$METAFEEDIT=t3lib_div::makeInstance('tx_metafeedit');
						$content .= '
						<form name="colorform" method="post" action="#">
						<!-- Value box: -->
						<p class="c-head">File saved at : '.$fs.'</p>
						<table border="0" cellpadding="0" cellspacing="3">
							<tr>
								<td><input type="submit" name="close" value="close" /></td>
							</tr>
						</table>
						<!-- Hidden fields with values that has to be kept constant -->
						<input type="hidden" name="showPicker" value="1" />
						<input type="hidden" name="fieldChangeFunc" value="'.htmlspecialchars($this->fieldChangeFunc).'" />
						<input type="hidden" name="fieldName" value="'.htmlspecialchars($this->fieldName).'" />
						<input type="hidden" name="formName" value="'.htmlspecialchars($this->formName).'" />
						<input type="hidden" name="md5ID" value="'.htmlspecialchars($this->md5ID).'" />
						<input type="hidden" name="tpl" value="Template ...." />
						<input type="hidden" name="exampleImg" value="'.htmlspecialchars($this->exampleImg).'" />
						</form>';
						$this->content.=$this->doc->section('Report has been saved', $content, 0,1);
						break;
					case 'load':
						$fs=$this->loadReport($this->ttContentUid);
						$METAFEEDIT=t3lib_div::makeInstance('tx_metafeedit');
						$content .= '
						<form name="colorform" method="post" action="#">
						<!-- Value box: -->
						<p class="c-head">File loaded from : '.$fs.'</p>
						<table border="0" cellpadding="0" cellspacing="3">
							<tr>
								<td><input type="submit" name="close" value="close" /></td>
							</tr>
						</table>
						<!-- Hidden fields with values that has to be kept constant -->
						<input type="hidden" name="showPicker" value="1" />
						<input type="hidden" name="fieldChangeFunc" value="'.htmlspecialchars($this->fieldChangeFunc).'" />
						<input type="hidden" name="fieldName" value="'.htmlspecialchars($this->fieldName).'" />
						<input type="hidden" name="formName" value="'.htmlspecialchars($this->formName).'" />
						<input type="hidden" name="md5ID" value="'.htmlspecialchars($this->md5ID).'" />
						<input type="hidden" name="tpl" value="Template ...." />
						<input type="hidden" name="exampleImg" value="'.htmlspecialchars($this->exampleImg).'" />
						</form>';
						$this->content.=$this->doc->section('Report has been loaded', $content, 0,1);
						
						break;
					case 'clear':
						$content .= '
							<form name="colorform" method="post" action="">
									<!-- Value box: -->
								<p class="c-head">test</p>
								<table border="0" cellpadding="0" cellspacing="3">
									<tr>
											<td><input type="submit" name="save_close" value="save & close" /></td>
									</tr>
								</table>
								<!-- Hidden fields with values that has to be kept constant -->
								<input type="hidden" name="showPicker" value="1" />
								<input type="hidden" name="fieldChangeFunc" value="'.htmlspecialchars($this->fieldChangeFunc).'" />
								<input type="hidden" name="fieldName" value="'.htmlspecialchars($this->fieldName).'" />
								<input type="hidden" name="formName" value="'.htmlspecialchars($this->formName).'" />
								<input type="hidden" name="md5ID" value="'.htmlspecialchars($this->md5ID).'" />
								<input type="hidden" name="cmdtpl" value="clear" />
								<input type="hidden" name="tpl" value="" />
								<input type="hidden" name="exampleImg" value="'.htmlspecialchars($this->exampleImg).'" />
							</form>';
						$this->content.=$this->doc->section('Are you sure you want to clear template ?', $content, 0,1);
						break;
					default :
						$METAFEEDIT=t3lib_div::makeInstance('tx_metafeedit');
						$content .= '
							<form name="colorform" method="post" action="#">
									<!-- Value box: -->
								<p class="c-head">test</p>
								<table border="0" cellpadding="0" cellspacing="3">
									<tr>
										<td><input type="submit" name="save_close" value="save & close" /></td>
									</tr>
								</table>
								<!-- Hidden fields with values that has to be kept constant -->
								<input type="hidden" name="showPicker" value="1" />
								<input type="hidden" name="fieldChangeFunc" value="'.htmlspecialchars($this->fieldChangeFunc).'" />
								<input type="hidden" name="fieldName" value="'.htmlspecialchars($this->fieldName).'" />
								<input type="hidden" name="formName" value="'.htmlspecialchars($this->formName).'" />
								<input type="hidden" name="md5ID" value="'.htmlspecialchars($this->md5ID).'" />
								<input type="hidden" name="tpl" value="Template ...." />
								<input type="hidden" name="exampleImg" value="'.htmlspecialchars($this->exampleImg).'" />
							</form>';
						$this->content.=$this->doc->section('Are you sure you want to set template ?', $content, 0,1);
					break;
		
				}	
			}
		}
	}
	
	function printContent()	{
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content;
	}

	
	function test() {
				return "ok";
	}
}


// Make instance:
$SOBE = t3lib_div::makeInstance('tx_metafeedit_wizards');
$SOBE->init();
$SOBE->printContent();

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/wizards/class.tx_metafeedit_wizards.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/wizards/class.tx_metafeedit_wizards.php']);
}
?>