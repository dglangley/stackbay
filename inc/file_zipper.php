<?php
	// Files is an array of files
	// $files = array('readme.txt', 'test.html', 'image.gif');

	function zipFiles($filelist, $item_id, $item_label) {
		// Location of said files
		$BUCKET = 'arn:aws:s3:::ventel.stackbay.com-docs';

		$dirlist = new RecursiveDirectoryIterator($BUCKET);

		// $filelist = new RecursiveIteratorIterator($dirlist);

		//how many file can be added before a reopen is forced?
		$filelimit = 245; 

		// Defines the action
		$file = tempnam("tmp", "zip");
		$zip = new ZipArchive();

		if ($zip->open($file, ZipArchive::OVERWRITE) !== TRUE) {
		    die ("Could not open archive");
		}

		// adds files to the file list
		foreach ($filelist as $key) {

		    //fix archive paths
		    $path = str_replace($BUCKET, "", $key); //remove the source path from the $key to return only the file-folder structure from the root of the source folder
		    
		    if (! file_exists($key)) { 
		    	die($key.' does not exist. Please contact your administrator or try again later.'); 
		    }
		    
		    if (! is_readable($key)) { 
		    	die($key.' not readable. Please contact your administrator or try again later.'); 
		    }     
		    
		    if ($zip->numFiles == $filelimit) {
		    	$zip->close(); $zip->open($file) or die ("Error: Could not reopen Zip");
		    }

		    $zip->addFromString($path, $key) or die ("ERROR: Could not add file: $key </br> numFile:".$zip->numFiles);
		    $zip->addFile(realpath($key), $path) or die ("ERROR: Could not add file: $key </br> numFile:".$zip->numFiles);
		    
		}

		// closes the archive
		$zip->close();

		// Makes the user download the zipped files
		// header('Content-Type: application/zip');
		// header('Content-disposition: attachment; filename='.$zipname);
		// header('Content-Length: ' . filesize($zipname));
		// readfile($zipname);
	}