<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

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

	$query = "DELETE FROM service_items WHERE id IN (SELECT service_item_id FROM maps_job_co); ";
	$result = qedb($query);

	$query = "TRUNCATE maps_job_co; ";
	$result = qedb($query);
    
    // Reset data and import code for jobs within the set range
    $DATA = array();
    
    $query = "SELECT co.*, j.job_id FROM services_jobquoteco co, services_jobquote j ";
	$query .= "WHERE co.approved = 1 AND j.id = co.quote_id AND j.job_id IS NOT NULL;";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    //print "<pre>" . print_r($DATA, true) . "</pre>";

    // Import Job Data
    foreach($DATA as $service) {
        $job_id = $service['job_id'];

        $service_item_id = mapJob($job_id);

		// we require an existing id to continue;
		if (! $service_item_id) { continue; }

            // This means we will see it as ICO
            $cco = false;

            if($service['cco']) {
                $cco = true;
            }

            // From service_item_id get all the info needed to create the CCO or ICO
            $query = "SELECT *, si.status_code scode, si.so_number as order_number FROM service_items si, service_orders so WHERE so.so_number = si.so_number AND si.id = ".res($service_item_id).";";
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
                $addy_label = $r3['item_label'];
				$status_code = $r3['scode'];
                $bill_to_id;
                $public_notes = $service['site_access_info_address'];;
                $private_notes;
                $status = 'Active';

                // Set variables being used for service items
                // $task_name = $service['job_no'];
				$task_name = $service['co_num'];
                $so_number = $r3['order_number'];

$line_number = '';

                $qty = 1;
                $amount = $service['quote_labor'];// + $service['quote_engineering'];
                $mileage_rate = $service['mileage_rate'];

                // Descr as scope
                $description = $service['scope'];

                // Insert into Service Items
                $query = "INSERT INTO service_items (line_number, so_number, task_name, qty, amount, item_id, item_label, ";
				$query .= "quote_item_id, description, due_date, mileage_rate, ref_1, ref_1_label, ref_2, ref_2_label, status_code, closeout_ln) ";
				$query .= "VALUES (".fres($line_number).",".fres($so_number).",".fres($task_name).",".fres($qty).",".fres($amount).",".fres($addy_id).",".fres($addy_label).",";
				$query .= "NULL,".fres($description).",NULL,".fres($mileage_rate).",NULL,NULL,".fres($service_item_id).",'service_item_id', ".fres($status_code).", NULL);";

                qdb($query) OR die(qe().'<BR>'.$query);
                $co_service_id = qid();

                // Insert into Map
                $query = "INSERT INTO maps_job_co (BDB_jid, service_item_id) VALUES (".res($service['id']).", ".res($co_service_id).");";
                qdb($query) OR die(qe().'<BR>'.$query);
            }

    }

    echo "IMPORT COMPLETE!";
