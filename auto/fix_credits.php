<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';

	//$query = "SELECT sales_item_id, sci.id sci_id, ri.id ri_id FROM sales_credits sc, sales_credit_items sci ";//, returns r, return_items ri ";
	//$query .= "WHERE sc.id = sci.cid AND sc.rma = r.rma_number AND r.rma_number = ri.rma_number AND inventoryid IS NOT NULL AND return_item_id > 10 AND sales_item_id > 10000; ";

	$query = "SELECT sales_item_id, sci.id sci_id, sc.rma  FROM sales_credits sc, sales_credit_items sci ";
	//$query .= "WHERE sc.id = sci.cid AND sales_item_id > 10000; ";
	$query .= "WHERE sc.id = sci.cid AND return_item_id > 1000 AND sales_item_id > 0; ";
echo $query.'<BR><BR>';
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$rma = $r['rma'];

		$query2 = "SELECT master_id, serial FROM inventory_rmaticket r, inventory_solditem si ";
		$query2 .= "WHERE r.id = '".$rma."' AND r.item_id = si.id; ";
echo $query2.'<BR>';
		$result2 = qdb($query2,'PIPE');
		if (mysqli_num_rows($result2)==0) {
			die("Missing bdb for rma ".$rma);
		}

		$r2 = mysqli_fetch_assoc($result2);
		$master_rma = $r2['master_id'];
		$serial = $r2['serial'];

		$query2 = "SELECT ri.inventoryid, h.value, ri.id ri_id FROM returns r, return_items ri, inventory i, inventory_history h, sales_items si ";
		$query2 .= "WHERE i.serial_no = '".res(trim($serial))."' AND r.rma_number = '".$master_rma."' AND r.rma_number = ri.rma_number ";
		$query2 .= "AND ri.inventoryid = i.id AND i.id = h.invid AND h.value = si.id AND h.field_changed = 'sales_item_id' AND r.order_number = si.so_number; ";//si.so_number = '".$r['sales_item_id']."'; ";
echo $query2.'<BR>';
		$result2 = qdb($query2);
		if (mysqli_num_rows($result2)==0) {
			die("Could not match: ".$query2);
continue;
			$query2 = "select sci.id, i.returns_item_id from sales_credit_items sci, returns r, return_items ri, inventory i ";
			$query2 .= "where sci.return_item_id = r.rma_number and r.rma_number = ri.rma_number and ri.inventoryid = i.id ";
			$query2 .= "AND sci.sales_item_id = ".$r['sales_item_id'].";";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
			}
echo $query2.'<BR>';
		}
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$query3 = "UPDATE sales_credit_items SET sales_item_id = '".$r2['value']."', return_item_id = '".$r2['ri_id']."' WHERE id = '".$r['sci_id']."'; ";
			$result3 = qdb($query3);
echo $query3.'<BR>';
		}
	}

exit;
	$query = "SELECT * FROM sales_credit_items WHERE return_item_id > 1000; ";
echo $query.'<BR>';
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$rma = $r['return_item_id'];

		$query2 = "SELECT master_id, serial FROM inventory_rmaticket r, inventory_solditem si ";
		$query2 .= "WHERE r.id = '".$rma."' AND r.item_id = si.id; ";
echo $query2.'<BR>';
		$result2 = qdb($query2,'PIPE');
		if (mysqli_num_rows($result2)==0) {
			die("Missing bdb for rma ".$rma);
		}
		$r2 = mysqli_fetch_assoc($result2);
		$master_rma = $r2['master_id'];
		$serial = $r2['serial'];

		$query2 = "SELECT ri.id ri_id FROM return_items ri, inventory i ";
		$query2 .= "WHERE rma_number = '".$master_rma."' AND i.serial_no = '".res(trim($serial))."' AND inventoryid = i.id; ";
		$result2 = qdb($query2);
		if (mysqli_num_rows($result2)==0) {
			die("Missing return items / inventory: ".$query2);
		}
		$r2 = mysqli_fetch_assoc($result2);
		$return_item_id = $r2['ri_id'];
echo $query2.'<BR>';
	}
?>
