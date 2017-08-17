<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	$debug = 1;

/*
	$query = "SELECT sc.*, c.invoice_no FROM sales_cogs sc, commissions c ";
	$query .= "WHERE c.invoice_item_id IS NOT NULL AND c.cogsid = sc.id AND c.commission_rate IS NOT NULL; ";
*/

	$types = array(
		/*'Sale'=>array('debit'=>'Inventory Sale COGS','credit'=>'Inventory Asset','items_table'=>'sales_items','item_label'=>'sales_item_id','order_type'=>'so_number'),*/
		'Repair'=>array('debit'=>'Repair COGS','credit'=>'Component Inventory Asset','items_table'=>'repair_items','item_label'=>'repair_item_id','order_type'=>'ro_number'),
	);
	foreach ($types as $type => $r) {
		$debit = $r['debit'];
		$credit = $r['credit'];

		$query = "SELECT i.invoice_no, ii.partid, ii.qty, ii.amount, h.invid inventoryid, SUM(sc.cogs_avg) cogs ";
		$query .= "FROM invoices i, invoice_items ii, invoice_shipments s, package_contents pc, inventory_history h, ".$r['items_table']." items, sales_cogs sc ";
		$query .= "WHERE i.date_invoiced >= '2017-05-01 00:00:00' AND i.invoice_no = ii.invoice_no ";
		$query .= "AND ii.id = s.invoice_item_id AND s.packageid = pc.packageid AND pc.serialid = h.invid ";
		$query .= "AND h.field_changed = '".$r['item_label']."' AND h.value = items.id AND items.id = sc.item_id AND sc.inventoryid = h.invid ";
		$query .= "AND sc.item_id_label = h.field_changed ";
		$query .= "AND items.".$r['order_type']." = i.order_number AND i.order_type = '".$type."' AND ii.partid = items.partid ";
		$query .= "GROUP BY i.invoice_no ORDER BY date_invoiced ASC; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT * FROM journal_entries WHERE trans_number = '".$r['invoice_no']."' AND trans_type = 'invoice'; ";
echo $query2.'<BR>';continue;
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) { continue; }

			$amount = $r['cogs'];
			print "<pre>".print_r($r,true)."</pre>"; continue;
			if (! $amount) {
				$amount = 0;

				print "<pre>".print_r($r,true)."</pre>"; continue;
			}

			$jeid = setJournalEntry(false,$GLOBALS['now'],$debit,$credit,'COGS for Invoice #'.$r['invoice_no'],$amount,$r['invoice_no'],'invoice');
		}
	}
?>
