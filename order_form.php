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
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';

	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "New";
	$order_type = ($_REQUEST['ps'] == 'p' || $_REQUEST['ps'] == 'Purchase') ? "Purchase" : "Sales";
	$order_short = ($order_type == 'Purchase')? 'po' : 'so';
	$db_table = strtolower($order_type)."_orders";
	$db_order = ($order_type == 'Purchase')? 'po_number' : 'so_number';
	

	function getStock($condition = 'new', $partid) {
		$stock;
		
		$partid = res($partid);
		$condition = res($condition);
		
		$query = "SELECT SUM(qty) as total FROM inventory WHERE partid = $partid AND item_condition = '$condition';";
         $result = qdb($query);
         if (mysqli_num_rows($result)>0) { $stock = mysqli_fetch_assoc($result);}
		
		return $stock['total'];
	}

?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title><?=($order_number != 'New' ? '' : 'New')?> <?=strtoupper($order_short)?><?=($order_number != 'New' ? '#' . $order_number : '')?></title>

	</head>
	<!---->
	<body class="sub-nav forms" id = "order_body" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
		<div class="container-fluid pad-wrapper">

		<?php include 'inc/navbar.php';
		include_once $rootdir.'/modal/address.php';
		include_once $rootdir.'/modal/accounts.php';
		include_once $rootdir.'/modal/alert.php';
		include_once $rootdir.'/modal/contact.php';
		?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:
		<?= ($order_type == "Sales")?"#f7fff0":"#f5dfba";?> 
		;">
			
			<div class="col-md-4">
				<?php
				if($order_number != "New"){
					$url = ($order_type == "Sales")?"shipping":"inventory_add";
					echo '<a href="/'.$url.'.php?on=' . $order_number . '" class="btn-flat pull-left"><i class="fa fa-truck"></i></a> ';
					echo '<a href="/docs/'.$order_type[0].'O'.$order_number.'.pdf" class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
				}
				?>
				
			</div>
			<div class="col-md-4 text-center">
				<?php
				echo"<h2 class='minimal' style='margin-top: 10px;'>";
				if ($order_number=='New'){
					echo $order_number;
				}
				echo " $order_type Order";
				if ($order_number!='New'){
					echo " #$order_number";
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
		<div class="container-fluid">
		
			<!--================== Begin Left Half ===================-->
			<div class="left-side-main col-md-3 col-lg-2" data-page="order">
				<!-- Everything here is put out by the order creation ajax script -->
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
									if ($order_number != 'New'){
		                        		$old_rep = "Select `sales_rep_id` from $db_table WHERE `$db_order` = $order_number";
		                        		$set_rep = qdb($old_rep);
		                        		$set_rep = mysqli_fetch_assoc($set_rep);
		                        		$set_rep = $set_rep['sales_rep_id'];
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
	    					echo(dropdown("condition","","full_drop","",false,"condition_global"));
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
	    				<th style='min-width:30px;'></th>
						<th style='min-width:30px;'></th>
    				</thead>
					<tbody id="right_side_main" style = "font-size:13px;">
						
			        </tbody>
					<tfoot id = "search_input">
					</tfoot>

				   </table>
			   </div>
		</div>
		<!--====================== End Right half ======================-->
	</div>
</div>
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>
		
		<script>
 
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
