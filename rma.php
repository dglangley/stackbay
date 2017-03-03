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
	$rma_number = grab("rma",'');
	
	//Check for any post data from save
	//Array = [counter] = invid
	$checkedItems = $_POST["inventory"];
	//Array = [inventoryid] = "String for Reason"
	$reason = $_POST["reason"];
	//Array = [inventoryid] = return_id
	$dispositionArray = $_POST["disposition"];
	//Array = [inventoryid] = return_id
	$return = $_POST["return"];
	
	$companyid = $_POST['companyid'];
    $contactid = $_POST['contactid'];
    
	if(!empty($checkedItems)){
		//print_r($dispositionArray); die;
		$rmaSave;
		$partid;
		$rmaQuery;
		$partidQuery;
		$reasonInfo;
		$dispositionid;
		$disposition;
		
		//New RMA
		if ($rma_number == ""){
			
	        $insert = "INSERT INTO `returns`(`created_by`,`companyid`,`order_number`,`order_type`,`contactid`)
	        VALUES (".$U['contactid'].",".prep($companyid).",".prep($so_number).",'Sale',".prep($contactid).");";
	        qdb($insert) OR die();
	        $rma_number = qid();
	    	
	    	//$checkedItems contains all the inventory id
	        foreach($checkedItems as $invid) {
	        	$partidQuery = "SELECT partid FROM inventory WHERE id = ".res($invid).";";
	        	$rmaSave = qdb($partidQuery) or die(qe());
	        	
	        	if (mysqli_num_rows($rmaSave)>0) {
					$rmaSave = mysqli_fetch_assoc($rmaSave);
					$partid = $rmaSave['partid'];
				}
	        	
	        	$reasonInfo = $reason[$invid];
	        	$disposition = ($dispositionArray[$invid] != null ? $dispositionArray[$invid] : 0);
	        	
	        	//echo 'Reason: ' . $reasonInfo . ' RMA: ' . $rma_number . ' invid:' . $invid . ' Partid:' . $partid . "<br>";
	        		//print_r($return); die;
		        	$rmaQuery = "
		                INSERT INTO `return_items`
		                (`partid`,`inventoryid`, `rma_number`, `line_number`, `reason`, `dispositionid`, `qty`) VALUES 
		                ($partid ,$invid,".$rma_number.",NULL,'".$reasonInfo."',$disposition,1);
		            ";
	        	if ($rmaQuery){
					//echo($rmaQuery);
					qdb($rmaQuery) OR die();
				}
	        }
	    //Tis an RMA Update or Delete
		} else if($rma_number != '') {
        	foreach($checkedItems as $invid) {
	        	$partidQuery = "SELECT partid FROM inventory WHERE id = ".res($invid).";";
	        	$rmaSave = qdb($partidQuery) or die(qe());
	        	
	        	if (mysqli_num_rows($rmaSave)>0) {
					$rmaSave = mysqli_fetch_assoc($rmaSave);
					$partid = $rmaSave['partid'];
				}
	        	
	        	$reasonInfo = $reason[$invid];
	        	$disposition = ($dispositionArray[$invid] != null ? $dispositionArray[$invid] : 0);
	        	
	        	// print_r($checkedItems);
	        	// echo "<br>";
	        	// print_r($return);
	        	// die;
	        	//echo 'Reason: ' . $reasonInfo . ' RMA: ' . $rma_number . ' invid:' . $invid . ' Partid:' . $partid . "<br>";
	        	if ($return[$invid] != ''){
		            $rmaQuery = "
		            UPDATE `return_items` SET 
		            `reason`= '$reasonInfo',
		            `dispositionid`= $disposition
		             WHERE `id` = ".prep($return[$invid]).";";
	        	} else {
	        		$rmaQuery = "
		                INSERT INTO `return_items`
		                (`partid`,`inventoryid`, `rma_number`, `line_number`, `reason`, `dispositionid`, `qty`) VALUES 
		                ($partid ,$invid,".$rma_number.",NULL,'".$reasonInfo."',$disposition,1);
		            ";
	        	}
	        	
	        	if ($rmaQuery){
					//echo($rmaQuery);
					qdb($rmaQuery) OR die();
				}
        	}
        	//Garbage collection for unchecked data (the key = the inventoryid)
				foreach($return as $key => $retid) {
					if(!in_array($key, $checkedItems) && $retid != '') {
						$query = "DELETE FROM `return_items` WHERE `id` = $retid;";
						//echo $query;print_r($return);
						qdb($query) OR die();
					}
					//print_r($checkedItems);
		        	//echo $retid . "<br>";
		        	//print_r($return);
		        	
				}
				// print_r($checkedItems);
	   //     	echo "<br>";
	   //     	print_r($return);
	   //     	die;
		}
	}

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
		$sales_macro = "SELECT companyid, contactid, so_number FROM sales_orders where `so_number` = ".prep($so_number).";";
		$sales_macro = mysqli_fetch_assoc(qdb($sales_macro));
		$sales_micro = "SELECT i.serial_no, si.partid, i.id inventoryid FROM sales_items si, inventory i WHERE `so_number` = ".prep($so_number)." AND `sales_item_id` = `si`.`id`;";
		// echo($sales_micro);
		$sales_micro = qdb($sales_micro);
		// print_r($sales_micro);
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
	$i = 0;
	// echo"<pre>";
	// print_r($rma_items);
	// echo"</pre>";
	// exit;
	
	//parameter id if left blank will pull everything else if id is specified then it will give the disposition value
	function getDisposition($id = '') {
		$dispositions = array();
		$disp_value;
		
		if($id == '') {
			$query = "SELECT * FROM dispositions;";
			$result = qdb($query) or die(qe());
			
			while ($row = $result->fetch_assoc()) {
				$dispositions[$row['id']] = $row['disposition'];
			}
		} else {
			$query = "SELECT * FROM dispositions WHERE id = ".prep($id).";";
			$result = qdb($query) or die(qe());
			
			if (mysqli_num_rows($result)>0) {
				$result = mysqli_fetch_assoc($result);
				$disp_value = $result['disposition'];
			}
			
			return $disp_value;
		}
		
		return $dispositions;
	}
	
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
	<body class="sub-nav forms" id = "rma-body" data-rma-number="<?=$rma_number?>" data-associated="<?=$so_number?>">
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';
		include_once $rootdir.'/modal/address.php';
		include_once $rootdir.'/modal/accounts.php';
		include_once $rootdir.'/modal/alert.php';
		include_once $rootdir.'/modal/contact.php';
		?>
		
		<form action="rma.php?on=<?=$so_number;?>&rma=<?=$rma_number;?>" method="post">
			
			<div class="row-fluid table-header" id = "order_header" style="width:100%;height:50px;background-color:#f0f4ff;">
				
				<div class="col-md-4">
					<?php
	                    // Add in the following to link to the appropriate page | href="/'.$url.'.php?on=' . $order_number . '" | href="/docs/'.$order_type[0].'O'.$order_number.'.pdf"
						echo '<a class="btn-flat pull-left" href="/shipping.php?on='.$so_number.'">To Origin</a> ';
						echo '<a class="btn-flat pull-left" target="_new">Create Printable RMA</a>';
						echo '<a class="btn-flat pull-left" href="/rma_add.php?on='.$rma_number.'">Receive</a>';
					?>
					
				</div>
				<div class="col-md-4 text-center"><h2 class = "minimal" style ="margin-top:10px;;">
				    <?=($rma_number)? "RMA #$rma_number" :'New RMA';?>
				    </h2>
				</div>
				
				<div class="col-md-4">
					<button class="btn-flat btn-sm  <?=($order_number=="New")?'success':'success'?> pull-right" id = "rma_save_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;">
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
									<th></th>
									<th></th>
								</tr>
							</thead>
							<?php foreach ($rma_items as $part => $row): ?>
							<?php $i++;?>
								<!-- html... -->
								<tr class="line_item" style = "padding-bottom:6px;">
									<td class="part"  data-partid="" style="padding-top: 5px !important;vertical-align:top !important;">
										<?=display_part(current(hecidb($part,"id")));?>
									</td>
								<!-- Grab the old serial values from the database and display them-->
									<td class="serials-col" style="">
										<?php $initial = $i; ?>
										<?php $line = $initial;?>
										<?php foreach ($row as $i => $inf): ?>
											<?php $i++; ?>
											
											<div class="input-group serial_box">
											    <input class="form-control input-sm" type="text" name="serial_<?=$line?>" placeholder="Serial" data-inv-id ="" value="<?=$inf['serial_no']?>" readonly>

												<!--Array Version-->
												<input type="text" name="return[<?=$inf['inventoryid']?>]" style="display:none;" value = "<?=$inf['id']?>"/>
												
											    <?php if($mode!="view"){ ?>
											    <span class="input-group-addon">
											    	<input type="checkbox" name="inventory[]" value="<?=$inf['inventoryid']?>" <?=($inf['id'])? "checked" : ''?>/>
											    </span>
											    <?php } ?>
								            </div>
											<?php $line++; ?>
										<?php endforeach; ?>
	
									</td>
									<td class="disp-col">
										<?php	$line = $initial;?>
										<?php foreach ($row as $i => $inf):?>
											<div class='line-disp'>
												<?php 
													//Get all the disposition values
													$dispositionoptions = getDisposition('');
												?>
											    <?php if($mode=='view'){?>
											    <div class="infinite" style="line-height:30px;"><?=getDisposition($inf['dispositionid']); ?></div>
											    <?php }else{?>
											    <select class="form-control infinite input-sm" name='disposition[<?=$inf['inventoryid']?>]'>
											    	<option value = "null">Disposition</option>
											    	<?php foreach($dispositionoptions as $key => $value): ?>
											    		<option value ="<?=$key?>" <?=($inf['dispositionid'] == $key ? 'selected' : '');?>><?=$value?></option>
											    	<?php endforeach; ?>
											    </select>
											    <?php }?>
											</div>
											<?php $line++; ?>
										<?php endforeach; ?>
									</td>
	
									<td class="reason-col">
										<?php	$line = $initial;?>
										<?php foreach ($row as $i => $inf):?>
										
											<div class='line-reason'>
											    <?php if($mode=='view'){?>
											    <div class="infinite" style="line-height:30px;"><?=$inf['reason']?></div>
											    <?php }else{?>
											    <input class="form-control infinite input-sm" type="text" name="reason[<?=$inf['inventoryid']?>]" value="<?=$inf['reason']?>" placeholder="Reason" data-row='<?=$inf['id']?>' data-part="">
											    <?php }?>
											</div>
											<?php $line++; ?>
										<?php endforeach; ?>
									</td>
									<td class="history-col">
										<?php foreach ($row as $i => $inf):?>
										
										<div class = "infinite">
											<?php 
												$history_text ='';
												$hist_count = 0;
												foreach ($inf['history'] as $history){
													if($hist_count > 0 && $hist_count != 3){echo(" | ");}
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
													$hist_count++;
													if ($hist_count == 3){
														echo  "<br>";
													}
													else if ($hist_count == 6){
														echo "<a class = 'lonk'>Show more</a>";
														break;
													}
												}
												if ($hist_count < 3){
													echo"<br>&nbsp;";
												}
	
											?>
										</div>
										<?php endforeach; ?>
										
									</td>
									<td>
										<?php	$line = $initial;?>
										<?php foreach ($row as $i => $inf):?>
											<div class = "infinite" style="line-height:30px;">
												<i class='fa fa-pencil fa-4' aria-hidden='true'></i>
											</div>
											<?php $line++; ?>
										<?php endforeach; ?>
									</td>
									<td>
										<?php	$line = $initial;?>
										<?php foreach ($row as $i => $inf):?>
										
											<div class = "infinite" style="line-height:30px;">
												<i class='fa fa-trash fa-4' aria-hidden='true'></i>
											</div>
										<?php $line++; ?>
										<?php endforeach; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</table>
							
						<!-- Macro Form Inputs-->
						<input type="text" name="companyid" style="display:none;" value="<?=$sales_macro['companyid']?>"/>
						<input type="text" name="contactid" style="display:none;" value="<?=$sales_macro['contactid']?>"/>

					</div>
				</div>
			<!--====================== End Right half ======================-->
			</div>
		</form>
</div>

	<?php include_once 'inc/footer.php';?>
	<script src="js/operations.js?id=<?php if (isset($V)) { echo $V; } ?>"></script>


	</body>
</html>
