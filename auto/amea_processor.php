<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/format_date.php';
	include_once '../inc/imap_decode.php';
	include_once '../inc/imap_parsers.php';
	include_once '../inc/getContact.php';
	include_once '../inc/getCompany.php';
	include_once '../inc/insertMarket.php';
	include_once '../inc/keywords.php';
	include_once '../inc/getPartId.php';
	require_once '../inc/google-api-php-client/src/Google/autoload.php';
	include_once '../phpmailer/PHPMailerAutoload.php';
	include_once '../inc/google_composer.php';

	/* connect to gmail */
	$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
	$username = 'amea@ven-tel.com';
	$password = 'venpass01';

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));

	$client = new Google_Client();
	$client->addScope("https://www.googleapis.com/auth/gmail.compose");
	$client->setAuthConfig($GAUTH);
	$client->setAccessType("offline");

	// without access token, try to refresh, if we have a refresh token
	if (! $ACCESS_TOKEN AND $REFRESH_TOKEN) {
		$client->refreshToken($REFRESH_TOKEN);
		$ACCESS_TOKEN = $client->getAccessToken();
		updateAccessToken($ACCESS_TOKEN,$U['id'],$REFRESH_TOKEN);
	}

	// default
	$since_datetime = format_date($now,'d-M-Y H:i:s',array('h'=>-2));
$since_datetime = '07-May-2016 06:00:00';
	if (isset($_REQUEST['since_datetime']) AND format_date($_REQUEST['since_datetime'])!==false) { $since_datetime = $_REQUEST['since_datetime']; }
	$email_number = 0;
	if (isset($_REQUEST['email_number']) AND is_numeric($_REQUEST['email_number'])) { $email_number = $_REQUEST['email_number']; }

	// try to connect
	$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());

	// grab emails
	$inbox_results = imap_search($inbox,'SINCE "'.$since_datetime.'"');

	// print "<pre>".print_r($inbox_results,true)."</pre>";

	// if emails are returned, cycle through each...
	if (! $inbox_results) { die("Could not find any emails in inbox"); }

	// put the newest emails on top
	rsort($inbox_results);

	$email_body = '';//final result for output below
	$from_email = '';
	$from_name = '';
	$from = '';

	// for every email...
	foreach ($inbox_results as $n) {
		// get information specific to this email
		$message = '';
		$header = imap_headerinfo($inbox,$n);
		$overview = imap_fetch_overview($inbox,$n,0);
		$structure = imap_fetchstructure($inbox, $n);
		$from_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;

		if (isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[1])) {
			$part = $structure->parts[1];
			$message = imap_decode(imap_fetchbody($inbox,$n,2),$part->encoding);
//			$message = imap_qprint(imap_body($inbox,$n));
		}
//echo $message.'<BR><BR>';

		// output the email overview information
		$status = ($overview[0]->seen ? 'read' : 'unread');
//		if ($status=='read') { continue; }

		$date_utc = $overview[0]->date;
		$date = date("Y-m-d",strtotime($date_utc));
		if ($date==$today) {
			$date = 'Today '.date("H:i:s a",strtotime($date_utc));
		} else if ($date==$yesterday) {
			$date = 'Yesterday '.date("H:i a",strtotime($date_utc));
		} else {
			$date = date("M j, H:i a",strtotime($date_utc));
		}
		$subject = $overview[0]->subject;
		$from = $overview[0]->from;
		$f = explode(' <',$from);
		$from_name = $f[0];

		$contactid = getContact($from_email,'email','id');
		$companyid = getContact($contactid,'id','companyid');
		if (! $contactid OR ! $companyid) { continue; }

//		if ($contactid<>14) { continue; }
		echo $from_email.':'.$contactid.'<BR>';

		// use this to identify if there are any html tables, which require different handling
		$DOM = new DOMDocument();
		$DOM->loadHTML($message);
		$tables = $DOM->getElementsByTagName('table');

		$signature_found = false;
		$results = array();
		$result_strings = array();
		$html_table = false;
		if (($tables->length)>0) {
			$html_table = true;
			$results = parseHtmlTable($tables);
		} else {/* if no html table in the email */
			$results = parsePlainText($message);
			if ($results===false) { continue; }
		}
//		print "<pre>".print_r($results,true)."</pre>";

		$matches_found = 0;
		$query = "SELECT * FROM amea WHERE contactid = '".$contactid."'; ";
//		echo $query.'<BR>';
		$result = qdb($query);
		if (mysqli_num_rows($result)==0) { continue; }

		while ($r = mysqli_fetch_assoc($result)) {
			$part_col = $r['part'];
			$part_from_end = $r['part_from_end'];
			$qty_col = $r['qty'];
			$qty_from_end = $r['qty_from_end'];
			$heci_col = $r['heci'];
			$heci_from_end = $r['heci_from_end'];
			foreach ($results as $rows) {
				$fields = array();
				foreach ($rows as $cols) {
					$words = explode(' ',$cols);
					foreach ($words as $word) {
						$fields[] = trim(str_replace(array(chr(160),chr(194)),'',$word));
					}
				}
				$num_fields = count($fields);

				$part = '';
				if ($part_from_end) { $part = $fields[(($num_fields-1)-$part_col)]; } else { $part = $fields[$part_col]; }
				$qty = '';
				if ($qty_from_end) { $qty = $fields[(($num_fields-1)-$qty_col)]; } else { $qty = $fields[$qty_col]; }
				$qty = preg_replace('/^([0-9]+)-$/','$1',$qty);
				if (! is_numeric($qty)) { $qty = ''; }
				$qty = (int)$qty;//convert 02's into 2's
				$heci = '';
				if ($heci_from_end) { $heci = $fields[(($num_fields-1)-$heci_col)]; } else { $heci = $fields[$heci_col]; }
				$heci = preg_replace('/^([[:punct:]]+)?([[:alnum:]]{7,10})([[:punct:]]+)?$/','$2',$heci);

				if (($part_col!==NULL AND ! $part) OR ($qty_col!==NULL AND ! $qty) OR (! $part AND ! $heci)) { continue; }

				$partid = getPartId($part,$heci);
echo 'getPartId:'.$partid.'<BR>';

				$numdb = 0;
				if ($heci) {
					$hecidb = hecidb(substr($heci,0,7));
					$numdb = count($hecidb);
echo $heci.' ';
				}

				if ($numdb==0 AND $part) {
					$hecidb = hecidb(format_part($part));
					$numdb = count($hecidb);
echo $part.' ';
				}
echo '('.$qty.' qty)<BR>';
				if ($numdb==0) { echo 'No Match!<BR>'; continue; }
				$matches_found += $numdb;

				foreach ($hecidb as $partid => $P) {
echo $partid.'<BR>';
//					insertMarket($partid,$list_qty,$list_price,$response_qty,$response_price,$metaid,$submit_type,$searchid,$ln);
				}
echo '<BR>';
			}

			if ($matches_found>0) {
				break;
			}
		}
echo '<BR><BR>';
	}
?>
