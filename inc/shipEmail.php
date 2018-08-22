<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';

	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	$DEBUG = 0;
	$ERROR = '';

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function shipEmail($order_number, $order_type, $shipment_date) {
		global $ERROR, $DEV_ENV;
		$contactid = 0;
		$conf_contactid = 0;

		$subj_order = $order_number;
		$email_body_html = '';

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
		if ($r['cust_ref']) { $subj_order = $r['cust_ref']; }
		if ($r['conf_contactid']) { $conf_contactid = $r['conf_contactid']; }

		$email_body_html = "<p>Tracking for Order# ".$subj_order.":</p><br/><br/>";

        $query2 = "SELECT * FROM packages WHERE order_type=".fres($order_type)." AND order_number = ".fres($r['so_number'])." ";
		$query2 .= "AND tracking_no IS NOT NULL AND datetime=".fres($shipment_date)." ORDER BY package_no ASC;";
        $result2 = qedb($query2);
		if (mysqli_num_rows($result2)==0) {
			$ERROR = "No tracking records found for Orer# ". $order_number;
			return false;
		}

		while($r2 = mysqli_fetch_assoc($result2)) {
			$tracking = explode(',', $r2['tracking_no']);
			$email_body_html .= '<span style="color:#aaa">'.$r2['package_no'].'.</span> ';

			foreach($tracking as $tracking_no) {
				$email_body_html .= ($r['freight_carrier_id'] == 1 ? '<a target="_blank" href="https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums='.$tracking_no.'">' . $tracking_no . '</a>' : $tracking_no) . ' ';
			}

			$email_body_html .= '<br/>';

			// Get package content
			$query3 = "SELECT serial_no, partid FROM package_contents p, inventory i WHERE packageid = ".fres($r2['id'])." AND i.id = p.serialid AND serial_no IS NOT NULL;";
			$result3 = qedb($query3);

			$parts = array();

			while($r3 = mysqli_fetch_assoc($result3)) {
				$parts[$r3['partid']][] = $r3['serial_no'];
			}
			
			foreach($parts as $partid => $rows) {
				$r = reset(hecidb($partid, 'id'));
				$parts = explode(' ',$r['part']);

				$display = "<span style='color: #aaa;'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
		
				$H = hecidb($partid,'id');
				$P = $H[$partid];
	
				$parts = explode(' ',$H[$partid]['part']);
				$part_name = $parts[0];
	
				$email_body_html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$display;
				$email_body_html .= '<br/>';
	
				foreach($rows as $item) {
					$email_body_html .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . $item;
					$email_body_html .= '<br/>';
				}
			}

			$email_body_html .= '<br/>';
		}

		$email_subject = 'Order# ' .$subj_order . ' Tracking';
		$recipients = getContact($contactid, 'id', 'email');

		if ($DEV_ENV) {
			$recipients = array('andrew@ven-tel.com');
		} else {
			$recipients = array(
				0 => array(getContact($contactid, 'id', 'email'),getContact($contactid, 'id', 'name')),
			);
			if ($conf_contactid) {
				$recipients[] = array(getContact($conf_contactid, 'id', 'email'),getContact($conf_contactid, 'id', 'name'));
			}
			$bcc = 'david@ven-tel.com';
		}

		$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
		if ($send_success) {
		    // echo json_encode(array('message'=>'Success'));
		} else {
		    $ERROR = "Email Failed to Send";
			return false;
		}

		return $email_body_html;
	}
