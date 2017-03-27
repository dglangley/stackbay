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
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/invoice.php';
	

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
	    $o = array();
	    //Switch statement to add in more features for now until we have a solid naming convention
	   // switch($order_type) {
	   //     case "Purchase":
	   //         $o['order'] = "purchase_orders";
	   //         $o['items'] = "purchase_items";
	   //         $o['type'] = 'po_number';
		  //      $o['date'] = "receive_date";
	   //         break;
	   //     case "Sales":
	   //         $o['order'] = "sales_orders";
	   //         $o['items'] = "sales_items";
	   //         $o['type'] = 'so_number';
		  //      $o['date'] = "delivery_date";
	   //         break;
	   //     case "RMA":
    //             $o['order'] = "purchase_orders";
	   //         $o['items'] = "purchase_items";
	   //         $o['type'] = 'po_number';
		  //      $o['date'] = "receive_date";
	   //         break;
	   //     default:
	   //         $o['order'] = "purchase_orders";
	   //         $o['items'] = "purchase_items";
	   //         $o['type'] = 'po_number';
		  //      $o['date'] = "receive_date";
	   //         break;
	   // }

        $o = o_params($order_type);
	    $prep = prep($order_number);
        
        $added_order ="";
        $added_order_join = "";
        $serials = array();
        if ($o['type'] == "Invoice"){
            $added_order = ", `sales_orders`, `terms` ";
            $added_order_join = " AND `sales_orders`.so_number = order_number AND termsid = terms.id";
            $serials = getInvoicedInventory($order_number, "`serial_no`, ``");
            
        } 
        
		$order = "SELECT * FROM `".$o['order']."`$added_order WHERE `".$o['id']."` = $order_number $added_order_join;";
		
// 		echo $order;exit;
		$order_result = qdb($order);
    
		$oi = array();
		if (mysqli_num_rows($order_result) > 0){
			$oi = mysqli_fetch_assoc($order_result);
            // echo("<pre>");
            // print_r($oi);
            // echo("</pre>");
            // exit;
		}

		$freight_services = ($oi['freight_services_id'])? ' '.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';
		$freight_terms = ($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';

		$items = "SELECT * FROM ".$o['item']." WHERE `".$o['id']."` = $order_number;";
		//Make a call here to grab RMA's items instead
		
		//And sort through serials instead of PO_orders
		
		$items_results = qdb($items);

		$item_rows = '';
		foreach($items_results as $item){
			$part_details = current(hecidb($item['partid'],'id'));
			$lineTotal = $item['price']*$item['qty'];
			$total += $lineTotal;
			
			//FREIGHT CALCULATION HERE FOR INVOICE (based off the payment type/shipping account)
            
			$item_rows .= '
                <tr>
                    <td class="text-center">'.$item['line_number'].'</td>
                    <td>
        	            '.$part_details['Part'].' &nbsp; '.$part_details['HECI'].'
                        <div class="description">'.$part_details['manf'].' '.$part_details['system'].' '.$part_details['description'].'</div>
                        <div class="'.($order_type == 'RMA' || $o['type']  =="Invoice" ? '' : 'remove').'" style = "padding-left:5em;">
                            <br>
                            <ul>';
                                if ($serials){
                                    //Add Serials label
                                    foreach($serials as $serial){
                                        $item_rows .= "<li>".$serial['serial_no']."</li>";       
                                    }
                                }
                            $item_rows .='</ul>
                        </div>
                    </td>
                    <td class="text-center '.(($order_type == 'RMA' || $o['type'] == "Invoice")? 'remove' : '').'">'.format_date($item[$date_field],'m/d/y').'</td>
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
    <body>';
$html_page_str .='
        <div id = "ps_bold">
            <h3>'.$o['header'].' #'.$order_number.'</h3>
            <table class="table-full" id = "vendor_add">
				<tr>
					<th class="text-center">'.$o['client'].'</th>
				</tr>
				<tr>
					<td class="half">
						'.address_out($oi["remit_to_id"]).'
					</td>
				</tr>
			</table>
        </div>
        ';
$html_page_str .='
        <div id = "letter_head"><b>
            <img src="img/logo.png" style="width:1in;"></img><br>
            Ventura Telephone, LLC <br>
            3037 Golf Course Drive <br>
            Unit 2 <br>
            Ventura, CA 93003<br>
            (805) 212-4959
            </b>
        </div>
';
$html_page_str .='
        <!-- Shipping info -->
        <h2 class="text-center credit_memo '.($order_type == 'RMA' ? '' : 'remove').'">THIS IS NOT A CREDIT MEMO</h2>
        <table class="table-full">
            <tr>
                <th class="'.($order_type == 'RMA' ? 'hidden' : '').'">Bill To</th>
                <th>'.($order_type == 'RMA' ? 'Return' : 'Ship').' To</th>
            </tr>
            <tr>
                <td class="half '.($order_type == 'RMA' ? 'hidden' : '').'">';

if($o['type'] != "Invoice"){
$html_page_str .='
                    Please email invoices to:<br/>
					<a href="mailto:accounting@ven-tel.com">accounting@ven-tel.com</a>
				';
} else{
    $html_page_str .= getContact($oi['contactid'])."<br>";
    $html_page_str .= address_out($oi['bill_to_id']);
}
$html_page_str .= '
                </td>
                <td class="half">
                    '.($order_type == 'RMA' ? 'Ventura Telephone, LLC <br>
                        3037 Golf Course Drive <br>
                        Unit 2 <br>
                        Ventura, CA 93003' : address_out($oi['ship_to_id'])).'
                </td>
            </tr>
        </table>
';
$rep_name = getContact($oi['sales_rep_id']);
$rep_phone = getContact($oi['sales_rep_id'],'id','phone');
$rep_email = getContact($oi['sales_rep_id'],'id','email');

$contact_name = getContact($oi['contactid']);
$contact_phone = getContact($oi['contactid'],'id','phone');
$contact_email = getContact($oi['contactid'],'id','email');

$html_page_str .='
        <!-- Freight Carrier -->
        <table class="table-full" id="order-info">
            <tr>
                <th>'.$o['rep_type'].' Rep</th>
                <th>'.$o['date_label'].' Date</th>
                '.(($o['type'] == 'Invoice')? "<th>Payment Due Date </th>" : '').'
                <th>'.$o['contact_col'].'</th>
                <th class="'.($order_type == 'RMA' ? 'remove' : '').'">Terms</th>
                <th class="'.($o['type'] == 'RMA' || $o['type'] == "Invoice" ? 'remove' : '').'">Shipping</th>
                '.(($o['type'] == 'Invoice')? "<th>PO # </th>" : '').'
                <th class="'.($order_type == 'RMA' || $o['type'] == "Invoice" ? 'remove' : '').'">Freight Terms</th>
            </tr>
            <tr>
                <td>
                    '.$rep_name.' <br>
                    '.(($rep_phone)?$rep_phone."<br>" : "").'
                    '.$rep_email.'
                </td>
                <td class="text-center">
                    '.format_date($oi['created'],'F j, Y').'
                </td>';
                if($o['type'] == "Invoice"){
                    $html_page_str .= '
                    <td class="text-center">
                        '.(($oi['type'] == "Credit")? format_date($oi['date_invoiced'],'F j, Y',array("d" => $oi['days'])) : format_date($oi['date_invoiced'],'F j, Y')).'
                    </td>
                    <td>'.$oi['so_number'].'</td>
                    ';            
                }else{
                    $html_page_str .= '<td class="text-center">
                        '.$contact_name.' <br>
                        '.(($contact_phone)?$contact_phone."<br>" : "").'
                        '.$contact_email.'
                        </td>';
                }
                
$html_page_str .= '<td class="text-center '.($order_type == 'RMA' ? 'remove' : '').'">
                    '.display_terms($oi['termsid']).'
                </td>';
$html_page_str .='
                <td class="text-center '.(($order_type == 'RMA' || $o['type'] == "Invoice") ? 'remove' : '').'">'.getFreight('carrier',$oi['freight_carrier_id'],'','name').'
					'.$freight_services.'</td>
                <td class="'.(($order_type == 'RMA' || $o['type'] == "Invoice") ? 'remove' : '').'">'.$freight_terms.'</td>
                '.(($o['type'] == 'Invoice')? "<td>".$oi['cust_ref']."</td>" : '').'
            </tr>
        </table>

<!-- Items Table -->
        <table class="table-full table-striped table-condensed">
            <tr>
                <th>Ln#</th>
                <th>Description</th>
                <th class="'.(($order_type == 'RMA' || $o['type'] == 'Invoice')? 'remove' : '').'">Due Date</th>
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
                Return Instructions:<br><br>
                Print and return this form with the product(s) to be returned. Improperly packaged or incomplete product(s) will void this RMA. Returned product(s) must match this RMA exactly, substitutes are not allowed. RMA is valid for 30 calendar days.
                <br><br>
                Product(s) returned can be replaced, credited or refunded at Ventura Telephone\'s sole discretion. Product(s) remain billable in full if not credited or refunded. No Trouble Found ("NTF") product(s) are subject to a restocking fee. RMA processing may take up to 30 calendar days after receipt.
                <br><br>
                Please ship UPS Ground on Account# 360E2A.
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
