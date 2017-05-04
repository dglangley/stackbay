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
        $translatedID = translateID($bpartid['partid']);
        $AWS_qty = 0;

        if($translatedID) {
            $translatedID = prep($translatedID);

            $query = "SELECT SUM(qty) as qty FROM inventory WHERE qty > 0 AND partid = $translatedID GROUP BY partid;";
            $result = qdb($query) OR die(qe().'<BR>'.$query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $AWS_qty = $r['qty'];
            }

            if($AWS_qty != $bpartid['qty']) {
                echo '<b>Error in QTY </b>partid: ' . $translatedID . ' has qty: ' . $AWS_qty . ' Brian\'s inventoryid: ' . $bpartid['partid'] . ' has qty: ' . $bpartid['qty'] . '<br>';
                //echo $query . '<BR><BR>';
            }

        } else {
            //echo 'Failed to Translate Inventory ID: ' .$bpartid['partid']. '<BR>';
        }
    }
    
    //BDB - inventory_inventory
    // $pipe_search = "SELECT `id`, `part_number`, `quantity_stock` qty FROM inventory_inventory;";
    // $pipe_results = qdb($pipe_search, "PIPE") or die(qe("PIPE")." | $pipe_search");
    // foreach($pipe_results as $p_row){
    //     $partid = part_process($p_row);
    //     $comp[$partid]['BDB']['part'] = $p_row['part_number'];
    //     $comp[$partid]['BDB']['qty'] += $p_row['qty'];
    // }

    // print_r($comp);
    // die;
        
    // // $pipe_search = "SELECT `id`, `part_number`, `quantity_stock` qty FROM inventory_;";
    // // $pipe_results = qdb($pipe_search, "PIPE") or die(qe("PIPE")." | $pipe_search");
    // // foreach($pipe_results as $p_row){
    // //     $comp[translateID($p_row['id'])]['BDB']['part'] = $p_row['part_number'];
    // //     $comp[translateID($p_row['id'])]['BDB']['qty'] += $p_row['qty'];
    // // }
    
    
    // //AWS
    // $inv_search = "SELECT `partid`,`part`,`qty` FROM inventory;";
    // $inv_results = qdb($inv_search) or die(qe()." | $inv_search");
    // foreach($inv_results as $i_row){
    //     $comp[$i_row['partid']]['AWS']['part'] = $i_row['part'];
    //     $comp[$i_row['partid']]['AWS']['qty'] += $i_row['qty'];
    // }
    // echo("<pre>");
    // foreach($comp as $part){
    //     if($part['AWS']['qty'] != $part['BDB']['qty']){
    //         print_r($part);
    //     }
    // }
    // echo("</pre>");
    
    
/*    

According to this query, there are 731 identifiable cases of the mismatch and only 115 that match

SELECT part_number, count(*) item_locations, quantity_stock inventory_amount
FROM inventory_itemlocation, inventory_inventory i 
WHERE orig_inv_id = i.id 
GROUP BY i.part_number 
HAVING item_locations != inventory_amount
ORDER BY part_number asc



*/


?>