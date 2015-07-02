<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Christophe Balisky <christophe@balisky.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * @author	Christophe Balisky <christophe@balisky.org>
 */
require_once(t3lib_extMgm::extPath('meta_feedit').'class.tx_metafeedit_lib.php');

class Tx_MetaFeedit_Tests_MetaFeedit_Test extends tx_phpunit_testcase {

	protected $WSDLURI;
	protected $SOAPServiceURI;

	public function __construct($name = NULL, array $data = array(), $dataName = '') {
		parent::__construct ($name, $data, $dataName);
	}
	
	/**
	 * @test
	 */
	public function getMetaFeeditVar() {	
		$metafeeditlib=t3lib_div::makeInstance('tx_metafeedit_lib');
		$conf['pluginId']='id';
		$conf['piVars']['test']['id']=4;
		//$_POST['test[id]']=4;
		$res=$metafeeditlib->getMetaFeeditVar($conf,'test');
		self::assertTrue ($res==4, 'Couldnt get test value;'); 
	}
		
}

?>