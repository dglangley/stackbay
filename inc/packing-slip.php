<?php
    // Standard includes section (We really need to condense this in a way which makes sense)
    $rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	
	function getLN($order_number,$partid){
	    //Grabs the line number
	    $select = "SELECT line_number FROM sales_items WHERE so_number = $order_number AND partid = $partid;";
	    $results = qdb($select);
	    
	    $line_number = '';
	    if (mysqli_num_rows($results) > 0){
	        $result = mysqli_fetch_assoc($results);
	        $line_number = $result['line_number'];
	    }
	    return $line_number;
	}
	
    $order_number = (grab('on'));
    $datetime = grab('date');
    function create_packing_slip($order_number,$datetime){
    // Grab the order number
    $porder_number = prep($order_number);
    $datetime = preg_replace('/%20/', ' ', $datetime);
    $ro_number = '';
 	$repair_item_check = "
		SELECT ro_number
			FROM sales_items si, packages, repair_items ri 
			where si.ref_1_label = 'repair_item_id' 
			and order_number = so_number 
			and order_number = ".prep($order_number)."
			and ri.id = si.ref_1 LIMIT 1;";
    
    $ro_number = rsrq($repair_item_check);
    
    $order = "SELECT * FROM sales_orders WHERE so_number = $porder_number;";
    $items = "
    SELECT serial_no, tracking_no, sales_item_id, inventory.id invid, inventory.partid, package_no, packages.datetime DATE, sales_orders.so_number
    FROM inventory, packages, package_contents, sales_items,sales_orders
    WHERE serialid = inventory.id
    AND packageid = packages.id
    AND sales_orders.so_number = sales_items.so_number
    AND sales_items.id = `inventory`.`sales_item_id`
    AND sales_orders.so_number = $porder_number 
    AND packages.datetime = '$datetime'
    GROUP BY serial_no
    ORDER BY package_no ASC;";


    
	$order_result = qdb($order);
	$items_results = qdb($items);

	$order_info = array();
	if (mysqli_num_rows($order_result) > 0){
	    $order_info = mysqli_fetch_assoc($order_result);
	    $public_notes = str_replace(chr(10),'<BR>',$order_info['public_notes']);
	}

	$items_info = array();
	$tracking = array();
	if (mysqli_num_rows($items_results) > 0){
	    foreach ($items_results as $i){
	        $part = $i['partid'];
	        $serial = $i['serial_no'];
	        $box = $i['package_no'];
	        $tracking = $i['tracking_no'];
	        $date = $i['DATE'];
	        //Nesting goes $meta[$box][$part]
	        $items_info["$box,$tracking,$date"][$part]['info'] = current(hecidb($part,'id'));
	        $items_info["$box,$tracking,$date"][$part]['qty'] += 1;
	        $items_info["$box,$tracking,$date"][$part]['serials'][] = $serial;
	    }
	}
$ps_string = '';
$ps_string .= '


<!DOCTYPE html>
<html>
    <head>
        <style type="text/css">
            body{
                font-family: "Arial";
                font-size:12px;
            }
            table{
                border-collapse: collapse;
                margin-bottom:30px;
            }
            td{
                border:thin black solid;
                padding:5px;
            }
            table{
                width:100%;
            }
            #addresses td{
                width:50%;
            }
            body{
                margin:0.5in;
            }
            #ps_bold{
                position: fixed;
                top: 0;
                width:100%;
                font-size:14pt;
                text-align:right;
                font-family:helvetica;
                font-size:16pt;
            }
            #letter_head{
                position:fixed;
                top: 0;
                left: 0;
                font-size:10pt;
            }

            #footer{
                text-align:center;
            }
        
            
        </style>
    </head>
    <body>
        <div id = "letter_head"><b>
            <img src="img/logo.png" style="width:1in;"></img><br>
            Ventura Telephone, LLC <br>
            3037 Golf Course Drive <br>
            Unit 2 <br>
            Ventura, CA 93003
        </b></div>
        <div id="ps_bold">
            Packing Slip
        </div>
        <br>
        <div style="width:100%;height:60px;">&nbsp;</div>
        <!-- Shipping info -->

        <table id="addresses">
            <tr>
                <td>Bill To</td>
                <td>Ship To</td>
            </tr>
            <tr>
                <td>'.address_out($order_info['bill_to_id']).'</td>
                <td>'.address_out($order_info['ship_to_id']).'</td>
            </tr>
        </table>
        
        <!-- Freight Carrier -->
        <table id = "order-info">
            <tr>
                <td>Sales Rep</td>
                <td>Freight Carrier</td>
                <td>Customer PO</td>
                '.(($ro_number)? "<td>Ticket #</td>" : "").'
                <td width="30%">Public Notes</td>
            </tr>
            <tr>
                <td>
                    '.getContact($order_info['sales_rep_id']).' <br>
                    '.getContact($order_info['sales_rep_id'],'id','phone').'<br>
                    '.getContact($order_info['sales_rep_id'],'id','email').'
                </td>
                
                <td>'.getFreight('carrier',$order_info['freight_carrier_id'],'','name').' '.strtoupper(getFreight('services','',$order_info['freight_services_id'],'method')).'</td>
                <td>'.$order_info['cust_ref'].'</td>
                '.(($ro_number)? "<td>".$ro_number."</td>" : "").'
                <td>'.$public_notes.'</td>
            </tr>
        </table>
';
    $tracking_table  = "<table>";
    $tracking_table .= "
    <tr>
        <td colspan='2' style='text-align:center;'>Tracking Numbers</td>
    </tr>
    <tr>
        <td><i>Box</i></td>
        <td><i>Tracking Number</i></td>
    </tr>
    ";
    foreach ($items_info as $box =>$part) {
        $row_span++;
        $box = explode(",",$box);
        $ps_string .="
        <table>
            <tr>
                <td colspan = '2' style = 'border:none;'><b>
                    Box #".$box[0]."
                </b></td>
                <td colspan = '5' style = 'text-align:right; border:none;'><b>
                    ".(($box[1])? "Tracking #: ".$box[1] : '')."
                </b></td>
            </tr>
            <tr>
                <td>Ln#</td>
                <td>Part</td>
                <td>HECI</td>
                <td>Description</td>
                <td>Ref No</td>
                <td>Qty</td>
                <td>Serials</td>
            </tr>";
            
            foreach ($part as $pid => $info) {
                $ps_string .="
                <tr>
                    <td>".getLN($order_number,$pid)."</td>
                    <td>".$info['info']['part']."</td>
                    <td>".$info['info']['heci']."</td>
                    <td>".$info['info']['Descr']."</td>
                    <td>  </td>
                    <td>".$info['qty']."</td>
                    <td>";
                        foreach ($info['serials'] as $serial) {
                            $ps_string .= ($serial."<br>");
                        }
                    $ps_string .= "</td>";
                $ps_string .= "</tr>";
            }
            $ps_string .= "</tr>";
        $ps_string .= "</table>";
        $tracking_table .= "
        <tr>
            <td>".$box[0]."</td>
            <td>".$box[1]."</td>
        </tr>
        ";
    }
        $tracking_table .= "</table>";
        $ps_string .= $tracking_table;
        $ps_string .='<div id="footer">If you have any questions, please call us at (805)212-4959</div>
            </body>
        </html>';
        return $ps_string;
}
    // create_packing_slip($order_number,$datetime);