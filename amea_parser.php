<?php
	set_time_limit(0);
	libxml_use_internal_errors(true);
//	include_once 'phpmailer/PHPMailerAutoload.php';
//	include_once 'inc/formatEmail.php';
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_part.php';
	include_once 'inc/set_columns.php';
	include_once 'inc/keywords.php';
//	include_once 'inc/find_fields.php';
	include_once 'inc/qty_functions.php';
	include_once 'inc/format_heci.php';
	include_once 'inc/imap_decode.php';

	function parseHtmlTable($tables) {
		global $signature,$intro;

		$results = array();

		$num_tables = $tables->length;
		foreach ($tables as $table) {
			$table_text = $table->nodeValue;
			if (! stristr($table_text,'heci')) {
				if (preg_match('/'.$intro.'/i',$table_text)) { continue; }
				else if (preg_match('/'.$signature.'.*/m',$table_text)) { continue; }
			}

			$rows = $table->getElementsByTagName('tr');

			$k = 0;
			foreach ($rows as $row) {
				$header = $row->getElementsByTagName('th');
				$cols = $row->getElementsByTagName('td');

				$rowstr = '';
				foreach ($header as $col) {
					if ($rowstr) { $rowstr .= ' '; }
					$rowstr .= $col->textContent;
				}
				if ($rowstr) {
					foreach ($header as $col) {
						$coltext = trim($col->textContent);
						$results[$k][] = $coltext;
					}
					$k++;
				}

				$rowstr = '';
				foreach ($cols as $col) {
					if ($rowstr) { $rowstr .= ' '; }
					$rowstr .= $col->textContent;
				}
				$rowstr = trim($rowstr);
				if ($rowstr) {
					foreach ($cols as $col) {
						$coltext = trim($col->textContent);
						$results[$k][] = $coltext;
					}
					$k++;
				}
			}
		}

		return ($results);
	}
	function parsePlainText($message) {
		global $signature,$signature_found,$email_pattern,$intro;

		// remove unnecessary and poorly-syntaxed comment lines ("/* /* */")
		$message = preg_replace('/&nbsp;[\s\S]?[\n]/','&nbsp;',preg_replace('/[\\/][*][\s\S]*[*][\\/]/m','',$message));
		// replace non-standard character?
		$message = str_replace(chr(226).chr(128).chr(147),'-',$message);
		// remove ellipsis-type periods
		$message = preg_replace('/(([[:space:]]*[\\.][[:space:]]*){2,})/',': ',$message);

		$message = str_ireplace("</div>","</div>".chr(10),$message);
		$fmessage = preg_replace('/^[\s\S]*body[{][\s\S^}]*[}]/','',strip_tags(str_ireplace(array("<br />","<br>","<br/>"),chr(10),$message)));
//		print "<pre>".print_r($fmessage,true)."</pre>";

		if (preg_match('/[^[:alpha:]]+attach(ed|ment)[^[:alpha:]]+/i',$fmessage)) {
			echo 'possible attachment! skipping message<br><BR>';
			return false;
		}

		$signature_matches = array();
		if (preg_match('/'.$signature.'?/m',$fmessage,$signature_matches)) {
			$body = preg_replace('/'.$signature.'[\s\S]*$/m','',$fmessage);
			$signature_found = true;
		} else {
			$body = preg_replace($email_pattern, '', $fmessage);

//			echo 'no signature found...<br>';
//			$body = $fmessage;
		}

		// the first preg_replace() tries to piece back together &nbsp; that have been split by string length
		// limits in emails; it's common to find "&= nbsp;" which disrupt column counting
		//$content = trim(preg_replace('/&[=]?[[:space:]]*n[=]?[[:space:]]*b[=]?[[:space:]]*s[=]?[[:space:]]*p[=]?[[:space:]]*;/i',' ',preg_replace('/('.$intro.'[[:alnum:][:space:]\\/=]*([-,.:!]|[[:space:]\n])+)/im','',trim($body))));
		//$content = trim(str_replace('&nbsp;',' ',preg_replace('/^[[:alnum:][:space:]]*('.$intro.'[[:alnum:][:space:]\\/=]*([,.:!-]|[[:space:]\n])+)/im','',trim($body))));
		$content = trim(str_replace('&nbsp;',' ',preg_replace('/^[[:alnum:][:space:]]*('.$intro.'[[:alnum:][:space:]\\/=]*([,.:!-]|[[:space:]\n])+)/i','',trim($body))));

		$k = 0;
		$lines = explode(chr(10),$content);
		foreach ($lines as $line) {
			$fields = preg_split('/[[:space:]]+/',$line);
			// when we add a field to any row below, it should increment our index
			$field_added = false;
			foreach ($fields as $field) {
				$field = trim($field);
				if ($field) {
					$results[$k][] = $field;
					$field_added = true;
				}
			}
			if ($field_added) { $k++; }
		}

		return ($results);
	}
	function get_db($part_str,$line_str,$part_col) {
		$fpart = trim(filter_var(format_part($part_str), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));
		$part_matches = array();
//		echo 'part str: ['.$fpart.']('.$line_str.')<BR>';
		// if the first column is a type of header/placeholder such as "MPN: xxxx" then process the entire line accordingly
		if ($part_col==0 AND preg_match('/^(product|upn|mpn|part|model|item)[[:alpha:][:space:]\\/#-]*[:]?[[:space:]]*/i',$fpart,$part_matches)) {
//			$part_str = preg_replace('/^(product|upn|mpn|part|model|item)([[:alpha:][:space:]\\/#-]*[:]?[[:space:]]*)([[:alnum:]-]+).*/i','$3',$line_str);//.'<BR>';
			$part_str = preg_replace('/^(product|upn|mpn|part|model|item)([[:space:]\\/#-]*[:]?[[:space:]]*)([[:alnum:]-]+).*/i','$3',$line_str);//.'<BR>';
			$fpart = $part_str;
		}
		$fpart = preg_replace('/-RF$/','',$fpart);
		$hecidb = hecidb($fpart);
		return ($hecidb);
	}

//	$signoff = '^([A-Z][a-z][[:alpha:]]*([[:space:]][[:alpha:]]+){0,2}[,.!]+[[:space:]]*)[\n]';
	$signoff = '^[[:space:]]*([A-Z][a-z][[:alpha:]]*([[:space:]][[:alpha:]]+){0,2}[,.!]+[[:space:]]*)[\n]';
	$signature = $signoff;
/*
	$buffer = '([[:space:]]*[\n])*';
	$name = '([[:alpha:][:space:]]+[\n]?)';
	$email = '([[:alnum:]_-.+]+@[[:alnum:]-]+.[[:alpha:]]+[\n]?)';
	$phone = '([[0-9]-.]{10,}[\n]?)';
//	$signature = $signoff.$buffer.$name.$email.$phone;
*/

	// for regular expression matching when there's no signature
	$email_pattern = '/[[:alnum:]._%+-]+@[[:alnum:].-]+[.][[:alpha:]]{2,6}([.][[:alpha:]]{2})?[\s\S]*/i';
	$intro = '(need|looking|requesting|searching|please|thank)';

	/* connect to gmail */
	$hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
	$username = 'amea@ven-tel.com';
	$password = 'venpass01';

	$since_datetime = format_date($now,'d-M-Y H:i:s',array('h'=>-2));
//hack for now
$since_datetime = '07-May-2016 06:00:00';

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
			if ($email_number<>74) { continue; }
//if ($email_number==69) { continue; }

			// get information specific to this email
			$message = '';
			$header = imap_fetch_overview($inbox,$email_number,0);
			$structure = imap_fetchstructure($inbox, $email_number);
			if (isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[1])) {
				$part = $structure->parts[1];
				$message = imap_decode(imap_fetchbody($inbox,$email_number,2),$part->encoding);
			}

			// output the email header information
			$status = ($header[0]->seen ? 'read' : 'unread');
//			if ($status=='read') { continue; }

			$date_utc = $header[0]->date;
			$date = date("Y-m-d H:i:s",strtotime($date_utc));
			$subject = $header[0]->subject;
			$from = $header[0]->from;

			$emails[$email_number] = array('message'=>$message,'date'=>$date,'from'=>$from,'subject'=>$subject);
		}
	}

	foreach ($emails as $email_number => $email) {
		$message = $email['message'];
		$date = $email['date'];
		$subject = $email['subject'];
		$from = $email['from'];

		echo '<strong>'.$email_number.'</strong>. '.$date.' from: '.$from.'<br/>';
		echo $subject.'<br/>';

		// no tel-explorer emails and no replies!
		if (stristr($subject,'tel-explorer') OR strtoupper(substr($subject,0,3))=='RE:') {
			echo 'Tel-Explorer email or Reply detected, skipping message<br><br>';
			continue;
		}

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
/*
foreach ($results[0] as $row) {
	echo $row.':';
	for ($i=0; $i<strlen($row); $i++) {
		echo ord($row[$i]).',';
	}
	echo '<BR>';
}
exit;
*/

		// go back now and reconstruct a string-based array of all rows for later use
		foreach ($results as $k => $row) {
			foreach ($row as $str) {
				if (! isset($result_strings[$k])) { $result_strings[$k] = ''; }
				if ($result_strings[$k]) { $result_strings[$k] .= ' '; }
				$result_strings[$k] .= $str;
			}
		}

		$matches = array();
		$m = 0;
		//when a heci (or "HECI" in the header) is found, we try to redeem non-matching part#s by searching all fields for hecis
		$heci_found = false;

		$total_count = 0;
		$part_col = false;
		$qty_col = false;
		$heci_col = false;
		$end_col = false;//tripped if one of the part/qty/heci columns is found to be counting from the end of the row
		$master_qty = false;
		$header_row_exists = false;
		$header_row_fields = 0;
		$header_row_slide = 0;//number of consecutive rows that don't match the header row
		foreach ($results as $k => $fields) {
			$num_fields = count($fields);
//			print "<pre>".print_r($fields,true)."</pre>";
			// if this is after the header row, AND if the number of fields is EITHER:
			// 1) two columns less than header row col count (one less could be just a missing qty field, which defaults to 1)
			// OR
			// 2) at least one column more than header row col count
			if ($header_row_exists AND ($num_fields<($header_row_fields-1) OR $num_fields>$header_row_fields)) {
				print "<pre>".print_r($fields,true)."</pre>";

				// the first real row of data is $k=1, so after that point if we're still mismatching cols, jump this row;
				// however, the one exception is if we've found a col to be counting from the end of the row ($end_col),
				// all bets are off with column counts
				if ($header_row_slide>2) { continue; }
				else if ($k>=1) { $header_row_slide++; }
				//if ($header_row_slide>2) { break; }
				//else if ($k>1 AND ! $end_col) { $header_row_slide++; continue; }
			} else if ($header_row_exists) {
				$header_row_slide = 0;//reset?
			}

			$row_count = 0;
			$part_str = '';
			$qty_str = 1;//always default
			$heci_str = '';
//			echo $result_strings[$k].'<BR>';
			if ($k==0) {//first row
				if ($num_fields>1) {
					list($part_col,$qty_col,$heci_col,$header_row_exists) = set_columns($fields,$results);

//					echo "part: $part_col, qty: $qty_col, heci: $heci_col<BR>";
//					print "<pre>".print_r($fields,true)."</pre>";
					if ($header_row_exists) {
						if ($heci_col) { $heci_found = true; }
						$header_row_fields = $num_fields;
					}

					if ($qty_col===false AND $header_row_exists) { break; }
					else if ($header_row_exists) { continue; }
//					if ($header_row_exists) { continue; }

					if ($part_col!==false) { $part_str = $fields[$part_col]; }
					if ($qty_col!==false) { $qty_str = format_qty($fields[$qty_col]); }
					if ($heci_col!==false) {
						$heci_found = true;
						$heci_str = $fields[$heci_col];
					}
//					echo "part: $part_col, qty: $qty_col, heci: $heci_col<BR>";
				} else {/* $num_fields==1 */
					list($part_col,$qty_col,$heci_col,$header_row_exists) = set_columns($fields,$results);

					//no qty is set so default to 1
					if ($qty_col===false AND $master_qty===false) { $master_qty = 1; }

					// this row is confirmed the header, so proceed to next row of real data
					if ($header_row_exists) {
						if ($heci_col) { $heci_found = true; }
						$header_row_fields = $num_fields;
						continue;
					}
//					$part_col = 0;
				}
			} else if ($part_col===false AND $heci_col===false AND $qty_col===false) {
				list($part_col,$qty_col,$heci_col) = sample_fields($fields,1);
			}

			$filtered_qty = filter_qty($result_strings[$k]);
			// if ALL the following conditions are met, assume this is a qty-only row, for the part in the previous row
			// 1) $k>0 (second row or later)
			// 2) NOT an html table
			// 3) a specified qty DOES exist ("qty- 3" / "need 3"), as per filter_qty()
			// 4) total count of matches found is already more than 1 (as in Frontier emails where the part is on first row, qty on second)
			if ($k>0 AND ! $html_table AND $filtered_qty>0 AND $total_count>0) {
				$matches[($m-1)]['qty'] = $filtered_qty;
				$qty_str = $filtered_qty;
				continue;
			} else if ($master_qty) {
				$qty_str = $master_qty;
			} else if ($qty_col!==false) {
				$qty_str = format_qty($fields[$qty_col]);
			} else {/* !$qty_col */
				if ($filtered_qty) {
					$master_qty = $filtered_qty;
					$qty_str = $filtered_qty;
				}
			}
			// try reverse column-counting from end
//			echo $result_strings[$k].'<BR>'.$k.': '.$num_fields.' num fields, '.$header_row_fields.' header row fields, '.$qty_str.' qty str, '.$qty_col.' qty col<BR>';
			if (! is_numeric($qty_str) AND (($num_fields-$qty_col)<>$part_col AND ($num_fields-$qty_col)<>$heci_col) AND ! $filtered_qty) {
				$qty_str = format_qty($fields[($num_fields-$qty_col)]);
				if ($qty_str>0) { $end_col = true; }
			}
			if (! $qty_str) {
				$master_qty = 1;
				$qty_str = $master_qty;
			}

			echo 'qty:'.$qty_str.', '.$part_col.' part col, '.$heci_col.' heci col (line: '.$result_strings[$k].')<BR>';

			$hecidb = array();
			// so we don't have a part col, qty or heci; let's treat every line as its own and attempt to parse based on inline flags
			if ($part_col===false AND $qty_col===false AND $heci_col===false) {
				$hecidb = get_db($fields[0],$result_strings[$k],0);
				if (count($hecidb)>0) {
					$part_col = 0;//can be anything, this is just to not be false
					foreach ($hecidb as $result) {
						$part_str = $result['search'];//get the search that got parsed out of the line string
						break;
					}
					$qty_str = $filtered_qty;
				}
			}

			$num_parts = 0;
			$num_hecis = 0;
			if ($part_col!==false) {
				if (! $part_str) { $part_str = $fields[$part_col]; }
				if (count($hecidb)==0) {
					$hecidb = get_db($part_str,$result_strings[$k],$part_col);
				}

				$num_parts = count($hecidb);
				$row_count += $num_parts;
				$total_count += $num_parts;

				// maybe we guessed wrong on the part column
				if ($total_count==0 AND ! $header_row_exists AND $part_col==0 AND $num_fields>0 AND $heci_col===false) {
					// remove qty-related strings
					$noqty = preg_replace('/'.$QTY_FILTER.'/i','',trim(str_replace($fields[$part_col],'',$result_strings[$k])));
					// clean up by removing one-character chunks
					$cleaned_str = trim(preg_replace('/((^[[:punct:]]*[[:alnum:]][[:punct:]]*[[:space:]]+)|([[:space:]]+[[:punct:]]*[[:alnum:]][[:punct:]]*[[:space:]]+))/','',$noqty));
					$breakups = explode(' ',$cleaned_str);
					foreach ($breakups as $word) {
						$hecidb = get_db($word,$cleaned_str,$part_col);
						if (count($hecidb)>0) {
							$part_str = $word;
							// fetch the original column for this word so we can set that as the new column for ensuing rows
							$part_col = array_search($word,$fields);
							$num_parts = count($hecidb);
							$row_count += $num_parts;
							$total_count += $num_parts;
							break;
						}
					}
				}

				// this is a manf-counter of the variance within the results of hecidb(), which is a tip-off of
				// a string that may be too broad to be useful...
				if (isset($keyword_manfs[$fpart]) AND count($keyword_manfs[$fpart])>3 AND $num_parts>20) {
					echo ' Rejected!<BR>';
					continue;
				}
//				echo ' '.$num_parts.' result(s)';
				if ($num_parts==0) {
//					echo $result_strings[$k].'<BR>';
//continue;
				}

				// check for alternate qty if not valid already
				if ($num_parts==0 AND $total_count>0 AND $num_fields<>$header_row_fields AND $master_qty) {
					if (! $header_row_exists AND $part_col==0 AND $heci_col===false AND $qty_col===false) { $part_col = false; }
					if ($filtered_qty) {
						// set qty to the match of the previous row
						$matches[($m-1)]['qty'] = $filtered_qty;
						// this becomes the master qty for this email because this is not a table but prob a single request
						$master_qty = $filtered_qty;
					}
					continue;
				}

//				print "<pre>".print_r($hecidb,true)."</pre>";
			}
			if ($heci_col!==false) {
				if (! $heci_str) { $heci_str = format_heci($fields[$heci_col]); }
				echo 'heci str: '.$heci_str.', '.$fields[$heci_col].' at heci col '.$heci_col.'<BR>';
				$heci_matches = array();
				if ($heci_col==0 AND preg_match('/^(heci|clei)[[:alpha:]\\/#-]*:[[:space:]]*/i',$fields[$heci_col],$heci_matches)) {
					$heci_str = preg_replace('/^(heci|clei)([[:alpha:]\\/#-]*:[[:space:]]*)([[:alnum:]]+).*/i','$3',$result_strings[$k]);//.'<BR>';
				}

				if ($heci_str) {
					$hecidb2 = hecidb($heci_str);
//					print "<pre>".print_r($hecidb2,true)."</pre>";
					$num_hecis = count($hecidb2);
					if ($num_hecis>0) { $hecidb = $hecidb2; }
					$row_count += $num_hecis;
					$total_count += $num_hecis;
					if ($num_hecis==0) {
						//scan every field for a heci, since header specified a heci
						foreach ($fields as $field) {
							$fheci = format_heci($field);
							if (! $fheci) { continue; }

							$hecidb3 = hecidb($fheci,'heci');
							$num_hecis = count($hecidb3);
							if ($num_hecis>0) {
								$heci_str = $fheci;
								$row_count += $num_hecis;
								$total_count += $num_hecis;
								$hecidb = $hecidb3;
//								echo 'replaced '.$heci_str.' with '.$fheci.'<BR>';
							}
						}
					}
				}
			}
//			echo '<BR>';
			// after the first row, check even line for the signature, a la a field in the format of an email
			if ($row_count==0 AND $k>0 AND $signature_found===false) {
				foreach ($fields as $field) {
					if (filter_var($field, FILTER_VALIDATE_EMAIL)) {
						$signature_found = true;
						break;
					}
				}
				if ($signature_found) { break; }
			}

			$search_str = $part_str;
			if ($heci_str) { $search_str = $heci_str; }

			if (count($hecidb)>0) {
				$matches[$m] = array('search'=>$search_str,'qty'=>$qty_str,'parts'=>$hecidb);
				$m++;
			}
		}

		echo "<BR><BR>";
//		continue;

		foreach ($matches as $match) {
			echo 'search => '.$match['search'].', qty => '.$match['qty'].'<BR>';
			foreach ($match['parts'] as $r) {
				print "<pre>".print_r($r,true)."</pre>";
				break;
			}
		}
//		print "<pre>".print_r($matches,true)."</pre>";

		/* output the email body */
//		echo $message.'<br/><br/>';
	}

	/* close the connection */
	imap_close($inbox);
?>
