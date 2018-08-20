<?php
    require_once 'inc/dbconnect.php';
    require_once 'inc/user_access.php';
    require_once 'inc/user_edit.php';

	if (! isset($action)) { $action = false; }

    //Create new object for instance to class Ven Reg that extends Ven Priveleges
    $venEdit = new VenEdit;

	$msg = '';
	if ($U['admin'] AND $action) {
		if ($action=='deactivate') {
			$venEdit->deactivateUser();
			$msg = 'User has successfully passed away.';
		} else if ($action=='activate') {
			$venEdit->activateUser();
			$msg = 'User has successfully woken up.';
		}
	}

	$alert = '';
	if ($msg) { $alert = '?ALERT='.urlencode($msg); }

	header('Location: user_management.php'.$alert);
	exit;
?>
