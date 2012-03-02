<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Ingo Renner (typo3@ingo-renner.com)
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
 * Plugin 'calendar' for the 'meta_feedit' extension.
 * Most code shamlesly taken from wordpress ;-)
 *
 * $Id$
 *
 * @author	Ingo Renner <typo3@ingo-renner.com>
 * @modified by 	Christophe Balisky <cbalisky@metaphore.fr>
*/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   58: class tx_metafeedit_calendar extends tslib_pibase
 *   74:     function main($content, &$conf,&$sql)
 *   93:     function init(&$conf,&$sql)
 *  118:     function getCalendar(&$conf,&$sql)
 *  274:     function getCurrentTime($gmt = false)
 *  291:     function getDaysWithPosts($conf,$monthBeginn)
 *  329:     function getResetLink(&$conf)
 *  353:     function getMonthLink(&$conf,$timestamp, $now)
 *  385:     function getDayLink(&$conf,$timestamp, $day, $title)
 *  420:     function getWeekdays(&$conf)
 *  440:     function calendarWeekMod($num)
 *
 * TOTAL FUNCTIONS: 10
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require_once(PATH_tslib.'class.tslib_pibase.php');

class tx_metafeedit_calendar extends tslib_pibase {
	var $prefixId = 'tx_metafeedit_pi1';		// Same as class name
	//var $scriptRelPath = 'pi3/class.tx_timtab_pi3.php';	// Path to this script relative to the extension dir.
	var $extKey = 'meta_feedit';	// The extension key.
	var $enableFields;
	var $pi_checkCHash = TRUE;
	var $conf;
	var $metafeeditlib;

	/**
	 * main funtction for blogroll
	 *
	 * @param	string		plugin output is added to this
	 * @param	array		configuration array
	 * @param	[type]		$sql: ...
	 * @return	string		complete content generated by the blogroll plugin
	 */
	function main($content, &$conf,&$sql,&$metafeeditlib)	{
	  $this->metafeeditlib=&$metafeeditlib;
		$this->init($conf,$sql);
		$calendar = $this->getCalendar($conf,$sql);
		$content  = $this->cObj->stdWrap($calendar, $this->conf['header_stdWrap.']);

        //OSR modif a la con... 
		if(($this->conf['dontWrapInDiv'] == 1)||(1==1)) {
			return $content;
		} else {
			return $this->pi_wrapInBaseClass($content);
		}
	}

	/**
	 * initializes the configuration for this plugin
	 *
	 * @param	array		configuration array
	 * @param	[type]		$sql: ...
	 * @return	void
	 */
	function initObj(&$metafeeditlib,&$cObj) {
	  $this->metafeeditlib=&$metafeeditlib;
	  $this->cObj=&$cObj;
	}


	/**
	 * initializes the configuration for this plugin
	 *
	 * @param	array		configuration array
	 * @param	[type]		$sql: ...
	 * @return	void
	 */
	function init(&$conf,&$sql,&$metafeeditlib,&$cObj) {
		$this->metafeeditlib=&$metafeeditlib;
		$this->cObj=$GLOBALS['TSFE']->cObj;
		$this->conf=$conf;
		if (!$conf['list.']['beginDateField']) die ("Plugin Meta Feedit, Calendar Mode and no begin date set !");
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		// pidList is the pid/list of pids from where to fetch the faq items.
		$cePidList = $this->cObj->data['pages']; //ce = Content Element
		$pidList=$cePidList?$cePidList:trim($this->cObj->stdWrap($this->conf['pid_list'], $this->conf['pid_list.']));
		$this->enableFields=$sql['DBSELECT'].$sql['lockPidWhere'];
		 unset($this->conf['pid_list']);
	}

	/**
	 * renders calendar which shows days with posts, addopted from wordpress
	 *
	 * @param	[type]		$$conf: ...
	 * @param	[type]		$sql: ...
	 * @return	string		the html for the calendar
	 */
	 
	function getCalendar(&$conf,&$sql) {
		$conf['calendarNbMonth']=2;
		$conf['calendarDecalMonth']=0;
		$content='';
		// Quick check. If we have no posts at all, abort!
		$check = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid',
			$conf['table'],
			"1=1 " .$sql['DBSELECT'].$sql['lockPidWhere']
		);
		if(empty($check)) {
			return 'Pas de résultat.';
		}
		
		for($numCalendar=0;$numCalendar<$conf['calendarNbMonth']; $numCalendar++){
			// week_begins = 0 stands for sunday
			$newrow = false;
			$weekBegins = $this->conf['week_begins'];
			$addHours   = $this->conf['gmt_offset'];
			$addMinutes = intval(60 * ($this->conf['gmt_offset'] - $addHours));
	
			// Let's figure out where we are
			$newsGET = t3lib_div::_GET('tx_metafeedit');		
			$thisYear = intval( $newsGET['calendarSearch'][$conf['pluginId']]['year'] ? 
				$newsGET['calendarSearch'][$conf['pluginId']]['year'] : 
				gmdate('Y', $this->getCurrentTime()-$conf['calendarDecalMonth']*3600*24*30 + $this->conf['gmt_offset'] * 3600)
			);
			
			$thisMonth = intval( $newsGET['calendarSearch'][$conf['pluginId']]['month'] ? 
				$newsGET['calendarSearch'][$conf['pluginId']]['month'] : 
				gmdate('n', $this->getCurrentTime()-$conf['calendarDecalMonth']*3600*24*30 + $this->conf['gmt_offset'] * 3600)
			);		
			if (($numCalendar>0)){
				if (empty($next)) {
					break;
				}
				$thisMonth=date('n',$next);
				$thisYear=date('Y',$next);
			}
	
			$unixMonth = mktime(0, 0 , 0, $thisMonth, 1, $thisYear);
	
			// Get the next and previous month and year with at least one post
			$prevTime = $unixMonth;

			if ($_GET['test']=='test') {echo "<br/>--->".$conf['list.']['beginDateField'].' < \''.$prevTime.'\''.$this->enableFields;}
			$prev = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				$conf['list.']['beginDateField'],
				$conf['table'],
				$conf['list.']['beginDateField'].' < \''.$prevTime.'\''.$this->enableFields. $conf['list.']['whereString'],
				'',
				$conf['list.']['beginDateField'].' DESC',
				1
			);
			
			
			if(!empty($prev)) {
				$prev = $prev[0][$conf['list.']['beginDateField']];
			}
	
			$nextTime = mktime(0, 0, 0, $thisMonth + 1, 1, $thisYear);
			$next = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				$conf['list.']['beginDateField'],
				$conf['table'],
				$conf['list.']['beginDateField'].' > \''.$nextTime.'\''.$this->enableFields.$conf['list.']['whereString'],
				'',
				$conf['list.']['beginDateField'].' ASC',
				1
			);
			if ($_GET['test']=='test') {
				echo "<br> Next : ";
				print_r($next);
			}
			if(!empty($next)) {
				$next = $next[0][$conf['list.']['beginDateField']];
				if ($_GET['test']=='test') {
					echo "<br> Next n'est pas vide ";
				}	
			}
			else
			{
if ($_GET['test']=='test') {
echo "<br> Next est vide. ";
}
				
			}
			
			if(empty($nextForNav)) {
				$nextForNav = $next;
				
			}
			if (sizeof($this->getDaysWithPosts($conf,$unixMonth))==0){
				continue;
			}
			//beginn output
			//if ($thisMonth == intval( $newsGET['calendarSearch'][$conf['pluginId']]['month'])){
			    $content .= '<div class="metafeedit-calendar-bloc metafeedit-calendar_'.$numCalendar.'"><table id="metafeedit-calendar">
			 	    	<thead>
			    	<tr><td colspan="7">'.$this->getCurentMonthLink($this->conf,$unixMonth ,$unixMonth , $unixMonth).'</td></tr>
			   	<tr>';
			/*}
			else {
				$content .= '<div class="metafeedit-calendar-bloc-active metafeedit-calendar_'.$numCalendar.'"><table id="metafeedit-calendar">
			 	    	<thead>
			    	<tr><td colspan="7">'.$GLOBALS['TSFE']->csConvObj->conv(strftime('%B', $unixMonth),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset).' '.date('Y', $unixMonth).'</td></tr>
			   	<tr>';
			}
			*/	
		    	$week     = array();
	  		$weekdays = $this->getWeekdays($this->conf);
	
	    		for($i = 0; $i <= 6; $i++) {
	    			$week[] = $weekdays[($i + $weekBegins) % 7];
	    		}
	    		foreach ($week as $wd) {
	    			$content .= "\n\t\t\t".'<th abbr="'.$wd.'" scope="col" title="'.$wd.'">'.substr($wd, 0, $this->conf['list.']['weekdayNameLength']).'</th>';
			}
	
			$content .= '
			</tr>
			</thead>
	
			<tfoot>
			<tr>';
	
			if (($prev)&&(($numCalendar<=0)||($conf['calendarNbMonth']<2))) {
				$content .= "\n\t\t\t".'<td abbr="'.$GLOBALS['TSFE']->csConvObj->conv(strftime('%b', $prev),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset).'" colspan="3" id="prev">'
						 .$this->getMonthLink($this->conf,$prev,$prev, $unixMonth).'</td>';
			} else {
				$content .= "\n\t\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
			}
	
			$content .= "\n\t\t\t".'<td class="pad">'.$this->getResetLink($this->conf).'</td>';
	
			if (($next)&&(($numCalendar>=$conf['calendarNbMonth']-1)||($conf['calendarNbMonth']<2))) {
				$content .= "\n\t\t\t".'<td abbr="'.$GLOBALS['TSFE']->csConvObj->conv(strftime('%b', $next),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset).'" colspan="3" id="next">'
						 .$this->getMonthLink($this->conf,$next, $nextForNav, $unixMonth).'</td>';
			} else {
				$content .= "\n\t\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
			}
	
			$content .= '
			</tr>
			</tfoot>
	
			<tbody>
			<tr>';
	
			// Get days with posts
			$daysWithPosts = $this->getDaysWithPosts($conf,$unixMonth);
	
			// See how much we should pad in the beginning
			$pad = $this->calendarWeekMod(date('w', $unixMonth) - $weekBegins);
			if($pad != 0) {
				$content .= "\n\t\t\t".'<td colspan="'.$pad.'" class="pad">&nbsp;</td>';
			}
			 
			$daysInMonth = intval(date('t', $unixMonth));
			for ($day = 1; $day <= $daysInMonth; ++$day) {
				if(isset($newrow) && $newrow) {
					$content .= "\n\t\t</tr>\n\t\t<tr>\n\t\t\t";
				}
				$newrow = false;
	
				if($day == gmdate('j', (time() + ($addHours * 3600))) && $thisMonth == gmdate('m', time()+($addHours * 3600)) && $thisYear == gmdate('Y', time()+($addHours * 3600))) {
					$content .= '<td id="today">';
				} else {
					$content .= '<td>';
				}
	
				if(array_key_exists($day, $daysWithPosts)) {
					// any posts today?
					$content .= $this->getDayLink($conf,$unixMonth, $day, $daysWithPosts[$day],$conf);
				} else {
					$content .= $day;
				}
				$content .= '</td>';
	
				if (6 == $this->calendarWeekMod(date('w', mktime(0, 0 , 0, $thisMonth, $day, $thisYear))-$weekBegins)) {
					$newrow = true;
				}
			}
	
			$pad = 7 - $this->calendarWeekMod(date('w', mktime(0, 0 , 0, $thisMonth, $day, $thisYear))-$weekBegins);
			if ($pad != 0 && $pad != 7) {
				$content .= "\n\t\t\t".'<td class="pad" colspan="'.$pad.'">&nbsp;</td>';
			}
	
			$content .= "\n\t\t</tr>\n\t\t</tbody>\n\t\t</table></div>";
	
	
	
	
		}



		return $content;
	}

	/**
	 * gets the current time optionaly regarding GMT offset
	 *
	 * @param	boolean		get time without GMT offset when set to true
	 * @return	integer		the current timestamp
	 */
	function getCurrentTime($gmt = false) {
		if($gmt) {
			$time = time();
		} else {
			$time = time() + ($this->conf['gmt_offset'] * 3600);
		}

		return $time;
	}

	/**
	 * gets array with days where posts were made
	 *
	 * @param	integer		timestamp of the beginning of the month we want to get posts from
	 * @param	[type]		$monthBeginn: ...
	 * @return	array		array with days where posts were made
	 */
	function getDaysWithPosts($conf,$monthBeginn) {
		$monthEnd = $monthBeginn + ((int)date('t', $monthBeginn) * 24 * 3600);

		$userAgent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');
		if (strstr($userAgent, 'MSIE') || strstr(strtolower($userAgent), 'camino') || strstr(strtolower($userAgent), 'safari')) {
			//IE, Camino, Safari
			$titleSeparator = "\n";
		} else {
			//every other browser
			$titleSeparator = ', ';
		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'title, '.$conf['list.']['beginDateField'],
			$conf['table'],
			$conf['list.']['beginDateField'].' > '.$monthBeginn.' AND '.$conf['list.']['beginDateField'].' < '.$monthEnd.$this->enableFields.$conf['list.']['whereString'],
			$conf['list.']['beginDateField'].' ASC'
		);

		$daysWithPosts = array();
		foreach($result as $row) {
			$day = date('j', $row[$conf['list.']['beginDateField']]);
			if(!empty($daysWithPosts[$day])) {
				$daysWithPosts[$day] .= $titleSeparator.$row['title'];
			} else {
				$daysWithPosts[$day] = $row['title'];
			}
		}

		return $daysWithPosts;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$$conf: ...
	 * @return	[type]		...
	 */
	function getResetLink(&$conf) {
		$urlParams = array();
		$l = $this->metafeeditlib->getLL('calReset',$conf);
		$tagAttribs = ' title="'.$l.'"';

		$lconf = array(
			'useCacheHash'     => $this->conf['allowCaching'],
			'no_cache'         => !$this->conf['allowCaching'],
			'parameter'        => $GLOBALS['TSFE']->id,
			'additionalParams' => $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParams,'',1).$this->pi_moreParams.$conf['GLOBALPARAMS'],
			'ATagParams'       => $tagAttribs
		);
		return $this->cObj->typoLink($l, $lconf);
	}


	/**
	 * generates a typolink to the month of the given timestamp
	 *
	 * @param	integer		timestamp of the month to link to
	 * @param	integer		timestamp of the currently shown month
	 * @param	[type]		$now: ...
	 * @return	string		typolink
	 */
	function getMonthLink(&$conf,$timestampForTitle, $timestamp,$now) {
		$urlParams = array(
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][year]'  => date('Y', $timestamp),
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][month]' => date('m', $timestamp)
		);

		$tagAttribs = ' title="'.sprintf($this->metafeeditlib->getLL('view_posts',$conf), 		$GLOBALS['TSFE']->csConvObj->conv(strftime('%B %G', $timestampForTitle),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset)).'"';
		$lconf = array(
			'useCacheHash'     => $this->conf['allowCaching'],
			'no_cache'         => !$this->conf['allowCaching'],
			'parameter'        => $GLOBALS['TSFE']->id,
			'additionalParams' => $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParams,'',1).$this->pi_moreParams.$conf['GLOBALPARAMS'],
			'ATagParams'       => $tagAttribs
		);
		$link = $GLOBALS['TSFE']->csConvObj->conv(strftime('%b', $timestampForTitle),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset);
		if($timestampForTitle < $now) {
			$link = '&laquo; '.$link;
		} else {
			$link = $link.' &raquo;';
		}
		return $this->cObj->typoLink($link, $lconf);
	}


	/**
	 * generates a typolink to the month of the given timestamp
	 *
	 * @param	integer		timestamp of the month to link to
	 * @param	integer		timestamp of the currently shown month
	 * @param	integer		timestamp of the month used for text navigation
	 * @return	string		typolink
	 */
	function getCurentMonthLink(&$conf,$timestampForTitle, $timestamp,$now) {
		$urlParams = array(
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][year]'  => date('Y', $timestamp),
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][month]' => date('m', $timestamp)
		);

		$tagAttribs = ' title="'.sprintf($this->metafeeditlib->getLL('view_posts',$conf), 		$GLOBALS['TSFE']->csConvObj->conv(strftime('%B %G', $timestamp),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset)).'"';
		$lconf = array(
			'useCacheHash'     => $this->conf['allowCaching'],
			'no_cache'         => !$this->conf['allowCaching'],
			'parameter'        => $GLOBALS['TSFE']->id,
			'additionalParams' => $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParams,'',1).$this->pi_moreParams.$conf['GLOBALPARAMS'],
			'ATagParams'       => $tagAttribs
		);
		$link = $GLOBALS['TSFE']->csConvObj->conv(strftime('%B', $timestampForTitle),$GLOBALS['TSFE']->localeCharset,$GLOBALS['TSFE']->renderCharset);
		return $this->cObj->typoLink($link, $lconf);
	}
	/**
	 * generates a typolink to the day of the given timestamp
	 *
	 * @param	integer		timestamp of the month to link to
	 * @param	integer		the day to link to
	 * @param	string		the title attribute for the link
	 * @param	[type]		$title: ...
	 * @return	string		typolink
	 */
	function getDayLink(&$conf,$timestamp, $day, $title) {
		if($day < 10) {
			$day = '0'.$day;
		}
		$d1 = mktime(0, 0 , 0, date('m', $timestamp), $day, date('Y', $timestamp));
		$d2 = mktime(0, 0 , 0, date('m', $timestamp), $day+1, date('Y', $timestamp));

		$urlParams = array(
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][year]'  => date('Y', $timestamp),
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][month]' => date('m', $timestamp),
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].'][day]'   => $day,
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].']['.$conf['list.']['beginDateField'].'][op]' => '>ts<',
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].']['.$conf['list.']['beginDateField'].'][val]' => $d1,
			'tx_metafeedit[calendarSearch]['.$conf['pluginId'].']['.$conf['list.']['beginDateField'].'][valsup]' => $d2,
		);

		$tagAttribs = ' title="'.$title.'"';

		$lconf = array(
			'useCacheHash'     => $this->conf['allowCaching'],
			'no_cache'         => !$this->conf['allowCaching'],
			//'parameter'        => $this->conf['targetPid'],
			'parameter'        => $GLOBALS['TSFE']->id,
			'additionalParams' => $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParams,'',1).$this->pi_moreParams.$conf['GLOBALPARAMS'],
			'ATagParams'       => $tagAttribs
		);
		return $this->cObj->typoLink($day, $lconf);
	}

	/**
	 * returns an array with localized weekday names
	 *
	 * @param	[type]		$$conf: ...
	 * @return	array		array with localized weekday names
	 */
	function getWeekdays(&$conf) {
		$week = array(
			$this->metafeeditlib->getLL('sunday',$conf),
			$this->metafeeditlib->getLL('monday',$conf),
			$this->metafeeditlib->getLL('tuesday',$conf),
			$this->metafeeditlib->getLL('wednesday',$conf),
			$this->metafeeditlib->getLL('thursday',$conf),
			$this->metafeeditlib->getLL('friday',$conf),
			$this->metafeeditlib->getLL('saturday',$conf),
		);

		return $week;
	}

	/**
	 * I have no clue what this thing does (taken from wordpress)
	 *
	 * @param	integer		$num
	 * @return	integer		...
	 */
	function calendarWeekMod($num) {
		$base = 7;
		return ($num - $base * floor($num/$base));
	}
	
	/**
	 * PrepareCalendarTemplates
	 *
	 * @param	[type]		$TABLES: ...
	 * @param	[type]		$markerArray
	 * @return	[type]		...
	 */
	 
	 function prepareCalendarTemplates(&$conf,&$markerArray,$exporttype) {
		// template blocks
		$tpl=array();
		$tpl['templateCode'] = $this->metafeeditlib->getPlainTemplate($conf,$markerArray,'###TEMPLATE_CALENDAR'.($exporttype?'_'.$exporttype:'').'###');
		$tpl['weekCode'] = $this->cObj->getSubpart($tpl['templateCode'], '###WEEKDIV###');
		$tpl['hourLibCode'] = $this->cObj->getSubpart($tpl['weekCode'], '###HOURLIBDIV###');
		$tpl['dayCode'] = $this->cObj->getSubpart($tpl['weekCode'], '###DAYDIV###');
		$tpl['hourCode'] = $this->cObj->getSubpart($tpl['dayCode'], '###HOURDIV###');
		$tpl['catCtnrCode'] = $this->cObj->getSubpart($tpl['templateCode'], '###CATCTNRDIV###');
		$tpl['catCode'] = $this->cObj->getSubpart($tpl['catCtnrCode'], '###CATDIV###');
		return $tpl;
	}
	
	  /**
  * Displays Calendar Screens, Excel File, Pdf Page ...
  *
  * @param	[type]		$TABLES: ...
  * @param	[type]		$
  * @return	[type]		...
  */
    
	function displayCalendarScreen($TABLES,$DBSELECT,&$conf,&$markerArray,&$perfArray)	{

    if ($conf['performanceaudit']) $perfArray['class.tx_metafeedit_calendar.php displayCalendarScreen start:']=$this->metafeeditlib->displaytime()." Seconds";
	  //-- initialisation
		$conf['cmdmode']='cal';
		$content='';
		$DEBUG='';
		$dispDir= $conf['cal.']['displayDirection']?$conf['cal.']['displayDirection']:'Right'; //-- this should be handled in template choice...
		//---  Gestion des templates selon exporttype (can't this be done in Pi1 ?)
		$exporttype=$this->piVars['exporttype'];		
		if (($exporttype == 'PDF') && ($conf['list.']['nbCols'])) {	$exporttype = "PDFTAB";		}
		//---
		
		
		if (!$conf['no_action']) {
			$this->backURL=$this->metafeeditlib->makeBackURLTypoLink($conf,$this->backURL);
			if (!strpos($this->backURL,'?')) $this->backURL.='?';
			$markerArray['###BACK_URL###'] = $this->backURL;
		}
		
		// TOBE replaced by specific pagebrowser 
	  
	  //if ($conf['cal.']['pageSize']) $this->internal['results_at_a_time'] = $conf['list.']['pageSize']; // Number of results to show in a listing.
		
		//if ($conf['cal.']['pageSize']&& $conf['list.']['nbCols']) $this->internal['results_at_a_time'] = $cal['list.']['pageSize']*$conf['cal.']['nbCols'];
	  //  if ($conf['cal.']['maxPages']) $this->internal['maxPages'] = $conf['cal.']['maxPages']; // The maximum number of "pages" in the browse-box: "Page 1", 'Page 2', etc.
	  //  $this->internal['currentTable'] = $this->theTable;
		//-- end init...


		
		// We build the SQL Query
		$sql=$this->metafeeditlib->getListSQL($TABLES,$DBSELECT,$conf,$markerArray,$DEBUG);
		
		//--- we get sort variable and sort direction from GET/POST Vars (should this be not done in Pi1 ?)...hmm ...

		$Arr=explode(',',$conf['cal.']['show_fields']);
    foreach($Arr as $fieldName) {
			if ($conf['cal.']['sortFields']){
				$markerArray['%23%23%23SORT_DIR_'.$fieldName.'%23%23%23']=($fieldName==$this->internal['orderBy'])?($this->internal['descFlag']?'0':'1'):'1';

			}
        }
        //-- end sort calculations
        //-- page calculations
		// We handle pagination here and should just do a count here ...
		
    $cols=$conf['cal.']['nbCols'];
		$ps=$conf['cal.']['pageSize'];

		// default page size is 100
 
		if (!$ps) $ps=100;
		$ncols=1;
		$nbdispcols=0;
		if ($cols) $ncols=$cols;

		$nbe=$ncols; // number of elements per column (Down)/row (RIGHT) 
		$nbep=$ps*$ncols; //Number of elements per page
		if ($conf['list.']['jumpPageOnGroupBy']) $nbe=$nbep;
		if ($dispDir=='Down') {
			$nbdispcols=$ncols;
			$ncols=$cols=1;
			$nbe=$ps;
		}
        //-- end page calculations		
		// This counts the number of lines ...

		
		$distinct=$conf['general.']['useDistinct']?" distinct ":"";
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($distinct.$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having']);
	  if ($conf['debug.']['sql']) $this->metafeeditlib->debug('displayList row count ',$GLOBALS['TYPO3_DB']->SELECTquery($distinct.$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having']),$DEBUG);
		$num=$GLOBALS['TYPO3_DB']->sql_num_rows($res);	// If there are menu-items ...

		$NBSup=0; // Number of empty elements for page breaks and group by breaks;
		$pageidx=0;
		$pi=1; //page number we start at 1
		$pagedelta=array(); // array of page deltas for page browser
		$paged=0; // current page delta due to page and groupby breaks
		$oldpage=0; // pagechangeflag
		$pageid=1; // page number for grouby nb elts
		$mode=0;
		$NbGroupBys=0;

		// CALENDAR_SETUP
		if ($conf['list.']['calendarSearch']){			
					  $markerArray['###CALENDAR_SEARCH###']=$cal->main('', $conf,$sql);
		}

		// List SQL REQUEST with limitations, pagination, etc ...
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($distinct.$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql'].($exporttype?'':$LIMIT));
	   if ($conf['debug.']['sql']) $this->metafeeditlib->debug('displayList rows',$GLOBALS['TYPO3_DB']->SELECTquery($distinct.$sql['fields'], $sql['fromTables'], '1 '.$sql['where'].$sql['groupBy'].$sql['having'].$sql['orderBySql'].($exporttype?'':$LIMIT)),$DEBUG);

 		$tpl=$this->prepareCalendarTemplates($conf,$markerArray,$exporttype);
		// process variables
		
		// MODIF CBY
		// List alternate templates
		$nbAltRow=$conf['cal.']['nbAltRows'];
		$nar=1;

		$templateExport='';
		
		// List Item Loop  
		$days=array('mon','tue','wed','thu','fri','sat','sun','hol');
		$cats=array('entrée libre','badgeage','badgeage+code pin');

		$hour=0;
		$item='';
		$markerArray['###NATTR###']=1;
		while($hour<=24) {
			$markerArray['###HOURLIB###']=$hour.'h00';
			$item.=$this->cObj->substituteMarkerArray($tpl['hourLibCode'], $markerArray);
				//echo "oooo".$item." - $hour";
			$hour++;
			$markerArray['###NATTR###']=($markerArray['###NATTR###']==1?$markerArray['###NATTR###']=2:$markerArray['###NATTR###']=1);
		}

		$markerArray['###DAY###']='Planning';
		$j=$this->cObj->substituteSubpart($tpl['dayCode'], '###HOURDIV###', $item);
		$content.=$this->cObj->substituteMarkerArray($j, $markerArray);

		foreach($days as $day) {
			//echo $day;
			$markerArray['###DAY###']=$day;
			$markerArray['###CATTITLE###']='Catégories';//TO BE RMEOVED TO LANG FILE
			$hour=0;
			$item='';
			$markerArray['###NATTR###']=1;
			while($hour<=24) {
				$markerArray['###HOUR###']='';//$hour;
				$item.=$this->cObj->substituteMarkerArray($tpl['hourCode'], $markerArray);
				//echo "oooo".$item." - $hour";
				$hour++;
				$markerArray['###NATTR###']=($markerArray['###NATTR###']==1?$markerArray['###NATTR###']=2:$markerArray['###NATTR###']=1);
			}
			$j=$this->cObj->substituteSubpart($tpl['dayCode'], '###HOURDIV###', $item);
			$content.=$this->cObj->substituteMarkerArray($j, $markerArray);
		}
		
		// We handle categories here ...
		
		$content=$this->cObj->substituteSubpart($tpl['templateCode'], '###WEEKDIV###', $content);
		$ncat=1;
		$item='';
		foreach($cats as $cat) {
			$markerArray['###CALCAT###']=$cat;
			$markerArray['###NCAT###']=$ncat;	
			$item.=$this->cObj->substituteMarkerArray($tpl['catCode'], $markerArray);
			$ncat ++;
		}
		$item=$this->cObj->substituteSubpart($tpl['catCtnrCode'],'###CATDIV###' ,$item);
		$content=$this->cObj->substituteSubpart($content, '###CATCTNRDIV###', $item);
		$content=$this->cObj->substituteMarkerArray($content, $markerArray);
		
  if ($conf['performanceaudit']) $perfArray['class.tx_metafeedit_calendar.php displayCalendarScreen end:']=$this->metafeeditlib->displaytime()." Seconds";
	return $content.$DEBUG;

	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_calendar.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meta_feedit/class.tx_metafeedit_calendar.php']);
}

?>