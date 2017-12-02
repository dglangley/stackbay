<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

    $PIPE = mysqli_init();
    $PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
    $PIPE->real_connect('db.ven-tel.com', 'andrew', 'venpass01', 'service', '13306');
    if (mysqli_connect_errno($PIPE)) {
        //add error to global array that is outputted to alert modal
        if (isset($ALERTS)) {
            $ALERTS[] = "Failed to connect to the PIPE!";
        } else {
            //die( "Failed to connect to MySQL: " . mysqli_connect_error() );
            echo "<BR><BR><BR><BR><BR>Failed to connect to MySQL: " . mysqli_connect_error(). "<BR><BR>";
        }
    }

    // From getCompany and this says Aaron code so use with caution

    function companyMap($service_companyid,$customer='') {
        $companyid = 0;
		$customer = trim($customer);

        $query = "SELECT companyid FROM company_maps WHERE service_companyid = ".res($service_companyid).";";
        $result = qdb($query) OR die(qe().'<BR>'.$query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);
            $companyid = $r['companyid'];
        } else if ($customer) {
			$query = "SELECT * FROM companies WHERE name = '".res($customer)."'; ";
        	$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$companyid = $r['id'];
			} else {
				$query = "INSERT INTO companies (name) VALUES ('".res($customer)."'); ";
   		     	$result = qdb($query) OR die(qe().'<BR>'.$query);
				$companyid = qid();
			}

			$query = "INSERT INTO company_maps (companyid, service_companyid) VALUES ('".res($companyid)."','".res($service_companyid)."'); ";
        	$result = qdb($query) OR die(qe().'<BR>'.$query);
		}

        return $companyid;
    }

    function mapJob($BDB_jid) {
        $service_item_id = 0;

        $query = "SELECT service_item_id FROM maps_job WHERE BDB_jid = ".res($BDB_jid).";";
        $result = qdb($query) OR die(qe() . '<BR>' . $query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);

            $service_item_id = $r['service_item_id'];
        }

        return $service_item_id;
    }
    
    // Reset data and import code for job materials within the set range
    $DATA = array();
    
    $query = "SELECT * FROM services_jobbulkinventory;";
    $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    // print "<pre>" . print_r($DATA, true) . "</pre>";

    // Import Job Data
    foreach($DATA as $material) {
        if($material['job_id']) {
            // Check if the service_item_id exists else it is not an imported job
            $service_item_id = mapJob($material['job_id']);
            $part = '';
            $partid = 0;

            if ($service_item_id) {
                echo $service_item_id . '<BR>';

                // Within each item get the component info from BDB and check with the current
                $query = "SELECT * FROM services_component WHERE id = ".res($material['component_id']).";";
                $result = qdb($query,'PIPE') OR die(qe('PIPE') . '<BR>' . $query);

                if(mysqli_num_rows($result)) {
                    $r = mysqli_fetch_assoc($result);

                    $part = trim($r['part_number']);

                    // Check if the part exists in the current DB
                    $query = "SELECT * FROM parts WHERE part = ".fres($part).";";
                    $result = qdb($query) OR die(qe() . '<BR>' . $query);

                    if(mysqli_num_rows($result)) {
                        $r2 = mysqli_fetch_assoc($result);

                        $partid = $r2['id'];
                        echo $partid . '<BR><BR>';
                    } else {
                        echo $part . '<BR><BR>';

                        // Insert the part into the parts table
                        $query = "INSERT INTO parts (part, manfid, systemid, description, classification) VALUES (".fres($part)", ".fres()", ".fres()", ".fres()", 'component');";
                        qdb($query) OR die(qe() . '<BR>' . $query);

                        $partid = qid();
                    }

                    // Map in the maps_component table
                    $query = "INSERT INTO maps_component () VALUES (".fres($part)", ".fres()", ".fres()", ".fres()", 'component');";
                    qdb($query) OR die(qe() . '<BR>' . $query);
                }

                // Insert into the materials table
                $query = "INSERT INTO service_materials (service_item_id, datetime, qty, amount, inventoryid) VALUES (".fres($service_item_id).", ".fres().", ".fres($material['required_qty']).", ".fres($material['cost']).",".fres().");";
                qdb($query) OR die(qe() . '<BR>' . $query);
            }
        }
    }

    echo "IMPORT COMPLETE!";
