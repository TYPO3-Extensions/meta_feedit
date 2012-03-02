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
* Example of hook handler for extension Front End User Registration (sr_feuser_register)
*
* @author Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
*
*/
	 // $invokingObj is a reference to the invoking object
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
if(t3lib_extmgm::isLoaded('eu_ldap')) require_once(t3lib_extMgm::extPath('eu_ldap').'mod1/class.tx_euldap_div.php');

class tx_metafeedit_srfeuserregister_hooksHandler {

	function registrationProcess_beforeConfirmCreate(&$recordArray, &$invokingObj) {
		$this->syncroLdap($recordArray, $invokingObj);
		//echo '2mybeforeConfirmCreate';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recordArray: ...
	 * @param	[type]		$invokingObj: ...
	 * @return	[type]		...
	 */
	function registrationProcess_afterSaveEdit($recordArray, &$invokingObj) {
		$this->syncroLdap($recordArray, $invokingObj);
		//echo 'myafterSaveEdit';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recordArray: ...
	 * @param	[type]		$invokingObj: ...
	 * @return	[type]		...
	 */
	function registrationProcess_beforeSaveDelete($recordArray, &$invokingObj) {
		$this->syncroLdap($recordArray, $invokingObj);
		//echo 'mybeforeSaveDelete';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recordArray: ...
	 * @param	[type]		$invokingObj: ...
	 * @return	[type]		...
	 */
	function registrationProcess_afterSaveCreate($recordArray, &$invokingObj) {
		$this->syncroLdap($recordArray, $invokingObj);
		//echo 'afterSaveCreate';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$recordArray: ...
	 * @param	[type]		$invokingObj: ...
	 * @return	[type]		...
	 */
	function confirmRegistrationClass_preProcess(&$recordArray, &$invokingObj) {
		$this->syncroLdap($recordArray, $invokingObj);
			// in the case of this hook, the record array is passed by reference
			// you may not see this echo if the page is redirected to auto-login
		//echo 'confirmRegistrationClass_preProcess';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recordArray: ...
	 * @param	[type]		$invokingObj: ...
	 * @return	[type]		...
	 */
	function confirmRegistrationClass_postProcess($recordArray, &$invokingObj) {
		$this->syncroLdap($recordArray, $invokingObj);

			// you may not see this echo if the page is redirected to auto-login
		//echo 'confirmRegistrationClass_postProcess';
		//if (!$recordArray['disable']) $this->createMemberHome($recordArray,$invokingObj);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$recordArray: ...
	 * @param	[type]		$invokingObj: ...
	 * @return	[type]		...
	 */
 	function syncroLdap($recordArray, &$invokingObj) {
		if(t3lib_extmgm::isLoaded('eu_ldap') && t3lib_extmgm::isLoaded('sr_feuser_register') && !$recordArray['disable'] ) {

			$im=$recordArray['image'];
                	$im.=$im?($recordArray['image_file']?','.$recordArray['image_file']:''):$recordArray['image_file'];


                	if ( $im) {
                	        $ima=t3lib_div::trimexplode(',',$im);
                	        $im=$ima[0];
				t3lib_div::loadTCA('fe_users');
 				include(t3lib_extMgm::extPath('sr_feuser_register').'ext_tables.php');

                /*if ($conf["extTables"]) {
                $extKeys=t3lib_div::trimExplode(chr(10),$conf["extTables"]);
                        $this->mergeExtendingTCAs($extKeys);
                }*/

                	        $uf=$GLOBALS['TCA']['fe_users']['columns']['image']['config']['uploadfolder'];
                	        $jpegFile=PATH_site.$uf.'/'.$im;
                	        $fd = fopen ($jpegFile, "r");
                	        $fsize = filesize ($jpegFile);
                	        $jpegStr = fread ($fd, $fsize);
                	        fclose ($fd);
                       		$recordArray['ldapJpegPhoto']=$jpegStr;
                	}
			$Pid=$invokingObj->conf['pid'];
                	tx_euldap_div::initChar('');
               	 	$servArr= tx_euldap_div::getLdapServers($Pid);
               	 	tx_euldap_div::export_user($servArr,$recordArray,$Pid,true);
		}

	}

}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/meta_feedit/class.tx_metafeedit_srfeuserregister_hooksHandler.php"]) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/meta_feedit/class.tx_metafeedit_srfeuserregister_hooksHandler.php"]);
}

?>
