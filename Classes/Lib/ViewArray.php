<?php
/**
 * Class to parse view an arary uri.
 * Manage aperio, lon and isoview (loc, vir, aut...) address.
 * Usable as an object or static methodes can be used.
 */
class Tx_MetaFeedit_Lib_ViewArray {
	/**
	 * Typo3 version compatible view Array
	 * @param unknown_type $data
	 */
	static function viewArray($data) {
		if (version_compare($GLOBALS['TYPO_VERSION'], '4.7.0', '>=')) {
			t3lib_utility_Debug::viewArray($data);
		} else {
			t3lib_div::view_array($data);
		}
	}
}