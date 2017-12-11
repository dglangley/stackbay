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
    
    // Reset data and import code for jobs within the set range
    $DATA = array();
    
    $query = "SELECT * FROM services_job WHERE completed_date IS NULL OR date_entered >= '2017-01-01';";
    $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    //print "<pre>" . print_r($DATA, true) . "</pre>";

    // Import Job Data
    foreach($DATA as $service) {
        // Set variables being used for service orders
        $classid;

        // We will get the quoteid updated later as BDB uses the jobid from quotes to link instead of our new way
        $quoteid;

        $companyid = companyMap($service['company_id'],$service['customer']);
        $contactid;
        $cust_ref = $service['customer_job_no'];
        $ref_ln;
        $userid;
        $datetime = $service['date_entered'];
        $bill_to_id;
        $termsid;
        $public_notes = $service['site_access_info_address'];;
        $private_notes;
        $status = 'Active';

        // Convert the BDB terms to our terms
        $query = "SELECT invoice_days FROM services_terms WHERE id = ".res($service['terms_id']).";";
        $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);

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

        // Set variables being used for service items
        $task_name = $service['job_no'];
        $so_number = 0;
        $line_number = 1;
        $qty = 1;
        $amount = $service['quote_labor'] + $service['quote_engineering'];
        $mileage_rate = $service['mileage_rate'];
        $description = $service['description'];

        // Variables needed from both analysis
        // If the job name has FFR in it then I figure the classid must be for FFR
        if(strpos($task_name, 'FFR') !== false) {
            $classid = 3; // 3 = FFR , 2 = Installation , 1 = Repair 
        } else {
            $classid = 2; // Repair is never used here
        }

        // Insert into Service Orders
        $query = "INSERT INTO service_orders (classid, quoteid, companyid, contactid, cust_ref, ref_ln, userid, datetime, bill_to_id, termsid, public_notes, private_notes, status) VALUES (".fres($classid).",".fres($quoteid).",".fres($companyid).",NULL,".fres($cust_ref).",".fres($ref_ln).",".fres($userid).",".fres($datetime).",".fres($bill_to_id).",".fres($termsid).",".fres($public_notes).",".fres($private_notes).",".fres($status).");";
        qdb($query) OR die(qe().'<BR>'.$query);
        $so_number = qid();

        // Insert into Service Items
        $query = "INSERT INTO service_items (line_number, so_number, task_name, qty, amount, item_id, item_label, quote_item_id, description, due_date, mileage_rate, ref_1, ref_1_label, ref_2, ref_2_label, closeout_ln) VALUES (".fres($line_number).",".fres($so_number).",".fres($task_name).",".fres($qty).",".fres($amount).",NULL,".fres('addressid').",NULL,".fres($description).",NULL,".fres($mileage_rate).",NULL,NULL,NULL,NULL,NULL);";

        qdb($query) OR die(qe().'<BR>'.$query);
        $service_item_id = qid();

        // Insert into Map
        $query = "INSERT INTO maps_job (BDB_jid, service_item_id) VALUES (".res($service['id']).", ".res($service_item_id).");";
        qdb($query) OR die(qe().'<BR>'.$query);
    }

    echo "IMPORT COMPLETE!";
