<?php
	include_once 'inc/dbconnect.php';
	require_once 'inc/google-api-php-client/src/Google/autoload.php';
	include_once 'inc/updateAccessToken.php';

	$err = '';
	$code = '';
	if (isset($_REQUEST['error'])) { $err = $_REQUEST['error']; }
	if (isset($_REQUEST['code'])) { $code = $_REQUEST['code']; }

	$query = "SELECT client_secret FROM google; ";
	$result = qdb($query);
	if (mysqli_num_rows($result)<>1) { die("Could not establish token"); }
	$row = mysqli_fetch_assoc($result);
	$auth = $row['client_secret'];

	$client = new Google_Client();
	$client->addScope("https://www.googleapis.com/auth/gmail.compose");
	$client->setAuthConfig($auth);

	// if no code then go back to ask user again
	if (! $code) {
		$auth_url = $client->createAuthUrl();
		header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
		exit;
	} else {//with code, get access token, store it for session use later
		$client->authenticate($code);

		$access_token = $client->getAccessToken();
		updateAccessToken($access_token,$U['id']);

		$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/';
//		$redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/gmail.php';
		header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
		exit;
	}
?>
