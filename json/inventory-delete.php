<?php

//Prepare the page as a JSON type
header('Content-Type: application/json');

$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/jsonDie.php';

	//This is a list of everything
	$partid = grab('partid');
	$serial = grab('serial');
	$po_number = grab('po_number');
	$page = grab('page');
	
	//items = ['partid', 'serial', 'qty', 'location', 'status', 'condition'];
	function deletefromDatabase($partid, $serial, $po_number = '', $page) {
		$result = array();
		
		$result['query'] = true;
		
		
		//First make sure that the serial and the partid combination exists in the inventory database
		$query = "SELECT * FROM inventory WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
		$check = qdb($query);
		
		//If the item exists then delete the inventory row
		if($check->num_rows == 1) {
			//Check if the page is shipping or not (Purchase Order or Sales Order receive/ship)
			if($page != 'shipping') {
				//Delete the inventory item
				$query = "DELETE FROM inventory WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
				$result['query'] = qdb($query);
	
				//If the item is deleted from the inventory then increment the purchase items back to original state, before the serial was scanned
				//Or the PO Number does not exists, in which case most likely the inventory edit page is using this script to delete
				if($result['query'] && $po_number != '') {
					$query = "UPDATE purchase_items SET qty_received = qty_received - 1 WHERE po_number = ". res($po_number) ." AND partid = ". res($partid) .";";
					qdb($query);
				}
			//Current process is shipping
			} else {
				$query = "UPDATE inventory SET qty = qty + 1, sales_item_id = NULL, status = 'received' WHERE partid = '" . res($partid) . "' AND serial_no = '" . res($serial) . "';";
				$result['query'] = qdb($query);
				
				//Remove the package from package contents
				$invid = qid();
				$pc_delete  ="DELETE FROM `package_contents` WHERE `packageid` in (SELECT `id` FROM packages where `order_type` = 'Sale' and order_number = ".prep($po_number).") and `serialid` = $invid;";
				qdb($pc_delete) or jsonDie(qe()." | $pc_delete");
	
				//If the item is deleted from the inventory then increment the purchase items back to original state, before the serial was scanned
				if($result['query']) {
					$query = "UPDATE sales_items SET qty_shipped = qty_shipped - 1, ship_date = NULL WHERE so_number = ". res($po_number) ." AND partid = ". res($partid) .";";
					qdb($query);
				}
			}
		} else {
			$result['query'] = false;
		}
		
		return $result;
	}
	
	$result = deletefromDatabase($partid, $serial, $po_number, $page);
	echo json_encode($result);
    exit;
