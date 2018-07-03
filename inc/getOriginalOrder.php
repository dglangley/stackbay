<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	// This function gets the current order and type
	// traverses backwards up the ref labels until amount/price on a order list is > 0
	// If it falls into a hole where the there is no more leads and there exists no invoice then just return false
	function getOriginalOrder($item_id, $order_type, $return = 'item_id') {
		$T = order_type($order_type);

		$query = "SELECT * FROM ".$T['items']." WHERE id = ".res($number).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			$T2 = array();
			$lead_id = '';
			$lead_label = '';
			$invoice_no = 0;

			// Only track if there is ref labels and the price is 0
			// If there is a price there should be an invoice
			if((strpos($r['ref_1_label'], 'item_id') !== false OR (strpos($r['ref_2_label'], 'item_id') !== false)) AND $r['price'] == 0) {
				// Default setting lead
				// Based on the statement above there will a ref label that exists
				$lead_label = (($r['ref_1_label']?:$r['ref_2_label'])); 
				$lead_id = $r['ref_1'];

				// Based on findings it is better to find if a sales_item_id is stamped and track that vs looking into return_item_id
				// In most cases sales_item_id stamped on ref_2 led straight to the original order
				if($r['ref_2_label'] == 'sales_item_id') {
					$lead_label = $r['ref_2_label']; 
					$lead_id = $r['ref_2'];
				}

				if($r['ref_1_label'] == 'sales_item_id') {
					$lead_label = $r['ref_1_label']; 
					$lead_id = $r['ref_1'];
				}

				// Run it back through the function with the next iteration
				// Recursive function
				getOriginalOrder($lead_id, $lead_label);
			} else {
				// It has hit a point where the price is > 0 at the minimum
				if($return == 'order_number') {
					return $r[$T['order']];
				} else {
					// else return the item_id
					return $r['id'];
				}
			}
		}
	}