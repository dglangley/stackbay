<?php

    $rootdir = $_SERVER["ROOT_DIR"];
    include_once $rootdir.'/inc/dbconnect.php';
    
    $DATA = array();
    
    $query = "SELECT ro_number, id FROM purchase_requests;";
    $result = qdb($query) OR die(qe() . ' ' . $query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    //print_r($DATA);

    foreach($DATA as $line) {
        if($line['ro_number']) {
            $query = "SELECT id FROM repair_items WHERE ro_number = ".res($line['ro_number']).";";
            $result = qdb($query) OR die(qe().' '.$query);

            if(mysqli_num_rows($result)) {
                $r = mysqli_fetch_assoc($result);
                
                $query = "UPDATE purchase_requests SET item_id = ".res($r['id']).", item_id_label = 'repair_item_id' WHERE id = ".res($line['id']).";";
                echo $query . "<br>";
                qdb($query);
            } else {
                echo 'Failure to find item_id';
            }
        }
    }

    $DATA = array();

    $query = "SELECT ro_number, id FROM repair_components;";
    $result = qdb($query) OR die(qe() . ' ' . $query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    print_r($DATA);

    foreach($DATA as $line) {
         if($line['ro_number']) {
            $query = "SELECT id FROM repair_items WHERE ro_number = ".res($line['ro_number']).";";
            $result = qdb($query) OR die(qe().' '.$query);

            if(mysqli_num_rows($result)) {
                $r = mysqli_fetch_assoc($result);
                
                $query = "UPDATE repair_components SET item_id = ".res($r['id']).", item_id_label = 'repair_item_id' WHERE id = ".res($line['id']).";";
                echo $query . "<br>";
                qdb($query);
            } else {
                echo 'Failure to find item_id';
            }
        }
    }
