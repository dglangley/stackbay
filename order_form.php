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
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getUser.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getDisposition.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/getCharges.php';
	include_once $rootdir.'/inc/getSerial.php';

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

	if (isset($_REQUEST['purchase_request']) AND $_REQUEST['purchase_request']) {
		$purchase_request = $_REQUEST['purchase_request'];
	}

	// Test print for purchase request only
	// if(! empty($purchase_request)) {
	// 	print '<pre>' . print_r($_REQUEST, true) . '</pre>';
	// }

	//High level order parameters
	$o = o_params(grab('ps',"s"));
	$order_number = grab('on','New');
	$build_number;
	$sales_order;
	$tracking;

	$repair_billable;
	$repair_item_id;
	$received_inventory;

	//3 is consider Operations in the user_privilege table
	if(!in_array("1", $USER_ROLES) && !in_array("5", $USER_ROLES) && !in_array("7", $USER_ROLES) && !in_array("4", $USER_ROLES)) {
		if(in_array("3", $USER_ROLES)){
			if($o['type'] == "Sale"){
			 	header('Location: /shipping.php?on='.$order_number.'&ps=s');
			} else if($o['type'] == "Purchase"){
			 	header('Location: /inventory_add.php?on='.$order_number.'&ps=p');
			} else if($o['type'] == "Repair"){
/*
			 	header('Location: /repair_add.php?on='.$order_number);
*/
			} else if($o['rtv']){
			
			} else {
				exit;
			}
		}
	}

	include_once $rootdir.'/inc/invoice.php';
	
	if(strtolower($o['type']) == 'rtv'){
	 	$status = 'Active';
		$origin = $order_number;
		$order_number = "New";
		//If there are items to be Returned to Vendor, we gather the items in through a passed JSON parameter
		$rtv_items = $_REQUEST['partid'];
	} else if ($o['type'] == "Invoice"){
	 	$inv_info = getInvoice($order_number);
	 	$origin = $inv_info['order_number'];
	} else if ($o['type'] == "Sale" AND $order_number!='New') {
		$sales_order = $order_number;
	} else if($o['type'] == "Repair") {

		$repair_billable = true;

		$query = "SELECT ro.termsid, ri.price, ri.id, ro.ro_number FROM repair_orders ro, repair_items ri WHERE ro.ro_number = ".prep($order_number)." AND ro.ro_number = ri.ro_number;";
		$result = qdb($query) or die(qe());

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$repair_item_id = $result['id'];

			//print_r($result);
			if($result['termsid'] == '15' && $result['price'] == '0.00')
				$repair_billable = false;
		}

	 	//Check to see if a sales_item record has been created for this item
	 	//echo $status . 'test';
		if($status || $o['type'] == "Repair") {
			$query = "SELECT so_number FROM sales_items s, repair_items r WHERE s.ref_1_label = 'repair_item_id' AND s.ref_1 = r.id AND r.ro_number = ".prep($order_number).";";
			$result = qdb($query) or die(qe());

			//echo $query;
			if (mysqli_num_rows($result)) {
				$result = mysqli_fetch_assoc($result);
				$sales_order = $result['so_number'];
			} else {
				$query = "SELECT tracking_no FROM packages WHERE order_type = 'Repair' AND order_number = ".prep($order_number).";";
				$result = qdb($query) or die(qe());
				if (mysqli_num_rows($result)) {
					$result = mysqli_fetch_assoc($result);
					$tracking = ($result['tracking_no'] ? $result['tracking_no'] : 'N/A');
				} else {
					$query = "SELECT id inventoryid FROM inventory WHERE repair_item_id = ".prep($repair_item_id)." AND status <> 'in repair';";
					$result = qdb($query) or die(qe());

					if (mysqli_num_rows($result)) {
						$result = mysqli_fetch_assoc($result);
						$received_inventory = $result['inventoryid'];
					}
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

	function format($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);
	    $display = "<span class = 'descr-label'>".$r['part']." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary(substr($r['description'],0,30)).'</span></div>';

	    return $display;
	}

	function getRMA($order_number, $type){
		$RMA = array();

/*
		$query = "SELECT *, date_changed inv_date FROM returns as r, return_items as i ";
		$query .= "LEFT JOIN inventory_history h ON i.inventoryid = h.invid ";
		$query .= "WHERE r.order_number = ".prep($order_number)." AND r.order_type = ".prep($type)." AND r.rma_number = i.rma_number ";
		$query .= "GROUP BY h.invid, r.rma_number; ";
		$query .= "AND h.field_changed = 'returns_item_id' AND h.value = i.id ";
*/
		$query = "SELECT *, i.id return_item_id FROM returns as r, return_items as i ";
		$query .= "WHERE r.order_number = ".prep($order_number)." AND r.order_type = ".prep($type)." AND r.rma_number = i.rma_number; ";
		$result = qdb($query) OR die(qe());
		while ($row = $result->fetch_assoc()) {
			$row['inv_date'] = '';

			$query2 = "SELECT * FROM inventory_history h ";
			$query2 .= "WHERE invid = '".$row['inventoryid']."' AND h.field_changed = 'returns_item_id' AND h.value = '".$row['return_item_id']."' ";
			$query2 .= "GROUP BY invid; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$row['inv_date'] = $r2['date_changed'];
			}
			$RMA[] = $row;
		}

		return $RMA;
	}

	$ORDER = array('fcreated'=>'','companyid'=>0);
	$dataid = array();
	$charges = array();
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
		$q = "SELECT * FROM $table WHERE $id = '".$order_number."'; ";
		$result = qdb($q) or die(qe()." | $q");
		if (mysqli_num_rows($result)){
			$ORDER = mysqli_fetch_assoc($result);
			$ORDER['fcreated'] = format_date($ORDER['created'],"D n/j/y g:ia");
		}
		
		if($o['sales'] || $o['purchase']){
			$charges = getCharges($order_number, $o['type']);
		}
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

	$RMA_history = getRMA($order_number, $o['type']);

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
			AND (i.order_number = $order_number AND i.order_type = ".prep($o['type']).")
/*
			AND p.order_number = $order_number
			AND p.order_type = ".prep($o['type'])."
*/
			GROUP BY package_no;
		";
	 	$invoices = qdb($query) or die(qe()." | $query");
		foreach($invoices as $shipment) {
		 	$output .= "
		 		<li>
	 				<a target='_blank' href='invoice.php?invoice=".$shipment['invoice_no']."'>
	 					Shipment ".$shipment['package_no']." Invoice ".$shipment['invoice_no']. " (".format_date($shipment['date_invoiced'],'n/j/Y').")
	 				</a>
	 			</li>
		 	";
		 }

	 	return $output;
	 }

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
				include_once $rootdir.'/modal/repair_receive.php';
				include_once $rootdir.'/modal/trello.php';
			?>
			<div class="row-fluid table-header" id = "order_header" style="width:100%;background-color:<?=$o['color']?>; height:60px">
				<div class="col-md-4">
					<?php
						if($order_number != "New"){
							if($o['type'] == 'Invoice'){
								echo '<a href="/order_form.php?on='. $origin .'&ps=s" class="btn btn-default btn-sm pull-left"><i class="fa fa-pencil"></i></a> ';
							}

							if (($o['type']=='Repair' AND strtolower($status)<>'completed' AND strtolower($status)<>'repaired') OR ($o['type']<>'Repair' AND $o['type']<>'Sale')) {
								echo '<a href="/'.$o['url'].'.php?on=' . ($build_number ? $build_number . '&build=true' : (($origin)? $origin : $order_number)) . '" class="btn btn-default btn-sm pull-left text-warning"><i class="fa fa-qrcode"></i> Receive</a> ';
							}

							if (strtolower($status) != 'voided' && strtolower($status) != 'canceled' && $status) {
								if ($o['type']=='Sale' AND $order_number!='New') {
									echo '<a class="btn btn-primary btn-sm" href="/shipping.php?on='.$order_number.'"><i class="fa fa-truck"></i> Ship</a>';
								} else if ($o['type']=='Repair' AND (strtolower($status)=='completed' OR strtolower($status)=='repaired') AND ($sales_order OR $tracking OR $received_inventory OR $order_number!='New')) {
									if ($order_number!='New') { echo '<form id="repair_ship" action="repair_shipping.php" method="POST">'; }

									echo '
									<div class="btn-group pull-left">
										<button type="button" class="btn btn-default btn-sm text-success dropdown-toggle" data-toggle="dropdown">
											<i class="fa fa-truck"></i> Ship Manager
											<span class="caret"></span>
			                            </button>
			                            <ul class="dropdown-menu">
									';

									if ($sales_order) {
										echo '
			                            	<li>
												<a href="/shipping.php?on='.$sales_order.'"><i class="fa fa-truck"></i> Ship</a>
											</li>
										';
									} else if ($tracking) {
										echo '
			                            	<li>
												<span style="padding: 3px 5px;">Trk# '.$tracking.'</span>
											</li>
										';
									} else if ($received_inventory) {
										echo '
			                            	<li>
												<span style="padding: 3px 5px;">Returned to Stock</span>
											</li>
										';
									} else if ($order_number!='New') {
										echo '
				                            	<li>
													<a class="ship" data-ship="ship" href="/repair_shipping.php?ro_number='.$order_number.'"><i class="fa fa-truck"></i> Ship</a>
												</li>
												<li>
													<a class="ship" data-ship="stock" id="stock_order" type="submit" name="ro_number" value="'.$order_number.'" href="#"><i class="fa fa-qrcode"></i> Return to Stock</a>
												</li>
										';
									}
									echo '
										</ul>
									</div>
									';
								}
							}

							if($o['type'] == 'Builds' OR $o['type']=='Repair'){
								$build_on = '';
								if ($o['type']=='Builds') { $build_on = '&build=true'; }
								echo '<a href="/repair.php?on='. ($build_number ? $build_number : $order_number) . $build_on .'" class="btn btn-primary btn-sm pull-left"><i class="fa fa-wrench"></i> Tech View</a> ';
							} else if ($o['type']=='Purchase') {
								echo '<a target="_blank" href="/docs/'.strtoupper($o['short']).$order_number.'.pdf" class="btn btn-brown btn-sm pull-left"><i class="fa fa-file-pdf-o"></i></a>';
							}
							if($o['type'] == 'Builds'){
								echo '<a href="/builds_management.php?on='.$build_number.'" class="btn btn-default btn-sm pull-left">Edit</a>';
							}
						}

						if($order_number != "New" && ($o['sales'] || $o['repair'] || $o['type'] == 'Builds')){
							$output = '
								<div class ="btn-group">
									<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
		                              <i class="fa fa-folder-open-o"></i>
		                              <span class="caret"></span>
		                            </button>
									<ul class="dropdown-menu">
							';
							$rows = get_assoc_invoices($order_number, $o['type']);
							foreach ($rows as $inv) {
								$output .= '
										<li>
											<a href="/docs/INV'.$inv['invoice_no'].'.pdf">
												Invoice# '.$inv['invoice_no'].' ('.format_date($inv['date_invoiced'],'n/j/Y').') 
											</a>
										</li>
								';
							}

//							$output .= getPackagesFix($order_number, $o['type']);

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
						}/* end if($order_number != "New" && ($o['type'] == 'Sale' || $o['type'] == 'Repair'))*/

						if($order_number != "New" && $o['purchase']){
							$bills_selector = 'SELECT * FROM `bills` WHERE po_number = '.prep($order_number).";";
							$rows = qdb($bills_selector);
							$output = '
								<div class ="btn-group">
									<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
		                              <i class="fa fa-folder-open-o"></i>
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
											<a href="/bill.php?on='.$order_number.'&bill=">
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
							$query = 'SELECT * FROM payment_details WHERE order_number = '.prep($order_number).' AND order_type = "'.($o['type'] == 'Sale' ? 'so' : 'po').'";';
							$rows = qdb($query);
							if(mysqli_num_rows($rows) > 0){
								$output = '
							<div class ="btn-group">
								<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
	                              <i class="fa fa-usd" aria-hidden="true"></i>
	                              <span class="caret"></span>
	                            </button>';
	                            
								$output .= '<ul class="dropdown-menu">';
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
/*
											<li>
												<a style="cursor: pointer" data-toggle="modal" class="new-payment" data-target="#modal-payment">
													<i class="fa fa-plus"></i> Add New Payment
												</a>
											</li>
*/
								$output .= '
										</ul>
									</div>
								';
								echo $output;
							}
						}
?>
					
					
					
				</div>
				
				<div class="col-md-4 text-center">
					<?php
					echo"<h2 class='minimal' style='margin-top: 10px;'>";
					if(!$o['invoice'] && !$o['rtv']){
						if ($order_number=='New'){
							echo $order_number;
						} else {
							//echo getCompany($ORDER['companyid']);
						}
						echo " ".($o['short'] == 'build' ? ucfirst($o['short']) : strtoupper($o['short']));
						if ($order_number!='New') {
							if (!$build_number){
								echo "# $order_number";
							} else {
								echo "# $build_number";
							}
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
					if ($order_number!='New'){
						echo '<div class="info text-center" style="font-size:14px;">'.$ORDER['fcreated'].'</div>';
					}
					?>
				</div>
				<div class="col-md-4 text-right">
<?php
					if ($order_number!='New' AND ($o['type']=='Sale' OR $repair_billable)) {
						if($o['type'] == 'Sale') { 
							$rma_select = 'SELECT rma_number FROM `returns` where order_type = "Sale" AND order_number = "'.$order_number.'"';
						} else if($o['type'] == 'Repair' || $o['type'] == 'Builds') { 
							$rma_select = 'SELECT rma_number FROM `returns` where order_type = "Repair" AND order_number = "'.$order_number.'"';
						}
						$rows = qdb($rma_select) or die(qe().$rma_select);
						echo '
							<div class ="btn-group">
								<button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
	                              <i class="fa fa-question-circle-o"></i>
									Support
	                              <span class="caret"></span>
	                            </button>
						';
	                        
						echo '<ul class="dropdown-menu text-left">';
						if(mysqli_num_rows($rows)>0){
							foreach ($rows as $rma) {
								echo '
										<li>
											<div class = "row rma-list-items">
												<div class = "col-md-3">
													<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-right">
														<i class="fa fa-pencil"></i>
													</a>
												</div>
												<div class = "col-md-6" style="padding-left:0px;padding-right:0px;">
													<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-right">
														RMA #'.$rma['rma_number'].'
													</a>	
												</div>
												<div class = "col-md-3 pull-left">
													<a href="/rma.php?rma='.$rma['rma_number'].'" class = "pull-left">
														<i class="fa fa-truck"></i>
													</a>
												</div>
											</div>
										</li>
								';
							}
						}
						echo '<li>
								<a href="/rma.php?on='.$order_number.($o['repair'] ? '&repair=true' : ($o['type'] == 'Builds' ? '&repair=true' : '')).'">
									<i class ="fa fa-plus"></i>
									Create RMA
								</a>
							</li>
                       	</ul>
					</div>
						';
					}
?>

					<button type="button" class="btn btn-success <?=(strtolower($status) == 'void' || strtolower($status) == 'voided' || strpos(strtolower($status), 'canceled') !== false ? 'gray' : 'success');?>" id = "save_button" data-validation="left-side-main">
						<i class="fa fa-save"></i> <?=($order_number=="New") ? 'Create' :'Save'?>
					</button>
				</div>
			</div>

<?php
			if($status && $o['type'] == 'Repair' && $order_number!='New'){
				echo '
			<div class="alert alert-default" style="padding:5px; margin:0px">
				<h3 class="text-center">
					<span class="ticket_status_'.(strpos(strtolower($status), 'unrepairable') !== false || strpos(strtolower($status), 'voided') !== false || strpos(strtolower($status), 'canceled') !== false ? 'danger' : (strpos(strtolower($status), 'trouble') ? 'warning' : 'success')).'">' .ucwords($status) . '</span>
				</h3>
			</div>
				';
			}
?>
			
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
            			<h3 class="pull-left text-center" style="width:80%; margin:20px 0px 0px 0px">Order view is getting a new look! <i class="fa fa-eye"></i> <a class="btn btn-sm btn-info" href="/order.php?order_number=<?=$_REQUEST['on'];?>&order_type=<?=$_REQUEST['ps'];?>">Preview it now.</a></h3>
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
			                    <select id="sales-rep" data-creator = <?=$U['contactid']?> >
	
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
		    				<?php //if(! empty($_REQUEST['purchase_request'])) { ?>		
			    				<th class='col-md-<?=($o['repair']?"4":"2")?>'>Item Information</th>
			    				<th class='col-md-2'>Ref 1</th>
			    				<th class='col-md-2'>Ref 2</th>
			    				<th class='col-md-1'>Delivery Date</th>
		    				<?php //} else { ?>
		    					<!-- <th class='col-md-<?=($o['repair']?"7":"5")?>'>Item Information</th>
			    				<th class='col-md-2'>Delivery Date</th> -->
		    				<?php //} ?>	
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

			        		<?php 
			        			if(! empty($purchase_request)) { 
			        				foreach ($purchase_request as $partid => $row) {
			        					//echo $partid . '<br>';
			        					foreach ($row as $ro_number => $row2) {
			        						//echo $ro_number . '<br>';
			        						foreach ($row2 as $qty => $row3) {
			        							foreach($row3 as $pr_id => $repair_item_id) {
			        		?>
									        		<tr class="easy-output " data-record="new">
												        <td class="line_line" data-line-number=""></td>
												        <td class="line_part" data-search="<?=$partid;?>" data-record="new">
												        	<?=format($partid);?>
												        	<!-- <span class="descr-label">ER-B &nbsp; </span> &nbsp; <div class="description desc_second_line descr-label" style="color:#aaa;">MISC &nbsp;  <span class="description-label">BALDOR <abbr title="ALTERNATING CURRENT">AC</abbr> GEARMOTOR <abbr title="LONG-HAUL">L/H</abbr> 1/3 HP 115/230 VOLTS</span></div></td> -->
											    
											        	<td class="line_ref_1">
											        		<div class="col-md-6">
											        			<input class="form-control input-sm" type="text" name="" value="<?=$ro_number;?>" readonly>
											        			<input class="form-control input-sm ref_1" type="hidden" name="ref_1" value="<?=$repair_item_id;?>">
											        			<!-- Hidden Field to contain all the purchase request values -->
											        			<input class="form-control input-sm pr_id" type="hidden" name="pr_id" value="<?=$pr_id;?>">
											        		</div>
											        		<div class="col-md-6">
											        			<select class="form-control input-sm" readonly>
											        				<option>RO</option>
											        			</select>
											        			<input class="form-control input-sm ref_1_label" type="hidden" name="ref_1_label" value="repair_item_id">
											        		</div>
											        	</td>
											        	<td class="line_ref_2">
											        		<div class="col-md-6">
											        			<input class="form-control input-sm ref_2" type="text" name="ref_2" value="">
											        		</div>
											        		<div class="col-md-6">
											        			<select name="ref_2_label" class="form-control input-sm ref_2_label">
											        				<option value="" selected disabled>Optional</option>
											        				<option value="PN">PN</option>
											        				<option value="SAP">SAP</option>
											        				<option value="PO">PO</option>
											        				<option value="FF">FF</option>
											        			</select>
											        		</div>
											        	</td>
											        	<td class="line_date" data-date="<?=addBusinessDays($now, 4);?>"><?=addBusinessDays($now, 4);?></td>
														<td class="line_cond" data-cond="2">Used (Untested)</td>
											        	<td class="line_war" data-war="4">30 Days</td>
											        	<td class="line_qty" data-stock="0" data-qty="<?=$qty;?>"><?=$qty;?></td>
											            <td class="line_price">$0.00</td>
											            <td class="line_linext">$0.00</td>
											            <td class="line_ref" style="display: none;" data-label=""></td>
											            <td class="forms_edit" style="cursor: pointer;">
											            	<i class="fa fa-pencil fa-4" aria-hidden="true"></i></td><td class="forms_trash" style="cursor: pointer;"><i class="fa fa-trash fa-4" aria-hidden="true"></i>
											            </td>
										            </tr>

									            	<tr class="lazy-entry" style="display:none;">
														<td style="padding:0;"><input class="form-control input-sm" type="text" name="ni_line" placeholder="#" value="" data-value="" style="height:28px;padding:0;text-align:center;"></td>
											            <td class="search_collumn" colspan="3">
											            	<div class="item-selected">
																<select class="item_search input-xs">
																	<option data-search="<?=$partid;?>"><?=getPart($partid);?></option>
																</select>
															</div>
														</td>
											            <td>				
											            	<div class="input-group date datetime-picker-line">
													            <input type="text" name="ni_date" class="form-control input-sm" value="<?=format_date($now);?>" data-value="<?=format_date($now);?>" style="min-width:50px;">
													            <span class="input-group-addon">
													                <span class="fa fa-calendar"></span>
													            </span>
												            </div>
													    </td>
													    <td>
													    	<div class="">
													    		<select id="conditionid" name="condition" class="form-control input-sm conditionid">    
													    			<option value="-7">Pending Test</option>
													    			<option value="-6">Internal Use Only</option>
													    			<option value="-5">Needs Repair</option>
													    			<option value="-4">Scrapped</option>
													    			<option value="-3">Use for Parts (Unrepairable)</option>
													    			<option value="-2">Physical Damage</option>
													    			<option value="-1">Tested Bad</option>
													    			<option value="1">New</option>
													    			<option selected="" value="2">Used (Untested)</option>
													    			<option value="3">Tested Good</option>
													    			<option value="4">Refurbished</option>
													    			<option value="5">Repaired (Untested)</option>
													    			<option value="6">Repaired (Tested)</option>
													    			<option value="7">Unknown</option>
									    			    		</select>
									    	        		</div>
									    	        	</td>
											            <td>
											            	<div class="">
											            		<select id="warranty" class="form-control input-sm warranty ">	    
											            			<option value="1">AS IS</option>
											            			<option value="2">5 Days</option>
											            			<option value="3">14 Days</option>
											            			<option selected="" value="4">30 Days</option>
											            			<option value="5">45 Days</option>
											            			<option value="6">60 Days</option>
											            			<option value="7">90 Days</option>
											            			<option value="8">120 Days</option>
											            			<option value="9">6 Months</option>
											            			<option value="10">1 Year</option>
											            			<option value="11">2 Years</option>
											            			<option value="12">3 Years</option>
											            			<option value="13">Lifetime</option>
											            			<option value="14">N/A</option>
									    			    		</select>
									    	        		</div>
									    	        	</td>
									    	        	<td><input class="form-control input-sm oto_qty" type="text" name="ni_qty" placeholder="QTY" value="<?=$qty;?>" data-value="<?=$qty;?>"></td>
											            <td><input class="form-control input-sm oto_price" type="text" name="ni_price" placeholder="0.00" value="0.00" data-value="0.00"></td>
											            <td><input class="form-control input-sm oto_ext" readonly="readonly" type="text" name="ni_ext" placeholder="0.00"></td>
											            <td style="display: none;" data-label=""><input class="form-control input-sm line_ref_1" readonly="readonly" type="text" name="" placeholder="0.00" value=""></td>
														<td colspan="2" id="check_collumn">
															<div class="btn-group">
																<a class="btn-flat danger pull-right line_item_unsubmit" style="padding: 3px;margin-left:2px;">
																	<i class="fa fa-minus fa-4" aria-hidden="true"></i>
																</a>
																
																<a class="btn-flat success pull-right line_item_submit" style="padding: 3px;">
																	<i class="fa fa-save fa-4" aria-hidden="true"></i>
																</a>
															</div>
														</td>
													</tr>
							<?php 	
												}
											}
			        					}
			        				}
		        				} 
		        			?>
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
							        <td colspan='3' id = 'search'>
							            <div class='input-group' style="width: 100%;">
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
					                    <a class='btn btn-sm btn-success pull-right multipart_sub' >
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
					                <td></td>
					                <td></td>
					                <td style='text-align:right;'>Subtotal:</td>
					                <td><input class='form-control input-xs' readonly='readonly' tabIndex='-1' type='text' id ='subtotal' name='np_subtotal' placeholder='0.00'></td>
					                <td></td>
					            </tr>
		            	<?php if($o['sales'] || $o['purchase']):?>
					            <tr id = 'first_fee_field' style=''>
					        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
									<?php endif; ?>
					                <td></td>
					                <td></td>
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
		            <?php endif; ?>
					            <tr id = 'freight_row' style=''>
				        		<?php if(!$o['repair']):?>
					                <td></td>
					                <td></td>
								<?php endif; ?>
					                <td></td>
					                <td></td>
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
					<h4 class="text-center">Returns and Credits</h4><br/>
					<div class="table-responsive">
						<table class="table table-hover table-striped table-condensed">
							<thead>
							<tr class="bg-warning">
								<th>Date</th>
								<th>RMA #</th>
								<th>Description</th>
								<th>Disposition</th>
								<th>Serial</th>
								<th>Status</th>
								<th>Action</th>
							</tr>
							</thead>

							<tbody>
								<?php foreach($RMA_history as $history): ?>
									<?php
										$status = 'Pending';
										if ($history['inv_date']) {
											$status = '<strong>'.format_date($history['inv_date'],'D n/d/y').'</strong> Received back<BR>';

											$query = "SELECT * FROM inventory_history h ";
											$query .= "WHERE invid = '".$history['inventoryid']."' AND field_changed RLIKE 'item_id' ";
											$query .= "AND date_changed > '".$history['inv_date']."' AND (field_changed <> 'returns_item_id' OR value <> '".$history['id']."') ";
											$query .= "ORDER BY date_changed ASC; ";
											$result = qdb($query) OR die(qe().'<BR>'.$query);
											while ($r = mysqli_fetch_assoc($result)) {
												$row_info = '<strong>'.format_date($r['date_changed'],'D n/d/y').'</strong> ';

												if ($r['field_changed']=='sales_item_id') {
													$query2 = "SELECT so_number FROM sales_items WHERE id = '".$r['value']."' ";
													if ($o['type']=='Sale') { $query2 .= "AND so_number <> '".$order_number."' "; }
													$query2 .= "; ";
													$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
													if (mysqli_num_rows($result2)==0) { continue; }
													$r2 = mysqli_fetch_assoc($result2);
													$row_info .= 'Sold on SO'.$r2['ro_number'].' <a href="/SO'.$r2['so_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a>';
												} else if ($r['field_changed']=='repair_item_id') {
													$query2 = "SELECT ro_number FROM repair_items WHERE id = '".$r['value']."' ";
													if ($o['type']=='Repair') { $query2 .= "AND ro_number <> '".$order_number."' "; }
													$query2 .= "; ";
													$result2 = qdb($query2) OR die(qe().'<BR>'.$query2);
													if (mysqli_num_rows($result2)==0) { continue; }
													$r2 = mysqli_fetch_assoc($result2);
													$row_info .= 'Repaired on RO'.$r2['ro_number'].' <a href="/RO'.$r2['ro_number'].'" target="_new"><i class="fa fa-arrow-right"></i></a>';
												} else {
												}

												$status .= $row_info.'<BR>';
											}
										}
										$action = '';
										if ($history['dispositionid']==1) {
											// look for credits issued against Credit disposition, and if exists then link to it
											$query = "SELECT * FROM credits c, credit_items i ";
											$query .= "WHERE c.order_number = '".$order_number."' AND c.order_type = '".$o['type']."' AND rma_number = '".$history['rma_number']."' ";
											$query .= "AND c.id = i.cid AND return_item_id = '".$history['id']."'; ";
											$result = qdb($query) OR die(qe().'<BR>'.$query);
											if (mysqli_num_rows($result)>0) {
												$r = mysqli_fetch_assoc($result);
												$action = '<a href="/docs/CM'.$r['cid'].'.pdf" target="_new"><i class="fa fa-file-pdf-o"></i></a>';
											} else if ($history['inv_date']) {//if already received back, eligible for credit
												$action = '<a href="/credit.php?rma_number='.$history['rma_number'].'&order_number='.$order_number.'&order_type='.$o['type'].'" '.
													'class="btn btn-danger btn-xs" data-toggle="tooltip" data-placement="bottom" title="Issue Credit Memo">'.
													'<i class="fa fa-inbox"></i></a>';
											}
										}
									?>
									<tr class="valign-top">
										<td><?=format_date($history['created']);?></td>
										<td><?=$history['rma_number']?> <a href="/rma.php?rma=<?=$history['rma_number']?>" target="_new"><i class="fa fa-arrow-right"></i></a></td>
										<td><small><?=display_part(current(hecidb($history['partid'], 'id')));?></small></td>
										<td><?=getDisposition($history['dispositionid']);?></td>
										<td>
											<?=getSerial($history['inventoryid']);?>
											&nbsp;<a href="javascript:void(0);" data-id="<?=$history['inventoryid']?>" class="btn-history" data-toggle="tooltip" data-placement="bottom" title="View Serial History"><i class="fa fa-history"></i></a><br/>
											<small class="info"><?=$history['reason']?></small>
										</td>
										<td><?=$status?></td>
										<td><?=$action?></td>
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
	
		<?php include_once 'modal/history.php'; ?>
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
