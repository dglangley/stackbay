<?php
	include '../inc/dbconnect.php';
	include '../inc/jsonDie.php';
	include '../inc/lici.php';

    $taskid = 0;
    $task_label = '';
    $type = '';
	
	if (isset($_REQUEST['taskid'])) { $taskid = $_REQUEST['taskid'];}
    if (isset($_REQUEST['task_label'])) { $task_label = $_REQUEST['task_label'];}
    if (isset($_REQUEST['type'])) { $type = $_REQUEST['type'];}

	$ERR = '';
    $id = lici($taskid, $task_label, $type);

	if ($id===false) {
		jsonDie($ERR);
	}

	echo json_encode(array('id'=>$id,'message'=>$ERR));
    exit;
