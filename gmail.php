<?php
	include_once 'inc/dbconnect.php';
	require_once 'inc/google-api-php-client/src/Google/autoload.php';
//	include_once 'inc/mailer.php';

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
echo $ACCESS_TOKEN;
		$client->setAccessToken($ACCESS_TOKEN);

		mailer($email,'Inventory Upload Report '.date("D n/j/y"),$mail_msg,'info@lunacera.com',$replyTo='no-reply@lunacera.com','',array('info@lunacera.com','LunaCera'),$attachment);
		$email_message = format_email('test','test body','teaser');
		$base64 = base64_encode($email_message);

		// Get the API client and construct the service object.
		$service = new Google_Service_Gmail($client);

		sendMessage($service, 'david@ven-tel.com', $email_message);

exit;
	} else {
		//$client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/mail_auth.php');

		$auth_url = $client->createAuthUrl();
		header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
	}



define('APPLICATION_NAME', 'Gmail API PHP Quickstart');
define('CREDENTIALS_PATH', '~/.credentials/gmail-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/json/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/gmail-php-quickstart.json
define('SCOPES', implode(' ', array(
  Google_Service_Gmail::GMAIL_READONLY)
));

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
  $client = new Google_Client();
  $client->setApplicationName(APPLICATION_NAME);
  $client->setScopes(SCOPES);
  $client->setAuthConfigFile(CLIENT_SECRET_PATH);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Gmail($client);

// Print the labels in the user's account.
$user = 'me';
$results = $service->users_labels->listUsersLabels($user);

if (count($results->getLabels()) == 0) {
  print "No labels found.\n";
} else {
  print "Labels:\n";
  foreach ($results->getLabels() as $label) {
    printf("- %s\n", $label->getName());
  }
}

?>
