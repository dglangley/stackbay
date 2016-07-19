<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imap_decode.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/imap_parsers.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getContact.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	require_once $_SERVER["ROOT_DIR"].'/inc/google-api-php-client/src/Google/autoload.php';
	include_once $_SERVER["ROOT_DIR"].'/phpmailer/PHPMailerAutoload.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPipeIds.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPipeQty.php';

	$U['id'] = 5;
	$U['name'] = 'Amea Cabula';
	$U['email'] = 'amea@ven-tel.com';
	$U['phone'] = '(805) 212-4959';
	setGoogleAccessToken($U['id']);

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

	$commons = array('CARD'=>1,'POWER'=>1,'FAN'=>1,'PIC'=>1,'SHELF'=>1,'UNIT'=>1,'HOUSING'=>1,'TEST'=>1,'PACK'=>1,'MODULE'=>1,'NID'=>1,'TIMING'=>1,'TAG'=>1,'CABLE'=>1,'DFMS'=>1);

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

			$results = array();
			if (($tables->length)>0) {
				$results = parseHtmlTable($tables);
			} else {/* if no html table in the email */
				$results = parsePlainText($message);
				if ($results===false) { continue; }
			}
//			print "<pre>".print_r($results,true)."</pre>";

			//reset
			$metaid = 0;
			$matches_found = 0;

			// find patterns already stored for this contact
			$query = "SELECT * FROM amea WHERE contactid = '".$contactid."'; ";
//			echo $query.'<BR>';
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) {
				// no patterns detected so send the email to a user as if it was hand-written
				$results_body = 'Please see email below from '.$from_name.' at '.getCompany($companyid).'<BR>';
			} else {
//				$metaid = logSearchMeta($companyid,false,'','email');
			}

			// use each pattern found in the db for this contact, and attempt to match against each of the $results rows from above
			while ($r = mysqli_fetch_assoc($result)) {
				$part_col = $r['part'];
				$part_from_end = $r['part_from_end'];
				$qty_col = $r['qty'];
				$qty_from_end = $r['qty_from_end'];
				$heci_col = $r['heci'];
				$heci_from_end = $r['heci_from_end'];

				$last_fields = array();//used for looking back at previous row's fields, in case qty is on a subsequent row
				$search_results = array();//consolidates results to avoid duplicates and to catch qtys on subsequent rows
				foreach ($results as $ln => $rows) {
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
					$part = strtoupper($fields[$part_field]);
					// if this is a 'common' word and if we already have a matched field, resort to that previously-matched field
					if (isset($commons[$part]) AND isset($last_fields[$part_field])) { $part = $last_fields[$part_field]; }

					$qty = '';
					if ($qty_from_end) { $qty = $fields[(($num_fields-1)-$qty_col)]; } else { $qty = $fields[$qty_col]; }

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

				foreach ($search_results as $base => $qty) {
					$roots = explode('|',$base);
					$part = format_part($roots[0]);
					$heci = $roots[1];

					$matches = getPartId($part,$heci,0,true);//return ALL results, not just first found
//					if (count($matches)==0) { continue; }

					$num_matches = count($matches);
//					$matches_found += $num_matches;

					$match_results = '';
					$stk_qty = 0;
					foreach ($matches as $partid) {
						$pipe_ids = array();
						$part_str = getPart($partid,'part');
						$heci_str = getPart($partid,'heci');

						// get pipe ids for qty check
						$ids = getPipeIds(format_part($part_str),'part');
						foreach ($ids as $pipe_id => $arr) {
							$pipe_ids[$pipe_id] = $pipe_id;
						}
						if ($heci_str) {
							$ids = getPipeIds(substr($heci_str,0,7),'heci');
							foreach ($ids as $pipe_id => $arr) {
								$pipe_ids[$pipe_id] = $pipe_id;
							}
						}
						//$results_body .= $part_str.' '.$heci_str.' (id '.$partid.')<BR>';
						$match_results .= $part_str.' '.$heci_str.'<BR>';

						$matches_found++;
//						insertMarket($partid, $qty, false, false, false, $metaid, 'demand', 0, $ln);
					}
					if ($matches_found>0) {
						$results_body .= 'I ran <span style="color:#468847; font-weight:bold">'.$part.' '.$heci.'</span> (qty '.$qty.')';
					}

					foreach ($pipe_ids as $pipe_id) {
						$stk_qty += getPipeQty($pipe_id);
					}
					if ($stk_qty>0) {
						$results_body .= ' <strong>CHECK STOCK</strong>';
					}
					$results_body .= '...<BR>'.$match_results;
				}

				// pattern match successfully found for this email so don't try next pattern
				if ($matches_found>0) { break; }
			}
			// after trying all patterns, still couldn't find a match so send message
			if ($matches_found==0) {
				$results_body .= 'I could not find <span style="color:#b94a48; font-weight:bold">'.$part.' '.$heci.'</span> (qty '.$qty.'), please check your system';
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
