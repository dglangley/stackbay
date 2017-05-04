<?php

	$rootdir = $_SERVER['ROOT_DIR'];
		
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';

    $brian = "SELECT i.id, serial, location_id, freight_cost, orig_cost, part_number, 
    short_description, heci, clei, modified_date as date, im.name manf, 
    count(*) groupcount, sum(quantity_stock) tqs, count(il.id) tcount 
    FROM inventory_itemlocation il, inventory_inventory i, inventory_manufacturer im 
    where il.inventory_id = i.id  AND i.manufacturer_id_id = im.id 
    GROUP BY part_number, heci, manf;";
    $result = qdb($brian,"PIPE") or die(qe("PIPE")." $brian");
    $count = 0;
    echo("<pre>");
    foreach ($result as $row) {
        $part = $row['part_number'];
        $heci = $row['heci'];
        $manf = getManf($row['manf'],"name", "id");
        $partid = getPartID($part, $heci, $manf);
        if(!$partid){
            print_r($row);
            $count++;
        }
    }
    echo("</pre>");
    echo("<br>Count: $count");

?>