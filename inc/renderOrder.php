<?php
    // Standard includes section (We really need to condense this in a way which makes sense)
    $rootdir = $_SERVER['ROOT_DIR'];

    // include dompdf autoloader
    include_once $rootdir.'/dompdf/autoload.inc.php';
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
    include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//I will eventually have to generalize this: It is useful enough
	function address_out($address_id){
		//General function for handling the standard display of addresses
		$address = '';
		//Address Handling
		$row = getAddresses($address_id);
		$name = $row['name'];
		$street = $row['street'];
		$city = $row['city'];
		$state = $row['state'];
		$zip = $row['postal_code'];
		$country = $row['country'];
		
		//Address Output
		if($name){$address .= $name."<br>";}
		if($street){$address .= $street."<br>";}
		if($city && $state){$address .= $city.", ".$state;}
		else if ($city || $state){ ($address .= $city.$state);}
		if($zip){$address .= "  $zip";}
		
		return $address;
	}
    
    function display_terms($id){
        if($id){
            $terms = "Select terms FROM terms WHERE id = $id;";
            $term = qdb($terms);
            if(mysqli_num_rows($term) > 0){
                $term = mysqli_fetch_assoc($term);
                return $term['terms'];
            }
        }
        else{
            return "None";
        }
    }
    
    // Grab the order number
//    $order_number = grab('on');
//	$order_type = ($_REQUEST['ps'] == 'p' || $_REQUEST['ps'] == 'Purchase') ? "Purchase" : "Sales";

	function renderOrder($order_number,$order_type='Purchase') {
	    
	    //Switch statement to add in more features for now until we have a solid naming convention
	    switch($order_type) {
	        case "Purchase":
	            $order_table = "purchase_orders";
	            $item_table = "purchase_items";
	            $number_type = 'po_number';
		        $date_field = "receive_date";
	            break;
	        case "Sales":
	            $order_table = "sales_orders";
	            $item_table = "sales_items";
	            $number_type = 'so_number';
		        $date_field = "delivery_date";
	            break;
	        case "RMA":
                $order_table = "purchase_orders";
	            $item_table = "purchase_items";
	            $number_type = 'po_number';
		        $date_field = "receive_date";
	            break;
	        default:
	            $order_table = "purchase_orders";
	            $item_table = "purchase_items";
	            $number_type = 'po_number';
		        $date_field = "receive_date";
	            break;
	    }

	    $prep = prep($order_number);

		$order = "SELECT * FROM $order_table WHERE `$number_type` = $order_number;";
		// echo $order;exit;    
		$order_result = qdb($order);
    
		$oi = array();
		if (mysqli_num_rows($order_result) > 0){
			$oi = mysqli_fetch_assoc($order_result);
		}

		$freight_services = ($oi['freight_services_id'])? ' '.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';
		$freight_terms = ($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';

		$items = "SELECT * FROM $item_table WHERE `$number_type` = $order_number;";
		
		//Make a call here to grab RMA's items instead
		//$items = "SELECT * FROM $item_table WHERE `$number_type` = $order_number;";
		//And sort through serials instead of PO_orders
		
		$items_results = qdb($items);
		$item_rows = '';
		foreach($items_results as $item){
			$part_details = current(hecidb($item['partid'],'id'));
			$lineTotal = $item['price']*$item['qty'];
			$total += $lineTotal;

			$item_rows .= '
                <tr>
                    <td class="text-center">'.$item['line_number'].'</td>
                    <td>
        	            '.$part_details['Part'].' &nbsp; '.$part_details['HECI'].'
                        <div class="description">'.$part_details['manf'].' '.$part_details['system'].' '.$part_details['description'].'</div>
                        <div class="'.($order_type == 'RMA' ? '' : 'remove').'">
                            <br>
                            <b>Serials</b>
                            <ul>
                                <li>AAA</li>
                                <li>BBB</li>
                            </ul>
                        </div>
                    </td>
                    <td class="text-center '.($order_type == 'RMA' ? 'remove' : '').'">'.format_date($item[$date_field],'m/d/y').'</td>
                    <td class="text-center '.($order_type == 'RMA' ? 'remove' : '').'">'.getWarranty($item['warranty'],'name').'</td> 
                    <td class="text-center '.($order_type == 'RMA' ? 'remove' : '').'">'.$item['qty'].'</td>
                    <td class="text-right '.($order_type == 'RMA' ? 'remove' : '').'">'.format_price($item['price']).'</td>
                    <td class="text-right '.($order_type == 'RMA' ? 'remove' : '').'">'.format_price($lineTotal).'</td>
                    <td class="'.($order_type == 'RMA' ? '' : 'remove').'">Insert some reason here based on DB</td>
                    <td class="'.($order_type == 'RMA' ? '' : 'remove').'">Text here</td>
                    <td class="'.($order_type == 'RMA' ? '' : 'remove').' text-center">'.$item['qty'].'</td>
				</tr>
			';
		}

		$html_page_str = '
<!DOCTYPE html>
<html>
    <head>
		<title>'.$order_type.' '.$order_number.'</title>
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
            #footer{
                display:none;
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
            #footer{
                display:block;
                position:absolute;
                bottom:60px;
                text-align:left;
                width:100%;
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
        <div id = "ps_bold">
            <h3>'.($order_type == 'RMA' ? 'RMA' : $order_type . ' Order').' #'.$order_number.'</h3>
            <table class="table-full" id = "vendor_add">
				<tr>
					<th class="text-center">'.($order_type == 'RMA' ? 'Customer' : 'Vendor').'</th>
				</tr>
				<tr>
					<td class="half">
						'.address_out($oi["remit_to_id"]).'
					</td>
				</tr>
			</table>
        </div>
        <div id = "letter_head"><b>
            <img src="img/logo.png" style="width:1in;"></img><br>
            Ventura Telephone, LLC <br>
            3037 Golf Course Drive <br>
            Unit 2 <br>
            Ventura, CA 93003<br>
            (805) 212-4959
            </b>
        </div>

        <!-- Shipping info -->
        <h2 class="text-center credit_memo '.($order_type == 'RMA' ? '' : 'remove').'">THIS IS NOT A CREDIT MEMO</h2>
        <table class="table-full">
            <tr>
                <th class="'.($order_type == 'RMA' ? 'hidden' : '').'">Bill To</th>
                <th>'.($order_type == 'RMA' ? 'Return' : 'Ship').' To</th>
            </tr>
            <tr>
                <td class="half '.($order_type == 'RMA' ? 'hidden' : '').'">
                    Please email invoices to:<br/>
					<a href="mailto:accounting@ven-tel.com">accounting@ven-tel.com</a>
                </td>
                <td class="half">
                    '.($order_type == 'RMA' ? 'Ventura Telephone, LLC <br>
                        3037 Golf Course Drive <br>
                        Unit 2 <br>
                        Ventura, CA 93003' : address_out($oi['ship_to_id'])).'
                </td>
            </tr>
        </table>
        
        <!-- Freight Carrier -->
        <table class="table-full" id="order-info">
            <tr>
                <th>'.($order_type == 'RMA' ? 'Sales' : 'Purchase').' Rep</th>
                <th>'.($order_type == 'RMA' ? 'RMA' : 'PO').' Date</th>
                <th>'.($order_type == 'RMA' ? 'Contact' : 'Sales Rep').'</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Terms</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Shipping</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Freight Terms</th>
            </tr>
            <tr>
                <td>
                    '.getContact($oi['sales_rep_id']).' <br>
                    '.getContact($oi['sales_rep_id'],'id','phone').'<br>
                    '.getContact($oi['sales_rep_id'],'id','email').'
                </td>
                <td class="text-center">
                    '.format_date($oi['created'],'F j, Y').'
                </td>
                <td class="text-center">
                    '.getContact($oi['contactid']).' <br>
                    '.getContact($oi['contactid'],'id','phone').'<br>
                    '.getContact($oi['contactid'],'id','email').'
                </td>
                <td class="text-center '.($order_type == 'RMA' ? 'remove' : '').'">
                    '.display_terms($oi['termsid']).'
                </td>
                <td class="text-center '.($order_type == 'RMA' ? 'remove' : '').'">'.getFreight('carrier',$oi['freight_carrier_id'],'','name').'
					'.$freight_services.'</td>
                <td class="'.($order_type == 'RMA' ? 'remove' : '').'">'.$freight_terms.'</td>
            </tr>
        </table>

<!-- Items Table -->
        <table class="table-full table-striped table-condensed">
            <tr>
                <th>Ln#</th>
                <th>Description</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Due Date</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Warranty</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Qty</th>
                <th>'.($order_type == 'RMA' ? 'Reason' : 'Price').'</th>
                <th>'.($order_type == 'RMA' ? 'Disposition' : 'Ext Price').'</th>
                <th class="'.($order_type == 'RMA' ? '' : 'remove').'">Qty</th>
            </tr>
            
			'.$item_rows.'
		</table>
        <table class="table-full '.($order_type == 'RMA' ? 'remove' : '').'">
            <!-- Subtotal -->
            <tr>
                <td style="text-align:right;border:none;">Subtotal</td>
                <td class="text-price">
                    '.format_price($total).'
                </td>
            </tr>
            <!--  -->
            <tr>
                <td style="text-align:right;border:none;">Freight</td>
                <td class="text-price">
                    '.format_price(0).'
                </td>
            </tr>
            <tr>
                <td style="text-align:right;border:none;">Tax 0.00%</td>
                <td class="text-price">
                    $0.00
                </td>
            </tr>
            <tr class="total">
                <td style="text-align:right;"><b>Total</b></td>
                <td id = "total" class="text-price">
                    <b>'.format_price($total).'</b>
                </td>
            </tr>
        </table>
        <div id="footer">
            <p class="'.($order_type == 'RMA' ? 'remove' : '').'">
                Terms and Conditions:<br><br>
                Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications
                listed herein. Shipment of goods or execution of services against this PO specifies agreement with our
                terms.<br><br>
                Invoicing: VenTel requires that vendors provide ONE invoice per purchase order. Items on the invoice must
                match items on the purchase order. Due date for payment terms begins when the order is received
                complete. Failure to abide by these terms may result in delayed payment at no fault by the purchaser.
                Please communicate all questions regarding these conditions within 15 days.
            </p>
            <p class="'.($order_type == 'RMA' ? '' : 'remove').'">
                RMA text goes here.
            </p>
        </div>
    </body>
</html>
		';

	return ($html_page_str);
}

/*
die($html_page_str);
//    $po_html = renderPHPtoHTML($html_page_str);

    // reference the Dompdf namespace
    use Dompdf\Dompdf;

    // instantiate and use the dompdf class
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html_page_str);

    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4');//, 'portrait');

    // Render the HTML as PDF
    $dompdf->render();

    // set HTTP response headers
//	header('Content-Type:application/pdf');
//	header("Cache-Control: max-age=0");
//	header("Accept-Ranges: none");
//	header("Content-Disposition: attachment; filename=PO".$order_number.".pdf");

    // Output the generated PDF to Browser
    $dompdf->stream();
    //$output = $dompdf->output();
	//echo $output;
*/
?>
