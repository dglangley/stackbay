<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/indexer.php';

	$DEBUG = 1;

    function mapTerms($termid) {
        // Just straight map it manually because there isn't a lot of records
        $terms = array(1 => '10', 2 => '6', 3 => '12', 4 => '4', 5 => '14', 6 => '13', 7 => '7', 8 => '3', 9 => '2', 10 => '11', 11 => '8', 12 => '1', 13 => '1', 14 => '9');

        return $terms[$termid];
    }

    function companyMap($service_companyid,$customer='') {
        $companyid = 0;
        $customer = trim($customer);

        $query = "SELECT companyid FROM company_maps WHERE service_companyid = ".res($service_companyid).";";
        $result = qedb($query);

        //echo $query . '<BR><BR>';

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);
            $companyid = $r['companyid'];
        } else if ($customer) {
            $query = "SELECT * FROM companies WHERE name = '".res($customer)."'; ";
            $result = qedb($query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $companyid = $r['id'];
            } else {
                $query = "INSERT INTO companies (name) VALUES ('".res($customer)."'); ";
                $result = qedb($query);
                $companyid = qid();
            }

            $query = "INSERT INTO company_maps (companyid, service_companyid) VALUES ('".res($companyid)."','".res($service_companyid)."'); ";
            $result = qedb($query);
        }

        return $companyid;
    }

/*
    $query = "DELETE FROM inventory WHERE notes = 'Services Import'; ";
    $result = qedb($query);

    $query = "DELETE FROM inventory_costs WHERE notes = 'Services Import'; ";
    $result = qedb($query);

    $query = "DELETE FROM purchase_requests WHERE notes = 'Services Import'; ";
    $result = qedb($query);

    $query = "TRUNCATE service_materials; ";
    $result = qedb($query);

    $query = "SELECT po_number FROM maps_PO, purchase_items WHERE purchase_item_id = purchase_items.id GROUP BY po_number; ";
    $result = qedb($query);
    while ($r = mysqli_fetch_assoc($result)) {
        $query2 = "DELETE FROM purchase_items WHERE po_number = '".$r['po_number']."'; ";
        $result2 = qedb($query2);

        $query2 = "DELETE FROM purchase_orders WHERE po_number = '".$r['po_number']."'; ";
        $result2 = qedb($query2);
    }

    $query = "TRUNCATE maps_PO; ";
    $result = qedb($query);
*/


    // Grab only records with a valid job_id and co_id
    $query = "SELECT *, sc.id as service_component_id, manf.id as service_manf_id, purchase.id as purchase_id FROM services_jobbulkinventory inventory ";
    $query .= "LEFT JOIN services_component sc ON sc.id = inventory.component_id ";
    $query .= "LEFT JOIN services_manufacturer manf ON manf.id = sc.manufacturer_id ";
    $query .= "LEFT JOIN services_jobmaterialpo purchase ON purchase.id = inventory.po_id ";
    $query .= "WHERE job_id IS NOT NULL ";
$query .= "AND co_id = 199 ";
//	$query .= "AND po_id = 357471 ";
	$query .= "; ";
    $result = qedb($query,'SVCS_PIPE');

    while($material = mysqli_fetch_assoc($result)) {
        // Reset service_item_id
        $service_item_id = 0;

        if(! $material['job_id']) { continue; }

        // Check if the service_item_id exists else it is not an imported job
        // But instead use the maps_job_co table

        if($material['co_id']) {
            $service_item_id = mapJob($material['co_id'], 'co');
        } else {
            $service_item_id = mapJob($material['job_id']);
        }

        $part = '';
        $partid = 0;
        $purchase_item_id = 0;

		if ($DEBUG) { echo 'item '.$service_item_id . '<BR>'; }
        if (! $service_item_id) { continue; }


            // Within each item get the component info from BDB and check with the current
//                 $query = "SELECT * FROM services_component WHERE id = ".res($material['component_id']).";";
// //               echo $query.'<BR>';
//                 $result2 = qedb($query,'SVCS_PIPE');

            // replaces if no $result2
            if(! $material['service_component_id']) { continue; }



                //$r = mysqli_fetch_assoc($result2);

                $part = utf8_encode(trim($material['part_number']));
                $manf = '';
                $partid = 0;
                $manfid = 0;

                $BDB_partid = trim($material['component_id']);
                $BDB_manfid = trim($material['manufacturer_id']);

                $partid = getPartId($part);

                if ($partid) {
//                        echo $partid . '<BR><BR>';
                } else {

                    // If Parts Component import was done correctly this should never be ran
                    // Found issue with part Misc Material causing issues
                    // Check component map to see if it has already been mapped
                    $query2 = "SELECT partid FROM maps_component WHERE BDB_cid = ".fres($material['service_component_id']).";";
                    $result2 = qedb($query2);

                    if(mysqli_num_rows($result2)) {
                        $r2 = mysqli_fetch_assoc($result2);
                        $partid = $r2['partid'];

//                        echo $partid . '<BR><BR>';
                    } else {

                        die("I failed on: " . $part . ' ' . $material['service_component_id']);
    //                        echo $part . '<BR><BR>';
    //                        echo $r['description'] . '<BR><BR>';
                    
                        // Get the manf name from BDB
                        if($BDB_manfid) {
                            $query3 = "SELECT manfid FROM maps_manf WHERE BDB_manfid = '".res($material['manufacturer_id'])."'; ";
                            $result3 = qedb($query3);
                            if (mysqli_num_rows($result3)>0) {
                                $r3 = mysqli_fetch_assoc($result3);
                                $manfid = $r3['manfid'];
                            } else {
                                // $query = "SELECT * FROM services_manufacturer WHERE id = ".res($material['manufacturer_id']).";";
                                // $result3 = qedb($query,'SVCS_PIPE');

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
                        qedb($query);
                    }
                }

                    // Generate the purchase order
                    if($material['po_id']) {
                        // Get the purchase order information
                        // $query = "SELECT * FROM services_jobmaterialpo WHERE id = ".res($material['po_id']).";";
                        // $result5 = qedb($query,'SVCS_PIPE');

                        // if(mysqli_num_rows($result5)) {
                        //     $r5 = mysqli_fetch_assoc($result5);

                        // All PO seemed to be created by Sam Sabedra
                        $query = "INSERT INTO purchase_orders (created, created_by, sales_rep_id, companyid, contactid, assoc_order, remit_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, status) VALUES ('".res($material['po_date'])."', '13', '13', ".res(companyMap($material['vendor_id'])).", NULL, NULL, NULL, NULL, '1', '1', NULL, ".mapTerms($material['po_terms_id']).", NULL, NULL, 'Active');";
                        qedb($query);
                        $purchase_order = qid();

                        $query = "INSERT INTO purchase_items (partid, po_number, line_number, qty, qty_received, receive_date, ref_1, ref_1_label, price) VALUES (".res($partid).", ".res($purchase_order).", NULL,".res($material['received_quantity']).", ".res($material['received_quantity']).", '".$GLOBALS['now']."', ".fres($service_item_id).", 'service_item_id' , '".res($material['sale_price'])."');";
                        qedb($query);

                        $purchase_item_id = qid();

                        $query = "INSERT INTO maps_PO (BDB_poid, purchase_item_id) VALUES (".res($material['po_id']).", ".res($purchase_item_id).");";
                        qedb($query);

						$query = "INSERT INTO purchase_requests (techid, ro_number, item_id, item_id_label, repid, requested, po_number, partid, qty, notes, status) ";
						$query .= "VALUES (0, NULL, ".fres($service_item_id).", 'service_item_id', NULL, '".res($material['po_date'])."', '".res($purchase_order)."', '".res($partid)."', ";
						$query .= fres($material['required_quantity']).", 'Services Import', NULL); ";
						qedb($query);
                        // }
                    }

                    // BDB has a lot of 0 qty items so avoid that too
                    if($material['received_quantity'] != 0) {
                        // Insert into Inventory
                        $query = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, userid, date_created, purchase_item_id, notes) VALUES (NULL, ".res($material['received_quantity']).", ".res($partid).", '2', 'installed', '149', '13', '".$GLOBALS['now']."', ".fres($purchase_item_id).", 'Services Import');";
                        qedb($query);
                        $inventory_id = qid();

                        // Insert into the materials table
                        $query = "INSERT INTO service_materials (service_item_id, datetime, qty, inventoryid) ";
                        $query .= "VALUES (".fres($service_item_id).", NULL, ".fres($material['required_quantity']).", ".fres($inventory_id).");";
                        qedb($query);

                        if ($material['cost']>0) {
                            $query = "INSERT INTO inventory_costs (inventoryid, actual, notes) ";
                            $query .= "VALUES ('".$inventory_id."', '".res($material['cost'])."', 'Services Import'); ";
                            qedb($query);
                        }
                    }
    }

    echo "IMPORT COMPLETE!";
