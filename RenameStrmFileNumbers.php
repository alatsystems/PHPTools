<?php

/**
 * @author: Muhammed Ali Alat
*/

/**
* TODO: Usage of second parameter to move down or up
*/

class RenameStrmFileNumbers {

	var $starttime;
	var $path;
	var $files;
	var $moveUp;
	var $no;

	function __construct($no) {
		$this->starttime = time();
		$this->path = getcwd();
		$this->files = array();
		$this->moveUp = TRUE;
		$this->no = $no;
	}

	function start() {
		$handle = @opendir($this->path);
		if ($handle === FALSE) {
			echo 'Could not handle ' . $this->path . '.' . chr(10);
			return;
		}
		echo $this->path . '/' . chr(10);

		$report = array();

		while (FALSE !== ($file = readdir($handle))) {
			$curPathFile = $this->path . '/' . $file;
			if ($file == '.' || $file == '..') {
				continue;
			}
			if(is_link($curPathFile)) {
				continue;
			}
			if(is_file($curPathFile)) {
				$filenameParts = explode(':', $file);
				// Only strm extension
				if(substr($file, -4) != 'strm'){
					$report[$curPathFile] = $curPathFile . chr(9) . '(Not a stream file)' . chr(10);
				} else if(is_numeric($filenameParts[0])) {
					$currentNo = intval($filenameParts[0]);
					if($currentNo >= $this->no) {
						$newNo = $currentNo + 1;
						if($currentNo < 10) {
							$currentNo = '0' . $currentNo;
						}
						$currentNo .= ':';
						if($newNo < 10) {
							$newNo = '0' . $newNo;
						}
						$newNo .= ':';
						$newFile = str_replace($currentNo, $newNo ,$file);
						rename($this->path . '/' . $file, $this->path . '/' . $newFile);
						$report[$curPathFile] = $curPathFile . chr(9) . '=>' . chr(9) . $newFile . chr(10);
					} else {
						$report[$curPathFile] = $curPathFile . chr(10);
					}
				} else {
					$report[$curPathFile] = $curPathFile . chr(9) . '(File has no channel number)' . chr(10);
				}
			}
		}

		ksort($report);

		foreach($report as $oneLine) {
			echo $oneLine;
		}

		closedir($handle);

		echo '------------------------------' . chr(10);
		echo 'Duration:' . chr(9) . (time() - $this->starttime) . ' seconds' . chr(10);
		echo '------------------------------' . chr(10);
	}

}

if(!isset($argv[1])) {
	exit('Usage: php RenameStrmFileNumbers.php [NO] [UP|DOWN]' . chr(10));
} else if(!is_numeric($argv[1])) {
	exit($argv[1] . ' is not a number.' . chr(10) . 'Usage: php RenameStrmFileNumbers.php [NO] [UP|DOWN]' . chr(10));
}

$obj = new TVList($argv[1]);
$obj->start();

?>
