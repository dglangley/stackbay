<?php
    
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

    //BDB
	$i = 1;
    $query = "SELECT part_number, clei, inventory_id, COUNT(*) as qty ";
	$query .= "FROM inventory_itemlocation il, inventory_inventory i ";
	$query .= "WHERE il.inventory_id = i.id AND i.id <> 1 AND i.id <> 5953 AND i.id <> 234081 AND i.id <> 224786 ";
	$query .= "GROUP BY inventory_id;";
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

		$part_partid = getPartId($r['part_number']);
		$part_pid = prep($part_partid);
		$part_qty = 0;
		$query2 = "SELECT SUM(qty) as qty FROM inventory WHERE qty > 0 AND partid = $part_pid GROUP BY partid;";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$part_qty = $r2['qty'];
		}
		if ($part_qty==$bdb_qty OR ($aws_qty+$part_qty)==$bdb_qty) { continue; }

		$query2 = "SELECT part, heci FROM parts WHERE id = $pid; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		$r2 = mysqli_fetch_assoc($result2);
		$parts = explode(' ',$r2['part']);

		echo ($i++).'. Brians inventory_id <strong>'.$r['inventory_id'].'</strong> ('.$r['part_number'].' '.$r['clei'].') has qty <strong>'.$bdb_qty.'</strong><BR>'.
			'Our partid <strong>'.$partid.'</strong> ('.$parts[0].' '.$r2['heci'].') has qty <strong>'.$aws_qty.'</strong><BR>';
		if ($aws_qty<>$bdb_qty) {
			$query2 = "SELECT part, heci FROM parts WHERE id = $part_pid; ";
			$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			$r2 = mysqli_fetch_assoc($result2);
			$parts = explode(' ',$r2['part']);
			echo 'Part-only partid <strong>'.$part_partid.'</strong> ('.$parts[0].' '.$r2['heci'].') has qty '.$part_qty.'<BR>';
		}

		// try to figure out the type of discrepancy, specifically if duplicate serials created
		$query2 = "SELECT * FROM inventory WHERE partid = $pid AND qty > 0; ";
		$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
		if (mysqli_num_rows($result2)>0) {
		}
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$serial = $r2['serial_no'];
			$prep_serial = prep($serial);

			// get brians inventory record of this serial
			$fixed_partid = 0;
			$query3 = "SELECT inventory_id FROM inventory_itemlocation WHERE serial = $prep_serial; ";
			$result3 = qdb($query3,'PIPE') OR die(qe('PIPE').'<BR>'.$query3);
			if (mysqli_num_rows($result3)==0) {
//				continue;
			} else {
				$r3 = mysqli_fetch_assoc($result3);
				$fixed_partid = translateID($r3['inventory_id']);
			}

			$query3 = "SELECT * FROM inventory WHERE serial_no = $prep_serial; ";
			$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
			if (mysqli_num_rows($result3)>1) {
$postquery = "";
echo $query3.'<BR>';
echo '<table>
	<tr>
		<th>serial_no</th><th>qty</th><th>partid</th><th>conditionid</th><th>status</th><th>conditionid</th>
		<th>purchase_item_id</th><th>sales_item_id</th><th>returns_item_id</th><th>userid</th><th>date_created</th><th>notes</th><th>id</th>
	</tr>
';
				while ($r3 = mysqli_fetch_assoc($result3)) {
					$style = '';
					if ($fixed_partid==$r3['partid']) {
						$style = ' style="font-style:italic"';
					} else {
						// check for associated inventory_costs
						if (! $fixed_partid) {
							$query4 = "SELECT * FROM inventory_costs WHERE inventoryid = '".$r3['id']."'; ";
							$result4 = qdb($query4) OR die(qe().'<BR>'.$query4);
							if (mysqli_num_rows($result4)>0) {
								$style = ' style="font-style:italic"';
							}
						}
					}
					if (! $style) {
						$query4 = "DELETE FROM inventory WHERE id = '".$r3['id']."'; ";
$postquery .= $query4.'<BR>';
					}
echo '
	<tr'.$style.'>
		<td>'.$r3['serial_no'].'</td>
		<td>'.$r3['qty'].'</td>
		<td>'.$r3['partid'].'</td>
		<td>'.$r3['conditionid'].'</td>
		<td>'.$r3['status'].'</td>
		<td>'.$r3['conditionid'].'</td>
		<td>'.$r3['purchase_item_id'].'</td>
		<td>'.$r3['sales_item_id'].'</td>
		<td>'.$r3['returns_item_id'].'</td>
		<td>'.$r3['userid'].'</td>
		<td>'.$r3['date_created'].'</td>
		<td>'.$r3['notes'].'</td>
		<td>'.$r3['id'].'</td>
	</tr>
';
				}
if (! $postquery) { $postquery = '<BR>'; }
echo '
</table> 
'.$postquery;
			}
		}

		echo '<BR>';
	}
?>
