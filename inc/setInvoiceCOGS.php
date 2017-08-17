<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	function setInvoiceCOGS($invoice_no,$order_type='') {
		if (! $order_type) {
			$query = "SELECT order_type FROM invoices WHERE invoice_no = '".$invoice_no."'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)==0) {
				return false;
			}
			$r = mysqli_fetch_assoc($result);
			$order_type = $r['order_type'];
		}

		if ($order_type=='Sale') {
			$order_field = 'so_number';
			$item_field = 'sales_item_id';
			$item_table = 'sales_items';
			$debit = 'Inventory Sale COGS';
			$credit = 'Inventory Asset';
		} else if ($order_type=='Repair') {
			$order_field = 'ro_number';
			$item_field = 'repair_item_id';
			$item_table = 'repair_items';
			$debit = 'Repair COGS';
			$credit = 'Component Inventory Asset';
		}

		$jeid = 0;
		$query2 = "SELECT i.invoice_no, i.order_number, i.order_type, ii.partid, ii.qty, ii.amount, h.invid inventoryid, SUM(sc.cogs_avg) cogs ";
		$query2 .= "FROM invoices i, invoice_items ii, invoice_shipments s, package_contents pc, inventory_history h, ".$item_table." items, sales_cogs sc ";
		$query2 .= "WHERE i.invoice_no = '".$invoice_no."' AND i.invoice_no = ii.invoice_no ";
		$query2 .= "AND ii.id = s.invoice_item_id AND s.packageid = pc.packageid AND pc.serialid = h.invid ";
		$query2 .= "AND h.field_changed = '".$item_field."' AND h.value = items.id AND items.id = sc.item_id AND sc.inventoryid = h.invid ";
		$query2 .= "AND sc.item_id_label = h.field_changed ";
		$query2 .= "AND items.".$order_field." = i.order_number AND ii.partid = items.partid ";
		$query2 .= "GROUP BY i.invoice_no; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);

			$query3 = "SELECT * FROM journal_entries WHERE trans_number = '".$r2['invoice_no']."' AND trans_type = 'invoice'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) { continue; }

			$amount = $r2['cogs'];
			print "<pre>".print_r($r2,true)."</pre>"; continue;
			if (! $amount) {
				$amount = 0;

				print "<pre>".print_r($r2,true)."</pre>"; continue;
			}

			$jeid = setJournalEntry(false,$GLOBALS['now'],$debit,$credit,'COGS for Invoice #'.$r2['invoice_no'],$amount,$r2['invoice_no'],'invoice');
		}
		return ($jeid);
	}
?>
