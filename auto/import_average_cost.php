<?php
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/pipe.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getPartId.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/setAverageCost.php';

	$costs = array();
    $query = "SELECT i.id, part_number, heci, clei, avg_cost, count(il.id) qty  ";
    $query .= "FROM inventory_inventory i, inventory_itemlocation il ";
    $query .= "WHERE i.id = il.inventory_id AND i.id > 2 AND i.id <> 226870 AND i.id <> 240882 AND i.id <> 247080 AND i.id <> 230438 ";
$query .= "AND location_id <> 125 ";
    $query .= "GROUP BY i.id HAVING qty > 0; ";
    $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
    while ($r = mysqli_fetch_assoc($result)) {
        $inventory_id = $r['id'];

        $partid = 0;
        if ($r['clei']) {
            $partid = getPartId($r['part_number'],$r['clei']);
        } else if ($r['heci']) {
            $partid = getPartId($r['part_number'],$r['heci']);
        } else {
            $partid = getPartId($r['part_number']);
        }

		if (! isset($costs[$partid])) { $costs[$partid] = array('qty'=>0,'cost'=>0); }
		$costs[$partid]['qty'] += $r['qty'];
		$costs[$partid]['cost'] += ($r['avg_cost']*$r['qty']);
	}

	foreach ($costs as $partid => $r) {
        if (! $partid) { die("Could not import ".$r['part_number']); }

        $query2 = "SELECT * FROM parts WHERE id = '".res($partid)."'; ";
        $result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
        if (mysqli_num_rows($result2)==0) { die("Problem: no part exists for id ".$partid); }
        $r2 = mysqli_fetch_assoc($result2);

        $qty = 0;
        $query2 = "SELECT serial_no, qty, sales_item_id FROM inventory WHERE partid = '".$partid."' AND (status = 'shelved' OR status = 'received' OR status = 'in repair') ";
//      $query2 .= "AND (date_created < '2017-05-01 00:00:00' OR userid = 0) ";
        $query2 .= "; ";
        $result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
        while ($r2 = mysqli_fetch_assoc($result2)) {
if ($r2['qty']>0) { $qty += $r2['qty']; }
else if ($r2['serial_no']) { $qty++; }
        }

        $query3 = "SELECT serial_no, i.qty FROM inventory i, purchase_items pi, purchase_orders po ";
        $query3 .= "WHERE i.partid = '".$partid."' AND (i.status = 'shelved' OR i.status = 'received') ";
        $query3 .= "AND i.purchase_item_id = pi.id AND pi.po_number = po.po_number AND po.created >= '2017-05-01 00:00:00'; ";
        $result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
        while ($r3 = mysqli_fetch_assoc($result3)) {
if ($r3['qty']>0) { $qty -= $r3['qty']; }
else if ($r3['serial_no']) { $qty--; }
        }

        $query3 = "SELECT serial_no, i.qty FROM inventory i, sales_items si, sales_orders so ";
        $query3 .= "WHERE i.partid = '".$partid."' AND (i.status = 'outbound' OR i.status = 'manifest') ";
        $query3 .= "AND i.sales_item_id = si.id AND si.so_number = so.so_number AND so.created >= '2017-05-01 00:00:00'; ";
        $result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
        while ($r3 = mysqli_fetch_assoc($result3)) {
if ($r3['qty']>0) { $qty += $r3['qty']; }
else if ($r3['serial_no']) { $qty++; }
        }

//        if ($r['qty']<>$qty) {
//echo $r['part_number'].' '.$r['clei'].' with qty '.$r['qty'].' = partid '.$partid.' with qty '.$qty.'<BR>';
//        }

		$avg_cost = $r['cost']/$r['qty'];
        setAverageCost($partid,$avg_cost,true);
    }
?>
