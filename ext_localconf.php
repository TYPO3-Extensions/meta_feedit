<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
t3lib_extMgm::addTypoScript($_EXTKEY,"editorcfg","
	tt_content.CSS_editor.ch.tx_metafeedit_pi1 = < plugin.tx_metafeedit_pi1.CSS_editor
",43);

// AJAX FILES
$TYPO3_CONF_VARS['FE']['eID_include']['tx_metafeedit_pi2'] = 'EXT:meta_feedit/pi2/class.tx_metafeedit_pi2.php';
$TYPO3_CONF_VARS['FE']['eID_include']['tx_metafeedit_pi1'] = 'EXT:meta_feedit/pi1/class.tx_metafeedit_pi1.php';
t3lib_extMgm::addPItoST43($_EXTKEY,'class.tx_metafeedit_pi2.php','_pi2','CType',1);
t3lib_extMgm::addPItoST43($_EXTKEY,"pi1/class.tx_metafeedit_pi1.php","_pi1","list_type",1);


$TYPO3_CONF_VARS['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['confirmRegistrationClass'][] = 'EXT:meta_feedit/class.tx_metafeedit_srfeuserregister_hooksHandler.php:&tx_metafeedit_srfeuserregister_hooksHandler';
$TYPO3_CONF_VARS['EXTCONF']['sr_feuser_register']['tx_srfeuserregister_pi1']['registrationProcess'][] = 'EXT:meta_feedit/class.tx_metafeedit_srfeuserregister_hooksHandler.php:&tx_metafeedit_srfeuserregister_hooksHandler';
//$TYPO3_CONF_VARS["FE"]["XCLASS"]["tslib/class.tslib_fe.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_tslib_fe.php";

// eId scripts
$TYPO3_CONF_VARS['FE']['eID_include']['tx_metafeedit'] = 'EXT:meta_feedit/Classes/eID/Edit.php';
/****************************************************************************************************/
/*											HOOKS													*/
/****************************************************************************************************/
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc'][] = 'Tx_MetaFeedit_Hook_ClearCache->postProc';

/****************************************************************************************************/
/*							Add fonts to the fpdf library											*/
/****************************************************************************************************/
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['3OF9.z'] = array('3OF9', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', '3OF9.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['baskerville.z'] = array('baskerville', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'baskerville.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['baskervilleb.z'] = array('baskerville', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'baskervilleb.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['baskervillei.z'] = array('baskerville', 'I', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'baskervillei.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['calibri.z'] = array('calibri', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'calibri.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['calibriI.z'] = array('calibri', 'I', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'calibrii.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['calibriB.z'] = array('calibri', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'calibrib.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['calibriBI.z'] = array('calibri', 'BI', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'calibribi.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['cursive.z'] = array('cursive', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'cursive.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['cursiveb.z'] = array('cursive', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'cursiveb.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['georgia.z'] = array('georgia', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'georgia.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['georgiaI.z'] = array('georgia', 'I', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'georgiai.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['georgiaB.z'] = array('georgia', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'georgiab.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['georgiaBI.z'] = array('georgia', 'BI', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'georgiabi.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['monospace.z'] = array('monospace', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'monospace.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['monospaceb.z'] = array('monospace', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'monospaceb.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['sans-serif.z'] = array('sans-serif', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'sans-serif.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['sans-serifi.z'] = array('sans-serif', 'I', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'sans-serifi.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['sans-serifb.z'] = array('sans-serif', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'sans-serifb.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['sans-serifbi.z'] = array('sans-serif', 'BI', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'sans-serifbi.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['serif.z'] = array('serif', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'serif.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['serifi.z'] = array('serif', 'I', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'serifi.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['serifb.z'] = array('serif', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'serifb.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['serifbi.z'] = array('serif', 'BI', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'serifbi.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['tahoma.z'] = array('tahoma', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'tahoma.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['tahomab.z'] = array('tahoma', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'tahomab.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['verdana.z'] = array('verdana', '', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'verdana.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['verdanai.z'] = array('verdana', 'I', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'verdanai.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['verdanab.z'] = array('verdana', 'B', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'verdanab.php');
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['fpdf']['fonts']['verdanaz.z'] = array('verdana', 'U', t3lib_extMgm::extPath('meta_feedit').'Resources/Private/Fonts/', 'verdanaz.php');


//$this->addFont('times','','times.php');
//$this->addFont('times','B','timesb.php');
//$this->addFont('times','I','timesi.php');
//$this->addFont('times','BI','timesbi.php');

// We add path to libraries
if (TYPO3_MODE){
	set_include_path(get_include_path() . PATH_SEPARATOR . t3lib_extMgm::extPath('meta_feedit').'/lib/');
}
?>
