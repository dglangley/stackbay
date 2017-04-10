<?php
	set_time_limit(0);
	ini_set('memory_limit', '2000M');

	if (! isset($_SERVER["RDS_HOSTNAME"])) {
		$_SERVER["RDS_HOSTNAME"] = 'localhost';
		$_SERVER["RDS_USERNAME"] = 'root';
		$_SERVER["RDS_PASSWORD"] = 'langer';
		$_SERVER["RDS_PORT"] = '3306';
		$_SERVER["DEFAULT_DB"] = 'vmmdb';
	}
	if (! isset($_SERVER["ROOT_DIR"])) {
		$_SERVER["ROOT_DIR"] = '/Users/Shared/WebServer/Sites/marketmanager';
	}
	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/setPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcRepairCost.php';
	include_once $rootdir.'/inc/form_handle.php';

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
echo $query2.'<BR>'.chr(10);
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		}

		return true;
	}
	function iso($id) {
		if ($id==1) { return 'yes'; }
		else if ($id==2) { return 'no'; }
		else if ($id==3) { return 'n/a'; }
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
		$cid = prep($companyid);

		$contactid = 0;
		$query2 = "SELECT * FROM contacts WHERE name = '".res($name)."' AND companyid = $cid; ";
		$result2 = qdb($query2) OR die(qe().' '.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
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
			$query2 .= "VALUES ($name, $title, $notes, NULL, '".$status."', $cid); ";
echo $query2.'<BR>'.chr(10);
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


	$DISPOSITIONS = array(
		3=>1,/*credit*/
		4=>1,/*credit and refund*/
		1=>2,/*replace/exchange*/
		2=>3,/*repair*/
		5=>0,/*tbd*/
	);
	$COUNTRY_MAPS = array(
		'' => 'US',
		'USA' => 'US',
		'Brazil' => 'BR',
		'Canada' => 'CA',
		'China' => 'CN',
		'Guam' => 'GU',
		'Israel' => 'IL',
		'Isreal' => 'IL',
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

	$SERIALS = array();
	$ITEMS = array();
	$bogus_serial = 100001;
	function setSerial($serial,$order_type='',$order=0,$id=0) {
		global $bogus_serial,$SERIALS;

		// create new order of serials if '000' because Brian re-used this generic serial not just in
		// one or two duplicate times, but EVERY time, resulting in duplicate nonsense garbage that
		// we can't have in the new system
		if ($serial=='000') {
			if ($order_type AND isset($SERIALS[$order_type][$order]) AND $id AND isset($SERIALS[$order_type][$order][$id])) {
echo 'Re-using serial '.$serial.' for '.$order_type.' order# '.$order.', item id '.$id.' = ';
				$serial = $SERIALS[$order_type][$order][$id];
echo '"'.$serial.'"<BR>';
			} else {
				$serial = 'VTL'.($bogus_serial++);
				if ($order_type AND $id) {
					if (! isset($SERIALS[$order_type])) { $SERIALS[$order_type] = array(); }
					if (! isset($SERIALS[$order_type][$order])) { $SERIALS[$order_type][$order] = array(); }
					$SERIALS[$order_type][$order][$id] = $serial;
				}
			}
		}
		return ($serial);
	}

	function setPackage($order_num,$n,&$freight,$trk_no='',$date='') {
		$order = prep($order_num);
		$n = prep($n);
		$trk = prep(trim($trk_no));
		$date = prep($date);

		$query3 = "SELECT id FROM packages WHERE order_number = $order AND package_no = $n; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$pkgid = $r3['id'];
		} else {
			$freight = prep($freight);

			$query3 = "REPLACE packages (weight, length, width, height, order_number, package_no, ";
			$query3 .= "tracking_no, datetime, freight_amount) ";
			$query3 .= "VALUES (NULL, NULL, NULL, NULL, $order, $n, ";
			$query3 .= "$trk, $date, $freight); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
			$pkgid = qid();

			// apply all freight on one package, then reset for all ensuing packages
			$freight = '';
		}

		return ($pkgid);
	}

	$new_po = array();
	function importPurchase($r) {
		global $new_po,$packages,$SERVICE_MAPS,$CARRIER_MAPS,$WARRANTY_MAPS,$TERMS_MAPS,$SERIALS;

		$companyid = dbTranslate($r['company_id']);
		$cid = prep($companyid);
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
			$query2 .= "VALUES ($po, $date, $created_by, $created_by, $cid, $contactid, ";
			$query2 .= "NULL, $remit_to_id, $ship_to_id, $freight_carrier_id, $freight_services_id, ";
			$query2 .= "$freight_account_id, $termsid, $public_notes, $private_notes, '".$status."'); ";
echo $query2.'<BR>'.chr(10);
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
/*
		if ($r['ref_no']) {
			$ref1 = prep($r['ref_no']);
			$ref1_label = prep('ref');
		}
*/
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
		$query2 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, conditionid) ";
		$query2 .= "VALUES ($partid, $po_number, $ln, $qty, $qty, $price, $receive_date, ";
		$query2 .= "$ref1, $ref1_label, $ref2, $ref2_label, $warranty, 2); ";
echo $query2.'<BR>'.chr(10);
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		$po_item_id = qid();

		// add notes from quote to prices table
		setNotes($partid,$r['notes'],$created_by,$date);

		// add package data, only once per order (so only the first time for this 'quote_id', per IF condition above)
		if (! isset($packages['PO'.$r['quote_id']])) { $packages['PO'.$r['quote_id']] = array(); }
		$query2 = "SELECT s.tracking_no, s.date, pi.freight_cogs, pi.id, s.serials, replace(s.note,pi.po_id,'') note ";
		$query2 .= "FROM inventory_incoming_quote_invoice iqi, inventory_purchaseinvoice pi, inventory_shipping s ";
		$query2 .= "WHERE pi.po_id = $po AND iqi.purchase_invoice_id = pi.id AND iqi.id = s.iqi_id; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
//			$note = trim(preg_replace('/^'.$r['quote_id'].'/','',$r2['note']));
			$note = trim($r2['note']);

			$packages['PO'.$r['quote_id']][$r2['id']] = true;//group packages on purchase invoice id
			$n = count($packages['PO'.$r['quote_id']]);

			$freight = '';
			if ($r2['freight_cogs']>0) { $freight = $r2['freight_cogs']; }
			$pkgid = setPackage($new_po[$r['quote_id']],$n,$freight,$r2['tracking_no'],$r2['date'].' 10:00:00');

			$serials = explode(chr(10),$r2['serials']);
			$recd_qty += count($serials);
			foreach ($serials as $serial) {
				$serial = strtoupper(trim($serial));
				if (! $serial) { continue; }

				// get stock date based on rep exp date
				$stock_date = $r2['date'];

				// validates serial format and sequencing
				$serial = setSerial($serial,'purchase',$r['quote_id'],$r['iqid']);
				$ser = prep($serial);//re-prep after new bogus serial has been generated

				$serialid = setInventory($ser,$partid,$po_item_id,'purchase_item_id','shelved',$stock_date." 10:00:00",1);

				$query3 = "REPLACE package_contents (packageid, serialid) VALUES ($pkgid, $serialid); ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
			}
		}

		// finished receiving all serials (qtys), compare now with the PO qty and if not the same,
		// update the purchase_items with the correct qty_received
		if ($recd_qty<>$qty) {
			$query2 = "UPDATE purchase_items SET qty_received = $recd_qty WHERE id = $po_item_id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
		}
	}

	$new_so = array();
	function importSale($r) {
		global $new_so,$packages,$ITEMS,$SERVICE_MAPS,$CARRIER_MAPS,$WARRANTY_MAPS,$TERMS_MAPS,$SERIALS;

		$companyid = dbTranslate($r['company_id']);
		$cid = prep($companyid);
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
			$query2 .= "VALUES ($so, $date, $created_by, $created_by, $cid, $contactid, ";
			$query2 .= "$po_number, $po_file, $bill_to_id, $ship_to_id, $freight_carrier_id, $freight_services_id, ";
			$query2 .= "$freight_account_id, $termsid, $public_notes, $private_notes, '".$status."'); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
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
		$query2 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, conditionid) ";
		$query2 .= "VALUES ($partid, $so_number, $ln, $qty, $qty, $price, $delivery_date, $ship_date, ";
		$query2 .= "$ref1, $ref1_label, $ref2, $ref2_label, $warranty, 2); ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
		$so_item_id = qid();

		// add notes from quote to prices table
		setNotes($partid,$r['notes'],$created_by,$date);

		$freight = '';
		if ($r['freight_charge']>0) {
			$freight = $r['freight_charge'];
		}

		// add package data, only once per order (so only the first time for this 'quote_id')
		if (! isset($packages['SO'.$r['quote_id']])) { $packages['SO'.$r['quote_id']] = array(); }
		$query2 = "SELECT si.id, si.tracking_no, si.date, s.serials, s.inventory_id, si.freight_cogs ";
		$query2 .= "FROM inventory_outgoing_quote_invoice oqi, inventory_shipping s, inventory_salesinvoice si ";
		$query2 .= "WHERE si.so_id = $so AND si.id = oqi.sales_invoice_id AND oqi.id = s.oqi_id AND oqi.oq_id = '".$r['oqid']."'; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$packages['SO'.$r['quote_id']][$r2['id']] = true;//group packages on sales invoice id
			$n = count($packages['SO'.$r['quote_id']]);

			$pkgid = setPackage($r['quote_id'],$n,$freight,$r2['tracking_no'],$r2['date'].' 16:00:00');

			$used_items = array();//prevent usage of duplicates
			$serials = explode(chr(10),$r2['serials']);
//			$qty_shipped += count($serials);
			foreach ($serials as $serial) {
				$serial = strtoupper(trim($serial));
				if (! $serial) { continue; }
				// count serial as long as it's not blank (some records have empty rows, thanks brian)
				$qty_shipped++;

				$ser = prep($serial);

				// get stock date based on rep exp date
				$stock_date = $r['so_date'];
				$cost = 0;
				$avg_cost = 0;
				$itemid = 0;
				// this will be previously-set if serial is generic like '000'
				if (! isset($used_items[$serial])) { $used_items[$serial] = ''; }

				$query3 = "SELECT rep_exp_date, cost, avg_cost, po, iq_id, id FROM inventory_solditem ";
				$query3 .= "WHERE serial = $ser ";
				if ($used_items[$serial]<>'') { $query3 .= "AND id NOT IN (".$used_items[$serial].") "; }
				//3-31-17
				//$query3 .= "AND inventory_id = '".$r2['inventory_id']."' AND so_id = '".$r['quote_id']."' ";
				$query3 .= "AND so_id = '".$r['quote_id']."' ";
				//$query3 .= "AND rep_exp_date IS NOT NULL AND rep_exp_date <> ''; ";
				$query3 .= "ORDER BY IF (inventory_id = '".$r2['inventory_id']."',0,1); ";
				$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
				if (mysqli_num_rows($result3)>0) {
					$r3 = mysqli_fetch_assoc($result3);
					if ($r3['rep_exp_date']) {
						$stock_date = $r3['rep_exp_date'];
					}
					if ($r3['avg_cost']>0) { $avg_cost = $r3['avg_cost']; }
					if ($r3['cost']>0) { $cost = $r3['cost']; }
					$itemid = $r3['id'];
					if ($used_items[$serial]) { $used_items[$serial] .= ','; }
					$used_items[$serial] .= $r3['id'];

					if ($serial=='000') {
						// if this non-descript serial has a PO and if we can determine its iq_id source, then
						// get the serial from our previously-stored SERIALS
						if ($r3['po'] AND isset($SERIALS['purchase']) AND isset($SERIALS['purchase'][$r3['po']]) AND isset($SERIALS['purchase'][$r3['po']][$r['iq_id']])) {
							$serial = $SERIALS['purchase'][$r3['po']][$r['iq_id']];
						}
					}
				}

				// validates serial format and sequencing
				$serial = setSerial($serial,'sale',$r['quote_id'],$itemid);//$r['oqid']);
				$ser = prep($serial);//re-prep after new bogus serial has been generated

				$serialid = setInventory($ser,$partid,$so_item_id,'sales_item_id','manifest',$stock_date." 10:00:00",0);
				$ITEMS[$serialid] = $so_item_id;

				$query3 = "REPLACE inventory_costs (inventoryid, datetime, actual, average, notes) ";
				$query3 .= "VALUES ($serialid, '".$stock_date." 10:00:00', '".$cost."', '".$avg_cost."', 'Imported'); ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);

				$query3 = "REPLACE package_contents (packageid, serialid) VALUES ($pkgid, $serialid); ";
				$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
			}
		}

		// finished shipping all serials (qtys), compare now with the SO qty and if not the same,
		// update the sales_items with the correct qty_shipped
		if ($qty_shipped<>$qty) {
			$query2 = "UPDATE sales_items SET qty_shipped = $qty_shipped WHERE id = $so_item_id; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
		}

		// ISO checklist
		if ($r['iso_pn_match_po_id'] OR $r['iso_heci_match_po_id'] OR $r['iso_cosm_damage_id'] OR $r['iso_comp_damage_id']
			OR $r['iso_po_special_req_id'] OR $r['iso_ship_correct_id'] OR $r['iso_transit_req_id']) {
			$query2 = "SELECT * FROM iso WHERE so_number = $so_number; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			if (mysqli_num_rows($result2)==0) {
				$pn_check = $r['iso_pn_match_po_id'];
				$heci_check = $r['iso_heci_match_po_id'];
				$cosmetic_check = $r['iso_cosm_damage_id'];
				$component_check = $r['iso_comp_damage_id'];
				$specialreq_check = $r['iso_po_special_req_id'];
				$ship_check = $r['iso_ship_correct_id'];
				$transit_check = $r['iso_transit_req_id'];

				$query2 = "REPLACE iso (part, heci, cosmetic, component, special_req, shipping_info, transit_time, so_number) ";
				$query2 .= "VALUES (".prep(iso($pn_check)).",".prep(iso($heci_check)).",".prep(iso($cosmetic_check)).",".prep(iso($component_check)).",";
				$query2 .= prep(iso($specialreq_check)).",".prep(iso($ship_check)).",".prep(iso($transit_check)).",$so_number); ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
			}
		}

echo '<BR>'.chr(10);
	}

	$new_rma = array();
	function importReturn($r) {
		global $new_rma,$packages,$bogus_serial,$ITEMS,$SERVICE_MAPS,$CARRIER_MAPS,$WARRANTY_MAPS,$TERMS_MAPS,$SERIALS,$DISPOSITIONS;

//		print "<pre>".print_r($r,true)."</pre>";

		$companyid = dbTranslate($r['company_id']);
		$cid = prep($companyid);
		$rma = prep($r['rma']);
		$date = prep($r['date'].' 12:00:00');//append noon just cuz it's better than impractical 00:00:00
		$status = prep('Active');
		//$contactid = prep(mapContact($r['contact_id'],$companyid));
		$created_by = prep(mapUser($r['created_by_id']));
		//$po_number = prep($r['po_number']);
		$serial = strtoupper($r['serial']);

		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		$partid = getPartId($r['part_number'],$r['heci']);
		if (! $partid) {
			$partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['description']));
		}

		// for RTV?
		$vendor_file = prep($r['vendordoc']);
		$notes = prep($r['return_to']);

		if (! isset($new_rma[$r['rma']])) {
			$query2 = "REPLACE returns (rma_number, created, created_by, companyid, order_number, order_type, contactid, notes, status) ";
			$query2 .= "VALUES ($rma, $date, $created_by, $cid, NULL, NULL, NULL, $notes, $status); ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
		}

		// if serial is already set by the SO serial auto-generation process, use that serial now; otherwise, create our own
		if ($serial=='000' AND isset($SERIALS['sale']) AND isset($SERIALS['sale'][$r['so_id']]) AND isset($SERIALS['sale'][$r['so_id']][$r['solditem_id']])) {
			$serial = $SERIALS['sale'][$r['so_id']][$r['solditem_id']];
echo 'Found existing serial '.$serial.' for "000" on SO '.$r['so_id'].' for item id '.$r['solditem_id'].'<BR>'.chr(10);
		} else {
			$serial = setSerial($serial);
		}
		$ser = prep($serial);//re-prep after new bogus serial has been generated

		$query2 = "SELECT id FROM inventory WHERE serial_no = $ser AND partid = $partid; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)==0) {
			// if the serial doesn't already exist from a purchase, it's likely a '000' that was audited in by Brian,
			// so add it to stock 
			$serialid = setInventory($ser,$partid,false,false,'shelved',$r['date']." 12:00:00",1);

			// now sell it using the SO OQ ID info, to prep it for being re-shelved on RMA below
			if ($r['so_id'] AND $r['oq_id']) {
				$serialid = setInventory($ser,$partid,$r['oq_id'],'sales_item_id','manifest',$r['date']." 12:00:00",0);
			} else {
//repair scenario
			}
		} else {
			$r2 = mysqli_fetch_assoc($result2);
			$serialid = $r2['id'];
		}

		$dispositionid = $DISPOSITIONS[$r['action_id']];
		$query2 = "REPLACE return_items (partid, inventoryid, rma_number, line_number, reason, dispositionid, qty) ";
		$query2 .= "VALUES ($partid, $serialid, $rma, NULL, ".prep($r['reason']).", $dispositionid, 1); ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
echo $query2.'<BR>'.chr(10);
		$rma_item_id = qid();

		$serialid = setInventory($ser,$partid,$rma_item_id,'returns_item_id','shelved',$r['date']." 12:00:00",1);
/* replaced this with ==0 code above
		} else {
echo 'Missing serialid for serial "'.$serial.'":<BR>'.$query2.'<BR>'.chr(10);
exit;
		}
*/

		// unit was replaced if there is a new_item_id, so all code below is to handle a replacement order
		if (! $r['new_item_id']) { return; }

		// REPLACEMENT ORDER
		$query2 = "SELECT serial, part_number, clei, heci, m.name, i.short_description description, si.cost actual_cost, si.avg_cost ";
		$query2 .= "FROM inventory_solditem si, inventory_inventory i, inventory_manufacturer m ";
		$query2 .= "WHERE si.id = '".$r['new_item_id']."' AND si.inventory_id = i.id AND i.manufacturer_id_id = m.id; ";
		$result2 = qdb($query2,'PIPE') OR die(qe('PIPE').'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);

			if ($r2['clei']) { $r2['heci'] = $r2['clei']; }
			else if (strlen($r2['heci'])<>7 OR is_numeric($r2['heci']) OR preg_match('/[^[:alnum:]]+/',$r2['heci'])) { $r2['heci'] = ''; }
			else { $r2['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

			$partid = getPartId($r2['part_number'],$r2['heci']);
			if (! $partid) {
				$partid = setPart(array('part'=>$r2['part_number'],'heci'=>$r2['heci'],'manf'=>$r2['manf'],'descr'=>$r2['description']));
			}

			$warranty = 'NULL';//$WARRANTY_MAPS[$r2['warranty_period_id']];
			$orig_so = prep($r['so_id']);
			if ($r['shipped_date']) { $ship_date = $r['shipped_date']; }
			else { $ship_date = $r['date']; }

			// add a new sales item record to original sales order, but as a replacement line item to the original
			$query3 = "REPLACE sales_items (partid, so_number, line_number, qty, qty_shipped, price, delivery_date, ship_date, ";
			$query3 .= "ref_1, ref_1_label, ref_2, ref_2_label, warranty, conditionid) ";
			$query3 .= "VALUES ($partid, $orig_so, NULL, 1, 1, '0.00', '".$ship_date."', '".$ship_date."', ";
			$query3 .= "NULL, NULL, '".$ITEMS[$serialid]."', 'sales_item_id', $warranty, 2); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
			$so_item_id = qid();

			// validates serial format and sequencing
			$repl_serial = setSerial($r2['serial'],'sale',$r['so_id'],$r['new_item_id']);//$r['oqid']);
			$ser = prep($repl_serial);//re-prep after new bogus serial has been generated

			$serialid = setInventory($ser,$partid,$so_item_id,'sales_item_id','manifest',$r['date']." 16:00:00",0);

			// get count of boxes on this sales order, and increment by 1 because this will have been shipped in a new shipment
			$query3 = "SELECT id FROM packages WHERE order_number = $orig_so; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			$n = mysqli_num_rows($result3)+1;

			$freight = '';
			$pkgid = setPackage($r['so_id'],$n,$freight,$r['outbound_tracking_no'],$ship_date.' 16:00:00');

			$query3 = "REPLACE inventory_costs (inventoryid, datetime, actual, average, notes) ";
			$query3 .= "VALUES ($serialid, '".$r['date']." 10:00:00', '".$r2['actual_cost']."', '".$r2['avg_cost']."', 'Imported'); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);

			$query3 = "REPLACE package_contents (packageid, serialid) VALUES ($pkgid, $serialid); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
		}
echo '<BR>'.chr(10);
	}

	function setInventory($ser,$partid,$item_id,$id_field,$status,$stock_date,$qty=false) {
		$status = prep($status);

		// check new inventory system for existing serial, and if already added during PO's above,
		// just set `sales_item_id` on the existing record
		$query3 = "SELECT id FROM inventory WHERE serial_no = $ser AND partid = $partid; ";
		$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			$serialid = $r3['id'];

			//$query3 = "UPDATE inventory SET sales_item_id = $so_item_id, qty = 0 WHERE id = $serialid; ";
			$query3 = "UPDATE inventory SET $id_field = $item_id, status = $status ";
			if ($qty!==false) { $query3 .= ", qty = $qty "; }
			$query3 .= "WHERE id = $serialid; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);

			$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date."' ";
			$query3 .= "WHERE invid = $serialid AND field_changed = '".$id_field."' AND value = $item_id; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
		} else {
			$query3 = "REPLACE inventory (serial_no, qty, partid, conditionid, status, locationid, ";
			$query3 .= "purchase_item_id, sales_item_id, returns_item_id, userid, date_created, notes) ";
			$query3 .= "VALUES ($ser, $qty, $partid, 2, $status, 1, ";
			//$query3 .= "NULL, $so_item_id, NULL, 0, '".$stock_date."', NULL); ";
			if ($id_field=="purchase_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
			if ($id_field=="sales_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
			if ($id_field=="returns_item_id") { $query3 .= "$item_id, "; } else { $query3 .= "NULL, "; }
			$query3 .= "0, '".$stock_date."', NULL); ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
			$serialid = qid();

			$query3 = "UPDATE inventory_history SET date_changed = '".$stock_date."' ";
			$query3 .= "WHERE invid = $serialid; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
echo $query3.'<BR>'.chr(10);
		}
		return ($serialid);
	}

	$query = "DELETE FROM purchase_orders; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM purchase_items; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM sales_orders; ";
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
	$query = "DELETE FROM inventory_costs; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM returns; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM return_items; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM iso; ";
	$result = qdb($query) OR die(qe().' '.$query);
/*
	$query = "DELETE FROM sales_orders WHERE so_number = 15559; ";
	$result = qdb($query) OR die(qe().' '.$query);
	$query = "DELETE FROM sales_items WHERE so_number = 15559; ";
	$result = qdb($query) OR die(qe().' '.$query);
*/

	$purchStart = '2012-01-01';
	$startDate = '2012-01-01';
	 = '2018-01-15';
	$packages = array();

	/*************************************/
	/**** run company_maps.php first! ****/
	/*************************************/

	$records = array();
	$query = "SELECT po.po_date, po.bill_from_id, po.po_terms_id, po.delivery_due, po.drop_ship_to, po.freight_carrier_id, ";
	$query .= "po.memo, po.freight_charge, po.canceled_by_id, po.ext_memo, po.freight_charge_billed, ";//po.contact_id,
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
echo $query.'<BR>'.chr(10);
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$records[$r['po_date']][] = array('type'=>'purchase','data'=>$r);
	}

	$query = "SELECT oq.company_id, oq.quote_id, oq.id oqid, so.so_date, canceled_by_id, oq.contact_id, oq.creator_id, ";
	$query .= "so.po_number, so.po_file, so.ext_memo, so.memo, so.bill_to_id, so.ship_to_id, oq.notes, ";
	$query .= "freight_carrier_id, so_terms_id, freight_notes, oq.inventory_id, i.part_number, i.clei, i.heci, ";
	$query .= "i.short_description description, m.name manf, so.ship_to_override, oq.clei_override, ";
	$query .= "oq.line_number, oq.quantity, oq.price, so.delivery_date, oq.ref_no, so.freight_charge, ";
	$query .= "so.iso_pn_match_po_id, iso_heci_match_po_id, iso_cosm_damage_id, iso_comp_damage_id, ";
	$query .= "iso_po_special_req_id, iso_ship_correct_id, iso_transit_req_id ";
	$query .= "FROM inventory_salesorder so, inventory_outgoing_quote oq, inventory_company c, inventory_inventory i, inventory_manufacturer m ";
	$query .= "WHERE so.quote_ptr_id = oq.quote_id AND oq.company_id = c.id AND oq.inventory_id = i.id AND m.id = i.manufacturer_id_id ";
//$query .= "AND oq.quote_id = 15559 ";
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d');
		$dbEndDate = format_date($endDate, 'Y-m-d');
		$query .= "AND so.so_date between CAST('".$dbStartDate."' AS DATE) AND CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY so.so_date ASC; ";
echo $query.'<BR>'.chr(10);
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$records[$r['so_date']][] = array('type'=>'sale','data'=>$r);
	}
//DAVID: Also need to import so_id IS NULL from sold items

	$query = "SELECT tm.id rma, tm.company_id, t.item_id solditem_id, t.new_item_id, t.date, t.repair_id, t.status_id, ";
	$query .= "t.action_id, t.received_date, t.vendor_shipped_date, t.vendorout_tracking_no, t.shipped_date, ";
	$query .= "t.outbound_tracking_no, t.disposition_id, t.reason, t.vendor_id, t.vendor_ship_to, t.vendor_return, ";
	$query .= "t.vendor_rma, t.vendor_orig_po, t.cm_id, t.return_to, t.vendordoc, t.vendor_ship_acct, t.created_by_id, ";
	$query .= "si.serial, si.so_id, si.iq_id, si.oq_id, i.part_number, i.clei, i.heci, m.name manf, i.short_description description ";
	$query .= "FROM inventory_rmaticket t, inventory_rmaticketmaster tm, inventory_solditem si, inventory_inventory i, ";
	$query .= "inventory_manufacturer m ";
	$query .= "WHERE t.master_id = tm.id AND si.id = t.item_id AND si.inventory_id = i.id AND m.id = i.manufacturer_id_id ";
	if ($startDate) {
		$dbStartDate = format_date($startDate, 'Y-m-d');
		$dbEndDate = format_date($endDate, 'Y-m-d');
		$query .= "AND t.date between CAST('".$dbStartDate."' AS DATE) AND '2015-05-31' ";//CAST('".$dbEndDate."' AS DATE) ";
	}
	$query .= "ORDER BY t.date ASC; ";
echo $query.'<BR>'.chr(10);
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$records[$r['date']][] = array('type'=>'return','data'=>$r);
	}

	// sort by date ascending
	ksort($records);
	//print "<pre>".print_r($records,true)."</pre>";
	foreach ($records as $date => $rows) {
		//echo $date.':<br>';
		foreach ($rows as $row) {
			$r = $row['data'];
			//print "<pre>".print_r($r,true)."</pre>";

			switch ($row['type']) {
				case 'purchase':
					importPurchase($r);
					break;

				case 'sale':
					importSale($r);
					break;

				case 'return':
					importReturn($r);
					break;
			}
		}
	}
?>
