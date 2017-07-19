<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imap_decode.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imap_parsers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	require_once $_SERVER["ROOT_DIR"].'/inc/google-api-php-client/src/Google/autoload.php';
	include_once $_SERVER["ROOT_DIR"].'/phpmailer/PHPMailerAutoload.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';

	$userid = 5;
	setGoogleAccessToken($userid);

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
		updateAccessToken($ACCESS_TOKEN,$userid,$REFRESH_TOKEN);
	}

	// default
//	$since_datetime = format_date($now,'d-M-Y H:i:s',array('h'=>-2));
	$since_datetime = format_date($now,'d-M-Y 00:00:00');
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

	$commons = array(
		'CARD'=>1,
		'MODEL'=>1,
		'POWER'=>1,
		'FAN'=>1,
		'PIC'=>1,
		'SHELF'=>1,
		'UNIT'=>1,
		'HOUSING'=>1,
		'TEST'=>1,
		'PACK'=>1,
		'MODULE'=>1,
		'NID'=>1,
		'TIMING'=>1,
		'TAG'=>1,
		'CABLE'=>1,
		'DFMS'=>1,
		'DLC'=>1,
		'REPEATER'=>1,
		'LABEL'=>1,
		'DL'=>1,
		'GTD-5'=>1,
		'ASSY'=>1,
		'RACK'=>1,
		'SWITCH'=>1,
		'TRANSCEIVER'=>1,
		'CENTRAL'=>1,
		'FUSE'=>1,
		'DACS'=>1,
		'CTF'=>1,
		'REMOTE'=>1,
		'ESS#5'=>1,
		'AMPLIFIER'=>1,
		'CONTROLLER'=>1,
		'EWSD'=>1,
		'BREAKER'=>1,
		'PWBA'=>1,
		'CLOCK'=>1,
		'KIT'=>1,
		'GENERATOR'=>1,
		'ITEM'=>1,
		'PARTNO'=>1,
		'PART'=>1,
		'SYS'=>1,
		'PWR'=>1,
		'OVERNIGHT'=>1,
		'PART'=>1,
		'LCM'=>1,
		'CO'=>1,
		'XFP'=>1,
		'SFP'=>1,
		'MODEM'=>1,
		'STATUS'=>1,
		'PANEL'=>1,
		'TRANSPORT'=>1,
		'ITEMCODE'=>1,
		'PICS'=>1,
		'DRIVE'=>1,
		'SUPPLY'=>1,
		'MODULES'=>1,
		'RECTIFIER'=>1,
		'-48V'=>1,
		'48VDC'=>1,
		'CONVERTOR'=>1,
		'RING'=>1,
		'48V'=>1,
		'T1'=>1,
		'CONVERTER'=>1,
		'TRACER'=>1,
		'UMC1000'=>1,
		'HI'=>1,
		'5ESS'=>1,
		'THIS'=>1,
	);

	// for every email...
	foreach ($inbox_results as $n) {
		// get information specific to this email
		$message = '';
		$header = imap_headerinfo($inbox,$n);
		$overview = imap_fetch_overview($inbox,$n,0);
		// output the email overview information
		$status = ($overview[0]->seen ? 'read' : 'unread');
		if ($status=='read') { continue; }

		$structure = imap_fetchstructure($inbox, $n);
		$from_email = $header->from[0]->mailbox . "@" . $header->from[0]->host;

		if (isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[1])) {
			$mpart = $structure->parts[1];
//changed 7-13-16 when I stopped redirect-forwarding emails to Amea
//			$message = imap_decode(imap_fetchbody($inbox,$n,2),$mpart->encoding);

			$message = imap_decode($inbox,$n,$mpart->encoding);
		} else {
			$message = imap_body($inbox,$n);
		}
//echo $message.'<BR><BR>';

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

		$results_body = '';
		$contactid = getContact($from_email,'email','id');
		$companyid = getContact($contactid,'id','companyid');
		if (! $contactid OR ! $companyid OR substr($subject,0,3)=='RE:') {
			$results_body = 'Please see email below from '.$from_name.'<BR>';
		} else {
			// use this to identify if there are any html tables, which require different handling
			$DOM = new DOMDocument();
			$DOM->strictErrorChecking = false;
			$DOM->loadHTML($message);
			$tables = $DOM->getElementsByTagName('table');

			$email_content = array();
			if (($tables->length)>0) {
				$email_content = parseHtmlTable($tables);
			} else {/* if no html table in the email */
				$email_content = parsePlainText($message);
				if ($email_content===false) { continue; }
			}
//			print "<pre>".print_r($email_content,true)."</pre>";

			//reset
			$metaid = 0;
			// array for holding all market data to be inserted
			$inserts = array();
			$num_inserts = 0;//running count of array above
			$failed_strings = "";//concatenated list of strings failing to produce matches, for sending to users as recap of results

			// find patterns already stored for this contact
			$query = "SELECT * FROM amea WHERE contactid = '".$contactid."'; ";
//			echo $query.'<BR>';
			$patterns = qdb($query);
			if (mysqli_num_rows($patterns)==0) {
				// no patterns detected so send the email to a user as if it was hand-written
				$results_body = 'Please see email below from '.$from_name.' at '.getCompany($companyid).'<BR>';
			}

			// use each pattern found in the db for this contact, and attempt to match against each of the $email_content rows from above
			while ($r = mysqli_fetch_assoc($patterns)) {
				$part_col = $r['part'];
				$part_from_end = $r['part_from_end'];
				$qty_col = $r['qty'];
				$qty_from_end = $r['qty_from_end'];
				$heci_col = $r['heci'];
				$heci_from_end = $r['heci_from_end'];

				// handle email content against this pattern to look for possible matches
				$last_fields = array();//used for looking back at previous row's fields, in case qty is on a subsequent row
				$search_results = array();//consolidates results to avoid duplicates and to catch qtys on subsequent rows
				foreach ($email_content as $ln => $rows) {
					$fields = array();
					foreach ($rows as $cols) {
						$words = preg_split('/[[:space:]]+/',$cols);
						foreach ($words as $word) {
							$fields[] = trim(str_replace(array(chr(160),chr(194)),'',$word));
						}
					}
					$num_fields = count($fields);

					$part = '';
					// set part field based on counting from start or end
					$part_field = $part_col;
					if ($part_from_end) { $part_field = ($num_fields-1)-$part_col; }
					// set part string from identified field, uppercase it for $commons lookup purposes
					if (isset($fields[$part_field])) {
						$part = strtoupper($fields[$part_field]);
					}
					// if this is a 'common' word and if we already have a matched field, resort to that previously-matched field
					if (isset($commons[$part]) AND isset($last_fields[$part_field])) { $part = $last_fields[$part_field]; }

					$qty = '';
					if ($qty_from_end) {
						if (isset($fields[(($num_fields-1)-$qty_col)])) {
							$qty = $fields[(($num_fields-1)-$qty_col)];
						}
					} else {
						if (isset($fields[$qty_col])) {
							$qty = $fields[$qty_col];
						}
					}

					$heci = '';
					if ($heci_from_end AND isset($fields[(($num_fields-1)-$heci_col)])) { $heci = $fields[(($num_fields-1)-$heci_col)]; }
					else if (isset($fields[$heci_col])) { $heci = $fields[$heci_col]; }

					// cols can't be the same
					if ($part==$qty) { $qty = 1; }

					// trailing -RF (refurb) is common in frontier emails, but also occurs elsewhere at times
					$part = preg_replace('/-RF$/','',$part);

					$qty = preg_replace('/^([0-9]+)-$/','$1',$qty);
					if (! is_numeric($qty)) { $qty = ''; }
					$qty = (int)$qty;//convert 02's into 2's

					$heci = preg_replace('/^([[:punct:]]+)?([[:alnum:]]{7,10})([[:punct:]]+)?$/','$2',$heci);

					$last_fields = $fields;//set for next iteration, if any

//for now, default to 1 if qty not found on this row
if ($qty_col!==NULL AND ! $qty) { $qty = 1; }
					//if (($part_col!==NULL AND ! $part) OR ($qty_col!==NULL AND ! $qty) OR (! $part AND ! $heci)) { continue; }
					if (($part_col!==NULL AND ! $part) OR ($qty_col!==NULL AND ! $qty) OR (! $part AND ! $heci)) { continue; }

					$base = $part.'|'.$heci;
					if (! isset($search_results[$base])) {
						$search_results[$base] = $qty;
					} else {
						if ($qty>$search_results[$base]) { $search_results[$base] = $qty; }
					}
				}

				// $search_results handles everything that could possibly be used as a search string - it's the match against the above patterns
				foreach ($search_results as $base => $qty) {
					$roots = explode('|',$base);
					$part = format_part($roots[0]);
					$heci = $roots[1];
					$partkey = $part;
					if ($heci) { $partkey .= ' '.substr($heci,0,7); }

					if (strlen($part)<2 AND ! $heci) {
						if (! strstr($failed_strings,$partkey)) {
							if ($failed_strings) { $failed_strings .= ', '; }
							$failed_strings .= $partkey;
						}
						continue;
					}

					$matches = getPartId($part,$heci,0,true);//return ALL results, not just first found
//					if (count($matches)==0) { continue; }

					$num_matches = count($matches);

					$match_results = '';
					$stk_qty = 0;
					foreach ($matches as $partid) {
						$part_str = getPart($partid,'part');
						$heci_str = getPart($partid,'heci');

						// get qty per partid
						$stk_qty += getQty($partid);
						$match_results .= $part_str.' '.$heci_str.'<BR>';

						// add first occurrence of this $partkey (part and 7-digit heci, if present) to $inserts for adding to db
						if (! isset($inserts[$partkey])) {
							$inserts[$partkey] = array('partid'=>$partid,'qty'=>$qty,'ln'=>$ln);
						}
					}
					$num_inserts += count($inserts);

					if ($num_matches>0) {
						$results_body .= 'I ran <span style="color:#468847; font-weight:bold">'.$partkey.'</span> (qty '.$qty.')';
					} else if (! strstr($failed_strings,$partkey)) {
						if ($failed_strings) { $failed_strings .= ', '; }
						$failed_strings .= $partkey;
					}

					// if we have stock (or at least looks that way), add a note to user to check stock
					if ($stk_qty>0) { $results_body .= ' <strong>CHECK STOCK</strong>'; }
					// end the line with a line break and then add the matched results
					if ($match_results) { $results_body .= '...<BR>'.$match_results; }
				}

				// pattern match successfully found for this email so don't try next pattern
				if ($num_inserts>0) { break; }
			}

			// no data inserted above so use our failed strings to add to email message to user
			if ($failed_strings) {
				$results_body .= 'I could not find <span style="color:#b94a48; font-weight:bold">'.$failed_strings.
					'</span> (qty '.$qty.'), please check your system...<BR>';
			}

			if ($num_inserts>0) {
				$metaid = logSearchMeta($companyid,false,'','email');
				foreach ($inserts as $r) {
					insertMarket($r['partid'], $r['qty'], false, false, false, $metaid, 'demand', 0, $r['ln']);
				}
			}
		}

		// build message body and send
		if ($results_body) {
			$email_body = $results_body.'<BR>'.$message;
//			echo $from_email.':'.$contactid.' (contactid) / '.$companyid.' (companyid)<BR>'.$results_body.$message.'<BR><BR>';

			$send_success = send_gmail($email_body,$subject,array('david@ven-tel.com','sam@ven-tel.com'),'',$from_email);//set reply to as $from_email
			if ($send_success) {
				echo json_encode(array('message'=>'Success'));
			} else {
				echo json_encode(array('message'=>$SEND_ERR));
			}
		}
	}
?>
