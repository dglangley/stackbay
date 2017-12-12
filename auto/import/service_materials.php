<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';

/*
    $SVCS_PIPE = mysqli_init();
    $SVCS_PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
    $SVCS_PIPE->real_connect('db.ven-tel.com', 'andrew', 'venpass01', 'service', '13306');
    if (mysqli_connect_errno($SVCS_PIPE)) {
        //add error to global array that is outputted to alert modal
        if (isset($ALERTS)) {
            $ALERTS[] = "Failed to connect to the SVCS_PIPE!";
        } else {
            //die( "Failed to connect to MySQL: " . mysqli_connect_error() );
            echo "<BR><BR><BR><BR><BR>Failed to connect to MySQL: " . mysqli_connect_error(). "<BR><BR>";
        }
    }
*/

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

	$query = "DELETE FROM parts WHERE id IN (SELECT partid FROM maps_component) AND classification = 'material'; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "DELETE FROM maps_component; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "DELETE FROM inventory WHERE notes = 'Services Import'; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "DELETE FROM inventory_costs WHERE notes = 'Services Import'; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "DELETE FROM service_materials; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);

	$query = "SELECT po_number FROM maps_PO, purchase_items WHERE purchase_item_id = purchase_items.id GROUP BY po_number; ";
    $result = qdb($query) OR die(qe().'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$query2 = "DELETE FROM purchase_items WHERE po_number = '".$r['po_number']."'; ";
    	$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);

		$query2 = "DELETE FROM purchase_orders WHERE po_number = '".$r['po_number']."'; ";
    	$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
	}



    $query = "SELECT * FROM services_jobbulkinventory WHERE job_id IS NOT NULL;";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);
    while($material = mysqli_fetch_assoc($result)) {
        if(! $material['job_id']) { continue; }

		// Check if the service_item_id exists else it is not an imported job
		$service_item_id = mapJob($material['job_id']);
		$part = '';
		$partid = 0;
		$purchase_item_id = 0;

		if (! $service_item_id) { continue; }

//              echo $service_item_id . '<BR>';

                // Within each item get the component info from BDB and check with the current
                $query = "SELECT * FROM services_component WHERE id = ".res($material['component_id']).";";
//				echo $query.'<BR>';
                $result2 = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE') . '<BR>' . $query);

                if(mysqli_num_rows($result2)==0) { continue; }



                    $r = mysqli_fetch_assoc($result2);

                    $part = utf8_encode(trim($r['part_number']));
                    $manf = '';
                    $partid = 0;
                    $manfid = 0;

                    $BDB_partid = trim($r['id']);
                    $BDB_manfid = trim($r['manufacturer_id']);

					$partid = getPartId($part);

					if ($partid) {
//                        echo $partid . '<BR><BR>';
                    } else {
//                        echo $part . '<BR><BR>';
//                        echo $r['description'] . '<BR><BR>';
                    
                        // Get the manf name from BDB
                        if($BDB_manfid) {
							$query3 = "SELECT manfid FROM maps_manf WHERE BDB_manfid = '".res($r['manufacturer_id'])."'; ";
                            $result3 = qdb($query3) OR die(qe() . '<BR>' . $query3);
							if (mysqli_num_rows($result3)>0) {
								$r3 = mysqli_fetch_assoc($result3);
								$manfid = $r3['manfid'];
							} else {
	                        	$query = "SELECT * FROM services_manufacturer WHERE id = ".res($r['manufacturer_id']).";";
                            	$result3 = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE') . '<BR>' . $query);

                                if(mysqli_num_rows($result3)) {
                                    $r3 = mysqli_fetch_assoc($result3);
                                    $manf = trim($r3['name']);
                                }

                        		// We now need the manufacturer imported
                        		// Check to see if the manufacturer already exists
                        		// No need to import MANF if the part already exists to save time
								if ($manf) {
	                        		$query = "SELECT * FROM manfs WHERE name = '".res($manf)."';";
   	                     			$result4 = qdb($query) OR die(qe() .'<BR>'.$query);
	                        		if(mysqli_num_rows($result4)) {
                            			$r4 = mysqli_fetch_assoc($result4);
                            			$manfid = $r4['id'];
									} else {
                            			// Manf does not exist so add it
                            			$query = "INSERT INTO manfs (name) VALUES ('".res($manf)."');";
                            			qdb($query) OR die(qe() .'<BR>'.$query);
                            			$manfid = qid();

                            			// Map into maps_manf
                            			$query = "INSERT INTO maps_manf (BDB_manfid, manfid) VALUES (".res($BDB_manfid).", ".res($manfid).");";
                            			qdb($query) OR die(qe() . '<BR>' . $query);
									}
								}
							}
                        }

                        // Insert the part into the parts table
                        $query = "INSERT INTO parts (part, manfid, systemid, description, classification) VALUES ('".res($part)."', ".fres($manfid).", NULL, ".fres(utf8_encode($r['description'])).", 'material');";
                        qdb($query) OR die(qe() . '<BR>' . $query);

                        $partid = qid();

                    	// Map in the maps_component table
                    	$query = "INSERT INTO maps_component (BDB_cid, partid) VALUES (".fres($BDB_partid).", ".fres($partid).");";
echo $query.'<BR>';
                    	qdb($query) OR die(qe() . '<BR>' . $query);
                    }

                    // Generate the purchase order
                    if($material['po_id']) {
                        // Get the purchase order information
                        $query = "SELECT * FROM services_jobmaterialpo WHERE id = ".res($material['po_id']).";";
                        $result5 = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE') . '<BR>' . $query);

                        if(mysqli_num_rows($result5)) {
                            $r5 = mysqli_fetch_assoc($result5);

                            // All PO seemed to be created by Sam Sabedra
                            $query = "INSERT INTO purchase_orders (created, created_by, sales_rep_id, companyid, contactid, assoc_order, remit_to_id, ship_to_id, freight_carrier_id, freight_services_id, freight_account_id, termsid, public_notes, private_notes, status) VALUES ('".res($r5['po_date'])."', '13', '13', ".res(companyMap($r5['vendor_id'])).", NULL, NULL, NULL, NULL, '1', '1', NULL, ".mapTerms($r5['po_terms_id']).", NULL, NULL, 'Active');";
echo $query.'<BR>';
                            qdb($query) OR die(qe() . '<BR>' . $query);

                            $purchase_order = qid();

                            $query = "INSERT INTO purchase_items (partid, po_number, line_number, qty, qty_received, receive_date, ref_1, ref_1_label, price) VALUES (".res($partid).", ".res($purchase_order).", NULL,".res($material['received_quantity']).", ".res($material['received_quantity']).", '".$GLOBALS['now']."', ".fres($service_item_id).", 'service_item_id' , '".res($material['sale_price'])."');";
                            qdb($query) OR die(qe() . '<BR>' . $query);

                            $purchase_item_id = qid();

                            $query = "INSERT INTO maps_PO (BDB_poid, purchase_item_id) VALUES (".res($material['po_id']).", ".res($purchase_item_id).");";
echo $query.'<BR>';
                            qdb($query) OR die(qe() . '<BR>' . $query);
                        }
                    }

                    // BDB has a lot of 0 qty items so avoid that too
                    if($material['received_quantity'] != 0) {
	                    // Insert into Inventory
                        $query = "INSERT INTO inventory (serial_no, qty, partid, conditionid, status, locationid, userid, date_created, purchase_item_id, notes) VALUES (NULL, ".res($material['received_quantity']).", ".res($partid).", '2', 'installed', '149', '13', '".$GLOBALS['now']."', ".fres($purchase_item_id).", 'Services Import');";
echo $query.'<BR>';
                        qdb($query) OR die(qe() . '<BR>' . $query);
                        $inventory_id = qid();

                        // Insert into the materials table
                        $query = "INSERT INTO service_materials (service_item_id, datetime, qty, inventoryid) ";
						$query .= "VALUES (".fres($service_item_id).", NULL, ".fres($material['required_quantity']).", ".fres($inventory_id).");";
echo $query.'<BR>';
                        qdb($query) OR die(qe() . '<BR>' . $query);

						if ($material['cost']>0) {
							$query = "INSERT INTO inventory_costs (inventoryid, actual, notes) ";
							$query .= "VALUES ('".$inventory_id."', '".res($material['cost'])."', 'Services Import'); ";
echo $query.'<BR>';
                        	qdb($query) OR die(qe() . '<BR>' . $query);
						}
                    }
    }

    echo "IMPORT COMPLETE!";
