<?php
/***************************************************************
 *  Copyright notice
*
*  (c) 2010 ARD Christophe BALISKY <christophe.balisky@ard.fr>
*  All rights reserved
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @package    MetaFeedit
* This package is an abstract class for OS independant php scripts
* @section startup
* @warning for the moment this class requires mysql service to be running !!!
* @author     Christophe BALISKY <christophe.balisky@ard.fr>

* @usage as admin, cd <websiteroot>/typo3conf/ext/ard_mcm/Cli
* @example php ardRun.php Tx_MetaFeedit_Cli_Export <extname> <reportname> <exporttype>
* @example ardrun Tx_MetaFeedit_Cli_Export migration <extname> <reportname> <exporttype>
* 
* @cron : crontab -uwww-data -e
* add line :
* 22 * * * * /usr/bin/php /home/access/www/typo3conf/ext/ard_mcm/Cli/ardRun.php Tx_MetaFeedit_Cli_Export export afe_custom_mulhouse_ch rptArdCustomMulhouseChExportJES XLSX 'mytitle'
* Exports report very day at 1O pm.
* @todo purge action
*/

require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_lib.php');
class Tx_MetaFeedit_Cli_Export extends Tx_ArdMcm_Cli_Batch {
	protected  $package="MetaFeedit";
	/**
	 * @var array of sting
	 */
	private $preRequisites=array();
	/**
	 * @var string name of extension hosting report
	 */
	private $exportExtension='';
	/**
	 * @var string name of report description file
	 */
	private $exportReportName='';
	private $exportTitle='';
	/**
	 * @var string full path of exported file
	 */
	private $exportFilepath='';
	/**
	 * @var string json string of filter to apply to Report (see ard_mcm's Tx_ArdMcm_Backend_JsonFilter)
	 */
	
	private $exportFilter='';
	
	/**
	 * @var string type of export (CSV,PDF,XLS, ...)
	 */
	private $exportType='CSV';
	/**
	 * link to metafeedit lib tools
	 * @var tx_metafeedit_lib
	 */
	private $metafeeditlib=null;
	/**
	 * Initialize
	 * @see Tx_ArdMcm_Cli_Batch::initialize()
	 */
	public function initialize() {
		error_log(__METHOD__);
		//$this->pkiLib = t3lib_div::makeInstance('Tx_ArdPki_Lib_Pki');
		if ($_COOKIE[t3lib_beUserAuth::getCookieName()]) {
			require_once(PATH_t3lib . 'class.t3lib_timetrack.php');
			$TT = new t3lib_timeTrack();
		} else {
			require_once(PATH_t3lib . 'class.t3lib_timetracknull.php');
			$TT = new t3lib_timeTrackNull();
		}
		$TT->start();
		$TT->push('', 'Script start');
		$GLOBALS['TT']=$TT;
		//get meta_feedit lib
		$this->metafeeditlib=t3lib_div::makeInstance('tx_metafeedit_lib');
		//Login a user ?
	}
	
	/**
	 * Returns name of export file
	 * @throws Exception
	 */
	protected function getExportFileName() {
		$fileName=date("Ymdhis-").$this->metafeeditlib->filterFileNameCharacters($this->exportTitle).'.'.strtolower($this->exportType);
		$dirPath=$_SERVER['DOCUMENT_ROOT'].'fileadmin/exchange/exports';
		if (!is_dir($dirPath)) {
			if (mkdir($dirPath,02750,true)===true) {
		
			} else {
				throw new Exception(__METHOD__.': Cannot make dir '.$dirPath,500);
			}
		}
		$this->exportFilepath=$dirPath.'/'.$fileName;
	}
	/**
	 * Validates parameters for export action
	 */
	private function validateExportParameters() {
		$params=$this->getParams();
		if ($params[3]) $this->exportExtension=$params[3];
		if ($params[4]) $this->exportReportName=$params[4];
		if ($params[5]) $this->exportType=$params[5];
		if ($params[6]) $this->exportTitle=$params[6];
		if (!in_array($this->exportType,array('CSV','XLSX','PDF'))) throw new Exception(__METHOD__.": Unhandled export type ".$this->exportType,400);
		if (!$this->exportExtension ) throw new Exception(__METHOD__."No extension defined",400);
		if (!$this->exportReportName ) throw new Exception(__METHOD__."No report defined",400);
		if (!$this->exportTitle ) throw new Exception(__METHOD__."Export has no title",400);
	}
	/**
	 * @description Syntax: 'export <extension> <report> <exportType (CSV,XLSX,PDF)>'. Starts export scripts
	 * @description-fr Syntaxe:  'export <extension> <etat> <typeExport (CSV,XLSX,PDF)>'. Lance les scripts d'export
	 * @throws Exception
	 */
	protected function doExport() {
		require_once(t3lib_extMgm::extPath('meta_feedit').'pi1/class.tx_metafeedit_pi1.php');
		require_once(t3lib_extMgm::extPath('meta_feedit').'Classes/eID/Tools.php');
		try {
			$this->validateExportParameters();
			$this->getExportFileName();
			//error_log(__METHOD__.print_r($params,true));
			// [filter] => {\"t\":\"fe_users\",\"bf\":[{\"f\":\"pid\",\"o\":\"=\",\"v\":413}],\"f\":[],\"ufp\":{\"fc\":\"Taxons\",\"fe\":\"ArdDesktop\",\"fp\":[422,\"taxons\",\"usergroup\"]}}
			//XSLSX
			$_GET['tx_metafeedit']=array(
					'title'=>$this->exportTitle,
					'filter'=>'',//"{\"t\":\"fe_users\",\"bf\":[{\"f\":\"pid\",\"o\":\"=\",\"v\":413}],\"f\":[],\"ufp\":{\"fc\":\"Taxons\",\"fe\":\"ArdDesktop\",\"fp\":[422,\"taxons\",\"usergroup\"]}}",
					'exporttype'=>($this->exportType=='XLSX'?'EXCEL':$this->exportType),
					'exportfile'=>$this->exportFilepath
			);
			//return true;
			ob_end_flush();
			// We call script
			// we initialize page id from calling page.
			$GLOBALS['TSFE']->id=0;
			// we create front end....
			$GLOBALS["TSFE"]= Tx_MetaFeedit_EID_Tools::getTSFE();
			// we initialize frontend user
			$feUserObj = Tx_MetaFeedit_EID_Tools::initFeUser();
			$GLOBALS['TSFE']->additionalJavaScript=array();
			$GLOBALS["TSFE"]->fe_user=$feUserObj;
			$GLOBALS["TSFE"]->determineId();
			if (is_array($GLOBALS['TSFE']->fe_user->user)) {
				$GLOBALS['TSFE']->loginUser = 1;
			}
			$GLOBALS["TSFE"]->initTemplate();
			$GLOBALS["TSFE"]->getConfigArray();
			$GLOBALS['TSFE']->cObj = t3lib_div::makeInstance('tslib_cObj');	// Local cObj.
			$GLOBALS['TSFE']->cObj->start(array());
			$this->metafeeditlib->cObj=&$GLOBALS['TSFE']->cObj;
			// Render charset must be UTF8 for json encode !
			$GLOBALS['TSFE']->renderCharset='utf-8';
			// Report path is either in fileadmin/reports or in module Reports path
			$configFile='/home/access/www/typo3conf/ext/'.$this->exportExtension.'/Resources/Private/Reports/'.$this->exportReportName.'.json';
			$c=new tx_metafeedit_pi1();
			$c->cObj=$GLOBALS['TSFE']->cObj;
			$content= $c->main('','',$configFile);
	
			//$scripts1=implode(chr(10),$GLOBALS['TSFE']->additionalHeaderData);
			//$scripts2="";
			// We update  user int scripts here if necessary
			if ($GLOBALS['TSFE']->isINTincScript())
			{
				$GLOBALS['TSFE']->content=$content;
				$GLOBALS['TSFE']->INTincScript();
				$content=$GLOBALS['TSFE']->content;
				//$scripts2=implode(chr(10),$GLOBALS['TSFE']->additionalHeaderData);
			}
			/*switch($exportType) {
				case "CSV":
				case "XLS":
				case "PDF":
					break;
				default:
					
					$content= '<html><head><link href="'.t3lib_extMgm::siteRelPath('meta_feedit').'res/css/meta_feedit.css" rel="stylesheet" type="text/css"/>'.$scripts1.$scripts2.'</head><body>'.$content.'</body></html>';
			
			}*/
			
			unset($c);
		} catch (Exception $e) {
			$this->errorHandler->logErrors($e->getMessage(),1,'meta_feedit','Tx_MetaFeedit_Cli_Export');
			throw $e;
		}
		return true;
	}
}
?>