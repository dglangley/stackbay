<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/indexer.php';

    $query = "DELETE FROM parts WHERE id IN (SELECT partid FROM maps_component) AND classification = 'material'; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);

    $query = "DELETE FROM maps_component; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);



    // Grab everything service component
    $query = "SELECT * FROM services_component;";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($material = mysqli_fetch_assoc($result)) {
        // Reset service_item_id
        $partid = 0;
        $part = '';

        $part = utf8_encode(trim($material['part_number']));
        $manf = '';
        $partid = 0;
        $manfid = 0;

        $BDB_partid = trim($material['id']);
        $BDB_manfid = trim($material['manufacturer_id']);

        $partid = getPartId($part);

        if ($partid) {
                           echo $partid . '<BR><BR>';
        } else {
            // Get the manf name from BDB
            if($BDB_manfid) {
                $query3 = "SELECT manfid FROM maps_manf WHERE BDB_manfid = '".res($material['manufacturer_id'])."'; ";
                $result3 = qedb($query3);
                if (mysqli_num_rows($result3)>0) {
                    $r3 = mysqli_fetch_assoc($result3);
                    $manfid = $r3['manfid'];
                } else {
                    // $query = "SELECT * FROM services_manufacturer WHERE id = ".res($material['manufacturer_id']).";";
                    // $result3 = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE') . '<BR>' . $query);

                    if(! empty($material['service_manf_id'])) {
                        // $r3 = mysqli_fetch_assoc($result3);
                        $manf = trim($material['name']);
                    }

                    // We now need the manufacturer imported
                    // Check to see if the manufacturer already exists
                    // No need to import MANF if the part already exists to save time
                    if ($manf) {
                        $query = "SELECT * FROM manfs WHERE name = '".res($manf)."';";
                        $result4 = qedb($query);

                        if(mysqli_num_rows($result4)) {
                            $r4 = mysqli_fetch_assoc($result4);
                            $manfid = $r4['id'];
                        } else {
                            // Manf does not exist so add it
                            $query = "INSERT INTO manfs (name) VALUES ('".res($manf)."');";
                            qedb($query);
                            $manfid = qid();

                            // Map into maps_manf
                            $query = "INSERT INTO maps_manf (BDB_manfid, manfid) VALUES (".res($BDB_manfid).", ".res($manfid).");";
                            qedb($query);
                        }
                    }
                }
            }

            // Insert the part into the parts table
            $query = "INSERT INTO parts (part, manfid, systemid, description, classification) VALUES ('".res($part)."', ".fres($manfid).", NULL, ".fres(utf8_encode($r['description'])).", 'material');";
            qedb($query);

            $partid = qid();
            indexer($partid,'id');

            // Map in the maps_component table
            $query = "INSERT INTO maps_component (BDB_cid, partid) VALUES (".fres($BDB_partid).", ".fres($partid).");";
            echo $query.'<BR>';
            qedb($query);
        }
    }

    echo "IMPORT COMPLETE!";
