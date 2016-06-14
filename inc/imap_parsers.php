<?php
	$signature = '^[[:space:]]*([A-Z][a-z][[:alpha:]]*([[:space:]][[:alpha:]]+){0,2}[,.!]+[[:space:]]*)[\n]';

	// for regular expression matching when there's no signature
	$email_pattern = '/[[:alnum:]._%+-]+@[[:alnum:].-]+[.][[:alpha:]]{2,6}([.][[:alpha:]]{2})?[\s\S]*/i';
	$intro = '(need|looking|requesting|searching|please|thank)';

	function parseHtmlTable($tables) {
		global $signature,$intro;

		$results = array();

		$num_tables = $tables->length;
		foreach ($tables as $table) {
			$table_text = $table->nodeValue;
			if (! stristr($table_text,'heci')) {
				if ($num_tables>1 AND (preg_match('/'.$intro.'/i',$table_text) OR preg_match('/'.$signature.'.*/m',$table_text))) { continue; }
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
	$signature_found = false;
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
//			$body = preg_replace('/'.$signature.'[\s\S]*$/m','',$fmessage);
			// break off first occurrence of a line to avoid confusion between signature and introduction
			$body_parts = explode(chr(10),trim($fmessage),2);
			$body = $body_parts[0].chr(10).preg_replace('/'.$signature.'[\s\S]*$/m','',$body_parts[1]);
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
?>
