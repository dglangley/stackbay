<?php
	setcookie('userid',$_REQUEST['userid'],0,'/');

	header('Location: /');
	exit;
?>
