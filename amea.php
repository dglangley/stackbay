<?php
	set_time_limit(0);
	libxml_use_internal_errors(true);
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_part.php';
	include_once 'inc/setColumns.php';
	include_once 'inc/keywords.php';
	include_once 'inc/qty_functions.php';
	include_once 'inc/format_heci.php';
	include_once 'inc/imap_decode.php';
	include_once 'inc/getCompany.php';
	include_once 'inc/getContact.php';
	include_once 'inc/imap_parsers.php';
	include_once 'inc/get_db.php';

	/* connect to gmail */
	$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
	$username = 'amea@ven-tel.com';
	$password = 'venpass01';

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));

	$since_datetime = format_date($now,'d-M-Y H:i:s',array('d'=>-2));
?>
<!DOCTYPE html>
<html>
<head>
	<title>Améa</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="">

	<?php include 'inc/navbar.php'; ?>

	<form class="form-inline" method="get" action="/amea_train.php">
	<input type="hidden" name="since_datetime" value="<?php echo $since_datetime; ?>">

    <div id="pad-wrapper">

		<div class="row header">
			<h2><i class="fa fa-female"></i> <strong>A</strong>uto<strong>m</strong>ated <strong>E</strong>mail <strong>A</strong>ttendant</h2>
		</div>
		<table class="table table-hover">
			<tr>
				<th class="col-md-2">Name</th>
				<th class="col-md-3">Email Address</th>
				<th class="col-md-5">Subject</th>
				<th class="col-md-1">Date</th>
				<th class="col-md-1 text-center">
					<button class="btn btn-success btn-sm" type="submit">Train Me!</button>
				</th>
			</tr>

<?php
	$emails = array();
	$debug_num = 0;
	if ($debug_num) {
		$emails = array($debug_num=>array('message'=>file_get_contents(sys_get_temp_dir().'/email71.txt'),'date'=>'2016-05-10 10:27:29','from'=>'Yurish, Ann Marie','subject'=>'URGENT MATERIAL NEEDED'));
	} else {
		// try to connect
		$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());

		// grab emails
		$inbox_results = imap_search($inbox,'SINCE "'.$since_datetime.'"');

		// print "<pre>".print_r($inbox_results,true)."</pre>";

		// if emails are returned, cycle through each...
		if (! $inbox_results) { die("Could not find any emails in inbox"); }
	
		// put the newest emails on top
		rsort($inbox_results);
	
		// for every email...
		foreach ($inbox_results as $email_number) {
//			if ($email_number<>74) { continue; }
//if ($email_number==69) { continue; }

			// get information specific to this email
			$message = '';
			$header = imap_headerinfo($inbox,$email_number);
			$overview = imap_fetch_overview($inbox,$email_number,0);
			$structure = imap_fetchstructure($inbox, $email_number);
			$from_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;

			if (isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[1])) {
				$mpart = $structure->parts[1];
				$message = imap_decode($inbox,$n,$mpart->encoding);
			}

			// output the email overview information
			$status = ($overview[0]->seen ? 'read' : 'unread');
//			if ($status=='read') { continue; }

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

			$contactid = getContact($from_email,'email','id');

//			if (! $contactid) {
				echo '
			<tr>
				<td><strong>'.$email_number.'</strong> '.$from.'</td>
				<td>'.$from_email.'</td>
				<td>'.$subject.'</td>
				<td>'.$date.'</td>
				<td class="text-center">
					<input type="radio" name="email_number" value="'.$email_number.'">
				</td>
			</tr>
				';
//			}
			$companyid = getCompany($contactid,'contactid','id');

			$emails[$email_number] = array('message'=>$message,'date'=>$date,'from'=>$from,'subject'=>$subject);
		}
	}

	echo '
		</table>
	';

	foreach ($emails as $email_number => $email) {
		$message = $email['message'];
		$date = $email['date'];
		$subject = $email['subject'];
		$from = $email['from'];

		// no tel-explorer emails and no replies!
		if (stristr($subject,'tel-explorer') OR strtoupper(substr($subject,0,3))=='RE:') {
			echo 'Tel-Explorer email or Reply detected, skipping message<br><br>';
			continue;
		}

		// use this to identify if there are any html tables, which require different handling
		$DOM = new DOMDocument();
		$DOM->loadHTML($message);
		$tables = $DOM->getElementsByTagName('table');

		$signature_found = false;//reset every loop for parser below
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

		// go back now and reconstruct a string-based array of all rows for later use
		foreach ($results as $k => $row) {
			foreach ($row as $str) {
				if (! isset($result_strings[$k])) { $result_strings[$k] = ''; }
				if ($result_strings[$k]) { $result_strings[$k] .= ' '; }
				$result_strings[$k] .= $str;
			}
		}

		/* output the email body */
//		echo $message.'<br/><br/>';
	}

	/* close the connection */
	imap_close($inbox);
?>

	</div>

	</form>

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
