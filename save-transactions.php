<?php
	print_r($_REQUEST);
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	$entry_date = '';
	if (isset($_REQUEST['entry_date'])) {
		$entry_date = format_date($_REQUEST['entry_date'],'Y-m-d');
	}
	$entry_no = '';
	if (isset($_REQUEST['entry_no'])) { $entry_no = trim($_REQUEST['entry_no']); }
	$entry_debit = '';
	if (isset($_REQUEST['entry_debit'])) { $entry_debit = trim($_REQUEST['entry_debit']); }
	$entry_credit = '';
	if (isset($_REQUEST['entry_credit'])) { $entry_credit = trim($_REQUEST['entry_credit']); }
	$entry_memo = '';
	if (isset($_REQUEST['entry_memo'])) { $entry_memo = trim($_REQUEST['entry_memo']); }
	$entry_amount = '';
	if (isset($_REQUEST['entry_amount'])) { $entry_amount = trim($_REQUEST['entry_amount']); }

	if ($entry_date AND $entry_no AND $entry_debit AND $entry_credit) {
		$id = setJournalEntry($entry_no,$entry_date.' '.date("H:i:s"),$entry_debit,$entry_credit,$entry_memo,$entry_amount);

		$query = "UPDATE journal_entries SET confirmed_datetime = '".$now."', confirmed_by = '".$U['id']."' WHERE id = '".res($id)."'; ";
		$result = qedb($query);
	}

	header('Location: transactions.php');
	exit;
?>
