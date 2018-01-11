<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';

	function setInvoiceCOGS($invoice_no,$order_type='') {
		if (! $order_type) {
			$query = "SELECT order_type FROM invoices WHERE invoice_no = '".$invoice_no."'; ";
			$result = qedb($query);
			if (mysqli_num_rows($result)==0) {
				return false;
			}
			$r = mysqli_fetch_assoc($result);
			$order_type = $r['order_type'];
		}

		$T = order_type($order_type);

		$debit = $T['je_debit'];
		$credit = $T['je_credit'];

		$jeid = 0;
		$query = "SELECT i.invoice_no, i.order_number, ii.item_id, ii.item_label, ii.line_number, ii.taskid, ii.task_label, ii.id ";
		$query .= "FROM invoices i, invoice_items ii ";
		$query .= "WHERE i.invoice_no = '".$invoice_no."' AND i.invoice_no = ii.invoice_no ";
//		$query .= "GROUP BY i.invoice_no ";
		$query .= "; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==0) {
			return ($jeid);
		}

		$cogs_amount = 0;
		$num_cogs = 0;
		while ($r = mysqli_fetch_assoc($result)) {
			$order_number = $r['order_number'];
			$partid = $r['item_id'];
			$ln = $r['line_number'];

			if ($r['taskid'] AND $r['task_label']) {
				$query2 = "SELECT SUM(sc.cogs_avg) cogs FROM sales_cogs sc WHERE item_id = '".$r['taskid']."' AND item_id_label = '".$r['task_label']."'; ";
			} else {//legacy support
				$query2 = "SELECT SUM(sc.cogs_avg) cogs ";
				$query2 .= "FROM invoice_shipments s, package_contents pc, inventory_history h, ".$T['items']." items, sales_cogs sc ";
				$query2 .= "WHERE s.invoice_item_id = '".$r['id']."' AND s.packageid = pc.packageid AND pc.serialid = h.invid ";
				$query2 .= "AND h.field_changed = '".$T['inventory_label']."' AND h.value = items.id AND items.id = sc.item_id AND sc.inventoryid = h.invid ";
				$query2 .= "AND sc.item_id_label = h.field_changed ";
				$query2 .= "AND items.".$T['order']." = '".$order_number."' AND items.partid = '".$partid."' ";
				if (! $ln AND $ln<>'0') { $query2 .= "AND items.line_number IS NULL "; }
				else { $query2 .= "AND items.line_number = '".$ln."' "; }
				$query2 .= "; ";
			}
//			$query2 .= "AND (ii.line_number = items.line_number OR (ii.line_number IS NULL AND items.line_number IS NULL)) ";
//			$query2 .= "GROUP BY i.invoice_no; ";

			$result2 = qedb($query2);
			$num_results = mysqli_num_rows($result2);
			if ($num_results==0 AND ! $GLOBALS['DEBUG']) {
				continue;
			}
			$num_cogs += $num_results;

			$r2 = mysqli_fetch_assoc($result2);
			$cogs_amount += number_format($r2['cogs'],2,'.','');
		}

		// if no cogs from sales cogs, at least we have a journal entry id from above, just return that
		if ($num_cogs==0) {
			return ($jeid);
		}

		$je_amount = false;
		$query3 = "SELECT SUM(amount) amount FROM journal_entries ";
		$query3 .= "WHERE trans_number = '".$invoice_no."' AND trans_type = 'invoice' AND amount IS NOT NULL; ";
		$result3 = qedb($query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$je_amount = $r3['amount'];
		}

		if ($je_amount!==false AND $cogs_amount==$je_amount AND ! $GLOBALS['DEBUG']) { return ($jeid); }
//		echo $invoice_no.': '.$cogs_amount.' COGS, '.$je_amount.' existing JE amount<BR>';

		// if there's a saved JE amount, determine if it makes $cogs_amount go negative (i.e., COGS was $500 but now $100, so result should be -$400 on COGS account)
		if ($je_amount>0) {
			$cogs_amount -= $je_amount;
			if ($cogs_amount<0) {
				$cogs_amount = -$cogs_amount;//we're entering this into JE as a positive credit as opposed to a negative debit (same diff, but QB likes it this way)
				// reverse accounts
				$debit = $T['je_credit'];
				$credit = $T['je_debit'];
			}
		}

//		print "<pre>".print_r($r2,true)."</pre>"; return false;
		if (! $cogs_amount) {
			$cogs_amount = 0;

//			print "<pre>".print_r($r2,true)."</pre>"; return false;
		}
		$jeid = setJournalEntry(false,$GLOBALS['now'],$debit,$credit,'COGS for Invoice #'.$invoice_no,$cogs_amount,$invoice_no,'invoice');
//		echo '<BR>';

		return ($jeid);
	}
?>
