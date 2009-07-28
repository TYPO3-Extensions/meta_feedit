<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Christophe BALISKY (christophe@balisky.org)
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
 * Contains classes for meta feedit utilities
 *
 * $Id: class.tx_metafeedit_userfuncs.php 5470 2009-05-22 10:47:47Z ohader $
 *
 * class tx_metafeedit_userfuncs			:		utilities for meta_feedit.
 *
 * @author	Christophe BALISKY (christophe@balisky.org)
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  47: class tx_metafeedit_userfuncs
 *  49:     function user_moneyFormat($val,$params)
 *  
 */

class tx_metafeedit_userfuncs {
	/**
	* user_moneyFormat : Loads Tabel TCA with user overrides...
	*
	* @params array $params: array of parameters ('cents'=> 1 value is stored in cents and must be devided by 100 for display, 'currency'=>'$' value of currency string ).
	* @example : 
	*/
	function user_moneyFormat($val,$params) {
		$v=strval($val);
		if ($params['cents']) {
			$v=strval(round($val/100,2));
		}
		$p=strpos($v,'.');
		if ($p) {
			$int=substr($v,0,$p);
			$dec=substr($v,$p+1);
		} else {
			$dec=$v;
			$int="0";
		}
		$dec=str_pad($dec,2,'0');
		return $int.".".$dec."&nbsp;$params[currency]";
	} 
} 
?>