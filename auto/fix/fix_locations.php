<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

	$query = "SELECT * FROM inventory_itemlocation il, inventory_inventory i ";
	$query  .= "WHERE location_id <> '1' AND il.inventory_id = i.id AND serial <> '000'; ";
	$result = qdb($query,'PIPE');
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['clei']) { $r['heci'] = $r['clei']; }
		else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
		else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string

		$partid = getPartId($r['part_number'],$r['heci']);

		$query2 = "SELECT serial_no, part, heci, locationid, conditionid, i.id inventoryid FROM inventory i, parts p ";
		$query2 .= "WHERE locationid <> '".$r['location_id']."' AND partid = '".$partid."' AND serial_no = '".$r['serial']."' ";
		$query2 .= "AND i.partid = p.id; ";
		$result2 = qdb($query2);
		if (mysqli_num_rows($result2)==0) { continue; }
		$r2 = mysqli_fetch_assoc($result2);

		$query3 = "SELECT * FROM inventory_history h WHERE invid = '".$r2['inventoryid']."' ";
		$query3 .= "AND date_changed >= '2017-05-01 00:00:00' AND field_changed = 'locationid'; ";
		$result3 = qdb($query3);
echo $r['serial'].' <strong>'.$r['location_id'].'</strong><BR>'.
	' &nbsp; '.$r['part_number'].' '.$r['heci'].' = '.$partid.'<BR>';

		if (mysqli_num_rows($result3)>0) {
			$r3 = mysqli_fetch_assoc($result3);
			echo 'LOCATION CHANGED TO '.$r3['value'].' FROM '.$r3['changed_from'].' ON '.$r3['date_changed'].'<BR><BR>';
			continue;
		}

		$parts = explode(' ',$r2['part']);
		echo ' &nbsp; '.$r2['serial_no'].' '.$parts[0].' '.$r2['heci'].' <strong>'.$r2['locationid'].'</strong><BR>';

		$query3 = "UPDATE inventory SET locationid = '".$r['location_id']."' ";
		if (($r['location_id']==67 OR $r['location_id']==81) AND $r2['conditionid']==2) {
			$query3 .= ", conditionid = '-5' ";
		} else if (($r['location_id']==80 OR $r['location_id']==128) AND $r2['conditionid']==2) {
			$query3 .= ", conditionid = '-6' ";
		} else if ($r['location_id']==125) {
			$query3 .= ", status = 'scrapped' ";
		}
		$query3 .= "WHERE id = '".$r2['inventoryid']."'; ";
		echo $query3.'<BR><BR>';
	}
?>
