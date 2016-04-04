<?php
	include_once '../inc/dbconnect.php';
	require_once '../inc/google-api-php-client/src/Google/autoload.php';
	include_once '../phpmailer/PHPMailerAutoload.php';
	include_once '../inc/updateAccessToken.php';

	function sendMessage($service, $userId, $message) {
		try {
			$message = $service->users_messages->send($userId, $message);
			print 'Message with ID: ' . $message->getId() . ' sent.';
			return $message;
		} catch (Exception $e) {
			print 'An error occurred: ' . $e->getMessage();
		}
	}

	$consent = false;
	if (isset($_REQUEST['consent'])) { $consent = true; }
	$message_body = '';
	if (isset($_REQUEST['message_body'])) { $message_body = $_REQUEST['message_body']; }
	$companyids = array();
	if (isset($_REQUEST['companyids']) AND is_array($_REQUEST['companyids'])) { $companyids = $_REQUEST['companyids']; }

	$query = "SELECT client_secret FROM google; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)<>1) { die("Could not establish client token"); }
	$row = mysqli_fetch_assoc($result);
	$auth = $row['client_secret'];

	$client = new Google_Client();
	$client->addScope("https://www.googleapis.com/auth/gmail.compose");
	$client->setAuthConfig($auth);
	$client->setAccessType("offline");
	// forces prompt for user to authorize app so that we can be sure
	// it will always return a refresh token for long-term access
//	$client->setApprovalPrompt('force');

	// without access token, try to refresh, if we have a refresh token
	if (! $ACCESS_TOKEN AND $REFRESH_TOKEN) {
		$client->refreshToken($REFRESH_TOKEN);
		$ACCESS_TOKEN = $client->getAccessToken();
		updateAccessToken($ACCESS_TOKEN,$userid,$REFRESH_TOKEN);
	}

	if ($ACCESS_TOKEN) {
//	if (! $client->isAccessTokenExpired()) {
//		echo $ACCESS_TOKEN;

		if (count($companyids)==0) {
			echo json_encode(array('message'=>'Oops! Did you forget to select at least one item?'));
			exit;
		}
		$client->setAccessToken($ACCESS_TOKEN);

		//prepare the mail with PHPMailer
		$mail = new PHPMailer();
		$mail->CharSet = "UTF-8";
		$mail->Encoding = "base64";
		$mail->Priority = 3;

$sbj = "You've got mail";

		//supply with your header info, body etc...
		$mail->Subject = $sbj;
		$mail->MsgHTML(format_email($sbj,$message_body,""));
		$mail->SetFrom($U['email'],$U['name']);
		$mail->addAddress("davidglangley@gmail.com");
		$mail->addBCC($U['email']);

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
		//$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/mail_auth.php?prompt=consent');
		$auth_url = $client->createAuthUrl();

		if (! $consent) {
			$msg = "In order to Send RFQ's through VenTel Market Manager, ".
				"you need to authorize access to send emails on your behalf. ".
				"This is a one-time action, but you will lose any unsaved ".
				"activity on your current page and you will need to reload it ".
				"again in order to send the rfq. Press OK to proceed to authorization.";
			echo json_encode(array('message'=>$msg,'confirm'=>"1",'url'=>filter_var($auth_url, FILTER_SANITIZE_URL)));
		} else {
			header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
		}

		exit;
	}
?>
