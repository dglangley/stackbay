<?php

//==============================================================================
//============================ RMA General Template ============================
//==============================================================================
//  Description to come when I have a better sense of all the functions this   |
//	will cover.                                                                |
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
	include_once $rootdir.'/inc/credit_functions.php';
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/order_type.php';

	function lastPrice($invid,$ps = 'sold',$before_date = ''){
		//Search for the last purchased price based off an inventory id.
		//If there are multiple, look for the most recent prior to the date of the search
		$o = o_params($ps);
		$select = "SELECT `price` FROM `".$o['item']."` WHERE `id` = (
			SELECT `value`
			from `inventory_history`
			WHERE invid = $invid 
			AND field_changed = '".$o['inv_item_id']."'
			".(($before_date)? "AND date(date_changed) <= ".format_date($before_date,"Y-m-d"):"")." 
			order by date_changed desc 
			limit 1 
			);";
		$result = qdb($select) or die(qe()." | $select");
		$result = mysqli_fetch_assoc($result);
		return($result['price']);
	}
	function insertRMA($partid, $invid, $rma_number, $line_number, $reasonInfo, $disposition, $qty=1) {
		$reasonInfo = trim($reasonInfo);

       	$rmaQuery = "INSERT INTO return_items ";
		$rmaQuery .= "(`partid`,`inventoryid`, `rma_number`, `line_number`, `reason`, `dispositionid`, `qty`) ";
		$rmaQuery .= "VALUES ($partid, $invid, $rma_number, ";
		if ($line_number) { $rmaQuery .= "'".res($line_number)."',"; } else { $rmaQuery .= "NULL,"; }
		if ($reasonInfo) { $rmaQuery .= "'".res($reasonInfo)."',"; } else { $rmaQuery .= "NULL,"; }
		$rmaQuery .= "$disposition,$qty); ";
		$result = qdb($rmaQuery) OR die(qe().'<BR>'.$rmaQuery);
	}

	//Declarations
	$mode = '';
	$order_number = '';
	$order_type = '';
	$rma_number = '';
	$rma_items = array();
	$date_created = '';
	$yesterday = '';
	$build_number;
	$companyid = 0;
	if (isset($_REQUEST['companyid'])) { $companyid = $_REQUEST['companyid']; }
	$rma_date = '';

	//Determine Mode AND if it was referred from a pre-existing Sales Order
	$mode = grab("mode");
	$order_number = grab("on");
	$rma_number = grab("rma",'');
	$repair = grab("repair");
	$build = grab("build");

	//If this record has a rma number, find the RMA
	if ($rma_number){
		$query = "SELECT created, companyid, order_number, order_type FROM `returns` WHERE rma_number = ".prep($rma_number).";";
		$result = qdb($query) or die(qe()." $query");
		if (mysqli_num_rows($result)){
			$r = mysqli_fetch_assoc($result);
			$order_number = $r['order_number'];
			$order_type = $r['order_type'];
			if (! $companyid) { $companyid = $r['companyid']; }
			$rma_date = $r['created'];

			if($r['order_type'] == 'Repair') {
				$repair = true;
			}
		}
	}

	if($build) {
		//get the build # for usage
		$build_number = $order_number;
		//Get the real number aka the RO number
		$query = "SELECT ro_number FROM builds WHERE id=".prep($order_number).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$order_number = $result['ro_number'];
		} else {
			$query = "SELECT id FROM builds WHERE ro_number=".prep($order_number).";";

			$result = qdb($query) or die(qe());
			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$build_number = $result['id'];
			}
		}
	}

	$T = order_type($order_type);

	//Check for any post data from save
	$checkedItems = $_POST["inventory"];
	$reason = $_POST["reason"];
	$dispositionArray = $_POST["disposition"];
	$rma_notes = $_POST["rma_notes"];
	$return = $_POST["return"];

    $contactid = $_POST['contactid'];
    
	if(!empty($checkedItems)){
		$rmaSave;
		$partid;
		$rmaQuery;
		$partidQuery;
		$reasonInfo;
		$dispositionid;
		$disposition;
		
		//New RMA
		if (!$rma_number){
			
	        $insert = "INSERT INTO `returns`(`created_by`,`companyid`,`order_number`,`order_type`,`contactid`,`notes`)
		        VALUES (".$U['contactid'].",".prep($companyid).",".prep($order_number).",'".$order_type."',".prep($contactid).",".prep($rma_notes).");";
	        qdb($insert) OR die();
	        $rma_number = qid();
	    	
	    	//$checkedItems contains all the inventory id
	        foreach($checkedItems as $invid) {
	        	$partidQuery = "SELECT partid FROM inventory WHERE id = ".res($invid).";";
	        	$result = qdb($partidQuery) or die(qe());
	        	
	        	if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$partid = $r['partid'];
				}
	        	
	        	$reasonInfo = $reason[$invid];
	        	$disposition = ($dispositionArray[$invid] != null ? $dispositionArray[$invid] : 0);

				insertRMA($partid,$invid,$rma_number,false,$reasonInfo,$disposition,$qty);
			}

		} else { //Tis an RMA Update or Delete

        	foreach($checkedItems as $invid) {
       			$partidQuery = "SELECT partid FROM inventory WHERE id = ".res($invid).";";
	        	$result = qdb($partidQuery) or die(qe());
				if (mysqli_num_rows($result)==0) {
					echo 'FAILED! No partid for query: '.$partidQuery.'<BR>';
					exit;
				}

				$r = mysqli_fetch_assoc($result);
				$partid = $r['partid'];

	        	$reasonInfo = $reason[$invid];
	        	$disposition = ($dispositionArray[$invid] != null ? $dispositionArray[$invid] : 0);
	        	
	        	$rma_macro_update = "UPDATE `returns` SET `notes` = ".prep($rma_notes)." WHERE rma_number = '".$rma_number."';";
	        	qdb($rma_macro_update) OR die();
	        	if ($return[$invid] != ''){
		            $rmaQuery = "
		            UPDATE `return_items` SET 
		            `reason`= '$reasonInfo',
		            `dispositionid`= $disposition
		             WHERE `id` = ".prep($return[$invid]).";";
					qdb($rmaQuery) OR die();
	        	} else {
					insertRMA($partid,$invid,$rma_number,false,$reasonInfo,$disposition,$qty);
				}
        	}

        	//Garbage collection for unchecked data (the key = the inventoryid)
			foreach($return as $key => $retid) {
				if(!in_array($key, $checkedItems) && $retid != '') {
					$query = "DELETE FROM `return_items` WHERE `id` = $retid;";
					qdb($query) OR die();
				}
			}
		}
	}

	// echo"<div style='position:fixed;right:15px;bottom:10px;'>";
	//If there is an RMA number, then this is an existing RMA record
	if ($rma_number){
		$rma_select = "SELECT * FROM `returns` WHERE `rma_number` = ".prep($rma_number).";";
		$rma_select = qdb($rma_select);
		//Verify there are Rows from this returns/get meta information
		if (mysqli_num_rows($rma_select)){
			//Should only be one row
			$rma = mysqli_fetch_assoc($rma_select);
			
			//Check to see if the order was within the last day
			$date_created = format_date($rma['created'],"Y-m-d");
			$yesterday = format_date(date("Y-m-d"),"Y-m-d",array("d"=>-1));
			if($date_created >= $yesterday){
				//If order was within last day, load Available sales order rows (allowing the user to edit them)
				if($rma['order_type'] == "Sale" || $rma['order_type'] == "Repair"){
					$order_number = $rma['order_number'];
				}
				else{
					//Otherwise, there is no need: just pull the 
					$order_number = false;
				}
			} else {
				$mode = 'old';
			}
			
			//Grab all the serials which have been received on this RMA already
			$rma_mic = "SELECT i.serial_no, ri.* FROM `return_items` ri, inventory i WHERE i.id = ri.inventoryid and `rma_number` = ".prep($rma_number).";";
			$rma_micro = qdb($rma_mic) or die(qe()." $rma_mic");
			
			$receive_check = '';
			//This check is grouping line item information by the inventory ID. I should be able to 
			foreach($rma_micro as $line_item){
				$receive_check = $line_item['id'].", ";
				$rma_items[$line_item['partid']][$line_item['inventoryid']] = $line_item;
				$rma_items[$line_item['partid']][$line_item['inventoryid']]['history'] = getItemHistory($line_item['inventoryid'],"exchange"); 
				//Exhange is a bit of a doubled name in the line above: here it refers to a "exhange" as a general term for sales, returns, purhcases and repairs.
			}
			$receive_check = trim($receive_check,", ");
			//This Query will search the history to see if the parts were ever received against the line item record
			$receive_query = "SELECT * FROM `inventory_history` where field_changed = 'returns_item_id' and value IN ($receive_check);";
			$receive_result = qdb($receive_query) or die(qe()." $receive_query");
			//If there are more rows (Or equivalent rows) in the receive row result, then all items have been received)
			if (mysqli_num_rows($rma_micro) <= mysqli_num_rows($receive_result) && mysqli_num_rows($rma_micro) > 0){
				$mode = 'view';
			}
		}
	}
	
	//If there is an so_number set, and we are not just looking at an existing record, here I would pull the SO items for addition
	if($order_number && $mode != 'view' && $mode != 'old'){

		//Aaron| when the dust has settled on table renaming, here is where I will be able to look to the Line's warranty to see if a line item is valid

		$query = "SELECT companyid, contactid, ".$T['order']." FROM repair_orders WHERE ".$T['order']." = ".prep($order_number).";";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		$r = mysqli_fetch_assoc($result);

		//Check to see if these items have already been RMA'd off this particular order
		$limiter = "SELECT `inventoryid`
			FROM `returns` r , `return_items` ri, inventory i
			WHERE order_number=".prep($order_number)." 
			AND order_type = '".$order_type."' 
			AND r.`rma_number` = ri.`rma_number`
			AND i.id = `inventoryid`
			AND i.`qty` > 0;
		";
		$limit_result = qdb($limiter) or die(qe()." | $limiter");
		$limit = '';
		$limit_arr = array();

		//here is where I take out the results of the serials I have already RMA'ed
		if (mysqli_num_rows($limit_result)){
			foreach ($limit_result as $invid){
				$limit_arr[$invid['inventoryid']] = true;
			}
		}

		// pull all items on this order
		$query2 = "SELECT i.serial_no, i.partid, i.id inventoryid, items.price FROM ".$T['items']." items, inventory i ";
		$query2 .= "WHERE ".$T['order']." = ".prep($order_number)." AND i.".$T['item_label']." = items.id; ";
		$result2 = qdb($query2) or die(qe()." | $query2");
		foreach ($result2 as $line_item){
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
				$rma_items[$partid][$invid]['price'] = '';
				$rma_items[$partid][$invid]['history'] = getItemHistory($invid,"exchange");
				if($limit_arr[$invid]){
					$rma_items[$partid][$invid]['already'] = true;
				} 
			}
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
		<title>RMA<?=($rma_number ? '# '.$rma_number : '')?></title>
		<style type="text/css">
			.serial_box{
				padding-bottom:10px;
			}
			.history-col{
				font-size:8pt;
			}

			.table td {
				vertical-align: top !important;
			}
		</style>
	</head>
	<!---->
	<body class="sub-nav forms" id = "rma-body" data-rma-number="<?=$rma_number?>" data-associated="<?=$order_number?>">
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';
		include_once $rootdir.'/modal/address.php';
		include_once $rootdir.'/modal/accounts.php';
		include_once $rootdir.'/modal/alert.php';
		include_once $rootdir.'/modal/contact.php';
		include_once $rootdir.'/modal/history.php';
		?>
		
		<form action="rma.php?on=<?=$order_number;?>&rma=<?=$rma_number;?><?=($repair ? '&repair=true' : ($build ? '&build=true' : ''));?>" onsubmit="return validateForm()" method="post" style="height: 100%;">
			
			<div class="row-fluid table-header" id = "order_header" style="width:100%;background-color:#f0f4ff;">
				
				<div class="col-md-4">
					<?php
	                    // Add in the following to link to the appropriate page | href="/'.$url.'.php?on=' . $rma_number . '" | href="/docs/'.$type[0].'O'.$rma_number.'.pdf"
/*
						if ($order_type=='Sale') {
							echo '<a class="btn btn-primary btn-sm" href="/shipping.php?on='.$order_number.'"><i class="fa fa-truck"></i> SO# '.$order_number.'</a>';
						} else if ($repair) {
							echo '<a class="btn btn-default btn-sm text-warning" href="/repair_add.php?on='.$order_number.'"><i class="fa fa-qrcode"></i> RO# '.$order_number.'</a>';
						} else {
							//It's a build mask
							echo '<a class="btn btn-default btn-sm text-warning" href="/repair_add.php?on='.$order_number.'"><i class="fa fa-qrcode"></i> Build# '.$build_number.'</a>';
						}
*/
						if($rma_number){
							echo '<a class="btn btn-default btn-sm text-warning" href="/rma_add.php?on='.$rma_number.'"><i class="fa fa-qrcode"></i> Receive</a>';
							$rows = get_assoc_credit($rma_number);
							if(mysqli_num_rows($rows)>0){
								$output = '
								<div class ="btn-group">
									<button type="button" class="btn-flat btn-default dropdown-toggle" data-toggle="dropdown">
		                              <i class="fa fa-credit-card"></i>
		                              <span class="caret"></span>
		                            </button>';
	                            
								$output .= '<ul class="dropdown-menu">';
								foreach ($rows as $cm) {
									$output .= '
										<li>
											<a href="/docs/CM'.$cm['id'].'.pdf">
											Credit Memo #'.$cm['id'].'
											</a>
										</li>';
								}
	                            $output .= "</ul>";
								$output .= "</div>";
								echo $output;
							}
						}
					?>
					
				</div>
				<div class="col-md-4 text-center">
					<h2 class="minimal" style="margin-top:10px"><?=($rma_number)? "RMA# $rma_number" :'New RMA';?></h2>
					<?php if ($rma_date) { echo '<div class="info text-center" style="font-size:14px">'.format_date($rma_date,"D n/j/y g:ia").'</div>'; } ?>
				</div>
				<div class="col-md-4 text-right">
					<button class="btn btn-success btn-sm" id = "rma_save_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;" disabled>
						<i class="fa fa-save"></i> <?=($rma_number=="New") ? 'Create' :'Save'?>
					</button>
				</div>
			</div>
			
			<!-- Row declaration for splitting the two "halves of the page  -->
			<div class="row remove-margin">
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-md-3 col-lg-2" data-page="order" style="height:100%;background-color:#efefef">
					<div class="row">
						<div class="col-sm-12">
<?php
	$prefix = strtoupper(substr($T['order'],0,2));
?>
							<h4 style="margin-top:10px"><?php echo strtoupper(getCompany($companyid)); ?></h4>
							<h3><?php echo $prefix.' '.$order_number; ?> <a href="/<?php echo $prefix.$order_number; ?>"><i class="fa fa-arrow-right"></i></a></h3>
						</div>
					</div>
					<BR><BR><BR>
					<div class="row">
						<div class="col-sm-12">
							<label for="rma_notes">RMA Notes</label>
							<textarea id="rma_notes" class="form-control textarea" name="rma_notes" rows="3"><?php echo $rma_notes; ?></textarea>
						</div>
					</div>	
				</div>
			
				<!--======================= End Left half ======================-->
			
		
				<!--===================== Begin Right half =====================-->
				<div class="col-sm-10 shipping-list" style="padding-top: 20px">
					<div class="table-responsive">
						<table class="rma-create table table-hover table-striped table-condensed" style="margin-top: 15px;">
							<thead>
								<tr>
									<th class="col-md-3">Description</th>
									<th class="col-md-1">Price</th>
									<th class="col-md-1">Warr Exp</th>
									<th class="col-md-2">Serials</th>
									<th class="col-md-1">Disposition</th>
									<th class="col-md-2">Reason</th>
									<th class="col-md-2">History</th>
								</tr>
							</thead>
							
							<?php foreach ($rma_items as $part => $row): $init = true;?>
								<!-- html... -->
								<?php foreach ($row as $i => $inf):?>
									<tr class="line_item" style = "padding-bottom:6px;">
										<td class="part"  data-partid="">
											<?php if($init):?>
												<?=display_part(current(hecidb($part,"id")));?>
											<?php endif;?>
										</td>

										<td>
											<?php if($init):?>
												<?=format_price(lastPrice($inf['inventoryid']));?>
											<?php endif;?>
										</td>

										<td class="warranty-col" style="">
											<?php if($init):?>
												<div class='war-disp'>
												    <div class="infinite warranty_col brow-<?=$i?>"><?=calcWarranty($inf['inventoryid'])?></div>
												</div>
											<?php endif;?>
											
										</td>
									<!-- Grab the old serial values from the database and display them-->
										<td class="serials-col" style="">											
											<div class="input-group serial_box">
											    <input class="form-control input-sm" type="text" name="serial_<?=$line?>" placeholder="Serial" data-inv-id ="" value="<?=$inf['serial_no']?><?=($inf['already'])? "&nbsp;(RMA'ed)" : ''?>" readonly>

												<!--Array Version-->
												<input type="text" name="return[<?=$inf['inventoryid']?>]" style="display:none;" value = "<?=$inf['id']?>"/>
												
											    <?php if($mode!="view"){ ?>
											    <span class="input-group-addon">
											    	<input type="checkbox" class ='brow-<?=$i?>' name="inventory[]" value="<?=$inf['inventoryid']?>" 
											    	<?=($inf['id'] || $inf['already'])? "checked" : ''?>
											    	<?=($inf['already'])? "disabled" : ''?>/>
											    </span>
											    <?php } ?>
								            </div>	
										</td>

										<td class="disp-col">
											<div class='line-disp'>
												<?php 
													//Get all the disposition values
													$dispositionoptions = getDisposition('');
												?>
											    <?php if($mode=='view'){?>
											    <div class="infinite brow-<?=$i?>"><?=getDisposition($inf['dispositionid']); ?></div>
											    <?php }else{?>
											    <select class="form-control disposition_drop infinite brow-<?=$i?> input-sm" data-row='<?=$i?>' style='min-width:145px;padding-right:2px;' name='disposition[<?=$inf['inventoryid']?>]' <?=($inf['already'])? "disabled" : ''?>>
											    	<option value = "null">- Select Disposition -</option>
											    	<?php foreach($dispositionoptions as $key => $value): ?>
											    		<option value ="<?=$key?>" <?=($inf['dispositionid'] == $key ? 'selected' : '');?>><?=$value?></option>
											    	<?php endforeach; ?>
											    </select>
											    <?php }?>
											</div>
										</td>
		
										<td class="reason-col">
											<div class='line-reason'>
											    <?php if($mode=='view'){?>
											    <div class="infinite brow-<?=$i?>" style="line-height:30px;"><?=$inf['reason']?></div>
											    <?php }else{?>
											    <input class="form-control reason_input infinite brow-<?=$i?> input-sm" type="text" name="reason[<?=$inf['inventoryid']?>]" value="<?=$inf['reason']?>" placeholder="Reason" data-row='<?=$inf['id']?>' data-part="" <?=($inf['already'])? "disabled" : ''?>>
											    <?php }?>
											</div>
										</td>
										<td class="history-col">										
											<!--font-size:1.2vh;-->
											<div class = "infinite">
												<?php 
													$first_war = true;
													$hist_count = 0;
													$printable = array();
													$war_text = "";
													if($inf['history']){
														foreach ($inf['history'] as $i => $history){
															$select = "";
															$link = "";
															$actionHolder = "";
															$action = "";
															switch ($history['field']) {
																case 'sales_item_id':
																	$select = "SELECT `so_number` o FROM `sales_items` WHERE `id` = ".prep($history['value']).";";
																	$link = "/SO";
																	$actionHolder = "SO";
																	break;
																case 'returns_item_id':
																	$select = "SELECT `rma_number` o FROM `return_items` WHERE `id` = ".prep($history['value']).";";
																	$link = "/rma.php?rma=";
																	$actionHolder = "RMA";
																	break;
																case 'purchase_item_id':
																	$select = "SELECT `po_number` o FROM `purchase_items` WHERE `id` = ".prep($history['value']).";";
																	$actionHolder = "PO";
																	$link = "/PO";
																	if($first_war){
																		$war_text = "Warr ";
																		// $most_recent_war = $history['value'];
																		$war_text .= calcWarranty($history['value'],"history") . '<br>';
																	}
																	break;
															}

															if($select){
																$result = qdb($select);
																$result = mysqli_fetch_assoc($result);
																if($result['o'] != $order_number) {
																	$action .= $actionHolder . $result['o']." <a class = 'lonk' href='$link".$result['o']."'> <i class='fa fa-arrow-right' aria-hidden='true'></i></a> &nbsp; ";
																	if($actionHolder != 'PO') {
																		$action .= format_date($history['date'],"n/j/y") . "<br>";
																	}
																}
															}

															$printable[] = $action;
														
															if($first_war && $war_text){
																//Print out the $warranty text
																$printable[] = $war_text;
																//We only care about the most recent warranty
																$first_war = false;
															}
														}
													}
													foreach ($printable as $o => $text) {
														echo($text);
														$final = $o;
													}
													if (count($printable)==0) { echo "&nbsp;<br>&nbsp;"; }
												?>
											</div>										
										</td>
									</tr>
								<?php $init = false; endforeach; ?>
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

	<script>
		(function($){
			$(document).on("change", "input[name='inventory[]']", function(){
				$('#rma_save_button').removeClass('gray');
				$('#rma_save_button').addClass('success');
				$('#rma_save_button').prop('disabled', false);
				
				var rmaCheck = $("input[name='inventory[]']:checked").length;
    			if (!rmaCheck){
    				$('#rma_save_button').addClass('gray');
					$('#rma_save_button').removeClass('success');
					$('#rma_save_button').prop('disabled', true);
    			}
			});
			$(document).on("change", ".disposition_drop", function(){
				$('#rma_save_button').removeClass('gray');
				$('#rma_save_button').addClass('success');
				$('#rma_save_button').prop('disabled', false);
				var row = $(this).data('row');
				$(this).closest("tr").find(".serials-col").find(".brow-"+row).attr('checked', 'checked');
			});
			$(document).on("keyup", ".reason_input", function(){
				$('#rma_save_button').removeClass('gray');
				$('#rma_save_button').addClass('success');
				$('#rma_save_button').prop('disabled', false);
				var row = $(this).data('row');
				$(this).closest("tr").find(".serials-col").find(".brow-"+row).attr('checked', 'checked');
			});
			$(document).on("keyup", "#rma_notes", function(){
				$('#rma_save_button').removeClass('gray');
				$('#rma_save_button').addClass('success');
				$('#rma_save_button').prop('disabled', false);
				var row = $(this).data('row');
				$(this).closest("tr").find(".serials-col").find(".brow-"+row).attr('checked', 'checked');
			});
		})(jQuery);
		
		function validateForm() {
		    // var x = "";
		    // if (x == "") {
		    //     modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Disposition can not be empty. <br><br> Please select a dispostion and re-submit the form.");
		    //     return false;
		    // }
		}
	</script>

	</body>
</html>
