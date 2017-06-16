<?php

	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/import_aid.php';
    
    qdb("DELETE FROM `purchase_items` WHERE line_number = 999;");
    qdb("DELETE FROM `purchase_orders` WHERE private_notes = 'Component History Import';");
    qdb("TRUNCATE `purchase_requests`;");
    qdb("DELETE FROM `inventory` WHERE `notes` = 'IMPORTED ON COMPONENTS IMPORT';");
    qdb("TRUNCATE repair_components;");
    qdb("TRUNCATE repair_orders;");
    qdb("TRUNCATE repair_items;");
    qdb("TRUNCATE TABLE `parts_component_map`;");
    qdb("DELETE FROM `parts` WHERE `classification` = 'component'");
    
    $component_query = "SELECT * FROM inventory_component;";
    $result = qdb($component_query,"PIPE") or die(qe("PIPE")." | $component_query");
    $i = 0;
    foreach($result as $r){
        $partid = "";
        //Check to see if we already have the part by part_number or description
        $check = "SELECT * FROM parts WHERE part LIKE ".prep($r['part_number']).";";
        $check_result = qdb($check) or die(qe()." | $check");
        if(mysqli_num_rows($check_result)){
            $check_result = mysqli_fetch_assoc($check_result);
            $partid = $check_result['id'];
        } else {
            $insert = "
            INSERT INTO `parts` (`part`,`description`,`classification`) 
            VALUES (".prep($r['part_number']).", ".prep($r['description']).",'component');
            ";
            qdb($insert) or die(qe()." | $insert");
            $partid = qid();
        }
        print_r($r);
        echo("<br>");
        $pcm_insert = "INSERT INTO parts_component_map (`partid`,`componentid`) VALUES (".prep($partid).", ".prep($r['id']).");";
        qdb($pcm_insert) or die(qe());
    }
?>