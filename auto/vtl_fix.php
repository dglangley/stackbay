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

    $brian = "SELECT i.id, cost, serial, location_id, freight_cost, orig_cost, part_number,
    short_description, heci, clei, modified_date as date, 
    count(*) groupcount, sum(quantity_stock) tqs, count(il.id) tcount
    FROM inventory_itemlocation il, inventory_inventory i
    where il.inventory_id = i.id and (serial = '000' or serial = '0')
    GROUP BY part_number, heci order by cost desc;";
    $result = qdb($brian,"PIPE") or die(qe("PIPE")." $brian");
    $count = 0;
    echo("<pre>");
    foreach ($result as $row) {
        $part = $row['part_number'];
        $heci = $row['heci'];
        $partid = getPartId($part);//, $heci, $manf);
        if(!$partid){
            print_r($row);
            $count++;
        }
    }
    echo("</pre>");
    echo("<br>Count: $count");

?>
