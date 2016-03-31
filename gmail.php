<?php
	include_once 'inc/dbconnect.php';
	require_once 'inc/google-api-php-client/src/Google/autoload.php';
	include_once 'phpmailer/PHPMailerAutoload.php';

	function sendMessage($service, $userId, $message) {
		try {
			$message = $service->users_messages->send($userId, $message);
			print 'Message with ID: ' . $message->getId() . ' sent.';
			return $message;
		} catch (Exception $e) {
			print 'An error occurred: ' . $e->getMessage();
		}
	}

	$query = "SELECT client_secret FROM google; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)<>1) { die("Could not establish client token"); }
	$row = mysqli_fetch_assoc($result);
	$auth = $row['client_secret'];

	$client = new Google_Client();
	$client->addScope("https://www.googleapis.com/auth/gmail.compose");
//	$client->setApplicationName("VenTel Market Manager");
//	$client->setDeveloperKey("YOUR_APP_KEY");
//	$client->setAuthConfigFile('client_secrets.json');
	$client->setAuthConfig($auth);

	// without access token, ask user for permission
	if ($ACCESS_TOKEN) {
//		echo $ACCESS_TOKEN;
		$client->setAccessToken($ACCESS_TOKEN);

		//prepare the mail with PHPMailer
		$mail = new PHPMailer();
		$mail->CharSet = "UTF-8";
		$mail->Encoding = "base64";
		$mail->Priority = 3;

		//supply with your header info, body etc...
		$mail->Subject = "You've got mail!";
		$mail->MsgHTML("New test email");
		$mail->SetFrom("david@ven-tel.com","David Langley");
		$mail->addAddress("davidglangley@gmail.com");
		$mail->addBCC("david@ven-tel.com");

		//create the MIME Message
		$mail->preSend();
		$mime = $mail->getSentMIMEMessage();
		$mime = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

		//create the Gmail Message
		$message = new Google_Service_Gmail_Message();
		$message->setRaw($mime);

		$service = new Google_Service_Gmail($client);

		sendMessage($service, 'me', $message);
	} else {
		//$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/mail_auth.php');

		$auth_url = $client->createAuthUrl();
		header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
	}
?>
