<?php
	include_once 'inc/dbconnect.php';
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

	<div id="pad-wrapper">
		<div class="row header">
			<h2><i class="fa fa-female"></i> <strong>A</strong>uto<strong>m</strong>ated <strong>E</strong>mail <strong>A</strong>ttendant</h2>
		</div>


<!--
				<td>
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
					</select>
				</td>
-->
<?php
	include_once 'inc/format_date.php';
	include_once 'inc/imap_decode.php';
	include_once 'inc/imap_parsers.php';
	include_once 'inc/getContact.php';

	/* connect to gmail */
	$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
	$username = 'amea@ven-tel.com';
	$password = 'venpass01';

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));

	// default
	$since_datetime = format_date($now,'d-M-Y H:i:s',array('h'=>-2));
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

	$email = '';//final result for output below

	// for every email...
	foreach ($inbox_results as $n) {
		if ($n<>$email_number) { continue; }

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

		$contactid = getContact($from_email,'email','id');

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

		$result_str = '';
		foreach ($results as $i => $row) {
			$line = '';
			$linestr = '';
			foreach ($row as $x => $field) {
				if ($linestr) { $linestr .= ' '; }
				$linestr .= $field;

				$words = explode(' ',$field);
				foreach ($words as $word) {
					if ($line) { $line .= ' '; }
					$line .= '<a href="javascript:void(0);" class="highlight-word" data-row="'.$i.'" data-col="'.$x.'" data-word="'.$word.'">'.$word.'</a>';
				}
			}
			$result_str .= '<div class="selector-line" data-string="'.$linestr.'">'.$line.'</div>';
		}

		$email = '
			<hr><div class="pull-right">'.$date.'</div>
			<div><strong>'.$from.'</strong> &lt;'.$from_email.'&gt;</div><hr>'.$result_str;
	}
?>

		<div class="container">
			<div class="row">
				<div class="col-md-2"> </div>
				<div class="col-md-8">
					Teach me, I'm eager to learn! Please help me by using the highlighters on the left, and then click on the
					words or terms in the email below that are Part Numbers, HECI Codes or Quantities. I can be easily confused
					so please be careful what you highlight.
<!--
					If there are <em>duplicate</em> occurrences of a Part#/HECI in the same item (not just multiple lines of the
					same part#), only show me the most consistent, predictable instance.<BR><BR>
-->
				</div>
				<div class="col-md-2"> </div>
			</div>
			<div class="row">
				<div class="col-md-2">
					<p><a href="javascript:void(0);" class="btn btn-sm btn-default highlighter-pen" data-color="danger"><i class="fa fa-pencil"></i></a> PART</p>
					<p><a href="javascript:void(0);" class="btn btn-sm btn-default highlighter-pen" data-color="success"><i class="fa fa-pencil"></i></a> HECI</p>
					<p><a href="javascript:void(0);" class="btn btn-sm btn-default highlighter-pen" data-color="warning"><i class="fa fa-pencil"></i></a> QTY</p>
				</div>
				<div class="col-md-8">
					<?php echo $email; ?>
				</div>
				<div class="col-md-2"> </div>
			</div>
		</div>

	</div><!-- pad-wrapper -->

<?php include_once 'modal/amea.php'; ?>
<?php include_once 'inc/footer.php'; ?>

</body>
</html>
