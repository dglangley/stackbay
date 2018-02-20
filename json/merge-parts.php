<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/indexer.php';
	header("Content-Type: application/json", true);

	function reportError($err) {
		echo json_encode(array('message'=>$err));
		exit;
	}

	$partids = array();
	if (isset($_REQUEST['partids'])) {
		if (is_array($_REQUEST['partids'])) {
			$partids = $_REQUEST['partids'];
		} else {
			$partids = explode(",",$_REQUEST['partids']);
		}
	}

	// we're going to attempt to determine the "master" fields by filling in gaps
	// between the two records
	$parts = array();
	$hecis = array();
	$descrs = array();
	$systemids = array();
	$manfids = array();
	foreach ($partids as $partid) {
		$query = "SELECT * FROM parts WHERE id = '".res($partid)."'; ";
		$result = qdb($query) OR reportError(qe().' '.$query);
		// confirm part existence
		if (mysqli_num_rows($result)<>1) {
			reportError("Could not find part in db");
		}
		while ($r = mysqli_fetch_assoc($result)) {
			$parts[$partid] = trim($r['part']);
			$hecis[$partid] = trim($r['heci']);
			$descrs[$partid] = trim($r['description']);
			$systemids[$partid] = $r['systemid'];
			$manfids[$partid] = $r['manfid'];
		}
	}

	$part = '';
	$heci = '';
	$description = '';
	$manfid = 0;
	$systemid = 0;

	// attempt to find master by comparing manfid and systemid first, assuming that the
	// master will have a more complete set
	$masterid = $partids[0];
	$slaveid = $partids[1];
	if ($manfids[$slaveid] AND $systemids[$slaveid] AND $descrs[$slaveid] AND (! $manfids[$masterid] OR ! $systemids[$masterid] OR ! $descrs[$masterid])) {
		$temp_master = $slaveid;
		$slaveid = $masterid;
		$masterid = $temp_master;
	}

	if (! $masterid OR ! $slaveid) {
		reportError("Missing one or more partids");
	}

	/*** PART ***/
	$part = $parts[$masterid];
	// parse second part number and if any terms overlap, discard
	$fpart = preg_replace('/[^[:alnum:]]*/','',$part);
	if (strtolower($parts[$masterid])!==strtolower($parts[$slaveid])) {
		$partTerms2 = preg_split('/[[:space:]]+/',$parts[$slaveid]);
		foreach ($partTerms2 as $term) {
			if (stristr($fpart,preg_replace('/[^[:alnum:]]*/','',$term))!==false) { continue; }

			if ($part) { $part .= ' '; }
			$part .= $term;
		}
	}
	/*** HECI ***/
	if ($hecis[$masterid]) { $heci = $hecis[$masterid]; }
	else if ($hecis[$slaveid]) { $heci = $hecis[$slaveid]; }
	// if both records have hecis and they don't match, add heci to part string as an alias
	if ($hecis[$masterid] AND $hecis[$slaveid] AND $hecis[$masterid]!==$hecis[$slaveid]) { $part .= ' '.$hecis[$slaveid]; }
	/*** DESCR ***/
	if ($descrs[$masterid]) { $description = $descrs[$masterid]; }
	// parse second descr and if any words overlap, discard
	$fdescr = preg_replace('/[^[:alnum:]]*/','',$description);
	if (strtolower($descrs[$masterid])!==strtolower($descrs[$slaveid])) {
		$descrTerms2 = preg_split('/[[:space:]]+/',$descrs[$slaveid]);
		foreach ($descrTerms2 as $term) {
			if (stristr($fdescr,preg_replace('/[^[:alnum:]]*/','',$term))!==false) { continue; }

			if ($description) { $description .= ' '; }
			$description .= $term;
		}
	}
	/*** MANF ***/
	$manfid = $manfids[$masterid];
	/*** SYSTEM ***/
	$systemid = $systemids[$masterid];

/*
	echo json_encode(array('message'=>'Success'));
	exit;
*/

	$query = "UPDATE parts SET part = '".res($part)."', heci = ";
	if ($heci) { $query .= "'".res($heci)."', "; } else { $query .= "NULL, "; }
	$query .= "manfid = '".res($manfid)."', systemid = ";
	if ($systemid) { $query .= "'".res($systemid)."', "; } else { $query .= "NULL, "; }
	$query .= "description = ";
	if ($description) { $query .= "'".res($description)."' "; } else { $query .= "NULL "; }
	$query .= "WHERE id = '".$masterid."' LIMIT 1; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	// update keywords index for this part
	indexer($masterid,'id');

	$query = "UPDATE market SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	// check for existing record in availability table, and delete duplicate if present
	$query = "SELECT metaid, id FROM availability WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		// is there a duplicate already under the $masterid?
		$query2 = "SELECT id FROM availability WHERE partid = '".res($masterid)."' AND metaid = '".$r['metaid']."'; ";
		$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		if (mysqli_num_rows($result2)==0) {
			// no pre-existing record, so update accordingly
			$query2 = "UPDATE availability SET partid = '".res($masterid)."' WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		} else {
			// duplicate
			$query2 = "DELETE FROM availability WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		}
	}

	$query = "SELECT * FROM picture_maps WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "SELECT * FROM picture_maps WHERE partid = '".res($masterid)."' AND image = '".$r['image']."'; ";
		$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		if (mysqli_num_rows($result2)==0) {
			$query2 = "UPDATE picture_maps SET partid = '".res($masterid)."' WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		} else {
			$query2 = "DELETE FROM picture_maps WHERE id = '".$r['id']."'; ";
			$result2 = qdb($query2) OR reportError(qe().' '.$query2);
		}
	}

	/***** UPDATE QUERIES *****/

	$query = "UPDATE average_costs SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE bill_items SET item_id = '".res($masterid)."' WHERE item_id = '".res($slaveid)."' AND item_label = 'partid'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE builds SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE demand SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE favorites SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE inventory SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE inventory_history SET value = '".res($masterid)."' WHERE field_changed = 'partid' AND value = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE invoice_items SET item_id = '".res($masterid)."' WHERE item_id = '".res($slaveid)."' AND (item_label = 'partid' OR item_label IS NULL); ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE messages SET ref_1 = '".res($masterid)."' WHERE ref_1_label = 'partid' AND ref_1 = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE purchase_items SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE purchase_requests SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE repair_items SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE repair_quotes SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE repair_sources SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE return_items SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE rfqs SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE sales_items SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE outsourced_items SET item_id = '".res($masterid)."' WHERE item_id = '".res($slaveid)."' AND item_label = 'partid'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "UPDATE staged_qtys SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);


	/***** DELETE QUERIES *****/
	$query = "DELETE FROM parts WHERE id = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "DELETE FROM parts_index WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

	$query = "DELETE FROM qtys WHERE partid = '".res($slaveid)."'; ";
	$result = qdb($query) OR reportError(qe().' '.$query);

//	$query = "UPDATE prices SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
//	$result = qdb($query) OR reportError(qe().' '.$query);
//	$query = "UPDATE notifications SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
//	$result = qdb($query) OR reportError(qe().' '.$query);
//	$query = "UPDATE inventory_costs SET partid = '".res($masterid)."' WHERE partid = '".res($slaveid)."'; ";
//	$result = qdb($query) OR reportError(qe().' '.$query);

	echo json_encode(array('message'=>'Success'));
	exit;
?>
