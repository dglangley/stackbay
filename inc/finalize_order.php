<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPackageContents.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/shipEmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/invoice.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/calcCOGS.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/getInvoiceNumber.php';

	function finalize_order($order_number, $type) {
		$T = order_type($type);

		// Get all the packages that are pending on the order
		$packages = getPendingPackages($order_number, $type);
		$shipped_packages = array();

		// array(invoice_no => cogs diff)
		$invoice_cogs = array();

		$INV = array(
			"invoice_no" => 0,
			"invoice" => 0,
			"error" => '',
		);

		// If the order being shipped has ref labels that point to an outsourced order, do NOT create invoice, it's a dummy shipment
		$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = ".res($order_number)." AND (ref_1_label = 'outsourced_item_id' OR ref_2_label = 'outsourced_item_id');";
		$result = qedb($query);
		$dummy_shipment = qnum($result);

		$inventory_ids = array();

		// Parse out only the ones with content to be shipped out
		foreach($packages as $package) {

			$contents = getPackageContents($package['id']);

			// collect inventory ids for use below
			foreach($contents as $content) {
				$inventory_ids[$content['id']] = $content['id'];
			}
			$shipped_packages[$package['id']] = $package['id'];
		}

		// stamp each package with the current datetime as the shipment id
		foreach($shipped_packages as $packageid) {
			$query = "UPDATE packages SET datetime = ".fres($GLOBALS['now'])." WHERE id = ".res($packageid).";";
			qedb($query);
		}

		// Generate Shipment Email with tracking and serials
		shipEmail($order_number, $type, $GLOBALS['now']);

		/***** GENERATE INVOICE *****/

		// moved this here so we can record the invoice against the cogs records; 10/15/18
		if (! $dummy_shipment) {
			$INV = create_invoice($order_number, $GLOBALS['now']);

			// redeclare with order type from invoicing process, which further detects if this is a referenced RO from SO
			$T = order_type($INV['order_type']);
		}

		// now having created the invoice, we can set COGS with invoice info using inventory ids collected above
		foreach ($inventory_ids as $id => $invid) {
			if ($GLOBALS['DEBUG']==1 OR $GLOBALS['DEBUG']==3) {
				$INV['invoice'] = 18602;
			}

			$invoice_item_id = 0;
			$query2 = "SELECT ii.id FROM invoice_items ii, invoice_shipments s, package_contents pc ";
			$query2 .= "WHERE ii.invoice_no = '".res($INV['invoice'])."' AND ii.id = s.invoice_item_id ";
			$query2 .= "AND s.packageid = pc.packageid AND pc.serialid = '".res($id)."'; ";
			$result2 = qedb($query2);
			if (qnum($result2)>0) {
				$r2 = qrow($result2);
				$invoice_item_id = $r2['id'];
			}

			if ($GLOBALS['DEBUG']==1 OR $GLOBALS['DEBUG']==3) {
				$invoice_item_id = 13732;
			}

$cogs_info = array();//reset to empty for now
//			$cogs_info = calcCOGS($order_number, $id, $INV['invoice'], $invoice_item_id);

			foreach ($cogs_info as $item_id => $cogs_diff) {
				// set at 0 if not exists
				if (! array_key_exists($INV['invoice'], $invoice_cogs)) { $invoice_cogs[$INV['invoice']] = 0; }

				$invoice_cogs[$INV['invoice']] += $cogs_diff;
/*
				$item_invoice = getInvoiceNumber($content['id'], $sales_item_id, $type);

				if($item_invoice) {
					if(! array_key_exists($item_invoice, $invoice_cogs)) {
						$invoice_cogs[$item_invoice] = 0;
					}

					$invoice_cogs[$item_invoice] += $cogs_diff;
				}
*/
			}
		}

// I DON'T THINK THIS SHOULD EVER HAPPEN, or why do we have invoice# referenced inside an IF statement where there is no invoice!!?? 10/15/18
		// either something went wrong, or this is a non-invoiceable shipment (zero-priced)
		if (! $INV['invoice']) {
			$debit_type = ($INV['order_type']=='Repair' ? 'Repair COGS' : 'Inventory Sale COGS');
			$credit_type = ($INV['order_type']=='Repair' ? 'Component Inventory Asset' : 'Inventory Asset');

			// we need to create journal entry because no matter what, we're still shipping out an item,
			// and need to debit sale COGS and credit inventory asset
			$debit_account = ($T['je_debit']?:$debit_type);//'Inventory Sale COGS');
			$credit_account = ($T['je_credit']?:$credit_type);//'Inventory Asset');

			foreach($invoice_cogs as $invoice => $diff) {
				setJournalEntry(false,$GLOBALS['now'],$debit_account,$credit_account,'COGS for Invoice #'.$invoice, $diff,$invoice,'invoice');
			}
		}
	}
?>
