<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';

	$DEBUG = 0;
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function shipEmail($order_number,$order_type,$datetime) {
		$init = true;
		$contactid = 0;
		$conf_contactid = 0;

		$subj_order = $order_number;
		$email_body_html = "";//<p>Order# ".$subj_order." tracking number(s):</p><br/><br/>";

		// Grab the order information from the sales table
		$query = "SELECT si.*, so.* FROM sales_items si, sales_orders so WHERE si.so_number = ".fres($order_number)." AND so.so_number = si.so_number;";
		$result = qedb($query);

		if (mysqli_num_rows($result)==0) { return false; }

			$r = mysqli_fetch_assoc($result);
			if ($r['cust_ref']) { $subj_order = $r['cust_ref']; }
			if ($r['conf_contactid']) { $conf_contactid = $r['conf_contactid']; }
			$email_body_html = "<p>Tracking for Order# ".$subj_order.":</p><br/><br/>";

			if($r['contactid']) {
				$contactid = $r['contactid'];

		        $query2 = "SELECT * FROM packages ";
				$query2 .= "WHERE order_type='".res($order_type)."' AND order_number = '".res($r['so_number'])."' ";
				$query2 .= "AND tracking_no IS NOT NULL AND datetime = '".res($datetime)."' ";
				$query2 .= "ORDER BY package_no ASC;";
		        $result2 = qedb($query2);
				while($r2 = mysqli_fetch_assoc($result2)) {
					$tracking = explode(',', $r2['tracking_no']);
					$email_body_html .= '<span style="color:#aaa">'.$r2['package_no'].'.</span> ';
					foreach($tracking as $tracking_no) {
						$email_body_html .= ($r['freight_carrier_id'] == 1 ? '<a target="_blank" href="https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums='.$tracking_no.'">' . $tracking_no . '</a>' : '') . ' ';
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
			        
			}

		if($contactid) {
			$email_subject = 'Order# ' .$subj_order . ' Tracking';

			if ($GLOBALS['DEV_ENV']) {
				$recipients = array('david@ven-tel.com');
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
			    $this->setError(json_encode(array('message'=>$SEND_ERR)));
			}
		}

		return $email_body_html;
	}
