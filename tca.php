<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$TCA["tx_metafeedit_actions"] = Array (
	"ctrl" => $TCA["tx_metafeedit_actions"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,starttime,endtime,fe_group,title,image,url,tooltip,category"
	),
	"feInterface" => $TCA["tx_metafeedit_actions"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"starttime" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"fe_group" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.fe_group",
			"config" => Array (
				"type" => "select",	
				"items" => Array (
					Array("", 0),
					Array("LLL:EXT:lang/locallang_general.php:LGL.hide_at_login", -1),
					Array("LLL:EXT:lang/locallang_general.php:LGL.any_login", -2),
					Array("LLL:EXT:lang/locallang_general.php:LGL.usergroups", "--div--")
				),
				"foreign_table" => "fe_groups"
			)
		),
		
		"title" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_actions.title",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "100",	
				"eval" => "required",
			)
		),
		
		"image" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_actions.image",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => $GLOBALS["TYPO3_CONF_VARS"]["GFX"]["imagefile_ext"],	
				"max_size" => 500,	
				"uploadfolder" => "uploads/tx_metaadminactions",
				"show_thumbs" => 1,	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"url" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_actions.url",		
			"config" => Array (
				"type" => "input",		
				"size" => "15",
				"max" => "255",
				"checkbox" => "",
				"eval" => "trim",
				"wizards" => Array(
					"_PADDING" => 2,
					"link" => Array(
						"type" => "popup",
						"title" => "Link",
						"icon" => "link_popup.gif",
						"script" => "browse_links.php?mode=wizard",
						"JSopenParams" => "height=300,width=500,status=0,menubar=0,scrollbars=1"
					)
				)
			)
		),
		
		"page" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_actions.page",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
				"allowed" => "pages",
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),

		"tooltip" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_actions.tooltip",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",	
				"rows" => "5",
			)
		),
		"category" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_actions.category",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "100",	
				"eval" => "required",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, title;;;;2-2-2, image;;;;3-3-3, url, page,tooltip, category")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "starttime, endtime, fe_group")
	)
);

$TCA["tx_metafeedit_comments"] = Array (
	"ctrl" => $TCA["tx_metafeedit_comments"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,sys_language_uid,firstname,surname,email,homepage,place,entry,entrycomment,remote_addr"
	),
	"feInterface" => $TCA["tx_metafeedit_comments"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,	
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'crdate' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'mergeIfNotBlank',
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.crdate",	
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'tstamp' => Array (
			'exclude' => 1,	
			'l10n_mode' => 'mergeIfNotBlank',
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.tstamp",	
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		"firstname" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.firstname",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "25",	
				"eval" => "trim,required,strip_tags,uniqueFields[surname][email][homepage][place][entrycomment]",
			)
		),
		"surname" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.surname",		
			"config" => Array (
				"type" => "input",	
				"size" => "48",	
				"max" => "25",	
				"eval" => "trim,required,strip_tags",
			)
		),
		"email" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.email",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "50",	
				"eval" => "trim,email,required,strip_tags",
			)
		),
		"homepage" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.homepage",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "2083",	
				"wizards" => Array(
					"_PADDING" => 2,
					"link" => Array(
						"type" => "popup",
						"title" => "Link",
						"icon" => "link_popup.gif",
						"script" => "browse_links.php?mode=wizard",
						"JSopenParams" => "height=300,width=500,status=0,menubar=0,scrollbars=1"
					),
				),
				"eval" => "trim,wwwURL,strip_tags",
			)
		),
		"place" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.place",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "50",	
				"eval" => "trim,strip_tags",
			)
		),
		"entry" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.entry",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"eval" => "trim,required,strip_tags",
			)
		),
		"entrycomment" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.entrycomment",		
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
				"eval" => "trim,required,strip_tags",
			)
		),
		"remote_addr" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.remote_addr",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",	
				"max" => "50",	
				"eval" => "trim",
			)
		),
		"linked_row" => Array (		
			"exclude" => 0,		
			"label" => "LLL:EXT:meta_feedit/locallang_db.xml:tx_metafeedit_comments.linked_row",		
			"config" => Array (
				"type" => "group",
				"internal_type" => "db",
				"allowed" => "*",
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, sys_language_uid, crdate, tstamp,linked_row, firstname, surname, email, homepage, place, entry, entrycomment, remote_addr;")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);
?>