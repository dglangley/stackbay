<?php
	$rootdir = '';
	include_once $rootdir.'inc/dbconnect.php';
	include_once $rootdir.'inc/format_date.php';
	include_once $rootdir.'inc/format_price.php';
	include_once $rootdir.'inc/getCompany.php';
	include_once $rootdir.'inc/getPart.php';
	include_once $rootdir.'inc/getPartId.php';
	include_once $rootdir.'inc/setPart.php';
	include_once $rootdir.'inc/pipe.php';
	include_once $rootdir.'inc/getPipeIds.php';
	include_once $rootdir.'inc/calcRepairCost.php';
	include_once $rootdir.'inc/form_handle.php';

	$USER_MAPS = array(
		1=>1398,/*brian*/
		2=>2,/*sam*/
		3=>3,/*chris*/
		5=>3,/*accounting => chris*/
		9=>1401,/*mike*/
		11=>3,/*sabedra*/
		13=>1399,/*vicky*/
		18=>1,/*david*/
		21=>3,/*juan*/
	);
	function mapUser($id) {
		global $USER_MAPS;

		if (! $id) { return false; }

		return ($USER_MAPS[$id]);
	}
	function setNotes($partid,$notes,$userid,$date) {
		$notes = trim($notes);
		if (! $notes) { return false; }

		$note = prep(trim($notes));
		$query2 = "SELECT * FROM prices WHERE partid = $partid AND note = $note AND userid = $userid; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) {
			$query2 = "REPLACE prices (partid, price, datetime, note, userid) ";
			$query2 .= "VALUES ($partid, NULL, $date, $note, $userid); ";
echo $query2.'<BR>';
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		}

		return true;
	}

	$CONTACT_MAPS = array();
	function mapContact($id,$companyid=false) {
		global $CONTACT_MAPS;

		if (! $id) { return false; }
		if (isset($CONTACT_MAPS[$id])) { return ($CONTACT_MAPS[$id]); }

		$query = "SELECT * FROM inventory_people WHERE id = '".res($id)."'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		$r = mysqli_fetch_assoc($result);
		$name = trim($r['name']);
		if ($companyid===false) { $companyid = dbTranslate($r['company_id']); }

		$contactid = 0;
		$query2 = "SELECT * FROM contacts WHERE name = '".res($name)."' AND companyid = '".$companyid."'; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$contactid = $r2['id'];
		}

		// name, title, email, phone, im, note, ar, ap, hide?, company_id
		if (! $contactid) {
			$name = prep($name);
			$title = prep(trim(str_replace('None','',$r['title'])));
			$notes = trim($r['note']);
			if ($r['ar']) { $notes .= ' AR'; }
			if ($r['ap']) { $notes .= ' AP'; }
			$notes = prep(trim($notes));
			$status = 'Active';
			if ($r['hide']) { $status = 'Inactive'; }

			$query2 = "INSERT INTO contacts (name, title, notes, ebayid, status, companyid) ";
			$query2 .= "VALUES ($name, $title, $notes, NULL, '".$status."', '".$companyid."'); ";
echo $query2.'<BR>';
//			$result2 = qdb($query2) OR die(qe().' '.$query2);
//			$contactid = qid();
		}

		$email = trim($r['email']);
		if ($email) {
			$query2 = "";
		}
		$im = trim($r['im']);
		if ($im) {
			$query2 = "";
		}
		$phone = trim($r['phone']);
		if ($phone) {
			$query2 = "";
		}
	}

	$COUNTRY_MAPS = array(
		'' => 'US',
		'USA' => 'US',
		'Brazil' => 'BR',
		'Canada' => 'CA',
		'China' => 'CN',
		'Guam' => 'GU',
		'Israel' => 'IL',
		'Lomé' => 'TG',
		'Togo' => 'TG',
		'UK' => 'UK',
		'United Kingdom' => 'UK',
	);
	$ADDRESS_MAPS = array();
	function mapAddress($id) {
		global $ADDRESS_MAPS,$COUNTRY_MAPS;

		if (! $id) { return false; }
		if (isset($ADDRESS_MAPS[$id])) { return ($ADDRESS_MAPS[$id]); }

		$query = "SELECT * FROM inventory_companylocation WHERE id = '".res($id)."'; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		$r = mysqli_fetch_assoc($result);
		$name = prep($r['name']);
		$street = prep($r['address']);
		$addr2 = 'NULL';
		$addr3 = 'NULL';
		$city = trim($r['city']);
		if (! $city) { $city = ''; }
		$city = prep($city);
		$state = trim($r['state']);
		if (! $state) { $state = ''; }
		$state = prep($state);
		$postal_code = prep($r['zip']);
		$country = prep($COUNTRY_MAPS[trim($r['country'])]);
		$notes = prep($r['notes']);

		$query2 = "SELECT * FROM addresses WHERE name ";
		if ($name=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $name "; }
		$query2 .= "AND street ";
		if ($street=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $street "; }
		$query2 .= "AND addr2 ";
		if ($addr2=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $addr2 "; }
		$query2 .= "AND addr3 ";
		if ($addr3=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $addr3 "; }
		$query2 .= "AND city ";
		if ($city=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $city "; }
		$query2 .= "AND state ";
		if ($state=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $state "; }
		$query2 .= "AND postal_code ";
		if ($postal_code=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $postal_code "; }
		$query2 .= "AND country ";
		if ($country=='NULL') { $query2 .= "IS NULL "; } else { $query2 .= "= $country "; }
		$query2 .= "; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$ADDRESS_MAPS[$id] = $r2['id'];

			$query3 = "UPDATE addresses SET notes = $notes WHERE id = '".$r2['id']."'; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		} else {
			$query2 = "INSERT INTO addresses (name, street, addr2, addr3, city, state, postal_code, country, notes) ";
			$query2 .= "VALUES ($name, $street, $addr2, $addr3, $city, $state, $postal_code, $country, $notes); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$ADDRESS_MAPS[$id] = qid();
		}

		return ($ADDRESS_MAPS[$id]);
	}

	$TERMS_MAPS = array(
		1 => 10,
		2 => 6,
		3 => 12,
		4 => 4,
		5 => 14,
		6 => 13,
		7 => 7,
		8 => 3,
		9 => 2,
		10 => 11,
		11 => 8,
		12 => 1,
		13 => 6,
		14 => 9,
	);
	$WARRANTY_MAPS = array(
		0 =>0,
		1 =>4,/*30 days*/
		2 =>1,/*AS IS*/
		3 =>2,/*5 days*/
		4 =>5,/*45 days*/
		5 =>7,/*90 days*/
		6 =>8,/*120 days*/
		7 =>10,/*1 year*/
		8 =>11,/*2 years*/
		9 =>12,/*3 years*/
		10 =>13,/*lifetime*/
		11 =>9,/*6 months*/
		12 =>6,/*60 days*/
		13 =>3,/*14 days*/
		14 =>9,/*180 days*/
	);
	$upsid = 1;
	$fedexid = 2;
	$otherid = 3;
	$CARRIER_MAPS = array(
		1 => $upsid,
		2 => $fedexid,
		3 => $otherid,
		4 => $fedexid,
		5 => $fedexid,
		6 => $fedexid,
		7 => $upsid,
		8 => $upsid,
		9 => $upsid,
		10 => $otherid,
		11 => $otherid,
		12 => $upsid,
		13 => $upsid,
	);
	$SERVICE_MAPS = array(
		1 => 1,/*UPS GROUND*/
		2 => 7,/*FedEx GROUND*/
		3 => 13,/*Other LTL*/
		4 => 12,/*FedEx Standard Overnight*/
		5 => 9,/*FedEx 2Day*/
		6 => 8,/*FedEx Express Saver*/
		7 => 4,/*UPS Overnight*/
		8 => 3,/*UPS 2nd Day Air*/
		9 => 2,/*UPS 3 Day Select*/
		10 => 14,/*Other Other*/
		11 => 14,/*Other Other*/
		12 => 6,/*UPS Red Saver*/
		13 => 4,/*UPS Overnight*/
	);

/*
	$query = "DELETE FROM addresses WHERE id > 22; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "ALTER TABLE addresses AUTO_INCREMENT = 1; ";
	$result = qdb($query) OR die(qe().' '.$query);
*/
	$query = "DELETE FROM purchase_orders; ";// WHERE created <= '2015-12-31 23:59:59'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM purchase_items; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM sales_orders; ";// WHERE created <= '2015-12-31 23:59:59'; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM sales_items; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM packages; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM package_contents; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM inventory; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM inventory_history; ";
	$result = qdb($query) OR die(qe().' '.$query);

	$purchStart = '2013-01-01';
	$startDate = '2014-01-01';
	$endDate = '2014-03-31';
	$bogus_serial = 100001;

	/*************************************/
	/**** run company_maps.php first! ****/
	/*************************************/

	$packages = array();
	$new_po = array();
	$query = "SELECT po.po_date, po.bill_from_id, po.po_terms_id, po.delivery_due, po.drop_ship_to, po.freight_carrier_id, ";
	$query .= "po.memo, po.freight_charge, po.canceled_by_id, po.ext_memo, po.freight_charge_billed, ";/*po.contact_id, */
//	$query .= "pq.company_id, pq.purchase_rep_id, pq.warranty_period_id, pq.terms_id, pq.contact_id ";
	$query .= "iq.company_id, iq.quote_id, iq.id iqid, iq.quantity, iq.price, iq.notes, iq.creator_id, iq.pn_override, iq.contact_id, ";
	$query .= "i.part_number, i.clei, i.heci, i.short_description description, m.name manf, pq.warranty_period_id ";
	$query .= "FROM inventory_purchaseorder po, inventory_incoming_quote iq, inventory_company c, inventory_inventory i, ";
	$query .= "inventory_manufacturer m, inventory_purchasequote pq ";
	$query .= "WHERE po.purchasequote_ptr_id = iq.quote_id AND iq.company_id = c.id AND iq.inventory_id = i.id ";
	$query .= "AND m.id = i.manufacturer_id_id AND pq.id = iq.quote_id ";
	if ($purchStart) {
		$dbStartDate = format_date($purchStart, 'Y-m-d');
		$dbEndDate = format_date($endDate, 'Y-m-d');
		$query .= "AND po.po_date between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY po.po_date ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$companyid = prep(dbTranslate($r['company_id']));
		$po = prep($r['quote_id']);
		$date = prep($r['po_date'].' 12:00:00');//append noon just cuz it's better than impractical 00:00:00
		$contactid = prep(mapContact($r['contact_id'],$companyid));
		$created_by = prep(mapUser($r['creator_id']));
		$ext_memo = preg_replace('/^((none)|(n\/a))$/i','',trim($r['ext_memo']));
		$public_notes = prep(trim($ext_memo));
		$private_notes = prep(preg_replace('/^((none)|(n\/a))$/i','',trim($r['memo'])));
		$remit_to_id = prep(mapAddress($r['bill_from_id']));
		$ship_to_id = prep(1);
		$freight_carrier_id = prep($CARRIER_MAPS[$r['freight_carrier_id']]);
		$freight_services_id = prep($SERVICE_MAPS[$r['freight_carrier_id']]);
		$freight_account_id = 'NULL';
		$termsid = prep($TERMS_MAPS[$r['po_terms_id']]);
		$status = 'Active';
		if ($r['canceled_by_id']) { $status = 'Void'; }

		if (! isset($new_po[$r['quote_id']])) {
			$query2 = "REPLACE purchase_orders (po_number, created, created_by, sales_rep_id, companyid, contactid, ";
			$query2 .= "assoc_order, remit_to_id, ship_to_id, freight_carrier_id, freight_services_id, ";
			$query2 .= "freight_account_id, termsid, public_notes, private_notes, status) ";
			$query2 .= "VALUES ($po, $date, $created_by, $created_by, $companyid, $contactid, ";
			$query2 .= "NULL, $remit_to_id, $ship_to_id, $freight_carrier_id, $freight_services_id, ";
			$query2 .= "$freight_account_id, $termsid, $public_notes, $private_notes, '".$status."'); ";
echo $query2.'<BR>';
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$new_po[$r['quote_id']] = qid();
		}

		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string
		$partid = getPartId($r['part_number'],$r['heci']);
		if (! $partid) {
			$partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['description']));
		}
		$ln = 'NULL';
		$qty = 0;
		$recd_qty = 0;
		if ($r['quantity']>0) {
			$qty = $r['quantity'];
		}
		$price = prep($r['price']);
		$receive_date = 'NULL';
		if ($r['delivery_due']) {
			$receive_date = prep($r['delivery_due'].' 10:00:00');
		}
		$ref1 = 'NULL';
		$ref1_label = 'NULL';
		if ($r['ref_no']) {
			$ref1 = prep($r['ref_no']);
			$ref1_label = prep('ref');
		}
		// use part override as ref2
		$ref2 = 'NULL';
		$ref2_label = 'NULL';
		if ($r['pn_override']) {
			$ref2 = prep($r['pn_override']);
			$ref2_label = prep('PN');
		}

		$warranty = $WARRANTY_MAPS[$r['warranty_period_id']];

		$po_number = prep($new_po[$r['quote_id']]);
		$partid = prep($partid);

		// add line items
		$query2 = "REPLACE purchase_items (partid, po_number, line_number, qty, qty_received, price, receive_date, ";
		$query2 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, cond) ";
		$query2 .= "VALUES ($partid, $po_number, $ln, $qty, $qty, $price, $receive_date, ";
		$query2 .= "$ref1, $ref1_label, $ref2, $ref2_label, $warranty, 'used'); ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		$po_item_id = qid();

		// add notes from quote to prices table
		setNotes($partid,$r['notes'],$created_by,$date);

		// add package data, only once per order (so only the first time for this 'quote_id', per IF condition above)
		if (! isset($packages['PO'.$r['quote_id']])) { $packages['PO'.$r['quote_id']] = array(); }
		$query2 = "SELECT s.tracking_no, s.date, pi.freight_cogs, s.serials, replace(s.note,pi.po_id,'') note ";
		$query2 .= "FROM inventory_incoming_quote_invoice iqi, inventory_purchaseinvoice pi, inventory_shipping s ";
		$query2 .= "WHERE pi.po_id = $po AND iqi.purchase_invoice_id = pi.id AND iqi.id = s.iqi_id; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
//			$note = trim(preg_replace('/^'.$r['quote_id'].'/','',$r2['note']));
			$note = trim($r2['note']);

			$packages['PO'.$r['quote_id']][$r2['id']] = true;//group packages on sales invoice id
			$n = prep(count($packages['PO'.$r['quote_id']]));

			$query3 = "SELECT id FROM packages WHERE order_number = $po_number AND package_no = $n; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$pkgid = $r3['id'];
			} else {
				$freight = 'NULL';
				if ($r2['freight_cogs']>0) { $freight = prep($r2['freight_cogs']); }

				$query3 = "REPLACE packages (weight, length, width, height, order_number, package_no, ";
				$query3 .= "tracking_no, datetime, freight_amount) ";
				$query3 .= "VALUES (NULL, NULL, NULL, NULL, $po_number, $n, ";
				$query3 .= "'".$r2['tracking_no']."', '".$r2['date']." 10:00:00', $freight); ";
echo $query3.'<BR>';
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				$pkgid = qid();
			}

			$serials = explode(chr(10),$r2['serials']);
			$recd_qty += count($serials);
			foreach ($serials as $serial) {
				$serial = trim($serial);
				if (! $serial) { continue; }

				$ser = prep($serial);

				// get stock date based on rep exp date
				$stock_date = $r2['date'];

				// create new order of serials if '000' because Brian re-used this generic serial not just in
				// one or two duplicate times, but EVERY time, resulting in duplicate nonsense garbage that
				// we can't have in the new system
				if ($serial=='000') { $serial = 'VTL'.($bogus_serial++); }
				$ser = prep($serial);//re-prep after new bogus serial has been generated

				$query3 = "SELECT id FROM inventory WHERE serial_no = $ser AND partid = $partid; ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					$serialid = $r3['id'];

					$query3 = "UPDATE inventory SET last_purchase = $po_number WHERE id = $serialid; ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

					$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date." 10:00:00' ";
					$query3 .= "WHERE invid = $serialid AND field_changed = 'last_purchase' AND value = $po_number; ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
/*
					// get last updated record from inventory_history to update with the record's date, not current timestamp
					$query3 = "SELECT changed_from, date_changed FROM inventory_history ";
					$query3 .= "WHERE invid = $serialid AND field_changed = 'last_purchase' ";
					$query3 .= "ORDER BY date_changed DESC; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$changed_from = $r3['changed_from'];
						$date_changed = $r3['date_changed'];

						$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date." 10:00:00' ";
						$query3 .= "WHERE invid = $serialid AND field_changed = 'last_purchase' ";
						$query3 .= "AND date_changed = '".$date_changed."' AND changed_from = '".$changed_from."'; ";
						$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					}
*/
					//continue;
				} else {
					$query3 = "REPLACE inventory (serial_no, qty, partid, item_condition, status, locationid, ";
					$query3 .= "last_purchase, last_sale, last_return, userid, date_created, notes) ";
					$query3 .= "VALUES ($ser, 1, $partid, 'used', 'shelved', 1, ";
					$query3 .= "$po_number, NULL, NULL, 0, '".$stock_date." 10:00:00', NULL); ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					$serialid = qid();

					$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date." 10:00:00' ";
					$query3 .= "WHERE invid = $serialid; ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				}

				$query3 = "REPLACE package_contents (packageid, serialid) VALUES ($pkgid, $serialid); ";
echo $query3.'<BR>';
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			}
		}

		// finished receiving all serials (qtys), compare now with the PO qty and if not the same,
		// update the purchase_items with the correct qty_received
		if ($recd_qty<>$qty) {
			$query2 = "UPDATE purchase_items SET qty_received = $recd_qty WHERE id = $po_item_id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		}
	}

	$new_so = array();
	$query = "SELECT oq.company_id, oq.quote_id, oq.id oqid, so.so_date, canceled_by_id, oq.contact_id, oq.creator_id, ";
	$query .= "so.po_number, so.po_file, so.ext_memo, so.memo, so.bill_to_id, so.ship_to_id, oq.notes, ";
	$query .= "freight_carrier_id, so_terms_id, freight_notes, oq.inventory_id, i.part_number, i.clei, i.heci, ";
	$query .= "i.short_description description, m.name manf, so.ship_to_override, ";
	$query .= "oq.line_number, oq.quantity, oq.price, so.delivery_date, oq.ref_no, so.freight_charge ";
	$query .= "FROM inventory_salesorder so, inventory_outgoing_quote oq, inventory_company c, inventory_inventory i, inventory_manufacturer m ";
	$query .= "WHERE so.quote_ptr_id = oq.quote_id AND oq.company_id = c.id AND oq.inventory_id = i.id AND m.id = i.manufacturer_id_id ";
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d');
		$dbEndDate = format_date($endDate, 'Y-m-d');
		$query .= "AND so.so_date between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY so.so_date ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$companyid = prep(dbTranslate($r['company_id']));
		$so = prep($r['quote_id']);
		$date = prep($r['so_date'].' 12:00:00');//append noon just cuz it's better than impractical 00:00:00
		$status = 'Active';
		if ($r['canceled_by_id']) { $status = 'Void'; }
		$contactid = prep(mapContact($r['contact_id'],$companyid));
		$created_by = prep(mapUser($r['creator_id']));
		$po_number = prep($r['po_number']);
		$po_file = prep($r['po_file']);
		$ext_memo = preg_replace('/^((none)|(n\/a))$/i','',trim($r['ext_memo']));
		$freight_notes = preg_replace('/^((none)|(n\/a))$/i','',trim($r['freight_notes']));
		$public_notes = trim($freight_notes.' '.$ext_memo);
		if ($r['ship_to_override']) {
			$public_notes .= chr(10).chr(10).$r['ship_to_override'];
		}
		$public_notes = prep($public_notes);
		$private_notes = prep(preg_replace('/^((none)|(n\/a))$/i','',trim($r['memo'])));
		$bill_to_id = prep(mapAddress($r['bill_to_id']));
		$ship_to_id = prep(mapAddress($r['ship_to_id']));
		$freight_carrier_id = prep($CARRIER_MAPS[$r['freight_carrier_id']]);
		$freight_services_id = prep($SERVICE_MAPS[$r['freight_carrier_id']]);
		$freight_account_id = 'NULL';
		$termsid = prep($TERMS_MAPS[$r['so_terms_id']]);

		if (! isset($new_so[$r['quote_id']])) {
			$query2 = "REPLACE sales_orders (so_number, created, created_by, sales_rep_id, companyid, contactid, ";
			$query2 .= "cust_ref, ref_ln, bill_to_id, ship_to_id, freight_carrier_id, freight_services_id, ";
			$query2 .= "freight_account_id, termsid, public_notes, private_notes, status) ";
			$query2 .= "VALUES ($so, $date, $created_by, $created_by, $companyid, $contactid, ";
			$query2 .= "$po_number, $po_file, $bill_to_id, $ship_to_id, $freight_carrier_id, $freight_services_id, ";
			$query2 .= "$freight_account_id, $termsid, $public_notes, $private_notes, '".$status."'); ";
echo $query2.'<BR>';
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$new_so[$r['quote_id']] = qid();
		}

		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		$partid = getPartId($r['part_number'],$r['heci']);
		if (! $partid) {
			$partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['description']));
		}
		$ln = prep($r['line_number']);
		$qty = 0;
		$qty_shipped = 0;
		if ($r['quantity']>0) {
			$qty = $r['quantity'];
		}
		$price = prep($r['price']);
		$ship_date = 'NULL';
		$ref1 = 'NULL';
		$ref1_label = 'NULL';
		if ($r['ref_no']) {
			$ref1 = prep($r['ref_no']);
			$ref1_label = prep('ref');
		}
		// use clei override as ref2
		$ref2 = 'NULL';
		$ref2_label = 'NULL';
		if ($r['clei_override']) {
			$ref2 = prep($r['clei_override']);
			$ref2_label = prep('HECI');
		}
		$delivery_date = 'NULL';
		if ($r['delivery_date']) { $delivery_date = prep($r['delivery_date']); }
		$warranty = 'NULL';
		// better be a valid quote_id! otherwise bad data
		if ($r['quote_id']) {
			$query2 = "SELECT warranty_period_id FROM inventory_quote WHERE id = '".$r['quote_id']."'; ";
			$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$warranty = $WARRANTY_MAPS[$r2['warranty_period_id']];
			}
		}

		$so_number = $new_so[$r['quote_id']];
		$partid = prep($partid);

		// add line items
		$query2 = "REPLACE sales_items (partid, so_number, line_number, qty, qty_shipped, price, delivery_date, ship_date, ";
		$query2 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, cond) ";
		$query2 .= "VALUES ($partid, $so_number, $ln, $qty, $qty, $price, $delivery_date, $ship_date, ";
		$query2 .= "$ref1, $ref1_label, $ref2, $ref2_label, $warranty, 'used'); ";
echo $query2.'<BR>';
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		$so_item_id = qid();

		// add notes from quote to prices table
		setNotes($partid,$r['notes'],$created_by,$date);

		$freight = 'NULL';
		if ($r['freight_charge']>0) {
			$freight = prep($r['freight_charge']);
		}

		// add package data, only once per order (so only the first time for this 'quote_id', per IF condition above)
		if (! isset($packages['SO'.$r['quote_id']])) { $packages['SO'.$r['quote_id']] = array(); }
		$query2 = "SELECT si.id, si.tracking_no, si.date, s.serials, s.inventory_id, si.freight_cogs ";//, solditem.rep_exp_date ";
		$query2 .= "FROM inventory_outgoing_quote_invoice oqi, inventory_shipping s, inventory_salesinvoice si ";
//		$query2 .= "LEFT JOIN inventory_solditem solditem ON solditem.so_id = si.so_id ";
		$query2 .= "WHERE si.so_id = $so AND si.id = oqi.sales_invoice_id AND oqi.id = s.oqi_id AND oqi.oq_id = '".$r['oqid']."'; ";
//		$query2 .= "AND (solditem.inventory_id = s.inventory_id); ";// OR solditem.inventory_id IS NULL); ";//AND solditem.so_id = si.so_id; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$packages['SO'.$r['quote_id']][$r2['id']] = true;//group packages on sales invoice id
			$n = prep(count($packages['SO'.$r['quote_id']]));

			$query3 = "SELECT id FROM packages WHERE order_number = $so_number AND package_no = $n; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)>0) {
				$r3 = mysqli_fetch_assoc($result3);
				$pkgid = $r3['id'];
			} else {
				$query3 = "REPLACE packages (weight, length, width, height, order_number, package_no, ";
				$query3 .= "tracking_no, datetime, freight_amount) ";
				$query3 .= "VALUES (NULL, NULL, NULL, NULL, $so_number, $n, ";
				$query3 .= "'".$r2['tracking_no']."', '".$r2['date']." 16:00:00', $freight); ";
echo $query3.'<BR>';
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				$pkgid = qid();

				// apply all freight on one package, then reset for all ensuing packages
				$freight = 'NULL';
			}

			$serials = explode(chr(10),$r2['serials']);
			$qty_shipped += count($serials);
			foreach ($serials as $serial) {
				$serial = trim($serial);
				if (! $serial) { continue; }

				$ser = prep($serial);

				// get stock date based on rep exp date
				$stock_date = $r['so_date'];
				$query3 = "SELECT rep_exp_date FROM inventory_solditem WHERE serial = $ser ";
				$query3 .= "AND inventory_id = '".$r2['inventory_id']."' AND so_id = '".$r['quote_id']."' ";
				$query3 .= "AND rep_exp_date IS NOT NULL AND rep_exp_date <> ''; ";
				$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					if ($r3['rep_exp_date']) {
						$stock_date = $r3['rep_exp_date'];
					}
				}

				// create new order of serials if '000' because Brian re-used this generic serial not just in
				// one or two duplicate times, but EVERY time, resulting in duplicate nonsense garbage that
				// we can't have in the new system
				if ($serial=='000') { $serial = 'VTL'.($bogus_serial++); }
				$ser = prep($serial);//re-prep after new bogus serial has been generated

				// check new inventory system for existing serial, and if already added during PO's above,
				// just set `last_sale` on the existing record
				$query3 = "SELECT id FROM inventory WHERE serial_no = $ser AND partid = $partid; ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					$serialid = $r3['id'];

					$query3 = "UPDATE inventory SET last_sale = $so_number, qty = 0 WHERE id = $serialid; ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

					$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date." 10:00:00' ";
					$query3 .= "WHERE invid = $serialid AND field_changed = 'last_sale' AND value = $so_number; ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);

/*
					// get last updated record from inventory_history to update with the record's date, not current timestamp
					$query3 = "SELECT changed_from, date_changed FROM inventory_history ";
					$query3 .= "WHERE invid = $serialid AND field_changed = 'last_sale' ";
					$query3 .= "ORDER BY date_changed DESC; ";
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					if (mysqli_num_rows($result3)>0) {
						$r3 = mysqli_fetch_assoc($result3);
						$changed_from = $r3['changed_from'];
						$date_changed = $r3['date_changed'];

						$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date." 10:00:00' ";
						$query3 .= "WHERE invid = $serialid AND field_changed = 'last_sale' ";
						$query3 .= "AND date_changed = '".$date_changed."' AND changed_from = '".$changed_from."'; ";
						$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					}
*/
				} else {
					$query3 = "REPLACE inventory (serial_no, qty, partid, item_condition, status, locationid, ";
					$query3 .= "last_purchase, last_sale, last_return, userid, date_created, notes) ";
					$query3 .= "VALUES ($ser, 0, $partid, 'used', 'manifest', 1, ";
					$query3 .= "NULL, $so_number, NULL, 0, '".$stock_date." 12:00:00', NULL); ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
					$serialid = qid();

					$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date." 10:00:00' ";
					$query3 .= "WHERE invid = $serialid; ";
echo $query3.'<BR>';
					$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
				}
//see SO 13081 for major issue to resolve on serializing

				$query3 = "REPLACE package_contents (packageid, serialid) VALUES ($pkgid, $serialid); ";
echo $query3.'<BR>';
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			}
		}

		// finished shipping all serials (qtys), compare now with the SO qty and if not the same,
		// update the sales_items with the correct qty_shipped
		if ($qty_shipped<>$qty) {
			$query2 = "UPDATE sales_items SET qty_shipped = $qty_shipped WHERE id = $so_item_id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		}

		// ISO checklist


echo '<BR>';

	}
?>
