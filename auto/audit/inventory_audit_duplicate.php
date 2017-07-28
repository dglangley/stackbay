<?php
    
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    
    $BDB_inventory_qty = array();

    //BDB
    $query = "SELECT inventory_id as partid, COUNT(*) as qty FROM inventory_itemlocation GROUP BY inventory_id;";
    $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
    while ($r = mysqli_fetch_assoc($result)) {
        $BDB_inventory_qty[] = $r;
    }

    foreach($BDB_inventory_qty as $bpartid){
        $BDB_serials = array();
        $BDB_soldserials = array();
        $part_serials = array();

        $translatedID = translateID($bpartid['partid']);

        if(!$translatedID) {

        }

        $AWS_qty = 0;

        if($translatedID) {
            $translatedID = prep($translatedID);

            $query = "SELECT SUM(qty) as qty FROM inventory WHERE qty > 0 AND partid = $translatedID GROUP BY partid;";
            $result = qdb($query) OR die(qe().'<BR>'.$query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $AWS_qty = $r['qty'];
            }

            // if($AWS_qty != $bpartid['qty']) {
            //     echo '<b>Error in QTY </b>partid: ' . $translatedID . ' has qty: ' . $AWS_qty . ' Brian\'s inventoryid: ' . $bpartid['partid'] . ' has qty: ' . $bpartid['qty'] . '<br>';
            // }

            //Add in the query here to get the translated partid and check BDB's part
            //Most cases the duplicate means that the serial has been inputted multiple times
            if($AWS_qty != $bpartid['qty']) {
                $query = "SELECT serial FROM inventory_itemlocation WHERE inventory_id = ".prep($bpartid['partid']).";";
                $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
                while ($r = mysqli_fetch_assoc($result)) {
                    $BDB_serials[] = $r['serial'];
                }

                $query = "SELECT serial FROM inventory_solditem WHERE inventory_id = ".prep($bpartid['partid']).";";
                $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
                while ($r = mysqli_fetch_assoc($result)) {
                    $BDB_soldserials[] = $r['serial'];
                }

                //Query to grab the mismatched qty partid and grab all serials
                $query = "SELECT serial_no, partid FROM inventory WHERE qty > 0 AND partid = $translatedID;";
                $result = qdb($query) OR die(qe().'<BR>'.$query);
                while ($r = mysqli_fetch_assoc($result)) {
                    $part_serials[] = $r;
                }

                ?>

                <style>
                    table, th, td {
                        border: 1px solid black;
                    }
                </style>

                <table>
                    <tr><th colspan="3">
                        <?php echo '<b>Error in QTY </b>partid: ' . $translatedID . ' has QTY: ' . $AWS_qty . ' (Brian\'s inventoryid: ' . $bpartid['partid'] . ' has QTY: ' . $bpartid['qty'] . ')<br>'; ?>
                    </th></tr>
                    <tr>
                        <th>Serial</th>
                        <th>Partid</th>
                        <th>Valid</th>
                    </tr>
                    <?php foreach ($part_serials as $item): ?>
                    <tr>
                        <td><?php echo $item['serial_no']; ?></td>
                        <td><?php echo $item['partid']; ?></td>
                        <td><?php
                            //Check if the serial w/ relation to partid exists using BDB data
                            if(in_array($item['serial_no'], $BDB_serials)) {
                                echo '<b>YES</b><br>';

                                $BDB_location = 0;

                                //Find the location of the matching item and update it on the item.
                                $query = "SELECT location_id FROM inventory_itemlocation WHERE serial = ".prep($item['serial_no']).";";
                                //echo '<b>Location Find Query</b>: ' . $query . '<br>';
                                $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
                                if (mysqli_num_rows($result)==1) {
                                    $r = mysqli_fetch_assoc($result);
                                    $BDB_location = $r['location_id'];
                                }

                                echo 'BDB Location to update: ' . $BDB_location . '<br>';

                                if($BDB_location) {
                                    $query = "UPDATE inventory SET locationid = ".prep($BDB_location)." WHERE serial_no = ".prep($item['serial_no'])." AND partid = ".prep($item['partid']) .";";
                                    echo '<b>Location Update Query</b>: ' . $query . '<br>';
                                    //qdb($query) OR die(qe().'<BR>'.$query);
                                }

                                //Query our Database for this serial and delete all the serial entries that do not pertain to serial_no matched to part
                                $query = "DELETE FROM inventory WHERE serial_no = ".prep($item['serial_no'])." AND partid <> ".prep($item['partid']) ." AND qty = 1;";
                                echo '<b>DELETE Query</b>: ' . $query . '<br>';
                                //qdb($query) OR die(qe().'<BR>'.$query);
                            } else {
                                echo '<b>NO</b><br>';

                                $soldinvid = 0;

                                //Quickly check BDB if the serial was sold
                                $query = "SELECT inventory_id FROM inventory_solditem WHERE serial = ".prep($item['serial_no']).";";
                                $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
                                if (mysqli_num_rows($result)>1) {
                                    $r = mysqli_fetch_assoc($result);
                                    $soldinvid = $r['inventory_id'];
                                }

                                echo '<b>Search Query in itemsold</b>: ' . $query . '<br>';

                                if($soldinvid) {
                                    $translatedSID = prep(translateID($soldinvid));
                                    echo "Item was Sold w/ relation to partid: $translatedSID (BINVD: $soldinvid)<br>";

                                    //This query is just a double check to see if the sold serial w/ partid exists before deleting all duplicates as a fall back
                                    $query = "SELECT id FROM inventory WHERE serial_no = ".prep($item['serial_no'])." AND partid = $translatedSID;";
                                    $result = qdb($query) OR die(qe().'<BR>'.$query);
                                    if (mysqli_num_rows($result)>0) {
                                        $query = "DELETE FROM inventory WHERE serial_no = ".prep($item['serial_no'])." AND partid <> $translatedSID;";
                                        echo '<b>DELETE Query</b>: ' . $query . '<br>';
                                        //qdb($query) OR die(qe().'<BR>'.$query);
                                    } else {
                                        echo 'Something went horribly wrong...<br>';
                                    }
                                //Strain it one more time if sold items fail back to itemlocation (840501083, 841148934)
                                } else {
                                    //Not used yet
                                }
                            }
                        ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>

                <?php

                //print_r($part_serials);
                //echo '<br>';
                //print_r($BDB_serials);
                echo '<br><br>';

                //$query = "SELECT serial_no, COUNT(serial_no) as occurrences FROM inventory WHERE partid = $translatedID;";
            }

        } else {
            //echo 'Failed to Translate Inventory ID: ' .$bpartid['partid']. '<BR>';
        }
    }

?>
