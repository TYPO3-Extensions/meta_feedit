<?php
/**
 * Typo3 clear cache Hook methods.
 * @author cbalisky
 *
 */
class Tx_MetaFeedit_Hook_ClearCache {
	/**
	 * 
	 * @param unknown_type $dir
	 */
	private function delTree($dir) {
		$files = glob( $dir . '*', GLOB_MARK );
		foreach( $files as $file ){
			if( substr( $file, -1 ) == '/' )
				$this->delTree( $file );
			else
				//error_log(__METHOD__.":$file");
				unlink( $file );
		}
	
		if (is_dir($dir)) rmdir( $dir );
	
	}
	/**
	 * clearCachePostProc hook.
	 * clears metafedit's report cache
	 */
	public function postProc() {
		$finalCacheDirectory = PATH_site . 'typo3temp/Cache/Reports/';
		//error_log(__METHOD__.":$finalCacheDirectory");
		//Security so that we don't delete "/"
		if (strlen($finalCacheDirectory)>10) {
			$this->delTree($finalCacheDirectory);
		}
	}
	
}