<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005, 2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
*
* Extension to copy tree model
*
* @author Christophe BALISKY < cbalisky@metaphore.fr>
*
*/
// $invokingObj is a reference to the invoking object
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_userauthgroup.php');
require_once(PATH_t3lib.'class.t3lib_beuserauth.php');
require_once(PATH_t3lib.'class.t3lib_querygenerator.php');

class tx_metafeedit_treecopy {

	// copies Source tree to target

		function copyPages($src_uid,$target_uid,$admin_uid)  {
				$new_BE_USER = t3lib_div::makeInstance("t3lib_beUserAuth");	 // New backend user object
				$new_BE_USER->OS = TYPO3_OS;
				$new_BE_USER->setBeUserByUid($admin_uid);
				$new_BE_USER->fetchGroupData();

				$tce = t3lib_div::makeInstance("t3lib_TCEmain");
				$tce->stripslashes_values=0;
				$tce->copyTree=10;
				$tce->neverHideAtCopy=1;

				// setting the user to admin rights temporarily during copy. The reason is that everything must be copied fully!
				$new_BE_USER->user["admin"]=1;

				// Making copy-command
				$cmd=array();
				$cmd["pages"][$src_uid]["copy"]=$target_uid;
				$tce->start(array(),$cmd,$new_BE_USER);
				$tce->process_cmdmap();

				// Unsetting the user.
				unset($new_BE_USER);

				// Getting the new root page id.
		  					$res=array();
				$res['rootId'] = $tce->copyMappingArray["pages"][$src_uid];
				return $res;
		}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$rec: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
	function copyTree($rec,$conf) {
		$res = $this->copyPages($conf['T3SourceTreePid'],$conf['T3TreeTargetPid'],$conf['T3AdminUid']);
				if ($res['rootId'])	 {
					  $this->setRootPageProperties($rec,$res,$conf);
		} else {
			echo "ERROR META_FEEDIT TREE COPY !!!!!!!!!!!!!!!!";
		}
		return $res;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recordArray: ...
	 * @param	[type]		$res: ...
	 * @param	[type]		$conf: ...
	 * @return	[type]		...
	 */
function setRootPageProperties($recordArray,&$res,$conf)  {

		$root_page_pid=$res['rootId'];
		// must be changed to TS formula
		$pageTitle = ($conf["table_label"]&& $recordArray[$conf["table_label"]])? $recordArray[$conf["table_label"]] : 'Unknown';
		$counter = 0;
		$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('pages', 'title', $pageTitle, 'LIMIT 1');
		while($pageTitle."$counter" && $DBrows) {
			$counter = $counter + 1;
			$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('pages', 'title', $pageTitle."$counter", 'LIMIT 1');
		}

		$pageTitle = $counter?$pageTitle."$counter":$pageTitle;
				$pageData=array();
				$pageData["alias"]=$this->enleveaccentsetespaces($pageTitle);
				$pageData["title"]=$pageTitle;
				$pageData["perms_userid"]=0;	// The root page should not be owned (and thereby deleteable) by the user

				$this->insertInDatabase("pages",$pageData,$root_page_pid);


		// we get the template of the copied tree

		$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('sys_template', 'pid', $root_page_pid, 'LIMIT 1');
		$constants=$DBrows[0]['constants'];
		$tmpl_uid=$DBrows[0]['uid'];

		// we get all children pages of the root page of the copied tree


		// We get marker ids for each page (in the end we should add a tx_metafeedit_uid field to pages !!!).

		$MarkerArray=array();
		$pageAdminPid="";
		$MarkerArray['###METAFEEDIT_ROOTPID###']=$root_page_pid;
		$this->getPageIdMarkers($root_page_pid, $MarkerArray, $pageAdminPid,$res);

		// We put all the record data in the MarkerArray.
		foreach($recordArray as $key=>$value) {
			$MarkerArray['###METAFEEDIT_FIELD_'.$key.'###']=$value;
			//echo '###METAFEEDIT_FIELD_'.$key.'###<br>';
		}
		//$MarkerArray['###METAFEEDIT_FIELD_'.$conf['T3TableHomePidField'].'###']=$root_page_pid;
		$MarkerArray['###METAFEEDIT_RECUID###']=$recordArray['uid'];


		// cr�ation du groupe utilisateur

		$counter = 0;
		$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('fe_groups', 'title', $recordArray[$reclabel], 'LIMIT 1');
		while( $recordArray[$reclabel]."$counter" && $DBrows) {
			$counter = $counter + 1;
			$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('fe_groups', 'title',  $recordArray[$reclabel]."$counter", 'LIMIT 1');
		}

		$groupname = $this->enleveaccentsetespaces($pageTitle);
	$update=array();
		$update['pid']=$conf['T3FEGroupsPID'];
		$update['title']=$groupname;
	$grp_uid=$this->insertInDatabase("fe_groups",$update);
		$res['grpId']=$grp_uid;
		$res['grpName']=$groupname;
		$MarkerArray['###METAFEEDIT_GRPUID###']=$recordArray['uid'];

		// add error log here

		$constants=tslib_cObj::substituteMarkerArray($constants, $MarkerArray);

		$update=array();
		$update['constants']=$constants;
			  $this->insertInDatabase("sys_template",$update,$tmpl_uid);

		// Mise a jour des droits de la page admin
		if ($pageAdminPid) {
			$update=array();
			$update['fe_group']=$grp_uid;
			//echo $conf['T3GroupUids']."##################";
			if ($conf['T3GroupUids']) $update['fe_group'].=','.$conf['T3GroupUids'];
					$this->insertInDatabase("pages",$update,$pageAdminPid);
		}
		}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$data: ...
	 * @param	[type]		$uid: ...
	 * @return	[type]		...
	 */
		function insertInDatabase($table,$data,$uid=0)  {
				if ($table && is_array($data))  {
						unset($data["uid"]);

						if (count($data))	   {
								if ($this->sysConfig["testingOnly"])	{
										debug($table);
										debug($data);

										if ($uid)	   {
												$query = $GLOBALS['TYPO3_DB']->UPDATEquery($table, 'uid='.intval($uid), $data);
										} else {
												$query = $GLOBALS['TYPO3_DB']->INSERTquery($table, $data);
										}
										debug($query,1);

										return "99999";
								} else {
										if ($uid)	   {
												$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), $data);
										} else {
												$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $data);
										}
										$err = $GLOBALS['TYPO3_DB']->sql_error();
										if ($err)	   {
												debug($err,1);
												debug($table,1);
												debug($query,1);

												exit;
										}
										return $GLOBALS['TYPO3_DB']->sql_insert_id();
								}
						}
				}
				die("The record could not be inserted or updated! ".$table);
		}

	Function enleveaccents($chaine) {
			$string= strtr($chaine, "�����������������������������������������������������", "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn");
			return $string;
		}

	Function enleveaccentsetespaces($chaine) {
			$string= $this->enleveaccents($chaine);
		$string=str_replace(' ','',$string);
			return $string;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pid: ...
	 * @param	[type]		$MarkerArray: ...
	 * @param	[type]		$pageAdminPid: ...
	 * @param	[type]		$res: ...
	 * @return	[type]		...
	 */
	function getPageIdMarkers($pid, &$MarkerArray, &$pageAdminPid,&$res) {
		/*$requette = new t3lib_queryGenerator;
		$liste_page=$requette->getTreeList($pid, 999, 0, '1');
		$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('pages', '1','1' ,' and uid in ('.$liste_page.')','','uid');
		*/
		$DBrows = $GLOBALS['TSFE']->sys_page->getRecordsByField('pages', 'pid', $pid,'','','uid');
		foreach($DBrows as $row) {
			$res[$this->enleveaccentsetespaces($row['title'])]=$row['uid'];
			$MarkerArray['###METAFEEDIT_PAGE_'.$this->enleveaccentsetespaces($row['title']).'###']=$row['uid'];
			//echo chr(10).'<br>'.'###METAFEEDIT_PAGE_'.$this->enleveaccentsetespaces($row['title']).'###='.$row['uid'];
			if (strtoupper($this->enleveaccentsetespaces($row['title']))=='ADMIN') $pageAdminPid=$row['uid'];
			if ($row['uid']) $this->getPageIdMarkers($row['uid'],$MarkerArray,$pageAdminPid,$res);
		}
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/meta_feedit/class.tx_metafeedit_treecopy.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/meta_feedit/class.tx_metafeedit_treecopy.php"]);
}

?>
