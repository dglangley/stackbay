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
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/order_parameters.php';
	
	//use this variable when RTV is used to grab all the checked items from the last post
	$rtv_items = array();
	$rtv_array = array();
	
	//General Variable Declarations
	$origin = '';
	$order_short;
	$o = array();
	
	
	//High level order parameters
	$o = o_params(grab('ps',"s"));
	$order_number = grab('on','New');
	$status = getOrderStatus($o['type'],$order_number);
	
	
	 if(strtolower($o['type']) == 'rtv'){
		$origin = $order_number;
		$order_number = "New";
		//If there are items to be Returned to Vendor, we gather the items in through a passed JSON parameter
		$rtv_items = $_REQUEST['partid'];
	}
	
	if(!in_array("3", $USER_ROLES) && !in_array("1", $USER_ROLES)) {
		if ($order_number!='New' && $o['type'] == "Purchase"){
			header('Location: /inventory_add.php?on='.$order_number);
			exit;
		} else if ($order_number!='New') {
			header('Location: /shipping.php?on='.$order_number);
			exit;
		} else {
			header('Location: /operations.php');
			exit;
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
		<title><?=($order_number != 'New')? (strtoupper($o['short'])." #".$order_number) : ('New '.strtoupper($o['short']) )?></title>

	</head>
	<!---->
	<body class="sub-nav forms" id = "order_body" data-order-type="<?=$o['type']?>" data-order-number="<?=$order_number?>">
		<div class="pad-wrapper">

			<?php 
				include 'inc/navbar.php';
				include_once $rootdir.'/modal/address.php';
				include_once $rootdir.'/modal/accounts.php';
				include_once $rootdir.'/modal/alert.php';
				include_once $rootdir.'/modal/contact.php';
			?>
			<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:<?=$o['color']?>;">
				
				<div class="col-md-4">
					<?php
					if($order_number != "New"){
						echo '<a href="/'.$o['url'].'.php?on=' . $order_number . '" class="btn-flat pull-left"><i class="fa fa-truck"></i></a> ';
						echo '<a href="/docs/'.strtoupper($o['short']).$order_number.'.pdf" class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
					}
					?>
					
				</div>
				
				<div class="col-md-4 text-center">
					<?php
					echo"<h2 class='minimal' style='margin-top: 10px;'>";
					if ($order_number=='New'){
						echo "$order_number ";
					}
					echo $o['type']." Order";
					if ($order_number!='New'){
						echo " #$order_number";
					}
					if (strtolower($status) == 'void'){
						echo ("<b><span style='color:red;'> [VOIDED]</span></b>");
					}
					echo"</h2>"
					?>
				</div>
				<div class="col-md-4">
					<button class="btn-flat btn-sm  <?=($order_number=="New")?'success':'success'?> pull-right" id = "save_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;">
						<?=($order_number=="New") ? 'Create' :'Save'?>
					</button>
				</div>
			</div>
			
			
			<!-- Row declaration for splitting the two "halves of the page  -->
			<div class="row remove-margin">
			
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-md-3 col-lg-2" data-page="order">
					<?php $order_number = ($origin)? $origin: $order_number;?>
					<!-- Everything here is put out by the order creation ajax script -->
					<?=sidebar_out($order_number,$o['type'])?>
				</div>
				<!--======================= End Left half ======================-->
			
		
				<!--===================== Begin Right half =====================-->
				<div class="col-md-9 col-lg-10">
					
					<!-- Output the sales-rep dropdown in the top right corner -->
					<div class="forms_section">
						<div style="float:right;padding-top:15px;">
							<div class="ui-select" style="width:125px; 'margin-bottom:0;">
			                    <select id="sales-rep" data-creator = <?=$U['contactid']?>>
	
			                        <?php
			                        	//REP OUTPUT
										$get_reps = "SELECT users.id userid, contacts.name name, contacts.id contactid FROM users, contacts ";
										$get_reps .= "WHERE users.contactid = contacts.id; ";
										
			                        		$old_rep = "Select `sales_rep_id` from ".$o['table']." WHERE `".$o['id']."` = $order_number";
			                        		$rep_res = qdb($old_rep);
										if ($order_number != 'New'){
			                        		$rep_row = mysqli_fetch_assoc($rep_res);
			                        		$set_rep = $rep_row['sales_rep_id'];
			                        		// echo("<option>$old_rep</option>");
			                        	}
			                        	
			                        	$all_reps = qdb($get_reps);
			                        	foreach ($all_reps as $rep) {
			                        		//If it is a new order, set the default to the current user
			                        		if($order_number == 'New'){
			                        			if($rep['contactid'] == $U['contactid']){
			                        				echo("<option selected data-rep-id='".$rep['contactid']."'>".$rep['name']."</option>");
			                        			}
			                        			else{
			                        				echo("<option data-rep-id='".$rep['contactid']."'>".$rep['name']."</option>");
			                        			}
			                        		}
			                        		else{
				                        		if($rep['contactid'] == $set_rep){
				                        			echo("<option selected data-rep-id='".$rep['contactid']."'>".$rep['name']."</option>");
				                        		}
				                        		else{
													echo("<option data-rep-id='".$rep['contactid']."'>".$rep['name']."</option>");
				                        		}
			                        		}
			                        	}
			                       
			                        ?>
			                    </select>
			                </div>
						</div>
					</div> 
					
					<div class="table-responsive">
						<table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
						<thead>
							
		    				<th style='min-width:30px;'>#</th>		
		    				<th class='col-md-5'>Item Information</th>
		    				<th class='col-md-2'>Delivery Date</th>
		    				<th class='col-md-1'>
			    				<?php
			    					$rootdir = $_SERVER['ROOT_DIR'];
			    					include_once($rootdir.'/inc/dropPop.php');
			    					echo(dropdown("conditionid","","full_drop","",false,"condition_global"));
			    				?>
		    				</th>
		    				<th class='col-md-1'>
		    					<?php
			    					$rootdir = $_SERVER['ROOT_DIR'];
			    					include_once($rootdir.'/inc/dropPop.php');
			    					echo(dropdown("warranty","","full_drop","",false,"warranty_global"));
		    					?>
	    					</th>
	    					<th class='col-md-1'>Qty</th>
		    				<th class='col-md-1'>Price</th>
	    					<th class='col-md-1'>Ext. Price</th>
	    					<th></th>
	    					<th></th>
	    				</thead>
	
			        	<tbody id="right_side_main" <?=($o['type'] == 'RTV' ? 'data-rtvarray = '. json_encode($rtv_items) : '');?> style = "font-size:13px;">
			        	</tbody>
				        
				        <?php if($o['type'] != 'RTV') { ?>
							<tfoot id = "search_input">
					            <tr id = 'totals_row' style='display:none;'>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td style='text-align:right;'>Total:</td>
					                <td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' id ='total' name='np_total' placeholder='0.00'></td>
					                <td></td>
					            </tr>
					            
								<?php
								        //Default is ground aka 4 days
								        $default_add = 4;
								        $default_date = addBusinessDays(date("Y-m-d H:i:s"), $default_add);
								        //Condition | conditionid can be set per each part. Will play around with the tactile (DROPPOP, BABY)
								        //Aaron is going to marry Aaron 2036 
								        $condition_dropdown = dropdown('conditionid','','','',false);
								        //Warranty
								        $warranty_dropdown = dropdown('warranty',$warranty,'','',false,'new_warranty');
								?>
								
					            <tr class ='search_row' style = 'padding:50px;background-color:#eff0f6;'>
					        		<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='' style='height:28px;padding:0;text-align:center;'></td>
							        <td id = 'search'>
							            <div class='input-group'>
							              <input type='text' class='form-control input-sm' id = 'go_find_me' placeholder='SEARCH FOR...'>
							              <span class='input-group-btn'>
							                <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
							            </span>
							            </div>
							        </td>
							        <td>			
									    <div class="input-group date datetime-picker-line">
							                <input type="text" name="ni_date" class="form-control input-sm" value="<?php echo $default_date; ?>" style = "min-width:50px;"/>
							                <span class="input-group-addon">
							                    <span class="fa fa-calendar"></span>
										    </span>
							            </div>
							        </td>
					        		<td><?php echo $condition_dropdown; ?></td>
					        		<td><?php echo $warranty_dropdown; ?></td>
					        		<td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_qty' placeholder='QTY' value = ''></td>
					            	<td>
						                <div class='input-group'>
						                    <span class='input-group-addon'>$</span>
						                    <input class='form-control input-sm' type='text' name = 'ni_price' placeholder='0.00' value=''>
						                </div>
						            </td>
					        		<td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_ext' placeholder='0.00'></td>
					                <td colspan='2' id = 'check_collumn'> 
					                    <a class='btn-sm btn-flat success pull-right multipart_sub' >
					                    <i class='fa fa-save fa-4' aria-hidden='true'></i></a>
					                </td>
								</tr>
							    <!-- Adding load bar feature here -->
						   	 	<tr class='search_loading'><td colspan='12'><span style='text-align:center; display: none; padding-top: 10px;'>Loading...</span></td></tr>
					    
								<!-- dummy line for nothing found -->
						   	 	<tr class='nothing_found' style='display: none;'><td colspan='12'><span style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Nothing Found</span></td></tr>
							</tfoot>
						<?php } ?>
				   </table>
				</div>
			</div>
			<!--====================== End Right half ======================-->
		</div>
	</div>
	
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		
		<script>
			$('#order_void').click(function(){
				var number = $("body").data("order-number");
				var type = $("body").data("order-type");
				if(confirm("Are you sure you want to void this order?")){
			    	$.post('/json/void-order.php',{"number" : number,"type" : type}, function(data) {
							location.reload();
							console.log("Order Voided: /json/void-order.php?"+"number="+number+"&type="+type); 
						}
					);
				}
			});
 
			(function($){
				$('.item_search').select2().on("change", function(e) {
					var $itemRow = $(this).closest('#add_row');
				    var obj = $(this).select2("data");
				    $.ajax({
						type: "POST",
						url: '/json/condition_stock.php',
						data: {
			   		    	"partid" : obj[0].id,
						},
						dataType: 'json',
						success: function(data) {
							console.log("change val=" + data); 
							$itemRow.find('#new_row_condition').empty();
							for(var i = 0; i < data.length; i++) {
								$itemRow.find('#new_row_condition').append('<option>' + data[i] + '</option>');
							}
						}
					});
				});
			})(jQuery);
		</script>

	</body>
</html>
