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
	include_once $rootdir.'/inc/getCarrier.php';
	include_once $rootdir.'/inc/getFreightService.php';
	include_once $rootdir.'/inc/getFreight.php';
    include_once $rootdir.'/inc/getWarranty.php';
    include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/form_handle.php';
//	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/order_type.php';
	include_once $rootdir.'/inc/invoice.php';
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/getStatusCode.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_address.php';
	
    function getPackageTracking($invoice_number) {
        $tracking = array();
        $html = '';

        $packages = "SELECT * FROM invoice_items ii, invoice_shipments s, packages p
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
            return "N/A";
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
            SELECT il.date lumpdate, ili.invoice_no, ii.id invoice_item_id, il.id id, i.order_type, i.order_number, ii.item_id partid,
			  ii.ref_1, ii.ref_1_label, ii.ref_2, ii.ref_2_label, ii.qty, ii.amount/*, p.tracking_no, iss.* */
              FROM `invoice_lumps` il,`invoice_lump_items` ili, `invoices` i, `invoice_items` ii/*, invoice_shipments iss, packages p*/
              WHERE il.id = ili.lumpid 
              AND i.invoice_no = ili.invoice_no 
              AND i.invoice_no = ii.invoice_no
/*
              AND p.order_number = i.order_number
              AND i.order_type = p.order_type
              AND iss.packageid = p.id
              AND iss.invoice_item_id = ii.id
*/
              AND il.id = $plumpid
              ORDER by ii.id ;";
            $results = qdb($select) or die(qe()." | $select");
            $return = array('subtotal'=>0.00);
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
                //print_r($r);

                //Get the order level information
//				$o = o_params($r['order_type']);
				$T = order_type($r['order_type']);
                $order_number = $r['order_number'];
                $macro_select = "
                SELECT *, '".$r['lumpdate']."' lumpdate, '".$r['order_type']."' order_type FROM
                ".$T['orders']." o ,".$T['items']." i, inventory inv
                WHERE o.".$T['order']." = ".prep($order_number)." 
                AND o.".$T['order']." = i.".$T['order']." 
                AND inv.".$T['inventory_label']." = i.id;";
                $macro = qdb($macro_select) or die(qe()." | $macro_select");
                $return['order_info']= mysqli_fetch_assoc($macro);
                // $part = current(hecidb($r['partid'],'id'));
                if(!$already_entered[$r['order_type'].$r['order_number']]){
                    $already_entered[$r['order_type'].$r['order_number']] = true;
                    // echo($macro_select);
                    foreach ($macro as $it => $mac) {
                        //Normally we will only have one record per macro, but eventually RO/SOs will be lumped for multiple items, so foreach should work
                        $item_status = '';
                        if($r['order_type']=='Repair') {
                            $item_status = getStatusCode($mac['repair_code_id'],$r['order_type']);
                        } else {
                            $item_status = "Shipped";
                        }
                		$row = array();
                        $row['line_no'] = ++$line_no;
                        $row['item'] = $T['abbrev']." ".$order_number;
                        $row['description'] = "
                        P/N: ".getPart($r['partid'])."<br>
                        S/N: ".$mac['serial_no']."<br>
                        Tracking #: ".$r['tracking_no']."<br>
                        Item Status: $item_status<br>
                        ".($r['ref_1_label']? $r['ref_1_label'].": ".$r['ref_1'] : "")."<br>
                        ".($r['ref_2_label']? $r['ref_2_label'].": ".$r['ref_2'] : "");
                        $row['qty'] = $r['qty'];
                        $row['price'] = format_price($r['amount'],false,'',true);
                        $row['ext'] = $r['qty'] * $r['amount'];
                        $return['subtotal'] += $row['ext'];
                        $row['ext'] = format_price($row['ext'],true,' ');
                        $return['main_table_rows'][] = $row;
                    }
                }
            }
			$return['subtotal'] = format_price($return['subtotal']);
            return $return;
        }

	function renderOrder($order_number,$order_type='Purchase', $email = false) {
        $oi = array();

//		$o = o_params($order_type);
		$lump = false;
		if ($order_type=='Lump') {
			$order_type = 'Invoice';
			$lump = true;
		}
		$order_type = ucwords(strtolower($order_type));
		if ($order_type=='Rma') { $order_type = 'RMA'; }
		else if ($order_type=='Inv') { $order_type = 'Invoice'; }

		$T = order_type($order_type);
	    $prep = prep($order_number);
        
		$tax_rate = 0;
		$sales_tax = 0;
		$due_date = "";
		$subtotal = 0;
		$freight = 0;
		$total = 0;
        $serials = array();
		if ($order_type=='Invoice') {
            $serials = getInvoicedInventory($order_number, "`serial_no`,`invoice_item_id`,`taskid`,`task_label`");
        } 

		$orig_order = $order_number;
		if (! $lump) {
			$query = "SELECT * ";
			if ($order_type=='Credit') { $query .= ", order_number "; }
			$query .= "FROM `".$T['orders']."` ";
			$query .= "WHERE `".$T['order']."` = $order_number;";

			$result = qedb($query);
			if (mysqli_num_rows($result) == 0 AND ! $GLOBALS['DEBUG']) {
				die("Could not pull record");
			}
			$oi = mysqli_fetch_assoc($result);
			// if this record extends off another record (i.e., Credit Memo for Repair)
			if (isset($oi['order_type'])) {
				$T2 = order_type($oi['order_type']);
				// query corresponding record for address details
				$query2 = "SELECT * FROM ".$T2['orders']." WHERE ".$T2['order']." = '".$oi['order_number']."'; ";
				$result2 = qedb($query2);

				// should be just one record, but whatever...
				while ($r2 = mysqli_fetch_assoc($result2)) {
					if ($r2[$T2['addressid']]) { $oi[$T2['addressid']] = $r2[$T2['addressid']]; }
					$tax_rate = $r2['tax_rate'];
				}
			}
		} else { 
			$lumps = printLumpedInvoices($order_number);
			$oi = $lumps['order_info'];
		}

		// is order a sale or repair?
		if (! $lump AND ($order_type=='Invoice' OR $order_type=='Credit')) {
			$orig_order = $oi['order_number'];
			if ($oi["order_type"]=='Sale') {
				$query2 = "SELECT * FROM sales_orders, terms WHERE so_number = '".$oi["order_number"]."' AND termsid = terms.id; ";
				$result2 = qedb($query2);
			} else if ($oi["order_type"]=='Repair') {
				$query2 = "SELECT ro.*, t.* FROM repair_orders ro, terms t, repair_items ri ";
				$query2 .= "WHERE ro.ro_number = '".$oi["order_number"]."' AND t.id = ro.termsid AND ro.ro_number = ri.ro_number; ";
				$result2 = qedb($query2);
			} else if ($oi["order_type"]=='Service') {
				$query2 = "SELECT so.*, t.* FROM service_orders so, terms t ";
				$query2 .= "WHERE so.so_number = '".$oi["order_number"]."' AND t.id = so.termsid; ";
				$result2 = qedb($query2);
			}
			if (mysqli_num_rows($result2)==0) {
				die("Could not pull originating ".$oi["order_type"]." record for this invoice: ".$query2);
			}
			$r2 = mysqli_fetch_assoc($result2);
			foreach ($r2 as $k => $v) {
				if (! isset($oi[$k])) { $oi[$k] = $v; }
			}
		}

		if ($order_type=='Invoice') {
		    $freight = $oi["freight"];
		    $sales_tax = $oi["sales_tax"]; 
		    if ($oi['days'] < 0){
		        $due_date = format_date($oi['date_invoiced'],'F j, Y');
		    } else {
		        $due_date = format_date($oi['date_invoiced'],'F j, Y',array("d"=>$oi['days']));
		    }
		}

		$freight_services = '';
		if ($oi['freight_carrier_id']) {
			$freight_services = getCarrier($oi['freight_carrier_id']);
		}
		if ($oi['freight_services_id']) {
			if ($freight_services) { $freight_services .= ' '; }
			$freight_services .= getFreightService($oi['freight_services_id']);
		}
//		$freight_services = ($oi['freight_services_id'])? ' '.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';
		$freight_terms = ($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';

		$items = "SELECT * FROM ".$T['items']." WHERE `".$T['order']."` = $order_number ORDER BY IF(".$T['order']." IS NOT NULL,0,1), ".$T['order']." ASC, line_number ASC, id ASC;";
		if (! $lump AND $order_type=='Credit' AND is_numeric($order_number)) {
			$items = "SELECT sci.*, sci.id AS scid, sci.amount AS price, GROUP_CONCAT(i.serial_no) AS serials, SUM(sci.qty) qty, i.partid ";
			$items .= "FROM credits sc, credit_items sci, return_items ri, inventory i, inventory_history ih ";
			$items .= "WHERE sc.id = '".$order_number."' AND sci.cid = sc.id ";
			$items .= "AND sci.return_item_id = ri.id AND ri.inventoryid = i.id AND i.id = ih.invid ";
			if ($oi['order_type']=='Repair') {
				$items .= "AND ih.field_changed = 'repair_item_id' AND ih.value = sci.item_id AND sci.item_id_label = 'repair_item_id' ";
			} else {
				$items .= "AND ih.field_changed = 'sales_item_id' AND ih.value = sci.item_id AND sci.item_id_label = 'sales_item_id' ";
			}
		    $items .= "GROUP BY sci.cid; ";

		}

		$order_charges = array();

		// This section takes care of order_charges as requested by Sabedra on 2/15/2018
		// Group the sum by memo so we don't get duplicates in case
		if($order_type == 'Sale' OR $order_type == 'Purchase') {
			$query = "SELECT memo, SUM(price) total FROM ".$T['charges']." WHERE ".$T['order']." = ".res($order_number)." GROUP BY memo;";
			$result = qedb($query);

			while($r = mysqli_fetch_assoc($result)) {
				$order_charges[] = $r;
			}
		}

		//print_r($order_charges);

		//Make a call here to grab RMA's items instead

		//And sort through serials instead of PO_orders
		// Choosing 9 to avoid any conflicts and as a fast solution to fix instead of searching for the last query number
		$result9 = qdb($items) or die (qe()." | ".$items);

		// By pass Aaron's way of parsing thru each line and pregenerate the array instead of generate html lines on the go
		if ($order_type == 'Purchase') {
			while($r9 = mysqli_fetch_assoc($result9)) {
				// $items_results[] = $r9;
				$found = false;
				if(! empty($items_results)) {
					foreach($items_results as $key => $part) {
						if($part['partid'] == $r9['partid'] AND $part['price'] == $r9['price']) {
							$items_results[$key]['qty'] = $part['qty'] + $r9['qty'];
							$items_results[$key]['line_number'] = '';
							$quantity+=$r9['qty'];
							$found = true;

							// If it is found then end the foreach loop as the qty has been updated on the basis the price and the partid matches
							break;
						} 
					}

					if(! $found) {
						$items_results[] = $r9;
					}
				} else {
					// Initial first element
					$items_results[] = $r9;
				}
			}
		} else {
			$items_results = $result9;
		}
		// print_r($items_results);
        
        //Process Item results of the credit to associate the serials into a nested array
		$item_rows = '';
        $i = 0;
		if (! $lump) {
		    foreach($items_results as $item){
			if ($order_type=='Credit') {
                $serials = explode(",",$item['serials']); 
            }
            
			$price = 0.00;
			if ($item['price']){
				$price = $item['price'];
			} else if ($item['amount']){
				$price = $item['amount'];
			}
			$lineTotal = $price*$item['qty'];

			$charge_descr = '';
			$part_descr = '';
			if ($item['partid'] OR ($item['item_id'] AND ($item['item_label']=='partid' OR ! $item['item_label']))) {
				$partid = 0;
				if ($item['partid']) { $partid = $item['partid']; }
				else { $partid = $item['item_id']; }
				$part_details = current(hecidb($partid,'id'));
				$part_strs = explode(' ',$part_details['Part']);
				$charge_descr = $part_strs[0].' &nbsp; '.$part_details['HECI'];
				$part_descr = $part_details['manf'].' '.$part_details['system'].' '.preg_replace('/, REPLACE.*/','',$part_details['description']);
			} else if ($item['item_id'] AND $item['item_label']=='addressid') {
				$charge_descr = format_address($item['item_id'],', ',true,'',$oi['companyid'],'<br/>');
				if ($item['notes']) { $charge_descr .= '<br>'.$item['notes']; }

			} else {
				$charge_descr = $item['notes'];
			}
			if (isset($item['memo']) AND $item['memo']) {
				if ($item['memo']=='Freight') {
					$freight += $lineTotal;
					continue;
				} else if (strstr($item['memo'],'Tax')) {
					$sales_tax += $lineTotal;
					continue;
				}
				if ($charge_descr) { $charge_descr .= '<br>'; }
				$charge_descr .= $item['memo'];
			}
			$subtotal += $lineTotal;
			
			//FREIGHT CALCULATION HERE FOR INVOICE (based off the payment type/shipping account)
			$item_rows .= '
                <tr>
                    <td class="text-center">'.(($order_type=='Credit' OR $order_type=='RMA') ? ++$i : $item['line_number']).'</td>
                    <td style="text-align:left !important">
        	            <div class="text-left">'.$charge_descr.'</div>
                        <div class="description">'.$part_descr.'</div>
                        <div class="'.($order_type=='RMA' OR $order_type=='Invoice' ? '' : 'remove').'" style = "padding-left:5em;">
			';
			if ($serials AND $order_type<>'Credit') {
				//Add Serials label
				foreach($serials as $serial){
					if($serial['invoice_item_id'] == $item['id']){
//if (! $serial['taskid'] OR ($serial['taskid'] AND $serial['task_label'] AND $item['taskid']==$serial['taskid'] AND $item['task_label']==$serial['task_label'])) {
						$item_rows .= $serial['serial_no']."<br/>";
//}
					}
				}
			}
			$item_rows .='</ul>
                        </div>
                    </td>
                    <td class="text-center '.(($T['due_date'])? '' : 'remove' ).'">'.format_date($item[$T['datetime']],'m/d/y').'</td>
					<td class="text-center '.($oi['repair_code_id'] ? '' : 'remove').'">'.getStatusCode($oi['repair_code_id'],$oi['order_type']).'</td>
                    <td class="text-center '.($T['warranty'] ? '' : 'remove').'">'.getWarranty($item[$T['warranty']],'name').'</td> 
			';
			$item_rows .= ($order_type=='Purchase' ? '<td>'.getCondition($item['conditionid']).'</td>' : "");
			if($order_type=='Credit'){
				$item_rows .= "<td>";
				foreach($serials as $serial){
					$item_rows .= "$serial<br>";
				}
				$item_rows .= "</td>";
			}
			$item_rows .= '
                    <td class="text-center '.($order_type=='RMA' ? 'remove' : '').'">'.$item['qty'].'</td>
                    <td class="text-right '.($order_type=='RMA' ? 'remove' : '').'">'.format_price($price).'</td>
                    <td class="text-right '.($order_type=='RMA' ? 'remove' : '').'">'.format_price($lineTotal).'</td>
                    <td class="'.($order_type=='RMA' ? '' : 'remove').'">'.$item['reason'].'</td>
                    <td class="'.($order_type=='RMA' ? '' : 'remove').'">'.getDisposition($item['dispositionid']).'</td>
                    <td class="'.($order_type=='RMA' ? '' : 'remove').' text-center">'.$item['qty'].'</td>
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
            table tr td, table tr th {
                page-break-inside: avoid;
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
            #footer {
                position: absolute;
                bottom: 0;
            }

            #footer_offset {
                height: 200px;
            }
        </style>
    </head>
    <body>';

	$header = '';
	if ($email) {
		$header = $T['abbrev'].' '.$order_number.' Complete';
	} else {
		if (($order_type=='Outsourced' OR $order_type=='outsourced_item_id') AND $oi["order_type"]) { $header = 'Outside '.$oi["order_type"].' '; }
		else if ($order_type=='Credit') { $header = $order_type.' Memo '; }
		else if ($order_type=='Sale') { $header = 'Proforma Invoice '; }
		else if ($order_type) { $header = $order_type.' '; }
		if (! $lump AND $order_type<>'Credit' AND $order_type<>'Invoice' AND $order_type<>'RMA' AND $order_type<>'Sale') { $header .= 'Order '; }
		$header .= $order_number;
	}

$html_page_str .='
        <div id = "ps_bold">
            <h3>'.$header.'</h3>
            <table class="table-full" id = "vendor_add" '.($order_type=='Credit' ? "style='display:none;'": "").'>
				<tr>
					<th class="text-center">Company</th>
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
        <h2 class="text-center credit_memo '.($order_type=='RMA' ? '' : 'remove').'">THIS IS NOT A CREDIT MEMO</h2>
        <table class="table-full">
            <tr>';
if($order_type<>'Credit'){
$html_page_str .='
                <th class="'.($order_type=='RMA' ? 'hidden' : '').'">Bill To</th>
                <th class="'.($liump ? 'remove' : '').'">'.($order_type=='RMA' ? 'Return' : 'Ship').' To</th>';
} else {
    $html_page_str .= '
    <th>Customer</th>
    <th>Credit Date</th>
    <th>PO #</th>
    <th>'.strtoupper(substr($oi['order_type'],0,1)).'O #</th>';
    if ($oi['rma_number']){
    $html_page_str.='
        <th>RMA #</th>
    ';
    }
}
$html_page_str .='                
            </tr>
            <tr>
                <td class="half '.($order_type=='RMA' ? 'hidden' : '').'">';

if (! $lump AND $order_type<>'Invoice' AND $order_type<>'Credit') {
$html_page_str .='
                    Please email invoices to:<br/>
					<a href="mailto:accounting@ven-tel.com">accounting@ven-tel.com</a>
				';
} else{
    //$html_page_str .= getContact($oi['contactid'])."<br>";
    $html_page_str .= address_out($oi['bill_to_id'], 'street');
}

$html_page_str.='</td>';

if ($order_type<>'Credit' AND ! $lump) {
$html_page_str .= '
                <td class="half">
                    '.($order_type=='RMA' ? 'Ventura Telephone, LLC <br>
                        3037 Golf Course Drive <br>
                        Unit 2 <br>
                        Ventura, CA 93003' : address_out($oi['ship_to_id'])).'
                </td>';
}
else if (!$lump){
    $html_page_str .='
    <td class="text-center">'.format_date($oi['date_created'],"M j, Y").'</td>
    <td class="text-center">'.$oi['cust_ref'].'</td>
    <td class="text-center">'.$oi['order_number'].'</td>';
    
    if($oi['rma_number']){
        $html_page_str .= '<td class= "pull-center">'.$oi['rma_number'].'</td>';
    }
}
$html_page_str .= '
            </tr>
        </table>
';
//End of the addresses table

$rep_name = getContact($oi['sales_rep_id'],'userid','name');
$rep_phone = getContact($oi['sales_rep_id'],'userid','phone');
$rep_email = getContact($oi['sales_rep_id'],'userid','email');

$contact_name = getContact($oi['contactid']);
$contact_phone = getContact($oi['contactid'],'id','phone');
$contact_email = getContact($oi['contactid'],'id','email');

$order_date = $oi[$T['datetime']];
if ($order_type=='Invoice') { $order_date = $oi['date_invoiced']; }
if($lump){ $order_date = $oi['lumpdate']; }

//Shipping information table
if($order_type<>'Credit'){
$html_page_str .='
        <!-- Freight Carrier -->
        <table class="table-full" id="order-info">
            <tr>
                <th>Rep</th>
                <th>'.$T['abbrev'].' Date</th>
                '.(($order_type=='Invoice')? "<th>Payment Due</th>" : '').'
                <th>'.($lump ? "Contact" : $oi['order_type']).'</th>
                <th class="'.($order_type=='RMA' ? 'remove' : '').'">Terms</th>
                <th class="'.($order_type=='RMA' || $lump ? 'remove' : '').'">Shipping</th>
                <th class="'.($order_type=='RMA' || $lump ? 'remove' : '').'">Freight Terms</th>
                '.(($order_type=='Invoice'|| $order_type=='RMA')? "<th>PO # </th>" : '').'
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
                if($order_type=='Invoice'){
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
                
$html_page_str .= '<td class="text-center '.($order_type=='RMA' ? 'remove' : '').'">
                    '.display_terms($oi['termsid']).'
                </td>';
                //<td class="text-center '.(($order_type=='RMA' || $lump) ? 'remove' : '').'">'.getFreight('carrier',$oi['freight_carrier_id'],'','name').' '.$freight_services.'</td>
$html_page_str .='
                <td class="text-center '.(($order_type=='RMA' || $lump) ? 'remove' : '').'">'.$freight_services.'</td>
                <td class="'.(($order_type=='RMA' || $lump) ? 'remove' : '').'">'.$freight_terms.'</td>
                '.(($order_type=='Invoice')? "<td>".$oi['cust_ref']."</td>" : '').'
            </tr>
        </table>';
        //End of the shipping information table
}

	$subtotal = round($subtotal,2);
	$total = round($subtotal+$freight+$sales_tax,2);
if(!$lump){
	$html_page_str .= '
<!-- Items Table -->
        <table class="table-full table-striped table-condensed">
            <tr>
                <th>Ln#</th>
                <th>Description</th>
                <th class="'.(($T['due_date'])? '' : 'remove' ).'">Due Date</th>
				<th class="'.($oi['repair_code_id']? '' : 'remove').'">Repair Status</th>
                <th class="'.($T['warranty']? '' : 'remove').'">Warranty</th>
                '.($order_type=='Credit'? '<th>Serials</th>' : "").'
                '.($T['condition']? '<th>Cond</th>' : "").'
                <th class="'.($order_type=='RMA' ? 'remove' : '').'">Qty</th>
                <th>'.$T['amount'].'</th>
                <th>'.($order_type=='RMA' ? 'Disposition' : 'Ext Amount').'</th>
                <th class="'.($order_type=='RMA' ? '' : 'remove').'">Qty</th>
            </tr>
            
			'.$item_rows.'
		</table>
        <table class="table-full '.($order_type=='RMA' ? 'remove' : '').'">
            <!-- Subtotal -->
            <tr>
				<td rowspan=3>'.$oi['public_notes'].'</td>
                <td style="text-align:right;border:none;">Subtotal</td>
                <td class="text-right">
                    '.format_price($subtotal,true,' ').'
                </td>
            </tr>
            <tr>
                <td style="text-align:right;border:none;">Freight</td>
                <td class="text-price">
                    '.format_price($freight,true,' ').'
                </td>
            </tr>';

    if(! empty($order_charges)) {
	    foreach($order_charges as $charge) {
	    	$html_page_str .= '
	    		<tr>
	                <td style="text-align:right;border:none;">'.$charge['memo'].'</td>
	                <td class="text-price">
	                    '.format_price($charge['total'],true,' ').'
	                </td>
	            </tr>
	    	';

	    	$total += $charge['total'];
	    }
	}

    $html_page_str .= '
            <tr>
            	'.(! empty($order_charges) ? '<td></td>' : '').'
                <td style="text-align:right;border:none;">Sales Tax'.($tax_rate ? ' '.$tax_rate.'%' : '').'</td>
                <td class="text-price">
                    '.format_price($sales_tax,true,' ').'
                </td>
            </tr>
            <tr class="total">
				<td> </td>
                <td style="text-align:right;"><b>Total</b></td>
                <td id = "total" class="text-price">
                    <b>'.format_price($total,true,' ').'</b>
                </td>
            </tr>
        </table>
	';
} else {
    $cols = 0;
    $main = "<table class ='table-full'>";
    $main .="<thead><tr>";
    foreach($lumps['mts'] as $type => $info){
        $main .="<th class='".$info['size']."'>".$info['title']."</th>";
        $cols++;
    }
    $main .= "</tr></thead>";
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
                <td class='text-right'>".format_price($lumps['subtotal'],true,' ')."</td>
            </tr>";
    $main .= "</tfoot>";
    $main .= "</table>";
    $html_page_str .= $main;
}
	$package_list = getPackageTracking($order_number);
	if($order_type=='Invoice' AND ! $lump){
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
        $html_page_str .=' <div id="footer_offset"></div>';
        $html_page_str .=' <div id="footer">
            <p class="'.($order_type<>'RMA' && $order_type<>'Credit' ? '' : 'remove').'">
		';
		if ($order_type=='Sale') {
			$html_page_str .= '
				<strong>WIRE INSTRUCTIONS</strong><br/>
				Bank Name: JPMorgan/ Chase<br/>
				Account Name: Ventura Telephone LLC<br/>
				Bank Address:<br/>
				Ventura Marina<br/>
				2499 Harbor Blvd<br/>
				Ventura, CA 93001<br/>
				Phone (805) 650-5567<br/>
				Account # 599568883<br/>
				Routing# 322271627<br/>
				Swift Code: CHASUS33<br/>
				<br/>
			';
		}
		$html_page_str .= '
                Terms and Conditions:<br><br>
		';
    }
        if ($order_type=='Invoice') {
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
            <p class="'.($order_type=='RMA' ? '' : 'remove').'">
                Return Instructions:<br><br>
                Print and return this form with the product(s) to be returned. Improperly packaged or incomplete product(s) will void this RMA. Returned product(s) must match this RMA exactly, substitutes are not allowed. RMA is valid for 30 calendar days.
                <br><br>
                Product(s) returned can be replaced, credited or refunded at Ventura Telephone\'s sole discretion. Product(s) remain billable in full if not credited or refunded. No Trouble Found ("NTF") product(s) are subject to a restocking fee. RMA processing may take up to 30 calendar days after receipt.
                <br><br>
                Please ship UPS Ground on Account# 360E2A.
            </p>
            <p class="'.($order_type=='Credit' ? '' : 'remove').' text-center">
                If you have any questions please call us at (805)212-4959
            </p>
        </div>
    </body>
</html>
		';
    }
    
    // echo $html_page_str;
    // die();

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
