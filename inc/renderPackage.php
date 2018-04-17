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
    include_once $_SERVER['ROOT_DIR'].'/inc/getWarranty.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getCondition.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/form_handle.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getDisposition.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getRepairCode.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getOrderNumber.php';

    include_once $_SERVER['ROOT_DIR'].'/inc/getOrder.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getPackageContents.php';	

    function getLINE($order_number,$partid){
        //Grabs the line number
        $query = "SELECT line_number FROM sales_items WHERE so_number = ".res($order_number)." AND partid = ".res($partid).";";
        $result = qedb($query);
        
        $line_number = '';
        if (mysqli_num_rows($result) > 0){
            $r = mysqli_fetch_assoc($result);
            $line_number = $r['line_number'];
        }

        return $line_number;
    }

	function renderPackage($packageids, $order_type = 'Sale', $order_number = 0) {

		$html_page_str = '
<!DOCTYPE html>
<html>
    <head>
		<title>Packing Slip</title>
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

            #footer{
                text-align:center;
            }

			.page-break { display: block; page-break-before: always; }
        </style>
    </head>
    <body>';

$initial = true;
foreach($packageids as $packageid) {
	if($initial) {
		$initial = false;
	} else {
		$html_page_str .= '<div class="page-break"></div>';
	}

	// get the current package number and contents
    $package = reset(getISOPackage($packageid));
    $packageContents = getISOPackageContents($packageid);

    $T = order_type($package['order_type']);

    $ORDER = getOrder($package['order_number'], $package['order_type']);

    $repair_order = 0;

    if(reset($ORDER['items'])['ref_1_label'] == 'repair_item_id') {
		$repair_item = reset($ORDER['items'])['ref_1'];
	}

	if($repair_item) {
		$query = "SELECT ro_number FROM repair_items WHERE id = ".res($repair_item).";";
		$result = qedb($query);

		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$repair_order = $r['ro_number'];
		}
	}

	$html_page_str .='
	        <div id = "ps_bold">
				<table class="table-full">
					<tr>
						<td class="text-right">
							Packing Slip
						</td>
					</tr>
				</table>
	            <table class="table-full" id = "vendor_add">
					<tr>
						<th class="text-center">Company</th>
					</tr>
					<tr>
						<td class="half">
	                        '.(getContact($ORDER['contactid']) ? getContact($ORDER['contactid']) . '<br>' : "").'
							'.(address_out($ORDER["bill_to_id"]) ? address_out($ORDER["bill_to_id"]) : address_out($ORDER["remit_to_id"])).'
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
	                (805) 212-4959 p<br/>
	                (805) 212-4771 f
	            </b>
	        </div>
	';

	//Begin addresses table
		$html_page_str .='
		        <!-- Shipping info -->
		        <table class="table-full">
		            <tr>';
		$html_page_str .='
		                <th class="">Bill To</th>
		                <th class="">Ship To</th>';
		$html_page_str .='                
		            </tr>

		            <tr>
		                <td class="half">';
	    
	    $html_page_str .= address_out($ORDER['bill_to_id'], 'street');

		$html_page_str.='</td>';

		$html_page_str .= '
		                <td class="half">
		                    '.(address_out($ORDER['ship_to_id'])).'
		                </td>';
		$html_page_str .= '
		            </tr>
		        </table>
		';
		//End of the addresses table

		$rep_name = getContact($ORDER['sales_rep_id'],'userid','name');
		$rep_phone = getContact($ORDER['sales_rep_id'],'userid','phone');
		$rep_email = getContact($ORDER['sales_rep_id'],'userid','email');

		$html_page_str .='
	        <!-- Freight Carrier -->
	        <table class="table-full" id="order-info">
	            <tr>
	                <th>Sales Rep</th>
	                <th>Shipment Date</th>
	                <th>'.($repair_order?'RO':$T['abbrev']).'#</th>
	                <th>Shipping</th>
	                <th>Tracking#</th>
	            </tr>
	            <tr>
	                <td>
	                    '.$rep_name.' <br>
	                    '.(($rep_phone)?$rep_phone."<br>" : "").'
	                    '.$rep_email.'
	                </td>
	                <td class="text-center">
	                    '.format_date($package['datetime'],'F j, Y').'
	                </td>
	                <td>
	                	'.($repair_order?:$package['order_number']).'
	                </td>
	                <td class="text-center '.($order_type=='RMA' ? 'remove' : '').'">
	                    '.getCarrier($ORDER['freight_carrier_id']).' '.getFreightService($ORDER['freight_services_id']).'
	                </td>
	                <td>
	                	'.$package['tracking_no'].'
	                </td>
	            </tr>
	        </table>';
	        //End of the shipping information table

	    $html_page_str .= '
	    	<table class="table-full table-striped table-condensed">
	            <tr>
	                <th>Ln#</th>
	                <th>Part</th>
	                <th>HECI</th>
	                <th>Qty</th>
	               	<th>Serial</th>
	            </tr>';

	    foreach ($packageContents as $part => $item) {
	         $html_page_str .="
	                    <tr>
	                        <td>".getLINE($package['order_number'],$item['partid'])."</td>
	                        <td>".explode(' ',$part)[0]."</td>
	                        <td>".$item['heci']."</td>
	                        <td>".$item['qty']."</td>
	                        <td>".$item['serial']."</td>
	                    </tr>";
	    }

		$html_page_str .= '
			</table>
			<table class="table-full">
	            <!-- Subtotal -->
	            <tr>
					<td rowspan="3" class="half">'.$ORDER['public_notes'].'</td>
	                <td></td>
	            </tr>
	        </table>
	    ';


		$html_page_str .='
					<div id="footer">If you have any questions, please call us at (805)212-4959</div>';

	}

		$html_page_str .='
	            </body>
	        </html>';


	return ($html_page_str);
}
