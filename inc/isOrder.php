<?php
	function isOrder($str,$type='') {
		$str = trim($str);
		$arr = array('search'=>$str,'type'=>$type);

		$search = '';
		if ($type) {
			$search = $str;
		} else {
			$search_parts = explode('|',preg_replace('/^([\/])([SPR]O)?([[:alnum:].-]{3,25})$/i','$2|$3',trim($str)));
//12-12-18 because I'm using this function now to detect if an order, and not just assuming it IS an order waiting to be parsed
//			if (count($search_parts)<>2) { return $arr; }
			if (empty($search_parts)) { return $arr; }

			if (count($search_parts)==1) {
				$search = $search_parts[0];
			} else {
				$type = $search_parts[0];
				$search = $search_parts[1];
			}
		}
		if (! $search) { return $arr; }

		$arr['search'] = $search;

		if ($type=='SO' OR ! $type) {
			// is this a Service, or a Sale?
			if ($search>=100000 AND $search<200000) {
				$order_type = 'Sale';
				$query = "SELECT so_number, 'Sale' type FROM sales_orders WHERE cust_ref = '".res($search)."' OR so_number = '".res($search)."'; ";
			} else if ($search>=400000 AND $search<500000) {
				$order_type = 'Service';
				$query = "SELECT so_number, 'Service' type FROM service_orders WHERE cust_ref = '".res($search)."' OR so_number = '".res($search)."'; ";
			} else {
				$query = "SELECT so_number, 'Sale' type FROM sales_orders WHERE cust_ref = '".res($search)."' OR so_number = '".res($search)."'; ";
				$result = qedb($query);
				if (mysqli_num_rows($result)==0) {
					$query = "SELECT so_number, 'Service' type FROM service_items WHERE task_name = '".res($search)."' OR so_number = '".res($search)."'; ";
				}
			}
			$result = qedb($query);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['so_number'];
				$arr['type'] = $r['type'];//'SO';
			}
		}

		// if $type is specified and passed into function, or if no type is specified and no type is found above
		if ($type=='RO' OR ! $type AND ! $arr['type']) {
			$query = "SELECT ro_number FROM repair_orders WHERE cust_ref = '".res($search)."' OR ro_number = '".res($search)."'; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['ro_number'];
				$arr['type'] = 'Repair';//'RO';
			}
		}

		// if $type is specified and passed into function, or if no type is specified and no type is found above
		if ($type=='PO' OR ! $type AND ! $arr['type']) {
			$query = "SELECT po_number FROM purchase_orders WHERE assoc_order = '".res($search)."' OR po_number = '".res($search)."'; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['po_number'];
				$arr['type'] = 'Purchase';//'PO';
			} else if (substr($search,0,3)=='357') {
				$query = "SELECT po_number FROM purchase_items pi, maps_PO WHERE BDB_poid = '".res($search)."' AND purchase_item_id = pi.id; ";
				$result = qedb($query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$arr['search'] = $r['po_number'];
					$arr['type'] = 'Purchase';//'PO';
				}
			}
		}

		// if $type is specified and passed into function, or if no type is specified and no type is found above
		if ($type=='OS' OR ! $type AND ! $arr['type']) {
			$query = "SELECT os_number FROM outsourced_orders WHERE os_number = '".res($search)."' OR order_number = '".res($search)."'; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['os_number'];
				$arr['type'] = 'Outsourced';//'OS';
			}
		}

		// if $type is specified and passed into function, or if no type is specified and no type is found above
		if ($type=='RMA' OR ! $type AND ! $arr['type']) {
			$query = "SELECT rma_number FROM returns WHERE rma_number = '".res($search)."'; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$arr['search'] = $r['rma_number'];
				$arr['type'] = 'Return';//'RMA';
			}
		}

		return ($arr);
	}
?>
