<?php
exit;
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

    function companyMap($company='') {
        $companyid = 0;
        $customer = trim($customer);

        $query = "SELECT * FROM companies WHERE name LIKE '".res($company)."'; ";
        $result = qedb($query);
        if (mysqli_num_rows($result)>0) {
            $r = mysqli_fetch_assoc($result);
            $companyid = $r['id'];
            //echo "<b>Found</b>: ".$company."<br>";
        } else {
            $query = "INSERT INTO companies (name) VALUES ('".res($company)."'); ";
            $result = qedb($query);
            $companyid = qid();
            echo "<b>Added</b>: ".$company."<br>";
        }

        return $companyid;
    }

    $vendor_filter = array();


    $query = "SELECT vendor, id FROM services_expense;";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        //if(! preg_grep( "/".trim($r['vendor'])."/i" , $vendor_filter )) {
            $vendor_filter[] = trim($r['vendor']);

            $companyid = companyMap(trim($r['vendor']));

            // Map it to the companyid
            $query2 = "SELECT expenseid FROM maps_expense WHERE BDB_id = ".$r['id'].";";
            $result2 = qedb($query2);

            if(mysqli_num_rows($result2)) {
                $r2 = mysqli_fetch_assoc($result2);

                $query3 = "UPDATE expenses SET companyid = ".fres($companyid)." WHERE id = ".$r2['expenseid']." AND companyid IS NULL;";
                qedb($query3);

echo $query3 . "<BR>";
            }
        // } else {

        // }
    }

    // print "<BR><BR><pre>" . print_r($vendor_filter, true) . "</pre>";


    echo "IMPORT COMPLETE!";
