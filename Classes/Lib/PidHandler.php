<?php
class Tx_MetaFeedit_Lib_PidHandler {
	protected $rootPid=0;
	
	function __construct($rootPid=0) {
		$this->rootPid=$rootPid;
	}
	/**
	 * Get's pid from path
	 * If it doesn't exist it creates it.
	 * @param $path string path to directory from root folder
	 */
	function getPid($path,$rootpid=0,$firstcall=true) {
		
		if (!$path && $firstcall) throw new Exception('Tx_MetaFeedit_Lib_PidHandler getPid : Path is not set!');
		
		// We get rootpid here :
		// - either from current page (if walled from web page
		// - either from rootpid defined in ard_mcm configruration
		
		if (!$rootpid) {
			$rootLine=$GLOBALS['TSFE']->rootLine;
			if (is_array($rootLine) && count($rootLine)) {
				$arr=array_pop($rootLine);
				// When working with rootline we add ard-root to path to get the first root node.
				//@todo maybe i should set rootPid to pid of ard-root then ..
				$path='ard-root/'.$path;
				$rootpid=$arr['uid'];
			} else {
				$rootpid=$this->rootPid;
			}
			if (!$rootpid) throw new Exception('Tx_MetaFeedit_Lib_PidHandler : Root pid for path getPid: '.$path.' is not set!');
		}
		
		// We analyze path here
		
		$pathArray=explode('/',$path);
		if (count($pathArray) && $path) {
			$p=array_shift($pathArray);
			$from='pages';
			$fields='uid';
			$where="pid=$rootpid and title='$p' and deleted=0";
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$from, $where);
			
			if ($res===false){
				$err=mysql_error();
				throw new Exception('getPid :  Sql Error '.$GLOBALS['TYPO3_DB']->SELECTquery($fields,$from, $where,'',$orderby).'--'.$err,404);
			}
				
			$cnt=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
			if (!$cnt)  {
				$data=array();
				$data['pid']=$rootpid;
				$data['doktype']=254; // we create pages of folder type
				$data['title']=$p;
				$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery($from, $data);
				if ($res===false){
					$err=mysql_error();
					throw new Exception('Tx_MetaFeedit_Lib_PidHandler->getPid :  Sql Error '.$GLOBALS['TYPO3_DB']->INSERTquery($from, $data).'--'.$err,404);
				}
				$uid=$GLOBALS['TYPO3_DB']->sql_insert_id();
				return $this->getPid(implode('/',$pathArray),$uid,false);
			} else {
				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					return $this->getPid(implode('/',$pathArray),$row['uid'],false);
				}
				// We should never come here
				return $rootpid;
			}	
				
		} else return $rootpid;
	}
	
	/**
	 * Get's Path from Pid
	 * If it doesn't exist it creates it.
	 * @param $path string path to directory from root folder
	 */
	
	function getPath($uid) {
		$from='pages';
		$fields='pid,title';
		$where="uid=$uid  and deleted=0";
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$from, $where);
		$path='';
		if ($res===false){
			$err=mysql_error();
			throw new Exception('getPid :  Sql Error '.$GLOBALS['TYPO3_DB']->SELECTquery($fields,$from, $where,'',$orderby).'--'.$err,404);
		}
			
		$cnt=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
		if (!$cnt)  {
			return $path;
		} else {
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($row['title']=='ard-root') return '';
				return $this->getPath($row['pid']).'/'.$row['title'];
			}
			// We should never come here
			return $path;
		}	
	}
	
	/**
	 * 
	 * @param $path
	 * @param $rootpid
	 * @param $firstcall
	 */
	function getGroupUid($path,$rootpid=0,$firstcall=true) {
		
		if (!$path && $firstcall) throw new Exception('Tx_MetaFeedit_Lib_PidHandler getGroupUid : Path is not set!');
		$pathArray=explode('/',$path);
		$group=array_pop($pathArray);
		$pid=$this->getPid(implode('/',$pathArray),$rootpid);
		
		$from='fe_groups';	
		$fields='uid';
		$where="pid=$pid and title='$group' and deleted=0";
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$from, $where);
			
		if ($res===false){
			$err=mysql_error();
			throw new Exception('getGroupUid :  Sql Error '.$GLOBALS['TYPO3_DB']->SELECTquery($fields,$from, $where,'',$orderby).'--'.$err,404);
		}
			
		$cnt=$GLOBALS['TYPO3_DB']->sql_num_rows($res);
		if (!$cnt)  {
				$data=array();
				$data['pid']=$pid;
				$data['title']=$group;
				$res=$GLOBALS['TYPO3_DB']->exec_INSERTquery($from, $data);
				if ($res===false){
					$err=mysql_error();
					throw new Exception('Tx_MetaFeedit_Lib_PidHandler->getGroupUid :  Sql Error '.$GLOBALS['TYPO3_DB']->INSERTquery($from, $data).'--'.$err,404);
				}
				$uid=$GLOBALS['TYPO3_DB']->sql_insert_id();
				return $uid;
		} else {
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				return $row['uid'];
			}
			// We should never come here
			return false;
		}	
				
	}
	
}
?>