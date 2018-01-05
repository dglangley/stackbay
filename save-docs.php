<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/saveFiles.php';

	$DEBUG = 0;
	if ($DEBUG) { print "<pre>".print_r($_REQUEST,true)."</pre>"; }
	if ($DEBUG) { print "<pre>".print_r($_FILES,true)."</pre>"; }

	$title = '';
	if (isset($_REQUEST['title'])) { $title = trim($_REQUEST['title']); }
	$deleteid = 0;
	if (isset($_REQUEST['deleteid'])) { $deleteid = trim($_REQUEST['deleteid']); }

	if ($deleteid) {
		$query = "DELETE FROM documentation WHERE id = '".res($deleteid)."'; ";
		$result = qedb($query);
	}

	if (! empty($_FILES)) {
		$BUCKET = 'ventel.stackbay.com-docs';

		$file = $_FILES['files'];
//		if ($file['error']) { continue; }

		$file_url = saveFile($file);

		$query = "REPLACE documentation (title, file, userid, datetime) ";
		$query .= "VALUES ('".res($title)."', '".res($file_url)."', '".$U['id']."', '".$now."'); ";
		$result = qedb($query);
	}

	if ($DEBUG) { exit; }

	header('Location: company_info.php');
	exit;
?>
