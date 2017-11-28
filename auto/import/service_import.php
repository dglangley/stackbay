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

    // function companydbTranslate($companyid){
    //     $query = "SELECT name FROM services_company WHERE id = '".$companyid."'; ";
    //     $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
    //     if (mysqli_num_rows($result)==0) { return 0; }
    //     $r = mysqli_fetch_assoc($result);

    //     $query = "SELECT c.id `c` FROM companies c LEFT JOIN company_aliases a ON c.id = a.companyid ";
    //     $query .= "WHERE c.name = '".trim($r['name'])."' OR a.name = '".trim($r['name'])."' ";
    //     $query .= "GROUP BY c.id ORDER BY c.id ASC LIMIT 1; ";

    //     $result = qdb($query) OR die(qe().'<BR>'.$query);
    //     if(mysqli_num_rows($result)){
    //         foreach($result as $row){
    //             $COMPANY_MAPS[$companyid][$oldToNew] = $row['c'];

    //             return $row['c'];
    //         }
    //     } else{
    //         $COMPANY_MAPS[$companyid][$oldToNew] = $companyid;
    //         echo "<br><b>Company does not exist!</b>";

    //         return false;
    //     }
    // }

    // Import code for Companies
    $DATA = array();
    
    $query = "SELECT * FROM service_companies;";
    $result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    foreach($DATA as $company) {

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

        $companyid = companydbTranslate($service['company_id']);
        $contactid;
        $cust_ref = $service['customer_job_no'];
        $ref_ln;
        $userid;
        $datetime = $service['date_entered'];
        $bill_to_id;
        $termsid;
        $public_notes = $service['description'];;
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

        // Variables needed from both analysis
        // If the job name has FFR in it then I figure the classid must be for FFR
        if(strpos($task_name, 'FFR') !== false) {
            $classid = 3; // 3 = FFR , 2 = Installation , 1 = Repair 
        } else {
            $classid = 2; // Repair is never used here
        }

        // Insert into Service Orders
        $query = ";";

        // Insert into Map
        $query = ";";

        // Insert into Service Items
        $query = "INSERT INTO service_items (line_number, so_number, task_name, qty, ) VALUES (".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().",".fres().");";

        // Insert into Map
        $query = ";";
    }