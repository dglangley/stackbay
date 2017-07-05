<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	//include_once $rootdir.'/dompdf/autoload.inc.php';
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

    $order_number = $_REQUEST['on'];

    $item_rows = renderOrder($order_number);
	$total;
	$oi;

    function renderOrder($order_number,$order_type='Sales') {
    	global $total;
    	global $billto;
    	global $oi;

	    $o = array();
	    //Switch statement to add in more features for now until we have a solid naming convention

        $o = o_params($order_type);
	    $prep = prep($order_number);
        
        $added_order ="";
        $added_order_join = "";
        $serials = array();
        if ($o['invoice']){
            $added_order = ", `sales_orders`, `terms` ";
            $added_order_join = " AND `sales_orders`.so_number = order_number AND termsid = terms.id";
            $serials = getInvoicedInventory($order_number, "`serial_no`");
        }
        
		$order = "SELECT * FROM `".$o['order']."`$added_order WHERE `".$o['id']."` = $order_number $added_order_join;";
		
// 		echo $order;exit;
		$order_result = qdb($order);
    
		$oi = array();
		if (mysqli_num_rows($order_result) > 0){
			$oi = mysqli_fetch_assoc($order_result);
		}

		$freight_services = ($oi['freight_services_id'])? ' '.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';
		$freight_terms = ($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';

		$items = "SELECT * FROM ".$o['item']." WHERE `".$o['item_id']."` = $order_number;";
		//Make a call here to grab RMA's items instead
		
		//And sort through serials instead of PO_orders
		
		$items_results = qdb($items) or die (qe()." | ".$items);
        
        //Process Item results of the credit to associate the serials into a nested array
		$item_rows = '';
        $i = 0;
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
			$part_details = current(hecidb($item['partid'],'id'));
			$part_strs = explode(' ',$part_details['Part']);
			$lineTotal = $price*$item['qty'];
			$total += $lineTotal;
			
			//FREIGHT CALCULATION HERE FOR INVOICE (based off the payment type/shipping account)
			$item_rows .= '
                <tr>
                    <td class="text-center">'.(($o['credit'] || $o['rma']) ? ++$i : $item['line_number']).'</td>
                    <td>
        	            '.$part_strs[0].' &nbsp; '.$part_details['HECI'].'
                        <div class="description '.$part_details['manf'].' '.$part_details['system'].' '.$part_details['description'].'</div>
                        <div class="'.($o['rma'] || $o['invoice'] ? '' : 'remove').'" style = "padding-left:5em;">
                            <br>
                            <ul>';
                                if ($serials && !$o['credit']){
                                    //Add Serials label
                                    foreach($serials as $serial){
                                        $item_rows .= "<li>".$serial['serial_no']."</li>";       
                                    }
                                }
                            $item_rows .='</ul>
                        </div>
                    </td>
                    
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
                    <td class="text-center"><input class="qty-input" data-price="'.$price.'" value="'.$item['qty'].'" style="border: 0; text-align: center; background: transparent;"></td>
                    <td class="text-right">'.format_price($price).'</td>
                    <td class="text-right extended-cost"><span class="lineTotal" data-extprice="'.$lineTotal.'">'.format_price($lineTotal).'</span> <input type="checkbox" class="row-check no-print"></td>
				</tr>
			';
		}

		return $item_rows;
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

?>

<!DOCTYPE html>
<html>
    <head>
		<title></title>
		<link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet"> 
        <style type="text/css">
            body{
                font-size:11px;
                -webkit-print-color-adjust: exact;

            }
			body, table, td, th {
                font-family: "Lato", sans-serif;
                font-weight: normal !important;
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
            #footer{
                display:none;
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
				font-weight: normal !important;
            }
			#ps_bold h3 {
				padding-top:0px;
				margin-top:0px;
			}
            #letter_head{
				margin-bottom:40px;
                font-size:9pt;
            }
            #footer{
                display:block;
                /*position:absolute;
                bottom:60px;*/
                text-align:left;
                width:100%;
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

            @media print
			{    
			    .no-print, .no-print *
			    {
			        display: none !important;
			    }
			}
        </style>
    </head>
    <body>

        <div id = "ps_bold">
            <h3>Proforma Invoice #<?=$order_number;?></h3>
            <table class="table-full" id = "vendor_add">
				<tr>
					<th class="text-center">CUSTOMER</th>
				</tr>
				<tr>
					<td class="half">
                        <?=(getContact($oi['contactid']) ? getContact($oi['contactid']) . '<br>' : "");?>
						<?=(address_out($oi["bill_to_id"]) ? address_out($oi["bill_to_id"]) : address_out($oi["remit_to_id"]))?>
					</td>
				</tr>
			</table>
        </div>

        <div id = "letter_head">
            <img src="img/logo.png" style="width:1in;"></img><br>
            Ventura Telephone, LLC <br>
            3037 Golf Course Drive <br>
            Unit 2 <br>
            Ventura, CA 93003<br>
            (805) 212-4959
            
        </div>

        <!-- Shipping info -->
        <!-- <h2 class="text-center credit_memo '.($o['rma'] ? '' : 'remove').'">THIS IS NOT A CREDIT MEMO</h2> -->
        <table class="table-full">
            <tr>
                <th>Bill To</th>
                <th>Ship To</th>    
            </tr>
            <tr>
                <td>
				    <?=address_out($oi['bill_to_id'], 'street');?>
				</td>

                <td class="half">
                    <?=address_out($oi['ship_to_id']);?>
                </td>
            </tr>
        </table>

<?php 
	$rep_name = getContact($oi['sales_rep_id']);
	$rep_phone = getContact($oi['sales_rep_id'],'id','phone');
	$rep_email = getContact($oi['sales_rep_id'],'id','email');

	$contact_name = getContact($oi['contactid']);
	$contact_phone = getContact($oi['contactid'],'id','phone');
	$contact_email = getContact($oi['contactid'],'id','email');
?>


        <!-- Freight Carrier -->
        <table class="table-full" id="order-info">
            <tr>
                <th>Sales Rep</th>
                <th>Invoice Date</th>
                <th>Payment Due Date </th>
                <th>SO #</th>
                <th>Terms</th>
                <th>Shipping</th>
                <th>Freight Terms</th>
                <th>PO #</th>
            </tr>
            <tr>
                <td>
                    <?=$rep_name;?> <br>
                    <?=(($rep_phone)?$rep_phone."<br>" : "");?>
                    <?=$rep_email;?>
                </td>
				<td class="text-center">
					<?=format_date($oi['created'],'F j, Y');?>
				</td>

				<td class="text-center">
					<?=(format_date($oi['created'],'F j, Y',array("d" => $oi['days'])));?>
				</td>

                <td>
                	<?=$oi['so_number'];?>
            	</td>
                
				<td>
                    <?=display_terms($oi['termsid']);?>
                </td>

                <td>
                	<?=getFreight('carrier',$oi['freight_carrier_id'],'','name');?>
					<?=($oi['freight_services_id'])? ' '.strtoupper(getFreight('services','',$oi['freight_services_id'],'method')): '';?>
				</td>

                <td>
                	<?=($oi["freight_account_id"])?getFreight('account','',$oi['freight_account_id'],'account_no') : 'Prepaid';;?>
            	</td>

                <td>
                	<?=$oi['cust_ref'];?>
            	</td>
            </tr>
        </table>

<!-- Items Table -->
        <table class="table-full table-striped table-condensed">
            <tr>
                <th>Ln#</th>
                <th>Description</th>
                <th>Warranty</th>
                <th>Qty</th>
                <th>Price</th>
                <th>EXT Price</th>
            </tr>
            
			<!-- '.$item_rows.' -->
			<?= $item_rows; ?>
		</table>

        <table class="table-full">
            <!-- Subtotal -->
            <tr>
                <td style="text-align:right;border:none;">Subtotal</td>
                <td class="text-price subtotal-price" data-subprice="<?=$total;?>">
                    <?=format_price($total);?>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;border:none;">Freight</td>
                <td class="text-price">
                    <?=format_price(0);?>
                </td>
            </tr>
            <tr>
                <td style="text-align:right;border:none;">Tax 0.00%</td>
                <td class="text-price">
                    <?=format_price(0);?>
                </td>
            </tr>
            <tr class="total">
                <td style="text-align:right;">Total</td>
                <td id = "total" class="text-price total-price">
                    <?=format_price($total);?>
                </td>
            </tr>
        </table>

<!-- <table class="table-full table-striped table-condensed">
                    <tr>
                        <th>Package #</th>
                       <th>Tracking</th>
                    </tr>
                    .$package_list.
                </table> -->

		<div id="footer">
            <p>
                Terms and Conditions:<br><br>
                Acceptance: Accept this order only in accordance with the prices, terms, delivery method and specifications
                listed herein. Shipment of goods or execution of services against this PO specifies agreement with our
                terms.<br><br>
                Invoicing: VenTel requires that vendors provide ONE invoice per purchase order. Items on the invoice must
                match items on the purchase order. Due date for payment terms begins when the order is received
                complete. Failure to abide by these terms may result in delayed payment at no fault by the purchaser.
                Please communicate all questions regarding these conditions within 15 days.
            </p>
        </div>

        <?php include_once 'inc/footer.php';?>
        <script type="text/javascript">
        	function formatCurrency(total) {
			    var neg = false;
			    if(total < 0) {
			        neg = true;
			        total = Math.abs(total);
			    }
			    return (neg ? "-$" : '$') + parseFloat(total, 10).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, "$1,").toString();
			}

	    	(function($){
	    		$(document).on("click", ".row-check", function(){
	    			//alert($(".subtotal-price").attr("data-subprice"));
	    			//Using .attr as .data loads the cached value and not the changed values
	    			if($(this).prop("checked")){
	    				var removedCost = $(this).siblings(".lineTotal").data("extprice");
	    				$(this).closest("tr").addClass("no-print");
	    				$(this).closest("tr").css("opacity", "0.3");
	    				$(".subtotal-price").text(formatCurrency($(".subtotal-price").attr("data-subprice") - removedCost));
	    				$(".subtotal-price").attr("data-subprice", ($(".subtotal-price").attr("data-subprice") - removedCost));
	    			} else {
	    				var removedCost = $(this).siblings(".lineTotal").data("extprice");
	    				$(this).closest("tr").removeClass("no-print");
	    				$(this).closest("tr").css("opacity", "1");
	    				$(".subtotal-price").text(formatCurrency(Number($(".subtotal-price").attr("data-subprice")) + Number(removedCost)));
	    				$(".subtotal-price").attr("data-subprice", (Number($(".subtotal-price").attr("data-subprice")) + Number(removedCost)));
	    			}

	    			$(".total-price").text(formatCurrency($(".subtotal-price").attr("data-subprice")));
	    			//alert($(".subtotal-price").data("subprice"));
	    			
	    		});

	    		$(document).on("change", ".qty-input", function(){
	    			var subtotal = 0;

	    			$(this).closest("tr").find(".lineTotal").text(formatCurrency($(this).data("price") * $(this).val()));
	    			$(this).closest("tr").find(".lineTotal").attr("data-extprice", $(this).data("price") * $(this).val());
	    			$(".lineTotal" ).each(function() {
						subtotal += $(this).data("extprice");
					});

					$(".subtotal-price").text(formatCurrency(subtotal)).attr("data-subprice", subtotal);
					$(".total-price").text(formatCurrency(subtotal));

	    		});
	    	})(jQuery);
	    </script>

    </body>
</html>