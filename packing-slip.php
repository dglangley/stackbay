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
	    $select = "SELECT line_number FROM sales_items WHERE so_number = $order_number AND partid = $partid;";
	    $results = qdb($select);
	    
	    $line_number = '';
	    if (mysqli_num_rows($results) > 0){
	        $result = mysqli_fetch_assoc($results);
	        $line_number = $result['line_number'];
	    }
	    return $line_number;
	}
	
	function address_out($address_id){
		//General function for handling the standard display of addresses
		$address = '';
		//Address Handling
		$row = getAddresses($address_id);
		$name = $row['name'];
		$street = $row['street'];
		$city = $row['city'];
		$state = $row['state'];
		$zip = $row['postal_code'];
		$country = $row['country'];
		
		//Address Output
		if($name){$address .= $name."<br>";}
		if($street){$address .= $street."<br>";}
		if($city && $state){$address .= $city.", ".$state;}
		else if ($city || $state){ ($address .= $city.$state);}
		if($zip){$address .= "  $zip";}
		
		return $address;
	}

    // Grab the order number
    $order_number = prep(grab('on'));

    $order = "SELECT * FROM sales_orders WHERE so_number = $order_number;";
    $items = "SELECT serial_no, qty, last_sale,inventory.id invid, partid, package_no, packages.datetime date 
    FROM inventory, packages, package_contents WHERE last_sale = $order_number AND serialid = inventory.id and packageid = packages.id;";
    
	$order_result = qdb($order);
	$items_results = qdb($items);

	$order_info = array();
	if (mysqli_num_rows($order_result) > 0){
	    $order_info = mysqli_fetch_assoc($order_result);
	}
	
	$items_info = array();
	$tracking = array();
	if (mysqli_num_rows($items_results) > 0){
	    foreach ($items_results as $i){
	        $part = $i['partid'];
	        $serial = $i['serial_no'];
	        $qty = $i['qty'];
	        $box = $i['package_no'];
	        $tracking = $i['tracking'];
	        //Nesting goes $meta[$box][$part]
	        $items_info["$box,$tracking"][$part] = current(hecidb($part,'id'));
	        $items_info["$box,$tracking"][$part][]['qty'] += 1;
	        $items_info["$box,$tracking"][$part]['serials'][] = $serial;
	    }
	}
	echo("<pre>");
	print_r($items_info);
	echo("</pre>");
?>

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
            @media print{
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
                    right: 0;
                    font-size:14pt;
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
        <br>
        <div id = 'ps_bold'><b>PACKING SLIP #<?=grab('on');?></b></div>
        
        <div style="width:100%;height:60px;">&nbsp;</div>
        <!-- Shipping info -->

        <table id="addresses">
            <tr>
                <td>Bill To</td>
                <td>Ship To</td>
            </tr>
            <tr>
                <td><?=address_out($order_info['bill_to_id'])?></td>
                <td><?=address_out($order_info['ship_to_id'])?></td>
                
                
            </tr>
        </table>
        
        <!-- Freight Carrier -->
        <table id = "order-info">
            <tr>
                <td>Sales Rep</td>
                <td>Freight Carrier</td>
                <td>Customer PO</td>
            </tr>
            <tr>
                <td>
                    <?=getContact($order_info['sales_rep_id'])?> <br>
                    <?=getContact($order_info['sales_rep_id'],'id','phone')?><br>
                    <?=getContact($order_info['sales_rep_id'],'id','email')?>
                </td>
                
                <td><?=getFreight('carrier',$order_info['freight_carrier_id'],'','name')?> <?=strtoupper(getFreight('services','',$order_info['freight_services_id'],'method'))?></td>
                <td><?=$order_info['cust_ref']?></td>

            </tr>
        </table>
<?php
    foreach ($items_info as $box =>$part) {
        $box = explode(",",$box);
        echo"
        <table>
            <tr>
                <td colspan = '2' style = 'border:none;'><b>
                    Box #".$box[0]."
                </b></td>
                <td colspan = '5' style = 'text-align:right; border:none;'>
                    ".$box[1]."
                </td>
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
                echo"
                <tr>
                    <td>".getLN($order_number,$pid)."</td>
                    <td>".$info['part']."</td>
                    <td>".$info['heci']."</td>
                    <td>".$info['Descr']."</td>
                    <td>  </td>
                    <td>".$info['qty']."</td>
                    <td>";
                        foreach ($info['serials'] as $serial) {
                            echo ($serial."<br>");
                        }
                    echo "</td>";
                echo "</tr>";
            }
            echo "</tr>";
        echo "</table>";
    }
?>
        <div id="footer">If you have any questions, please call us at (805)212-4959</div>
    </body>
</html>