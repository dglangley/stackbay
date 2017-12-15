<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	if (! isset($debug)) { $debug = 0; }

	function setJournalEntry($entry_no,$date,$debit_account,$credit_account,$memo,$amount,$trans_num,$trans_type='invoice',$confirmed=false,$confirmed_by=false) {
		global $debug;

		$q_entryno = prep($entry_no);
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

		// can we consolidate an existing entry with this one if all fields match? if so, sum the result
		$id = 0;
		$query = "SELECT id, amount FROM journal_entries WHERE datetime = $date AND debit_account = $debit_account AND credit_account = $credit_account ";
		$query .= "AND memo = $memo AND trans_number = $trans_num AND trans_type = $trans_type AND confirmed_datetime IS NULL AND confirmed_by IS NULL; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$id = $r['id'];
			$amount += $r['amount'];
		}

		$query = "REPLACE journal_entries (entry_no, datetime, debit_account, credit_account, memo, trans_number, trans_type, amount, ";
		$query .= "confirmed_datetime, confirmed_by";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ($q_entryno, $date, $debit_account, $credit_account, $memo, $trans_num, $trans_type, $amount, ";
		$query .= "$confirmed, $confirmed_by";
		if ($id) { $query .= ", $id"; }
		$query .= "); ";
		if ($debug) {
			echo $query.'<BR>';
			if (! $id) { $id = 999999; }
		} else {
			$result = qedb($query);
			if (! $id) { $id = qid(); }
		}

		// go back and update now with ID; this is kind of legacy crap, maybe revisit this at some point; dl 6-30-17
		if (! $entry_no) {
			$query = "UPDATE journal_entries SET entry_no = '".$id."-JE' WHERE id = $id; ";
			if ($debug) {
				echo $query.'<BR>';
			} else {
				$result = qedb($query);
			}
		}

		return ($id);
	}
?>
