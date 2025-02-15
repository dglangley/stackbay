<?php
	include_once 'inc/dbconnect.php';
	require_once 'inc/google-api-php-client/src/Google/autoload.php';
	include_once 'phpmailer/PHPMailerAutoload.php';
	include_once 'inc/updateAccessToken.php';
	include_once 'inc/format_email.php';
	include_once 'inc/getContact.php';

	function sendMessage($service, $userId, $message) {
		try {
			$message = $service->users_messages->send($userId, $message);
//			print 'Message with ID: ' . $message->getId() . ' sent.';
			return $message;
		} catch (Exception $e) {
			print 'An error occurred: ' . $e->getMessage();
		}
	}

	$userid = $U['id'];
	$useremail = getContact($userid,'id','email');
	$username = getContact($userid,'id','name');
	//force token for this userid
	setGoogleAccessToken($userid);

	$sbj = "Gmail authorization test email";
	$email_body = "You have successfully authorized Gmail for use in Stackbay!";

	$query = "SELECT client_secret FROM google; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)<>1) {
		echo json_encode(array('message'=>'Could not establish client token for email authorization'));
		exit;
	}
	$row = mysqli_fetch_assoc($result);
	$auth = $row['client_secret'];

	$client = new Google_Client();
	$client->addScope("https://www.googleapis.com/auth/gmail.compose");
	$client->setAuthConfig($auth);
	$client->setAccessType("offline");
	// forces prompt for user to authorize app so that we can be sure
	// it will always return a refresh token for long-term access
	$client->setApprovalPrompt('force');

	/**************** TO FORCE A NEW TOKEN, PASS IN THIS VAR ***************/
	if (isset($_REQUEST['reset']) AND $_REQUEST['reset']==1) { $ACCESS_TOKEN = ''; }

	// without access token, try to refresh, if we have a refresh token
	if (! $ACCESS_TOKEN AND $REFRESH_TOKEN) {
		try {
			$client->refreshToken($REFRESH_TOKEN);

			$ACCESS_TOKEN = $client->getAccessToken();

			updateAccessToken($ACCESS_TOKEN,$userid,$REFRESH_TOKEN);
		} catch (Exception $e) {
//			print 'An error occurred: '.$e->getMessage();
			$ACCESS_TOKEN = '';
		}
	}

	if ($ACCESS_TOKEN) {
		$client->setAccessToken($ACCESS_TOKEN);

		//prepare the mail with PHPMailer
		$mail = new PHPMailer();
		$mail->CharSet = "UTF-8";
		$mail->Encoding = "base64";
		$mail->Priority = 3;

		//supply with your header info, body etc...
		$mail->Subject = $sbj;
		$mail->SetFrom($useremail,$username);

		$mail->addAddress($useremail);

		$send_err = '';
		$mail->addBCC('david@ven-tel.com');

		$mail->MsgHTML(format_email($sbj,$email_body));
		//create the MIME Message
		$preSend = $mail->preSend();
		if (! $preSend) {
			$send_err .= 'Mailer Error ('.str_replace("@", "&#64;", $e["email"]).') '.$mail->ErrorInfo.chr(10);
		} else {
//			echo "Message sent to :" . $row['full_name'] . ' (' . str_replace("@", "&#64;", $row['email']) . ')<br />';
		}

		$mime = $mail->getSentMIMEMessage();
		$mime = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

		//create the Gmail Message
		$message = new Google_Service_Gmail_Message();
		$message->setRaw($mime);

		$service = new Google_Service_Gmail($client);

		sendMessage($service, 'me', $message);

		// Clear all addresses and attachments for next loop
		$mail->clearAddresses();
		$mail->clearAttachments();

		if ($send_err) {
			echo json_encode(array('message'=>$send_err));
		} else {
			echo json_encode(array('message'=>'Success'));
		}
		exit;
	} else {
		//$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/mail_auth.php?prompt=consent');
		$auth_url = $client->createAuthUrl();

		header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));

		exit;
	}
?>
