<?php
	if (! isset($root_dir)) {
		$root_dir = '';
		if (isset($_SERVER["HOME"]) AND $_SERVER["HOME"]=='/Users/davidglangley') { $root_dir = '/Users/Shared/WebServer/Sites/lunacera.com/db'; }
		else if (isset($_SERVER["DOCUMENT_ROOT"]) AND $_SERVER["DOCUMENT_ROOT"]) { $root_dir = preg_replace('/\/$/','',$_SERVER["DOCUMENT_ROOT"]).'/db'; }
		else { $root_dir = '/var/www/html/db'; }
	}
	include_once $root_dir.'/inc/mconnect.php';
	include_once $root_dir.'/inc/signout.php';

	$signout_url = signout();

	session_destroy();

	if (isset($_REQUEST['json'])) {
		echo json_encode(array('code'=>0,'message'=>'Success!'));
	} else {
		header('Location: /');
	}
	exit;
?>
