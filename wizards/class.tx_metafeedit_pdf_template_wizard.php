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
	var $colorValue;	// Value of the current color picked.
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


	function init() {
		// Setting GET vars (used in frameset script):
		$this->P = t3lib_div::_GP('P',1);
		// Setting GET vars (used in colorpicker script):
		$this->colorValue = t3lib_div::_GP('colorValue');
		$this->fieldChangeFunc = t3lib_div::_GP('fieldChangeFunc');
		$this->fieldName = t3lib_div::_GP('fieldName');
		$this->formName = t3lib_div::_GP('formName');
		$this->md5ID = t3lib_div::_GP('md5ID');
		$this->exampleImg = t3lib_div::_GP('exampleImg');		
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
		require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit.php');
		$conf=array();
		$mfedt=t3lib_div::makeInstance('tx_metafeedit');
		$mfedt->initTpl($this,$conf);
		$this->content.=$mfedt->getPDFDETTemplate(&$conf);
		if(!t3lib_div::_GP('showPicker')) {	// Show frameset by default:
			$this->frameSet();
		} else {
			// If the save/close button is clicked, then close:
			if(t3lib_div::_GP('save_close')) {
				$content.=$this->doc->wrapScriptTags('
					setValue(\''.t3lib_div::_GP('tpl').'\');
					parent.close();
				');
				$this->content.=$this->doc->section('Goodbye !', $content, 0,1);
			} else {
				switch($cmd) {
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