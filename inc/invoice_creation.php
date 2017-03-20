<?php
	
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/form_handle.php';
    
function create_invoice($so_number){
    //Function to be run to create an invoice
    

    $total_owed_calculation = "
    SELECT SUM(price) owed
    FROM (Select price FROM sales_items si WHERE so_number = ".prep($so_number)."
    UNION 
    SELECT freight_amount price FROM packages p WHERE order_number = ".prep($so_number).") as p; 
    ";
    
    
    $total_due = mysqli_fetch_assoc(qdb($total_owed_calculation));
    $total = $total_due['owed'];
    
    $macro = "
    SELECT `companyid`, `created`, `days`, `type`
    FROM sales_orders, terms
    WHERE sales_orders.so_number = ".prep($so_number)." AND
    sales_orders.termsid = termsid;
    ";
    
    $invoice_macro = mysqli_fetch_assoc(qdb($macro));
    if ($invoice_macro['type'] == 'prepaid'){
        $pay_day = $GLOBALS['today'];
        $status = 'Completed';
        //THERE WILL NEED TO BE A CHECK HERE TO ENSURE THE PRODUCT WAS ACTUALLY PAID FOR
        $owed = '0.00';
    } else {
        $pay_day = format_date($invoice_macro['created'],"Y-m-d",array("d"=>$invoice_macro['days']));
        $status = 'Pending';
        $owed = $total;
    }
    
    
    $invoice_creation = "
    INSERT INTO `invoices`(
    `so_number`, `companyid`, `total_due`, `outstanding_amount`, `payment_due_date`, `date_invoiced`, `status`) 
    VALUES ( ".prep($so_number).", ".$invoice_macro['companyid'].", $total, $owed, CAST('".$pay_day."' AS DATE), NOW(), '$status');";
    // echo($invoice_creation);
    $result = qdb($invoice_creation) OR die(qe().": ".$invoice_creation);
    
    $invoice_id =  qid();
    //Select associated package orders

// Aaron's commented out super ultra mega join that will eventually be useful for inventory checks and such
    // $package_select = "
    // SELECT line_number, si.qty sold_qty, warranty, si.conditionid, i.id, i.notes, p.* 
    // FROM sales_items si, inventory i, package_contents pc, packages p 
    // WHERE si.id = ".prep($so_number)."
    // AND i.sales_item_id = si.id 
    // AND pc.serialid = i.id 
    // AND packageid = p.id;
    // ";
    
    $package_insert = "
    INSERT INTO invoice_shipments(`packageid`, `invoice_no`)
    SELECT id, $invoice_id AS inv_no FROM packages WHERE order_number = ".prep($so_number).";
    ";
    
    $result = qdb($package_insert) or die(qe().": ".$package_insert);
    
    return $invoice_id;

    
}

?>