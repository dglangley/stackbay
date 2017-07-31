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
	include_once $rootdir.'/inc/getDisposition.php';
	
    function getPackageTracking($invoice_number) {
        $tracking = array();
        $html = '';

        $packages = "SELECT * from invoice_items ii, invoice_shipments s, packages p
                WHERE ii.invoice_no = ".prep($invoice_number)."
                and ii.id = s.invoice_item_id
                AND s.packageid = p.id
                GROUP BY s.packageid;";
        $result = qdb($packages) OR die(qe().'<BR>'.$packages);
        while ($r = mysqli_fetch_assoc($result)) {
            $tracking[] = $r;
        }

        foreach($tracking as $i => $item) {
            $html .= "<tr>";
                $html .= "<td>";
                    $html .= $item['package_no'];
                $html .= "</td>";
                $html .= "<td>";
                $html .= (!$item['tracking_no'] && $i == 0)? 'None Provided' : $item['tracking_no'];
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
    function printLumpedInvoices($lumpid){
            $plumpid = prep($lumpid);
            $select = "
            SELECT il.date lumpdate, ili.invoice_no, ii.id invoice_item_id, il.id id, companyid, i.order_type, i.order_number, ii.partid, p.tracking_no, ii.ref_1, ii.ref_1_label, ii.ref_2, ii.ref_2_label, ii.qty, ii.amount, iss.*
              FROM `invoice_lumps` il,`invoice_lump_items` ili, `invoices` i, `invoice_items` ii, invoice_shipments iss, packages p
              WHERE il.id = ili.lumpid 
              AND i.invoice_no = ili.invoice_no 
              AND i.invoice_no = ii.invoice_no
              AND p.order_number = i.order_number
              AND i.order_type = p.order_type
              AND iss.packageid = p.id
              AND iss.invoice_item_id = ii.id
              AND il.id = $plumpid
              ORDER by ii.id ;";
            $results = qdb($select) or die(qe()." | $select");
            // echo("<pre>");
            // echo($select);
            $return = array();
            $return['subtotal'] = 0.00;
            $main_table_structure = array(
                "line_no" => array(
                    "title" => "Line #",
                    "size" => "",
                    "it_style" => "",
                    ),
                "item" => array(
                    "title"=> "Item",
                    "size" => "",
                    "it_style" => "",
                    ),
                "description" => array(
                    "title"=> "Description",
                    "size" => "",
                    "it_style" => "text-align:left;",
                    ),
                "qty" => array(
                    "title"=> "Qty",
                    "size" => "",
                    "it_style" => "",
                    ),
                "price" => array(
                    "title"=> "Price",
                    "size" => "",
                    "it_style" => "text-align:right;",
                    ),
                "ext" => array(
                    "title"=> "Ext Price",
                    "size" => "",
                    "it_style" => "text-align:right;",
                    ),
                );
            $return['mts'] = $main_table_structure;
            
            $already_entered = array();
            $line_no = 0;
            foreach($results as $i => $r){
                //Var Dec
                $repair_code = '';
                $order_number = '';
                $part = array();
                $row = array();
                // print_r($r);

                //Get the order level information
                $o = o_params($r['order_type']);
                $order_number = $r['order_number'];
                $macro_select = "
                SELECT *, '".$r['lumpdate']."' as lumpdate FROM
                ".$o['order']." o ,".$o['item']." i, inventory inv
                WHERE o.".$o['id']." = ".prep($order_number)." 
                AND o.".$o['id']." = i.".$o['id']." 
                AND inv.".$o['inv_item_id']." = i.id;";
                $macro = qdb($macro_select) or die(qe()." | $macro_select");
                $return['order_info']= mysqli_fetch_assoc($macro);
                // $part = current(hecidb($r['partid'],'id'));
                if(!$already_entered[$r['order_type'].$r['order_number']]){
                    $already_entered[$r['order_type'].$r['order_number']] = true;
                    // echo($macro_select);
                    foreach ($macro as $it => $mac) {
                        //Normally we will only have one record per macro, but eventually RO/SOs will be lumped for multiple items, so foreach should work
                        $item_status = '';
                        if($o['repair']){
                            $item_status = getRepairCode($mac['repair_code_id']);
                        } else {
                            $item_status = "Shipped";
                        }
                        $row['line_no'] = ++$line_no;
                        $row['item'] = ucwords($o['type'])." Order # ".$order_number;
                        $row['description'] = "
                        P/N: ".getPart($r['partid'])."<br>
                        S/N: ".$mac['serial_no']."<br>
                        Tracking #: ".$r['tracking_no']."<br>
                        Item Status: $item_status<br>
                        ".($r['ref_1_label']? $r['ref_1_label'].": ".$r['ref_1'] : "")."<br>
                        ".($r['ref_2_label']? $r['ref_2_label'].": ".$r['ref_2'] : "");
                        $row['qty'] = $r['qty'];
                        $row['price'] = format_price($r['amount']);
                        $row['ext'] = format_price($r['qty'] * $r['amount']);
                        $return['main_table_rows'][] = $row;
                        $return['subtotal'] += $row['ext'];
                        // print_r($row);
                    }
                    $return['subtotal'] = format_price($return['subtotal']);
                }
            }
            // echo("</pre>");
            return $return;
        }

	function renderOrder($order_number,$order_type='Purchase', $email = false) {
	    $o = array();
	    $oi = array();
	    //Switch statement to add in more features for now until we have a solid naming convention

        $o = o_params($order_type);
	    $prep = prep($order_number);
        
		$due_date = "";
		$subtotal = 0;
		$freight = 0;
		$total = 0;
        $serials = array();
        if ($o['invoice']){
            $serials = getInvoicedInventory($order_number, "`serial_no`,`invoice_item_id`");
        } 
        
        
		$orig_order = $order_number;
		if(!$o['lump']){
    		$order = "SELECT * FROM `".$o['order']."` WHERE `".$o['id']."` = $order_number;";
    		$order_result = qdb($order) or die(qe()." | $order");
    		if (mysqli_num_rows($order_result) == 0) {
    			die("Could not pull record");
    		}
    		$oi = mysqli_fetch_assoc($order_result);
		} else { 
		    $lumps = printLumpedInvoices($order_number);
		    $oi = $lumps['order_info'];

		}
// 		echo $order;exit;


		// is order a sale or repair?
		if ($o["invoice"] OR $o["credit"]) {
			$orig_order = $oi['order_number'];
			if ($oi["order_type"]=='Sale') {
				$query2 = "SELECT * FROM sales_orders, terms WHERE so_number = '".$oi["order_number"]."' AND termsid = terms.id; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			} else if ($oi["order_type"]=='Repair') {
				$query2 = "SELECT ro.*, t.* FROM repair_orders ro, terms t, repair_items ri ";
				$query2 .= "WHERE ro.ro_number = '".$oi["order_number"]."' AND t.id = ro.termsid AND ro.ro_number = ri.ro_number; ";
				$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
			}
			if (mysqli_num_rows($result2)==0) {
				die("Could not pull originating record for this invoice");
			}
			$r2 = mysqli_fetch_assoc($result2);
			foreach ($r2 as $k => $v) {
				$oi[$k] = $v;
			}
		}

		if($o['invoice']){ 
		    $freight = $oi["freight"]; 
		    if ($oi['days'] < 0){
		        $due_date = format_date($oi['date_invoiced'],'F j, Y');
		    } else {
		        $due_date = format_date($oi['date_invoiced'],'F j, Y',array("d"=>$oi['days']));
		    }
		}

		$freight_services = ($oi['freight_services_id'])? ' '.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';
		$freight_terms = ($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';

		$items = "SELECT * FROM ".$o['item']." WHERE `".$o['item_id']."` = $order_number ORDER BY IF(".$o['item_order']." IS NOT NULL,0,1), ".$o['item_order']." ASC;";
		if($o['credit'] && is_numeric($order_number)){
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
        if(!$o['lump']){
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
			$lineTotal = $price*$item['qty'];

			$part_details = current(hecidb($item['partid'],'id'));
			$part_strs = explode(' ',$part_details['Part']);
			$charge_descr = '';
			if ($item['partid']) {
				$part_details = current(hecidb($item['partid'],'id'));
				$part_strs = explode(' ',$part_details['Part']);
				$charge_descr = $part_strs[0].' &nbsp; '.$part_details['HECI'];
			} else if (isset($item['memo']) AND $item['memo']) {
				if ($item['memo']=='Freight') {
					$freight += $lineTotal;
					continue;
				}
				$charge_descr = $item['memo'];
			}
			$subtotal += $lineTotal;
			
			//FREIGHT CALCULATION HERE FOR INVOICE (based off the payment type/shipping account)
			$item_rows .= '
                <tr>
                    <td class="text-center">'.(($o['credit'] || $o['rma']) ? ++$i : $item['line_number']).'</td>
                    <td>
        	            '.$charge_descr.'
                        <div class="description '.$part_details['manf'].' '.$part_details['system'].' '.$part_details['description'].'</div>
                        <div class="'.($o['rma'] || $o['invoice'] ? '' : 'remove').'" style = "padding-left:5em;">
			';
			if ($serials && !$o['credit']){
				//Add Serials label
				foreach($serials as $serial){
					if($serial['invoice_item_id'] == $item['id']){
						$item_rows .= $serial['serial_no']."<br/>";
					}
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
                    <td class="'.($o['rma'] ? '' : 'remove').'">'.$item['reason'].'</td>
                    <td class="'.($o['rma'] ? '' : 'remove').'">'.getDisposition($item['dispositionid']).'</td>
                    <td class="'.($o['rma'] ? '' : 'remove').' text-center">'.$item['qty'].'</td>
				</tr>
			';
		}
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

$html_page_str .='
        <div id = "ps_bold">
            <h3>'.($email ? 'PO# ' . $order_number . ' Received' : $o['header'].' #'.$order_number).'</h3>
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
        <h2 class="text-center credit_memo '.($o['rma'] ? '' : 'remove').'">THIS IS NOT A CREDIT MEMO</h2>
        <table class="table-full">
            <tr>';
if(!$o['credit']){
$html_page_str .='
                <th class="'.($o['rma'] ? 'hidden' : '').'">Bill To</th>
                <th class="'.($o['lump'] ? 'remove' : '').'">'.($o['rma'] ? 'Return' : 'Ship').' To</th>';
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

if(!$o['invoice'] && !$o['credit'] && !$o['lump']){
$html_page_str .='
                    Please email invoices to:<br/>
					<a href="mailto:accounting@ven-tel.com">accounting@ven-tel.com</a>
				';
} else{
    //$html_page_str .= getContact($oi['contactid'])."<br>";
    $html_page_str .= address_out($oi['bill_to_id'], 'street');
}

$html_page_str.='</td>';

if(!$o['credit'] && !$o['lump']){
$html_page_str .= '
                <td class="half">
                    '.($o['rma'] ? 'Ventura Telephone, LLC <br>
                        3037 Golf Course Drive <br>
                        Unit 2 <br>
                        Ventura, CA 93003' : address_out($oi['ship_to_id'])).'
                </td>';
}
else if (!$o['lump']){
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

$order_date = $oi['created'];
if ($o['invoice']) { $order_date = $oi['date_invoiced']; }
if($o['lump']){ $order_date = $oi['lumpdate']; }

//Shipping information table
if(!$o['credit']){
$html_page_str .='
        <!-- Freight Carrier -->
        <table class="table-full" id="order-info">
            <tr>
                <th>'.$o['rep_type'].' Rep</th>
                <th>'.$o['date_label'].' Date</th>
                '.(($o['invoice'])? "<th>Payment Due</th>" : '').'
                <th>'.($o['lump'] ? "Contact" : $oi['order_type']).'</th>
                <th class="'.($o['rma'] ? 'remove' : '').'">Terms</th>
                <th class="'.($o['rma'] || $o['lump'] ? 'remove' : '').'">Shipping</th>
                <th class="'.($o['rma'] || $o['lump'] ? 'remove' : '').'">Freight Terms</th>
                '.(($o['invoice']|| $o['rma'])? "<th>PO # </th>" : '').'
            </tr>
            <tr>
                <td>
                    '.$rep_name.' <br>
                    '.(($rep_phone)?$rep_phone."<br>" : "").'
                    '.$rep_email.'
                </td>
                <td class="text-center">
                    '.format_date($order_date,'F j, Y').'
                </td>';
                if($o['invoice']){
                    $html_page_str .= '
                    <td class="text-center">
                        '.(($oi['credit'])? format_date($oi['date_invoiced'],'F j, Y',array("d" => $oi['days'])) : $due_date).'
                    </td>
                    <td>'.$orig_order.'</td>
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
                <td class="text-center '.(($o['rma'] || $o['lump']) ? 'remove' : '').'">'.getFreight('carrier',$oi['freight_carrier_id'],'','name').'
					'.$freight_services.'</td>
                <td class="'.(($o['rma'] || $o['lump']) ? 'remove' : '').'">'.$freight_terms.'</td>
                '.(($o['invoice'])? "<td>".$oi['cust_ref']."</td>" : '').'
            </tr>
        </table>';
        //End of the shipping information table
}

	$subtotal = round($subtotal,2);
	$total = round($subtotal+$freight,2);
if(!$o['lump']){
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
                    '.format_price($subtotal).'
                </td>
            </tr>
            <!--  -->
            <tr>
                <td style="text-align:right;border:none;">Freight</td>
                <td class="text-price">
                    '.format_price($freight).'
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
	';
} else {
    $cols = 0;
    $main = "<table class ='table-full'>";
    $main .="<thead>";
    foreach($lumps['mts'] as $type => $info){
        $main .="<th class='".$info['size']."'>".$info['title']."</th>";
        $cols++;
    }
    $main .= "</thead>";
    $main .= "<tbody>";
    foreach($lumps['main_table_rows'] as $i => $info){
        $main .= "<tr>";
        foreach($info as $type => $ordered){
            $main .= "<td style='".$lumps['mts'][$type]['it_style']."'>$ordered</td>";
        }
        $main .= "</tr>";
    }
    $main .= "</tbody>";
    $main .= "<tfoot>";
    $main .= "<tr>
                <td colspan='".($cols-1)."' style='text-align:right;'>Subtotal:</td>
                <td>".$lumps['subtotal']."</td>
            </tr>";
    $main .= "</tfoot>";
    $main .= "</table>";
    $html_page_str .= $main;
}
	$package_list = getPackageTracking($order_number);
	if($o['invoice']){
		$html_page_str .= '
				<table class="table-full table-striped table-condensed">
                    <tr>
                        <th>Package #</th>
                       <th>Tracking</th>
                    </tr>
                    '.$package_list.'
                </table>
		';
	}
    if(!$email) {
        $html_page_str .=' <div id="footer">
            <p class="'.(!$o['rma'] && !$o['credit'] ? '' : 'remove').'">
                Terms and Conditions:<br><br>
		';
    }
        if ($o['invoice']) {
			$html_page_str .= '
Ventura Telephone LLC ("VenTel") provides a limited warranty ("Warranty") against defects, as related to the functionality of the item, that occur within the established term of the Warranty, as described in the aforementioned Warranty options (Premium, Plus or Economy). The term of the Warranty begins on the date as printed on VenTel\'s invoice(s).

The Warranty also covers physical damage, only if discovered and reported to VenTel within five (5) business days from the delivery date, and so long as such claims can be established as pre-existing conditions prior to shipment. VenTel offers no insurance on damage to products during shipping transit, and VenTel is released from all liability of such damage to products after they leave VenTel\'s possession. Freight charges are not eligible for a credit or refund, or any other form of reimbursement, in the case a product is covered under this Warranty.

Software licensing or similar compatibility problems (ie, software/firmware version mismatch) are not covered under this Warranty. Products covered under the Warranty can be replaced, credited or refunded at VenTel\'s sole discretion. RMA or order cancellation requests not covered under the Warranty can, and will be, declined at VenTel\'s sole discretion, and such items remain billable in full (or subject to a Restocking Fee at VenTel\'s sole discretion). VenTel reserves the right to require documentation of equipment defect to determine Warranty eligibility.
</p>
			';
		} else if(!$email) {
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
?>
