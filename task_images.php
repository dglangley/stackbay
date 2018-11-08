<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	// File Zipper and also File save to BUCKET
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/file_zipper.php';

	$DEBUG = 0;
	$ALERT = '';

	function insertDocument($doc_type, $taskid, $notes, $T) {

		if(! empty($_FILES)) {
			if(! $_FILES['files']['error'] AND ! empty($_FILES['files'])) {

				$BUCKET = 'ventel.stackbay.com-docs';

				$name = $_FILES['files']['name'];
				$temp_name = $_FILES['files']['tmp_name'];
				$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
				$file_url = saveFile($file);

				$query = "INSERT INTO service_docs (filename, notes, datetime, userid, type, item_label, item_id) VALUES (".fres($file_url).", ".fres($notes).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($doc_type).", ".fres($T['item_label']).", ".fres($taskid).");";
				qedb($query);
			}

			if(! $_FILES['filesImage']['error'] AND ! empty($_FILES['filesImage'])) {

				$BUCKET = 'ventel.stackbay.com-docs';

				$name = $_FILES['filesImage']['name'];
				$temp_name = $_FILES['filesImage']['tmp_name'];
				$file = array('name'=>str_replace($TEMP_DIR,'',$name),'tmp_name'=>$temp_name);
				$file_url = saveFile($file);

				$query = "INSERT INTO service_docs (filename, notes, datetime, userid, type, item_label, item_id) VALUES (".fres($file_url).", ".fres($notes).", ".fres($GLOBALS['now']).", ".fres($GLOBALS['U']['id']).", ".fres($doc_type).", ".fres($T['item_label']).", ".fres($taskid).");";
				qedb($query);
			}
		} 
	}

	function closeOut($files, $taskid, $order_number, $T) {
		global $ALERT;


		$fileList = array();

		foreach($files as $docID) {
			$query = "SELECT filename, notes FROM service_docs WHERE id = ".res($docID).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0) {
				$r = mysqli_fetch_assoc($result);
				if($r['notes']) {
					$fileList[str_replace(' ', '_', $r['notes'])] = $r['filename'];
				} else {
					$path_parts = pathinfo($r['filename']);
					$fileList[str_replace(' ', '_', $path_parts['filename'])] = $r['filename'];
				}
			}
		}

		// print_r($fileList);

		// die('here');
		zipFiles($fileList, $taskid, $T['item_label'], $order, $T['type']);

	}

	function deleteDocument($doc_id) {
		global $USER_ROLES, $ALERT;

		$userid = 0;

		// First do a check to see if the current user deleting this file is matching the service doc userid uploader
		$query = "SELECT * FROM service_docs WHERE id = ".res($doc_id).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);

			$userid = $r['userid'];
		}

		// If the userid currently is matching that of the deleting doc then delete or if the user is an admin
		if($userid = $GLOBALS['U']['id'] OR array_intersect($USER_ROLES,array(1,4))) {
			$query = "DELETE FROM service_docs WHERE id = ".res($doc_id).";";
			qedb($query);
		} else {
			$ALERT = "Warning. You are trying to delete another user's uploaded document. Permission denied!";
		}
	}

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }

	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }

	$type = '';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$delete = '';
	if (isset($_REQUEST['delete'])) { $delete = trim($_REQUEST['delete']); }

	$doc_type = '';
	if (isset($_REQUEST['doc_type'])) { $doc_type = trim($_REQUEST['doc_type']); }

	$notes = '';
	if (isset($_REQUEST['notes'])) { $notes = trim($_REQUEST['notes']); }

	$files = '';
	if (isset($_REQUEST['files'])) { $files = $_REQUEST['files']; }

	$T = order_type($type);

	if(! empty($files)) {
		closeOut($files, $taskid, $order_number, $T);
	} elseif($delete) {
		deleteDocument($delete);
	} else {
		insertDocument($doc_type, $taskid, $notes, $T);
	}

	// Responsive Testing
	$responsive = false;
	if (isset($_REQUEST['responsive'])) { $responsive = trim($_REQUEST['responsive']); }

	$link = '/service.php';

	if($responsive) {
		$link = '/responsive_task.php';
	} 

	header('Location: '.$link.'?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=images' . ($ALERT?'&ALERT='.$ALERT:''));

	// header('Location: /service.php?order_type='.ucwords($type).'&taskid=' . $taskid . '&tab=images' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
