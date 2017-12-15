<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

    // $SVCS_PIPE = mysqli_init();
    // $SVCS_PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
    // $SVCS_PIPE->real_connect('db.ven-tel.com', 'andrew', 'venpass01', 'service', '13306');
    // if (mysqli_connect_errno($SVCS_PIPE)) {
    //     //add error to global array that is outputted to alert modal
    //     if (isset($ALERTS)) {
    //         $ALERTS[] = "Failed to connect to the SVCS_PIPE!";
    //     } else {
    //         //die( "Failed to connect to MySQL: " . mysqli_connect_error() );
    //         echo "<BR><BR><BR><BR><BR>Failed to connect to MySQL: " . mysqli_connect_error(). "<BR><BR>";
    //     }
    // }

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

    // function mapJob($BDB_jid) {
    //     $service_item_id = 0;

    //     $query = "SELECT service_item_id FROM maps_job WHERE BDB_jid = ".res($BDB_jid).";";
    //     $result = qdb($query) OR die(qe() . '<BR>' . $query);

    //     if(mysqli_num_rows($result)) {
    //         $r = mysqli_fetch_assoc($result);

    //         $service_item_id = $r['service_item_id'];
    //     }

    //     return $service_item_id;
    // }
    
    // Reset data and import code for jobs within the set range
    $DATA = array();
    
    $query = "SELECT * FROM services_jobquoteco WHERE approved = 1;";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    //print "<pre>" . print_r($DATA, true) . "</pre>";

    // Import Job Data
    foreach($DATA as $service) {
        // Get the Original job_id
        $query = "SELECT * FROM services_jobquote WHERE id = ".res($service['quote_id'])." AND job_id IS NOT NULL;";
        $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

        if(mysqli_num_rows($result)) {
            $r2 = mysqli_fetch_assoc($result); 
            $job_id = $r2['job_id'];

            $service_item_id = mapJob($job_id);
        }

        // There must be an original order in order to create an ICO or CCO
        if($service_item_id) {
            // Set variables being used for service orders
            $classid;

            // This means we will see it as ICO
            $cco = false;

            if($service['cco']) {
                $cco = true;
            }

            // From service_item_id get all the info needed to create the CCO or ICO
            $query = "SELECT *, si.so_number as order_number FROM service_items si, service_orders so WHERE so.so_number = si.so_number AND si.id = ".res($service_item_id).";";
            $result = qdb($query) OR die(qe().'<BR>'.$query);


            if(mysqli_num_rows($result)) {
                $r3 = mysqli_fetch_assoc($result); 


                $companyid = $r3['companyid'];
                $contactid = $r3['contactid'];
                $cust_ref = $r3['cus_ref'];
                $ref_ln = $r3['ref_ln'];
                $userid = $r3['userid'];
                $datetime = $r3['datetime'];
                $addy_id = $r3['item_id'];
                $bill_to_id;
                $termsid;
                $public_notes = $service['site_access_info_address'];;
                $private_notes;
                $status = 'Active';

                if($service['terms_id']) {
                    // Convert the BDB terms to our terms
                    $query = "SELECT invoice_days FROM services_terms WHERE id = ".res($service['terms_id']).";";
                    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

                    if(mysqli_num_rows($result)) {
                        $r = mysqli_fetch_assoc($result);

                        // Find the exact matching days in our database
                        $query = "SELECT id FROM terms WHERE days = ".fres(trim($r['invoice_days'])).";";
                        $result = qdb($query) OR die(qe().'<BR>'.$query);

                        if(mysqli_num_rows($result)) {
                            $r = mysqli_fetch_assoc($result);
                            $termsid = $r['id'];
                        } else {
                            $termsid = 15; // Also Known as N/A
                        }

                    }
                }

                // Set variables being used for service items
                // $task_name = $service['job_no'];
                $so_number = $r3['order_number'];

                $line_number = 1;

                // Get the max line_number
                $query = " SELECT MAX(line_number) as max_line FROM service_items WHERE so_number = ".res($so_number).";";
                $result = qedb($query);

                if(mysqli_num_rows($result)) {
                    $r6 = mysqli_fetch_assoc($result); 
                    $line_number = $r6['max_line'] + 1;
                }
 
                $qty = 1;
                $amount = $service['quote_labor'] + $service['quote_engineering'];
                $mileage_rate = $service['mileage_rate'];

                // Descr as scope
                $description = $service['scope'];

                // Variables needed from both analysis
                // If the job name has FFR in it then I figure the classid must be for FFR
                if(strpos($task_name, 'FFR') !== false) {
                    $classid = 3; // 3 = FFR , 2 = Installation , 1 = Repair 
                } else {
                    $classid = 2; // Repair is never used here
                }

                // Enable this to generate a new order for ICO orders

                // if(! $cco) {
                // // Insert into Service Orders
                //     $query = "INSERT INTO service_orders (classid, quoteid, companyid, contactid, cust_ref, ref_ln, userid, datetime, bill_to_id, termsid, public_notes, private_notes, status) VALUES (".fres($classid).",".fres($quoteid).",".fres($companyid).",NULL,".fres($cust_ref).",".fres($ref_ln).",".fres($userid).",".fres($datetime).",".fres($bill_to_id).",".fres($termsid).",".fres($public_notes).",".fres($private_notes).",".fres($status).");";
                //     qdb($query) OR die(qe().'<BR>'.$query);
                //     $so_number = qid();
                // }

                // Insert into Service Items
                $query = "INSERT INTO service_items (line_number, so_number, task_name, qty, amount, item_id, item_label, quote_item_id, description, due_date, mileage_rate, ref_1, ref_1_label, ref_2, ref_2_label, closeout_ln) VALUES (".fres($line_number).",".fres($so_number).",".fres($task_name).",".fres($qty).",".fres($amount).",".fres($addy_id).",".fres('addressid').",NULL,".fres($description).",NULL,".fres($mileage_rate).",NULL,NULL,".fres($service_item_id).",'service_item_id',NULL);";

                qdb($query) OR die(qe().'<BR>'.$query);
                $service_item_id = qid();

                // Insert into Map
                $query = "INSERT INTO maps_job_co (BDB_jid, service_item_id) VALUES (".res($service['id']).", ".res($service_item_id).");";
                qdb($query) OR die(qe().'<BR>'.$query);
            }
        }
    }

    echo "IMPORT COMPLETE!";
