<?php

/**
 * @author: Muhammed Ali Alat
*/

class ImagesRenameToDate {

	var $starttime;
	var $path;
	var $stat;
	var $files;

	function __construct($path, $simulation) {
		$this->starttime = time();
		$this->path = $path;
		$this->stat = array(
			'simulation' => $simulation,
			'all' => 0,
			'mv' => 0,
			'notJpg' => 0,
			'notFile' => 0
		);
		$this->files = array();
	}

	function start() {
		$handle = @opendir($this->path);
		if ($handle === FALSE) {
			echo 'Could not handle ' . $this->path . '.' . chr(10);
			return;
		}
		echo $this->path . '/' . chr(10);
		while (FALSE !== ($file = readdir($handle))) {
			$curPathFile = $this->path . '/' . $file;
			if ($file == '.' || $file == '..') {
				continue;
			}
			$this->stat['all']++;
			if(is_link($curPathFile)) {
				$this->stat['notFile']++;
				continue;
			}
			if(is_file($curPathFile)) {
				// Only JPGs
				if(exif_imagetype($curPathFile) != IMAGETYPE_JPEG){
					echo 'NOT AN IMAGE:' . chr(9) . $curPathFile . chr(10);
					$this->stat['notJpg']++;
					continue;
				}
				$exif = exif_read_data($curPathFile);
				if(!isset($exif['DateTimeOriginal'])) {
					continue;
				}
				$dateTimeOriginal = substr($exif['DateTimeOriginal'], 0, 4) . '-' . substr($exif['DateTimeOriginal'], 5, 2) . '-' . substr($exif['DateTimeOriginal'], 8, 2) . '-' . substr($exif['DateTimeOriginal'], 11, 2) . substr($exif['DateTimeOriginal'], 14, 2) . substr($exif['DateTimeOriginal'], 17, 2);
				$this->files[$dateTimeOriginal][] = $file;
			} else if( is_dir($curPathFile) ){
				$this->stat['notFile']++;
			} else {
				$this->stat['notFile']++;
				echo 'Nothing to do with ' . $file . chr(10);
			}
		}
		closedir($handle);

		$this->renameImages();

		echo '------------------------------' . chr(10);
		echo ($this->stat['simulation'] ? 'Simulation!' . chr(10) : '');
		echo 'Files:' . chr(9) . chr(9) . $this->stat['all'] . chr(10);
		echo 'Renamed:' . chr(9) . $this->stat['mv'] . chr(10);
		echo 'Not an image:' . chr(9) . $this->stat['notJpg'] . chr(10);
		echo 'Not a file:' . chr(9) . $this->stat['notFile'] . chr(10);
		echo 'Duration:' . chr(9) . (time() - $this->starttime) . ' seconds' . chr(10);
		echo '------------------------------' . chr(10);
	}

	function renameImages() {

		ksort($this->files);

		foreach($this->files as $oneDateTime => $files) {
			$i = 1;
			$dec = 1;
			if(count($files) > 1) {
				sort($files);
				$dec = strlen(count($files));
			}
			foreach($files as $oneFile) {
				if(count($files) > 1) {
					$newFileName = sprintf($oneDateTime . '-%1$0' . $dec .  'd', $i++);
					if(!$this->stat['simulation']) rename($this->path . '/' . $oneFile, $this->path . '/' . $newFileName . '.jpg');
					echo 'Rename ' . $oneFile . ' => ' . $newFileName . '.jpg' . chr(10);
				} else {
					if(!$this->stat['simulation']) rename($this->path . '/' . $oneFile, $this->path . '/' . $oneDateTime . '.jpg');
					echo 'Rename ' . $oneFile . ' => ' . $oneDateTime . '.jpg' . chr(10);
				}
				$this->stat['mv']++;
			}
		}
	}

	function readInput() {
		echo 'test' . chr(10);
		echo 'Are you sure you want to do this?  Type "yes" to continue: ';
		$handle = fopen('php://stdin','r');
		return fgets($handle);
	}

}

if(!isset($argv[1])) {
	exit('Usage: php ImagesRenameToDate.php /PATH/TO/IMAGES/FOLDER' . chr(10));
} else if(!is_dir($argv[1])) {
	exit($argv[1] . ' is not a Directory.' . chr(10) . 'Usage: php ImagesRenameToDate.php /PATH/TO/IMAGES/FOLDER' . chr(10));
}

$simulation = FALSE;
if(isset($argv[2])) {
	$simulation = TRUE;
}


$obj = new ImagesRenameToDate($argv[1], $simulation);
$obj->start();

?>
