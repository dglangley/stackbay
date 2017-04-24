<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	$query = "SELECT * FROM inventory_journalentry WHERE memo NOT LIKE 'ADP Payroll%' ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {

	function setJournalEntry($entry_no,$date,$debit_account,$credit_account,$memo,$amount,$trans_num,$trans_type='invoice',$confirmed=false,$confirmed_by=false) {
		$entry_no = prep($r['id']."-JE");
		$date = prep($r['date']," 06:00:00");
		$debit_account = prep($r['debit_acct']);
		$credit_account = prep($r['credit_acct']);
		$memo = prep(trim($r['memo']));
		$amount = format_price($r['amount'],false,'',true);//set to db format
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

		//ADP Payroll%
		//COGS for Invoice #%
		//COGS for IT Invoice #%
		//Build Project Close%
		//COGS for Repair AE%
		
		//COGS for Repair Invoice%
		//COGS for RMA Repair #%
		//COGS for RMA Repair Return #%
		//COGS for RMA Shipment #%
		//COGS for RMA Return #%
		//Scrap Inventory to %
		//Audit
		
		// inventory_bill.qbref
		// inventory_bill.qb_amount
		// inventory_billli.qbref //THIS FIELD IS ALWAYS NULL IN HIS TABLE
		// inventory_company.qbref_c
		// inventory_company.qbref_v
		// inventory_creditmemo.qbref
		// inventory_invoice.qbref
		// inventory_invoiceli.qbref
		// inventory_journalentry.qbref
		// inventory_journalentry.qbclass
		// inventory_journalentryli.qbclass
		// inventory_qbgroup.qbclass
		// inventory_vendorcredit.qbref
		return ($id);
	}
		setJournalEntry($r['id']."-JE",$r['date']." 06:00:00",$r['debit_acct'],$r['credit_acct'],$r['memo'],$r['amount'],$r['invoice_id'],'invoice',$r['validated']);
	}
?>
