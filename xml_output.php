<?php
	/***************************************************************
	 * Copyright notice
	 *
	 * (c) 2004 macmade.net
	 * All rights reserved
	 *
	 * This script is part of the TYPO3 project. The TYPO3 project is 
	  * free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 *
	 * The GNU General Public License can be found at
	 * http://www.gnu.org/copyleft/gpl.html.
	 *
	 * This script is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/
	
	/** 
	 * XML page generation script for the 'fe_mp3player' extension.
	 *
	 * @author		Jean-David Gadina (macmade@gadlab.net)
	 * @version		2.0
	 */
	
	/**
	 * Creates XML output for Flash.
	 * 
	 * This function creates an XML output wich can be used by a SWF movie.
	 * 
	 * @return		The XML content.
	 */
	function writeXML() {
		
		// XML Storage
		$xml = array();
		
		// XML parameters storage
		$params = array();
		
		// Auto start
		$params['autoStart'] = (t3lib_div::_GP('autoStart')) ? 'yes' : 'no';
		
		// Show playlist
		$params['showPlaylist'] = (t3lib_div::_GP('showPlaylist')) ? 'yes' : 'no';
		
		// Show display
		$params['showDisplay'] = (t3lib_div::_GP('showDisplay')) ? 'yes' : 'no';
		
		// GSkin color
		$params['skinColor'] = (t3lib_div::_GP('gskinColor'));
		
		// Playlist UID
		$playlist = t3lib_div::_GP('playlist');
		
		// Select requested playlist
		if ($playlist) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_femp3player_playlists','tx_femp3player_playlists.uid=' . $playlist);
		
		// Get songs
		$songs = ($res) ? $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res) : false;
		}
				$mediaFile= t3lib_div::_GP('mediafile');
		//echo "$mediaFile ...";

		// Check MySQL ressource
		if ((is_array($songs) && $songs['hidden'] != 1)|| $mediaFile)  {
			
			// XML declaration
			$xml[] = '<?xml version="1.0" encoding="utf-8"?' . '>';
			
			// XML root start
			$xml[] = '<player ' . tx_apimacmade::div_writeTagParams($params) . '>';
			
			// Check type
			if ($songs['type'] == 0) {
				
				// Get an array with songs
				$songsArray = tx_apimacmade::div_xml2array($songs['playlist'],0);
				
				// Check keys
				if (tx_apimacmade::div_checkArrayKeys($songsArray,'T3FlexForms,data,sDEF,lDEF,fields,el')) {
					
					// Process each MP3 file
					foreach($songsArray['T3FlexForms']['data']['sDEF']['lDEF']['fields']['el'] as $mp3File) {
						
						// Check element type
						if (tx_apimacmade::div_checkArrayKeys($mp3File,'mp3,el,file,vDEF')) {
							
							// MP3 file
							$mp3_path = 'uploads/tx_femp3player/' . $mp3File['mp3']['el']['file']['vDEF'];
							
							// Check if file exists
							if (@is_file(t3lib_div::getFileAbsFileName($mp3_path))) {
								
								// MP3 title
								$mp3_title = (tx_apimacmade::div_checkArrayKeys($mp3File,'mp3,el,title,vDEF')) ? $mp3File['mp3']['el']['title']['vDEF'] : $mp3File['mp3']['el']['file']['vDEF'];
								
								// Add song tag
								$xml[] = '<song path="' . str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($mp3_path)) . '" title="' . utf8_encode($mp3_title) . '" />';
							}
						} else if (tx_apimacmade::div_checkArrayKeys($mp3File,'path,el,path,vDEF')) {
							
							// MP3 path
							$mp3_path = $mp3File['path']['el']['path']['vDEF'];
							
							// Check if file exists
							if (@is_file(t3lib_div::getFileAbsFileName($mp3_path))) {
								
								// Check for a title
								if (tx_apimacmade::div_checkArrayKeys($mp3File,'path,el,title,vDEF')) {
									
									// MP3 title
									$mp3_title = $mp3File['path']['el']['title']['vDEF'];
									
								} else {
									
									// Explode path segments
									$path_info = explode('/',$mp3File['path']['el']['path']['vDEF']);
									
									// MP3 title
									$mp3_title = array_pop($path_info);
								}
								
								// Add song tag
								$xml[] = '<song path="' . str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($mp3_path)) . '" title="' . utf8_encode($mp3_title) . '" />';
							}
						}
					}
				}
				if ($mediaFile) {
												
												$xml[] = '<song path="' . str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($mediaFile)) . '" title="' . utf8_encode($mediaFile) . '" />';

				}
				
			} else if ($songs['type'] == 1) {
				
				// Get directory path
				$uploadDir = str_replace(PATH_site,'',t3lib_div::getFileAbsFileName($songs['dir_path']));
				
				// Check for a trailing slash
				if (strrpos($uploadDir,'/') != strlen($uploadDir) - 1) {
					
					// Add trailing slash
					$uploadDir .= '/';
				}
				
				// Get an array with songs
				$songsArray = explode(',',$songs['dir_songs']);
				
				// Unify linebreaks for titles
				$titles = tx_apimacmade::div_convertLineBreaks($songs['dir_titles']);
				
				// Get an array with titles
				$titlesArray = explode(chr(10),$titles);
				
				// Process each MP3 file
				foreach($songsArray as $key=>$mp3File) {
					
					// MP3 path
					$mp3_path = $uploadDir . $mp3File;
					
					// Get raw title
					$mp3_title = (is_array($titlesArray) && array_key_exists($key,$titlesArray) && $titlesArray[$key] != '') ? $titlesArray[$key] : $mp3File;
					
					// Check if file exists
					if (@is_file(t3lib_div::getFileAbsFileName($mp3_path))) {
						
						// Add song tag
						$xml[] = '<song path="' . $mp3_path. '" title="' . utf8_encode($mp3_title) . '" />';
					}
				}
				
			} else {
				
				// Check PodCast type
				if ($songs['type'] == 2) {
					
					// Get remote PodCast XML content
					$podCastXML = @file_get_contents($songs['podcast_url']);
					
				} else {
					
					// Get PodCast XML content from nbo_podcast
					$podCastXML = @file_get_contents(t3lib_div::getFileAbsFileName('nbo_podcast/' . $songs['nbo_podcast']));
				}
				
				// Check XML content
				if ($podCastXML) {
					
					// Convert XML to a PHP array
					$podCast = tx_apimacmade::div_xml2array($podCastXML);
					
					// Check XML structure
					if (tx_apimacmade::div_checkArrayKeys($podCast,'rss,channel')) {
						
						// Process PodCast items
						foreach($podCast['rss']['channel'] as $key=>$value) {
							
							// Check if XML node contains a link section
							if (substr($key,0,4) == 'item' && tx_apimacmade::div_checkArrayKeys($value,'enclosure,xml-attribs,url')) {
								
								// Podcast file
								$podCastFile = $value['enclosure']['xml-attribs']['url'];
								
								// Check if file has .mp3 extension
								if (strrpos($podCastFile,'.mp3') == (strlen($podCastFile) - 4)) {
									
									// check for a title
									if (array_key_exists('title',$value) && $value['title'] != '') {
										
										// MP3 title
										$mp3_title = $value['title'];
										
									} else {
										
										// Explode path segments
										$path_info = explode('/',$podCastFile);
										
										// MP3 title
										$mp3_title = array_pop($path_info);
									}
									
									// Add song tag
									$xml[] = '<song path="' . $value['enclosure']['xml-attribs']['url'] . '" title="' . utf8_encode($mp3_title) . '" />';
								}
							}
						}
					}
				}
			}
			
			// XML root end
			$xml[] = '</player>';
		}
		// Return XML code
		return implode(chr(10),$xml);
	}
	
	// Include Developer API class
	include_once(t3lib_extMgm::extPath('api_macmade') . 'class.tx_apimacmade.php');
	
	// DEBUG ONLY - Show XML
	#echo('<pre>' . htmlspecialchars(writeXML()) . '</pre>');
	
	// Write XML
	echo(writeXML());
	
	// Exit
	exit();
?>
