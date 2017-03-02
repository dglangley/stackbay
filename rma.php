<?php

//==============================================================================
//============================ RMA General Template ============================
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
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/item_history.php';
	
	//Declarations
	$mode = '';
	$so_number = '';
	$rma_number = '';
	$rma_items = array();
	$date_created = '';
	$yesterday = '';
	
	
	//Determine Mode AND if it was referred from a pre-existing Sales Order
	$mode = grab("mode");
	$so_number = grab("on");
	$rma_number = grab("rma");
	$rma_number = 1;
	// echo"<div style='position:fixed;right:15px;bottom:10px;'>";
	if ($rma_number){
		$rma_select = "SELECT * FROM `returns` WHERE `rma_number` = ".prep($rma_number).";";
		$rma_select = qdb($rma_select);
		//If this record exists, perform the check that there is some valid answer

		if (mysqli_num_rows($rma_select) > 0){
			//Date Created
			
			$rma = mysqli_fetch_assoc($rma_select);
			
			//Check to see if the order was within the last day
			$date_created = format_date($rma['created'],"Y-m-d");
			$yesterday = format_date(date("Y-m-d"),"Y-m-d",array("d"=>-1));
			if($date_created >= $yesterday){
				if($rma['order_type'] == "Sale"){
					//If order was within last day, load associated Available sales order rows
					$so_number = $rma['order_number'];
				}
				else{
					$so_number = false;
				}
			}

			$rma_mic = "SELECT i.serial_no, ri.* FROM `return_items` ri, inventory i WHERE i.id = ri.inventoryid and `rma_number` = ".prep($rma_number).";";
			
			$rma_micro = qdb($rma_mic);
			foreach($rma_micro as $line_item){
				
				$rma_items[$line_item['partid']][$line_item['inventoryid']] = $line_item;
				$rma_items[$line_item['partid']][$line_item['inventoryid']]['history'] = getItemHistory($line_item['inventoryid'],"exchange");

			}
		}
	}
	
	if($so_number){
		//Aaron| when the dust has settled on table renaming, here is where I will be able to look to the Line's warranty to see if a line item is valid
		$sales_micro = "SELECT i.serial_no, si.partid, i.id inventoryid FROM sales_items si, inventory i WHERE `so_number` = ".prep($so_number)." AND `sales_item_id` = `si`.`id`;";
		$sales_micro = qdb($sales_micro);
		foreach ($sales_micro as $line_item){
			$partid = '';
			$invid = '';
			if (!isset($rma_items[$line_item['partid']][$line_item['inventoryid']])){
				$partid = $line_item['partid'];
				$invid = $line_item['inventoryid'];
				$rma_items[$partid][$invid] = $line_item;
				$rma_items[$partid][$invid]['rma_number'] = '';
				$rma_items[$partid][$invid]['line_number'] = '';
				$rma_items[$partid][$invid]['reason'] = '';
				$rma_items[$partid][$invid]['dispositionid'] = '';
				$rma_items[$partid][$invid]['qty'] = '';
				$rma_items[$partid][$invid]['id'] = '';
				$rma_items[$partid][$invid]['history'] = getItemHistory($invid,"exchange");
			}
		}
	}
	
	foreach ($rma_items as $part => $serial){
		$history = array();
		foreach($serial as $invid => $info){
			$info['test'] = 'test';
		}
	}
	// echo"<pre>";
	// print_r($rma_items);
	// echo"</pre>";
	// exit;
	
?>






<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title>RMA</title>
		<style type="text/css">
			.serial_box{
				padding-bottom:10px;
			}
			.reason-col, .serials-col, .qty-col,.disp-col,.history-col{
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
	<body class="sub-nav forms" id = "rma-body" data-rma-number="<?=$rma_number?>">
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';
		include_once $rootdir.'/modal/address.php';
		include_once $rootdir.'/modal/accounts.php';
		include_once $rootdir.'/modal/alert.php';
		include_once $rootdir.'/modal/contact.php';
		?>
		<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:#f0f4ff;">
			
			<div class="col-md-4">
				<?php
                    // Add in the following to link to the appropriate page | href="/'.$url.'.php?on=' . $order_number . '" | href="/docs/'.$order_type[0].'O'.$order_number.'.pdf"
					echo '<a class="btn-flat pull-left">To Origin</a> ';
					echo '<a class="btn-flat pull-left" target="_new">Create Printable RMA</a>';
				?>
				
			</div>
			<div class="col-md-4 text-center"><h2 class = "minimal" style ="margin-top:10px;;">
			    <?=($rma_number)? "RMA #$rma_number" :'New RMA';?>
			    </h2>
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
			<div class="rma-macro col-md-3 col-lg-2" data-page="order" style="height:100%;background-color:#efefef;padding-top:15px;">
				
			</div>
		
			<!--======================= End Left half ======================-->
		
	
			<!--===================== Begin Right half =====================-->
				<div class="col-sm-10 shipping-list" style="padding-top: 20px">
					<div class="table-responsive">
						<table class="rma-create table table-hover table-striped table-condensed" style="margin-top: 15px;">
							<thead>
								<tr>
									<th class="col-md-3">Item</th>
									<th class="col-md-2">Serials</th>
									<th class="col-md-2">Disposition</th>
									<th class="col-md-2">Reason</th>
									<th class="3">History</th>
									<th></th>
									<th></th>
								</tr>
							</thead>
							<?php foreach ($rma_items as $part => $row): ?>
								<!-- html... -->
								<tr class="line_item" style = "padding-bottom:6px;">
									<td class="part"  data-partid="" style="padding-top: 5px !important;vertical-align:top !important;">
										<?=display_part(current(hecidb($part,"id")));?>
									</td>
								<!-- Grab the old serial values from the database and display them-->
									<td class="serials-col" style="">
										<?php foreach ($row as $inf): ?>
											<div class="input-group serial_box">
											    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="<?=$inf['inventoryid']?>" value='<?=$inf['serial_no']?>' disabled>
											    <?php if($mode!="view"){ ?>
											    <span class="input-group-addon">
											    	<input type="checkbox" name="line_selected" <?=($inf['id'])? "checked" : ''?>/>
											    </span>
											    <? } ?>
								            </div>
										<?php endforeach; ?>

									</td>
									<td class="disp-col">
										<?php foreach ($row as $inf):?>
											<div class='line-disp'>
											    <?php if($mode=='view'){?>
											    <div class="infinite" style="line-height:30px;">Disposition</div>
											    <?php }else{?>
											    <select class="form-control infinite input-sm">
											    	<option>
											    		Disposition
											    	</option>
											    </select>
											    <?php }?>
											</div>
										<?php endforeach; ?>
									</td>

									<td class="reason-col">
										<?php foreach ($row as $inf):?>
											<div class='line-reason'>
											    <?php if($mode=='view'){?>
											    <div class="infinite" style="line-height:30px;"><?=$inf['reason']?></div>
											    <?php }else{?>
											    <input class="form-control infinite input-sm" type="text" name="partReason" value="" placeholder="Reason" data-row='<?=$inf['id']?>' data-part="">
											    <?php }?>
											</div>
										<?php endforeach; ?>
									</td>
									<td class="history-col">
										<?php foreach ($row as $inf):?>
										<div class = "infinite">
											<?php 
												$history_text ='';
												$i = 0;
												foreach ($inf['history'] as $history){
													if($i > 0 && $i != 3){echo(" | ");}
													switch ($history['field']) {
														case 'sales_item_id':
															$action = "Sold";
															break;
														case 'return_items_id':
															$action = "Returned";
															break;
														case 'purchase_item_id':
															$action = "Purchased";
															break;
															
													} 
													 echo  $action.": ".$history['date'];
													$i++;
													if ($i == 3){
														echo  "<br>";
													}
													else if ($i == 6){
														echo "<a class = 'lonk'>Show more</a>";
														break;
													}
												}
												if ($i < 3){
													echo"<br>&nbsp;";
												}
	
											?>
										</div>
										<?php endforeach; ?>
										
									</td>
									<td>
										<?php foreach ($row as $inf):?>
											<div class = "infinite" style="line-height:30px;">
												<i class='fa fa-pencil fa-4' aria-hidden='true'></i>
											</div>
										<?php endforeach; ?>
									</td>
									<td>
										<?php foreach ($row as $inf):?>
											<div class = "infinite" style="line-height:30px;">
												<i class='fa fa-trash fa-4' aria-hidden='true'></i>
											</div>
										<?php endforeach; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
					</div>
				</div>
		<!--====================== End Right half ======================-->
	</div>
</div>
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>


	</body>
</html>
