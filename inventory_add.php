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
	//include_once $rootdir.'/inc/order-creation.php';
	
	$order_number = isset($_REQUEST['on']) ? $_REQUEST['on'] : "New";
	$order_type = ($_REQUEST['ps'] == 'p' || $_REQUEST['ps'] == 'Purchase') ? "Purchase" : "Sales";

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
	
	//Associated Order: If none, assume nothing happens.
	//
	
	
?>

<!DOCTYPE html>
<html>
	<head>
		<?php 
			include_once $rootdir.'/inc/scripts.php';
		?>
		<link rel="stylesheet" href="../css/operations-overrides.css" type="text/css" />
	</head>
	
	<body class="sub-nav" data-order-type="<?=$order_type?>" data-order-number="<?=$order_number?>">
	<!----------------------- Begin the header output  ----------------------->
		<div class="container-fluid pad-wrapper">
		<?php include 'inc/navbar.php';?>
		<div class="row table-header" id = "order_header" style="margin: 0; width: 100%;">
			<div class="col-sm-4"></div>
			<div class="col-sm-4 text-center">
				<h1>Inventory Addition</h1>
			</div>
			<div class="col-sm-4">
				<button class="btn-flat pull-right" id = "save_button_inventory" style="margin-top:2%;margin-bottom:2%;">
					Complete
				</button>
			</div>
		</div>
		
		
			<!-------------------- $$ OUTPUT THE MACRO INFORMATION -------------------->
			<div class="left-side-main" data-page="addition">
				<!-- Everything here is put out by the order creation ajax script -->
			</div>


			<div class="inventory_lines col-sm-10 table-responsive" style="margin-top:30px;">
					<table class="table table-hover table-striped table-condensed" style="table-layout:fixed;"  id="items_table">
							<thead>
						         <tr>
						            <th class="col-sm-4">
						            	<span class="line"></span>		
						            	PART	
						            </th>
						            <th class="col-sm-2">
						            	<span class="line"></span>		
						            	Serial	
						            </th>
						            <th class="col-sm-2">   	
						            	<span class="line"></span>   	
						            	Qty
						            </th>
						            <th class="col-sm-1">
										<span class="line"></span>   	
										Location	
									</th>
						        	<th class="col-sm-1">
						            	Status
						        	</th>
						            <th class="col-sm-1">
						            	<span class="line"></span>
										Condition
						        	</th>
						            <th class="col-sm-1">
						            	<span class="line"></span>
						            	&nbsp;
						        	</th>
				
						         </tr>

							    <tr class = "addRecord">
						            <td id='search_collumn'>
						            	<div style="max-width:inherit;">
											<select class='item_search' style="max-width:inherit;overflow:hidden;">
												<option data-search = 'Nothing at the moment'>Item</option>
											</select>
										</div>
									</td>
									<td id='serial'>
							            <input class="form-control input-sm" type="text" name = "NewSerial" placeholder="Serial">
									</td>
						            <td>
										<div class="input-group">
									    	<input type="text" class="form-control" id="new_qty" aria-label="Text input with checkbox">
							            <span class="input-group-addon">Serialize Each?</span>
									      	<span class="input-group-addon">
									        	<input type="checkbox" name="serialize">
											</span>
				
									    </div>
								    </td>
						            <td>
				                            <!--<div class="ui-select" style="width:100%;">-->
		                                <select class="form-control" id = "new_location">
		                                    <option selected="">W: 12</option>
		                                    <option>W: 13</option>
		                                    <option>W: 15</option>
		                                </select>
				                            <!--</div>-->
				                    </td>
						            <td>
						            	<div class="btn-group">
						            		<select class="form-control status" name="status">
					                    	<?php foreach(getEnumValue('inventory', 'status') as $status): ?>
												<option <?php echo ($status == $serial['status'] ? 'selected' : '') ?>><?php echo $status; ?></option>
											<?php endforeach; ?>
											</select>
										</div>
				                    </td>
						            <td>
										<select class="form-control condition_field condition" name="condition">
											<?php foreach(getEnumValue() as $condition): ?>
												<option <?php echo ($condition == $serial['item_condition'] ? 'selected' : '') ?>><?php echo $condition; ?></option>
											<?php endforeach; ?>
										</select>
				                    </td>
				                    <td>
				                    	<button class="btn-flat" id="inv_add_record">
				                    		ADD RECORD
				                    	</button>
				                    </td>
							    </tr>
							</thead>
						<tbody id="serial_each_table">
							
				        </tbody>
						<tfoot>
							<tr>
								<td>
					        		<a class="show_link" href="#"  style="display: none;">Show More</a>
								</td>
							</tr>
						</tfoot>
					</table>
						
				</div>
			</div>
		</div> 
		<!-- End true body -->
		<?php include_once 'inc/footer.php';?>
		<script src="js/operations.js"></script>
		<script type="text/javascript">

		</script>

	</body>
</html>