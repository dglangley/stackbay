<?php
    // include dompdf autoloader
    include_once $_SERVER['ROOT_DIR'].'/dompdf/autoload.inc.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	//include_once $_SERVER['ROOT_DIR'].'/inc/dictionary.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/locations.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_address.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCarrier.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getFreightService.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getFreight.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getWarranty.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getCondition.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/form_handle.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/invoice.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getDisposition.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getRepairCode.php';
    // include_once $_SERVER['ROOT_DIR'].'/inc/display_part.php';

    // Get the details of the current item_id (repair_item_id, service_item_id etc)
    function getItemDetails($item_id, $T, $field1) {
        $data = array();

        $query = "SELECT * FROM ".$T['items']." t, ".$T['orders']." sq WHERE t.$field1= ".res($item_id)." AND sq.".$T['order']." = t.".$T['order'].";";
        $result = qdb($query) OR die(qe().' '.$query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);

			$r['outsourced_services'] = 0;
			if ($T['orders']=='service_quotes') {
				$query2 = "SELECT SUM(quote) quote FROM service_quote_outsourced WHERE quote_item_id = '".res($item_id)."'; ";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					if ($r2['quote']>0) { $r['outsourced_services'] = $r2['quote']; }
				}
			}

            $data = $r;
        }

        return $data;
    }

    function getMaterials($item_id, $T) {
        global $materials_total;

        $purchase_requests = array();
        
        if($T['orders'] == 'service_quotes') {
            $query = "SELECT * FROM service_quote_materials WHERE quote_item_id = ".res($item_id).";";
            $result = qedb($query);

            //echo $query;

            while($row = mysqli_fetch_assoc($result)) {
                $purchase_requests[] = $row;
                $materials_total += $row['quote'];
            }
        } else if($T['orders'] == 'service_orders') {
            $query = "SELECT i.*, pi.price, pi.line_number FROM purchase_items pi, service_materials sm, inventory i ";
            $query .= "WHERE ((ref_1 = ".res($item_id)." AND ref_1_label = '".$T['item_label']."') OR (ref_2 = ".res($item_id). " AND ref_2_label = '".$T['item_label']."')) ";
            $query .= "AND pi.partid = i.partid ";
            $query .= "AND sm.".$T['item_label']." = ".res($item_id)." ";
            $query .= "AND sm.inventoryid = i.id ";
            $query .= ";";
            // $query = "SELECT * FROM service_materials WHERE ".$T['item_label']." = ".res($item_id).";";
            $result = qedb($query);

            // echo $query;

            while($row = mysqli_fetch_assoc($result)) {
                $purchase_requests[] = $row;
                $materials_total += $row['quote'];
            }
        }

        return $purchase_requests;
    }

	function renderQuote($item_id, $order_type = 'service_quote', $email = false, $tax = 0) {
		$T = order_type($order_type);
//print_r($T);
        $item_details = getItemDetails($item_id, $T, 'id');
        $item_materials = getMaterials($item_id, $T);

        // print_r($item_details);
        // echo "<BR>";
        // print_r($item_materials);
        
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
            .text-left {
                text-align:left;
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
            .total td, tr.total {
                background-color:#eee !important;
            }
            #spacer {
                width:100%;
                height:100px;
            }
        </style>
    </head>
    <body>';

	$header = '';
	
$html_page_str .='
        <div id = "ps_bold">
            <h3>'.$header.'</h3>
            <table class="table-full" id = "vendor_add">
				<tr>
					<th class="text-center">Company</th>
				</tr>
				<tr>
					<td class="half">
                        '.getContact($item_details['contactid']).'
                        <BR>
                        '.getCompany($item_details['companyid']).'
					</td>
				</tr>
			</table>
        </div>
        ';

$html_page_str .='
        <div id = "letter_head">
            <b>
                <img src="https://www.stackbay.com/img/logo.png" style="width:1in;"></img><br>
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
        <table class="table-full">
            <tbody>
            <tr>';
$html_page_str .='
                <th>Site Information</th>
                <th>Rep</th>';
$html_page_str .='                
            </tr>
            <tr>
                <td class="half">';

$html_page_str .= format_address($item_details['item_id'],'<BR/>',true,'',$item_details['companyid']);
$html_page_str.='</td>';
$html_page_str .= '
                <td class="half">
                    '.getContact($item_details['userid'], 'userid').'
                </td>';
$html_page_str .= '
            </tr>
            </tbody>
        </table>
';

$html_page_str .='
        <!-- Shipping info -->
        <table class="table-full">
            <tbody>
            <tr>';
$html_page_str .='
                <th class="text-left">Scope</th>';
$html_page_str .='                
            </tr>
            <tr>
                <td class="text-left">';

$html_page_str .= str_replace("\n","<br />",$item_details['description']);
$html_page_str.='</td>';
$html_page_str .= '
            </tr>
            </tbody>
        </table>
';
// End of the addresses table

$materials_total = 0;
$labor_total = 0;

// Shipping information table
	$html_page_str .= '
<!-- Items Table -->
    <table class="table-full table-condensed">
        <tr>
            <th class="text-left" style="width: 30px;">Ln#</th>
            <th class="text-left">Description</th>
            <th></th>
            <th class="text-right">'.$T['amount'].'</th>
        </tr>';

         $html_page_str .= '<tr>';

        $html_page_str .=   '<td>
                            </td>';

        $html_page_str .=   '<td class="text-left">
                                Labor Total
                            </td>';

        $html_page_str .= '<td></td>';

        if($T['orders'] == 'service_orders') {
            $labor_total = $item_details['amount'];

        } else {
            $labor_total = ($item_details['labor_hours'] * $item_details['labor_rate']) + $item_details['outsourced_services'];

        }
        $html_page_str .=   '<td class="text-right">
                                $ '.number_format($labor_total,2).'
                            </td>
						</tr>';

        if($T['orders'] == 'service_orders') {
            foreach($item_materials as $material) {
                 $html_page_str .= '<tr>';

                $html_page_str .=   '<td class="text-left">
                                        '.$material['line_number'].'
                                    </td>';

                $html_page_str .= '<td class="text-left">
                                        <span class="descr-label">'.getPart($material['partid']).'</span>
                                        <div class="description desc_second_line descr-label" style = "color:#aaa;">'
                                            .getPart($material['partid'], 'full_descr').
                                        '</td>';

                $html_page_str .=   '<td></td>';

                $html_page_str .=   '<td class="text-right">
                                        $ '.number_format(($material['price'] * $material['qty']), 2).'
                                    </td>';

                $html_page_str .= '</tr>';

                $materials_total += $material['price'] * $material['qty'];
            }

            $html_page_str .= '<tr>';

            $html_page_str .=   '<td colspan="2">
                                </td>';

            $html_page_str .= '<td class="text-right">Materials Subtotal</td>';

            $html_page_str .=   '<td class="text-right">
                                    $ '.number_format($materials_total, 2).'
                                </td>';

            $html_page_str .= '</tr>';
        } else {
            if(! empty($item_materials)) {
                foreach($item_materials as $material) {
                    $materials_total += (($material['amount'] + ($material['amount'] * ($material['profit_pct'] / 100))) * $material['qty']);

                }
            }

            $html_page_str .= '<tr>';

            $html_page_str .=   '<td></td>';

            $html_page_str .=   '<td class="text-left">
                                    Materials Total
                                </td>';

            $html_page_str .= '<td></td>';

            $html_page_str .=   '<td class="text-right">
                                    $ '.number_format($materials_total, 2).'
                                </td>';

            $html_page_str .= '</tr>';

            
            if(! empty($item_materials)) {
                // This is the section to list out all the materials used
                $html_page_str .= '<tr>';
                // First column is LN#
                $html_page_str .= '     <td></td>';

                $html_page_str .= '     <td colspan="2">
                                            <table class="table-full table-striped table-condensed">
                                                <tbody>
                                                    <tr>
                                                        <th class="text-left">Qty</th>
                                                        <th class="text-left">Part</th>
                                                        <th class="text-left">Description</th>
                                                        <th class="text-right">Price</th>
                                                        <th class="text-right">Ext. Price</th>
                                                    </tr>
                                ';
                foreach($item_materials as $material) {
                    $html_page_str .= "<tr>";
                    $html_page_str .= "     <td class='text-left'>".$material['qty']."</td>";
                    $html_page_str .= "     <td class='text-left'>".getPart($material['partid'])."</td>";
                    $html_page_str .= "     <td class='text-left'>".getPart($material['partid'], 'full_descr')."</td>";
                    $html_page_str .= "     <td class='text-right'>".format_price($material['quote'] / $material['qty'])."</td>";
                    $html_page_str .= "     <td class='text-right'>".format_price($material['quote'])."</td>";
                    $html_page_str .= "</tr>";

                }
                $html_page_str .= '             </tbody>
                                            </table>
                                        </td>';

                $html_page_str .= '     <td></td>';

                $html_page_str .= '</tr>';
            }
        }

        $subtotal = $materials_total + $labor_total;

	$html_page_str .= '
        <!-- Subtotal -->
        <tr>
            <td></td>
            <td></td>
            <td class="text-right">Subtotal</td>
            <td class="text-right">
                $ '.number_format($subtotal,2).'
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align:right;border:none;">Tax '.$tax.'%</td>
            <td class="text-price">
                $ '.number_format(($subtotal * ($tax / 100)),2).'
            </td>
        </tr>
        <tr class="total">
			<td></td>
            <td></td>
            <td style="text-align:right;"><b>Total</b></td>
            <td id="total" class="text-price">
                <b>$ '.number_format($subtotal + ($subtotal * ($tax / 100)),2).'</b>
            </td>
        </tr>
    </table>
    <BR>
';
if(!$email) {
			$html_page_str .= '
                Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications
                listed herein. Shipment of goods or execution of services against this PO specifies agreement with our
                terms.<!-- <br><br>
                Invoicing: VenTel requires that vendors provide ONE invoice per purchase order. Items on the invoice must
                match items on the purchase order. Due date for payment terms begins when the order is received
                complete. Failure to abide by these terms may result in delayed payment at no fault by the purchaser.
                Please communicate all questions regarding these conditions within 15 days.
-->
            </p>
        </div>
    </body>
</html>
		';
	}

	return ($html_page_str);

    // echo $html_page_str;
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
