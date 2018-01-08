<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';

	$DEBUG = 0;
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	function shipEmail($item_id) {
		$email_body_html = '';

		// Grab the order information from the sales table
		$query = "SELECT si.*, so.* FROM sales_items si, sales_orders so, packages p WHERE si.id = ".fres($item_id)." AND so.so_number = si.so_number;";

		$result = qedb($query);

		if (mysqli_num_rows($result) AND ! $DEV_ENV) {
			$r = mysqli_fetch_assoc($result);

			if($r['contactid']) {

				//print '<pre>' . print_r($r,true) . '</pre>';

				$email_body_html = '
				<html><head><style id="stndz-style"></style>
					<title>Sale 19940</title>
					<link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet"> 
			        <style type="text/css">
			            body{
			                font-size:11px;
			            }
						body, table, td {
			                font-family: "Lato", sans-serif;
						}
			            table {
			                border-collapse: collapse;
			                margin-bottom:40px;
			            }
						table.table-condensed {
							margin:0px;
						}
						th {
							text-transform:uppercase;
							background-color:#eee;
							text-align: center;
						}
			            td{
			                padding:5px;
			                vertical-align:top;
			            }
						a {
							color:black;
							text-decoration:none;
						}
						ul {
						    list-style-type: none;
						    padding: 0;
						}
						.text-right {
							text-align:right;
						}
						.text-center {
						    text-align: center;
						}
						.text-price {
							width:100px;
							text-align:right;
						}
						.description {
							font-size:6pt;
							color:#aaa;
						}
						.credit_memo {
						    float: left; 
						    margin-bottom: -35px;
						    width: 50%;
						}
						.hidden {
						    visibility: hidden;
						}
						.remove {
						    display: none;
						}
			            table.table-full {
			                width:100%;
			            }
			            table.table-modified {
			                width:75%;
			            }
						table.table-striped tr:nth-child(even) td {
							background-color: #ffffff;
						}
						table.table-striped tr:nth-child(odd) td {
							background-color: #f9f9f9;
						}
			            .half {
			                width:50%;
			            }
			            body{
			                /*margin:0.5in;*/
			            }
			            td{
			                text-align:center;
			            }
			            #ps_bold{
							float:right;
			                font-size:13pt;
			                text-align:right;
							width:50%;
			            }
						#ps_bold h3 {
							padding-top:0px;
							margin-top:0px;
						}
			            #letter_head{
							margin-bottom:40px;
			                font-size:9pt;
			            }
			            
			            #vendor_add{
			                font-size:11px;
							text-align:left;
			            }
			            .total td {
			                background-color:#eee;
			            }
			            #spacer {
			                width:100%;
			                height:100px;
			            }
			        </style>
			    </head>
			    <body>
			        <div id="ps_bold">
			            <h3>Shipping for Order# '.$r['so_number'].'</h3>
			            <table class="table-full" id="vendor_add">
							<tbody><tr>
								<th class="text-center">Company</th>
							</tr>
							<tr>
								<td class="half">
									'.getContact($r['contactid']).'<BR>
			                        '.getCompany($r['companyid']).'
								</td>
							</tr>
						</tbody></table>
			        </div>
			        
			        <div id="letter_head"><b>
			            <img src="https://www.stackbay.com/img/logo.png" style="width:1in;"><br>
			            Ventura Telephone, LLC <br>
			            3037 Golf Course Drive <br>
			            Unit 2 <br>
			            Ventura, CA 93003<br>
			            (805) 212-4959
			            </b>
			        </div>

			        <!-- Shipping info -->
			        <h2 class="text-center credit_memo remove">THIS IS NOT A CREDIT MEMO</h2>
			        <table class="table-full">
			            <tbody><tr>
			                <th class="">Ship To</th>             
			            </tr>
			            <tr>
			                <td class="half">
			                	'.address_out($r['ship_to_id']).'
			                </td>
			                 <td class="half"></td>
			            </tr>
			        </tbody></table>

			        <table class="table-full table-striped table-condensed">
			            <tbody><tr>
			                <th>Package#</th>
			                <th>Tracking#</th>
			            </tr>';
			         
			        $query2 = "SELECT * FROM packages WHERE order_type='Sale' AND order_number = ".fres($r['so_number'])." AND tracking_no IS NOT NULL ORDER BY package_no DESC;";
			        $result2 = qedb($query2);
					
					while($r2 = mysqli_fetch_assoc($result2)) {
						$tracking = explode(',', $r2['tracking_no']);
				        $email_body_html .=  '    <tr>
			            	<td>'.$r2['package_no'].'</td>
							<td>';
							foreach($tracking as $tracking_no) {
								$email_body_html .= ($r['freight_carrier_id'] == 1 ? '<a target="_blank" href="https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums='.$tracking_no.'">' . $tracking_no . '</a>' : '') . ' ';
							}
						$email_body_html .= '</td>';
					}
			        
			        $email_body_html .=  '</tr>
						
					</tbody></table>
				 	<div id="footer">
			        </div>
			    

				</body></html>
				';

				$email_subject = 'Tracking Information for Order# ' .$r['so_number'];
				$recipients = array(getContact($r['contactid'], 'id', 'email'));

				//print_r($recipients);
				$bcc = 'david@ven-tel.com';
				
				$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
				if ($send_success) {
				    // echo json_encode(array('message'=>'Success'));
				} else {
				    $this->setError(json_encode(array('message'=>$SEND_ERR)));
				}
			}
		}

		return $email_body_html;
	}