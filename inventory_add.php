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
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/locations.php';
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "New";
	$order_type = ($_REQUEST['ps'] == 'p' || $_REQUEST['ps'] == 'Purchase') ? "Purchase" : "Sales";
	
	
	//Get the ENUM values of the specified table and column (field)
	function getEnumValue( $table = 'inventory', $field = 'item_condition' ) {
		$statusVals;
		
	    $query = "SHOW COLUMNS FROM {$table} WHERE Field = '" . res($field) ."';";
	    $result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$statusVals = $result;
		}
		
		preg_match("/^enum\(\'(.*)\'\)$/", $statusVals['Type'], $matches);
		
		$enum = explode("','", $matches[1]);
		
		return $enum;
	}
	

	//Using the order number from purchase order, get all the parts being ordered and place them on the inventory add page
	function getPOParts () {
		global $order_number;
		
		$listParts;
		
		$query = "SELECT * FROM purchase_items WHERE po_number = ". res($order_number) ." AND qty != qty_received;";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listParts[] = $row;
			}
		}
		
		$query = "SELECT * FROM purchase_items WHERE po_number = ". res($order_number) ." AND qty = qty_received;";
		$result = qdb($query);
	    
	    if($result)
	    if (mysqli_num_rows($result)>0) {
			while ($row = $result->fetch_assoc()) {
				$listParts[] = $row;
			}
		}
		
		return $listParts;
	}
	
	//Get the part name from the part id
	function getPartName($partid) {
		$part;
		
		$query = "SELECT parts.part, parts.heci, parts.description, systems.system FROM parts LEFT JOIN systems ON systems.id = parts.systemid WHERE parts.id = ". res($partid) .";";
		$result = qdb($query) OR die(qe());
	
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$part[] = $result;
		}
	
		return $part[0];
	}
	
	$partsListing = getPOParts();
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
		<style type="text/css">
			.table td {
				vertical-align: top !important;
				padding-top: 10px !important;
				padding-bottom: 0px !important;
			}
			
			.btn-secondary {
				/*color: #373a3c;*/
				background-color: transparent;
				border: 0;
				padding: 0;
				line-height: 0;
			}
			
			.table .order-complete td {
				background-color: #efefef !important;
			}
			
			.infiniteLocations select {
				margin-bottom: 5px;
    			height: 31px;
			}
		</style>
	</head>
	
	<body class="sub-nav" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4"><a href="/order_form.php<?php echo ($order_number != '' ? "?on=$order_number&ps=p": '?ps=p'); ?>" class="btn btn-info pull-left" style="margin-top: 10px;"><i class="fa fa-list" aria-hidden="true"></i></a></div>
			<div class="col-sm-4 text-center">
				<h1><?php echo ($order_number != '' ? 'Outstanding Items for PO #' . $order_number : 'Inventory Addition'); ?></h1>
			</div>
			<div class="col-sm-4">
				<button class="btn-flat pull-right" id = "save_button_inventory" style="margin-top:2%;margin-bottom:2%;">
					Complete
				</button>
			</div>
		</div>
		
		
			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
			<div class="left-side-main col-md-2" data-page="addition" style="height: 100%;">
				<!-- Everything here is put out by the order creation ajax script -->
			</div>

			<div class="col-sm-10">
				<div class="table-responsive">
					<table class="inventory_add table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
						<thead>
					         <tr>
					            <th class="col-sm-3">
					            	PART	
					            </th>
					            <th class="col-sm-3">
									Location (*Auto Conditional Populating)	
								</th>
			                    <th class="col-sm-1">
									Condition
					        	</th>
								<th class="col-sm-3">
					            	Serial	(*Scan or Press Enter on Input for More)
					            </th>
					            <th class="col-sm-1">
									Remaining Qty
					        	</th>
					            <th class="col-sm-1">
					            	Lot Inventory (No Serial)
					        	</th>
					         </tr>
						</thead>
						
						<tbody>
						<?php 
							//Grab all the parts from the specified PO #
							if($partsListing) {
								foreach($partsListing as $part): 
									$item = getPartName($part['partid']);
						?>
								<tr class="<?php echo ($part['qty'] - $part['qty_received'] == 0 ? 'order-complete' : ''); ?>">
									<td class="part_id" data-partid="<?php echo $part['partid']; ?>" data-part="<?php echo $item['part']; ?>">
										<?php 
											echo $item['part'] . '&nbsp;&nbsp;';
											echo $item['heci'] . '&nbsp;&nbsp;';
											echo $item['heci'] . '&nbsp;';
											echo $item['description']; 
										?>
									</td>
									<td  class="infiniteLocations">
										<div class="row-fluid">
											<div class="col-md-6" style="padding: 0 0 0 5px;">
												<?=loc_dropdowns('place')?>
											</div>
											<div class="col-md-6" style="padding: 0 0 0 5px">
												<?=loc_dropdowns('instance')?>
											</div>
										</div>
									</td>
									<td class="infiniteCondition">
										<select class="form-control condition_field condition" name="condition" data-serial="" style="margin-bottom: 5px; height: 31px;" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? 'disabled' : ''); ?>>
											<?php foreach(getEnumValue() as $condition): ?>
												<option <?php echo ($condition == $part['cond'] ? 'selected' : '') ?>><?php echo $condition; ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td class="infiniteSerials">
										<div class="input-group" style="margin-bottom: 6px;">
										    <input class="form-control input-sm" type="text" name="NewSerial" placeholder="Serial" data-saved="" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? 'disabled' : ''); ?>>
										    <span class="input-group-addon">
										        <button class="btn btn-secondary deleteSerialRow" type="button" disabled><i class="fa fa-trash fa-4" aria-hidden="true"></i></button>
										    </span>
							            </div>
									</td>
									<td class="remaining_qty">
										<input class="form-control input-sm" data-qty="" name="qty" placeholder="LOT QTY" value="<?php echo $part['qty'] - $part['qty_received']; ?>" readonly>
									</td>
									<td>
										<div class="checkbox">
											<label><input class="lot_inventory" style="margin: 0 !important" type="checkbox" <?php echo ($part['qty'] - $part['qty_received'] == 0 ? 'disabled' : ''); ?>></label>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</div> 
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
		<script type="text/javascript">
			function updateBillTo(){
				if ( $("#mismo").prop( "checked" )){
					var display = $("#select2-ship_to-container").html()
					var value = $("#ship_to").val();
		    		$("#select2-bill_to-container").html(display)
		    		$("#bill_to").append("<option selected value='"+value+"'>"+display+"</option>");
				}
			}
		</script>

	</body>
</html>