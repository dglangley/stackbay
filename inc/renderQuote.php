<?php
    // include dompdf autoloader
    include_once $_SERVER['ROOT_DIR'].'/dompdf/autoload.inc.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
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
	include_once $_SERVER['ROOT_DIR'].'/inc/getSiteName.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getWarranty.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getCondition.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/form_handle.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getDisposition.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getStatusCode.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getOrderNumber.php';

	function addSubtotal($subtotal,$tax) {
		global $grand_total;

		$html = '
            <!-- Subtotal -->
            <tr>
                <td></td>
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
                <td></td>
                <td style="text-align:right;border:none;">Tax '.$tax.'%</td>
                <td class="text-price">
                    $ '.number_format(($subtotal * ($tax / 100)),2).'
                </td>
            </tr>
            <tr class="total">
    			<td></td>
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

	    $grand_total += $subtotal + ($subtotal * ($tax / 100));

		return ($html);
	}

    // Get the details of the current item_id (repair_item_id, service_item_id etc)
    function getItemDetails($item_id, $T) {
        $data = array();

        $query = "SELECT * FROM ".$T['items']." i, ".$T['orders']." o ";
		$query .= "WHERE i.id = ".res($item_id)." AND o.".str_replace('metaid','id',$T['order'])." = i.".$T['order'].";";
        $result = qdb($query) OR die(qe().' '.$query);

        if(mysqli_num_rows($result)) {
            $data = mysqli_fetch_assoc($result);
			if ($data['quote_qty']) { $data['qty'] = $data['quote_qty']; }
			else if ($data['request_qty'] AND $data['quote_price']>0) { $data['qty'] = $data['request_qty']; }

			$data['outsourced_services'] = 0;
			if ($T['orders']=='service_quotes') {
				$query2 = "SELECT SUM(quote) quote FROM service_quote_outsourced WHERE quote_item_id = '".res($item_id)."'; ";
				$result2 = qedb($query2);
				if (mysqli_num_rows($result2)>0) {
					$r2 = mysqli_fetch_assoc($result2);
					if ($r2['quote']>0) { $data['outsourced_services'] = $r2['quote']; }
				}
			}
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
			$query = "SELECT qty, (charge/qty) price, partid FROM service_bom WHERE item_id = '".$item_id."' AND item_id_label = '".$T['item_label']."'; ";
            $result = qedb($query);

            // echo $query;

            while($row = mysqli_fetch_assoc($result)) {
                $purchase_requests[] = $row;
                $materials_total += $row['quote'];
            }
        }

        return $purchase_requests;
    }

	$grand_total = 0;
	function renderQuote($item_id, $order_type = 'service_quote', $email = false, $tax = 0, $order_number = 0) {
		global $grand_total;

		$T = order_type($order_type);

        $item_ids = array();

        if($order_number) {// AND $T['orders'] == 'service_quotes') {
            $query = "SELECT id FROM ".$T['items']." WHERE ".$T['order']." = ".fres($order_number).";";
            $result = qedb($query);

            while($r = mysqli_fetch_assoc($result)) {
                $item_ids[] = $r['id'];

                if(empty($item_id)) {
                    $item_id = $r['id'];
                }
            }
        } else {
            // Put in at least 1 record to be ran in the loop
            $item_ids[] = $item_id;

            $order_number = getOrderNumber($item_id, $T['items'], $T['order']);
		}

		$item_materials = array();
//		$item_details = getItemDetails($item_id, $T);
//		$item_materials = getMaterials($item_id, $T);

		$quotetitle = trim(preg_replace('/([\/]docs[\/])([^.]+)([.]pdf)/i','$2',$_SERVER["REQUEST_URI"]));
		if (! $quotetitle) { $quotetitle = $T['abbrev']; }

		$multi = false;
		$site = false;

		//pre-check items on quote for multiple sites, in which case we want to present address info a little differently, per scott
		foreach($item_ids as $item) {
			$deets = getItemDetails($item, $T);

			// on first item discovered, set $site
			if(! $site) {
				$site = $deets['item_id'];
				continue;
			} 

			// if a successive item has a diff item id, it must be multi-site
			if($site != $deets['item_id']) {
				$multi = true;
				break;
			}
		}

		// default header info
		$item_details = $deets;

		$html_page_str = '
<!DOCTYPE html>
<html>
    <head>
		<title>'.$quotetitle.'</title>
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
			<table class="table-full">
				<tr>
					<td class="text-right">
						'.($item_details['companyid']==1893 ? 'Federal' : '').'
						'.ucwords(str_replace('Demand','Quote',str_replace('_',' ',$order_type))).' '.$order_number.'<br/>
						'.format_date($item_details['datetime'],'n/j/y').'
					</td>
				</tr>
			</table>
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
        <div id = "letter_head">
            <b>
                <img src="https://www.stackbay.com/img/logo.png" style="width:1in;"></img><br>
                Ventura Telephone, LLC <br>
                3037 Golf Course Drive <br>
                Unit 2 <br>
                Ventura, CA 93003<br>
                (805) 212-4959 p<br/>
                (805) 212-4771 f
            </b>
        </div>
        <!-- Shipping info -->
        <table class="table-full">
            <tbody>
            <tr>
                <th>Shipping / Site Address</th>
                <th>Rep</th>
            </tr>
            <tr>
                <td class="half">
	';
				if($multi) {
					$html_page_str .= 'Multiple Sites (see items below)';
				} else {
					$html_page_str .= format_address($item_details['item_id'],'<BR/>',true,'',$item_details['companyid']);
				}
	$html_page_str .= '
				</td>
                <td class="half">
                    '.getContact($item_details['userid'], 'userid').'<br/>
					'.getContact($item_details['userid'], 'userid', 'email').'<br/>
					'.getContact($item_details['userid'], 'userid', 'phone').'
                </td>
            </tr>
            </tbody>
        </table>
	';

	$header_declared = false;
	$num_items = count($item_ids);
	$subtotal = 0;
	$n = 0;

	foreach($item_ids as $item) {
		$n++;
		$item_details = getItemDetails($item, $T);
		$item_materials = getMaterials($item, $T);

		if ($item_details['description']) {
			$html_page_str .= '

            <!-- Scope info -->
            <table class="table-full">
                <tbody>
                	<tr>
	                    <th class="text-left">Scope</th>
					</tr>
			';
			if($multi) {
				$sitename = format_address($item_details['item_id'],'<BR/>',true,'',$item_details['companyid'],'',false);

				if($sitename) {
					$html_page_str .= '<tr>
										<td class="text-left"><strong>Site Name:</strong> '.$sitename.'</td>
									</tr>
					';
				}
			}
			$html_page_str .= '
	                <tr>
	                    <td class="text-left">
    						'.str_replace("\n","<br />",$item_details['description']).'
						</td>
					</tr>
				</tbody>
			</table>
			';
		}
		// End of the addresses table

		$materials_total = 0;
		$row_total = 0;

		// Shipping information table
		if ($T['orders']=='service_orders' OR $T['orders']=='service_quotes' OR ! $header_declared) {

			$html_page_str .= '
	    <!-- Items Table -->
        <table class="table-full table-condensed">
            <tr>
                <th class="text-left" style="width: 30px;">Ln#</th>
                <th class="text-left">Description</th>
                <th class="">Units</th>
                <th class="text-right">'.str_replace('quote_','',str_replace('offer_','',$T['amount'])).'</th>
                <th class="text-right">Ext '.str_replace('quote_','',str_replace('offer_','',$T['amount'])).'</th>
            </tr>
			';
			$header_declared = true;
			$subtotal = 0;
		}

		$descr = '';
		if ($T['orders']=='service_orders' OR $item_details['labor_hours'] OR $item_details['outsourced_services']) {
			$descr = 'Labor';
		} else if ($item_details['partid']) {
			$descr = getPart($item_details['partid']);
		}

		$item_amount = 0;
		if ($item_details['labor_hours'] OR $item_details['outsourced_services']) {
			$item_details['qty'] = 1;
			$item_amount = ($item_details['labor_hours'] * $item_details['labor_rate']) + $item_details['outsourced_services'];
			$row_total = $item_amount;
		} else {
			if (! $item_details['qty']) {
				if ($item_details[$T['amount']]>0) { $item_details['qty'] = 1; }
				else { continue; }
			}
			$item_amount = $item_details[$T['amount']] + $item_details['outsourced_services'];
			$row_total = $item_details['qty']*$item_amount;
		}

		$html_page_str .= '
			<tr>
				<td>'.$item_details['line_number'].'</td>
				<td class="text-left">'.$descr.'</td>
				<td>'.$item_details['qty'].'</td>
				<td class="text-right">$ '.number_format($item_amount,2).'</td>
				<td class="text-right">$ '.number_format($row_total,2).'</td>
			</tr>
		';

if ($item_details['companyid']==1893) {
                $html_page_str .= '
                        <tr>
                                <td> </td>
                                <td class="text-left"> 5-day lead-time ARO </td>
                                <td> </td>
                                <td class="text-right"> </td>
                                <td class="text-right"> </td>
                        </tr>
                ';
}

		$material_rows = '';
		if ($T['orders'] == 'service_orders' OR count($item_materials)) {
			foreach($item_materials as $material) {
				$materials_total += (($material['amount'] + ($material['amount'] * ($material['profit_pct'] / 100))) * $material['qty']);

				$material_rows .= '
						<tr style="line-height:10px">
							<td class="text-left" style="padding:2; margin:0; color:#555"><small>
								'.$material['qty'].'</small>
							</td>
							<td class="text-left" style="padding:2; margin:0; color:#555"><small>
								<span class="descr-label">'.getPart($material['partid']).'</span>
								<div class="description desc_second_line descr-label" style = "color:#aaa;">
									'.getPart($material['partid'], 'full_descr').'</small>
								</div>
							</td>
							<td style="padding:2; margin:0; color:#555" class="text-right"><small>
								$ '.number_format((($material['amount'] + ($material['amount'] * ($material['profit_pct'] / 100)))), 2).'</small>
<!-- '.format_price($material['quote'] / $material['qty']).' -->
							</td>
							<td style="padding:2; margin:0; color:#555" class="text-right"><small>
								$ '.number_format((($material['amount'] + ($material['amount'] * ($material['profit_pct'] / 100))) * $material['qty']), 2).'</small>
<!-- '.format_price($material['quote']).' -->
							</td>
						</tr>
				';
			}

			$html_page_str .= '
    		<tr>
				<td> </td>
				<td class="text-left">Materials</td>
				<td class="" colspan=2>
				</td>
				<td class="text-right">
					$ '.number_format($materials_total, 2).'
				</td>
			</tr>
			<tr>
				<td> </td>
				<td colspan="3">
					<table class="table table-full table-striped table-condensed" style="background-color:#fcfcfc">
						<tbody>
							<tr>
								<th class="text-left" style="color:#555"><small>Qty</small></th>
								<th class="text-left" style="color:#555"><small>Description</small></th>
								<th class="text-right" style="color:#555"><small>Price</small></th>
								<th class="text-right" style="color:#555"><small>Ext Price</small></th>
							</tr>
							'.$material_rows.'
						</tbody>
					</table>
				</td>
				<td> </td>
			</tr>
			';
		}

		$subtotal += $materials_total + $row_total;

//		if ($T['orders']<>'service_orders' AND $T['orders']<>'service_quotes' AND $n<>$num_items) { continue; }

		if ($T['orders']=='service_orders' OR $T['orders']=='service_quotes') {
			$html_page_str .= addSubtotal($subtotal,$tax);
		}
	}

if ($T['orders']<>'service_orders' AND $T['orders']<>'service_quotes') { $html_page_str .= addSubtotal($subtotal,$tax); }

if($order_number AND $T['orders']=='service_orders' OR $T['orders']=='service_quotes') {
    $html_page_str .= '
        <div style="text-align:right;"><b>Total</b> '.format_price(number_format($grand_total,2)).'</div>

        <BR>
    ';
}

	if (! $email AND $T['orders']=='service_orders') {
		$html_page_str .= '
                Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications
                listed herein. Shipment of goods or execution of services against this PO specifies agreement with our
                terms.<!-- <br><br>
                Invoicing: VenTel requires that vendors provide ONE invoice per purchase order. Items on the invoice must
                match items on the purchase order. Due date for payment terms begins when the order is received
                complete. Failure to abide by these terms may result in delayed payment at no fault by the purchaser.
                Please communicate all questions regarding these conditions within 15 days.  -->
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
