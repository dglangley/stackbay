<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';

	//Temp array to hold Brian's data
	$items = array();
	
	//Grab all the broken items with the date imported and the PO > 500000
	$query = "SELECT partid, purchase_item_id as po_number, date_created, id FROM inventory WHERE purchase_item_id > 500000;";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$items[] = $r;
	}

	foreach ($items as $key => $value) {
		//Prep items
		echo 'Part: ' .$value['partid'] . ' PO: ' . $value['po_number'] . '<br>';
		$partid = prep($value['partid']);
		$po_number = prep($value['po_number']);

		$purchase_item_id = 0;
		$date_created = '';

		//Query to get the actual purchase item id and not the PO
		$query = "SELECT id FROM purchase_items WHERE po_number = $po_number AND partid = $partid;";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$purchase_item_id = $r['id'];
		}

		echo 'Item ID: '.$purchase_item_id.'<br>';

		//Grab the created date from purchase order to fulfill the date the inventory item was created
		//If po exists grab the date else keep the date set to 2017-04-30 ...
		$query = "SELECT created FROM purchase_orders WHERE po_number = $po_number;";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$date_created = $r['created'];
		} else {
			$date_created = $value['date_created'];
		}

		//Run the update function with the fixed values
		if($purchase_item_id)
			updateSerialItem($purchase_item_id, $date_created, $value['id']);

		echo '<br><br>';
	}

	//Function to update the broken pieces
	function updateSerialItem($purchase_item_id = null, $date_created, $id) {
		echo 'Info: ' . $purchase_item_id . ' Date: ' . $date_created . ' ID: ' . $id .'<br>';
		//prep variables
		$purchase_item_id = prep($purchase_item_id); 
		$date_created = prep($date_created);
		$id = prep($id);

		//Function to create the insert script
		$query = "UPDATE inventory SET purchase_item_id = $purchase_item_id, date_created = $date_created WHERE id = $id";
		$result = qdb($query) OR die(qe().' '.$query);
		$id = qid();

		return $id;
	}

?>
