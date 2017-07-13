<?php

	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/indexer.php';

    $bdb_select = "
    SELECT cpo.date as datetime, co.* 
    FROM inventory_componentorder co, inventory_componentpurchaseorder cpo
    where co.cpo_id is not null 
    AND co.cpo_id = cpo.id 
    AND co.freight_cost > 0 
    order by co.cpo_id asc;";
    $bdb_results = qdb($bdb_select, "PIPE") or die(qe("PIPE")." | $bdb_select");
    
    if(!isset($debug)){$debug = 0;}
    
    $check_package = array();
    foreach($bdb_results as $r){
        //Variable Declarations
        $po_number = '';
        $component_id = '';
        $partid = '';
        $new_price = 0.00;
        $datetime = '';
        $package_id = '';
        $inventoryid = '';
        $purchase_item_id = '';
        /////
        
        
        //Info from BDB
        $po_number = $r['cpo_id'];
        $partid = translateComponent($r['component_id']);
        $datetime = prep(format_date($r['datetime']." 12:00:00", "Y-m-d H:i:s"));
        //Grab info from ours
        $select = "
        SELECT i.id invid, pi.id pid FROM purchase_items pi, inventory i 
        where pi.qty = i.qty
        AND i.purchase_item_id = pi.id
        AND i.partid = pi.partid
        AND pi.po_number < 880
        AND pi.po_number = ".prep($po_number)."
        AND pi.partid = ".prep($partid)."
        order by pi.po_number DESC;";
        $result = qdb($select) or die(qe()." | $select");
        
        //Was breaking on local because my data is less complete than the live
        if(!mysqli_num_rows($result)){continue;}
        $result = mysqli_fetch_assoc($result);

        echo($select."<br>");
        echo("<pre>"."<br>");
        print_r($r);
        print_r($result);
        echo("</pre>"."<br>");
        
        //Prep information from ours
        $inventoryid = prep($result['invid']);
        $purchase_item_id = prep($result['pid']);
        $new_price = $r['price'] - $r['freight_cost'];
        echo($new_price."<br>");
        
        //Make sure we don't get redundant package numbers.
        if(isset($check_package[$po_number])){
            $check_package[$po_number]++;
        } else {
            $check_package[$po_number] = 1;
        }
        $package_insert = "
        INSERT INTO `packages` (`order_number`, `order_type`, `package_no`, `datetime`, `freight_amount`) VALUES
                            (".prep($po_number).", 'Purchase', ".prep($check_package[$po_number]).", $datetime, ".prep($r['freight_cost']).");";
        if(!$debug){
            qdb($package_insert) or die(qe()." | $package_insert"); 
            $package_id = qid();
        }
        echo($package_insert."<br>");
        $pc_insert = "INSERT INTO `package_contents` (serialid, packageid) VALUES ($inventoryid, $package_id);";
        if($package_id){
            qdb($pc_insert) or die(qe()." | $pc_insert");
        } 
        echo($pc_insert."<br>");
        
        $update = "UPDATE purchase_items SET price = ".prep($new_price)." WHERE id = $purchase_item_id;";
        if(!$debug){qdb($update) or die(qe()." | $update");}
        echo($update."<br>");
        
    }
    
?>