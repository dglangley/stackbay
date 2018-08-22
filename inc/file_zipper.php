<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	// Files is an array of files
	// Full path names
	// $files = array('/url/readme.txt', 'test.html', 'image.gif');

	function zipFiles($filelist, $item_id, $item_label, $order_number, $order_type) {
		// Location of said files
		$BUCKET = 'ventel.stackbay.com-order-uploads';

		//how many file can be added before a reopen is forced?
		$filelimit = 245; 

		// Defines the action
		// $fileList = array('https://s3-us-west-2.amazonaws.com/ventel.stackbay.com-order-uploads/20180320_152154170495637150624.jpg');

		$file = $order_type.'_'.$order_number.'_'.$item_id.'.zip';
		$zip = new ZipArchive();

		if ($zip->open($file, ZipArchive::CREATE) !== TRUE) {
		    die ("Could not open archive");
		}

		// adds files to the file list
		foreach ($filelist as $filename => $key) {    

		    $download_file = file_get_contents($key);
		    
		    if ($zip->numFiles == $filelimit) {
		    	$zip->close(); $zip->open($file) or die ("Error: Could not reopen Zip");
		    }

		    $ext = new SplFileInfo($key);

		    $zip->addFromString($filename.'.'.$ext->getExtension(), $download_file) or die ("ERROR: Could not add file: $key </br> numFile:".$zip->numFiles);
		    //$zip->addFile($key, basename($key)) or die ("ERROR: Could not add file: $key </br> numFile:".$zip->numFiles);
		    
		}

		// closes the archive
		$zip->close();

		// Save the zip file into the preset $BUCKET above
		// $zip_url = saveFile($file);

		// if($zip_url) {
		// 	$query = "INSERT INTO service_docs (item_id, item_label, filename, datetime, userid, type) VALUES (".res($item_id).",".fres($item_label).", ".fres($zip_url).", ".fres($GLOBALS['now']).",".fres($GLOBALS['U']['id']).", 'COP');";
		// 	qdb($query) OR die(qe() . "<BR>" . $query);
		// } 

		// else {
		// 	echo 'Failed to save ' . $file;
		// }

		// Makes the user download the zipped files
		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename='.$file);
		header('Content-Length: ' . filesize($file));
		readfile($file);

		if(file_exists($file)){
		    unlink($file);
		}

	}