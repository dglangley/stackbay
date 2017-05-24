<?php
        include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';

$debug = 1;

        $query = "SELECT *, COUNT(id) n FROM inventory WHERE sales_item_id = 1771 GROUP BY serial_no HAVING n = 2 ORDER BY IF(qty=1,0,1), serial_no ASC; ";
        $query = "SELECT * FROM inventory WHERE serial_no = 'NNTM16029G4K' AND qty = 1; ";
        $result = qdb($query) OR die(qe().'<BR>'.$query);
$i = 1;
        while ($r = mysqli_fetch_assoc($result)) {
                $date_created = $r['date_created'];

echo $i++.'. '.$r['serial_no'].'<BR>';
                $query2 = "SELECT * FROM inventory WHERE serial_no = '".$r['serial_no']."' AND qty = 0; ";
                $result2 = qdb($query2);
                $r2 = mysqli_fetch_assoc($result2);

$query3 = "UPDATE inventory_costs SET inventoryid = '".$r['id']."' WHERE inventoryid = '".$r2['id']."'; ";
if (! $debug) { $result3 = qdb($query3); }
echo $query3.'<BR>';

$query3 = "UPDATE inventory SET qty = 0, partid = '".$r2['partid']."', status = 'manifest', date_created = '".$date_created."', sales_item_id = '".$r2['sales_item_id']."' ";
if ($r2['returns_item_id']) { $query3 .= ", returns_item_id = '".$r2['returns_item_id']."' "; }
$query3 .= "WHERE id = '".$r['id']."'; ";
if (! $debug) { $result3 = qdb($query3); }
echo $query3.'<BR>';

$query3 = "DELETE FROM inventory WHERE id = '".$r2['id']."'; ";
if (! $debug) { $result3 = qdb($query3); }
echo $query3.'<BR>';

$query3 = "UPDATE inventory_history SET date_changed = '".$date_created."' ";
$query3 .= "WHERE invid = '".$r['id']."'; ";
if (! $debug) { $result3 = qdb($query3); }
echo $query3.'<BR>';

if ($r2['returns_item_id']) {
	$query3 = "UPDATE inventory_history SET invid = '".$r['id']."' WHERE invid = '".$r2['id']."'; ";
	if (! $debug) { $result3 = qdb($query3); }
	echo $query3.'<BR>';

	$query3 = "UPDATE return_items SET inventoryid = '".$r['id']."' WHERE inventoryid = '".$r2['id']."'; ";
	if (! $debug) { $result3 = qdb($query3); }
	echo $query3.'<BR>';
}

echo '<BR>';
        }
?>
