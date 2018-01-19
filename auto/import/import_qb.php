<?php
exit;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	//Temp array to hold Brian's journal stuff
	$journalEntries = array();
	//Hold inventory alias
	$journalEntrieLines = array();
	
	//Store QBgroup information
	$qbgroup = array();
	
	//Query Grab inventory data
	$query = ";";
	
	//Query Grab inventory data
	$query = ";";

	//print_r($inventory);
	
	foreach($journalEntries as $key => $value) {
		//Variables that need to be mapped
		$entry_no = '';
		$datetime = date();
		$debit_account = '';
		$credit_account = '';
		$memo = '';
		$trans_number = 0;
		$trans_type = '';
		$amount = 0.00;
		$confirmed_datetime = date();
		$confirmed_by = 0;
		
		
		
		//trim the entire array
		$value = array_map('trim', $value);

		//Function to grab all data that is needed in the parts table 
		$jeid = setJournalEntry($entry_no, $datetime, $debit_account, $credit_account, $memo, $trans_number
		, $trans_type, $amount, $confirmed_datetime, $confirmed_by);
		
		//Grab journal entry lines
		$query = ";";
		
		//Do something about the journal entry lines (Based on lines 1 entry has multiple lines)
		foreach($journalEntrieLines as $lines) {

		}
		
		/*Locations that have references to QB
		- Invoice
		- Credit (I am assuming customers == credit)
		- Vendor Credit
		*/
		
		unset($journalEntrieLines);
	}
	
	//Parse through each of the qb group items and do something withthe data
	foreach($qbgroup as $group) {
		//Do something here with all of the qbgroup items
		$qbid = setQBgroup($qbid);
	}
	
	function setQBgroup($id) {
		//Write query to replace into qb group table that will be created
		$query = ";";
		return ($id);
	}
?>
