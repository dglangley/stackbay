<?php
    
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setJournalEntry.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/import_aid.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
    
    $comp = array();
    
    //BDB - inventory_inventory
    $pipe_search = "SELECT `id`, `part_number`, `quantity_stock` qty, i.heci, i.clei, i.short_description, im.name manf FROM inventory_inventory;";
    $pipe_results = qdb($pipe_search, "PIPE") or die(qe("PIPE")." | $pipe_search");
    foreach($pipe_results as $p_row){
        $partid = part_process($p_row);
        $comp[$partid]['BDB']['part'] = $p_row['part_number'];
        $comp[$partid]['BDB']['qty'] += $p_row['qty'];
    }
        
    $pipe_search = "SELECT `id`, `part_number`, `quantity_stock` qty FROM inventory_;";
    $pipe_results = qdb($pipe_search, "PIPE") or die(qe("PIPE")." | $pipe_search");
    foreach($pipe_results as $p_row){
        $comp[translateID($p_row['id'])]['BDB']['part'] = $p_row['part_number'];
        $comp[translateID($p_row['id'])]['BDB']['qty'] += $p_row['qty'];
    }
    
    
    //AWS
    $inv_search = "SELECT `partid`,`part`,`qty` FROM inventory;";
    $inv_results = qdb($inv_search) or die(qe()." | $inv_search");
    foreach($inv_results as $i_row){
        $comp[$i_row['partid']]['AWS']['part'] = $i_row['part'];
        $comp[$i_row['partid']]['AWS']['qty'] += $i_row['qty'];
    }
    echo("<pre>");
    foreach($comp as $part){
        if($part['AWS']['qty'] != $part['BDB']['qty']){
            print_r($part);
        }
    }
    echo("</pre>");
    
    
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