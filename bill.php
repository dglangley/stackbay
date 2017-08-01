<?php

//==============================================================================
//============================ Bill General Template ===========================
//==============================================================================
//  Description to come when I have a better sense of all the functions this   |
//	will cover.                                                                |
//                                                                             |
//	                                                        				   |
//																			   |
//	Aaron Morefield - February 28nd, 2017               					   |
//==============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/item_history.php';
	include_once $rootdir.'/inc/operations_sidebar.php'; 
	include_once $rootdir.'/inc/order_parameters.php'; 
	include_once $rootdir.'/inc/terms.php';
	
	//Declarations
	$mode = '';
	$po_number = '';
	$grouped = array();
	

	//Helper functions
	function getSerial($invid) {
		$serial = "";
		
		$query = "SELECT serial_no FROM inventory WHERE id = ". prep($invid) .";";
		$result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$serial = $result['serial_no'];
		}
		
		return $serial;
	}
	
	function outstanding_amount($order, $type){
		//Calculates the amount outstanding for an SO/PO
		$o = o_params($type);
		$order = prep($order);
		$money = array(
			"paid" => 0.00,
			"order_total" => 0.00,
			"remaining" => 0.00
			);
		if($o['purchase']){
			//This grabs all the payments made on a purchase order
			$recorded = "
			SELECT SUM(`amount`) paid FROM payment_details
			WHERE order_type = 'Bill'
			AND order_number in (SELECT `bill_no` FROM `bills` where po_number = $order);";
			
			$paid_results = qdb($recorded) or die(qe()." | $recorded");
			if(mysqli_num_rows($paid_results)){
				$paid_item = mysqli_fetch_assoc($paid_results);
				$money['paid'] = $paid_item['paid'];
			}
			
			$total = "SELECT SUM(`qty` * `price`) total FROM purchase_items where po_number = $order;";
			$total_results = qdb($total) or die(qe()." | $total");
			if(mysqli_num_rows($total_results)){
				$total_item = mysqli_fetch_assoc($total_results);
				$money['order_total'] = $total_item['total'];
			}
		}
		$money['remaining'] = $money['order_total'] - $money['paid'];
		return($money);
	}
	
	//Determine Mode AND if it was referred from a pre-existing Sales Order
	$mode = grab("mode");
	$po_number = grab("on");
	$bill_number = grab("bill");
	$sidebar_mode ='bill';
	$associated_order = '';
	$subtotal = 0.00;
	
	if(strtolower($bill_number) == 'new' || (is_numeric($bill_number))&&$mode=="edit"){
		//Get all the information from the previously purchased items with associated inventory
		$po_select = "
		SELECT pi.line_number ln, pi.partid, pi.qty, serial_no, pi.id purchid, pi.warranty, price, i.id
		FROM `purchase_items` pi
		LEFT JOIN `inventory` i on pi.`id` = i.`purchase_item_id`
		WHERE pi.po_number = ".prep($po_number)."
		AND (i.id NOT IN (
			SELECT inventoryid FROM `bill_items` bi, `bills` b, `bill_shipments` bs
			WHERE b.po_number = ".prep($po_number)."
			AND b.bill_no = bi.bill_no
			AND bi.id = bs.bill_item_id
			)
			OR i.id IS NULL
		)
		Order by ln
		;";
		// echo($po_select);
		$po_results = qdb($po_select) OR die(qe().": $po_select");
		// echo("<pre>");
		foreach($po_results as $row){
			$grouped[$row['purchid']]['partid'] = $row['partid'];
            $grouped[$row['purchid']]['qty'] = $row['qty'];
            $grouped[$row['purchid']]['purchid'] = $row['purchid'];
            $grouped[$row['purchid']]['warranty'] = $row['warranty'];
            $grouped[$row['purchid']]['price'] = $row['price'];
            // $grouped[$row['purchid']]['id'] = $row['id'];
			$grouped[$row['purchid']]['serials'][$row['id']] = $row['serial_no'];
			$grouped[$row['purchid']]['ln'] = $row['ln'];
			$subtotal += $row['qty'] * $row['amount'];
			
		}
		// print_r($grouped);exit;
		$due_estimate = terms_calc($po_number,"po");

		// $due_select = "
		// SELECT created, days FROM purchase_orders, terms WHERE termsid = terms.id and po_number = ".prep($po_number).";";
		// $due_estimate_result = qdb($due_select) or die(qe()." | $due_select");
		// $due_estimate_arr = mysqli_fetch_assoc($due_estimate_result);
		// $due_estimate = format_date($due_estimate_arr['created'], "n/j/Y", array("d"=>$due_estimate_arr['days']));
		
		// $billable = "SELECT SUM(`amount` * `qty`) FROM `bills` b, `bill_items` bi where b.bill_no = bi.bill_no AND po_number = ".prep($po_number).";";
	}	
	
	if(is_numeric($bill_number)){
		$bill_select = "
		SELECT *, bill_items.id as bill_id
		FROM bills, bill_items 
		LEFT JOIN bill_shipments on bill_items.id = bill_item_id
		WHERE bills.bill_no = ".prep($bill_number)." 
		AND bills.bill_no = bill_items.bill_no
		;";
		$rows = qdb($bill_select) or die(qe().": ".$bill_select);
		foreach($rows as $row){
			$po_number = $row['po_number'];
			$associated_order = $row['invoice_no'];
			$due_date = format_date($row['due_date'],"n/j/Y");
			$grouped[$row['bill_id']]['partid'] = $row['partid'];
            $grouped[$row['bill_id']]['qty'] = $row['qty'];
            $grouped[$row['bill_id']]['purchid'] = $po_number;
            $grouped[$row['bill_id']]['warranty'] = $row['warranty'];
            $grouped[$row['bill_id']]['price'] = $row['amount'];
            // $grouped[$row['bill_id']]['id'] = $row['id'];
			$grouped[$row['bill_id']]['serials'][$row['inventoryid']] = getSerial($row['inventoryid']);
			$subtotal += $row['qty'] * $row['amount'];
		}
		// echo("<pre>");
		// print_r($grouped);
		// echo("</pre>");
		// exit;
	} 
?>






<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title>Bill <?=(($bill_number == "new")?:"#$bill_number")?></title>
		<style type="text/css">
			.serial_box{
				padding-bottom:10px;
			}
			.reason-col, .serials-col, .qty-col,.disp-col,.history-col,.warranty-col{
				padding-top:10px !important;
			}
			.infinite{
				margin-bottom:10px;
			}
			.history-col{
				font-size:8pt;
			}

		</style>
	</head>
	<!---->
	<body class="sub-nav forms" id = "rma-body" data-bill-number="<?=$bill_number?>" data-associated="<?=$po_number?>">
		<?php include 'inc/navbar.php'; ?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:#f0f4ff;">
				
				<div class="col-md-4">
					<?php
	                    // Add in the following to link to the appropriate page | href="/'.$url.'.php?on=' . $order_number . '" | href="/docs/'.$order_type[0].'O'.$order_number.'.pdf"
						echo '<a class="btn-flat pull-left" href="/PO'.$po_number.'"><i class="fa fa-truck"></i> PO #'.$po_number.'</a>';
						// if($bill_number){
						// 	echo '<a class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
						// 	echo '<a class="btn-flat pull-left" href="/rma_add.php?on='.$bill_number.'">Receive</a>';
						// }
					?>
					
				</div>
				<div class="col-md-4 text-center"><h2 class = "minimal" style ="margin-top:10px;;">
				    <?=(is_numeric($bill_number))? "Bill #$bill_number" :'New Bill for PO #'.$po_number;?>
				    </h2>
				</div>
				
				<div class="col-md-4">
					<?php if(strtolower($bill_number)=="new" || $mode == "edit"){?>
					<button class="btn-flat btn-sm  <?=($order_number=="New")?'success':'success'?> pull-right" id = "bill_save_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;">
						<?=(strtolower($bill_number)=="new") ? 'Create' :'Update'?>
					</button>
					<?php } ?>
				</div>
			</div>
		<div class="row remove-margin">
				<!--================== Begin Left Half ===================-->
			<div class="left-side-main col-md-3 col-lg-2" data-page="order" style="height:100%;background-color:#efefef;padding-top:15px;">
					<!--'RMA'/$rma_number OR 'Sales'/$po_number-->
					<?=sidebar_out($bill_number,"purchase",$sidebar_mode)?>
				</div>
			
				<!--======================= End Left half ======================-->
			
		
				<!--===================== Begin Right half =====================-->
			<div class="col-sm-10 shipping-list" style="padding-top: 20px">
					<div class="table-responsive">
						<table  class = "table table-hover table-striped table-condensed" style="margin-top: 15px;">
							<thead>
								<th style='min-width:30px;'>Ln&nbsp;#</th>
								<th class = "col-md-1">Part</th>
								<th class = "col-md-8"></th>
								<th class = "col-md-1">Warranty</th>
								<th class = "col-md-1">Qty</th>
								<th class = "col-md-1">Amount</th>
							</thead>
							<tbody>
							<?php
							$output = '';
							$total = 0.00;
							if(isset($grouped)){
								foreach($grouped as $meta => $li){
									$total += $li['price'] * $li['qty'];
									$output .= "
									<tr class ='meta-line meta-$meta' data-line-number='$meta' data-og-qty='".$li['qty']."' 
									data-partid='".$li['partid']."' data-warranty='".$li['warranty']." data-ln='".$li['ln']."'
									data-price='".$li['price']."'>
										<td>".$li['ln']."</td>
										<td colspan = '2'>".display_part(current(hecidb($li['partid'],'id')))."</td>
										<td>".getWarranty($li['warranty'],"warranty")."</td>
										<td>"."<input type='text' name='qty' class='bill-qty form-control input-sm' value ='".$li['qty']."' placeholder = 'Qty' ".(is_numeric($bill_number) && ! $mode=="edit" ?"readonly" : "")."/>"."</td>
										<td>".format_price($li['price'])."</td>
									</tr>
									";
									if(current($li['serials'])){
										$first = true;
										foreach ($li['serials'] as $inv => $serial){
											$output .= "<tr class='serial-$meta' data-line-number='$meta' data-inv-id = '$inv'>";
												$output .= "<td colspan = '2' style='text-align:right;'>".($first ? 'Serials:': '')."</td>";
												$output .= "<td colspan = '2'>".$serial."</td>";
												$output .= "<td>".(is_numeric($bill_number)?"&nbsp;":'<input type="checkbox" class = "serialCheckBox">')."</td>";
												$output .= "<td></td>";
											$output .= "</tr>";
											$first = false;
										}
										$output.="<tr style='border:0;opacity:0;'><td colspan='6'>&nbsp;</td></tr>";
									}
								}
							}
							if(strtolower($bill_number)=="new" || $mode == "edit"){
								$money = outstanding_amount($po_number,"po");
								$output .= "<tr class='amount_remaining'>";
								$output .= "<td colspan = '5' style='text-align:right;'>Outstanding Amount Due:</td>";
								$output .= "<td><input type='text'class='form-control input-sm' value='".format_price($money['total'])."' readonly/></td>";
								$output .= "</tr>";
							}
							$output .= "<tr class='total'>";
							$output .= "<td colspan = '5' style='text-align:right;'>Total:</td>";
							$output .= "<td><input type='text'class='form-control input-sm' value='".format_price($total)."' readonly/></td>";
							$output .= "</tr>";
								// $output .= "
								// 		<tr class ='new_line'>
								// 			<td>"."<input type='text' name='new_ln' class='form-control input-sm' placeholder = 'LN' ".(is_numeric($bill_number) && ! $mode=="edit" ?"readonly" : "")."/>"."</td>
								// 			<td colspan = '2'>".display_part(current(hecidb($li['partid'],'id')))."</td>
								// 			<td>".getWarranty($li['warranty'],"warranty")."</td>
								// 			<td>"."<input type='text' name='qty' class='bill-qty form-control input-sm' value ='".$li['qty']."' placeholder = 'Qty' ".(is_numeric($bill_number) && ! $mode=="edit" ?"readonly" : "")."/>"."</td>
								// 			<td>".format_price($li['price'])."</td>
								// 		</tr>
								// 		";
								// 		if(current($li['serials'])){
								// 			$first = true;
								// 			foreach ($li['serials'] as $inv => $serial){
								// 				$output .= "<tr class='serial-$meta' data-line-number='$meta' data-inv-id = '$inv'>";
								// 					$output .= "<td colspan = '2' style='text-align:right;'>".($first ? 'Serials:': '')."</td>";
								// 					$output .= "<td colspan = '2'>".$serial."</td>";
								// 					$output .= "<td>".(is_numeric($bill_number)?"&nbsp;":'<input type="checkbox" class = "serialCheckBox">')."</td>";
								// 					$output .= "<td></td>";
								// 				$output .= "</tr>";
								// 				$first = false;
								// 			}
								// 			$output.="<tr style='border:0;opacity:0;'><td colspan='6'>&nbsp;</td></tr>";
								// 		}
									
								echo($output);
							?>
							</tbody>
						</table>
					</div>
				</div>
			<!--====================== End Right half ======================-->
		</div>

	<?php include_once 'inc/footer.php';?>
	<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
	<script type="text/javascript">
	$(document).ready(function() {
	
		$(".serialCheckBox").click(function(){
			var ln = ($(this).closest("tr").data('line-number'));
			var qty = 0;
			$.each($(".serial-"+ln),function(){
				if($(this).find(".serialCheckBox").is(":checked")){
					qty++;
				}
			});
			if(qty == 0){
				qty = $(this).closest("table").find(".meta-"+ln).data("og-qty");
			}
			$(this).closest("table").find(".meta-"+ln).find(".bill-qty").val(qty);
		});
		$("#bill_save_button").click(function(){
			console.log("TRIGGERED");
			var meta_submission = [];
			var bill_no = $("body").data("bill-number");
			var line_sub = [];
			$(".meta-line").each(function(){
				var id = $(this).data("line-number");
				var ln = $(this).data("ln");
				var warranty = $(this).data("warranty");
				var price = $(this).data("price");
				var partid = $(this).data("partid");
				var qty = 0;
				
				var serial_submission = [];
				var serialized = false;
				$.each($(".serial-"+id),function(){
					if($(this).find(".serialCheckBox").is(":checked")){
						serialized = true;
						serial_submission.push($(this).closest("tr").data("inv-id"));
						qty++;
					}
				});
				if (!serialized){
					qty = $(this).find(".bill-qty").val();
				}
				var line = {
					'id' : id,
					'ln' : ln,
					'warranty' : warranty,
					'partid' : partid,
					'qty' : qty,
					'price':price,
					'serials' : serial_submission,
				}
				line_sub.push(line);
			});
			meta_submission = {
				'bill_no' : bill_no,
				'invoice_no' : $("#customer_invoice").val(),
				'due_date': $("#due_date").val(),
				'po_number':'<?=$po_number?>',
				'lines': line_sub
			};
			
			console.log(window.location.origin+"/json/bill_process.php?submission="+JSON.stringify(meta_submission));
			$.ajax({
				type: "POST",
				url: '/json/bill_process.php',
				data: {
					"submission": JSON.stringify(meta_submission)
				},
				dataType: 'json',
				success: function(result) {
					window.location = "/bill.php?bill="+result;
					console.log(result);
				},
				error: function(xhr, status, error) {
					alert(error+" | "+status+" | "+xhr);
					console.log("JSON Bill Save bill_process.php: Error");
				}
			});

		});
		var date = null;
		if(!(isNaN(parseInt('<?=$bill_number?>')))){
			var assoc = '<?=$associated_order?>';
				$('#customer_invoice').val(assoc)
				.prop('readonly', true);
			date = '<?=$due_date?>';
		}else{
			date = '<?=$due_estimate?>';
		}
		$('#due_date').val(date);
		
	});
	</script>

	</body>
</html>
