<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/getUser.php';

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$listid = 0;
	if (isset($_REQUEST['id'])) { $listid = trim($_REQUEST['id']); }
	if (! is_numeric($listid)) {
		reportError("Requested list is invalid!");
	}

	$query = "SELECT uploads.*, search_meta.datetime, companies.name FROM uploads, search_meta, companies ";
	$query .= "WHERE uploads.metaid = search_meta.id AND companies.id = search_meta.companyid ";
	$query .= "AND uploads.id = '".res($listid)."'; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)==0) {
		reportError("Requested list does not exist!");
	}
	$list = mysqli_fetch_assoc($result);
	$list['datetime'] = format_date($list['datetime'],'D n/j/y g:ia');
	$user_names = explode(' ',getUser($list['userid']));
	$list['user'] = $user_names[0];
	// erase link if it doesn't exist anymore
	if (! file_exists($list['link'])) { $list['link'] = ''; }
	$temp_dir = sys_get_temp_dir();
	if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
	$list['link'] = str_replace($temp_dir,'/uploads/',$list['link']);

	if ($list['processed']) {
		$list['processed'] = format_date($list['processed'],'D n/j/y g:ia');
	} else {
		$list['processed'] = '';//utf8_encode($list['processed']);
	}

	echo json_encode($list);
	exit;
?>
