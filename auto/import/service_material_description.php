<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

    $query = "SELECT * FROM services_component;";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        // Grab the partid from the maps table
        $query2 = "SELECT * FROM maps_component WHERE BDB_cid = ".fres($r['id']).";";
        $result2 = qedb($query2);

        if(mysqli_num_rows($result2)) {
            $r2 = mysqli_fetch_assoc($result2);

            $partid = $r2['partid'];

            // Check if the part has a description or not
            // adding classification as a fail safe
            $query3 = "SELECT * FROM parts WHERE id = ".fres($partid)." AND description IS NULL AND classification = 'material';";
            $result3 = qedb($query3);

            if(mysqli_num_rows($result3)) {
                // Update the missing description
                $query4 = "UPDATE parts SET description = ".fres(utf8_encode(trim($r['description'])))." WHERE id = ".fres($partid).";";
                qedb($query4);

                echo $query4 . "<BR><BR>";
            }
        }
    }

    echo "IMPORT COMPLETE!";
