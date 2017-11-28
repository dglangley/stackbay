<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/attachInvoice.php';

	function sendInvoice($invoice) {
		$err = false;

		// render invoice and attach to a temp file, we get attachment as a temp file pointer
		$attachment = attachInvoice($invoice);

		if (! $GLOBALS['DEV_ENV']) {
			// set google session to amea for sending email below
			setGoogleAccessToken(5);

			// bcc david for now, so I can see what Joe is seeing
			$send_success = send_gmail('See attached Invoice '.$invoice,'Invoice '.$invoice,'accounting@ven-tel.com','david@ven-tel.com','',$attachment);

			if(!$send_success){
				$err = "Email not sent ".$GLOBALS['SEND_ERR'];
			}
		}

		return ($err);
	}
?>
