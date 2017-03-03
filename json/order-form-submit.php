<?php

//=============================================================================
//========================= Order Form Submit Template ========================
//=============================================================================
// This script is a JSON text which will handle the left side of the orders   |
// pages on general saves and creates. It should be flexible to allow users   |
// to create new fields with minimal updates, and handle the varience between | 
// new and pre-existing forms. Primarily, this will work with the orders table|
//                                                                            | 
// Last update: Aaron Morefield - November 28th, 2016                         |
//=============================================================================
    
    //Prepare the page as a JSON type
	header('Content-Type: application/json');
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/format_address.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/pipe.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/getAddresses.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/jsonDie.php';
		include_once $rootdir.'/inc/getContact.php';
		include_once $rootdir.'/inc/send_gmail.php';

		// initializes Amea's gmail API session
		setGoogleAccessToken(5);

//=============================== Inputs section ==============================

	// added by David 2/9/17 for file uploads; this takes a file upload when passed in its own, separate
	// ajax (synchronous) request, we upload the file(s) to its storage location, then pass back the
	// uploaded file name(s) as an indicator of success. the ensuing form post to this script uses
	// those file names but does not upload the files themselves, so this sub-script gets handled only once
	if (isset($_FILES) AND count($_FILES)>0 AND $_SERVER['REQUEST_METHOD'] == 'POST') {
		require($rootdir.'/vendor/autoload.php');

		// this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
		if (!$DEV_ENV) {
			$s3 = Aws\S3\S3Client::factory(array('region'=>'us-west-2'));
			$bucket = getenv('S3_ORDER_UPLOADS')?: die('No "S3_ORDER_UPLOADS" config var in found in env!');
		}

		$files = '';
		try {
			foreach ($_FILES as $file) {
				$filename = date("Ymd").'_'.preg_replace('/[^[:alnum:].]+/','-',$file['name']);

				// check for file existing already
				$keyExists = false;
				if (!$DEV_ENV) {
					$s3->registerStreamWrapper();
					$keyExists = file_exists("s3://".$bucket."/".$filename);
				}

				if ($keyExists) {//file has already been uploaded
					jsonDie('File has already been uploaded!');
				}

				if ($DEV_ENV) {
					$temp_dir = sys_get_temp_dir();
					if (substr($temp_dir,strlen($temp_dir)-1,1)<>'/') { $temp_dir .= '/'; }
					$temp_file = $temp_dir.$filename;
					$files = $temp_file;

					// store uploaded file in temp dir so we can use it later
					if (move_uploaded_file($file['tmp_name'], $temp_file)) {
//						echo "File is valid, and was successfully uploaded.\n";
					} else {
						echo json_encode(array('filename'=>'','message'=>'File "'.$file['tmp_name'].'" did not save to "'.$temp_file.'"!'));
						exit;
					}
				} else {
	                $upload = $s3->upload($bucket, $filename, fopen($file['tmp_name'], 'rb'), 'public-read');
					$files = "s3://".$bucket."/".$filename;
				}
			}
       	} catch(Exception $e) {
			echo json_encode(array('filename'=>'','message'=>'Error! '.$e));
			exit;
		}
		echo json_encode(array('filename'=>$files,'message'=>''));
		exit;
	}


    //Macros
    $order_type = $_REQUEST['order_type'];
    $order_number = $_REQUEST['order_number'];
    $form_rows = $_REQUEST['table_rows'];



    
    
    //Form Specifics
    $companyid = is_numeric($_REQUEST['companyid'])? trim($_REQUEST['companyid']) : trim(getCompany($_REQUEST['companyid'],'name','id'));
    $company_name = getCompany($companyid);
    $contact = grab('contact');
    $ship = grab('ship_to');
    $bill = grab('bill_to');
    $carrier = grab('carrier');
    $service = grab('service');
    $account = grab('account');
    $tracking = grab('tracking');
    $private = (trim($_REQUEST['pri_notes']));
    $public = (trim($_REQUEST['pub_notes']));
    $terms = grab('terms');
    $rep = grab('sales-rep');
    $created_by = grab('created_by');
    $email_to = grab('email_to');
    $email_confirmation = grab('email_confirmation');
	if ($email_confirmation) {
		$addl_recp = getContact($email_to,'id','email');
		if (! $addl_recp) {
			jsonDie('"'.getContact($email_to).'" does not have an email! Please update their profile first, or remove them from Order Confirmation in order to continue.');
		}
	}

    //Created By will be the value of the current userid
    $assoc_order = grab('assoc');

/*
    //Process the contact, see if a new one was added
    if (!is_numeric($contact) && !is_null($contact) && ($contact)){
        $title = '';
        if (strpos($contact,'.')){
            list($title, $contact) = explode('.',$contact,2);
        }
        $contact = prep($contact);
        $title = prep($title);

        if ($contact != "NULL" && strtolower($contact) != "'null'"){
            $new_con = "INSERT INTO `contacts`(`name`,`title`,`notes`,`status`,`companyid`,`id`) 
            VALUES ($contact,$title,NULL,'Active',$companyid,NULL)";
            
            $result = qdb($new_con) OR jsonDie(qe().' '.$new_con);
            $contact = qid();
        }
    }
*/


	// build freight service and terms descriptors for email confirmation
	$freight_service = '';
	$freight_terms = '';
	if ($email_confirmation) {
		$query = "SELECT method, name FROM freight_services fs, freight_carriers fc, companies c ";
		$query .= "WHERE fs.id = $service AND fs.carrierid = fc.id AND fc.companyid = c.id; ";
		$result = qdb($query) OR jsonDie(qe().' '.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$freight_service = $r['name'].' '.$r['method'];
		}
		if ($account AND strtoupper($account)<>'NULL') {
			$query = "SELECT account_no FROM freight_accounts WHERE id = $account; ";
			$result = qdb($query) OR jsonDie(qe().' '.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$freight_terms = $r['account_no'];
			}
		} else {
			$freight_terms = 'Prepay and Bill';
		}

		$sbj = 'Order '.$assoc_order.' Confirmation';

		// build confirmation email headers, then line items below
		$msg = "<p>Here's your confirmation for order number ".$assoc_order.". <em>Please review for accuracy.</em></p><br/><br/>";
		$msg .= "<p><strong>Order number:</strong> ".$assoc_order."</p>";
		$msg .= "<p><strong>Shipping Service:</strong> ".$freight_service."</p>";
		$msg .= "<p><strong>Shipping Terms:</strong> ".$freight_terms."</p>";
		$msg .= "<p><strong>Shipping Address:</strong><br/>";
		$msg .= format_address($ship)."</p>";
	}

    if ($order_number == "New"){
        //If this is a new entry, save the value, insert the row, and return the
        //new-fangled ID from the mega-sketch qid function
        $cid = prep($companyid);
        $rep = prep($rep);
        $created_by = prep($created_by);
        $contact = prep($contact);
        $carrier = prep($carrier);
        $terms = prep($terms);
        $ship = prep($ship);
        $bill = prep($bill); 
        $service = prep($service);
    	$account = prep($account);
        $public = prep($public);
        $private = prep($private);
        $assoc_order = prep($assoc_order);
        
        
        
        if($order_type=="Purchase"){
            $insert = "INSERT INTO `purchase_orders` (`created_by`, `companyid`, `sales_rep_id`, `contactid`, `assoc_order`,
            `remit_to_id`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES 
            ($created_by, $cid, $rep, $contact, $assoc_order, $bill, $ship, $carrier, $service, $account, $terms, $public, $private, 'Active');";
        }
        else{
    		$filename = grab('filename');
    		$filename = prep($filename);

            $insert = "INSERT INTO `sales_orders`(`created_by`, `sales_rep_id`, `companyid`, `contactid`, `cust_ref`, `ref_ln`, 
            `bill_to_id`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES 
            ($created_by, $rep, $cid, $contact, $assoc_order, $filename, $bill, $ship, $carrier, $service, $account, $terms, $public, $private, 'Active');";
        }

    //Run the update
		$result = qdb($insert) OR jsonDie(qe().' '.$insert);
        
        //Create a new update number
        $order_number = qid();
    }
    else{
        
        //Note that the update field doesn't have all the requisite fields
        $macro = "UPDATE ";
        $macro .= ($order_type == "Purchase")? "`purchase_orders`" :"`sales_orders`";
        $macro .= " SET ";
        $macro .= updateNull('sales_rep_id',$rep);
        $macro .= updateNull('companyid',$companyid);
        $macro .= updateNull('contactid',$contact);
        if ($order_type == "Purchase"){
            $macro .= updateNull('assoc_order',$assoc_order);
            $macro .= updateNull('remit_to_id',$bill);
        }
        else{
            $macro .= updateNull('cust_ref',$assoc_order);
//David commented this out 2/13/2017, but we will eventually need to add in a way to change the attached file
//            $macro .= updateNull('ref_ln','NULL');
            $macro .= updateNull('bill_to_id',$bill);
        }
        $macro .= updateNull('ship_to_id',$ship);
        $macro .= updateNull('freight_carrier_id',$carrier);
        $macro .= updateNull('freight_services_id',$service);
        $macro .= updateNull('freight_account_id',$account);
        $macro .= updateNull('termsid',$terms);
        $macro .= updateNull('public_notes',$public);
        $macro .= rtrim(updateNull('private_notes',$private),',');
        $macro .= " WHERE ";
        $macro .= ($order_type == "Purchase")? "`po_number`" :"`so_number`";
        $macro .= " = $order_number;";
        
        //Query the database

		$result = qdb($macro) OR jsonDie(qe().' '.$macro);
    }
    

    $stupid = 0;

    //RIGHT HAND SUBMIT
    
    if(isset($form_rows)){
		if (count($form_rows)>0) {
			$msg .= "<p><strong>Item Details:</strong><br/>";
		}

        foreach ($form_rows as $r){
            $stupid++;
            $line_number = prep($r['line_number']);
            $item_id = prep($r['part']);
            $record = $r['id'];
            $date = prep(format_date($r['date'],'Y-m-d'));
            $warranty = prep($r['warranty']);
            $conditionid = prep($r['conditionid']);
            $qty = prep($r['qty']);
            $unitPrice = prep(format_price($r['price'],true,'',true));

			if ($r['line_number']) { $msg .= '<span style="color:#aaa">'.$r['line_number'].'.</span> '; }
			$query2 = "SELECT part, heci FROM parts WHERE id = $item_id; ";
			$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				if ($r2['heci']) {
					$msg .= substr($r2['heci'],0,7).' ';
				}
				$msg .= $r2['part'];
			}
			if ($r['qty']) { $msg .= ' qty '.$r['qty']; }
			$msg .= '<br/>';

            if ($record == 'new'){
                
                //Build the insert statements
                $line_insert = "INSERT INTO ";
                $line_insert .=  ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
                $line_insert .=  " (`partid`, ";
                $line_insert .=  ($order_type=="Purchase") ? "`po_number`, `receive_date`, " : "`so_number`, `delivery_date`, ";
                $line_insert .=  "`line_number`, `qty`, `price`, `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`, `warranty`, `conditionid`, `id`) VALUES ";
                $line_insert .=   "($item_id, '$order_number' , $date, $line_number, $qty , $unitPrice , NULL, NULL, NULL, NULL, $warranty , $conditionid, NULL);";
                
				$result = qdb($line_insert) OR jsonDie(qe().' '.$line_insert);
            }
            else{
                $update = "UPDATE ";
                $update .= ($order_type=="Purchase") ? "`purchase_items`" : "`sales_items`";
                $update .= " SET 
                `partid`= $item_id,
                `line_number`= $line_number,
                `qty`= $qty,
                `price`= $unitPrice, ";
    $update .=  ($order_type == "Purchase")? "
                `receive_date` = $date, " : "
                `delivery_date` = $date, ";
    $update .= "
                `warranty` = $warranty,
                `conditionid` = $conditionid 
                WHERE id = $record;";
				$line_update = qdb($update) OR jsonDie(qe().' '.$line_update);
            }
        }
    }

	// send order confirmation
	if ($email_confirmation AND ! $DEV_ENV) {
		$send_success = send_gmail($msg,$sbj,array('david@ven-tel.com'));
		if ($send_success) {
//			jsonDie('Success');
		} else {
//			jsonDie($SEND_ERR);
		}
	}

    
    //Return the meta data about the information submitted, including the order
    //type, number, and the inserted statement (for debugging purposes)

    $form = array(
        'type' => $order_type,
        'order' => $order_number,
        'line_insert' => $line_insert,
        'error' => qe(),
        'stupid' => $stupid,
        'update' => $macro,
		'message' => 'Success',
        'input' => $insert,
        'qar' => qar()
    );
    
    echo json_encode($form);
    exit;

?>
