<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/mapJob.php';

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

    function mapTerms($termid) {
        // Just straight map it manually because there isn't a lot of records
        $terms = array(1 => '10', 2 => '6', 3 => '12', 4 => '4', 5 => '14', 6 => '13', 7 => '7', 8 => '3', 9 => '2', 10 => '11', 11 => '8', 12 => '1', 13 => '1', 14 => '9');

        return $terms[$termid];
    }

    function companyMap($service_companyid,$customer='') {
        $companyid = 0;
        $customer = trim($customer);

        $query = "SELECT companyid FROM company_maps WHERE service_companyid = ".res($service_companyid).";";
        $result = qdb($query) OR die(qe().'<BR>'.$query);

        //echo $query . '<BR><BR>';

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
    
    // Reset data and import code for job materials within the set range
    $DATA = array();
    
    $query = "SELECT * FROM services_jobbulkinventory WHERE job_id IS NOT NULL;";
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
            $purchase_item_id = 0;

            if ($service_item_id) {
                echo $service_item_id . '<BR>';

                // Within each item get the component info from BDB and check with the current
                $query = "SELECT * FROM services_component WHERE id = ".res($material['component_id']).";";
                $result = qdb($query,'PIPE') OR die(qe('PIPE') . '<BR>' . $query);

                if(mysqli_num_rows($result)) {
                    $r = mysqli_fetch_assoc($result);

                    $part = utf8_encode(trim($r['part_number']));
                    $manf = '';
                    $partid = 0;
                    $manfid = 0;

                    $BDB_partid = trim($r['id']);
                    $BDB_manfid = trim($r['manufacturer_id']);
                    
                    // Get the manf name from BDB
                    if($BDB_manfid) {
                        $query = "SELECT * FROM services_manufacturer WHERE id = ".res($r['manufacturer_id']).";";
                        $result = qdb($query,'PIPE') OR die(qe('PIPE') . '<BR>' . $query);

                        if(mysqli_num_rows($result)) {
                            $r3 = mysqli_fetch_assoc($result);
                            $manf = trim($r3['name']);
                        }
                    }

                    // Check if the part exists in the current DB
                    $query = "SELECT * FROM parts WHERE part RLIKE ".fres($part).";";
                    $result = qdb($query) OR die(qe() . '<BR>' . $query);

                    if(mysqli_num_rows($result)) {
                        $r2 = mysqli_fetch_assoc($result);
                        $partid = $r2['id'];
                        echo $partid . '<BR><BR>';
                    } else {
                        echo $part . '<BR><BR>';
                        echo $r['description'] . '<BR><BR>';

                        // We now need the manufacturer imported
                        // Check to see if the manufacturer already exists
                        // No need to import MANF if the part already exists to save time
                        $query = "SELECT * FROM manfs WHERE name RLIKE ".fres($manf).";";
                        $result = qdb($query) OR die(qe() .'<BR>'.$query);

                        if(mysqli_num_rows($result)) {
                            $r4 = mysqli_fetch_assoc($result);
                            $manfid = $r4['id'];

                        } else if($manf) {
                            // Manf does not exist so add it
                            $query = "INSERT INTO manfs (name) VALUES (".fres($manf).");";
                            qdb($query) OR die(qe() .'<BR>'.$query);

                            $manfid = qid();

                            // Map into maps_manf
                            $query = "INSERT INTO maps_manf (BDB_manfid, manfid) VALUES (".res($BDB_manfid).", ".res($manfid).");";
                            qdb($query) OR die(qe() . '<BR>' . $query);
                        }

                        // Insert the part into the parts table
                        $query = "INSERT INTO parts (part, manfid, systemid, description, classification) VALUES ('".res($part)."', ".fres($manfid).", NULL, ".fres(utf8_encode($r['description'])).", 'component');";
                        qdb($query) OR die(qe() . '<BR>' . $query);

                        $partid = qid();
                    }

                    // Map in the maps_component table
                    $query = "INSERT INTO maps_component (BDB_cid, partid) VALUES (".fres($BDB_partid).", ".fres($partid).");";
                    qdb($query) OR die(qe() . '<BR>' . $query);

                    // Generate the purchase order
                    if($material['po_id']) {
                        // Get the purchase order information
                        $query = "SELECT * FROM services_jobmaterialpo WHERE id = ".res($material['po_id']).";";
                        $result = qdb($query,'PIPE') OR die(qe('PIPE') . '<BR>' . $query);

                        if(mysqli_num_rows($result)) {
                            $r5 = mysqli_fetch_assoc($result);

                            // All PO seemed to be created by Sam Sabedra
                            $query = "INSERT INTO purchase_orders (created, created_by, sales_rep_id, companyid, contactid, assoc_order, remit_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, status) VALUES ('".res($r5['po_date'])."', '13', '13', ".res(companyMap($r5['vendor_id'])).", NULL, NULL, NULL, NULL, '1', '1', NULL, ".mapTerms($r5['po_terms_id']).", NULL, NULL, 'Active');";
                            qdb($query) OR die(qe() . '<BR>' . $query);

                            $purchase_order = qid();

                            $query = "INSERT INTO purchase_items (partid, po_number, line_number, qty, qty_received, receive_date, ref_1, ref_1_label) VALUES (".res($partid).", ".res($purchase_order).", NULL,".res($material['received_quantity']).", ".res($material['received_quantity']).", '".$GLOBALS['now']."', ".fres($service_item_id).", 'service_item_id');";
                            qdb($query) OR die(qe() . '<BR>' . $query);

                            $purchase_item_id = qid();

                            $query = "INSERT INTO maps_PO (BDB_poid, purchase_item_id) VALUES (".res($material['po_id']).", ".res($purchase_item_id).");";
                            qdb($query) OR die(qe() . '<BR>' . $query);
                        }
                    }

                    // Insert into Inventory
                    $query = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, userid, date_created, purchase_item_id) VALUES (NULL, ".res($material['received_quantity']).", ".res($partid).", '2', 'installed', '149', '13', '".$GLOBALS['now']."', ".res($purchase_item_id).");";
                    qdb($query) OR die(qe() . '<BR>' . $query);

                    $inventory_id = qid();

                    // Insert into the materials table
                    $query = "INSERT INTO service_materials (service_item_id, datetime, qty, amount, inventoryid) VALUES (".fres($service_item_id).", NULL, ".fres($material['required_qty']).", ".fres($material['cost']).",".fres($inventory_id).");";
                    qdb($query) OR die(qe() . '<BR>' . $query);
                }
            }
        }
    }

    echo "IMPORT COMPLETE!";
