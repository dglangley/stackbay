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
		include_once $rootdir.'/inc/order_parameters.php';


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
					$files = "https://s3-us-west-2.amazonaws.com/".$bucket."/".$filename;
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
    $o = o_params($order_type);
    if($o['rtv']){
    	$o = o_params("sales");
    }

    
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
    $public_notes = (trim($_REQUEST['pub_notes']));
    $terms = grab('terms');
    $rep = grab('sales-rep');
    $created_by = grab('created_by');
    $email_to = grab('email_to');
    $email_confirmation = grab('email_confirmation');
    $first_fee_label = grab('first_fee_label');
	$first_fee_amount = grab('first_fee_amount');
	$first_fee_id = grab('first_fee_id');
	$second_fee_label = grab('second_fee_label');
	$second_fee_amount = grab('second_fee_amount');
	$second_fee_id = grab('second_fee_id');
	$addl_recp_email = "";
	$addl_recp_name = "";
	if ($email_confirmation) {
		if ($email_to) {
			$addl_recp_email = getContact($email_to,'id','email');
			if ($addl_recp_email) {
				$addl_recp_name = getContact($email_to,'id','name');
			} else {
				jsonDie('"'.getContact($email_to).'" does not have an email! Please update their profile first, or remove them from Order Confirmation in order to continue.');
			}
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
        $save_rep = prep($rep);
        $created_by = prep($created_by);
        $save_contact = prep($contact);
        $carrier = prep($carrier);
        $terms = prep($terms);
        $ship = prep($ship);
        $bill = prep($bill); 
        $service = prep($service);
    	$account = prep($account);
        $public = prep($public_notes);
        $private = prep($private);
        $assoc_order = prep($assoc_order);
        $created = prep($now);
        
        if(!$o['purchase']){
    		$filename = grab('filename');
    		$filename = prep($filename);
        } 
        $insert = "INSERT INTO `".$o['order']."`(`created_by`, `created`, `sales_rep_id`, `companyid`, `contactid`, ".((!$o['purchase'])?"`cust_ref`, `ref_ln`, " : "")."
        `".$o['billing']."`, `ship_to_id`, `freight_carrier_id`, `freight_services_id`, `freight_account_id`, `termsid`, `public_notes`, `private_notes`, `status`) VALUES 
        ($created_by, $created, $save_rep, $cid, $save_contact, ".((!$o['purchase'])?"$assoc_order, $filename," : "")." $bill, $ship, $carrier, $service, $account, $terms, $public, $private, 'Active');";

    //Run the update
		$result = qdb($insert) OR jsonDie(qe().' '.$insert);
        
        //Create a new update number
        $order_number = qid();
        
    }
    if($o['sales'] && (($first_fee_label && $first_fee_amount) || ($second_fee_label && $second_fee_amount))){
    	if ($first_fee_label && $first_fee_amount){
    		$first_fee_label = prep($first_fee_label);
    		$first_fee_amount = prep($first_fee_amount);
    		if($first_fee_id == "New"){
	    		$sales_charge_insert = "
	    		INSERT INTO `sales_charges` (so_number, memo, qty, price) 
	    		VALUES ($order_number, $first_fee_label, 1, $first_fee_amount);";
	    		qdb($sales_charge_insert) or jsonDie(qe()." $sales_charge_insert");
	    	} else {
	    		$sales_charge_update = "
	    		UPDATE `sales_charges` SET 
	    		price = $first_fee_amount,
	    		memo = $first_fee_label
	    		WHERE id = $first_fee_id;";
	    		qdb($sales_charge_update) or jsonDie(qe()." $sales_charge_update");
	    	}
    	}
       	if ($second_fee_label && $second_fee_amount){
    		$second_fee_label = prep($second_fee_label);
    		$second_fee_amount = prep($second_fee_amount);
    		if($second_fee_id == "New"){
	    		$sales_charge_insert = "
	    		INSERT INTO `sales_charges` (so_number, memo, qty, price) 
	    		VALUES ($order_number, $second_fee_label, 1, $second_fee_amount);";
	    		qdb($sales_charge_insert) or jsonDie(qe()." $sales_charge_insert");
	    	} else {
	    		$sales_charge_update = "
	    		UPDATE `sales_charges` SET 
	    		price = $second_fee_amount,
	    		memo = $second_fee_label
	    		WHERE id = $second_fee_id;";
	    		qdb($sales_charge_update) or jsonDie(qe()." $sales_charge_update");
	    	}
    	}
    }
    else{
        
        //Note that the update field doesn't have all the requisite fields
        $macro = "UPDATE ".$o['order']." SET ";
        $macro .= updateNull('sales_rep_id',$rep);
        $macro .= updateNull('companyid',$companyid);
        $macro .= updateNull('contactid',$contact);
        if ($o['purchase']){
            $macro .= updateNull('assoc_order',$assoc_order);
        }
        else{
            $macro .= updateNull('cust_ref',$assoc_order);
//David commented this out 2/13/2017, but we will eventually need to add in a way to change the attached file
//            $macro .= updateNull('ref_ln','NULL');
        }
        $macro .= updateNull($o['billing'],$bill);
        $macro .= updateNull('ship_to_id',$ship);
        $macro .= updateNull('freight_carrier_id',$carrier);
        $macro .= updateNull('freight_services_id',$service);
        $macro .= updateNull('freight_account_id',$account);
        $macro .= updateNull('termsid',$terms);
        $macro .= updateNull('public_notes',$public_notes);
        $macro .= rtrim(updateNull('private_notes',$private),',');
        $macro .= " WHERE `".$o['id']."` = $order_number;";
        
        //Query the database

		$result = qdb($macro) OR jsonDie(qe().' '.$macro);
    }
    


    //RIGHT HAND SUBMIT
    
	$rows = array();
	// $form_rows = json_decode($form_rows,true);
    if(isset($form_rows)){
		if (count($form_rows)>0) {
			$msg .= "<p><strong>Item Details:</strong><br/>";
		}
        foreach ($form_rows as $r){
            $line_number = prep($r['line_number']);
            $item_id = prep($r['part']);
            $record = $r['id'];
            $date = prep(format_date($r['date'],'Y-m-d'));
            $warranty = prep($r['warranty']);
            $conditionid = prep($r['conditionid']);
            $qty = prep($r['qty']);
            $unitPrice = prep(format_price($r['price'],true,'',true));
            $ref_1 = prep($r['ref_1']);
            $ref_1_label = prep($r['ref_1_label']);

			$query2 = "SELECT part, heci FROM parts WHERE id = $item_id; ";
			$result2 = qdb($query2) OR jsonDie(qe().' '.$query2);
			if (mysqli_num_rows($result2)>0) {
				$r2 = mysqli_fetch_assoc($result2);
				$part_strs = explode(' ',$r2['part']);

				$partkey = '';
				if ($r['line_number']) { $partkey = $r['line_number']; }
				$heci = '';
				if ($r2['heci']) {
					$heci = substr($r2['heci'],0,7);
					$partkey .= '.'.$heci;
				} else {
					$partkey .= '.'.$part_strs[0];
				}
				if (! isset($rows[$partkey])) { $rows[$partkey] = array('qty'=>0,'part'=>$part_strs[0],'heci'=>$heci,'ln'=>$r['line_number']); }
				$rows[$partkey]['qty'] += $r['qty'];
			}

            if ($record == 'new'){
                //Build the insert statements
                $line_insert = "INSERT INTO ".$o['item']." (`partid`, `".$o['id']."`, `".$o['date_field']."`, ";
                // $line_insert .=  ($order_type=="Purchase") ? "`po_number`, `receive_date`, " : "`so_number`, `delivery_date`, ";
                $line_insert .=  " `line_number`, `qty`, `price` ";
                $line_insert .= (!$o['repair'] ? ", `ref_1`, `ref_1_label`, `ref_2`, `ref_2_label`  , `warranty`, `conditionid` " : "");
                $line_insert .= ") VALUES ";
                $line_insert .=   "($item_id, '$order_number' , $date, $line_number, $qty , $unitPrice ";
                $line_insert .= (!$o['repair'] ? " , $ref_1, $ref_1_label, NULL, NULL ,$warranty, $conditionid " : "");
                $line_insert .= ");";
                
                
				$result = qdb($line_insert) OR jsonDie(qe().' '.$line_insert);
            }
            else{
                $update = "UPDATE ".$o['item']." SET 
                `partid`= $item_id,
                `line_number`= $line_number,
                `qty`= $qty,
                `price`= $unitPrice, 
                `".$o['date_field']."`= $date ";
if(!$o['repair']){
    $update .= "
                ,`warranty` = ".$warranty."
                ,`conditionid` = ".$conditionid." ";
}
	$update .= "
                WHERE `id` = $record;";
				$line_update = qdb($update) OR jsonDie(qe().' '.$line_update);
            }
        }
    }

	// send order confirmation
	if ($email_confirmation AND ! $DEV_ENV) {
		foreach ($rows as $partkey => $r) {
			if ($r['ln']) { $msg .= '<span style="color:#aaa">'.$r['ln'].'.</span> '; }
			if ($r['heci']) { $msg .= $r['heci'].' '; }
			$msg .= $r['part'];
			if ($r['qty']) { $msg .= ' qty '.$r['qty']; }
			$msg .= '<br/>';
		}
		$recps = array();
		if ($contact) {
			$contact_email = getContact($contact,'id','email');
			if ($contact_email) {
				$recps[] = array($contact_email,getContact($contact,'id','name'));
			}
		}
		if ($addl_recp_email) {
			$recps[] = array($addl_recp_email,$addl_recp_name);
		}
		$recps[] = array('shipping@ven-tel.com','VenTel Shipping');

		$bcc = false;
		if ($rep) {
			$rep_contactid = getRep($rep,'id','contactid');
			$rep_email = getContact($rep_contactid,'id','email');
			$bcc = $rep_email;
		}

		if ($public_notes) {
			$msg .= '<br/>'.str_replace(chr(10),'<BR/>',$public_notes).'<br/>';
		}

		$send_success = send_gmail($msg,$sbj,$recps,$bcc);
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
        'update' => $macro,
		'message' => 'Success',
        'input' => $update,
        'qar' => qar()
    );
    
    echo json_encode($form);
    exit;

?>
