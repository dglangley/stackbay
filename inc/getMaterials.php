<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getMaterialsBOM.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/partKey.php';
	// include_once $_SERVER["ROOT_DIR"].'/inc/getInventoryCost.php';
	// include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';
	// This function gets all the materials and needs to cover 4 scenarios
	// - BOM without a PR
	// - BOM without PR installed to order
	// - BOM with PR not installed to order
	// - BOM with PR installed to order

	// Service Bom, purchase request, purchase items, repair components, service materials
	
	// Installed
	// Partid, qty requested, qty purchased, qty available or received, a PO #, qty installed

	// Key = partid
	// If on same PO then group them

	// Inventory record based on partid (status and condition code received) iterate finr results with no PO set as avail if has a PO then next set fo to purchase item and join with the PR on PO_number and request status not closed

	// For sub table
	// PO, Locations, Conditions, Serial

	function getMaterials($taskid, $T, $flag = false) {
		$materials = array();

		$BOM = array();
		
		// Query Service BOM if it is a service
		$BOM = getMaterialsBOM($taskid, $T['item_label']);

		foreach($BOM['materials'] as $row) {
			if (! $row['partid']) { continue; }

			$partkey = partKey($row['partid']);

			if($flag) {
				$partkey = $row['partid'];
			}

			if (isset($materials[$partkey]) AND $row['amount']<>$materials[$partkey]['amount']) {
				$partkey .= '.'.$row['amount'];
			}

			//$materials[$row['partid']] = array(
			$materials[$partkey] = array(
				'partid' => $row['partid'],
				'requested' => $row['qty'],
				'installed' => 0,
				'amount' => $row['amount'],
				'leadtime' => '',
				'leadtime_span' => '',
				'profit_pct' => $row['profit_pct'],
				'quote' => $row['charge']/$row['qty'],
			);
		}

		// Begin building the array for materials with the key partid

		// Query Purchase Request that leads to Purchase Items
		$query = "SELECT * FROM purchase_requests WHERE item_id = ".res($taskid)." AND item_id_label = ".fres($T['item_label']).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			if (! $r['partid']) { continue; }

			$partkey = partKey($r['partid']);

			if($flag) {
				$partkey = $row['partid'];
			}

			// Check and see if the partid exists as a key in the materials array
			//if(! isset($materials[$r['partid']]) AND $r['partid']) {
			if(! isset($materials[$partkey])) {
				$materials[$partkey] = array(
					'partid' => $r['partid'],
					'requested' => $r['qty'],
					'installed' => 0,
					'amount' => '',
					'leadtime' => '',
					'leadtime_span' => '',
					'profit_pct' => '',
					'quote' => '',
				);
			} 

			// Within here build the array of items for the request
			$details = array();

			$details['po_number'] = $r['po_number'];

//			$materials[$r['partid']]['requested'] += $r['qty'];

			$details['requested'] = $r['qty'];
			$details['status'] = $r['status'];
			$details['purchase_request_id'] = $r['id'];

			// If has a po number then query the purchase items for the records
			if($r['po_number']) {
				$query2 = "SELECT * FROM purchase_items WHERE po_number = ".res($r['po_number'])." AND partid = ".res($r['partid']);
				// Check the ref labels too
				$query2 .= " AND ((ref_1_label = ".fres($T['item_label'])." AND ref_1 = ".res($taskid).") OR (ref_2_label = ".fres($T['item_label'])." AND ref_2 = ".res($taskid)."))";
				$query2 .= ";";
				$result2 = qedb($query2);

				while($r2 = mysqli_fetch_assoc($result2)) {
					$details['purchase_item_id'] = $r2['id'];
				}
			}

			//$materials[$r['partid']][] = $details;
			$materials[$partkey]['requests'][] = $details;
		}

		// Query Repair Components / Service Materials
		// Due to differences in the column names and field generate 2 by using an if statement
		$query = "";

		if($T['type'] == 'Service') {
			$query = "SELECT * FROM service_materials WHERE service_item_id = ".res($taskid).";";
		} else {
			// Assume repair
			$query = "SELECT *, invid as inventoryid FROM repair_components WHERE item_id = ".res($taskid)." AND item_id_label = '".$T['item_label']."';";
		}

		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			// We can now attempt to find the purchase request based on the inventory purchase_item_id
			$query2 = "SELECT * FROM inventory WHERE id = ".res($r['inventoryid']).";";
			$result2 = qedb($query2);

			if(mysqli_num_rows($result2) == 0) { continue; }
			$r2 = mysqli_fetch_assoc($result2);

			$partkey = partKey($r2['partid']);

			if($flag) {
				$partkey = $row['partid'];
			}

			//if(! isset($materials[$r2['partid']])) {
			if(! isset($materials[$partkey])) {
				$materials[$partkey] = array(
					'partid' => $r2['partid'],
					'installed' => 0,
					'requested' => 0,
					'amount' => '',
					'leadtime' => '',
					'leadtime_span' => '',
					'profit_pct' => '',
					'quote' => '',
				);
			} 

			// If has a purchase_item_id attempt to find the matching purchase request
			if($r2['purchase_item_id']) {
				// Search within the array for the element with the matching purchase_item_id
				//$key = array_search($r2['purchase_item_id'], array_column($materials[$r2['partid']], 'purchase_item_id'));
				$key = array_search($r2['purchase_item_id'], array_column($materials[$partkey], 'purchase_item_id'));

				// array key can be 0 so check for that too
				if(! empty($key) OR $key === 0) {
					//$materials[$r2['partid']][$key]['installed'] = $r['qty'];
					$materials[$partkey][$key]['installed'] = $r['qty'];

					// Sum it also to the global
					//$materials[$r2['partid']]['installed'] += $r2['qty'];
					$materials[$partkey]['installed'] += $r2['qty'];

					//$materials[$r2['partid']][$key]['material_id'] = $r['id'];
					$materials[$partkey][$key]['material_id'] = $r['id'];
				} else {
					// Wasn't found within the array so add it in with respect to the partid
					$details = array(
						'installed' => $r['qty'],
					);

					//$materials[$r2['partid']]['installed'] += $r2['qty'];
					$materials[$partkey]['installed'] += $r2['qty'];

					//$materials[$r2['partid']][] = $details;
					$materials[$partkey][] = $details;
				}
			} else {
				$details = array(
					'installed' => $r['qty'],
				);

				//$materials[$r2['partid']]['installed'] += $r2['qty'];
				$materials[$partkey]['installed'] += $r2['qty'];

				//$materials[$r2['partid']][] = $details;
				$materials[$partkey][] = $details;
			}
		}

		$available = array();

		foreach($materials as $partkey => $info) {
			$partid = $info['partid'];
			if (! $partid) { continue; }

			$available = getAvailable($partid, $taskid, $T);
			//$materials[$partid]['available'] = $available;
			$materials[$partkey]['available'] = $available;
		}

		// print_r($available);

		// print_r($materials);
		// die($taskid .' '. $T['item_label']);

		return $materials;
	}

	function getQuotedMaterials($taskid, $T) {
		$materials = array();

		$query = "SELECT * FROM service_quote_materials WHERE quote_item_id = ".res($taskid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$materials[$r['partid']][] = array(
				'partid' => $r['partid'],
				'qty' => $r['qty'],
				'amount' => $r['amount'],
				'leadtime' => $r['leadtime'],
				'leadtime_span' => $r['leadtime_span'],
				'profit_pct' => $r['profit_pct'],
				'quote' => $r['quote'],
				'id' => $r['id'],
			);
		}

		return $materials;
	}

	function getAvailable($partid, $taskid, $T) {
		$stock = array();

		// $stock['partid'] = $partid;
		
		// Query the inventory table to find available qty that is floating
		// must be the part and received and also have a positive conditionid
		$query = "SELECT * FROM inventory WHERE partid = ".res($partid)." AND status = 'received' AND conditionid > 0;";
		
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$details = array();

			// Trace the purchase_item_id
			if($r['purchase_item_id']) {
				$query2 = "SELECT p.*, pr.status, pr.id as request_id FROM purchase_items p, purchase_requests pr ";
				$query2 .= "WHERE p.id = ".res($r['purchase_item_id'])." AND p.po_number = pr.po_number AND p.ref_1 = pr.item_id AND p.ref_1_label = pr.item_id_label;";
				$result2 = qedb($query2);

				if(mysqli_num_rows($result2)) {
					$r2 = mysqli_fetch_assoc($result2);

					// Either has to be purchased specifically for this line item or the purchase request has been closed
					if(($r2['ref_1'] == $taskid AND $r2['ref_1_label'] == $T['item_label']) OR ($r2['ref_2'] == $taskid AND $r2['ref_2_label'] == $T['item_label']) OR ($r2['status'] == 'Closed')) {
						// The inventory record is made for this order and has been received and is available
						$summed = false;
							
						foreach($stock[$partid] as $key => $info) {
							if($info['locationid'] == $r['locationid'] AND $info['conditionid'] == $r['conditionid'] AND (! $r['serial_no'] AND ! $info['serial'])){
								$stock[$partid][$key]['available'] += $r['qty'];
								$summed = true;
							}
						} 
						
						if(! $summed AND ! empty($r)) {
							$details['locationid'] = $r['locationid'];
							$details['conditionid'] = $r['conditionid'];
							$details['serial'] = $r['serial_no'];
							$details['available'] = $r['qty']; 
						}
					} 
					
				} else {
					// No purchase request found with the corresponding purchase_item_id
					// Consider it as free floating stock
					$summed = false;
							
					foreach($stock[$partid] as $key => $info) {
						if($info['locationid'] == $r['locationid'] AND $info['conditionid'] == $r['conditionid'] AND (! $r['serial_no'] AND ! $info['serial'])){
							$stock[$partid][$key]['available'] += $r['qty'];
							$summed = true;
						}
					} 
					
					if(! $summed AND ! empty($r)) {
						$details['locationid'] = $r['locationid'];
						$details['conditionid'] = $r['conditionid'];
						$details['serial'] = $r['serial_no'];
						$details['available'] = $r['qty']; 
					}
				}
			}

			if(! empty($details)) {
				$stock[] = $details;
			}
		}
		// print_r($stock);
		return $stock;
	}
