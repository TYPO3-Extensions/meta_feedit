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

// We add path to libraries
if (TYPO3_MODE){
	set_include_path(get_include_path() . PATH_SEPARATOR . t3lib_extMgm::extPath('meta_feedit').'/lib/');
}
?>
