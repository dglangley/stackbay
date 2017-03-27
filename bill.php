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
	include_once $rootdir.'/inc/pipe.php';
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


	//Declarations
	$mode = '';
	$po_number = '';
	$grouped = array();
	
	//Determine Mode AND if it was referred from a pre-existing Sales Order
	$mode = grab("mode");
	$po_number = grab("on");
	$bill_number = grab("bill");
	$sidebar_mode ='bill';
	
	if(strtolower($bill_number) == 'new' || (is_numeric($bill_number)&&$mode=='edit')){
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
            $grouped[$row['purchid']]['id'] = $row['id'];
			$grouped[$row['purchid']]['serials'][] = $row['serial_no'];
			
		}
		// print_r($grouped);
	}
?>






<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title>Bill</title>
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
						echo '<a class="btn-flat pull-left" href="/shipping.php?on='.$po_number.'"><i class="fa fa-truck"></i> PO #'.$po_number.'</a>';
						// if($bill_no){
						// 	echo '<a class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
						// 	echo '<a class="btn-flat pull-left" href="/rma_add.php?on='.$bill_no.'">Receive</a>';
						// }
					?>
					
				</div>
				<div class="col-md-4 text-center"><h2 class = "minimal" style ="margin-top:10px;;">
				    <?=($bill_no)? "Bill #$bill_no" :'New Bill for PO #'.$po_number;?>
				    </h2>
				</div>
				
				<div class="col-md-4">
					<button class="btn-flat btn-sm  <?=($order_number=="New")?'success':'success'?> pull-right" id = "bill_dave_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;">
						<?=(strtolower($bill_number)=="new") ? 'Create' :'Save'?>
					</button>
				</div>
			</div>
		<div class="row remove-margin">
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-md-3 col-lg-2" data-page="order" style="height:100%;background-color:#efefef;padding-top:15px;">
					<!--'RMA'/$rma_number OR 'Sales'/$po_number-->
					<?=sidebar_out($po_number,"purchase",$sidebar_mode)?>
				</div>
			
				<!--======================= End Left half ======================-->
			
		
				<!--===================== Begin Right half =====================-->
				<div class="col-sm-10 shipping-list" style="padding-top: 20px">
					<div class="table-responsive">
						<table  class = "table table-hover table-striped table-condensed" style="margin-top: 15px;">
							<thead>
								<th class = "col-md-1">Ln #</th>
								<th class = "col-md-1">Part</th>
								<th class = "col-md-7"></th>
								<th class = "col-md-1">Warranty</th>
								<th class = "col-md-1">Qty</th>
								<th class = "col-md-1">Amount</th>
							</thead>
							<tbody>
							<?php
							$output = '';
								if(isset($grouped)){
									foreach($grouped as $meta => $li){
										$output .= "
										<tr class ='meta-$meta' data-line-number='$meta' data-og-qty='".$li['qty']."'>
											<td>".$li['ln']."</td>
											<td colspan = '2'>".display_part(current(hecidb($li['partid'],'id')))."</td>
											<td>".getWarranty($li['warranty'],"warranty")."</td>
											<td>"."<input type='text' name='qty' class='bill-qty form-control input-sm' value ='".$li['qty']."' placeholder = 'Qty'/>"."</td>
											<td>".format_price($li['price'])."</td>
										</tr>
										";
										if($li['serials'][0]){
											foreach ($li['serials'] as $i => $serial){
												$output .= "<tr class='serial-$meta' data-line-number='$meta'>";
													$output .= "<td colspan = '2' style='text-align:right;'>".($i==0 ? 'Serials:': '')."</td>";
													$output .= "<td colspan = '2'>".$serial."</td>";
													$output .= "<td>".'<input type="checkbox" class = "serialCheckBox">'."</td>";
												$output .= "</tr>";
											}
											$output.="<tr style='border:0;opacity:0;'><td colspan='6'>&nbsp;</td></tr>";
										}
									}
								}
								echo($output);
						
							?>
						</table>
						</tbody>
					</div>
				</div>
			<!--====================== End Right half ======================-->
			</div>

	<?php include_once 'inc/footer.php';?>
	<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
	<script type="text/javascript">
		$(".serialCheckBox").click(function(){
			var ln = ($(this).closest("tr").data('line-number'));
			var qty = 0;
			$.each($(".serial-"+ln),function(){
				if($(this).find(".serialCheckBox").is(":checked")){
					qty++;
				};
			});
			if(qty == 0){
				qty = $(this).closest("table").find(".meta-"+ln).data("og-qty");
			}
			$(this).closest("table").find(".meta-"+ln).find(".bill-qty").val(qty);
		});
	</script>

	</body>
</html>
