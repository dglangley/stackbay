<?php
exit;
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/companyMap.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$query = "TRUNCATE maps_job; ";
	$result = qedb($query);

	$query = "TRUNCATE service_items; ";
	$result = qedb($query);

	$query = "TRUNCATE service_orders; ";
	$result = qedb($query);

	$query = "ALTER TABLE service_orders auto_increment = 400101; ";
	$result = qedb($query);
    
    $query = "SELECT job.*, terms.invoice_days FROM services_job job ";
	$query .= "LEFT JOIN services_terms terms ON terms.id = job.terms_id ";
	$query .= "WHERE (job.completed_date IS NULL OR job.date_entered >= '2016-01-01');";
    $result = qedb($query,'SVCS_PIPE');

    while($service = mysqli_fetch_assoc($result)) {
        // Set variables being used for service orders
        $classid;

        // We will get the quoteid updated later as BDB uses the jobid from quotes to link instead of our new way
        $quoteid;

        $companyid = companyMap($service['company_id'],$service['customer']);
        $contactid;

        $ref_ln = '';
        $cust_ref = '';//$service['customer_job_no'];
		$query2 = "SELECT po_number, po_file FROM services_jobpo WHERE job_id = '".$service['id']."'; ";
		$result2 = qedb($query2,'SVCS_PIPE');
		while ($r2 = mysqli_fetch_assoc($result2)) {
			if ($cust_ref) { $cust_ref .= ', '; }
			$cust_ref .= $r2['po_number'];

			if ($ref_ln) { $ref_ln .= ', '; }
			$ref_ln .= 'https://db.ven-tel.com:10086/service/usermedia/'.$r2['po_file'];
		}
        $userid;
        $datetime = $service['date_entered'];
        $bill_to_id;
        $termsid = 15; // Also Known as N/A
        $public_notes = $service['site_access_info_address'];;
        $private_notes;

        $status_code = 0;
		if ($service['cancelled'] == 1) {
			$status_code = 4;
		} else if ($service['admin_complete']==1) {
			$status_code = 2;
		} else if ($service['on_hold']==1) {
			$status_code = 3;
		}

		$tax_rate = '';
		if ($datetime<'2017-01-01 00:00:00') {
			$tax_rate = '7.5';
		} else {
			$tax_rate = '7.75';
		}

        // Convert the BDB terms to our terms
		if ($service['invoice_days']) {
            // Find the exact matching days in our database
            $query2 = "SELECT id FROM terms WHERE days = ".fres(trim($r['invoice_days'])).";";
            $result2 = qedb($query2);
            if(mysqli_num_rows($result2)) {
                $r2 = mysqli_fetch_assoc($result2);
                $termsid = $r2['id'];
            }
        }

        // Set variables being used for service items
        $task_name = $service['job_no'];
        $so_number = 0;
        $line_number = 1;
        $qty = 1;
        $amount = $service['quote_labor'] + $service['quote_material'] + $service['quote_engineering'];
        $mileage_rate = $service['mileage_rate'];
        $description = $service['description'];

        // Variables needed from both analysis
        // If the job name has FFR in it then I figure the classid must be for FFR
        if(strpos($task_name, 'FFR') !== false) {
            $classid = 3; // 3 = FFR , 2 = Installation , 1 = Repair 
        } else {
            $classid = 2; // Repair (1) is never used here
        }

        // Insert into Service Orders
        $query = "INSERT INTO service_orders (classid, quoteid, companyid, contactid, cust_ref, ref_ln, userid, datetime, bill_to_id, termsid, tax_rate, public_notes, private_notes, status) VALUES (".fres($classid).",".fres($quoteid).",".fres($companyid).",NULL,".fres($cust_ref).",".fres($ref_ln).",".fres($userid).",".fres($datetime).",".fres($bill_to_id).",".fres($termsid).",".fres($tax_rate).", ".fres($public_notes).",".fres($private_notes).",'Active');";
        qedb($query);
        $so_number = qid();

        // Insert into Service Items
        $query = "INSERT INTO service_items (line_number, so_number, task_name, qty, amount, item_id, item_label, quote_item_id, description, due_date, mileage_rate, ref_1, ref_1_label, ref_2, ref_2_label, status_code, closeout_ln) ";
		$query .= "VALUES (".fres($line_number).",".fres($so_number).",".fres($task_name).",".fres($qty).",".fres($amount).",NULL,";
		$query .= fres('addressid').",NULL,".fres($description).",NULL,".fres($mileage_rate).",NULL,NULL,NULL,NULL,".fres($status_code).",NULL);";

        qedb($query);
        $service_item_id = qid();

        // Insert into Map
        $query = "INSERT INTO maps_job (BDB_jid, service_item_id) VALUES (".res($service['id']).", ".res($service_item_id).");";
        qedb($query);
    }

    echo "IMPORT COMPLETE!";
