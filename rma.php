<?php

//==============================================================================
//============================ RMA General Template ============================
//==============================================================================
//  Description to come when I have a better sense of all the functions this   |
//	will cover.                                                                |
//                                                                             |
//	                                                        				   |
//																			   |
//	Aaron Morefield - February 22nd, 2017               					   |
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
			    RMA #101
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
									<th class="">History</th>
								</tr>
							</thead>
								<tr class="line_item" style = "padding-bottom:6px;">
									<td class="part"  data-partid="" style="padding-top: 5px !important;vertical-align:top !important;">
										<?=display_part(current(hecidb("erb")));?>
									</td>
								<!-- Grab the old serial values from the database and display them-->
									<td class="serials-col" style="">

										<div class="input-group serial_box">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="" data-saved="" value='' disabled>
										    <span class="input-group-addon">
										    	<input type="checkbox" name=""/>
										    </span>
							            </div>
									<div class="input-group serial_box">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="" data-saved="" value='' disabled>
										    <span class="input-group-addon">
										    	<input type="checkbox" name=""/>
										    </span>
							            </div>
									<div class="input-group serial_box">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="" data-saved="" value='' disabled>
										    <span class="input-group-addon">
										    	<input type="checkbox" name=""/>
										    </span>
							            </div>

									</td>
									<td class="disp-col">
										<div class='line-disp'>
										    <select class="form-control infinite input-sm">
										    	<option>
										    		Disposition
										    	</option>
										    </select>
										</div>
									<div class='line-disp'>
										    <select class="form-control infinite input-sm">
										    	<option>
										    		Disposition
										    	</option>
										    </select>
										</div>
										<div class='line-disp'>
										    <select class="form-control infinite input-sm">
										    	<option>
										    		Disposition
										    	</option>
										    </select>
										</div>
									</td>
									<td class="reason-col">
										<div class='line-reason'>
										    <input class="form-control infinite input-sm" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">
										</div>
										<div class='line-reason'>
										    <input class="form-control infinite input-sm" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">
										</div>
										<div class='line-reason'>
										    <input class="form-control infinite input-sm" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">
										</div>										
										<!--<button class="btn-sm btn-flat pull-right serial-expand" data-serial='serial-<?=$part['id'] ?>' style="margin-top: -40px;"><i class="fa fa-list" aria-hidden="true"></i></button>-->
									</td>
									<td class="history-col">
										<div class = "infinite">
											Bought 08/02/16 | Sold 08/02/16 | Return 08/05/16 <br>
											Repaired 08/07/16 | Sold 08/02/16 | <a class="lonk"> Show More</a>
										</div>
										<div class = "infinite">
											Bought 08/02/16 | Sold 08/02/16 | Return 08/05/16 <br>
											Repaired 08/07/16 | Sold 08/02/16 | <a class="lonk"> Show More</a>
										</div>
										<div class = "infinite">
											Bought 08/02/16 | Sold 08/02/16 | Return 08/05/16 <br>
										</div>
											Repaired 08/07/16 | Sold 08/02/16 | <a class="lonk"> Show More</a>
									</td>
								</tr>
								<tr class="line_item" style = "padding-bottom:6px;">
									<td class="part"  data-partid="" style="padding-top: 5px !important;vertical-align:top !important;">
										<?=display_part(current(hecidb("XE")));?>
									</td>
								<!-- Grab the old serial values from the database and display them-->
									<td class="serials-col" style="">

										<div class="input-group serial_box">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="" data-saved="" value='' disabled>
										    <span class="input-group-addon">
										    	<input type="checkbox" name=""/>
										    </span>
							            </div>
									<div class="input-group serial_box">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="" data-saved="" value='' disabled>
										    <span class="input-group-addon">
										    	<input type="checkbox" name=""/>
										    </span>
							            </div>
									<div class="input-group serial_box">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-inv-id ="" data-saved="" value='' disabled>
										    <span class="input-group-addon">
										    	<input type="checkbox" name=""/>
										    </span>
							            </div>

									</td>
									<td class="disp-col">
										<div class='line-disp'>
										    <select class="form-control infinite input-sm">
										    	<option>
										    		Disposition
										    	</option>
										    </select>
										</div>
									<div class='line-disp'>
										    <select class="form-control infinite input-sm">
										    	<option>
										    		Disposition
										    	</option>
										    </select>
										</div>
										<div class='line-disp'>
										    <select class="form-control infinite input-sm">
										    	<option>
										    		Disposition
										    	</option>
										    </select>
										</div>
									</td>
									<td class="reason-col">
										<div class='line-reason'>
										    <input class="form-control infinite input-sm" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">
										</div>
									<div class='line-reason'>
										    <input class="form-control infinite input-sm" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">
										</div>
										<div class='line-reason'>
										    <input class="form-control infinite input-sm" type="text" name="partComment" value="" placeholder="Comments" data-serial='' data-inv-id='' data-part="">
										</div>										
										<!--<button class="btn-sm btn-flat pull-right serial-expand" data-serial='serial-<?=$part['id'] ?>' style="margin-top: -40px;"><i class="fa fa-list" aria-hidden="true"></i></button>-->
									</td>
									<td class="history-col">
										<div class='line-history infinite'>
											<button class="btn-sm btn-flat"><i class="fa fa-history" aria-hidden="true"></i></button>
										</div>
										<div class='line-history infinite'>
											<button class="btn-sm btn-flat"><i class="fa fa-history" aria-hidden="true"></i></button>
										</div>
										<div class='line-history infinite'>
											<button class="btn-sm btn-flat"><i class="fa fa-history" aria-hidden="true"></i></button>
										</div>
									</td>
								</tr>								
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
