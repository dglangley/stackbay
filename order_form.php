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
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/invoice.php';
	include_once $rootdir.'/inc/getSalesCharges.php';
	
	// print_r(hecidb('ERB')); die;
	// $query = "SELECT * FROM parts WHERE id = 343669;";
 //            $result = qdb($query) or jsonDie(qe()." | $query");

 //            if (mysqli_num_rows($result)) {
 //                $result = mysqli_fetch_assoc($result);
 //            }

            //print_r($result); die;
	//use this variable when RTV is used to grab all the checked items from the last post
	$rtv_items = array();
	$rtv_array = array();
	
	//General Variable Declarations
	$origin = '';
	$order_short;
	$o = array();

	if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$keyword = $_REQUEST['s'];
	}
	
	//High level order parameters
	$o = o_params(grab('ps',"s"));
	$order_number = grab('on','New');
	$build_number;
	$sales_order;
	$tracking;
	
	if(strtolower($o['type']) == 'rtv'){
	 	$status = 'Active';
		$origin = $order_number;
		$order_number = "New";
		//If there are items to be Returned to Vendor, we gather the items in through a passed JSON parameter
		$rtv_items = $_REQUEST['partid'];
	} else if ($o['type'] == "Invoice"){
	 	$inv_info = getInvoice($order_number);
	 	$origin = $inv_info['order_number'];
	} else if($o['type'] == "Repair") {

		$repair_billable = true;

		$query = "SELECT ro.termsid, ri.price FROM repair_orders ro, repair_items ri WHERE ro.ro_number = ".prep($order_number).";";
		$result = qdb($query) or die(qe());

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			if($result['termsid'] == '15' && $result['price'] == '0.00')
				$repair_billable = false;
		}

	 	//Check to see if a sales_item record has been created for this item
	 	//echo $status . 'test';
		if($status) {
			$query = "SELECT so_number FROM sales_items s, repair_items r WHERE s.ref_1_label = 'repair_item_id' AND s.ref_1 = r.id AND r.ro_number = ".prep($order_number).";";
			$result = qdb($query) or die(qe());
			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$sales_order = $result['so_number'];
			} else {
				$query = "SELECT tracking_no FROM packages WHERE order_type = 'Repair' AND order_number = ".prep($order_number).";";
				$result = qdb($query) or die(qe());
				if (mysqli_num_rows($result)) {
					$result = mysqli_fetch_assoc($result);
					$tracking = ($result['tracking_no'] ? $result['tracking_no'] : 'N/A');
				}
			}
		}
		//echo $query;
	} else if($o['type'] == "Builds") {
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

	$status = getOrderStatus($o['type'],$order_number);


	function getFreightTotal($order_number) {
	 	$total = 0;
	 	
	 	$query = "SELECT SUM(freight_amount) AS total FROM packages WHERE order_number = '".res($order_number)."';";
	 	
	 	$result = qdb($query) OR die(qe());
			
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$total = $result['total'];
		}
	 	
	 	return $total;
	}

	function getRMA($order_number, $type){
		$RMA = array();

		$query = "SELECT * FROM returns as r, return_items as i WHERE r.order_number = ".prep($order_number)." AND r.order_type = ".prep($type)." AND r.rma_number = i.rma_number;";
		$result = qdb($query) OR die(qe());

		while ($row = $result->fetch_assoc()) {
			$RMA[] = $row;
		}

		return $RMA;
	}

	function getSerial($invid) {
		$serial;

		$query = "SELECT serial_no FROM inventory WHERE id = ".prep($invid).";";
		$result = qdb($query) or die(qe());
		if (mysqli_num_rows($result)){
			$row = mysqli_fetch_assoc($result);
			$serial = $row['serial_no'];
		}

		return $serial;
	}

	$ORDER = array('fcreated'=>'','companyid'=>0);
	$dataid = array();
	$dataid[0]['id'] = "New";
	$dataid[0]['text'] = "";
	$dataid[0]['memo'] = "";
	$dataid[1]['id'] = "New";
	$dataid[1]['text'] = "";
	$dataid[1]['memo'] = "";
	if ($order_number!='New') {
		$o = o_params($o['type']);
		$table = $o['order'];
		$id = $o['id'];
		$q = "SELECT * FROM `$table` WHERE $id = '".$order_number."'; ";
		$result = qdb($q) or die(qe()." | $q");
		if (mysqli_num_rows($result)){
			$ORDER = mysqli_fetch_assoc($result);
			$ORDER['fcreated'] = format_date($ORDER['created'],"D n/j/y g:ia");
		}
	
		$charges = getSalesCharges($order_number);
		
		if ($charges){
			$i = 0;
			foreach($charges as $charge){
				$dataid[$i]['id'] = $charge['id'];
				$dataid[$i]['text'] = $charge['price'];
				$dataid[$i]['label'] = $charge['memo'];
				$i++;
			}
		} 
	}

	$RMA_history = array();

	if ($o['type'] == "Sales"){
	 	$RMA_history = getRMA($order_number, 'Sale');
	}

	if ($o['type'] == "Purchases"){
	 	$RMA_history = getRMA($order_number, 'Purchase');
	}

 	function getPackagesFix($order_number, $type = 'Sale') {
 	 	$output = '';
	 	$invoices = array();
		$o = o_params($type);
	 	//Stores all the packages that have been accounted for in an invoice
	 	$packages_accounted = array();

	 	$porder_number = prep($order_number);
		
		$query = "
		SELECT GROUP_CONCAT(package_no) as package_no, tracking_no, i.invoice_no, i.date_invoiced 
			FROM  `packages` p,  `invoice_shipments` sp, invoice_items ii, invoices i
			WHERE p.id = sp.packageid
			AND ii.id = sp.invoice_item_id
			AND i.invoice_no = ii.invoice_no
			AND p.order_number = $order_number
			AND p.order_type = ".prep($o['ptype'])."
			GROUP BY package_no;
		";
	 	$invoices = qdb($query) or die(qe()." | $query");
		foreach($invoices as $shipment) {
		 	$output .= "
		 		<li>
	 				<a target='_blank' href='/docs/INV".$shipment['invoice_no'].".pdf'>
	 					Shipment ".$shipment['package_no']." Invoice ".$shipment['invoice_no']. " (".format_date($shipment['date_invoiced'],'n/j/Y').")
	 				</a>
	 			</li>
		 	";
		 }

	 	return $output;
	 }

	//print_r($RMA_history);

?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css?id=<?php if (isset($V)) { echo $V; } ?>" type="text/css" />
		<title><?=($order_number != 'New')? ($o['short'] == 'build' ? ucfirst($o['short'])."# ".($build_number ? $build_number : $order_number) : strtoupper($o['short'])."# ".$order_number) : ('New '.($o['short'] == 'build' ? ucfirst($o['short']) : strtoupper($o['short'])) )?></title>

		<style>
			tr.strikeout td:before {
			    content: " ";
			    position: absolute;
			    top: 50%;
			    left: 0;
			    border-bottom: 1px solid;
			    width: 100%;
			}

			.ticket_status_danger {
				color: #a94442;
			}
			.ticket_status_success {
				color: #3c763d;
			}
			.ticket_status_warning {
				color: #8a6d3b;
			}
		</style>

	</head>
	<!---->
	<body class="sub-nav forms <?=(strtolower($status) == 'void' || strtolower($status) == 'voided' ? 'void-order' : '');?>" id = "order_body" data-order-type="<?=$o['type']?>" data-order-number="<?=$order_number?>" <?=($_REQUEST['repair'] ? 'data-type="repair"' : '')?>>
		<div class="pad-wrapper">
			<?php 
				include 'inc/navbar.php';
				include_once $rootdir.'/modal/address.php';
				include_once $rootdir.'/modal/accounts.php';
				include_once $rootdir.'/modal/alert.php';
				include_once $rootdir.'/modal/contact.php';
				include_once $rootdir.'/modal/payments.php';
			?>
			<div class="row-fluid table-header" id = "order_header" style="width:100%;height:<?=(($status && $o['repair'] && $order_number!='New') ? '75':'50')?>px;background-color:<?=$o['color']?>;">
				
				<div class="col-md-3">
					<?php
						if($order_number != "New"){
							if($o['type'] == 'Invoice'){
								echo '<a href="/order_form.php?on='. $origin .'&ps=s" class="btn-flat pull-left"><i class="fa fa-list"></i></a> ';
							}
							echo '<a href="/'.$o['url'].'.php?on=' . ($build_number ? $build_number . '&build=true' : (($origin)? $origin : $order_number)) . '" class="btn-flat pull-left"><i class="fa fa-truck"></i></a> ';
							if($o['type'] == 'Builds'){
								echo '<a href="/repair.php?on='. ($build_number ? $build_number : $order_number) .'&build=true" class="btn-flat pull-left"><i class="fa fa-wrench"></i></a> ';
							}
							if($o['type'] == 'Repair'){
								echo '<a href="/repair.php?on='. ($build_number ? $build_number : $order_number) .'" class="btn-flat pull-left"><i class="fa fa-wrench"></i></a> ';
							}
							if($o['type'] != 'Repair' && $o['type'] != 'Builds') {
								echo '<a target="_blank" href="/docs/'.strtoupper($o['short']).$order_number.'.pdf" class="btn-flat pull-left"><i class="fa fa-file-pdf-o"></i></a>';
							}
							if($o['type'] == 'Builds'){
								echo '<a href="/builds_management.php?on='.$build_number.'" class="btn-flat pull-left">Manage</a>';
							}
						}

						if($order_number != "New" && ($o['sales'] || $o['repair'] || $o['type'] == 'Builds')){
							$rows = get_assoc_invoices($order_number, $o['type']);

							//Get packages pertaining to this order
							//$packages = getPackagesFix($order_number);

								$output = '
								<div class ="btn-group">
									<button type="button" class="btn-flat dropdown-toggle" data-toggle="dropdown">
		                              <i class="fa fa-credit-card"></i>
		                              <span class="caret"></span>
		                            </button>
									<ul class="dropdown-menu">
								';

								$output .= getPackagesFix($order_number, $o['type']);

								// if($packages) {
								// 	foreach($packages as $item){

								// 	}
								// }
								//Check to see if this order has a valid package to create an invoice

								$output .= '
										<li>
											<a target="_blank" href="/invoice.php?on='.$order_number.'">
												<i class="fa fa-plus"></i> Proforma Invoice
											</a>
										</li>
	                            	</ul>
								</div>
								';
								echo $output;

							// echo '<a class="btn-flat pull-left" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
							// echo '<a class="btn-flat pull-left" href="/rma_add.php?on='.$rma_number.'">Receive</a>';
							if($o['type'] == 'Sales') { 
								$rma_select = 'SELECT rma_number FROM `returns` where order_type = "Sale" AND order_number = "'.$order_number.'"';
							} else if($o['type'] == 'Repair' || $o['type'] == 'Builds') { 
								$rma_select = 'SELECT rma_number FROM `returns` where order_type = "Repair" AND order_number = "'.$order_number.'"';
							}

							if(!$repair_billable && $o['repair']) {
								//If the repair  is not billable don't allow the user to create an RMA for this order
							} else {
								$rows = qdb($rma_select) or die(qe().$rma_select);
								$output = '
							<div class ="btn-group">
								<button type="button" class="btn-flat dropdown-toggle" data-toggle="dropdown">
	                              <i class="fa fa-question-circle-o"></i>
	                              <span class="caret"></span>
	                            </button>
								';
	                        
							$output .= '<ul class="dropdown-menu">';
							if(mysqli_num_rows($rows)>0){
									foreach ($rows as $rma) {
										$output .= '
										<li>
											<div class = "row rma-list-items">
												<div class = "col-md-3">
													<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-right">
														<i class="fa fa-list"></i>
													</a>
												</div>
												<div class = "col-md-6" style="padding-left:0px;padding-right:0px;">
													<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-right">
														RMA #'.$rma['rma_number'].'
													</a>	
												</div>
												<div class = "col-md-3 pull-left">
													<a href="/rma.php?on='.$rma['rma_number'].'" class = "pull-left">
														<i class="fa fa-truck"></i>
													</a>
												</div>
											</div>
										</li>
										';
									}
								}
								$output .= '<li>
												<a href="/rma.php?on='.$order_number.''.($o['repair'] ? '&repair=true' : ($o['type'] == 'Builds' ? '&repair=true' : '')).'">
													ADD RMA <i class ="fa fa-plus"></i>
												</a>
											</li>
			                        	</ul>
									</div>
								';
							}
							echo $output;
						}/* end if($order_number != "New" && ($o['type'] == 'Sales' || $o['type'] == 'Repair'))*/

						if($order_number != "New" && $o['purchase']){
							$bills_selector = 'SELECT * FROM `bills` WHERE po_number = '.prep($order_number).";";
							$rows = qdb($bills_selector);
							$output = '
								<div class ="btn-group">
									<button type="button" class="btn-flat dropdown-toggle" data-toggle="dropdown">
		                              <i class="fa fa-credit-card"></i>
		                              <span class="caret"></span>
		                            </button>
									';
                            
							$output .= '<ul class="dropdown-menu">';
							if(mysqli_num_rows($rows)){
								foreach ($rows as $bill) {
									$output .= '
										<li>
											<a href="/bill.php?bill='.$bill['bill_no'].'">
											Bill #'.$bill['bill_no'].' ('.format_date($bill['date_created'],'n/j/Y').') 
											</a>
										</li>';
								}
							}
							$output .= '
										<li>
											<a href="/bill.php?on='.$order_number.'&bill=new">
												<i class="fa fa-plus"></i> Add New Bill
											</a>
										</li>
									</ul>
								</div>
							';
							echo $output;
						} elseif ($o['invoice']) {
							
						}

						if($order_number != "New"){
							$query = 'SELECT * FROM payment_details WHERE order_number = '.prep($order_number).' AND order_type = "'.($o['type'] == 'Sales' ? 'so' : 'po').'";';
							$rows = qdb($query);
							$output = '
							<div class ="btn-group">
								<button type="button" class="btn-flat dropdown-toggle" data-toggle="dropdown">
	                              <i class="fa fa-usd" aria-hidden="true"></i>
	                              <span class="caret"></span>
	                            </button>';
	                            
							$output .= '<ul class="dropdown-menu">';
							if(mysqli_num_rows($rows) > 0){
								foreach ($rows as $payment) {
									$number = 0;
									$amount = 0;
									$type = '';
									$notes = '';
									$date = '';
									
									$query = 'SELECT * FROM payments p, payment_details d WHERE id = '.$payment['paymentid'].' AND paymentid = p.id;';
									$result = qdb($query) OR die(qe().' '.$query);
	
									if (mysqli_num_rows($result)>0) {
							        	$r = mysqli_fetch_assoc($result);
										$number = $r['number'];
										$amount = $r['amount'];
										$type = $r['payment_type'];
										$notes = $r['notes'];
										$ref = $r['ref_type'].' '.$r['ref_number'];
										$date = format_date($r['date']);
							        }
									
									$output .= '
											<li>
												<a style="cursor: pointer" class="paid-data" data-date="'.$date.'" data-ref="'.$ref.'" data-notes="'.$notes.'" data-type="'.$type.'" data-number="'.$number.'" data-amount="'.$amount.'" data-toggle="modal" data-target="#modal-payment">
													Payment #'.$payment['paymentid'].'
												</a>
											</li>';
								}
							}
							$output .= '
											<li>
												<a style="cursor: pointer" data-toggle="modal" class="new-payment" data-target="#modal-payment">
													<i class="fa fa-plus"></i> Add New Payment
												</a>
											</li>
										</ul>
									</div>
							';
							echo $output;
						}
?>
					
					
					
				</div>
				
				<div class="col-md-6 text-center">
					<?php
					echo"<h2 class='minimal' style='margin-top: 10px;'>";
					if(!$o['invoice'] && !$o['rtv']){
						if ($order_number=='New'){
							echo $order_number;
						} else {
							//echo getCompany($ORDER['companyid']);
						}
						echo " ".($o['short'] == 'build' ? ucfirst($o['short']) : strtoupper($o['short']));
						if ($order_number!='New' && !$build_number){
							echo "# $order_number";
						} else {
							echo "# $build_number";
						}

						if($status && $o['type'] == 'Repair' && $order_number!='New'){
							echo '<br>(<span class="ticket_status_'.(strpos(strtolower($status), 'unrepairable') !== false || strpos(strtolower($status), 'voided') !== false || strpos(strtolower($status), 'canceled') !== false ? 'danger' : (strpos(strtolower($status), 'trouble') ? 'warning' : 'success')).'">' .ucwords($status) . '</span>) ';
						}

						if ($order_number!='New'){
							echo("<br><span style = 'font-size:14px;'>".$ORDER['fcreated']."</span>");
						}
					} else if ($o['invoice']){
						echo("Invoice# ".$order_number);
					} else if ($o['rtv']){
						echo("RTV FROM PO #$origin");
					}
					if ((strtolower($status) == 'void' || strtolower($status) == 'voided') && $o['type'] != 'Repair'){
						echo ("<b><span style='color:red;'> [".strtoupper($status)."]</span></b>");
					}
					echo"</h2>";
					?>
				</div>
				<div class="col-md-3">
					<button class="btn-flat btn-sm <?=(strtolower($status) == 'void' || strtolower($status) == 'voided' || strpos(strtolower($status), 'canceled') !== false ? 'gray' : 'success');?> pull-right" id = "save_button" data-validation="left-side-main" style="margin-top:2%;margin-bottom:2%;">
						<?=($order_number=="New") ? 'Create' :'Save'?>
					</button>
					<?php if((strtolower($status) != 'voided' && strpos(strtolower($status), 'canceled') === false) && $status) { ?>
						<?php if($sales_order && $o['type'] == 'Repair') { ?>
							<a href="/shipping.php?on=<?=$sales_order;
							?>" class="btn-flat info pull-right" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-truck"></i> Ship</a>
						<?php } else if($tracking && $o['type'] == 'Repair') { ?>
							<div class="pull-right" style="margin-top: 15px;">
								<b>Shipped Tracking#</b> <?=$tracking;?>
							</div>
						<?php } else if($o['type'] == 'Repair' && $order_number!='New') { ?>
							<form action="repair_shipping.php" method="POST">
								<button type="submit" name="ro_number" value="<?=$order_number?>" class="btn-flat info pull-right" style="margin-top: 10px; margin-right: 10px;"><i class="fa fa-truck"></i> Ship</button>
							</form>
						<?php } ?>
					<?php } ?>
				</div>
			</div>
			
			
			<!-- Row declaration for splitting the two "halves of the page  -->
			<div class="row order-data remove-margin">
			
				<!--================== Begin Left Half ===================-->
				<div class="left-side-main col-md-3 col-lg-2" data-page="order">
					<?php $order_number = ($origin)? $origin: $order_number;?>
					<!-- Everything here is put out by the order creation ajax script -->
					<?=sidebar_out($order_number,$o['type'],$o['edit_mode'])?>
				</div>
				<!--======================= End Left half ======================-->
			
		
				<!--===================== Begin Right half =====================-->
				<div class="col-md-9 col-lg-10">
					
					<!-- Output the sales-rep dropdown in the top right corner -->
					<div class="forms_section">
						<?php if(!$o['invoice'] && !$o['repair']){?>
							<div style="float:right;padding-top:15px;">
							<div class="ui-select" style="width:125px; 'margin-bottom:0;">
								<?php
									$old_rep = "Select `sales_rep_id` from ".$o['order']." WHERE `".$o['id']."` = $order_number";
	                        		$rep_res = qdb($old_rep);

									if ($order_number != 'New'){
		                        		$rep_row = mysqli_fetch_assoc($rep_res);
		                        		$set_rep = $rep_row['sales_rep_id'];
		                        		//echo("<option>".$old_rep."</option>");
		                        	}
								?>
			                    <select id="sales-rep" data-creator = <?=$U['contactid']?> <?=($set_rep ? 'disabled' : '');?>>
	
			                        <?php
			                        	//REP OUTPUT
										$get_reps = "SELECT users.id userid, contacts.name name, contacts.id contactid FROM users, contacts ";
										$get_reps .= "WHERE users.contactid = contacts.id; ";		               
			                        	
			                        	$all_reps = qdb($get_reps) or die(qe()." $get_reps");
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
						<?php };?>
					</div> 
					
					<div class="table-responsive">
						<table class="table table-hover table-striped table-condensed" id="items_table" style="margin-top:1.5%;">
						<thead>
							<?php //if($o['type'] != 'invoice'){?>
		    				<th style='min-width:30px;'>#</th>		
		    				<th class='col-md-<?=($o['repair']?"7":"5")?>'>Item Information</th>
		    				<th class='col-md-2'>Delivery Date</th>
		    				<?php if(!$o['repair']): ?>
		    				<th class='col-md-1'>
			    				<?php
			    					if(!$o['invoice'] && $status != 'void'){
				    					$rootdir = $_SERVER['ROOT_DIR'];
				    					include_once($rootdir.'/inc/dropPop.php');
				    					echo(dropdown("conditionid","","full_drop","",false,"condition_global"));
			    					} else {
			    						echo "Condition";
			    					}
			    				?>
		    				</th>
		    				<th class='col-md-1'>
		    					<?php
		    						if($o['type'] != 'Invoice' || $status =='void'){
				    					$rootdir = $_SERVER['ROOT_DIR'];
				    					include_once($rootdir.'/inc/dropPop.php');
				    					echo(dropdown("warranty","","full_drop","",false,"warranty_global"));
		    						} else {
		    							echo "Warranty";
		    						}
		    					?>
	    					</th>
	    					<?php endif;?>
	    					<th class='col-md-1'>Qty</th>
		    				<th class='col-md-1'>Price</th>
	    					<th class='col-md-1'>Ext. Price</th>
	    					<th></th>
	    					<th></th>
	    					
	    				</thead>
	
			        	<tbody id="right_side_main" <?=($o['rtv'] ? 'data-rtvarray = '. json_encode($rtv_items) : '');?> style = "font-size:13px;">
			        	</tbody>
				        
				        <?php if(!$o['rtv'] && !$o['invoice']){ ?>
							<tfoot id = "search_input">
					            
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
								
					            <tr id ='search_row' style = 'padding:50px;background-color:#eff0f6;'>
					        		<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='' style='height:28px;padding:0;text-align:center;'></td>
							        <td id = 'search'>
							            <div class='input-group'>
							              <input type='text' class='form-control input-sm' id = 'go_find_me' placeholder='SEARCH FOR...' value='<?=$keyword?>'>
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
				        		<?php if(!$o['repair']):?>
					        		<td><?=$condition_dropdown;?></td>
					        		<td><?=$warranty_dropdown;?></td>
					        	<?php endif; ?>
					        		<td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_qty' id = 'new_item_qty' placeholder='QTY' value = ''></td>
					            	<td>
						                <div class='input-group'>
						                    <span class='input-group-addon'>$</span>
						                    <input class='form-control input-sm' type='text' name = 'ni_price' placeholder='0.00' id = 'new_item_price' value=''>
						                </div>
						            </td>
					        		<td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_ext' id = 'new_item_total' placeholder='0.00'></td>
					                <td colspan='2' id = 'check_collumn'> 
					                    <a class='btn-sm btn-flat success pull-right multipart_sub' >
					                    <i class='fa fa-save fa-4' aria-hidden='true'></i></a>
					                </td>
								</tr>
							    <!-- Adding load bar feature here -->
						   	 	<tr class='search_loading'><td colspan='12'><span style='text-align:center; display: none; padding-top: 10px;'>Loading...</span></td></tr>
					    
								<!-- dummy line for nothing found -->
						   	 	<tr class='nothing_found' style='display: none;'><td colspan='12'><span style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Nothing Found</span></td></tr>
								<tr id = 'subtotal_row' style=''>
				        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
								<?php endif; ?>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td style='text-align:right;'>Subtotal:</td>
					                <td><input class='form-control input-xs' readonly='readonly' tabIndex='-1' type='text' id ='subtotal' name='np_subtotal' placeholder='0.00'></td>
					                <td></td>
					            </tr>
					            <tr id = 'first_fee_field' style=''>
				        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
								<?php endif; ?>
					                <td></td>
					                <td></td>
					                <td colspan="2">
										<input class='form-control input-xs hidden' tabIndex='-1' type='text' id ='first_memo' name='first_memo'placeholder='Memo'>
					                </td>
					                <td style='text-align:right;'>
					                	<select class='form-control input-xs' tabIndex='-1' type='text' id ='first_fee_label' name='first_fee_label' data-scid='<?=$dataid[0]['id']?>' value="<?=$dataid[0]['label']?>">
					                		<option>Add Charge...</option>
					                		<option <?=($dataid[0]['label'] == "CC Proc Fee"?"selected":"");?>>CC Proc Fee</option>
					                		<option <?=($dataid[0]['label'] == "Sales Tax"?"selected":"");?>>Sales Tax</option>
					                		<option <?=($dataid[0]['label'] == "Freight"?"selected":"");?>>Freight</option>
											<option <?=($dataid[0]['label'] == "Restocking Fee"?"selected":"");?>>Restocking Fee</option>
					                	</select>
					                </td>
					                <td>
					                	<div class="input-group">
						                    <span class="input-group-addon">$</span>
					                		<input class='form-control input-xs fee_inputs' tabIndex='-1' type='text' id ='first_fee_amount' name='first_fee_amount' placeholder='0.00' value="<?=$dataid[0]['text']?>"></td>
						                </div>
					                <td></td>
					            </tr>
					            
					            <tr id = 'second_fee_field' style=''>
				        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
								<?php endif; ?>
					                <td></td>
					                <td></td>
					                <td colspan="2">
										<input class='form-control input-xs hidden' tabIndex='-1' type='text' id ='second_memo' name='second_memo' placeholder='Memo'>
					                </td>
					                <td style='text-align:right;'>
					                	<select class='form-control input-xs' tabIndex='-1' type='text' id ='second_fee_label' name='second_fee_label' data-scid='<?=$dataid[1]['id']?>' value='<?=$dataid[1]['label']?>'>
					                		<option>Add Charge...</option>
					                		<option <?=($dataid[1]['label'] == "CC Proc Fee"?"selected":"");?>>CC Proc Fee</option>
					                		<option <?=($dataid[1]['label'] == "Sales Tax"?"selected":"");?>>Sales Tax</option>
					                		<option <?=($dataid[1]['label'] == "Freight"?"selected":"");?>>Freight</option>
											<option <?=($dataid[0]['label'] == "Restocking Fee"?"selected":"");?>>Restocking Fee</option>
					                	</select>
					                </td>
					                <td>
					                	<div class="input-group">
						                    <span class="input-group-addon">$</span>
					                		<input class='form-control input-xs fee_inputs' tabIndex='-1' type='text' id ='second_fee_amount' name='second_fee_amount' placeholder='0.00' value='<?=$dataid[1]['text']?>'></td>
						                </div>
					                <td></td>
					            </tr>
					            
					            <tr id = 'freight_row' style=''>
				        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
								<?php endif; ?>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td style='text-align:right;'>Freight:</td>
					                <td>
					                	<div class="input-group">
						                    <span class="input-group-addon">$</span>
					                		<input class='form-control input-xs' tabIndex='-1' type='text' id ='freight' name='np_freight' value='<?=format_price(getFreightTotal($order_number));?>' placeholder='0.00' readonly>
						                </div>
				                	</td>
					                <td></td>
					            </tr>
					            <tr id = 'totals_row' style=''>
				        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
								<?php endif; ?>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td></td>
					                <td style='text-align:right;'>Total:</td>
					                <td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' id ='order_total' name='np_total' placeholder='0.00'></td>
					                <td></td>
					            </tr>
							</tfoot>
						<?php } ?>
				   </table>
				</div>
				<?php if($RMA_history): ?>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-condensed">
							<thead>
								<th>RMA #</th>
								<th>Description</th>
								<th>Date</th>
								<th>Serial</th>
								<th>Disposition</th>
								<th>Reason</th>
							</thead>

							<tbody>
								<?php foreach($RMA_history as $history): ?>
									<tr>
										<td><?=$history['rma_number']?></td>
										<td><?=display_part(current(hecidb($history['partid'], 'id')));?></td>
										<td><?=format_date($history['created']);?></td>
										<td><?=getSerial($history['inventoryid']);?></td>
										<td><?=getDisposition($history['dispositionid']);?></td>
										<td><?=$history['reason']?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
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
				if(confirm("Are you sure you want to void/unvoid this order?")){
			    	$.post('/json/void-order.php',{"number" : number,"type" : type}, function(data) {
			    		//alert(data);
							window.location.href=window.location.href;
						}
					);
				}
			});
 
			(function($){
				$(document).on('click', '.create_invoice', function(){
					var order_number = $(this).data("order");

					$.ajax({
						type: "POST",
						url: '/json/invoice_creation.php',
						data: {
			   		    	"order_number" : order_number,
						},
						dataType: 'json',
						success: function(data) {
							//alert('added invoice');
							location.reload();
						}
					});
				});

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
				
				$(document).on("change", ".payment-type", function() {
					var placeholder = '';
					
					if($(this).val() == "Check") {
						placeholder = "Check #";
					} else if($(this).val() == "Wire Transfer") {
						placeholder = "Ref #";
					} else if($(this).val() == "Credit Card") {
						placeholder = "Appr Code";
					} else if($(this).val() == "Paypal") {
						placeholder = "Transaction #";
					} else {
						placeholder = "Other";
					}
					
		            $('.payment-placeholder').attr('placeholder', placeholder);
		        });
		        
		        $(document).on("click", ".paid-data", function() {
					var number = $(this).data('number');
					var amount = $(this).data('amount');
					var type = $(this).data('type');
					var ref = $(this).data('ref');
					var notes = $(this).data('notes');
					var date = $(this).data('date');
					
					$('select[name="payment_type"]').val(type);
					$('input[name="payment_ID"]').val(number);
					$('input[name="payment_amount"]').val(amount);
					$('input[name="payment_date"]').val(date);
					
					$('textarea[name="notes"]').val(notes);
					
					$('input[name="reference_button"][value="' + ref + '"]').prop('checked', true);
		        });
		        
		        $(document).on("click", ".new-payment", function() {
					$('select[name="payment_type"]').val('Wire Transfer');
					$('input[name="payment_ID"]').val('');
					$('input[name="payment_amount"]').val('');
					$('input[name="payment_date"]').val($('input[name="payment_date"]').data('date'));
					
					$('textarea[name="notes"]').val('');
					
					$('input[name="reference_button"]').prop('checked', false);
		        });
		        
		        //$(document).load(function(){
		        if($('body').hasClass('void-order')) {
		        	//Has a void
		        	$('.order-data input').prop('readonly', 'true');
		        	$('.order-data select').prop('disabled', 'true');
		        	$('.order-data textarea').prop('disabled', 'true');
		        	$('.order-data #mismo').prop('disabled', 'true');
		        	
		        	//Can use php if statment in the button creation
		        	$('.table-header button').prop('disabled', 'true');
		        	$('.table-header button').addClass('gray');
		        	$('.table-header button').removeClass('success');
		        	$('.forms_edit').find(".fa").hide();
		        	$('.forms_delete').find(".fa").hide();
		        }
		        //});
			})(jQuery);

		</script>

	</body>
</html>
