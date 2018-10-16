<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/calcRepairCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setCogs.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCost.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCOGS.php';

	function calcCOGS($order_number, $inventoryid, $invoice=0, $invoice_item_id=0) {
		$cogs_info = array();
		$cogs_ids = array();

		$results = array();
		$query2 = "SELECT si.* FROM inventory i, sales_items si ";
		$query2 .= "WHERE si.so_number = '".res($order_number)."' AND i.id = '".res($inventoryid)."' AND si.id = i.sales_item_id; ";
		$result2 = qedb($query2);// OR die(qe().'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$results[] = $r2;
		}

		if ($GLOBALS['DEBUG']) {
			$results[] = array(
				'partid' => '15937',
				'ref_1' => '3798',
				'ref_1_label' => 'repair_item_id',
				'ref_2' => '',
				'ref_2_label' => '',
				'id' => 2687
			);
		}

		foreach ($results as $r2) {
			$partid = $r2['partid'];

			/***** GET THE REPAIR ITEM ID AND FIND OUT WHAT TYPE OF REPAIR IT IS *****/
			if (($r2['ref_1'] AND $r2['ref_1_label']=='repair_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='repair_item_id')) {
				/***** REPAIR (RMA or BILLABLE) *****/
				$repair_item_id = 0;
				if ($r2['ref_1'] AND $r2['ref_1_label']=='repair_item_id') {
					$repair_item_id = $r2['ref_1'];
				} else {
					$repair_item_id = $r2['ref_2'];
				}

				// find out if the repair is a billable repair order, or a warranty replacement. if a billable repair,
				// the freight gets charged to the customer as an expense, so not claimable as COGS by freight. however, if
				// a warranty repair, then the freight gets added back to the original billable order as COGS because
				// we can't charge the customer, and it's an incurred cost to that original billable sale/repair
				$ro_number = 0;
				$query3 = "SELECT ro_number FROM repair_items WHERE id = '".$repair_item_id."'; ";
				$result3 = qedb($query3);
				if (mysqli_num_rows($result3)==0) {
					// really should never be a case this doesn't come up, but just the same...escape
					continue;
				}

				$r3 = mysqli_fetch_assoc($result3);
				$ro_number = $r3['ro_number'];

				/***** GET TERMS FOR THIS REPAIR ORDER TO DETERMINE ITS TYPE - RMA OR Billable *****/
				$NONBILLABLE = true;
				$query4 = "SELECT termsid FROM repair_orders ro WHERE ro.ro_number = '".res($ro_number)."'; ";
				$result4 = qedb($query4);
				// with an existing record, check the terms id for anything other than "N/A" (non-billable)
				if (mysqli_num_rows($result4)>0) {
					$r4 = mysqli_fetch_assoc($result4);
					if ($r4['termsid']<>15) {//15==N/A
						$NONBILLABLE = false;
					}
				}

				// get cost of repair order for this unit
				$repair_cogs = calcRepairCost($ro_number,$repair_item_id,$inventoryid,$NONBILLABLE);//get repair cost

				// If billable (! $NONBILLABLE), the $repair_cogs gets added to the repair itself; if NON-billable,
				// the $repair_cogs should be added to the *originating* repair, not this most direct
				// repair, since this is the warranty repair for that original billable repair
				if (! $NONBILLABLE) {//this means it's BILLABLE
					if(! array_key_exists($repair_item_id, $cogs_info)) {
						$cogs_info[$repair_item_id] = 0;
					}

					$cogs_info[$repair_item_id] += $repair_cogs;

					// for billable repairs, set cost of goods directly against repair item
					$cogsid = setCogs($inventoryid, $repair_item_id, 'repair_item_id', $repair_cogs, 0, $invoice, $invoice_item_id);
					$cogs_ids[$cogsid] = $repair_cogs;
					continue;
				}

				/***** NON-BILLABLE, GET RMA DATA TO PRODUCE ORIGINATING BILLABLE ORDER *****/
				$query4 = "SELECT ref_1, ref_1_label, ref_2, ref_2_label FROM repair_items ";
				$query4 .= "WHERE id = '".$repair_item_id."' AND (ref_1_label = 'return_item_id' OR ref_2_label = 'return_item_id'); ";
				$result4 = qedb($query4);
				if (mysqli_num_rows($result4)==0) {
					// RMA doesn't exist, which is admittedly weird and awkward, but let's get out of here
					continue;
				}
				$r4 = mysqli_fetch_assoc($result4);
				// determine which ref has return item id
				$return_item_id = 0;
				if ($r4['ref_1'] AND $r4['ref_1_label']=='return_item_id') {
					$return_item_id = $r4['ref_1'];
				} else if ($r4['ref_2'] AND $r4['ref_2_label']=='return_item_id') {
					$return_item_id = $r4['ref_2'];
				}

				// look up the RMA to determine if the originating billable order was a Sale, or Repair
				$query4 = "SELECT order_number, order_type FROM return_items ri, returns r ";
				$query4 .= "WHERE ri.id = '".res($return_item_id)."' AND ri.rma_number = r.rma_number; ";
				$result4 = qedb($query4);
				if (mysqli_num_rows($result4)==0) {
					// oops, missing record
					continue;
				}
				$r4 = mysqli_fetch_assoc($result4);

				if ($r4['order_type']=='Sale') {
					// get the sales item id to which we attach the cogs
					$query5 = "SELECT si.id FROM sales_items si, inventory_history h ";
					$query5 .= "WHERE si.so_number = '".res($r4['order_number'])."' AND h.invid = '".res($inventoryid)."' ";
					$query5 .= "AND si.id = h.value AND h.field_changed = 'sales_item_id'; ";
					$result5 = qedb($query5);
					if (mysqli_num_rows($result5)==0) { continue; }
					$r5 = mysqli_fetch_assoc($result5);

					// sum the new repair cogs with the existing cogs because setCogs() below will SUM and UPDATE the existing COGS record
					$existing_cogs = getCOGS($inventoryid, $r5['id'], 'sales_item_id');
					$cogs = $existing_cogs+$repair_cogs;

					if(! array_key_exists($r5['id'], $cogs_info)) {
						$cogs_info[$r5['id']] = 0;
					}

					$cogs_info[$r5['id']] += $repair_cogs;

					$cogsid = setCogs($inventoryid, $r5['id'], 'sales_item_id', $cogs, 0, $invoice, $invoice_item_id);
					$cogs_ids[$cogsid] = $cogs;
				} else if ($r4['order_type']=='Repair') {
					$query5 = "SELECT ri.id FROM repair_items ri WHERE ri.ro_number = '".res($r4['order_number'])."' AND ri.invid = '".res($inventoryid)."'; ";
					$result5 = qedb($query5);
					if (mysqli_num_rows($result5)==0) { continue; }
					$r5 = mysqli_fetch_assoc($result5);

					// sum the new repair cogs with the existing cogs because setCogs() below will SUM and UPDATE the existing COGS record
					$existing_cogs = getCOGS($inventoryid, $r5['id'], 'repair_item_id');
					$cogs = $existing_cogs+$repair_cogs;

					if(! array_key_exists($r5['id'], $cogs_info)) {
						$cogs_info[$r5['id']] = 0;
					}

					$cogs_info[$r5['id']] += $repair_cogs;

					$cogsid = setCogs($inventoryid, $r5['id'], 'repair_item_id', $cogs, 0, $invoice, $invoice_item_id);
					$cogs_ids[$cogsid] = $cogs;
				}

				// DAVID: we should be adding a negative commission amount (assuming there was cost added, which would negatively impact original comm)
				// setCommission($invoice,$invoice_item_id=0,$inventoryid=0);


			} else if (($r2['ref_1'] AND $r2['ref_1_label']=='sales_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='sales_item_id')) {
				/***** RMA REPLACEMENT *****/
				// this is a replacement unit for a previous sale; get the avg cost of the replacement unit and apply it
				// towards the cogs of the original sale
				$replacement_cogs = getCost($partid,'average',true);//get existing avg cost at this point in time

				// We're adding the $cogs to the originating sales item id, which is what the ref1/ref2 label refers to
				if ($r2['ref_1_label']=='sales_item_id') {
					$sales_item_id = $r2['ref_1'];
				} else {/*$r2['ref_2_label']=='sales_item_id']*/
					$sales_item_id = $r2['ref_2'];
				}

				$existing_cogs = getCOGS($inventoryid,$sales_item_id,'sales_item_id');
				$cogs = $existing_cogs+$replacement_cogs;

				if(! array_key_exists($sales_item_id, $cogs_info)) {
					$cogs_info[$sales_item_id] = 0;
				}

				$cogs_info[$sales_item_id] += $replacement_cogs;

				$cogsid = setCogs($inventoryid, $sales_item_id, 'sales_item_id', $cogs, 0, $invoice, $invoice_item_id);
				$cogs_ids[$cogsid] = $cogs;

				// DAVID: we should be adding a negative commission amount against the originating data in the comms table
				// setCommission($invoice,$invoice_item_id=0,$inventoryid=0);



			} else if (($r2['ref_1'] AND $r2['ref_1_label']=='purchase_item_id') OR ($r2['ref_2'] AND $r2['ref_2_label']=='purchase_item_id')) {
				/***** RTV *****/
				$purchase_item_id = 0;
				if ($r2['ref_1_label']=='purchase_item_id') {
					$purchase_item_id = $r2['ref_1'];
				} else {/*$r2['ref_2_label']=='purchase_item_id']*/
					$purchase_item_id = $r2['ref_2'];
				}
/*DAVID, finish this out!
					// if this item is being shipped back to vendor, the ref labels will have the originating purchase item id,
					// so we treat it as a credit. find out if it was used for a sale, and walk the cogs back
					$cogs = 0;
					$query3 = "SELECT cogs_avg FROM sales_cogs WHERE inventoryid = '".res($inventoryid)."' AND item_id_label = 'sales_item_id' AND cogs_avg > 0; ";
					$result3 = qedb($query3);
					while ($r3 = mysqli_fetch_assoc($result3)) {
						$cogs += $r3['cogs_avg'];
					}

					$cogs = getCost($partid,'average',true);//get existing avg cost at this point in time
					$cogsid = setCogs($inventoryid, $r2['id'], 'sales_item_id', $cogs, 0, $invoice, $invoice_item_id);
					$cogs_ids[$cogsid] = $cogs;
*/
			} else {
				/***** SALE ITEM *****/
				// this item is a billable sale and very straightforward; $r2['id'] is the sales item id
				$cogs = getCost($partid,'average',true);//get existing avg cost at this point in time
				$cogsid = setCogs($inventoryid, $r2['id'], 'sales_item_id', $cogs, 0, $invoice, $invoice_item_id);
				$cogs_ids[$cogsid] = $cogs;
			}
		}

//		return $cogs_info;
		return ($cogs_ids);
	}
?>
