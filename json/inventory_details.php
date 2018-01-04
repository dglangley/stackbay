<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/calcRepairCost.php';
	include_once '../inc/order_type.php';
	include_once '../inc/jsonDie.php';
	header("Content-Type: application/json", true);

	$inventoryid = 0;
	if (isset($_REQUEST['inventoryid']) AND is_numeric($_REQUEST['inventoryid']) AND $_REQUEST['inventoryid']>0) { $inventoryid = trim($_REQUEST['inventoryid']); }
	if (! $inventoryid) {
		jsonDie("Missing inventoryid!");
	}

	$results = array();
	$query = "SELECT * FROM inventory_costs_log ";
	$query .= "WHERE inventoryid = '".res($inventoryid)."'; ";
	$result = qedb($query) OR jsonDie(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)==0) {
		jsonDie("Could not find inventory record ".$inventoryid);
	}
	while ($r = mysqli_fetch_assoc($result)) {
		$event = '';
		switch ($r['event_type']) {
			case 'purchase_item_id':
				$query2 = "SELECT po_number, price FROM purchase_items WHERE id = '".$r['eventid']."'; ";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$event = 'PO '.$r2['po_number'].' $'.$r2['price'].' purchase price';
				}
				break;

			case 'repair_item_id':
				$query2 = "SELECT ro_number FROM repair_items WHERE id = '".$r['eventid']."'; ";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$event = 'RO '.$r2['ro_number'].' $'.calcRepairCost($r2['ro_number'],$r['eventid'],$inventoryid).' repair cost';
				}
				break;

			case 'packageid':
				$query2 = "SELECT order_number, order_type, freight_amount FROM packages WHERE id = '".$r['eventid']."'; ";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					$T = order_type($r2['order_type']);
					$event = $T['abbrev'].' '.$r2['order_number'].' $'.$r['amount'].' freight/pc (Total: $'.$r2['freight_amount'].')';
				}
				break;

			case 'buildid':
				$event = $r['event_type'].' '.$r['eventid'].' $'.$r['amount'];
				break;

			case 'rtv':
			case 'imported_cost':
			case 'import_adjustment':
			case 'freight':
			default:
				$event = $r['event_type'].' '.$r['eventid'].' $'.$r['amount'];
				break;
		}

		$data = array(
			'datetime' => $r['datetime'],
			'event' => $event,
		);
		$results[] = $data;
	}

	echo json_encode(array('results'=>$results));
	exit;
?>
