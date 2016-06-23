<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getContact.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getCompany.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/setContact.php';

	$from_email = '';
	$from_name = '';
	$companyid = 0;
	$fields = '';//array();
	$types = array();
	$lines = array();
	$email_number = 0;
	//if (isset($_REQUEST['fields']) AND is_array($_REQUEST['fields'])) { $fields = $_REQUEST['fields']; }
	if (isset($_REQUEST['fields'])) { $fields = $_REQUEST['fields']; }
	if (isset($_REQUEST['types']) AND is_array($_REQUEST['types'])) { $types = $_REQUEST['types']; }
	if (isset($_REQUEST['lines']) AND is_array($_REQUEST['lines'])) { $lines = $_REQUEST['lines']; }
	if (isset($_REQUEST['from_email'])) { $from_email = trim($_REQUEST['from_email']); }
	if (isset($_REQUEST['from_name'])) { $from_name = trim($_REQUEST['from_name']); }
	if (isset($_REQUEST['email_number']) AND is_numeric($_REQUEST['email_number'])) { $email_number = $_REQUEST['email_number']; }
//	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }
	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in

/*
print_r($lines);
echo "<BR><BR><BR>";
print_r($fields);
exit;
*/
	$contactid = 0;
	$contact_companyid = getContact($from_email,'email','companyid');

	if ($contact_companyid===false) {//contact doesn't exist
		$contactid = setContact(htmlentities($from_name),$companyid);

		$query = "INSERT INTO emails (email, type, contactid) VALUES ('".res($from_email)."','Work','".$contactid."'); ";
		$result = qdb($query);
	} else {
		$contactid = getContact($from_email,'email','id');
		// set the companyid to this contact, if none is already set
		if ($contactid AND ! $contact_companyid AND $companyid) {
			$query = "UPDATE contacts SET companyid = '".$companyid."' WHERE id = '".$contactid."'; ";
			$result = qdb($query);
		}
	}

/*
	// if the field placement varies from iteration to iteration, it may be that the fields
	// should be counting from the end, not the beginning...
	$pattern = array();
	foreach ($fields as $row => $arr) {
		if ($row>0) {
			$diff = array_diff_assoc($arr, $fields[($row-1)]);
			if (count($diff)>0) {
				//something here?
			}
		}
	}
*/

	// these will be entered into db
	$pattern = '';
	$part_col = false;
	if (isset($_REQUEST['part_col']) AND is_numeric($_REQUEST['part_col'])) { $part_col = $_REQUEST['part_col']-1; }//decrement on account of arrays
	$heci_col = false;
	if (isset($_REQUEST['heci_col']) AND is_numeric($_REQUEST['heci_col'])) { $heci_col = $_REQUEST['heci_col']-1; }//decrement on account of arrays
	$qty_col = false;
	if (isset($_REQUEST['qty_col']) AND is_numeric($_REQUEST['qty_col'])) { $qty_col = $_REQUEST['qty_col']-1; }//decrement on account of arrays

	$part_from_end = 0;
	if (isset($_REQUEST['part_from_end']) AND $_REQUEST['part_from_end']==1) { $part_from_end = 1; }
	$heci_from_end = 0;
	if (isset($_REQUEST['heci_from_end']) AND $_REQUEST['heci_from_end']==1) { $heci_from_end = 1; }
	$qty_from_end = 0;
	if (isset($_REQUEST['qty_from_end']) AND $_REQUEST['qty_from_end']==1) { $qty_from_end = 1; }

//	print "<pre>".print_r($fields,true)."</pre>";
//	print "<pre>".print_r($lines,true)."</pre>";

	$first_row = 0;
	$last_row = 0;

/*
	foreach ($fields as $row_num => $arr) {
		$last_row = $row_num;
		if ($first_row===false) { $first_row = $row_num; } else { continue; }

		$n = 0;
		foreach ($lines[$row_num] as $col_num => $col_text) {
			$type = $types[$row_num];

			$words = explode(' ',$col_text);
			foreach ($words as $k => $word) {
				$word = trim($word);
//				if ($pattern) { $pattern .= '([[:space:]]*'; } else { $pattern .= '('; }

				$type = '';
				if (isset($types[$row_num][$col_num][$k])) { $type = $types[$row_num][$col_num][$k]; }

				if ($type=='part') {
					if ($k>0) {
*/
//						$pattern .= preg_replace('/(.*)('.$arr[$col_num][$k].')/','($1)([[:alnum:][:punct:]]+)',$col_text);
/*
					} else {
						$pattern .= '([[:alnum:][:punct:]]+)';
					}
					if ($part_col) { $part_col .= '-'; }
					$part_col .= $n;
				} else if ($type=='heci') {
*/
//					$pattern .= '([[:punct:]]*[[:alnum:]]{7,10}[[:punct:]]*)';
/*
					$heci_col = $n;
				} else if ($type=='qty') {
					$pattern .= '([0-9]+)';
					$qty_col = $n;
				} else {
				}
				$n++;
			}
		}
echo $pattern.'<BR>';
exit;
continue;
	}
*/

	$query = "INSERT INTO amea (part, part_from_end, qty, qty_from_end, heci, heci_from_end, first_row, last_row, contactid) ";
	$query .= "VALUES (";//'".$pattern."',";
	if ($part_col!==false) {
		$query .= "'".$part_col."','".$part_from_end."',";
	} else {
		$query .= "NULL,NULL,";
	}
	if ($qty_col!==false) {
		$query .= "'".$qty_col."','".$qty_from_end."',";
	} else {
		$query .= "NULL,NULL,";
	}
	if ($heci_col!==false) {
		$query .= "'".$heci_col."','".$heci_from_end."',";
	} else {
		$query .= "NULL,NULL,";
	}
	$query .= "'".$first_row."','".$last_row."','".res($contactid)."'); ";
	$result = qdb($query) OR die(qe());

	header('Location: /amea.php');
	exit;
?>
