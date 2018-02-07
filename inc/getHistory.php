<?php
	function getHistory($inventoryid,$order_number=0,$order_type='',$field_changed='',$min_datetime='',$excl_field='',$excl_value=0) {
		$history_str = '';

		$query = "SELECT * FROM inventory_history h ";
		$query .= "WHERE invid = '".$inventoryid."' ";
		if ($field_changed) { $query .= "AND field_changed RLIKE '".res($field_changed)."' "; }
		if ($min_datetime) { $query .= "AND date_changed > '".$min_datetime."' "; }
		if ($excl_field OR $excl_value) {
			$query .= "AND (";
			if ($excl_field) { $query .= "field_changed <> 'returns_item_id' "; }
			if ($excl_field AND $excl_value) { $query .= "OR "; }
			if ($excl_value) { $query .= "value <> '".$excl_value."' "; }
			$query .= ") ";
		}
		$query .= "ORDER BY date_changed ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$row_info = '<strong>'.format_date($r['date_changed'],'D n/d/y').'</strong> ';

			if ($r['field_changed']=='sales_item_id') {
				$query2 = "SELECT so_number FROM sales_items WHERE id = '".$r['value']."' ";
				if ($order_type=='Sale') { $query2 .= "AND so_number <> '".$order_number."' "; }
				$query2 .= "; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) { continue; }
				$r2 = mysqli_fetch_assoc($result2);
				$row_info .= 'Shipped on SO'.$r2['so_number'].' <a href="/SO'.$r2['so_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a>';
			} else if ($r['field_changed']=='repair_item_id') {
				$query2 = "SELECT ro_number FROM repair_items WHERE id = '".$r['value']."' ";
				if ($order_type=='Repair') { $query2 .= "AND ro_number <> '".$order_number."' "; }
				$query2 .= "; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
				if (mysqli_num_rows($result2)==0) { continue; }
				$r2 = mysqli_fetch_assoc($result2);
				$row_info .= 'Repaired on RO'.$r2['ro_number'].' <a href="/RO'.$r2['ro_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a>';
			} else {
			}

			$history_str .= $row_info.'<BR>';
		}

		return ($history_str);
	}
?>
