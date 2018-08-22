<?php
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';

	// Order Type
	include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getOrder.php';

	// HTML to PDF
    include_once $_SERVER['ROOT_DIR'].'/dompdf/autoload.inc.php';

 //    // Get tools
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/getCarrier.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/getFreightService.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/getFreight.php';
 //    include_once $_SERVER['ROOT_DIR'].'/inc/getWarranty.php';
 //    include_once $_SERVER['ROOT_DIR'].'/inc/getCondition.php';
 //    include_once $_SERVER['ROOT_DIR'].'/inc/getDisposition.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/getRepairCode.php';

	// // hecidb
	// include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';

	// include_once $_SERVER['ROOT_DIR'].'/inc/locations.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/invoice.php';

	// // Formats
	// include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	// include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	
    function buildHeader($ORDER, $T, $company = true) {
    	global $PROFILE;

    	$htmlRow = '<div class="row">
    					<div class="col-sm-6">
    						<img src="'.$PROFILE['logo'].'" class="logo"></img><br>
    						'.(address_out(getCompanyAddressid($PROFILE['companyid']))?:getCompany($PROFILE['companyid'])).' <br>
	                		'.getCompany($PROFILE['companyid'], 'id', 'phone').'
    					</div>';

    	$htmlRow .= '	<div class="col-sm-6">
    						<h3 class="text-right">'.$T['abbrev'].'# '.$ORDER[$T['order']].'</h3>';
    	if($company) {
    		$htmlRow .= '	<div class="header">
    							<span class="text-center block font-bold">Company</span>
	    					</div>
	    					<div class="text-center">
				    			'.(getContact($ORDER['contactid']) ? getContact($ORDER['contactid']) . '<br>' : "").'
								'.(address_out($ORDER["bill_to_id"]) ? address_out($ORDER["bill_to_id"]) : address_out($ORDER["remit_to_id"])).'
	    					</div>';
    	}
    	$htmlRow .= '	</div>';

    	$htmlRow .= '</div>';

    	return $htmlRow;
    }

    function buildShipBill($ORDER, $T) {
    	$htmlRow = '<div class="row">
    					<div class="col-sm-6">
    						<div class="header">
	    						<span class="text-center block font-bold">Bill To</span>
	    					</div>
	    					<div class="text-center">
				    			'.address_out($ORDER['bill_to_id'], 'street').'
	    					</div>
    					</div>';

    	$htmlRow .= '	<div class="col-sm-6">
							<div class="header">
    							<span class="text-center block font-bold">Ship To</span>
    						</div>
	    					<div class="text-center">
				    			'.address_out($ORDER['ship_to_id'], 'street').'
	    					</div>';
    	$htmlRow .= '	</div>';

    	$htmlRow .= '</div>';

    	return $htmlRow;
    }

    function buildOrderInfo($ORDER, $T) {
    	$htmlRow = '<div class="row">
    					<table class="table-responsive" border="0" cellpadding="0" cellspacing="0">
    						<thead>
    							<tr>
					                <th>Rep</th>
					                <th>PO Date</th>
					                <th></th>
					                <th class="">Terms</th>
					                <th class="">Shipping</th>
					                <th class="">Freight Terms</th>  
					            </tr>
    						</thead>
				            <tbody>
					            <tr>
					                <td class="text-center">
					                    David Langley <br>
					                    (805) 824-0136<br>
					                    david@ven-tel.com
					                </td>
					                <td class="text-center">
					                    April 6, 2018
					                </td>
					                <td class="text-center">
				                        Shannan Mix <br>
				                        (770) 838-0230<br>
				                        shannan@excel-telco.net
			                        </td>
				                    <td class="text-center ">
					                    COD
					                </td>
					                <td class="text-center ">UPS 3 Day Select</td>
					                <td class="">360E2A</td>
					            </tr>
				        	</tbody>
				       	</table>
				    </div>';

    	return $htmlRow;
    }

    function buildCostRows($ORDERS, $T) {
    	$htmlRow = '<div class="row">
    					<table class="table-responsive" border="0" cellpadding="0" cellspacing="0">
    						<thead>
    							<tr>
					                <th>Ln#</th>
					                <th>Description</th>
					                <th class="remove">Due Date</th>
									<th class="remove">Repair Status</th>
					                <th class="">Warranty</th>
					                <th>Cond</th>
					                <th class="">Qty</th>
					                <th>price</th>
					                <th>Ext Amount</th>
					            </tr>
    						</thead>
				            <tbody>
				                '.buildParts($ORDERS).'
							</tbody>
						</table>
					</div>';

    	return $htmlRow;
    }

    function getTerm($termsid){
        if($termid){
            $query = "Select terms FROM terms WHERE id = ".res($termsid).";";
            $result = qedb($query);

            if(mysqli_num_rows($result) > 0){
                $r = mysqli_fetch_assoc($result);
                return $r['terms'];
            }
        } else{
            return "N/A";
        }
    }

    function buildParts($ORDERS) {

    	// print_r($ORDERS);
    	foreach($ORDERS['items'] as $item) {
    		$part_details = current(hecidb($item['partid'],'id'));
    		$part_strs = explode(' ',$part_details['Part']);
    		$part_descr = $part_details['manf'].' '.$part_details['system'].' '.preg_replace('/, REPLACE.*/','',$part_details['description']); 

    		$partHTML .= '<tr>
    						<td class="text-center">'.$item['line_number'].'</td>
		                    <td class="text-left">
		                    	'.$part_strs[0].' &nbsp; '.$part_details['HECI'].' <br>
		                    	<span class="description">
		        	            	'.$part_descr.'
		        	            </span>
		                    </td>
		                    <td class="text-center"></td>
							<td class="text-center"></td>
		                    <td class="text-center ">'.getTerm($ORDERS['termsid']).'</td> 
							<td>'.getCondition($item['conditionid']).'</td>
		                    <td class="text-center ">'.$item['qty'].'</td>
		                    <td class="text-right">'.format_price($item['price']).'</td>
		                    <td class="text-right">'.format_price($item['price'] * $item['qty']).'</td>
    					</tr>';
    	}

    	return $partHTML;
    }

    function renderOrder($order_number, $order_type='Purchase', $taskid, $email = false) {
    	$T = order_type($order_type);
    	$ORDER = getOrder($order_number, $order_type);

		// print_r($ORDER);
		

    	$htmlRow = '<!DOCTYPE html>
					<html>
						<head>
							<title></title>
							<style type="text/css">
								.col-sm-6 {
									width: 50%;
									float: left;
									display:block;
								}

								.logo {
									max-width: 100px
								}

								.text-right {
									text-align: right;
								}

								.text-center {
									text-align: center;
								}

								.block {
									display: block;
								}

								th {
								    text-transform: uppercase;
								    background-color: #eee;
								}

								.table-responsive {
									width: 100%;
								}

								.header {
									width: 100%;
									text-transform: uppercase;
								    background-color: #eee;
								}

								h3 {
								    padding-top: 0px;
   									margin-top: 0px;
   								}

   								body {
   									font-size: 11px;
   									font-family: "Lato", sans-serif;
   								}

   								.row:after {
								    clear: both;
								}

								.row:before, .row:after {
								    content: " ";
								    display: table;
								}

								.font-bold {
									font-weight: bold;
								}

								.row {
								    margin-bottom: 40px;
								}

								.description {
								    font-size: 6pt;
								    color: #aaa;
								}
							</style>
						</head>
						<body>

							'.buildHeader($ORDER, $T, true).'

							'.buildShipBill($ORDER, $T).'

							'.buildOrderInfo($ORDER, $T).'

							'.buildCostRows($ORDER, $T).'

							<div class="row">
								Terms and Conditions:

								Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications listed herein. Shipment of goods or execution of services against this PO specifies agreement with our terms.
							</div>
						</body>
					</html>';

    	return $htmlRow;
    }

