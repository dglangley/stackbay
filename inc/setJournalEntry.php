<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	function setJournalEntry($entry_no,$date,$debit_account,$credit_account,$memo,$amount,$trans_num,$trans_type='invoice',$confirmed=false,$confirmed_by=false) {
		$entry_no = prep($entry_no);
		$date = prep($date);
		$debit_account = prep($debit_account);
		$credit_account = prep($credit_account);
		$memo = prep(trim($memo));
		$amount = format_price($amount,false,'',true);//set to db format
		if ($trans_num) {
			$trans_num = prep($trans_num);
			$trans_type = prep($trans_type);
		} else {
			$trans_num = 'NULL';
			$trans_type = 'NULL';
		}
		$confirmed = prep($confirmed);
		$confirmed_by = prep($confirmed_by);

		$query = "INSERT INTO journal_entries (entry_no, datetime, debit_account, credit_account, memo, trans_number, trans_type, amount, confirmed_datetime, confirmed_by) ";
		$query .= "VALUES ($entry_no, $date, $debit_account, $credit_account, $memo, $trans_num, $trans_type, $amount, $confirmed, $confirmed_by); ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$id = qid();

		return ($id);
	}
	
	// function setJournalEntry($type,$row){
	// 	$type = strtolower($type);
	// 	$in = array();
	// 	$nfd = array(
	// 		"invoice" => "invoice_no",
	// 		"bill" => "bill_no",
	// 		"payment" => "id"
	// 		)
	// 	$number = $row[$nfd[$type]];
	// 	$select = "Select count(`id`) rows FROM `journal_entries` WHERE trans_type = '$type' AND trans_number = $number;";
	// 	$check_existing = qdb($select) or die(qe()." | $select");
	// 	$check = mysqli_fetch_assoc($check_existing);
	// 	if($check['rows']){return('Already Set JE for this '.$type);}
		
	// 	$in['type'] = $type;
	// 	$in['datetime'] = $now; //Eventual if statement if I choose to do the import script
	// 	if($type == "invoice"){
	// 		$in['debit_account'] = "Inventory Sale COGS";
	// 		$in['credit_account'] = "Inventory Asset";
			
	// 		//Amount Grabber
	// 		$amount_query = "SELECT SUM(`price`) amount FROM `invoice_items` WHERE `invoice_no` = $number;";
	// 		$amount_result = qdb($amount_query) or die(qe()." | $amount_query");
	// 		$amount_array = mysqli_fetch_assoc($amount_result);
	// 		$in['amount'] = $ammount_array['amount'];
			
	// 		//Eventual room for importing confirmed_datetime/confirmed_by
	// 	} else if($type == "bill"){
			
	// 	} else if($type == "je_import"){
	// 			$in['entry_no'] = $row['entry_no'];
	// 			$in['datetime'] = $row['date'];
	// 			$in['debit_account'] = $row['debit_acct'];
	// 			$in['credit_account'] = $row['credit_account'];
	// 			$memo = prep(trim($row['memo']));
	// 			$amount = format_price($amount,false,'',true);//set to db format
	// 			if ($trans_num) {
	// 				$trans_num = prep($trans_num);
	// 				$trans_type = prep($trans_type);
	// 			} else {
	// 				$trans_num = 'NULL';
	// 				$trans_type = 'NULL';
	// 			}
	// 			$confirmed = prep($confirmed);
	// 			$confirmed_by = prep($confirmed_by);
		
			
	// 	}
	// 	$query = "INSERT INTO journal_entries (entry_no, datetime, debit_account, credit_account, memo, trans_number, trans_type, amount, confirmed_datetime, confirmed_by) ";
	// 	$query .= "VALUES (
	// 		".$in['entry_no'].", 
	// 		".$in['date'].", 
	// 		".$in['debit_account'].", 
	// 		".$in['credit_account'].", 
	// 		".$in['memo'].", 
	// 		".$in['trans_num'].", 
	// 		".$in['trans_type'].", 
	// 		".$in['amount'].", 
	// 		".$in['confirmed'].", 
	// 		".$in['confirmed_by']."); ";
	// 	$result = qdb($query) OR die(qe().'<BR>'.$query);
	// 	$id = qid();

	// 	return ($id);
	// }
?>
