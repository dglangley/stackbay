<?php

//=============================================================================
//======================== Order Form General Template ========================
//=============================================================================
//  This is the general output form for the sales and purchase order forms.   |
//	This will be designed to cover all general use cases for shipping forms,  |
//  so generality will be crucial. Each of the sections is to be modularized  |
//	for the sake of general accessiblilty and practicality.					  |
//																			  |
//	Aaron Morefield - October 18th, 2016									  |
//=============================================================================

	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = $_REQUEST['on'];
	$order_type = 'build';
	
	$build_order = array();
	$notes;
	$name;
	$partid;
	$ro_number;
	$status;

	function getBuildOrder($order_number) {
		$buildData = array();

		$query = "SELECT * FROM builds WHERE id = ".prep($order_number).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			$buildData = $row;
		}

		return $buildData;
	}

	if($order_number) {
		$build_order = getBuildOrder($order_number);

		$name = $build_order['name'];
		$partid = $build_order['partid'];
		$ro_number = $build_order['ro_number'];
		$status = $build_order['status'];
		$build_num = $build_order['id'];

	} else {
		$order_number = 'New';
	}

	// print_r($build_order);
?>
	

<!DOCTYPE html>
<html>
	<head>
		<title>Build<?=($order_number != 'New' ? '# ' . $order_number : '')?></title>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		
		<style type="text/css">
			.table td {
				vertical-align: top !important;
				/*padding-top: 10px !important;*/
				/*padding-bottom: 0px !important;*/
			}
			
			.btn-secondary {
			    color: #292b2c;
			    background-color: #fff;
			    border-color: #ccc;
			}

			.infiniteSerials .btn-secondary {
				/*color: #373a3c;*/
				background-color: transparent;
				border: 0;
				padding: 0;
				line-height: 0;
			}
			
			.table .order-complete td {
				background-color: #efefef !important;
			}
			
			.infiniteSerials .input-group, .infiniteBox select {
				margin-bottom: 10px;
			}
			
			table.num {
			    counter-reset: rowNumber;
			}
			
			table.num tr > td:first-child {
			    counter-increment: rowNumber;
			}
			
			table.num tr td:first-child::before {
			    content: counter(rowNumber);
			    min-width: 1em;
			    margin-right: 0.5em;
			}
			
			table tr.nested_table td:first-child::before {
			    content: '';
			    min-width: 0em;
			    margin-right: 0em;
			}
			
			.infiniteISO .checkbox {
				margin-top: 5px;
				margin-bottom: 20px;
			}
			
			.btn:active, .btn.active {
				outline: 0;
				background-image: none;
				-webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.25);
				box-shadow: inset 0 3px 5px rgba(0, 0, 0, .25);
			}
			
			.order-exchange td {
				background-color: #f5fafc !important;
			}
			
			.master-package {
				font-weight:bold;
			}

			table td {
				border: 0 !important;
				background: transparent !important;
			}

			/*tfoot > tr > td {
			    padding: 0px 5px !important;
			}*/
		</style>

	</head>

	<body class="sub-nav" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
		<?php 
			include 'inc/navbar.php'; 
		?>
		<form id="build_form" action="build_add.php" method="post">
			<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color: #b9bfd8">
				<div class="col-md-4">
				 	<a href="/repair_add.php?on=<?=$order_number . '&build=true';?>" class="btn-flat pull-left"><i class="fa fa-truck"></i></a>
					<?php if(in_array("3", $USER_ROLES) || in_array("1", $USER_ROLES)) { ?>
						<a href="/repair.php?build=true&on=<?php echo $build_num; ?>&ps=bo" class="btn-flat pull-left" style="margin-top: 10px;"><i class="fa fa-wrench" aria-hidden="true"></i></a>
					<?php } ?>
				</div>
				
				<div class="col-md-4 text-center">
					<?php
						echo"<h2 class='minimal shipping_header' style='padding-top: 10px;' data-so='". $order_number ."'>";
							if ($order_number=='New'){
								echo "New ";
							}
							echo 'Build';
							if ($order_number!='New'){
								echo "# $order_number";
							}
						// if (strtolower($status) == 'void'){
						// 	echo ("<b><span style='color:red;'> [VOIDED]</span></b>");
						// }
						echo"</h2>";
					?>
				</div>
				<div class="col-md-4">
				<?php if($order_number=='New'): ?>
					<button class="btn-flat success pull-right" <?=($order_number=='New' ? '' : 'name="build_id" value="'.$order_number.'"')?> style="margin-top: 10px; margin-right: 10px;"><?=($order_number=='New' ? 'Create' : 'Edit')?></button>
				<?php endif; ?>
				</div>
			</div>

			<div class="loading_element">
				<div class="row remove-margin">

					<div class="col-sm-4" style="padding-top: 20px">
						<input class="form-control input-sm" type="text" name="name" value="<?=$name;?>" placeholder="Name...">
					</div>
					<div class="col-sm-6" style="padding-top: 15px">
						<div class="table-responsive">
	                        <table class="table table-hover table-striped table-condensed" id="items_table">

	                            <tbody id="right_side_main" class="table_components" style = "font-size:13px;">
	                            </tbody>
	                            <tfoot id = "search_input">                                             
	                                <tr id ='search_row'>
	                                    <td id = 'search' class='col-md-10'>
	                                        <div class='input-group'>
	                                            <input type='text' class='form-control input-sm' id = 'go_find_me' placeholder='SEARCH FOR ITEM...'>
	                                            <span class='input-group-btn'>
	                                                <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
	                                            </span>
	                                        </div>
	                                    </td>
	                                    <td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_qty' id = 'new_item_qty' placeholder='QTY' value = ''></td>
	                                    <!-- <td></td> -->
	                                    <!-- <td colspan="2" id="check_collumn"> 
	                                        <a class="btn-sm btn-flat success pull-right multipart_sub">
	                                        <i class="fa fa-save fa-4" aria-hidden="true"></i></a>
	                                    </td> -->
	                                </tr>
	                                <!-- Adding load bar feature here -->
	                                <tr class='search_loading'><td colspan='12'><span style='text-align:center; display: none; padding-top: 10px;'>Loading...</span></td></tr>
	                        
	                                <!-- dummy line for nothing found -->
	                                <tr class='nothing_found' style='display: none;'><td colspan='12'><span style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Nothing Found</span></td></tr>
	                            </tfoot>
	                        </table>
	                    </div>
					</div>
					<div class="col-sm-2" style="padding-top: 20px">
	                	<select class="form-control input-sm" name="status" <?=($order_number!='New' ? '' : 'style="display: none;"')?>>
	                		<option value="Active" <?=($status == 'Active' || !$status ? 'selected' : '');?>>Active</option>
	                		<option value="Completed" <?=($status == 'Completed' ? 'selected' : '');?>>Completed</option>
	                	</select>
	                </div>

	                <input class="form-control hidden" type="text" name="ro_number" value="<?=$ro_number;?>">
				<!--End Row-->
				</div>
			<!--End Loading Element-->
			</div>

		</form>
		
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		<script>
			(function($){
				$('#item-updated-timer').delay(1000).fadeOut('fast');

				$('form#build_form').find('input').keypress(function(e){
				    if ( e.which == 13 ) // Enter key = keycode 13
				    {
				        //$(this).next().focus();  
				        return false;
				    }
				});

				$(document).on("keydown",".search_line_qty",function(e){
					if (e.keyCode == 13) {
						var isValid = nonFormCase($(this), e);
						
						$(".items_label").html("").remove();
						if(isValid) {
							var qty = 0;
							console.log($(".search_lines"));
	   		    			$(".search_lines").each(function() {
								qty += populateSearchResults($(".multipart_sub"),$(this).attr("data-line-id"),$(this).find("input[name=ni_qty]").val(), $(this).find('.data_stock').data('stock'));
							});
							$(".items_label").html("").remove();
							
							if (qty == 0){
								modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Qty is missing or invalid. <br><br>If this message appears to be in error, please contact an Admin.");
							} else {
								$(".search_lines").html("").remove();
								$("#totals_row").show();
								$(this).val("");
								$("input[name='ni_qty']").val("");
								$("#order_total").val(updateTotal());
								$('#go_find_me').focus();
							}
						} 
					}
				});
			})(jQuery);
		</script>
	</body>
</html>
