<?
class tx_metafeedit_testfunc {
	
function afterWhere($conf,$conf2)	{
		$fe_adminLib = &$conf['parentObj'];
		echo "afterWhere : $where<br>"; 
		//print_r($conf);
		return $where;
}

function afterSave($conf,$c2)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterSave<br>"; 
}

function afterMark($conf,$c2)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterMark<br>"; 
}

function afterItemMark($conf,$c2)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterItemMark<br>"; 
}

function afterParse($conf,$c2)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterParse<br>"; 
}

function afterOverride($conf,$c2)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterOverride<br>"; 
}


function afterEval($conf,$c2)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterEval<br>"; 
}

function afterInitConf($conf,$vars)	{
	$fe_adminLib = &$conf['parentObj'];
	echo "afterInitConf<br>"; 
}


}
?>