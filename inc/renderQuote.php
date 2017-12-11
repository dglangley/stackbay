<?php
    // include dompdf autoloader
    include_once $_SERVER['ROOT_DIR'].'/dompdf/autoload.inc.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	//include_once $_SERVER['ROOT_DIR'].'/inc/dictionary.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/locations.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
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

    // Get the details of the current item_id (repair_item_id, service_item_id etc)
    function getItemDetails($item_id, $table, $field) {
        $data = array();

        $query = "SELECT * FROM $table t, service_quotes sq WHERE t.$field = ".res($item_id)." AND sq.id = t.quoteid;";
        $result = qdb($query) OR die(qe().' '.$query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);

            $data = $r;
        }

        return $data;
    }

    function getMaterials($item_id, $type = 'service_quote', $field = 'quote_item_id') {
        global $materials_total;

        $purchase_requests = array();
        
        if($type == 'service_quote') {
            $query = "SELECT * FROM service_quote_materials WHERE $field = ".res($item_id).";";
            $result = qdb($query) OR die(qe().' '.$query);

            //echo $query;

            while($row = mysqli_fetch_assoc($result)) {
                $purchase_requests[] = $row;
                $materials_total += $row['quote'];
            }
        } 

        return $purchase_requests;
    }

	function renderQuote($item_id, $order_type = 'service_quote', $email = false, $tax = 0) {
		$T = order_type($order_type);

        $item_details = getItemDetails($item_id, $T['items'], 'id');
        $item_materials = getMaterials($item_id);

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

$html_page_str .= address_out($item_details['item_id']);
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
                <th>Scope</th>';
$html_page_str .='                
            </tr>
            <tr>
                <td>';

$html_page_str .= $item_details['description'];
$html_page_str.='</td>';
$html_page_str .= '
            </tr>
            </tbody>
        </table>
';
// End of the addresses table

$materials_total = 0;

// Shipping information table
	$html_page_str .= '
<!-- Items Table -->
    <table class="table-full table-striped table-condensed">
        <tr>
            <th>Ln#</th>
            <th>Description</th>
            <th></th>
            <th>'.$T['amount'].'</th>
        </tr>';

         $html_page_str .= '<tr>';

        $html_page_str .=   '<td>
                            </td>';

        $html_page_str .=   '<td>
                                Labor
                            </td>';

        $html_page_str .= '<td></td>';

        $html_page_str .=   '<td>
                                '.format_price($item_details['labor_hours'] * $item_details['labor_rate']).'
                            </td>';

        $html_page_str .= '</tr>';

        foreach($item_materials as $material) {
            $materials_total += (($material['amount'] + ($material['amount'] * ($material['profit_pct'] / 100))) * $material['qty']);
        }

        $html_page_str .= '<tr>';

        $html_page_str .=   '<td>
                            </td>';

        $html_page_str .=   '<td>
                                Materials
                            </td>';

        $html_page_str .= '<td></td>';

        $html_page_str .=   '<td>
                                '.format_price($materials_total).'
                            </td>';

        $html_page_str .= '</tr>';

        $subtotal = $materials_total + ($item_details['labor_hours'] * $item_details['labor_rate']);

	$html_page_str .= '</table>
    <table class="table-full '.($order_type=='RMA' ? 'remove' : '').'">
        <!-- Subtotal -->
        <tr>
            <td class="text-right">Subtotal</td>
            <td class="text-right">
                '.format_price($subtotal,true,' ').'
            </td>
        </tr>
        <tr>
            <td style="text-align:right;border:none;">Tax '.$tax.'%</td>
            <td class="text-price">
                '.format_price(($subtotal * ($tax / 100))).'
            </td>
        </tr>
        <tr class="total">
			<td> </td>
            <td style="text-align:right;"><b>Total</b></td>
            <td id = "total" class="text-price">
                <b>'.format_price($subtotal + ($subtotal * ($tax / 100))).'</b>
            </td>
        </tr>
    </table>
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
