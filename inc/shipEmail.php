<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';

	$DEBUG = 0;
	$ERROR = '';

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function shipEmail($order_number, $order_type, $shipment_date) {
		global $ERROR;
		$contactid = 0;
		$email_body_html = '';

		$email_body_html .= "<p>Your tracking number(s) are:</p><br/><br/>";

		// Grab the order information from the sales table
		$query = "SELECT si.*, so.* FROM sales_items si, sales_orders so WHERE si.so_number = ".fres($order_number)." AND so.so_number = si.so_number;";

		$result = qedb($query);

		if (mysqli_num_rows($result) == 0) {
			$ERROR = "No Record Found for Order# " . $order_number;
			return false;
		} 

		$r = mysqli_fetch_assoc($result);

		if (empty($r['contactid'])) {
			$ERROR = "No Contact Found for Order# " . $order_number;
			return false;
		}

		$contactid = $r['contactid'];
		//print '<pre>' . print_r($r,true) . '</pre>';
	         
        $query2 = "SELECT * FROM packages WHERE order_type=".fres($order_type)." AND order_number = ".fres($r['so_number'])." AND tracking_no IS NOT NULL AND datetime=".fres($shipment_date)." ORDER BY package_no ASC;";
        $result2 = qedb($query2);
		
		while($r2 = mysqli_fetch_assoc($result2)) {
			$tracking = explode(',', $r2['tracking_no']);
			$email_body_html .= '<span style="color:#aaa">'.$r2['package_no'].'.</span> ';
			
			foreach($tracking as $tracking_no) {
				$email_body_html .= ($r['freight_carrier_id'] == 1 ? '<a target="_blank" href="https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums='.$tracking_no.'">' . $tracking_no . '</a>' : $tracking_no) . ' ';
			}

			$email_body_html .= '<br/>';

			// Get package content
			$query3 = "SELECT serial_no FROM package_contents p, inventory i WHERE packageid = ".fres($r2['id'])." AND i.id = p.serialid AND serial_no IS NOT NULL;";
			$result3 = qedb($query3);

			while($r3 = mysqli_fetch_assoc($result3)) {
				$email_body_html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $r3['serial_no'];
				$email_body_html .= '<br/>';
			}

			$email_body_html .= '<br/>';
		}

		$email_subject = 'Order# ' .$order_number . ' Tracking';
		$recipients = getContact($contactid, 'id', 'email');
		//$recipients = array('andrew@ven-tel.com');
		//echo getContact($contactid, 'id', 'email');
		//print_r($recipients);
		$bcc = 'david@ven-tel.com';
		
		$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
		if ($send_success) {
		    // echo json_encode(array('message'=>'Success'));
		} else {
		    $ERROR = "Email Failed to Send";
			return false;
		}

		return $email_body_html;
	}