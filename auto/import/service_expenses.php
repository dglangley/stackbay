<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/companyMap.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

    // $PIPE = mysqli_init();
    // $PIPE->options(MYSQLI_OPT_CONNECT_TIMEOUT,5);
    // $PIPE->real_connect('db.ven-tel.com', 'andrew', 'venpass01', 'service', '13306');
    // if (mysqli_connect_errno($PIPE)) {
    //     //add error to global array that is outputted to alert modal
    //     if (isset($ALERTS)) {
    //         $ALERTS[] = "Failed to connect to the PIPE!";
    //     } else {
    //         //die( "Failed to connect to MySQL: " . mysqli_connect_error() );
    //         echo "<BR><BR><BR><BR><BR>Failed to connect to MySQL: " . mysqli_connect_error(). "<BR><BR>";
    //     }
    // }

    // Reset data and import code for jobs within the set range
    $DATA = array();
    
    $query = "SELECT * FROM services_expense WHERE date >= '2016-01-01';";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    //print "<pre>" . print_r($DATA, true) . "</pre>";

    // Import Job Data
    foreach($DATA as $expense) {
        $item_id = '';
        $item_id_label = '';

        if($expense['job_id'] AND mapJob($expense['job_id'])) {
            $item_id = mapJob($expense['job_id']);
            $item_id_label = 'service_item_id';
        }

        $companyid = '';
        $expense_date = $expense['date'];
        $description = $expense['label'];
        $catergoryid = $expense['expenseitem_id'];
        $amount = $expense['amount'];
        $userid = (mapUser($expense['tech_id']) ?: '');
        $datetime = $expense['timestamp'];

        // Insert into Service Orders
        $query = "INSERT INTO expenses (item_id, item_id_label, companyid, expense_date, description, categoryid, units, amount, userid, datetime) ";
        $query .= "VALUES (".fres($item_id).",".fres($item_id_label).",".fres($companyid).",".fres($expense_date).",".fres($description).",".fres($catergoryid).", 1,".fres($amount).",".fres($userid).",".fres($datetime);
        $query .= "); ";
        qdb($query) OR die(qe().'<BR>'.$query);
        $expense_id = qid();

echo $query.'<BR>';

        $query = "INSERT INTO maps_expense (BDB_id, expenseid) VALUES (".res($expense['id']).", ".res($expense_id).");";
        qdb($query) OR die(qe().'<BR>'.$query);

echo $query.'<BR>';
    }

    echo "IMPORT COMPLETE!";
