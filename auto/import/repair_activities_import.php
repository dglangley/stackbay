<?php
exit;
//This script will import historical repairs information including any third party request and internal activities.
	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/setPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/filter.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/getUser.php';
    
    
    $activities_select = "SELECT `ticket_number`,`date_in`,`date_out`, `assigned_in`, `created_at`, `notes` from inventory_repair WHERE `ticket_number` is not NULL AND `created_at` > '2014-12-31';";
    $results = qdb($activities_select, "PIPE") or die(qe("PIPE").$activities_select);
    foreach($results as $r){
        $ro_number = $r['ticket_number'];
        
        //Get Line Item Number
        $ln_query = "SELECT `id` FROM `repair_items` WHERE `ro_number` = '$ro_number';";
        $ln = qdb($ln_query) or die(qe()." | $ln_query");
        $ln = mysqli_fetch_assoc($ln);
        $repair_item_id = $ln['id'];
        $tech_id = 16;
        if ($r['date_in']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 12:00:00","Y-m-d G:i:s")).", $tech_id, 'Checked In');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        if ($r['date_out']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_out']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Checked Out');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        
        //THIS IS NOT A STABLE WAY TO BUILD THIS BUT WILL MAKE THE WORLD SLIGHTLY BETTER? TALK TO DAVID;
        if ($r['assigned_in']){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['assigned_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        } else if($r['date_in']) {
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['date_in']." 17:00:00","Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        } else {
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['created'],"Y-m-d G:i:s")).", $tech_id, 'Claimed Ticket');";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert."<br>");
        }
        $n = $r['notes'];
        $rma = explode(" ",$r['notes']);
        $notes ='';
        if($rma[0] != "RMA" && strlen($n) > 3){
            $notes = $n;
        }
        if ($notes){
            $insert = "INSERT INTO `repair_activities`(`ro_number`, `repair_item_id`, `datetime`, `techid`, `notes`) 
                    VALUES (".prep($ro_number).",".prep($repair_item_id).", ".prep(format_date($r['created_at'],"Y-m-d G:i:s")).", $tech_id, ".prep($notes).");";
                    qdb($insert) or die(qe()." | $insert");
                    echo($insert);
        }
        echo("<br><br>");
        
    }
    
?>
