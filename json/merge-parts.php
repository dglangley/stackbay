<?php
	include_once '../inc/dbconnect.php';
	include_once '../inc/jsonDie.php';
	include_once '../inc/indexer.php';
	header("Content-Type: application/json", true);

	$partids = array();
	if (isset($_REQUEST['partids'])) {
		if (is_array($_REQUEST['partids'])) {
			$partids = $_REQUEST['partids'];
		} else {
			$partids = explode(",",$_REQUEST['partids']);
		}
	}

	// we're going to attempt to determine the "master" fields by filling in gaps between the records
	$parts = array();
	$fparts = array();
	$descrs = array();
	$systemids = array();
	$classes = array();
	$H = array();

	$masterid = 0;

	$last_class = '';
	$last_heci = '';
	foreach ($partids as $partid) {
		$query = "SELECT * FROM parts WHERE id = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		// confirm part existence
		if (mysqli_num_rows($result)<>1) { jsonDie("Could not find part in db"); }

		$r = mysqli_fetch_assoc($result);

		$p = explode(' ',trim($r['part']));
		$parts = array_merge($parts,$p);

		if ((strlen($r['heci'])<>7 AND strlen($r['heci'])<>10) OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) {
			$r['heci'] = '';
		}
		$H[$partid] = $r;

		if ($r['heci']) {
			if ($last_heci AND $r['heci']<>$last_heci) {
				jsonDie("You cannot merge multiple parts with full HECI codes");
			}
			$last_heci = $r['heci'];
		}

		$d = explode(' ',trim($r['description']));
		$descrs = array_merge($descrs,$d);

		if ($r['systemid']) { $systemids[] = $r['systemid']; }

		if ($last_class AND $r['classification']<>$last_class) {
			jsonDie("You cannot merge parts of two different classifications (equip vs material vs comp)");
		}
		$last_class = $r['classification'];

		if (! $masterid AND $r['manfid'] AND $r['heci']) {
			$masterid = $partid;
		}
	}

	if (! $masterid) { $masterid = $partid; }

	/*** BUILD PART STRING ***/
	$fstrings = array();
	$part_str = '';
	foreach ($parts as $part) {
		$fpart = preg_replace('/[^[:alnum:]]*/','',$part);
		if (isset($fstrings[$fpart])) { continue; }
		$fstrings[$fpart] = true;

		if ($part_str) { $part_str .= ' '; }
		$part_str .= $part;
	}

	/*** BUILD DESCR ***/
	$dstrings = array();
	$descr_str = '';
	foreach ($descrs as $descr) {
		$fdescr = preg_replace('/[^[:alnum:]]*/','',$descr);
		if (isset($dstrings[$fdescr])) { continue; }
		$dstrings[$fdescr] = true;

		if ($descr_str) { $descr_str .= ' '; }
		$descr_str .= $descr;
	}

	// update master with new info
	$query = "UPDATE parts SET part = '".res($part_str)."', ";
	if (! $H[$masterid]['systemid'] AND $systemids[0]) {
		$query .= "systemid = '".res($systemids[0])."', ";
	}
	$query .= "description = ".fres($descr_str)." ";
	$query .= "WHERE id = '".res($masterid)."' LIMIT 1; ";
	$result = qdb($query) OR jsonDie(qe().' '.$query);

	// update keywords index for this part
	indexer($masterid,'id');

	foreach ($partids as $partid) {
		if ($partid==$masterid) { continue; }

		$query = "UPDATE market SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		// check for existing record in availability table, and delete duplicate if present
		$query = "SELECT metaid, id FROM availability WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			// is there a duplicate already under the $masterid?
			$query2 = "SELECT id FROM availability WHERE partid = '".res($masterid)."' AND metaid = '".$r['metaid']."'; ";
			$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			if (mysqli_num_rows($result2)==0) {
				// no pre-existing record, so update accordingly
				$query2 = "UPDATE availability SET partid = '".res($masterid)."' WHERE id = '".$r['id']."'; ";
				$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			} else {
				// duplicate
				$query2 = "DELETE FROM availability WHERE id = '".$r['id']."'; ";
				$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			}
		}

		$query = "SELECT * FROM picture_maps WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$query2 = "SELECT * FROM picture_maps WHERE partid = '".res($masterid)."' AND image = '".$r['image']."'; ";
			$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			if (mysqli_num_rows($result2)==0) {
				$query2 = "UPDATE picture_maps SET partid = '".res($masterid)."' WHERE id = '".$r['id']."'; ";
				$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			} else {
				$query2 = "DELETE FROM picture_maps WHERE id = '".$r['id']."'; ";
				$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			}
		}

		/***** UPDATE QUERIES *****/

		$query = "UPDATE average_costs SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE bill_items SET item_id = '".res($masterid)."' WHERE item_id = '".res($partid)."' AND item_label = 'partid'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE builds SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE demand SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE favorites SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE inventory SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE inventory_history SET value = '".res($masterid)."' WHERE field_changed = 'partid' AND value = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE invoice_items SET item_id = '".res($masterid)."' WHERE item_id = '".res($partid)."' AND (item_label = 'partid' OR item_label IS NULL); ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE messages SET ref_1 = '".res($masterid)."' WHERE ref_1_label = 'partid' AND ref_1 = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE purchase_items SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE purchase_requests SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE repair_items SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE repair_quotes SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE repair_sources SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE return_items SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE rfqs SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE sales_items SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE outsourced_items SET item_id = '".res($masterid)."' WHERE item_id = '".res($partid)."' AND item_label = 'partid'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "UPDATE staged_qtys SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);


		/***** DELETE QUERIES *****/
		$query = "DELETE FROM parts WHERE id = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "DELETE FROM parts_index WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

		$query = "DELETE FROM qtys WHERE partid = '".res($partid)."'; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);

//		$query = "UPDATE prices SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
//		$result = qdb($query) OR jsonDie(qe().' '.$query);
//		$query = "UPDATE notifications SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
//		$result = qdb($query) OR jsonDie(qe().' '.$query);
//		$query = "UPDATE inventory_costs SET partid = '".res($masterid)."' WHERE partid = '".res($partid)."'; ";
//		$result = qdb($query) OR jsonDie(qe().' '.$query);
	}

	echo json_encode(array('message'=>'Success'));
	exit;
?>
