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
    include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/form_handle.php';
	
	//I will eventually have to generalize this: It is useful enough
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
    
    // Grab the order number
    $order_number = grab('on');
    $prep = prep($order_number);
	$order_type = ($_REQUEST['ps'] == 'p' || $_REQUEST['ps'] == 'Purchase') ? "Purchase" : "Sales";
	$number_type = ($order_type == 'Purchase')? 'po_number' : 'so_number';
    $vendor = ($order_type == "Purchase")? "remit" : "bill";
    $order_table = strtolower($order_type)."_orders";
    $item_table = strtolower($order_type)."_items";
    $date_field = ($order_type == "Purchase") ? "receive_date" : "delivery_date";
    $order = "SELECT * FROM $order_table WHERE `$number_type` = $order_number;";
    $items = "SELECT * FROM $item_table WHERE `$number_type` = $order_number;";
    // echo $order;exit;    
	$order_result = qdb($order);
	$items_results = qdb($items);
    
	$oi = array();
	if (mysqli_num_rows($order_result) > 0){
	    $oi = mysqli_fetch_assoc($order_result);
	}
	

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
                vertical-align:top;
            }
            #footer{
                display:none;
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
                    text-align:right;
                    margin-top:20.670px;
                }
                #letter_head{
                    position:fixed;
                    top: 0;
                    left: 0;
                    font-size:10pt;
                }
                #footer{
                    display:block;
                    position:fixed;
                    bottom:15px;
                    text-align:left;
                    width:100%;
                }
                #vendor_add{
                    font-size:13px;
                }
                #total{
                    background-color:#ccc;
                }
                #spacer {
                    width:100%;
                    height:100px;
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
            Ventura, CA 93003<br>
            (805) 212-4959
            </b>
        </div>
        <br>
        <div id = 'ps_bold'>
            <span ><b><?=$order_type?> Order #<?=grab('on');?></b><br></span>
            <span id = "vendor_add"><?=address_out($oi[$vendor."_to_id"])?></span>
        </div>
        
        <div id = "spacer">&nbsp;</div>
        <!-- Shipping info -->

        <table id="addresses">
            <tr>
                <td>Bill To</td>
                <td>Ship To</td>
            </tr>
            <tr>
                <td><?=address_out(1)?>
                    <br>
                    accounting@ven-tel.com
                </td>
                <td>
                    <?=address_out($oi['ship_to_id'])?>
                    <br>
                    <?=getFreight('account',"",$oi['freight_account_id'],"account_no")?>
                    <?=getFreight('carrier',$oi['freight_carrier_id'],'','name')?>
                </td>
                
                
            </tr>
        </table>
        
        <!-- Freight Carrier -->
        <table id = "order-info">
            <tr>
                <td>Purchase Rep</td>
                <td>PO Date</td>
                <td>Sales Rep</td>
                <td>Terms</td>
                <td>Shipping</td>
                <td>Freight Terms</td>
            </tr>
            <tr>
                <td>
                    <?=getContact($oi['sales_rep_id'])?> <br>
                    <?=getContact($oi['sales_rep_id'],'id','phone')?><br>
                    <?=getContact($oi['sales_rep_id'],'id','email')?>
                </td>
                <td>
                    <?=format_date($oi['created'],'F d, Y')?>
                </td>
                <td>
                    <?=getContact($oi['contactid'])?> <br>
                    <?=getContact($oi['contactid'],'id','phone')?><br>
                    <?=getContact($oi['contactid'],'id','email')?>
                </td>
                <td>
                    <?=display_terms($oi['termsid'])?>
                </td>
                <td><?=getFreight('carrier',$oi['freight_carrier_id'],'','name')?>
                <?=($oi['freight_services_id'])? '-'.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';?></td>
                <td><?=($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';?></td>
            </tr>
        </table>

<!-- Items Table -->
        <table>
            <tr>
                <td>Ln#</td>
                <td>Description</td>
                <td>Due Date</td>
                <td>Warranty</td>
                <td>Qty</td>
                <td>Price</td>
                <td>Ext Price</td>
            </tr>
            
<?php
            foreach($items_results as $items){
            $part_details = current(hecidb($items['partid'],'id'));
            $lineTotal = $items['price']*$items['qty'];
            $total += $lineTotal;
            echo"

                <tr>
                    <td>".$items['line_number']."</td>
                    <td>
        	            <span class='descr-label'><span class='part-label'>".$part_details['Part']."</span> &nbsp; <span class='heci-label'>".$part_details['HECI']."</span></span>
                        <div class='description descr-label' style='font-size:8pt;color:#aaa;'><span class='manfid-label'>".$part_details['manf']."</span> <span class='systemid-label'>".$part_details['system']."</span> <span class='description-label'>".$part_details['description']."</span></div>
                    </td>
                    <td>".format_date($items[$date_field],'m/d/y')."</td>
                    <td>".getWarranty($items['warranty'],'name')."</td> 
                    <td>".$items['qty']."</td>
                    <td>".format_price($items['price'])."</td>
                    <td>".format_price($lineTotal)."</td>";
                echo "</tr>";
            }
?>
            <!-- Subtotal -->
            <tr>
                <td colspan ='6' style="text-align:right;border:none;border-left:thin black solid;">Subtotal:</td>
                <td>
                    <?=format_price($total)?>
                </td>
            </tr>
            <!--  -->
            <tr>
                <td colspan ='6' style="text-align:right;border:none;border-left:thin black solid;">Freight:</td>
                <td>
                    <?=format_price(0)?>
                </td>
            </tr>
            <tr>
                <td colspan ='6' style="text-align:right;border:none;border-left:thin black solid;">Tax:</td>
                <td>
                    <?='0.00%'?>
                </td>
            </tr>
            <tr>
                <td colspan ='6' style="text-align:right;"><b>Total:</b></td>
                <td id = "total">
                    <b><?=format_price($total)?></b>
                </td>
            </tr>
        </table>
        <div id="footer">
            Terms and Conditions:<br><br>
            Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications
            listed herein. Shipment of goods or execution of services against this PO specifies agreement with our
            terms.<br><br>
            Invoicing: VenTel requires that vendors provide ONE invoice per purchase order. Items on the invoice must
            match items on the purchase order. Due date for payment terms begins when the order is received
            complete. Failure to abide by these terms may result in delayed payment at no fault by the purchaser.
            Please communicate all questions regarding these conditions within 15 days.
        </div>
    </body>
</html>
