<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';

	$csv_partids = '';
	$query = "SELECT SUM(qty) total, partid FROM inventory  ";
	$query .= "WHERE partid IS NOT NULL AND conditionid > 0 AND status = 'received' ";
	$query .= "GROUP BY partid;";
	$result = qdb($query) or die(qe()." $query");
	while ($r = mysqli_fetch_assoc($result)) {
		if ($csv_partids) { $csv_partids .= ','; }
		$csv_partids .= $r['partid'];

		$qty = $r['total'];
    
		//Check to see if there is a qtys record
		$query2 = "SELECT * FROM `qtys` WHERE partid = ".prep($r['partid']).";";
		$result2 = qdb($query2) or die(qe()." | $query2");

		//if not, new part and create one
		if (! mysqli_num_rows($result2)) {
			$insert = "INSERT INTO `qtys`(`partid`, `qty`, `hidden_qty`, `visible_qty`) VALUES (".$r['partid'].", '".$qty."', NULL, '".$qty."');";
			qdb($insert) or die(qe()." | $insert");
		} else {
			//otherwise, update the old value
			$query2 = "UPDATE `qtys` SET `qty` = '".$qty."',  
				`visible_qty` = CASE
					WHEN ((`hidden_qty` = 0 AND `hidden_qty` is not null) OR (`hidden_qty` >= '".$qty."')) THEN 0
					ELSE ('".$qty."' - ifnull(`hidden_qty`,0))
				END
				WHERE `partid` = ".prep($r['partid']).";
			";
			qdb($query2) or die(qe());
		}
	}

	// Delete all parts from qtys table that are NOT in stock per routine above; this removes sold / pulled items from inventory
	$del = "DELETE FROM qtys WHERE partid NOT IN (".$csv_partids."); ";
	qdb($del) or die(qe());
?>
