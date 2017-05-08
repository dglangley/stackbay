<?php
    
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';

    //BDB
    $query = "SELECT inventory_id, COUNT(*) as qty FROM inventory_itemlocation GROUP BY inventory_id;";
    $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
    while ($r = mysqli_fetch_assoc($result)) {
        $partid = translateID($r['inventory_id']);
		$bdb_qty = $r['qty'];
        if (! $partid) {
echo 'No partid for '.$r['inventory_id'].' (brians inventory id)<BR>';
continue;
        }

		$pid = prep($partid);

		$aws_qty = 0;
		$query2 = "SELECT SUM(qty) as qty FROM inventory WHERE qty > 0 AND partid = $pid GROUP BY partid;";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$aws_qty = $r2['qty'];
		}

		if ($aws_qty==$bdb_qty) { continue; }

		echo 'inventory_id '.$r['inventory_id'].' has qty '.$bdb_qty.', partid '.$partid.' has qty '.$aws_qty.'<BR>';
	}
?>
