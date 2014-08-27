<?php
/**
 * @author: Muhammed Ali Alat
 * @version: 0.2.0
 * @date: 20.02.2014
 * 
 * Multiple Files Finder
 * 
 * Usage: php MultipleFilesFinder.php -[d|t] [BYTES]
 * Or as Cronjob without --debug
 * 
*/
class BigFilesFinder {

	public $conf;
	public $log;
	public $multiple;

	/**
	 * Generate default log structure and load configs
	*/
	function __construct($debug, $threshold) {
		$this->log = array(
			'starttime' => time(),
			'endtime' => 0,
			'files' => 0,
			'error' => array(),
			'filesize' => array()
		);
		$this->multiple = array();
		$this->conf = array(
			# path to monitoring files with md5 sums
			'path' => getcwd() . '/',
			'debug' => $debug,
			'threshold' => $threshold
		);
		if(empty($this->conf['path'])) {
			echo 'No path settings in configuration found.' . chr(10);
			exit(0);
		}

	}

	/**
	 * Run Server check task
	*/
	function run() {

		// Check path and files on server
		if($this->conf['debug']) echo 'Starting path and files checking.' . chr(10);
		
		// Possibility to check different locations once
		if(is_array($this->conf['path'])) {
			foreach($this->conf['path'] as $onePath) {
				if(empty($onePath)) {
					continue;
				}
				$this->checkPath($onePath);
			}
		} else {
			$this->checkPath($this->conf['path']);
		}
		echo chr(10);
		$this->orderBigFiles();
		$this->finishTask();
		return TRUE;
	}

	/**
	 * check Paths and generate md5 hashs
	*/
	function checkPath($path){
		$openedDir = @opendir($path);
		if($openedDir === FALSE) {
			if($this->conf['debug']) echo 'Path <' . $path . '> could not be found.' . chr(10);
			$this->log['error'][] = date('d.m.Y H:i[s]') . ':Path <' . $path . '> could not be found.';
		}
		if($this->conf['debug']) {
			if( count(explode('/', $path)) < 6) {
				echo chr(10) . 'Check dir ' . $path . ' ';
			}
		}
		while($oneFile = readdir($openedDir)) {
			if($oneFile == '.' || $oneFile == '..') {
				continue;
			}
			if(is_link($path . $oneFile)) {
				continue;
			}
			if(is_dir($path . $oneFile)) {
				$this->checkPath($path . $oneFile . '/');
			} else if(file_exists($path . $oneFile)) {
				$this->log['files']++;
				if($this->conf['debug']) {
					echo '.';
				}
				$fileSize = filesize($path . $oneFile);
				if($fileSize < $this->conf['threshold']) {
					continue;
				}
				$this->log['filesize'][$fileSize][] = $path . $oneFile;
				if( count($this->log['filesize'][$fileSize]) > 1 && $this->conf['debug']) {
					echo count($this->log['filesize'][$fileSize]);
				}
				if($this->conf['debug']) {
					echo '. ';
				}
			} else {
				if($this->conf['debug']) {
					echo 'File <' . $path . $oneFile . '> could not be found.' . chr(10);
					$this->log['error'][] = date('d.m.Y H:i[s]') . ': File <' . $path . $oneFile . '> could not be found.';
				}
			}
		}
		closedir($openedDir);
	}

	function orderBigFiles() {
		$maxSafedBytes = 0;
		$countMultipleFiles = 0;
		$allMultipleFiles = 0;
		ksort($this->log['filesize']);
		foreach ($this->log['filesize'] as $filesize => $files) {
			if($this->conf['debug']) {
				echo number_format($filesize / 1048576, 3) . ' MB:' . chr(10);
			}
			foreach($files as $oneFile) {
				if($this->conf['debug']) {
					echo $oneFile . chr(10);
				}
			}
		}
	}

	/**
	 * Finish Server Check Task and write files
	*/
	function finishTask() {
		$this->log['endtime'] = time();
return;
		// Writing log file for errors found
		if(!empty($this->log['error']) || !empty($this->multiple)) {
			$errorLogFile = __DIR__ . '/logfile-' . date('Y-m-d-H-i-s') . '.log';
			if (!$openedFile = fopen($errorLogFile, 'a')) {
				if($this->conf['debug']) {
					echo 'Datei kann nicht geoeffnet werden: ' . $errorLogFile;
				}
				$this->log['error'][] = date('d.m.Y H:i[s]') . ': Can not open file <' . $errorLogFile . '>.';
			} else {
				$errorContent = implode(chr(10), $this->log['error']);
				foreach($this->multiple AS $filesize => $files) {
					$errorContent .= chr(10) . number_format($filesize / 1048576, 3) . ' MB: ' . chr(10);
					foreach($files as $md5Files) {
						$errorContent .= implode(chr(10), $md5Files) . ' |' . chr(10);
					}
				}
				if (!fwrite($openedFile, $errorContent)) {
					if($this->conf['debug']) {
						echo 'Datei kann nicht beschrieben werden: ' . $errorLogFile;
					}
					$this->log['error'][] = date('d.m.Y H:i[s]') . ': Can not write to file <' . $md5File . '>.';
				}
				fclose($openedFile);
			}
		}
	}
}

$debug = FALSE;
$threshold = 1048576;

if(isset($argv[1])) {
	if(strtolower($argv[1]) == '-d') {
		$debug = TRUE;
	}
}
if(isset($argv[2])) {
	if(intval($argv[2]) > 0 ) {
		$threshold = intval($argv[2] * 1048576);
		if($debug) {
			echo 'Threshold set to ' . number_format($threshold / 1048576, 3) . ' MB' . chr(10);
		}
	}
}

if($debug) {
	echo 'Starting Server Check.' . chr(10);
}
$obj = new BigFilesFinder($debug, $threshold);
if($debug) {
	echo 'Configuration loaded. Starting Task.' . chr(10);
}
$obj->run();
if($debug)  {
	echo $obj->log['files'] . ' files checked in ' . ($obj->log['endtime'] - $obj->log['starttime']) . ' seconds.' . chr(10);
	echo 'Finished successful.' . chr(10);
}

/*
TODO
- Save all filesize() in an array
- if multiple files with same filesize exists, check md5 value

*/

?>
