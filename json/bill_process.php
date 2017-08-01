<?php
	
// AddressSubmit handles the submission of a new address from an addresses modal


		header('Content-Type: application/json');
		$rootdir = $_SERVER['ROOT_DIR'];
			
			include_once $rootdir.'/inc/dbconnect.php';
			include_once $rootdir.'/inc/format_date.php';
			include_once $rootdir.'/inc/format_price.php';
			include_once $rootdir.'/inc/getCompany.php';
			include_once $rootdir.'/inc/getPart.php';
			include_once $rootdir.'/inc/keywords.php';
			include_once $rootdir.'/inc/getRecords.php';
			include_once $rootdir.'/inc/getRep.php';
			include_once $rootdir.'/inc/form_handle.php';


            $data = json_decode(grab('submission'),true);
            
            $bill_number = $data['bill_no'];
            $due_date = format_date($data['due_date'], "Y-m-d");
            
            if ($bill_number == 'new'){
                $company_id = '';
                
                $po_select = "
                Select `companyid` company, `days` FROM purchase_orders, terms
                WHERE terms.id = termsid AND po_number = ".prep($data['po_number'])."
                ";
                
                $po_results = qdb($po_select);
                
                if (mysqli_num_rows($po_results)>0){
                    $po_results = mysqli_fetch_assoc($po_results);
                    $company_id = $po_results['company'];
                    // if($po_results['days'] > 0){
                    //     $due_date = format_date($due_date, "Y-m-d",array("d" => $po_results['days']));
                    // }
                }

                $bill_insert = "
                INSERT INTO `bills`(
                `invoice_no`,
                `date_created`,
                `due_date`,
                `po_number`,
                `companyid`) VALUES (
                ".prep($data['invoice_no']).",
                '".$GLOBALS['now']."',
                ".prep($due_date,'NULL').",
                ".prep($data['po_number']).",
                ".prep($company_id,"NULL").");
                ";
                qdb($bill_insert) or die(qe().": ".$bill_insert);

                $bill_number = qid();
            }
            foreach ($data['lines'] as $line) {
                if ($line['qty']==0){
                    continue;
                }
                $line_insert = "
                    INSERT INTO `bill_items`(
                    `bill_no`,
                    `partid`,
                    `qty`,
                    `warranty`,
                    `amount`,
                    `line_number`
                    ) VALUES (
                    $bill_number,
                    ".prep($line['partid']).",
                    ".prep($line['qty']).",
                    ".prep($line['warranty']).",
                    ".prep($line['price']).",
                    ".prep($line['ln'])."
                    );
                ";
                qdb($line_insert) or die(qe().": ".$line_insert);
                $line_id = qid();
                if(current($line['serials'])){
                    foreach($line['serials'] as $invid){
                        $inv = prep($invid);
                        $serial_insert = "
                        INSERT INTO `bill_shipments`(`inventoryid`, `packageid`, `bill_item_id`) 
                        VALUES ($inv,NULL,$line_id);
                        ";
                        qdb($serial_insert) or die(qe().": ".$serial_insert);
                    }
                }
            }
            
                
	       
            echo json_encode($bill_number);
            exit;
?>

