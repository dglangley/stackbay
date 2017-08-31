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
	include_once $rootdir.'/inc/order_parameters.php'; 
	include_once $rootdir.'/inc/terms.php';
	

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
	
	//Declarations
	$mode = '';
	$grouped = array();
	$invoice_no = '';
	$subtotal = 0.00;
	
	//Determine Mode AND if it was referred from a pre-existing Sales Order
	$mode = grab("mode");
	$order_number = grab("on");
	$bill_no = grab("bill");
	if (! trim($bill_no) OR ! is_numeric($bill_no)) { $bill_no = 0; }
	$companyid = 0;
	$notes = '';

	if ($mode=='edit' AND ! $bill_no) {
		die("Cannot continue without a valid Bill No.");
	}

	if ($bill_no) {
		$query = "SELECT * FROM bills WHERE bill_no = '".res($bill_no)."'; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		if (mysqli_num_rows($result)==0) {
			die("Bill No. $bill_no is not valid");
		}
		$bill = mysqli_fetch_assoc($result);
		$invoice_no = $bill['invoice_no'];
		$due_date = format_date($bill['due_date'],"n/j/Y");
		$notes = $bill['notes'];
		$order_number = $bill['po_number'];

		$query2 = "SELECT *, bill_items.id as bi_id FROM bill_items ";
		$query2 .= "LEFT JOIN bill_shipments on bill_items.id = bill_item_id ";
		$query2 .= "WHERE bill_items.bill_no = '".res($bill_no)."'; ";
		$result2 = qdb($query2) or die(qe().": ".$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$grouped[$r2['bi_id']]['partid'] = $r2['partid'];
			$grouped[$r2['bi_id']]['qty'] = $r2['qty'];
//			$grouped[$r2['bi_id']]['purchid'] = $order_number;
			$grouped[$r2['bi_id']]['warranty'] = $r2['warranty'];
			$grouped[$r2['bi_id']]['price'] = $r2['amount'];

			// inventoryid comes from bill_shipments
			$grouped[$r2['bi_id']]['serials'][$r2['inventoryid']] = getSerial($r2['inventoryid']);
			$subtotal += $r2['qty'] * $r2['amount'];
		}
	} 

	$query = "SELECT * FROM purchase_orders WHERE po_number = '".res($order_number)."'; ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$companyid = $r['companyid'];
	}
	$due_date = terms_calc($order_number,"po");

	//Get all the information from the previously purchased items with associated inventory
	if (! $bill_no) {
		$po_select = "
		SELECT pi.line_number ln, pi.partid, pi.qty, serial_no, pi.id purchid, pi.warranty, price, i.id
		FROM `purchase_items` pi
		LEFT JOIN `inventory` i on pi.`id` = i.`purchase_item_id`
		WHERE pi.po_number = ".prep($order_number)."
		AND (i.id NOT IN (
			SELECT inventoryid FROM `bill_items` bi, `bills` b, `bill_shipments` bs
			WHERE b.po_number = ".prep($order_number)."
			AND b.bill_no = bi.bill_no
			AND bi.id = bs.bill_item_id
			)
			OR i.id IS NULL
		)
		ORDER BY ln
		;";
		$po_results = qdb($po_select) OR die(qe().": $po_select");
		foreach($po_results as $row){
			$grouped[$row['purchid']]['partid'] = $row['partid'];
			$grouped[$row['purchid']]['qty'] = $row['qty'];
//			$grouped[$row['purchid']]['purchid'] = $row['purchid'];
			$grouped[$row['purchid']]['warranty'] = $row['warranty'];
			$grouped[$row['purchid']]['price'] = $row['price'];
			$grouped[$row['purchid']]['serials'][$row['id']] = $row['serial_no'];
			$grouped[$row['purchid']]['ln'] = $row['ln'];

			$subtotal += $row['qty'] * $row['amount'];
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title>Bill<?=($bill_no) ? '# '.$bill_no : '';?></title>
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
	<body class="sub-nav forms" id = "rma-body" data-bill-number="<?=$bill_no?>" data-associated="<?=$order_number?>">
		<?php include 'inc/navbar.php'; ?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;min-height:50px;background-color:#f0f4ff;">
				
				<div class="col-md-4">
				</div>
				<div class="col-md-4 text-center"><h2 class = "minimal" style ="margin-top:10px;;">
				    <?=($bill_no) ? "Bill #$bill_no" :'New Bill for PO #'.$order_number;?>
				    </h2>
				</div>
				
				<div class="col-md-4 text-right">
					<button class="btn btn-success btn-sm" id = "bill_save_button" data-validation="left-side-main">
						<?=(! $bill) ? 'Create' :'Update'?>
					</button>
				</div>
			</div>
		<div class="row remove-margin">
			<!--================== Begin Left Half ===================-->
			<div class="left-side-main col-md-3 col-lg-2" data-page="order" style="height:100%;background-color:#efefef;padding-top:15px;">
				<div class="row">
					<div class="col-sm-12" style="margin-bottom:10px">
						<h4 style="margin-top:10px"><?php echo strtoupper(getCompany($companyid)); ?></h4>
						<h3><?php echo 'PO '.$order_number; ?> <a href="/<?php echo 'PO'.$order_number; ?>"><i class="fa fa-arrow-right"></i></a></h3>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12" style="padding-bottom: 10px;">
						<label for="associated_invoice"><h4>Vendor Invoice #</h4></label>
						<input name = "associated_invoice" id="customer_invoice" class = "form-control" value="<?php echo $invoice_no; ?>">
					</div>
				</div>
				<div class="row">
					<div class="col-sm-12">
						<label for="due_date"><h4>Payment Due</h4></label>
						<div class="form-group">
							<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
								<input type="text" id="due_date" name="due_date" class="form-control" value="<?php echo $due_date; ?>">
								<span class="input-group-addon">
									<span class="fa fa-calendar"></span>
								</span>
							</div>
						</div>
					</div>
				</div>
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
									data-partid='".$li['partid']."' data-warranty='".$li['warranty']."' data-ln='".$li['ln']."'
									data-price='".$li['price']."'>
										<td>".$li['ln']."</td>
										<td colspan = '2'>".display_part(current(hecidb($li['partid'],'id')))."</td>
										<td>".getWarranty($li['warranty'],"warranty")."</td>
										<td>"."<input type='text' name='qty' class='bill-qty form-control input-sm' value ='".$li['qty']."' placeholder = 'Qty' ".(is_numeric($bill_no) && ! $mode=="edit" ?"readonly" : "")."/>"."</td>
										<td>".format_price($li['price'])."</td>
									</tr>
									";
									if(current($li['serials'])){
										$first = true;
										foreach ($li['serials'] as $inv => $serial){
											$output .= "<tr class='serial-$meta' data-line-number='$meta' data-inv-id = '$inv'>";
												$output .= "<td colspan = '2' style='text-align:right;'>".($first ? 'Serials:': '')."</td>";
												$output .= "<td colspan = '2'>".$serial."</td>";
												$output .= "<td>".(is_numeric($bill_no)?"&nbsp;":'<input type="checkbox" class = "serialCheckBox">')."</td>";
												$output .= "<td></td>";
											$output .= "</tr>";
											$first = false;
										}
										$output.="<tr style='border:0;opacity:0;'><td colspan='6'>&nbsp;</td></tr>";
									}
								}
							}
							if(strtolower($bill_no)=="new" || $mode == "edit"){
								$money = outstanding_amount($order_number,"po");
								$output .= "<tr class='amount_remaining'>";
								$output .= "<td colspan = '5' style='text-align:right;'>Outstanding Amount Due:</td>";
								$output .= "<td><input type='text'class='form-control input-sm' value='".format_price($money['total'])."' readonly/></td>";
								$output .= "</tr>";
							}
							$output .= "<tr class='total'>";
							$output .= "<td colspan = '5' style='text-align:right;'>Total:</td>";
							$output .= "<td><input type='text'class='form-control input-sm' value='".format_price($total)."' readonly/></td>";
							$output .= "</tr>";
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
				'po_number':'<?=$order_number?>',
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
		if(!(isNaN(parseInt('<?=$bill_no?>')))){
			var assoc = '<?=$invoice_no?>';
//			$('#customer_invoice').val(assoc).prop('readonly', true);
			date = '<?=$due_date?>';
		}else{
			date = '<?=$due_date?>';
		}
		$('#due_date').val(date);
		
	});
	</script>

	</body>
</html>
