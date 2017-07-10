<?php
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/import_aid.php';
	include_once $rootdir.'/inc/terms.php';

	
if(!isset($debug)){$debug = 0;}
// $debug = 0;
	
$bdb_select = "SELECT * FROM inventory_bill b, inventory_billli bl, inventory_componentorder co 
    WHERE b.id = bl.bill_id 
    and bl.co_id is NOT NULL 
    and co.id = bl.co_id
    AND bl.bill_id != '14694';
    "; //Manually exclude 14694, since they are billed differently than they were bought
$bdb_results = qdb($bdb_select, "PIPE") or die(qe("PIPE")." | $bdb_select");
echo($bdb_select."<br>");

$arr_check = array();
$translation_array = array();
if($debug){
qdb("DELETE FROM `bills` WHERE notes like 'COMP BILLS%';");
qdb("DELETE FROM `bill_items` where memo like 'COMP BILLS%';");
qdb("DELETE FROM `payments` where number like 'Bill%';");
qdb("DELETE FROM `payment_details` where order_type like 'bill';");
}

foreach($bdb_results as $r){
    //Var dec
    $bill_no = $partid = $memo = $qty = $termsid = $amount = $line_number = $po_number = $invoice_no = $date_created = $due_date = $companyid = $notes = $status = '';
    //////

    $bill_no = $r['bill_id'];
    $invoice_no = $r['ref_no'];
    $partid = translateComponent($r['component_id']);
    $date_created = format_date($r['date']." 12:00:00", "Y-m-d H:m:s");
    $termsid = $r['terms_id'];
    $due_date = format_date($r['due_date'], "Y-m-d");
    if(!$r['due_date']){
        $termsid = $TERMS_MAPS[$termsid];
        $due_date = format_date($r['date'], "Y-m-d",array("d" => getDays($termsid)));
    }
    $companyid = dbTranslate($r['vendor_id']);
    $notes = "COMP BILLS IMPORT | ".$r['memo']; 
    if($r['voided']){
        $status = 'Voided';
    } else if (!$r['paid']){
        $status = 'Pending';
    } else {
        $status = 'Completed';
    }
    $qty = $r['qty'];
    $amount = $r['amount'];
    $pay = $qty * $amount;
    $po_number = $r['cpo_id'];

    
    if(!$arr_check[$bill_no] && !$translation_array[$bill_no]){
        $arr_check[$bill_no] = true;
        $check = "SELECT count(*) FROM `bills` where bill_no = ".prep($bill_no).";";
        
        if(!rsrq($check)){
            $bill_insert = "INSERT INTO `bills`(`bill_no`, `invoice_no`, `date_created`, `due_date`, `po_number`, `companyid`, `notes`, `status`) 
            VALUES (".prep($bill_no).",".prep($invoice_no).",".prep($date_created).",".prep($due_date).",".prep($po_number).",".prep($companyid).",".prep($notes).", ".prep($status).");";
            if(!$debug){qdb($bill_insert) or die(qe()." | $bill_insert");}
            else{echo($bill_insert."<br>");}
        } else {
            $bill_insert = "INSERT INTO `bills`(`invoice_no`, `date_created`, `due_date`, `po_number`, `companyid`, `notes`, `status`) 
            VALUES (".prep($invoice_no).",".prep($date_created).",".prep($due_date).",".prep($po_number).",".prep($companyid).",".prep($notes).", ".prep($status).");";
            if(!$debug){qdb($bill_insert) or die(qe()." | $bill_insert");}
            else{echo($bill_insert."<BR>");}
            $new_bill_no = qid();
            echo("<br><br>--------------------------------------<br>OLD: ".$bill_no." | NEW:".$new_bill_no."<br><br>");
            $translation_array[$bill_no] = array(
                "old" => $bill_no,
                "new" => $new_bill_no
            );
            $bill_no = $new_bill_no;
        }
        
        //Insert a blank payment and update it later
        if($status == "Completed"){
            $payment_insert = "INSERT INTO `payments`(`companyid`, `date`, `payment_type`, `number`, `amount`, `notes`) 
            VALUES (".prep($companyid).", ".prep($date_created).", 'Other','Bill".$bill_no."', '0.00', 'Imported');";
            if(!$debug){qdb($payment_insert) or die(qe()." | $payment_insert");}
            else{echo($payment_insert."<br>");}
            
            $paymentid = qid();
            $details_insert = "INSERT INTO `payment_details`(`order_number`, `order_type`, `ref_number`,`ref_type`, `amount`, `paymentid`) 
            VALUES (".prep($po_number).", ".prep("po").", ".prep($bill_no).", ".prep("bill").",'0.00', ".prep($paymentid).");";
            if(!$debug){qdb($details_insert) or die(qe()." | $details_insert");}
            else{echo($details_insert."<br>");}
        } else {
            echo("<br>Payment not yet collected, so don't freak out Aaron<br>");
        }
    }
    elseif($translation_array[$bill_no]){
        $bill_no = $translation_array[$bill_no]['new'];
    }
    $bli_insert = "
    INSERT INTO `bill_items`(`bill_no`, `partid`, `memo`, `qty`, `amount`, `warranty`, `line_number`) 
    VALUES ($bill_no,".prep($partid).",'COMP BILLS IMPORT',".prep($qty).",".prep($amount).",NULL,NULL);";
    
    if(!$debug){qdb($bli_insert) or die(qe()." | $bli_insert");}
    else{echo($bli_insert."<br>");}
    $payment_update = "UPDATE `payments` SET `amount` = `amount` + ".prep($pay,"0.00")." WHERE `number` = 'Bill".$bill_no."';";
    qdb($payment_update) or die(qe().$payment_update);
    $pd_update = "UPDATE `payment_details` SET `amount` = `amount` + ".prep($pay,"0.00")." WHERE ref_number = ".prep($bill_no).";";
    qdb($pd_update) or die(qe().$pd_update);
    
    echo("New Amount: ".rsrq("SELECT `amount` FROM `payments` where `number` = 'Bill".$bill_no."';"));
    echo("<br><br>");
}

echo("
<table style ='border:thin solid black;'>
<tr style ='border:thin solid black;'>
<td style ='border:thin solid black;'>OLD</td>
<td style ='border:thin solid black;'>NEW</td>
</tr>
");
foreach($translation_array as $r){
    echo("
    <tr style ='border:thin solid black;'>
<td style ='border:thin solid black;'>".$r['old']."</td>
<td style ='border:thin solid black;'>".$r['new']."</td>
</tr>");
}
echo("</table>");
?>