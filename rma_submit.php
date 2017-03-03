<?php
    
    //Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		
	include_once $rootdir.'/inc/form_handle.php';
	
    
    $rows = $_POST['rows'];
    $rma_number = $_POST['rma_number'];
    
    //Grab the macro information
    $origin = $_POST['origin_number'];
    $companyid = $_POST['companyid'];
    $contactid = $_POST['contactid'];
    
    $serial = array();
    
    //Get each indiviual line number, grouped accurately in rows
    for($i = 1; $i <= $rows; $i++){
        $serial[$i-1]['serial'] = grab("serial_$i");
        $serial[$i-1]['disposition'] = grab("disposition_$i");
        $serial[$i-1]['line_selected'] = grab("line_$i"."_selected");
        $serial[$i-1]['reason'] = grab("reason_$i");
        $serial[$i-1]['invid'] = grab("invid_$i");
        $serial[$i-1]['partid'] = grab("partid_$i");
        $serial[$i-1]['retid'] = grab("retid_$i");
    }
    echo"<pre>";
    print_r($serial);
    echo"</pre>";
    
    if ($rma_number == "new"){
        $insert = "INSERT INTO `returns`(`created_by`,`companyid`,`order_number`,`order_type`,`contactid`)
        VALUES (".$U['contactid'].",".prep($companyid).",".prep($origin).",'Sale',".prep($contactid).");";
        qdb($insert) OR die();
        $rma_number = qid();
        //I will eventually need to add inputs for notes and to change contact
        
    }
    
    
    foreach ($serial as $row){
        $query = '';
        $partid = prep($row['partid']);
        $retid = prep($row['retid']);
        $invid = prep($row['invid']);
        $reason = prep($row['reason']);
        $disposition = prep($row['disposition'], 0);
        $partid = prep($row['partid']);
        if($row['line_selected'] == "on" && !$row['retid']){
            $query = "
                INSERT INTO `return_items`
                (`partid`,`inventoryid`, `rma_number`, `line_number`, `reason`, `dispositionid`, `qty`) VALUES 
                ($partid ,$invid,".$rma_number.",NULL,$reason,$disposition,1);
            ";
        }
        elseif ($row['line_selected'] == "on" && $row['retid']){
            $query = "
            UPDATE `return_items` SET 
            `reason`=$reason,
            `dispositionid`= $disposition
             WHERE `id` = $retid;";
        }
        elseif(!$row['line_selected'] && $row['retid']) {
            $query = "DELETE FROM `return_items` WHERE `id` = $retid;";
        }
        if ($query){
            echo($query);
            qdb($query) OR die();
        }
    }
?>