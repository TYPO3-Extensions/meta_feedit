<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");
t3lib_extMgm::allowTableOnStandardPages("tx_metafeedit_actions");


t3lib_extMgm::addToInsertRecords("tx_metafeedit_actions");

$TCA["tx_metafeedit_actions"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:meta_feedit/locallang_db.php:tx_metafeedit_actions",		
		"label" => "title",	
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		'sortby' => 'sorting',
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",	
			"starttime" => "starttime",	
			"endtime" => "endtime",	
			"fe_group" => "fe_group",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."ext_icon_tx_metafeedit_actions.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, starttime, endtime, fe_group, title, image, url, tooltip, category",
	)
);

t3lib_div::loadTCA("tt_content");
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:meta_feedit/flexform_ds.xml');

t3lib_extMgm::addPlugin(Array("LLL:EXT:meta_feedit/locallang_db.php:tt_content.list_type_pi1", $_EXTKEY."_pi1"),"list_type");

t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/css/','Meta FE Edit  CSS Styles');
t3lib_extMgm::addStaticFile($_EXTKEY,"pi1/static/","Meta FE document icons");						
if (TYPO3_MODE=="BE")	$TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_metafeedit_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY)."pi1/class.tx_metafeedit_pi1_wizicon.php";
require_once(t3lib_extMgm::extPath($_EXTKEY).'class.tx_metafeedit_flexfill.php');
//require_once(t3lib_extMgm::extPath($_EXTKEY).'wizards/class.tx_metafeedit_wizards.php');

t3lib_div::loadTCA('tt_content');

$TCA["tx_metafeedit_comments"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:meta_feedit/locallang_db.php:tx_metafeedit_comments",		
		"label" => "entry",	
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"default_sortby" => "ORDER BY crdate DESC",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."ext_icon.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, sys_language_uid,linked_row, firstname, surname, email, homepage, place, entry, entrycomment, remote_addr",
	)
);


$TCA['tt_content']['types']['list']['subtypes_excludelist']['meta_feedit_pi1']='layout,select_key,pages,recursive';
//$TCA['tt_content']['types']['list']['subtypes_addlist']['ve_guestbook_pi1']='pi_flexform';

//t3lib_extMgm::addPlugin(Array('LLL:EXT:ve_guestbook/locallang_tca.php:ve_guestbook', 've_guestbook_pi1'));
//t3lib_extMgm::addPiFlexFormValue('ve_guestbook_pi1', 'FILE:EXT:ve_guestbook/flexform_ds.xml');

t3lib_extMgm::allowTableOnStandardPages("tx_metafeedit_comments");
t3lib_extMgm::addToInsertRecords('tx_metafeedit_comments');


//if (TYPO3_MODE=='BE')	{
	// Adds wizard icon to the content element wizard.
	//$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_veguestbook_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_veguestbook_pi1_wizicon.php';
	
//}


?>
