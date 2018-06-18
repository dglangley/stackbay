<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

	if (! isset($now)) { $now = date("Y-m-d H:i:s"); }
	if (! isset($SUPER_ADMIN)) { $SUPER_ADMIN = false; }

	function insertMarket($partid,$list_qty,$list_price=false,$response_qty=false,$response_price=false,$metaid,$type='availability',$searchid=false,$ln=0,$leadtime=false,$leadtime_span=false,$profit_pct=false) {
		if (! $partid) { return false; }

		if (! $list_qty) { $list_qty = 1; }
		$itemid = 0;
		$query = "SELECT id FROM ".$type." WHERE partid = '".$partid."' AND ";
		if ($type=='service_bom') { $query .= "item_id_label = 'service_item_id' AND item_id "; }
		else { $query .= "line_number = '".($ln+1)."' AND metaid = '".$metaid."' AND searchid "; }
		if ($searchid) { $query .= "= '".$searchid."' "; } else { $query .= "IS NULL "; }
//		$query .= "AND line_number = '".($ln+1)."'; ";
		$query .= "; ";
		$result = qedb($query);
		if (mysqli_num_rows($result)==1) {
			$r = mysqli_fetch_assoc($result);
			$itemid = $r['id'];
		}

		if (($leadtime===false OR $leadtime=='') AND $leadtime_span) {
			$leadtime = false;
			$leadtime_span = false;
		}

		// deliver results to table associated with the type of record this: supply (availability) or demand (request)
		if ($type=='demand') {
			$q1 = 'request_qty';
			$p1 = 'request_price';
			$q2 = 'quote_qty';
			$p2 = 'quote_price';
		} else if ($type=='availability') {
			$q1 = 'avail_qty';
			$p1 = 'avail_price';
			$q2 = 'offer_qty';
			$p2 = 'offer_price';
		} else if ($type=='repair_quotes') {
			$q1 = 'qty';
			$p1 = 'price';
			$q2 = '';
			$p2 = '';
			if ($response_price AND ! $list_price) { $list_price = $response_price; }
		} else if ($type=='service_bom') {
			$q1 = 'qty';
			$p1 = 'amount';
			$q2 = '';
			$p2 = 'charge';
//			if ($response_qty) { $list_qty = $response_qty; }
			if ($response_price AND ! $list_price) { $list_price = $response_price; }
			$response_price = $response_price*$response_qty;
		}

		$query = "REPLACE ".$type." (partid, ".$q1.", ".$p1.", ";
		if ($leadtime!==false AND $leadtime_span) { $query .= "leadtime, leadtime_span, "; }
		if ($profit_pct) { $query .= "profit_pct, "; }
		if ($q2) { $query .= $q2.", "; }
		if ($p2) { $query .= $p2.", "; }
		if ($type=='service_bom') {
			$query .= "item_id_label, item_id";
		} else {
			$query .= "metaid, line_number, searchid";
		}
		if ($itemid) { $query .= ", id"; }
		$query .= ") VALUES ('".$partid."','".$list_qty."',";
		if ($list_price AND $list_price<>'0.00') { $query .= "'".$list_price."',"; } else { $query .= "NULL,"; }
		if ($leadtime!==false AND $leadtime_span) { $query .= "'".res($leadtime)."', '".res($leadtime_span)."', "; }
		if ($profit_pct) { $query .= "'".res($profit_pct)."', "; }
		if ($q2) {
			if ($response_qty) { $query .= "'".$response_qty."',"; } else { $query .= "NULL,"; }
		}
		if ($p2) {
			if ($response_qty>0 AND $response_price>0) { $query .= "'".$response_price."',"; } else { $query .= "NULL,"; }
		}
		if ($type=='service_bom') {
			$query .= "'service_item_id',";
		} else {
			$query .= "'".$metaid."',";
			$query .= "'".($ln+1)."',";//always save it incremented by one since it's initialized in array starting at 0
		}
		if ($searchid) { $query .= "'".$searchid."'"; } else { $query .= "NULL"; }
		if ($itemid) { $query .= ",'".$itemid."'"; }
		$query .= "); ";
		$result = qedb($query);
	}

	function insertMarket2($partid,$qty,$companyid,$date,$source,$price=false,$partstr='') {
		if (! $partid OR ! $companyid) { return; }
		if (! $date) { $date = $GLOBALS['now']; }
		if ($qty>=9999 OR strstr($partstr,$qty)) { $qty = 1; }//fixes results such as in tel-explorer that gets Telmar's qty wrong

		// get today's marketid already stored, if any; also try to get previous price for this company
		$marketid = 0;
		$query2 = "SELECT * FROM market WHERE partid = '".res($partid)."' AND companyid = '".res($companyid)."' ";
		$query2 .= "AND (datetime LIKE '".res($date)."%' OR price > 0) ";
		if ($source) { $query2 .= "AND source = '".res($source)."' "; }
		$query2 .= "ORDER BY datetime DESC; ";
//		echo $query2.chr(10);
		$result2 = qedb($query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			if (substr($r2['datetime'],0,10)==substr($date,0,10)) {
				$marketid = $r2['id'];
				// if on the same list, sum qtys (see TSS)
				if (is_numeric($source)) { $qty += $r2['qty']; }
//				if ($price>0) { break; }
			}
			if (! $price) { $price = $r2['price']; }
		}

		$query2 = "REPLACE market (partid, qty, companyid, datetime, price, source";
		if ($marketid) { $query2 .= ", id"; }
		$query2 .= ") VALUES ('".res($partid)."','".res($qty)."','".res($companyid)."',";
		$query2 .= "'".res($date)."',";
		if ($price) { $query2 .= "'".res($price)."',"; } else { $query2 .= "NULL,"; }
		$query2 .= "'".res($source)."'";
		if ($marketid) { $query2 .= ",'".res($marketid)."'"; }
		$query2 .= "); ";
//echo $query2.'<BR>'; return true;
		$result2 = qedb($query2);

		return (true);
	}
?>
