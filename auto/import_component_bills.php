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
	
$bdb = "
select b.*, bi.* from inventory_bill b, inventory_billli bi
where bi.memo like '%cpo#%' and b.id = bi.bill_id;";
$bdb_results = qdb($bdb, "PIPE") or die(qe("PIPE")." | $bdb");
echo($bdb."<br>");
$arr_check = array();
$translation_array = array();
// qdb("DELETE FROM `bills` WHERE notes like 'COMP BILLS%';");
// qdb("DELETE FROM `bill_items` where memo like 'COMP BILLS%';");
// qdb("DELETE FROM `payments` where number like 'Bill%';");
// qdb("DELETE FROM `payment_details` where order_type like 'bill';");

foreach($bdb_results as $r){
    //Var dec
    $memo = array();
    $bill_no;
    $po_no = '';
    $invoice_no = '';
    $date_created = '';
    $due_date = '';
    $companyid = '';
    $notes = '';
    $status = '';
    $partid = '';
    $qty = '';
    $amount = '';
    //////
    
    
    //Memo Walk to get CPO number
    $memo = memo_walk($r['memo']);
    // echo("<pre>");
    // print_r($r);
    // print_r($memo);
    // echo("</pre>");
    
    $bill_no = $r['bill_id'];
    $po_no = $memo['cpo#'];
    $invoice_no = $r['ref_no'];
    $date_created = format_date($r['date']." 12:00:00", "Y-m-d H:m:s");
    $due_date = $r['due_date'];
    $companyid = dbTranslate($r['vendor_id']);
    $notes = "COMP BILLS IMPORT | ".$r['memo']; 
    if($r['voided']){
        $status = 'Voided';
    } else if (!$r['paid']){
        $status = 'Pending';
    } else {
        $status = 'Completed';
    }
    $partid = key(hecidb($memo['item']));
    $qty = $r['qty'];
    $amount = $r['amount'];
    $pay = $qty * $amount;
    

    
    if(!$arr_check[$bill_no] && !$translation_array[$bill_no]){
        $arr_check[$bill_no] = true;
        $check = "SELECT count(*) FROM `bills` where bill_no = ".prep($bill_no).";";
        
        if(!rsrq($check)){
            $bill_insert = "INSERT INTO `bills`(`bill_no`, `invoice_no`, `date_created`, `due_date`, `po_number`, `companyid`, `notes`, `status`) 
            VALUES (".prep($bill_no).",".prep($invoice_no).",".prep($date_created).",".prep($due_date).",".prep($po_no).",".prep($companyid).",".prep($notes).", ".prep($status).");";
            qdb($bill_insert) or die(qe()." | $bill_insert");
            echo($bill_insert."<br>");
        } else {
            $bill_insert = "INSERT INTO `bills`(`invoice_no`, `date_created`, `due_date`, `po_number`, `companyid`, `notes`, `status`) 
            VALUES (".prep($invoice_no).",".prep($date_created).",".prep($due_date).",".prep($po_no).",".prep($companyid).",".prep($notes).", ".prep($status).");";
            qdb($bill_insert) or die(qe()." | $bill_insert");
            $new_bill_no = qid();
            echo($bill_insert."<br>");
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
            qdb($payment_insert) or die(qe()." | $payment_insert");
            echo($payment_insert."<br>");
            
            $paymentid = qid();
            $details_insert = "INSERT INTO `payment_details`(`order_number`, `order_type`, `ref_number`,`ref_type`, `amount`, `paymentid`) 
            VALUES (".prep($po_no).", ".prep("po").", ".prep($bill_no).", ".prep("bill").",'0.00', ".prep($paymentid).");";
            qdb($details_insert) or die(qe()." | $details_insert");
            echo($details_insert."<br>");
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
    qdb($bli_insert) or die(qe()." | $bli_insert");
    echo($bli_insert."<br>");
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