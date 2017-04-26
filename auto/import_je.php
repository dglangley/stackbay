<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';

	$query = "SELECT * FROM inventory_qblog WHERE `source` = 'generate_invoice' ORDER BY date desc limit 15;";
	
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	
	foreach($result as $row){
		$assoc = simplexml_load_string($row['output']);
		
		
		echo("<pre>");
		echo($row['source']."<br><br>");
		
		if($row['source'] == 'generate_invoice'){
			if($assoc->QBXMLMsgsRq->InvoiceAddRq){
				#if there is an invoiceAddRq, then the result is 
				
			} else if ($assoc->QBXMLMsgsRq->VendorCreditAddRq){
				print_r($assoc->QBXMLMsgsRq->VendorCreditAddRq);
				$id_array = current($assoc->QBXMLMsgsRq->VendorCreditAddRq->attributes()));
				$vendor_credit_id = $id_array['requestID'];
				
			} else {
				print_r($assoc);	
			}
		}
		echo("</pre>");
		// $query = "INSERT INTO journal_entries (entry_no, datetime, debit_account, credit_account, memo, trans_number, trans_type, amount, confirmed_datetime, confirmed_by) ";
		// $query .= "VALUES ($entry_no, $date, $debit_account, $credit_account, $memo, $trans_num, $trans_type, $amount, $confirmed, $confirmed_by); ";
		// $result = qdb($query) OR die(qe().'<BR>'.$query);
		// $id = qid();

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
		
		/*
		SELECT * FROM inventory_journalentry 
		WHERE memo NOT LIKE 'ADP Payroll%' 
		  AND memo NOT LIKE 'COGS for Invoice #%' 
		  AND memo NOT LIKE 'COGS for IT Invoice #%' 
		  AND memo NOT LIKE 'Build Project Close%'
		  AND memo NOT LIKE 'COGS for Repair AE%' 
		  AND memo NOT LIKE 'COGS for Repair Invoice%' 
		  AND memo NOT LIKE 'COGS for RMA Repair #%'
		  AND memo NOT LIKE 'COGS for RMA Repair Return #%'
		  AND memo NOT LIKE 'COGS for RMA Shipment #%'
		  AND memo NOT LIKE 'COGS for RMA Return #%'
		  AND memo NOT LIKE 'Scrap Inventory to %'
		  AND memo NOT LIKE 'Audit'
		  ORDER BY id ASC;
		
		`entry_no` <-- 
		`datetime` <-- 
		`debit_account` <-- 
		`credit_account` <-- 
		`memo` <-- 
		`trans_number` <-- 
		`trans_type` <-- memo_walk looking for invoice, bill, or payment
		`amount` <-- amount
		`confirmed_datetime` <-- Direct import from Brian DB: validated.
		`confirmed_by` <-- Brian doesn't have a value of this, we can make null as the unique import value?
		`id` <-- UNIQUE (we don't need reference to this)
		*/
		
		//Audit
		// inventory_bill.qbref
		// inventory_bill.qb_amount
		// inventory_billli.qbref //THIS FIELD IS ALWAYS NULL IN HIS TABLE
		// inventory_company.qbref_c generate_customers
		// inventory_company.qbref_v generate_vendors
		// inventory_creditmemo.qbref
		// inventory_invoice.qbref
		// inventory_invoiceli.qbref
		// inventory_journalentry.qbref
		// inventory_journalentry.qbclass
		// inventory_journalentryli.qbclass
		// inventory_qbgroup.qbclass
		// inventory_vendorcredit.qbref
		/*
SELECT * FROM inventory_journalentry 
WHERE memo LIKE 'ADP Payroll%' #ADP Payroll has no invoice, no amount, no accounts, and no 
  AND memo NOT LIKE 'COGS for Invoice #%' 
  #All 1808 of these have invoices. 
  #There are some IT invoice items in here D:IT Inventory Sale COGS/C:IT Inventory Asset
  #the majority of the items are D:Inventory Sale COGS/C:Inventory Asset

  AND memo NOT LIKE 'COGS for IT Invoice #%' #All 63 of these are D: IT Inventory Sale COGS; C: IT Inventory Asset. All associated invoices.
  AND memo NOT LIKE 'Build Project Close -%' #All 9 of these are D:Inventory Asset; C: Component Inventory Asset. No associated invoices.
  AND memo NOT LIKE 'Build Project Close COGS%' #ALL 10 of these are D:Inventory Sale COGS/C:Inventory Asset. None of these have an associated invoice_id.
  AND memo NOT LIKE 'COGS for Repair AE%' #All 44 of these are D: Advanced Exchange COGS/C:Inventory Asset. All of these have an invoice_id
  AND memo NOT LIKE 'COGS for Repair Invoice%' #All of these are D:Repair COGS, C: Component inventory. All of them have an invoice_id
  AND memo NOT LIKE 'COGS for RMA Repair #%' 
  #can either have a null invoice_id or not; 
  #what does it mean to have a repair which doesn't have an invoice?
  #Only two of the entries which have no invoice have an associated cost.
  
  AND memo NOT LIKE 'COGS for RMA Repair Return #%' #All of these are D:Component Inventory Asset, C:Repair COGS. All of them have an invoice_id
  AND memo NOT LIKE 'COGS for RMA Shipment #%' #About half of these are D:Inventory Sale COGS, C:Inventory Asset; The rest are D: Inventory Sale COGS;C:Component Inventory Asset. 
  AND memo NOT LIKE 'COGS for RMA Return #%' #All of these are D:Inventory Asset, C:Inventory Sale COGS. Some of these have an associated invoice_id, most do not. 19 of them did not push
  AND memo NOT LIKE 'Scrap Inventory to %' #All of these are D:component, C:inventory asset. Seems slightly backwards to me. No related invoice_id.
  AND memo NOT LIKE 'Audit'  #Only one of these in his system, comparing inventory to inventory assets to inventory sales: no related invoice
  AND memo NOT LIKE 'KGP Logistics CC Charges' #Only one of these in his system, no related invoice_id
  ORDER BY id ASC;*/
		
	}
	
?>
