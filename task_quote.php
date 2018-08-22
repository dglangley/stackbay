<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	$EDIT = true;
	// Added other form submits for function access

	// allows access into each of the form functions so we don't need to reinvent the wheel
	// $EDIT disables the redirect and exit functions / requests / and function calls

	function createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private) {
		$quoteid = 0;

		$query = "INSERT INTO service_quotes (classid, companyid, contactid, datetime, userid, public_notes, private_notes) VALUES ";
		$query .= "(".fres($classid).",".fres($companyid).",".fres($contactid).",".fres($GLOBALS['now']).",".fres($GLOBALS['U']['id']).",".fres($public).",".fres($private).");";
		qedb($query);

		$quoteid = qid();

		return $quoteid;
	}

	function genQuoteItem($quoteid){
		global $ALERT, $T;

		$query = "SELECT * FROM service_quote_items WHERE quoteid = ".res($quoteid)."; ";// LIMIT 1;";
		$result = qedb($query);

		$max_ln = 0;
		// Get the largest line_number if exists and increment by 1
		while ($r = mysqli_fetch_assoc($result)) {
			if ($quote_item_id==$r['id']) {
				$ref_1 = $r['ref_1'];
				$ref_1_label = $r['ref_1_label'];
				$ref_2 = $r['ref_2'];
				$ref_2_label = $r['ref_2_label'];
			}

			if ($r['line_number']>$max_ln) { $max_ln = $r['line_number']; }
		}

		// sets the next coming line_number, else just 1
		$ln = $max_ln+1;

		// The simpliest insert of just a blank line_item
		// Going to assume the qty for the item is 1 currently
		// Can easily grow the qty if needed
		$query = "INSERT INTO service_quote_items (quoteid, line_number, qty) VALUES (".res($quoteid).", ".res($ln).", 1);";

		qedb($query);

		// pass back the taskid
		$taskid = qid();

		return $taskid;
	}

	$NEW = false;

	$quoteid = '';
	if (isset($_REQUEST['quoteid'])) { $quoteid = trim($_REQUEST['quoteid']); }

	$taskid = '';
	if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
	$order_number = '';
	if (isset($_REQUEST['order_number'])) { $order_number = trim($_REQUEST['order_number']); }
	$type = 'service_quote';
	if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

	$T = order_type($type);

	// No quote yet so this is a new quote
	if(! $quoteid) {
		$NEW = true;

		$companyid = '';
		if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
		$classid = '';
		if (isset($_REQUEST['classid'])) { $classid = $_REQUEST['classid']; }
		$contactid = '';
		if (isset($_REQUEST['contactid'])) { $contactid = $_REQUEST['contactid']; }
		$bill_to_id = '';
		if (isset($_REQUEST['bill_to_id'])) { $bill_to_id = $_REQUEST['bill_to_id']; }
		$public = '';
		if (isset($_REQUEST['public_notes'])) { $public = trim($_REQUEST['public_notes']); }
		$private = '';
		if (isset($_REQUEST['private_notes'])) { $private = trim($_REQUEST['private_notes']); }

		// Sidebar Saving
		$quoteid = createQuote($companyid, $contactid, $classid, $bill_to_id, $public, $private);
	}

	if(! $taskid) {
		$taskid = genQuoteItem($quoteid);
	}

	// Form submit for labor
	include_once $_SERVER["ROOT_DIR"].'/task_labor.php';

	// Form submit for the details
	include_once $_SERVER["ROOT_DIR"].'/task_details.php';

	if(! $NEW) {
		// Form submit for the outside orders tab
		include_once $_SERVER["ROOT_DIR"].'/task_outside.php';
	}
	// Add section for materials in the future once we have it created and know how it operates

	header('Location: /quoteNEW.php?taskid=' . $taskid . '&tab=details' . ($ALERT?'&ALERT='.$ALERT:''));

	exit;
