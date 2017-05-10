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
    include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/invoice.php';
	
    function getPackageTracking($so_number) {
        $tracking = array();
        $html = '';

        $packages = "SELECT package_no, tracking_no FROM packages WHERE order_number = ".prep($so_number).";";
        $result = qdb($packages) OR die(qe().'<BR>'.$packages);
        while ($r = mysqli_fetch_assoc($result)) {
            $tracking[] = $r;
        }

        foreach($tracking as $item) {
            $html .= "<tr>";
                $html .= "<td>";
                    $html .= $item['package_no'];
                $html .= "</td>";
                $html .= "<td>";
                    $html .= $item['tracking_no'];
                $html .= "</td>";
            $html .= "</tr>";
        }

        return $html;
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
    function getCompanyAddress($companyid){
        $select = "SELECT * FROM company_addresses WHERE companyid LIKE ".prep($companyid).";";
        $company_address_result = qdb($select) or die(qe()." | $select");
        if(mysqli_num_rows($results)){
            $results = mysqli_fetch_assoc($company_address_result);
        }
        
    }
    // Grab the order number
//    $order_number = grab('on');
//	$order_type = ($_REQUEST['ps'] == 'p' || $_REQUEST['ps'] == 'Purchase') ? "Purchase" : "Sales";

	function renderOrder($order_number,$order_type='Purchase') {
	    $o = array();
	    //Switch statement to add in more features for now until we have a solid naming convention

        $o = o_params($order_type);
	    $prep = prep($order_number);
        
        $added_order ="";
        $added_order_join = "";
        $serials = array();
        if ($o['invoice']){
            $added_order = ", `sales_orders`, `terms` ";
            $added_order_join = " AND `sales_orders`.so_number = order_number AND termsid = terms.id";
            $serials = getInvoicedInventory($order_number, "`serial_no`, ``");
        } else if ($o['credit']){
            $added_order = ", `sales_orders` ";
            $added_order_join = " AND sales_orders.so_number = order_num and order_type = 'Sales'";
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

		$items = "SELECT * FROM ".$o['item']." WHERE `".$o['item_id']."` = $order_number;";
		if($o['type'] == "Credit" && is_numeric($order_number)){
		    $items = 'SELECT sci.*, sci.id as scid, sci.amount as price ,GROUP_CONCAT(i.serial_no) as serials, COUNT(i.serial_no) as qty,i.partid 
		    FROM inventory_history ih, inventory i, '.$o['tables'].' 
		    AND sc.`'.$o['id'].'` = '.$order_number.' 
		    AND ih.field_changed = "sales_item_id" 
		    AND sci.sales_item_id = ih.value 
		    AND i.id = ih.invid GROUP BY sci.cid;';
		  //  echo($items);
		}
		//Make a call here to grab RMA's items instead
		
		//And sort through serials instead of PO_orders
		
		$items_results = qdb($items) or die (qe()." | ".$items);
        
        //Process Item results of the credit to associate the serials into a nested array
		$item_rows = '';
        $i = 0;
		foreach($items_results as $item){
		    if($o['type'] == "Credit"){
                $serials = explode(",",$item['serials']); 
            }
            
            $price = 0.00;
            if ($item['price']){
                $price = $item['price'];
            } else if ($item['amount']){
                $price = $item['amount'];
            }
			$part_details = current(hecidb($item['partid'],'id'));
			$part_strs = explode(' ',$part_details['Part']);
			$lineTotal = $price*$item['qty'];
			$total += $lineTotal;
			
			//FREIGHT CALCULATION HERE FOR INVOICE (based off the payment type/shipping account)
			$item_rows .= '
                <tr>
                    <td class="text-center">'.($o['credit']? ++$i : $item['line_number']).'</td>
                    <td>
        	            '.$part_strs[0].' &nbsp; '.$part_details['HECI'].'
                        <div class="description '.$part_details['manf'].' '.$part_details['system'].' '.$part_details['description'].'</div>
                        <div class="'.($o['rma'] || $o['invoice'] ? '' : 'remove').'" style = "padding-left:5em;">
                            <br>
                            <ul>';
                                if ($serials && !$o['credit']){
                                    //Add Serials label
                                    foreach($serials as $serial){
                                        $item_rows .= "<li>".$serial['serial_no']."</li>";       
                                    }
                                }
                            $item_rows .='</ul>
                        </div>
                    </td>
                    <td class="text-center '.(($o['due_date'])? '' : 'remove' ).'">'.format_date($item[$o['date_field']],'m/d/y').'</td>
                    <td class="text-center '.($o['warranty'] ? '' : 'remove').'">'.getWarranty($item['warranty'],'name').'</td> 
                    ';
                    $item_rows .= ($o['purchase']? '<td>'.getCondition($item['conditionid']).'</td>' : "");
                    if($o['credit']){
                        $item_rows .= "<td>";
                        foreach($serials as $serial){
                            $item_rows .= "$serial<br>";
                        }
                        $item_rows .= "</td>";
                    }
                    $item_rows .= '
                    <td class="text-center '.($o['rma'] ? 'remove' : '').'">'.$item['qty'].'</td>
                    <td class="text-right '.($o['rma'] ? 'remove' : '').'">'.format_price($price).'</td>
                    <td class="text-right '.($o['rma'] ? 'remove' : '').'">'.format_price($lineTotal).'</td>
                    <td class="'.($o['rma'] ? '' : 'remove').'">Insert some reason here based on DB</td>
                    <td class="'.($o['rma'] ? '' : 'remove').'">Text here</td>
                    <td class="'.($o['rma'] ? '' : 'remove').' text-center">'.$item['qty'].'</td>
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
            <table class="table-full" id = "vendor_add" '.($o['credit']? "style='display:none;'": "").'>
				<tr>
					<th class="text-center">'.$o['client'].'</th>
				</tr>
				<tr>
					<td class="half">
                        '.(getContact($oi['contactid']) ? getContact($oi['contactid']) . '<br>' : "").'
						'.(address_out($oi["bill_to_id"]) ? address_out($oi["bill_to_id"]) : address_out($oi["remit_to_id"])).'
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

//Begin addresses table
$html_page_str .='
        <!-- Shipping info -->
        <h2 class="text-center credit_memo '.($o['rma'] ? '' : 'remove').'">THIS IS NOT A CREDIT MEMO</h2>
        <table class="table-full">
            <tr>';
if(!$o['credit']){
$html_page_str .='
                <th class="'.($o['rma'] ? 'hidden' : '').'">Bill To</th>
                <th>'.($o['rma'] ? 'Return' : 'Ship').' To</th>';
} else {
    $html_page_str .= '
    <th>Customer</th>
    <th>Credit Date</th>
    <th>PO #</th>';
    if ($oi['rma']){
    $html_page_str.='
        <th>RMA #</th>
    ';
    }
}
$html_page_str .='                
            </tr>
            <tr>
                <td class="half '.($o['rma'] ? 'hidden' : '').'">';

if(!$o['invoice'] && !$o['credit']){
$html_page_str .='
                    Please email invoices to:<br/>
					<a href="mailto:accounting@ven-tel.com">accounting@ven-tel.com</a>
				';
} else{
    //$html_page_str .= getContact($oi['contactid'])."<br>";
    $html_page_str .= address_out($oi['bill_to_id'], 'street');
}

$html_page_str.='</td>';

if(!$o['credit']){
$html_page_str .= '
                <td class="half">
                    '.($o['rma'] ? 'Ventura Telephone, LLC <br>
                        3037 Golf Course Drive <br>
                        Unit 2 <br>
                        Ventura, CA 93003' : address_out($oi['ship_to_id'])).'
                </td>';
}
else{
    $html_page_str .='
    <td class="text-center">'.format_date($oi['date_created'],"M j, Y").'</td>
    <td class="text-center">'.$oi['cust_ref'].'</td>';
    
    if($oi['rma']){
        $html_page_str .= '<td class= "pull-center">'.$oi['rma'].'</td>';
    }
}
$html_page_str .= '
            </tr>
        </table>
';
//End of the addresses table

$rep_name = getContact($oi['sales_rep_id']);
$rep_phone = getContact($oi['sales_rep_id'],'id','phone');
$rep_email = getContact($oi['sales_rep_id'],'id','email');

$contact_name = getContact($oi['contactid']);
$contact_phone = getContact($oi['contactid'],'id','phone');
$contact_email = getContact($oi['contactid'],'id','email');


//Shipping information table
if(!$o['credit']){
$html_page_str .='
        <!-- Freight Carrier -->
        <table class="table-full" id="order-info">
            <tr>
                <th>'.$o['rep_type'].' Rep</th>
                <th>'.$o['date_label'].' Date</th>
                '.(($o['invoice'])? "<th>Payment Due Date </th>" : '').'
                <th>'.$o['contact_col'].'</th>
                <th class="'.($o['rma'] ? 'remove' : '').'">Terms</th>
                <th class="'.($o['rma'] ? 'remove' : '').'">Shipping</th>
                <th class="'.($o['rma']  ? 'remove' : '').'">Freight Terms</th>
                '.(($o['invoice'])? "<th>PO # </th>" : '').'
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
                if($o['invoice']){
                    $html_page_str .= '
                    <td class="text-center">
                        '.(($oi['credit'])? format_date($oi['date_invoiced'],'F j, Y',array("d" => $oi['days'])) : format_date($oi['date_invoiced'],'F j, Y')).'
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
                
$html_page_str .= '<td class="text-center '.($o['rma'] ? 'remove' : '').'">
                    '.display_terms($oi['termsid']).'
                </td>';
$html_page_str .='
                <td class="text-center '.(($o['rma']) ? 'remove' : '').'">'.getFreight('carrier',$oi['freight_carrier_id'],'','name').'
					'.$freight_services.'</td>
                <td class="'.(($o['rma']) ? 'remove' : '').'">'.$freight_terms.'</td>
                '.(($o['invoice'])? "<td>".$oi['cust_ref']."</td>" : '').'
            </tr>
        </table>';
        //End of the shipping information table
}

$html_page_str .= '
<!-- Items Table -->
        <table class="table-full table-striped table-condensed">
            <tr>
                <th>Ln#</th>
                <th>Description</th>
                <th class="'.(($o['due_date'])? '' : 'remove' ).'">Due Date</th>
                <th class="'.($o['warranty']? '' : 'remove').'">Warranty</th>
                '.($o['credit']? '<th>Serials</th>' : "").'
                '.($o['purchase']? '<th>Cond</th>' : "").'
                <th class="'.($o['rma'] ? 'remove' : '').'">Qty</th>
                <th>'.$o['price'].'</th>
                <th>'.($o['rma'] ? 'Disposition' : 'Ext Price').'</th>
                <th class="'.($o['rma'] ? '' : 'remove').'">Qty</th>
            </tr>
            
			'.$item_rows.'
		</table>
        <table class="table-full '.($o['rma'] ? 'remove' : '').'">
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
        </table>';
$package_list = getPackageTracking($oi['so_number']);
if($o['type'] == 'Invoice'){
        $html_page_str .='  <table class="table-full table-striped table-condensed">
                    <tr>
                        <th>Package #</th>
                       <th>Tracking</th>
                    </tr>'
                    .$package_list.
                '</table>';
}

        $html_page_str .=' <div id="footer">
            <p class="'.(!$o['rma'] && !$o['credit'] ? '' : 'remove').'">
                Terms and Conditions:<br><br>
                Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications
                listed herein. Shipment of goods or execution of services against this PO specifies agreement with our
                terms.<br><br>
                Invoicing: VenTel requires that vendors provide ONE invoice per purchase order. Items on the invoice must
                match items on the purchase order. Due date for payment terms begins when the order is received
                complete. Failure to abide by these terms may result in delayed payment at no fault by the purchaser.
                Please communicate all questions regarding these conditions within 15 days.
            </p>
            <p class="'.($o['rma'] && !$o['credit'] ? '' : 'remove').'">
                Return Instructions:<br><br>
                Print and return this form with the product(s) to be returned. Improperly packaged or incomplete product(s) will void this RMA. Returned product(s) must match this RMA exactly, substitutes are not allowed. RMA is valid for 30 calendar days.
                <br><br>
                Product(s) returned can be replaced, credited or refunded at Ventura Telephone\'s sole discretion. Product(s) remain billable in full if not credited or refunded. No Trouble Found ("NTF") product(s) are subject to a restocking fee. RMA processing may take up to 30 calendar days after receipt.
                <br><br>
                Please ship UPS Ground on Account# 360E2A.
            </p>
            <p class="'.($o['credit'] ? '' : 'remove').' text-center">
                If you have any questions please call us at (805)212-4959
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
