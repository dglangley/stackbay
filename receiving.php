<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/display_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getWarranty.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrderNumber.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/isBuild.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/cmp.php';

	$EDIT = true;

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

	$order_type =  isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : '';

	// This current use is more aimed towards things like repair in which you have an item received against a line_item over a common order
	$taskid =  isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : '';

	// This will be mainly used for PO and SO
	$order_number =  isset($_REQUEST['order_number']) ? $_REQUEST['order_number'] : '';

	// Variables below represent the passed back values from the submit to safe values
	$locationid =  isset($_REQUEST['locationid']) ? $_REQUEST['locationid'] : '';
	$bin =  isset($_REQUEST['bin']) ? $_REQUEST['bin'] : '';
	$conditionid =  isset($_REQUEST['conditionid']) ? $_REQUEST['conditionid'] : '';
	$checked_partid =  isset($_REQUEST['partid']) ? $_REQUEST['partid'] : '';

	if($order_type == 'Repair' AND ! $conditionid) {
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
		global $taskid, $checked_partid, $conditionid, $T, $CMP, $ord, $dir;

		$ITEMS = $ORDERS['items'];

		// print_r($ORDERS);

		$htmlRows = '';
		$lines = 0;
		foreach($ITEMS as $k => $part) {
			$ITEMS[$k]['part'] = getPart($part['partid']);
			$ITEMS[$k]['partkey'] = getPart($part['partid']) . '.' . $part['id'];
		}

		// if($ord = 'part') {
		// 	$ord = 'partkey';
		// }

		// print_r($ITEMS);
		uasort($ITEMS,$CMP($ord,$dir));

		// if($T['type'] == 'Service') {
		// 	uasort($ITEMS,$CMP('part','ASC'));
		// }

		$first = true;

		foreach($ITEMS as $part) {
			$checked = '';

			if(isset($part['invid']) AND $part['invid']) {
				$BUILD = isBuild($part['id'],'id');
				if ($BUILD) {
					$query = "SELECT SUM(qty) qty FROM inventory WHERE repair_item_id = '".res($part['id'])."'; ";
				} else {
					$query = "SELECT qty FROM inventory WHERE id=".res($part['invid']).";";
				}
				$result = qedb($query);

				if(mysqli_num_rows($result) > 0) {
					$r = mysqli_fetch_assoc($result);
					$part['qty_received'] = $r['qty'];
				}
			} else if (! array_key_exists('qty_received',$part)) {
				if ($part['ref_2'] AND $part['ref_2_label']=='repair_item_id') {
					$query = "SELECT SUM(qty) qty FROM inventory WHERE repair_item_id = '".res($part['ref_2'])."' AND (status = 'received' OR status = 'in repair'); ";
				} else {
					$query = "SELECT SUM(qty) qty FROM inventory WHERE repair_item_id = '".res($part['id'])."' AND (status = 'received' OR status = 'in repair'); ";
				}
//				$query = "SELECT SUM(qty) qty FROM inventory WHERE repair_item_id = '".res($part['id'])."'; ";
				$result = qedb($query);

				if (mysqli_num_rows($result) > 0) {
					$r = mysqli_fetch_assoc($result);
					$part['qty_received'] = $r['qty'];
				}
			}

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

			//print_r($H);

			if($part['ref_1_label']) {
				$ref1 = setRef($part['ref_1_label'],$part['ref_1'],$id,1);
			}

			if($part['ref_2_label']) {
				$ref2 = setRef($part['ref_2_label'],$part['ref_2'],$id,2);
			}

			$received = $part['qty'] <= $part['qty_received'];

			if($lines == 1 AND ! $conditionid AND ! $received) {
				// set the conditionid here to be autmatically populated if it is empty
				$conditionid = $part['conditionid'];
			}

			if($checked_partid == $part['partid'] AND ! $received AND $first) { // OR ! $checked_partid) AND ! $received AND $first
				$checked = 'checked';
				$first = false;
			}

			$partid = $part['partid'];

			if($part['item_id'] AND $part['item_label'] == 'partid') {
				$partid = $part['item_id'];
			}

			// Added disabled if the part has been completed
			// User should and needs to update the PO at a 0 cost if they want to receive more than what was ordered and paid for
			$htmlRows .= '<tr class="row-container '.($received ? 'grayed' : '').'">
							<td '.($received ? 'class="toggle_message"' : '').'>
								<input type="radio" '.($received ? '' : 'data-partid="'.$partid.'" data-conditionid="'.$part['conditionid'].'" data-class="'.getClassification($partid).'" data-ordered="'.($part['qty_received'] ?$part['qty'] - $part['qty_received']:$part['qty']).'" name="line_item" value="'.$part['id'].'" '.$checked ? : '').'>
							</td>

							<td>'.$part['line_number'].'</td>';

			$P = array();

			$H = hecidb($partid,'id');
			$P = $H[$partid];
			$def_type = 'Part';

			//print_r($H);

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
							<td>'.$part['qty'].'</td>
							<td><a target="_blank" class="qty_link" href="/inventory.php?s2='.$H['heci'].'&order_search='.$ORDERS[$T['order']].'"><div class="qty results-toggler">'.($part['qty_received'] ?:0).'</div></a></td>
							<td class="text-right">'.(($part['qty'] - $part['qty_received'] > 0)?$part['qty'] - $part['qty_received']:0).'</td>
						</tr>';
		}

		return $htmlRows;
	}

	//$ORDERS = getRecords('', '', '',$order_type,'', '', '', '', $order_number, $taskid);

	//$ORDERS = summarizeOrders($ORDERS);

	if($taskid AND ! $order_number) {
		$order_number = getOrderNumber($taskid,$T['items'],$T['order']);
	}

	$ORDER = getOrder($order_number, $order_type);

	$partRows = buildPartRows($ORDER);

	$TITLE = $T['abbrev'] . '# ' . $order_number . ' Receiving';
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
		include_once $_SERVER["ROOT_DIR"].'/modal/image.php';
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
			<div class="col-sm-1">
				<a href="/order.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-default btn-sm"><i class="fa fa-file-text-o" aria-hidden="true"></i> View</a>
			</div>
			<div class="col-sm-1">
				<a href="/service.php?order_type=<?=$order_type;?>&order_number=<?=$order_number;?>" class="btn btn-primary btn-sm"><i class="fa fa-wrench" aria-hidden="true"></i> Tech View</a>
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
			</div>
		</div>

	</form>
</div>

<?php 
	$EDIT = false;
	include 'sidebar.php'; 
?>

<div id="pad-wrapper">
<form class="form-inline" method="get" id="receiving_form" action="receiving_edit.php" enctype="multipart/form-data" >
	<input type="hidden" name="type" value="<?=$order_type;?>">

	<?php 
		if($order_number) { 
			echo '<input type="hidden" name="order_number" value="'.$order_number.'">'; 
		} 
	?>

	<div class="row">
		<div class="col-md-12">
			<!-- Legacy Packages for now -->
			<?php if($order_type == 'Purchase' OR $order_type == 'Repair') { ?>
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
								
								$masters = master_packages($order_number, strtolower($order_type));
								foreach($results as $b){
									$package_no = $b['package_no'];
									$box_button = "<button type='button' class='btn ";
									
									//Build classes for the box buttons based off data-options
									$box_button .= 'btn-grey'; //If the button has been shipped
									$box_button .= (($num_packages == 1 OR ($b['datetime'] == '' && $init)) ? ' active' : ''); //If the box is active, indicate that
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
								$insert = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`,`datetime`) VALUES ($order_number,'purchase','1','".$GLOBALS['now']."');";
								qdb($insert) or die(qe());
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

	<div class="row" style="margin: 20px 0;">					
		<!-- <div class="col-md-7" style="padding-left: 0px !important;"> -->
			<div class="col-md-1">
				
			</div>
			<div class="col-md-3 location">
				<div class="col-md-8" style="padding-right: 0;">
					<select name="locationid" size="1" class="location-selector" data-noreset="1">
						<?php 
							if($locationid) {
								echo '<option value="'.$locationid.'">'.getLocation($locationid).'</option>';
							}
						?>
					</select>
				</div>

				<div class="col-md-4" style="padding-left: 5px; padding-right: 0;">
					<select name="bin" class="form-control input-sm select2 bin" style="padding-left:0px;" placeholder="- Bin -">
						<option></option>
						<?php 
							for($i = 1; $i <= 10; $i++) {
								echo '<option value="'.$i.'" '.($bin == $i ? 'selected' : '').'>'.$i.'</option>';
							}
						?>
    			    </select>
				</div>
			</div>

			<div class="col-md-1" style="padding-left: 0;">
				<select name="conditionid" size="1" class="form-control input-sm condition-selector" data-placeholder="- Condition -">
					<?php 
						if($conditionid) {
							echo '<option value="'.$conditionid.'">'.getCondition($conditionid).'</option>';
						}
					?>
				</select>
			</div>
			
			<div class="col-md-3">
				<div class="row">
					<div class="input-group">
						<input class="form-control input-sm serialInput auto-focus" name="serial" type="text" placeholder="Serial" value="" autofocus>
						<span class="input-group-addon">or</span>
						<input class="form-control input-sm qtyInput auto-focus" name="qty" type="text" placeholder="QTY" value="" style="width: 70px;">
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
			        	<th class="col-sm-2">
							RECEIVED
			        	</th>
			        	<th class="text-right col-sm-1">
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
		if ($('input[name=line_item]:checked').length == 1) {
			// Make sure the location is filled with a value
			if ($('select[name=locationid]').val()) {
				// Make sure that the user has something entered in either qty or serial number
				if ($('input[name=serial]').val() || $('input[name=qty]').val()) {
					// This section checks for a qty overload and makes sure the user doesn't exceed qty to ordered
					var orderedAmount = $('input[name=line_item]:checked').data('ordered');
					if(($('input[name=qty]').val() && $('input[name=qty]').val() <= orderedAmount) || ! $('input[name=qty]').val()) {
						var classification = $('input[name=line_item]:checked').data('class');

						if(classification == 'equipment' && $('input[name=qty]').val()) {
							warning = "Are you sure you want to receive a qty amount for an equipment? <br>";
						} else if(classification != 'equipment' && $('input[name=serial]').val()) {
							warning = "Are you sure you want to receive a serialized item for a "+classification+"? <br>";
						}

						var conditionid = $('input[name=line_item]:checked').data('conditionid');

						if(conditionid != $('select[name=conditionid]').val() && conditionid != '-5' && conditionid) {
							warning = "Conditions do not match the original order. Please confirm you want to receive a different condition.";
						}
						
						// Create the hidden inputs: partid,
						input = $("<input>").attr("type", "hidden").attr("name", "partid").val($('input[name=line_item]:checked').attr('data-partid'));
						$('#receiving_form').append($(input));

						var package_number = $(".box_selector.active").data("row-id");

						// Create the hidden packageid
						input = $("<input>").attr("type", "hidden").attr("name", "packageid").val(package_number);
						$('#receiving_form').append($(input));

						if(warning) {
							modalAlertShow('<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Warning',warning,true,'submitReceivingForm');
							//alert('submitting');
							// if(confirm(warning)) {
							// 	submitForm();
							// }
						}							
					} else {
						ERR =  "Qty Exceeds Ordered. Please update the order with the correct qty if the receiving qty is correct.";
					}
				} else {
					ERR =  "Missing Serial/Qty";
				}
			} else {
				ERR =  "No Location Selected";
			}
		} else {
			ERR =  "No Part Selected";
		}

		if(ERR) {
			modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", ERR + " <br><br>If this message appears to be in error, please contact an Admin.");
		} else if(! warning) {
			submitReceivingForm();
		}
	}

	function submitReceivingForm() {
		$('#receiving_form').submit();
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
		modalAlertShow("<i class='fa fa-exclamation-triangle' aria-hidden='true'></i> Warning", "Part has been received in full. Please update the order if more qty is available. <br><br>If this message appears to be in error, please contact an Admin.");

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


</script>

</body>
</html>
