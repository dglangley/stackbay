<?php
        include_once 'inc/dbconnect.php';
        include_once 'inc/pipe.php';
        include_once 'inc/getPartId.php';

        $query = "SELECT part_number, clei, serial, inventory_id, location_id, il.id, count(il.id) n ";
        $query .= "FROM inventory_itemlocation il, inventory_inventory i ";
        $query .= "WHERE serial LIKE '000' AND il.inventory_id = i.id ";
        $query .= "GROUP BY inventory_id, location_id ORDER BY n DESC; ";// HAVING n <= 225 ORDER BY n DESC; ";
        $result = qdb($query,'PIPE');
        while ($r = mysqli_fetch_assoc($result)) {
                if ($r['clei']) { continue; }

/*
                $query2 = "SELECT part, heci, id FROM parts WHERE heci = '".$r['clei']."'; ";
                $result2 = qdb($query2);
                if (mysqli_num_rows($result2)==0) { continue; }
                $r2 = mysqli_fetch_assoc($result2);
                $partid = $r2['id'];
*/
                $partid = getPartId($r['part_number']);
echo $r['part_number'].' '.$r['clei'].' '.$r['serial'].' '.$r['inventory_id'].' inventoryid, '.$r['n'].'n: location_id '.$r['location_id'].'<BR>';

//              $query2 = "SELECT p.part, p.heci, i.*, count(i.id) n FROM inventory i, parts p ";
//              $query2 .= "WHERE qty > 0 AND serial_no like 'VTL%' AND locationid = 1 AND p.id = partid ";
//              $query2 .= "GROUP BY partid, LEFT(serial_no,3) ORDER BY n DESC; ";
                $query2 = "SELECT * FROM inventory WHERE partid = '".$partid."' AND ((serial_no LIKE 'VTL%' AND qty = 1) OR (serial_no IS NULL AND qty > 0)); ";//AND locationid = 1 ; ";
                $result2 = qdb($query2);
                $i = 0;
$loc1 = false;
$script = '';
$matching = 0;
                while ($r2 = mysqli_fetch_assoc($result2)) {
if ($r2['locationid']==$r['location_id']) { $matching += $r2['qty']; }
else if ($r2['locationid']==1) { $loc1 = true; }
                        $script .= ' &nbsp; '.(++$i).'. '.$r2['part'].' '.$r2['heci'].' '.$r2['serial_no'].' '.$r2['partid'].' partid, '.$r2['locationid'].' locationid, '.$r2['n'].' n<BR>';
                }
echo $matching.' location-matching record(s)/qty(s)<BR>';
//if ($loc1 OR $matching<>$r['n']) {
if ($matching<>$r['n']) {
echo $script;

                if ($i==$r['n'] AND $partid) {
$query2 = "UPDATE inventory SET locationid = '".$r['location_id']."' WHERE partid = '".$partid."' AND ((serial_no LIKE 'VTL%' AND qty = 1) OR (serial_no IS NULL AND qty > 0)) LIMIT ".$i."; ";
$result2 = qdb($query2);
echo $query2.'<BR>';
//$query2 = "SELECT * FROM inventory WHERE partid = '".$partid."'; ";
//echo $query2.'<BR>';
                }
echo '<BR>';
}
        }
?>
