<?php
	include_once '../inc/dbconnect.php';
	require_once '../inc/google-api-php-client/src/Google/autoload.php';
	include_once '../phpmailer/PHPMailerAutoload.php';
	include_once '../inc/updateAccessToken.php';
	include_once '../inc/format_email.php';
	include_once '../inc/getContact.php';

	function sendMessage($service, $userId, $message) {
		try {
			$message = $service->users_messages->send($userId, $message);
//			print 'Message with ID: ' . $message->getId() . ' sent.';
			return $message;
		} catch (Exception $e) {
			print 'An error occurred: ' . $e->getMessage();
		}
	}
	$query = "SELECT client_secret FROM google; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)<>1) {
		echo json_encode(array('message'=>'Could not establish client token for email authorization'));
		exit;
	}
	$row = mysqli_fetch_assoc($result);
	$GAUTH = $row['client_secret'];

	$SEND_ERR = '';
	function send_gmail($email_body,$email_subject,$userid) {
		global $GAUTH,$ACCESS_TOKEN,$REFRESH_TOKEN,$SEND_ERR;

		$SEND_ERR = '';
		if (! $userid OR ! is_numeric($userid)) {
			$SEND_ERR .= 'Invalid or missing user id';
			return false;
		}
	        $useremail = getContact($userid,'id','email');
	        $username = getContact($userid,'id','name');

		$client = new Google_Client();
		$client->addScope("https://www.googleapis.com/auth/gmail.compose");
		$client->setAuthConfig($GAUTH);
		$client->setAccessType("offline");
		// forces prompt for user to authorize app so that we can be sure
		// it will always return a refresh token for long-term access
//		$client->setApprovalPrompt('force');

		// without access token, try to refresh, if we have a refresh token
		if (! $ACCESS_TOKEN AND $REFRESH_TOKEN) {
			$client->refreshToken($REFRESH_TOKEN);
			$ACCESS_TOKEN = $client->getAccessToken();
			updateAccessToken($ACCESS_TOKEN,$userid,$REFRESH_TOKEN);
		}

		if (! $ACCESS_TOKEN) {
			//$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/mail_auth.php?prompt=consent');
			$auth_url = $client->createAuthUrl();

			header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
			exit;
		}

		$client->setAccessToken($ACCESS_TOKEN);

		//prepare the mail with PHPMailer
		$mail = new PHPMailer();
		$mail->CharSet = "UTF-8";
		$mail->Encoding = "base64";
		$mail->Priority = 3;

		//supply with your header info, body etc...
		$mail->Subject = $email_subject;
		$mail->SetFrom($useremail,$username);

		$mail->addBCC('david@ven-tel.com');

		$mail->addAddress('chris@ven-tel.com');

		$mail->MsgHTML(format_email($email_subject,$email_body));
		//create the MIME Message
		$preSend = $mail->preSend();
		if (! $preSend) {
			$SEND_ERR .= 'Mailer Error ('.str_replace("@", "&#64;", $e["email"]).') '.$mail->ErrorInfo.chr(10);
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

/*
		if ($send_err) {
			echo json_encode(array('message'=>$SEND_ERR));
		} else {
			echo json_encode(array('message'=>'Success'));
		}
*/

		return true;
	}

	$U['name'] = 'Amea Cabula';
	$U['email'] = 'amea@ven-tel.com';
	$U['phone'] = '(805) 212-4959';
	$sbj = "This is a test";
	$email_body = "Hi Chris,<br><br>I'm watching you. I see all.";
	$send_success = send_gmail($email_body,$sbj,5);
	if ($send_success) {
		echo json_encode(array('message'=>'Success'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
	}
?>