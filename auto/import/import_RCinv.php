<?php
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/keywords.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/setInventory.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/indexer.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getManf.php';
    
    exit;

    $DEBUG = 1;

    function getLocationID($place, $instance) {
        $locationid = 0;

        $query = "SELECT id FROM locations WHERE place = ".fres($place)." AND instance = ".fres($instance).";";
        $result = qedb($query);

        if(mysqli_num_rows($result) > 0) {
            $r = mysqli_fetch_assoc($result);

            $locationid = $r['id'];
        }

        return $locationid;
    }

    function searchHeci($H, $field, $value) {
       foreach($H as $partid => $r) {
          if ( $r[$field] === $value ) {
             return $partid;
          }
       }
       return false;
    }

    // Set the comma seperated file here
    $file = "RC_inventory.csv";

    $csv = array_map('str_getcsv', file($file));
    array_walk($csv, function(&$a) use ($csv) {
      $a = array_combine($csv[0], $a);
    });

    array_shift($csv); # remove column header

    // print "<pre>" . print_r($csv, true) . "</pre>";
// exit;

	foreach($csv as $r) {
        $partid = 0;
        // Using the straight way
        // $query = "SELECT id FROM parts WHERE part = ".fres($r['Part']).";";
        // $result = qedb($query);

        // if(mysqli_num_rows($result) > 0) {
        //     $r = mysqli_fetch_assoc($result);

        //     $partid = $r['id'];
        // } else {
        //     echo $r['Part'] . ' not found in the system.<BR>';

        //     $manf = getManf($r['Manufacturer'], 'name', 'id');
        //     $classification = "material";

        //     // Generate the part in the system
        //     $query = "INSERT INTO parts (part, heci, manfid, systemid, description, classification) VALUES (".fres($r['Part']).", ".fres($r['Heci']).", ".res($manf).", ".fres($r['Description']).", ".fres($classification).");";
        //     qedb($query);
        //     $partid = qid();

        //     // Index the new part
        //     indexer($partid,'id');
        // }

        $H = hecidb($r['Part'], 'part');

        if(! $H) {
            echo $r['Part'] . ' not found in the system.<BR>';

            $manf = getManf($r['Manufacturer'], 'name', 'id');
            $classification = "material";

            // Generate the part in the system
            $query = "INSERT INTO parts (part, heci, manfid, systemid, description, classification) VALUES (".fres($r['Part']).", ".fres($r['Heci']).", ".res($manf).", ".fres($r['Description']).", ".fres($classification).");";
            qedb($query);
            $partid = qid();

            // Index the new part
            indexer($partid,'id');
        } else { 
            if(count($H) > 1) {
                $partid = searchHeci($H, 'part', $r['Part']);
            } 

            if(! $partid) {
                $partid = reset($H)['id'];
            }

            echo $r['Part'].' => '.$H[$partid]['part'] . '<BR>';
        }

        $conditionid = 1; // set to new

        $location_str = $r['Location'];
        $location_data = explode("-", $location_str);
        $prefix = "RC-";

        $locationid = getLocationID($prefix.$location_data[0], $location_data[1]);

        // Add the item into the inventory using setInventory;
        $I = array('qty'=>$r['Qty'],'partid'=>$partid,'conditionid'=>$conditionid,'status'=>'received','locationid'=>$locationid);
        $inventoryid = setInventory($I);

        echo '<BR><BR>';
    }
?>
