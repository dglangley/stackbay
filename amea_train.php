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

<?php
	include_once 'inc/format_date.php';
	include_once 'inc/imap_decode.php';
	include_once 'inc/imap_parsers.php';
	include_once 'inc/getContact.php';
	include_once 'inc/getCompany.php';

	/* connect to gmail */
	$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
	$username = 'amea@ven-tel.com';
	$password = 'venpass01';

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));

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
		$f = explode(' <',$from);
		$from_name = $f[0];

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
			$z = 0;
			// get total count of fields in this row first
			foreach ($row as $col => $field) {
				$words = explode(' ',$field);
				foreach ($words as $k => $word) {
					$z++;
				}
			}

			$a = 1;
			foreach ($row as $col => $field) {
				$linestr .= '
					<input type="hidden" name="lines['.$i.']['.$a.']" value="'.$field.'">
				';

				$words = explode(' ',$field);
//				print "<pre>".print_r($words,true)."</pre>";
				foreach ($words as $k => $word) {
					$line .= '
					<a href="javascript:void(0);" class="highlight-word" data-for="fields_'.$i.'-'.$a.'" data-col="'.$a.'" id="label_'.$i.'-'.$a.'">'.$word.'</a>
					<input type="radio" name="fields" value="'.$word.'" id="fields_'.$i.'-'.$a.'" data-label="label_'.$i.'-'.$a.'" data-col="'.$a.'" data-end="'.$z.'" class="hidden">
					';
					$a++;
					$z--;
				}
			}
			$result_str .= $linestr.'
				<div>'.$line.'</div>
			';
		}

		$email_body = '
			<div><strong>'.$from_name.'</strong> &lt;'.$from_email.'&gt;</div><hr>'.$result_str;
	}

	if ($from_email) {
		// get companyid for select dropdown below
		$companyid = getContact($from_email,'email','companyid');
	}
?>

		<div class="container">
			<div class="row">
				<div class="col-md-2"> </div>
				<div class="col-md-8">
					<span class="info">Teach me, I'm eager to learn! Please help me by using the highlighters on the left, and then click on the
					words or terms in the email below that are Part Numbers, HECI Codes or Quantities. I can be easily confused
					so please be careful what you highlight.</span>
<!--
					If there are <em>duplicate</em> occurrences of a Part#/HECI in the same item (not just multiple lines of the
					same part#), only show me the most consistent, predictable instance.<BR><BR>
-->
				</div>
				<div class="col-md-2"> </div>
			</div>

			<form method="POST" action="save-amea.php" class="form-inline">
			<input type="hidden" name="from_email" value="<?php echo $from_email; ?>">
			<input type="hidden" name="from_name" value="<?php echo $from_name; ?>">
			<input type="hidden" name="email_number" value="<?php echo $email_number; ?>">
			<div class="row">
				<div class="col-md-2"> </div>
				<div class="col-md-8">
					<hr>
					<div class="pull-right">
						<select name="companyid" id="companyid" class="company-selector">
							<option value="">- Select a Company -</option>
<?php if ($companyid) { echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); } else { echo '<option value="">- Select a Company -</option>'.chr(10); } ?>
						</select>
					</div>
				</div>
				<div class="col-md-2"> </div>
			</div>
			<div class="row">
				<div class="col-md-2">
					<div class="form-group margin-10">
						<div class="input-group">
							<span class="input-group-btn">
								<a href="javascript:void(0);" class="btn btn-sm btn-default highlighter-pen" data-color="danger" data-type="part"><i class="fa fa-pencil"></i></a>
							</span>
							<input type="text" name="part_col" id="part-col" value="" placeholder="PART" size="3" class="form-control input-sm">
							<span class="input-group-btn">
								<input type="checkbox" class="hidden" id="part-end" name="part_from_end" value="1">
								<a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" data-for="part-end" data-input="part-col" title="Toggle alignment" class="btn btn-sm btn-default btn-end"><i class="fa fa-align-left"></i></a>
							</span>
						</div>
					</div>
					<div class="form-group margin-10">
						<div class="input-group">
							<span class="input-group-btn">
								<a href="javascript:void(0);" class="btn btn-sm btn-default highlighter-pen" data-color="success" data-type="heci"><i class="fa fa-pencil"></i></a>
							</span>
							<input type="text" name="heci_col" id="heci-col" value="" placeholder="HECI" size="3" class="form-control input-sm">
							<span class="input-group-btn">
								<input type="checkbox" class="hidden" id="heci-end" name="heci_from_end" value="1">
								<a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" data-for="heci-end" data-input="heci-col" title="Toggle alignment" class="btn btn-sm btn-default btn-end"><i class="fa fa-align-left"></i></a>
							</span>
						</div>
					</div>
					<div class="form-group margin-10">
						<div class="input-group">
							<span class="input-group-btn">
								<a href="javascript:void(0);" class="btn btn-sm btn-default highlighter-pen" data-color="warning" data-type="qty"><i class="fa fa-pencil"></i></a>
							</span>
							<input type="text" name="qty_col" id="qty-col" value="" placeholder="QTY" size="3" class="form-control input-sm">
							<span class="input-group-btn">
								<input type="checkbox" class="hidden" id="qty-end" name="qty_from_end" value="1">
								<a href="javascript:void(0);" data-toggle="tooltip" data-placement="top" data-for="qty-end" data-input="qty-col" title="Toggle alignment" class="btn btn-sm btn-default btn-end"><i class="fa fa-align-left"></i></a>
							</span>
						</div>
					</div>
				</div>
				<div class="col-md-8">
					<p>
						<div class="pull-right">
							<?php echo $date; ?>
						</div>
						<?php echo $email_body; ?>
					</p>
					<br/><hr><br/>
					<p>
						<button type="submit" class="btn btn-primary btn-md">Complete My Training!</button>
					</p>
				</div>
				<div class="col-md-2"> </div>
			</div>
			</form>
		</div>

	</div><!-- pad-wrapper -->

<?php //include_once 'modal/amea.php'; ?>
<?php include_once 'inc/footer.php'; ?>

</body>
</html>
