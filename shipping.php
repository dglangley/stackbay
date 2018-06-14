<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getAddresses.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getFreight.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getInventory.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	
	$EDIT = false;

	include_once $_SERVER["ROOT_DIR"].'/inc/buildDescrCol.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setInputSearch.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getItems.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/detectDefaultType.php';

	//Packages uses getLocation so we need to comment it out till the rebuild
	include_once $_SERVER["ROOT_DIR"].'/inc/packages_new.php';

	// Default sort
	$ord = 'line_number';
	$dir = 'asc';

	if (isset($_COOKIE['po_col_sort'])) { $ord = $_COOKIE['po_col_sort']; }
	if (isset($_COOKIE['po_col_sort_type'])) { $dir = $_COOKIE['po_col_sort_type']; }

	if(isset($_REQUEST['ord']) AND isset($_REQUEST['dir'])) {
		$ord = $_REQUEST['ord'];
		$dir = $_REQUEST['dir'];

		// set the cookie if a new ord and dir is being set
		setcookie('po_col_sort',$ord,time()+(60*60*24*365));
		setcookie('po_col_sort_type',$dir,time()+(60*60*24*365));
	}

	$order_type =  isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : 'Sale';

	// This current use is more aimed towards things like repair in which you have an item received against a line_item over a common order
	$taskid =  isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : '';

	// This will be mainly used for PO and SO
	$order_number =  isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '';
	if (! $order_number AND isset($_REQUEST['on'])) { $order_number = $_REQUEST['on']; }

	// Variables below represent the passed back values from the submit to safe values
	$locationid =  isset($_REQUEST['locationid']) ? $_REQUEST['locationid'] : '';
	$bin =  isset($_REQUEST['bin']) ? $_REQUEST['bin'] : '';
	$conditionid =  isset($_REQUEST['conditionid']) ? $_REQUEST['conditionid'] : '';
//	$checked_partid =  isset($_REQUEST['partid']) ? $_REQUEST['partid'] : '';
	$line_item = isset($_REQUEST['line_item']) ? $_REQUEST['line_item'] : '';
	$packageid = isset($_REQUEST['packageid']) ? $_REQUEST['packageid'] : 0;

	$ERR =  isset($_REQUEST['ERR']) ? $_REQUEST['ERR'] : '';

	$ERROR = '';

	// Check off if the ISO is complete
	$ISO = false;

	switch($ERR) {
		case 1:
			$ERROR = 'Scanned part not found. Please see an admin for assistance';
			break;
		case 2:
			$ERROR = 'Failed to add the part to the inventory. Please see an admin for assistance.';
			break;
		default:
			break;
	}

	if($order_type == 'Repair') {
		$conditionid = -5;
	}

	// Get the order type array values
	$T = order_type($order_type);

	// Get the part classification
	function getClassification($partid) {
		$classification = '';

		$query = "SELECT classification FROM parts WHERE id = ".res($partid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);
			$classification = $r['classification'];
		}

		return $classification;
	}

	function getDateStamp($order_number, $order_type) {
		$datestamp = '';
		
		$query = "SELECT * FROM packages  WHERE  order_number = ".res($order_number).";";
		$results = qedb($query);
		
		if (mysqli_num_rows($results)>0) {
			$r = mysqli_fetch_assoc($results);
			$datestamp = $r['datetime'];
		}
		
		return $datestamp;
	}

	// Stolen from David
	function setRef($label,$ref,$id,$n) {
		$grp = array('btn'=>'Ref','field'=>'','hidden'=>'','attr'=>' data-toggle="dropdown"');

		//if (! strstr($id,'NEW')) {
		if ($id OR strstr($label,'item_id')) {
			if (strstr($label,'item_id')) {
				$T2 = order_type($label);
				$ref_order = getOrderNumber($ref,$T2['items'],$T2['order']);

				$grp['attr'] = '';
				$grp['btn'] = $T2['abbrev'];
				$grp['field'] = '<input type="text" name="ref_'.$n.'_aux['.$id.']" class="form-control input-sm" value="'.$ref_order.'" readonly>';
				$grp['hidden'] = '<input type="hidden" name="ref_'.$n.'['.$id.']" value="'.$ref.'">';
			} else {
				// change default ref label, if set
				if ($label) { $grp['btn'] = $label; }

				$grp['field'] = '<input type="text" name="ref_'.$n.'['.$id.']" class="form-control input-sm" value="'.$ref.'">';
	
			}
		} else {
			$grp['field'] = '<input type="text" name="ref_'.$n.'['.$id.']" class="form-control input-sm" value="">';
		}

		return ($grp);
	}

	function buildRefCol($grp,$label,$ref,$id,$n) {
		$col = '';

		if ($ref) {
			// if an id is used for reference, convert it to the corresponding Order
			if (strstr($label,'item_id')) {
				$T2 = order_type($label);
				$order = getOrderNumber($ref,$T2['items'],$T2['order']);

				//$col = $T2['abbrev'].' '.$order.'<a href="/'.strtolower($T2['type']).'.php?order_type='.$T2['type'].'&order_number='.$order.'"><i class="fa fa-arrow-right"></i></a>';
				$col = $T2['abbrev'].' '.$order.'<a href="/'.strtolower($T2['type']).'.php?order_type='.$T2['type'].'&taskid='.$ref.'"><i class="fa fa-arrow-right"></i></a>';
			} else {
				$col = $label.' '.$ref;
			}
		}

		return ($col);
	}

	function buildPartRows($ORDERS) {
		global $taskid, $line_item, $conditionid, $T, $CMP, $ord, $dir;

		// print_r($ORDERS);
		$ITEMS = $ORDERS['items'];

		$htmlRows = '';
		$lines = 0;
		foreach($ITEMS as $k => $part) {
			$ITEMS[$k]['part'] = getPart($part['partid']);
			$ITEMS[$k]['partkey'] = getPart($part['partid']) . '.' . $part['id'];
		}

		uasort($ITEMS,$CMP($ord,$dir));

		$first = true;

		foreach($ITEMS as $part) {
			$checked = '';

			// If taskid is passed in then we only want to show the exact part so continue if exists and not equal to the taskid / item_id
			if($taskid AND $part['id'] <> $taskid) {
				continue;
			} else if($part['id'] == $taskid) {
				// Count lines pertaining to the taskid
				$lines++;
			} else {
				// else count lines as a total of parts present
				$lines = count($ITEMS);
			}

			$H = reset(hecidb($part['partid'],'id'));

			if($part['ref_1_label']) {
				$ref1 = setRef($part['ref_1_label'],$part['ref_1'],$id,1);
			}

			if($part['ref_2_label']) {
				$ref2 = setRef($part['ref_2_label'],$part['ref_2'],$id,2);
			}

			$received = $part['qty'] <= $part['qty_shipped'];

			if($lines == 1 AND ! $conditionid AND ! $received) {
				// set the conditionid here to be autmatically populated if it is empty
				$conditionid = $part['conditionid'];
			}

			//if(($checked_partid == $part['partid'] OR $lines == 1) AND ! $received AND $first) { // OR ! $checked_partid) AND ! $received AND $first
			if(($line_item == $part['id'] OR $lines == 1) AND ! $received AND $first) {
				$checked = 'checked';
				$first = false;
			}

			// Added disabled if the part has been completed
			// User should and needs to update the PO at a 0 cost if they want to receive more than what was ordered and paid for
			$htmlRows .= '<tr class="row-container '.($received ? 'grayed' : '').'">
							<td '.($received ? 'class="toggle_message"' : '').'>
								<input type="radio" '.($received ? '' : 'data-partid="'.$part['partid'].'" data-conditionid="'.$part['conditionid'].'" data-class="'.getClassification($part['partid']).'" data-ordered="'.($part['qty_shipped']?$part['qty'] - $part['qty_shipped']:$part['qty']).'" name="line_item" value="'.$part['id'].'" '.$checked ? : '').'>
							</td>

							<td>'.$part['line_number'].'</td>';

			$P = array();

			$partid = $part['partid'];

			$H = hecidb($partid,'id');
			$P = $H[$partid];
			$def_type = 'Part';

			$parts = explode(' ',$H[$partid]['part']);
			$part_name = $parts[0];

			$aliases = '';

			if(! empty($H[$partid]['heci7'])) {
				$aliases = $H[$partid]['heci7'];
			} else {
				$aliases = $part_name;
			}

			$htmlRows .= '	<td>
								<div class="row remove-pad">
										<div class="product-img pull-left"><img class="img" src="/img/parts/'.$part_name.'.jpg" alt="pic" data-part="'.$part_name.'"></div>
										<div class="part_changer" style="width: 240px; float: left;">
											'.buildDescrCol($P,$part['id'],'Part','', true, true, $aliases).'
										</div>
									</div>
								<div class="row remove-pad">
									<span class="descr-label part_description">'.display_part($partid, true, true, false).'</span>
								</div>
							</td>
							<td>'.buildRefCol($ref1,$part['ref_1_label'],$part['ref_1'],$id,1).'</td>
							<td>'.buildRefCol($ref2,$part['ref_2_label'],$part['ref_2'],$id,2).'</td>
							<td>'.getCondition($part['conditionid']).'</td>
							<td>'.getWarranty($part['warranty'], 'warranty').'</td>
							<td><a target="_blank" class="qty_link" href="/inventory.php?partids[]='.$part['id'].'"><div class="qty results-toggler">'.$part['qty'].'</div></a></td>
							<td>'.($part['qty_shipped'] ?$part['qty_shipped'].'<a target="_blank" style="margin-left: 10px;" class="qty_link" href="/inventory.php?taskid='.$part['id'].'&task_label=sales_item_id"><i class="fa fa-truck"></a>':0).'</td>
							<td class="text-center">'.(($part['qty'] - $part['qty_shipped'] > 0)?$part['qty'] - $part['qty_shipped']:0).'</td>
						</tr>';
		}

		return $htmlRows;
	}

	function buildPackageRows($order_number, $order_type, $iso = false) {
		global $ISO;

		$htmlRows = '';

		$packages = getPackages($order_number, $order_type);

		foreach($packages as $package) {
			// Fow now if at least 1 package is shipped then assume ISO has been followed through
			if($package['datetime']) {
				$ISO = true;
			}

			$subRows = buildPackageSubRows($package['id'], $package['datetime']);

			if($subRows) {
				if(! $iso) {
					$htmlRows .= '
								<tr>
									<td>'.$package['package_no'].'</td>
									<td>'.$package['tracking_no'].'</td>
									<td>'.format_date($package['datetime']).'</td>
									<td>'.format_price($package['freight_amount']).'</td>
									<td>
								';
					// If there is no datetime then it is assumed the package has NOT been shipped so remove the ability to generate a PS for this box
					if($package['datetime']) {
						$htmlRows .= '
										<input class="pull-right" type="checkbox" name="package_slip" value="'.$package['id'].'">
									';
					}
					$htmlRows .= '
									</td>
								</tr>
								<tr>
									<td style="width: 50px;"></td>
									<td colspan="3">
										<table class="table table-hover table-striped table-condensed" style="table-layout:fixed;">
											
											
											<tbody>
												'.$subRows.'
											</tbody>
										</table>
									</td>
									<td style="width: 50px;"></td>
								</tr>';
				} else {
					// Build for ISO only require Part and Serial
					$htmlRows .= buildPackageSubRows($package['id'], true, $package['package_no'], $iso);
				}
			}
		}

		return $htmlRows;
	}

	function buildPackageSubRows($packageid, $datetime, $box, $iso) {
		global $order_type, $order_number;

		$subRows = '';
		$deleteLink= '';

		$packageContents = getPackageContents($packageid);

		foreach($packageContents as $id => $content) {
			if(! $datetime) {
				$deleteLink = '<a class="pull-right return_stock" href="/shipping_edit.php?delete='.$id.'&packageid='.$packageid.'&type='.$order_type.'&order_number='.$order_number.'"><i class="fa fa-trash" aria-hidden="true"></i></a>';
			}

			$inv = getInventory($id);
			$subRows .= '
						<tr class="grayed">';
			if($box) {
			$subRows .= '	<td>'.$box.'</td>';	
			}		
			$subRows .= '	<td>'.$content['part'].'</td>
							<td>'.($content['serial']?:$content['qty']).'</td>
							<td>
								<div class="input-group">
							    	<input type="text" class="form-control input-xs iso_comment" name="iso_comment['.$id.']" value="'.$inv['notes'].'" placeholder="Comment" '.($iso ? 'disabled' : '').'>
								    <span class="input-group-btn">
										<button class="btn btn-xs btn-primary" type="submit" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Save Entry"><i class="fa fa-save"></i></button>
									</span>
								</div>
							</td>
							<td>'.($deleteLink?:($comment?:'')).'</td>
						</tr>
			';
		}

		return $subRows;
	}

	function checkOrderStatus($order_number, $order_type) {
		$status = true;
		$T = order_type($order_type);

		// Missing needed variables being used within the queries
		switch ($order_type) {
			case 'Service':
				$fqty_field = '';
				break;
			case 'Outsourced':
				$fqty_field = '';
				break;
			case 'Purchase':
				$fqty_field = 'qty_received';
				break;
			case 'Repair':
				$fqty_field = 'qty';
				break;
			case 'Sale':
				$fqty_field = 'qty_shipped';
				break;
			case 'Return':
				$fqty_field = '';
				break;
			default:
				return 0;
				break;
		}

		if($fqty_field) {
			$query = "SELECT * FROM ".$T['items']." WHERE ".$T['order']." = ".fres($order_number)." AND qty > ".$fqty_field.";";
			$result = qedb($query);

			if (mysqli_num_rows($result)>0) {
				$status = false;
			}
		} 

		return $status;
	}

	if($taskid AND ! $order_number) {
		$order_number = getOrderNumber($taskid,$T['items'],$T['order']);
	}

	$TITLE = '';
	$repair_order = 0;	

	// If order type is repair then it is actually a sales order
	$order_type = ($order_type == 'Repair' ? 'Sale' : $order_type);

	$ORDER = getOrder($order_number, $order_type);
	
	if(empty($ORDER)) {
		die("Order not found or does not exists!");
	}

	$partRows = buildPartRows($ORDER);

	// print '<pre>' . print_r($ORDER, true) . '</pre>';

	if(reset($ORDER['items'])['ref_1_label'] == 'repair_item_id') {
		$repair_item = reset($ORDER['items'])['ref_1'];
	}

	if($repair_item) {
		$query = "SELECT ro_number FROM repair_items WHERE id = ".res($repair_item).";";
		$result = qedb($query);

		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$repair_order = $r['ro_number'];
		}
	}

	if($repair_order) {
		$TITLE = 'RO# ' . $repair_order . ' Shipping';	
	} else {
		$TITLE = $T['abbrev'] . '# ' . $order_number . ' Shipping';	
	}

	$packageRows = buildPackageRows($order_number, $order_type);
	$order_status = checkOrderStatus($order_number, $order_type);

	// print '<pre>' . print_r($packageContents, true) . '</pre>';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.remove-pad {
			padding: 0;
			margin: 0;
		}

		.qty {
			border:1px inset #eee !important;
			background-color:#fafafa !important;
			border-radius:3px;
			width:40px;
			min-width:30px;
			max-width:80px;
			text-align:center;
			font-weight:bold;
		}
		.results-toggler {
			cursor:pointer;
		}

		.qty_link {
			color: #526273;
		}

		.qty_link:hover {
			color: #526273;
			text-decoration: none;
		}

		.grayed td {
			background: #EEE !important;
		}

		.dropdown-searchtype {
			display: none;
		}
	</style>
</head>
<body data-order-number="<?=$order_number;?>" data-order-type="<?=$order_type;?>" data-scope="Receiving">

<?php 
	include_once 'inc/navbar.php'; 
	include_once 'modal/image.php';
	include_once 'modal/iso_ship.php';
	include_once 'modal/package.php';
?>

<!-- FILTER BAR -->
<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >
		<input type="hidden" name="order_type" value="<?=$order_type;?>">
		<input type="hidden" name="order_number" value="<?=$order_number;?>">
		<input type="hidden" name="taskid" value="<?=$taskid;?>">

		<input type="hidden" name="ord" value="<?=$ord;?>" id="ord">
		<input type="hidden" name="dir" value="<?=$dir;?>" id="dir">

		<div class="row" style="padding:8px">
			<div class="col-sm-2">
				<a href="/order.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-default btn-sm pull-left"><i class="fa fa-file-text-o" aria-hidden="true"></i> Manage</a>
				<?php if($ISO) { ?>
					<a target="_blank" href="/iso-form.php?on=<?=$order_number?>" class="btn btn-default btn-sm pull-left" style="margin-left: 5px;"><i class="fa fa-check-square-o"></i> QC</a>
				<?php } ?>
			</div>
			<div class="col-sm-2">
			</div>
			<div class="col-sm-4 text-center">
				<h2 class="minimal"><?php echo $TITLE; ?></h2>
				<span class="info"></span>
			</div>
			<div class="col-sm-2">
			</div>
			<div class="col-sm-2">
				<button class="btn btn-success pull-right" id="iso_report" data-datestamp="<?=getDateStamp($order_number);?>"><i class="fa fa-save"></i> Complete</button>
			</div>
		</div>

	</form>
</div>

<?php 
	$EDIT = false;
	include 'sidebar.php'; 
?>

<div id="pad-wrapper">
<form class="form-inline" method="get" id="shipping_form" action="shipping_edit.php">
	<input type="hidden" name="type" value="<?=$order_type;?>">

	<?php 
		if($order_number) { 
			echo '<input type="hidden" name="order_number" value="'.$order_number.'">'; 
		} 
	?>

	<div class="row">
		<div class="col-md-12">
			<!-- Legacy Packages for now -->
			<?php if($order_type == 'Sale') { ?>
				<div class="row">
					<div class="btn-group box_group" style = "padding-bottom:16px;">
						<button type="button" class="btn btn-warning box_edit" title = 'Edit Selected Box'>
							<i class="fa fa-pencil fa-4" aria-hidden="true"></i>
						</button>
						<?php

							$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";

							$results = qdb($select) or die(qe()." ".$select);
							$num_packages = mysqli_num_rows($results);
							
							//Check for any open items to be shipped
							if ($num_packages > 0){
								//Initialize
								$init = true;
								$package_no = 0;
								
								$masters = master_packages($order_number,'Sale');
								foreach($results as $b){
									$package_no = $b['package_no'];
									$box_button = "<button type='button' class='btn ";
									
									//Build classes for the box buttons based off data-options
									$box_button .= 'btn-grey'; //If the button has been shipped
									$box_button .= (($num_packages == 1 OR $packageid == $b['id']) ? ' active' : ''); //If the box is active, indicate that
									$box_button .= (in_array($package_no,$masters)) ? ' master-package ' : '';
									$box_button .= " box_selector'";
									
									//Add Data tags for the future population of modals
									$box_button .= " data-width = '".$b['weight']."' data-l = '".$b['length']."' ";
									$box_button .= " data-h = '".$b['height']."' data-weight = '".$b['weight']."' ";
									$box_button .= " data-row-id = '".$b['id']."' data-tracking = '".$b['tracking_no']."' ";
									$box_button .= " data-row-freight = '".$b['freight_amount']."'";
									$box_button .= " data-order-number='" . $order_number . "'";
									$box_button .= " data-box-shipped ='".($b['datetime'] ? $b['datetime'] : '')."' >".$b['package_no']."</button>";
									echo($box_button);
			                    	
			                    	$box_list .= "<option value='".$b['id']."'>Box ".$b['package_no']."</option>";
			                    	if($b['datetime'] == '' && $init)
			                    		$init = false;
								}
								

							} else {
								$query = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`) VALUES ($order_number,'Sale','1');";
								qedb($query);
								echo("<button type='button' class='btn active box_selector master-package' data-row-id = '".qid()."'>1</button>");
							}

						?>
						<button type="button" class="btn btn-primary box_addition" title = "">
					  		<i class="fa fa-plus fa-4" aria-hidden="true"></i>
				  		</button>
					</div>
				</div>
			<?php } ?>
			<!-- End Legacy Packages -->
		</div>
	</div>

	<!-- Remove the top bar if the order is fully scanned out -->
	<?php if(! $order_status) { ?>
	<div class="row" style="margin: 20px 0;">					
		<!-- <div class="col-md-7" style="padding-left: 0px !important;"> -->
			<div class="col-md-1">
				
			</div>
			
			<div class="col-md-6">
				<div class="row">
					<div class="input-group">
						<input class="form-control input-sm serialInput auto-focus" name="serial" type="text" placeholder="Serial" value="">
						<span class="input-group-addon">or</span>
						<input class="form-control input-sm qtyInput" name="qty" type="text" placeholder="QTY" value="">
						<!-- <span class="input-group-btn">
							<button class="btn btn-success btn-sm addItem" type="submit"><i class="fa fa-save"></i></button>
						</span> -->
					</div>
				</div>
            </div>

            <div class="col-md-1">
            	<button class="btn btn-success btn-sm addItem" type="submit"><i class="fa fa-save"></i></button>
            </div>
        <!-- </div> -->
	</div>
	<?php } ?>

	<div class="row">
		<div class="table-responsive">
			<table class="table table-hover table-striped table-condensed" style="table-layout:fixed;">
				<thead>
			         <tr>
			         	<th style="width: 50px;">
			            	
			            </th>
			            <th style="width: 50px;">
			            	LN#	
			            	<a href="javascript:void(0);" class="sorter" data-ord="line_number" data-dir="<?= (($ord=='line_number' AND $dir=='desc') ? 'asc"><i class="fa fa-sort-numeric-asc"></i>' : 'desc"><i class="fa fa-sort-numeric-desc"></i>'); ?></a>
			            </th>
			            <th class="col-sm-3">
			            	DESCRIPTION	
			            	<a href="javascript:void(0);" class="sorter" data-ord="partkey" data-dir="<?= (($ord=='partkey' AND $dir=='desc') ? 'asc"><i class="fa fa-sort-alpha-asc"></i>' : 'desc"><i class="fa fa-sort-alpha-desc"></i>'); ?></a>
			            </th>
			            <th class="text-center col-sm-1">
							REF 1
			        	</th>
			        	<th class="text-center col-sm-1">
							REF 2
			        	</th>
			        	<th class="text-center col-sm-1">
							CONDITION
			        	</th>
			        	<th class="col-sm-2">
							WARRANTY
			        	</th>
			        	<th class="col-sm-1">
							QTY
			        	</th>
			        	<th class="col-sm-1">
							SHIPPED
			        	</th>
			        	<th class="text-center col-sm-1">
			        		OUTSTANDING
			        	</th>
			         </tr>
				</thead>
				
				<tbody>
					<?=$partRows;?>
				</tbody>
			</table>
		</div>
	</div>

</form>

<form class="form-inline" method="get" id="iso_form" action="iso_edit.php" enctype="multipart/form-data">
	<input type="hidden" name="type" value="<?=$order_type;?>">

	<?php 
		if($order_number) { 
			echo '<input type="hidden" name="order_number" value="'.$order_number.'">'; 
		} 
	?>

	<h3 class="text-center" style="margin-top: 100px;">Tracking</h3>

	<div class="table-responsive">
			<table class="table table-hover table-striped table-condensed" style="table-layout:fixed;">
				<thead>
			         <tr>
			            <th style="width: 50px;">
			            	BOX#	
			            </th>
			            <th class="">
			            	TRACKING#	
			            </th>
			            <th class="">
			            	Date	
			            </th>
			            <th class="">
							FREIGHT
			        	</th>
			        	<th class="text-right" style="width: 150px; padding-right: 5px !important;">
			            	Action	

			            	<?php if($packageRows) { ?>
			            	<button style="margin-left: 5px;" class="btn btn-default btn-sm btn_packing_slip" title="" data-toggle="tooltip" data-placement="bottom" data-original-title="View Packing Slip"><i class="fa fa-file-pdf-o"></i></button>
			            	<?php } ?>
			            </th>
			         </tr>
				</thead>
				
				<tbody>
					<?=$packageRows;?>
				</tbody>
			</table>
		</div>
	</div>

	<?php // print '<pre>' . print_r($ORDER, true) . '</pre>'; ?>

</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<!-- Packages js file -->
<script src="js/packages.js"></script>
<script src="js/item_search.js?id=<?php echo $V; ?>"></script>

<script type="text/javascript">
	$(document).ready(function() {
		//$(addItem);

		$('.bin').select2({
		    placeholder: "- Bin -"
		});

		$('form').preventDoubleSubmission();

		$('.sorter').click(function() {
			$("#ord").val($(this).data('ord'));
			$("#dir").val($(this).data('dir'));
			$("#filters-form").submit();
		});
	});

	jQuery.fn.preventDoubleSubmission = function() {
	  $(this).on('submit',function(e){
	    var $form = $(this);

	    if ($form.data('submitted') === true) {
	      // Previously submitted - don't submit again
	      e.preventDefault();
	    } else {
	      // Mark it so that the next submit can be ignored
	      $form.data('submitted', true);
	    }
	  });

	  // Keep chainability
	  return this;
	};

	function preSubmit() {
		var input = '';
		var ERR, warning;

		// Make sure there is a part selected to be scanned to
		if ($('input[name=line_item]:checked').length == 1 && $('input[name=qty]').val()) {
			// This section checks for a qty overload and makes sure the user doesn't exceed qty to ordered
			var orderedAmount = $('input[name=line_item]:checked').data('ordered');
			if(($('input[name=qty]').val() && $('input[name=qty]').val() <= orderedAmount) || ! $('input[name=qty]').val()) {
				var classification = $('input[name=line_item]:checked').data('class');
				if(classification == 'equipment' && $('input[name=qty]').val()) {
					warning = "Are you sure you want to ship qty amount for an equipment? <br>";
				} else if(classification != 'equipment' && $('input[name=serial]').val()) {
					warning = "Are you sure you want to ship a serialized item for a "+classification+"? <br>";
				}
				
				// Create the hidden inputs: partid,
				input = $("<input>").attr("type", "hidden").attr("name", "partid").val($('input[name=line_item]:checked').attr('data-partid'));
				$('#shipping_form').append($(input));

				var package_number = $(".box_selector.active").data("row-id");

				// Create the hidden packageid
				input = $("<input>").attr("type", "hidden").attr("name", "packageid").val(package_number);
				$('#shipping_form').append($(input));

				if(warning) {
					modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning',warning,true,'submitReceivingForm');
				}							
			} else {
				ERR =  "Qty Exceeds Ordered. Please update the order with the correct qty if the shipped qty is correct.";
			}
		// If a serial is being shipped out allow user to not have a partid / line selected
		} else if($('input[name=serial]').val()) {
			var package_number = $(".box_selector.active").data("row-id");

			var classification = $('input[name=line_item]:checked').data('class');

			//alert(classification);
			
			if(classification == 'component') {
				warning = "Are you sure you want to ship a serialized item for a "+classification+"? <br>";
			}

			// Create the hidden inputs: partid,
			input = $("<input>").attr("type", "hidden").attr("name", "partid").val($('input[name=line_item]:checked').attr('data-partid'));
			$('#shipping_form').append($(input));

			// Create the hidden packageid
			input = $("<input>").attr("type", "hidden").attr("name", "packageid").val(package_number);
			$('#shipping_form').append($(input));

			if(warning) {
				modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning',warning,true,'submitReceivingForm');
			}	
		} else {
			ERR =  "No Part Selected (Required for QTY Shipping)";
		}

		if(ERR) {
			modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", ERR + " <br><br>If this message appears to be in error, please contact an Admin.");
		} else if(! warning) {
			submitReceivingForm();
		}
	}

	function submitReceivingForm() {
		$('#shipping_form').submit();
	}

	$(document).on("click", ".addItem", function(e) {
		e.preventDefault();

		preSubmit();
	});

	// Key press enter on input to submit
	$(document).on("keydown",".serialInput",function(e){
		if (e.keyCode == 13) {
			e.preventDefault();
			preSubmit();
		}
	});

	$(document).on("keydown",".qtyInput",function(e){
		if (e.keyCode == 13) {
			e.preventDefault();
			preSubmit();
		}
	});

	// Clear serial or qty depending on what is changed
	$(document).on("keydown",".serialInput",function(e){
		$('.qtyInput').val('');
	});

	$(document).on("keydown",".qtyInput",function(e){
		$('.serialInput').val('');
	});

	$(document).on('click', '.toggle_message', function() {
		modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Part has been shipped in full. Please update the order if more qty is available. <br><br>If this message appears to be in error, please contact an Admin.");

		$(this).find('input').prop("checked", false);
	});

	// Generate and change to the corresponding conditionid if user changes part automatically
	$(document).on('change', 'input[name=line_item]', function(e) {
		var conditionid = $(this).data('conditionid');
		
		// get the value of the current condition if selected and check if it matches to reduce ajax queries
		if($('select[name=conditionid]').val() != conditionid) {
			$.ajax({
		        url: 'json/condition_update.php',
		        type: 'get',
		        dataType: "json",
		        data: {'conditionid': conditionid},
		        success: function(data) {
		        	console.log(data.condition);

		        	$('select[name="conditionid"]').find('option').remove();
		        	if(data.condition)
		        		$('select[name="conditionid"]').append('<option value="'+data.id+'" selected>'+data.condition+'</option>').trigger('change');
		        	//$('select[name="conditionid"]').val(data.id);
		        },
		        error: function(xhr, desc, err) {
		            console.log("Details: " + desc + "\nError:" + err);
		        }
		    }); // end ajax call
		}

	});

	$(document).on("change", ".part-selector", function() {
		var partid = $(this).val();
		// alert('changed detected ' + partid);

		$(this).closest('.row-container').find('input[type="radio"]').attr("data-partid", partid);
	});

	$(document).on("click","#iso_report", function(e) {
		e.preventDefault();
			
		$("#modal-iso").modal("show");
		
	});

	$(document).on('click','.btn_iso_parts_continue', function(e) {
		e.preventDefault();
		if($('.nav-tabs a[href="#iso_req"]').length > 0) {
			$('.nav-tabs a[href="#iso_req"]').tab('show');	
		} else {
			$('.nav-tabs a[href="#iso_match"]').tab('show');
		}
	});

	$(document).on('click','.btn_iso_req', function(e) {
		e.preventDefault();
		
		var so_number = $('body').data('order-number');

		$.ajax({
			type: 'POST',
			url: '/json/iso.php',
			data: {'special_req' : 'yes', 'contact_info' : 'n/a', 'transit_time' : 'n/a', 'so_number': so_number, 'type' : 'special'},
			dataType: 'json',
			success: function(data) {
				console.log(data + ' iso_match ' + so_number);
				$('.nav-tabs a[href="#iso_match"]').tab('show');
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				console.log("JSON iso.php: ERROR");
			},
		});
	});

	$(document).on('click','.btn_iso_parts', function(e) {
		e.preventDefault();
		var damage = false;
		var so_number, partName;
		var serialid = [];
		var serialComments = [];
		
		so_number = $('body').data('order-number');
		
		if($('.iso_comment').val()) {
			damage = true;
		}
		
		console.log(serialid + ' ' + serialComments + ' ' + so_number + ' ' + damage);
		
		$.ajax({
			type: 'POST',
			url: '/json/iso.php',
			data: {
				'part_no' : 'yes', 
				'heci' : 'yes',
				'damage' : damage, 
				'so_number' : so_number, 
				'invid' : serialid, 
				'comments' : serialComments,
				'type' : 'part',
			},
			dataType: 'json',
			success: function(data) {
				console.log(data);
				if($('.nav-tabs a[href="#iso_req"]').length > 0) {
					$('.nav-tabs a[href="#iso_req"]').tab('show');	
				} else {
					$('.nav-tabs a[href="#iso_match"]').tab('show');
				}
			},
			error: function(xhr, status, error) {
				alert(error+" | "+status+" | "+xhr);
				console.log("JSON iso.php: ERROR");
			},
		});
	});

	$(document).on("click", ".return_stock", function(e) {
		return confirm("Please confirm you want to remove this part from the order!");
	});

	$(document).on("keydown",".iso_comment",function(e){
		if (e.keyCode == 13) {
			e.preventDefault();
			$("#iso_form").submit();
		}
	});

	$(document).on("click", ".btn_packing_slip", function(e) {
		e.preventDefault();

		var packageids = [];
		var link = '';

		var init = true;

		$('input[name="package_slip"]:checked').each(function() {
			packageids.push($(this).val());
		});

		if(packageids.length > 0) {
			link = '/docs/PSP';
			packageids.forEach(function(id) {
			    if(init) {
			    	link += id;
			    	init = false;
			    } else {
			    	link += '_' + id;
			    }
			});

			link += '.pdf';

			console.log(packageids);
			console.log(link);
			
			window.open(link, '_blank'); 
		}
	});

</script>

</body>
</html>
