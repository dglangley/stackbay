<?php
//=============================================================================
//============================= Order Table Output ============================
//=============================================================================
// Although this is the first script I made in this suite, it still holds much|
// of the underlying premises as true. It will load the left-hand table with  |
// the following rules in mind: on initial load, do nothing. On in page edits,|
// return a row to be appended, and on existing load return matching lines.   |
//                                                                            | 
// Last update: Aaron Morefield - October 18th, 2016                          |
//=============================================================================
	header('Content-Type: application/json');
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/dictionary.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getWarranty.php';
		include_once $rootdir.'/inc/getCondition.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/dropPop.php';
		include_once $rootdir.'/inc/order_parameters.php';
		include_once $rootdir.'/inc/display_part.php';
		include_once $rootdir.'/inc/check_received.php';
		
	//Mode expects one of the following: update, append, load
	//Load: only the database, output the values associated ot the order number
	//Append: Gets a new instance of the row, submits it as an insert, and 
	$mode = ($_REQUEST['mode'])? ($_REQUEST['mode']) : "load";
	
//------------------------------------------------------------------------------
//---------------------------- Function Declarations ---------------------------
//------------------------------------------------------------------------------
	function getRepairNumber($repair_item_id) {
		$ro_number;

		$query = "SELECT ro_number FROM repair_items WHERE id = ".prep($repair_item_id)." LIMIT 1;";
		$result = qdb($query);

		if (mysqli_num_rows($result)) {
			$result = mysqli_fetch_assoc($result);
			$ro_number = $result['ro_number'];
		}

		return $ro_number;
	}

	//The general purpose array-to-row output
	function build_row($row = array()){
		//Re-access the mode, just to prevent uncertainty of it's arrival.
		$mode = ($_REQUEST['mode'])? ($_REQUEST['mode']) : "load";
		$o = o_params(grab("type"));
		
		//Process the search ID into readable text.
		$display = '';
		$select_display = '';
		$partid = $row['search'];
		// $display = display_part(current(hecidb($partid,"id")));
		$p = hecidb($partid,'id');
		foreach ($p as $r){
            //$display = $r['part']." &nbsp; ".$r['heci'].' &nbsp; '.$r['Manf'].' '.$r['system'].' '.$r['Descr'];
			$select_display = $r['part'].' '.$r['heci'];
            $display = "<span class = 'descr-label'>".$r['part']." &nbsp; ".$r['heci']."</span> &nbsp; ";
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">';
			$display .= dictionary($r['manf'])." &nbsp; ".dictionary($r['system']).'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';
		}
		
		
		
		//Gather and process the date into a readable date string
		$date = date("m/d/Y",strtotime($row['date']));
		
		//Build the display row
	   	$row_out = "<tr class='easy-output ".((($row['qty'] == '0' || !$row['qty']) && !$o['rtv']) ? 'strikeout' : '')."' data-record='".$row['id']."'>";
	   	if(!$o['tech'] && !$o['build']){
	   		$row_out .= "
	        <td class = 'line_line' data-line-number = ".$row['line'].">".$row['line']."</td>";
	    }

	    $row_out .= "<td class = 'line_part' ".($o['build'] ? 'name ="partid" value="'.$partid.'"' : '')." data-search='".$partid."' data-record='".$row['id']."'>".$display;
	    if($o['build']){
	    	$row_out .= "<input class='hidden' name='partid' value='".$partid."'>";
	    	$row_out .= "<input class='hidden' name='qty_variable' value='".$row['qty']."'>";
	    }
        // If is repair_item_id as the label then kill the option to edit
        $row_out .= "<td class='line_ref_1'>
			        		<div class='col-md-6'>
			        			<input class='form-control input-sm ".($row['ref_1_label'] == 'repair_item_id' ? '' : 'ref_1')."' type='text' name='".($row['ref_1_label'] == 'repair_item_id' ? '' : 'ref_1')."' value='".(($row['ref_1'] AND $row['ref_1_label'] == 'repair_item_id') ? getRepairNumber($row['ref_1']) : $row['ref_1'])."' ".($row['ref_1_label'] == 'repair_item_id' ? 'disabled' : '').">
			        			".($row['ref_1_label'] == 'repair_item_id' ? "<input class='form-control input-sm ref_1' type='hidden' name='ref_1' value='".$row['ref_1']."'>" : '')."
			        		</div>
			        		<div class='col-md-6'>
			        			<select class='form-control input-sm ref_1_label' ".($row['ref_1_label'] == 'repair_item_id' ? 'readonly' : '').">
			        				".($row['ref_1_label'] == 'repair_item_id' ? '<option value="repair_item_id" selected>RO</option>' : "
			        						<option value='' disabled ".($row['ref_1_label'] == '' ? 'Selected' : '').">Select</option>
			        						<option value='PN' ".($row['ref_1_label'] == 'PN' ? 'Selected' : '').">PN</option>
					        				<option value='SAP' ".($row['ref_1_label'] == 'SAP' ? 'Selected' : '').">SAP</option>
					        				<option value='PO' ".($row['ref_1_label'] == 'PO' ? 'Selected' : '').">PO</option>
			        					")."
			        			</select>
			        		</div>
			        	</td>
			        	<td class='line_ref_2'>
			        		<div class='col-md-6'>
			        			<input class='form-control input-sm ref_2' type='text' name='ref_2' value='".($row['ref_2'])."'>
			        		</div>
			        		<div class='col-md-6'>
			        			<select name='ref_2_label' class='form-control input-sm ref_2_label'>
			        				<option value='' disabled ".($row['ref_2_label'] == '' ? 'Selected' : '').">Select</option>
			        				<option value='PN' ".($row['ref_2_label'] == 'PN' ? 'Selected' : '').">PN</option>
			        				<option value='SAP' ".($row['ref_2_label'] == 'SAP' ? 'Selected' : '').">SAP</option>
			        				<option value='PO' ".($row['ref_2_label'] == 'PO' ? 'Selected' : '').">PO</option>
			        			</select>
			        		</div>
			        	</td>";
		$row_out .= "</td>";
	    if(!$o['tech'] && !$o['build']){
        	$row_out .= "<td class = 'line_date' data-date = '$date'>".$date."</td>";
        }
		if(!$o['repair'] && !$o['tech'] && !$o['build']){
			$row_out .= "
			<td class = 'line_cond'  data-cond = '".$row['conditionid']."'>".getCondition($row['conditionid'])."</td>
        	<td class = 'line_war'  data-war = ".$row['warranty'].">".($row['warranty'] == '0' ? 'N/A' : getWarranty($row['warranty'],'name'))."</td>";
		}
	   	$row_out .= "<td class = 'line_qty' data-stock=".($row['available'] ? $row['available'] : '0')." data-qty = ".$row['qty'].">".$row['qty']."</td>";

	   	if(!$o['tech'] && !$o['build']){
	   		$row_out .= "
            <td class = 'line_price'>".format_price($row['uPrice'])."</td>
            <td class = 'line_linext'>".format_price($row['qty']*$row['uPrice'])."</td>
            <td class = 'line_ref' style='display: none;' data-label='".$row['ref_1_label']."'>".$row['ref_1']."</td>";
        }
		if(!$o['tech'] && !$o['build']){
    		$row_out .= "<td class='forms_edit' style='cursor: pointer;'><i class='fa fa-pencil fa-4' aria-hidden='true'></i></td>";
		} else {
			$row_out .= "<td></td>";
		}
        $row_out .= "<td";
        if(num_received($o['type'],$row['id']) == 0){
			$row_out .=" class='forms_trash' style='cursor: pointer;'><i class='fa fa-trash fa-4' aria-hidden='true'></i>";
        }else{
        	$row_out .= ">";
        }
        $row_out .= "</td>";
		$row_out .= "</tr>";
	
	//If the row is being updated, this information would be duplicated, so ignore it
	   if ($mode != 'update'){
		   $row_out .= "<tr class='lazy-entry' style='display:none;'>
					<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='".$row['line']."' data-value = '".$row['line']."' style='height:28px;padding:0;text-align:center;'></td>
		            <td class='search_collumn'>
		            	<div class = 'item-selected'>
							<select class='item_search input-xs'>
								<option data-search = '$partid'>".$select_display."</option>
							</select>
						</div>
					</td>
		            <td>				
		            	<div class='input-group date datetime-picker-line'>
				            <input type='text' name='ni_date' class='form-control input-sm' value='$date' data-value = '$date' style = 'min-width:50px;'/>
				            <span class='input-group-addon'>
				                <span class='fa fa-calendar'></span>
				            </span>
			            </div>
				    </td>";
			if(!$o['repair']){
		    $row_out .="<td>".dropdown('conditionid',$row['conditionid'],'','',false)."</td>
			            <td>".dropdown('warranty',$row['warranty'],'','',false)."</td>";
	        }
			$row_out .="<td><input class='form-control input-sm oto_qty' type='text' name='ni_qty' placeholder='QTY' value = '".$row['qty']."' data-value = '".$row['qty']."'></td>
		            <td><input class='form-control input-sm oto_price' type='text' name = 'ni_price' placeholder='0.00' value='".$row['uPrice']."' data-value = '".$row['uPrice']."'></td>
		            <td><input class='form-control input-sm oto_ext' readonly='readonly' type='text' name='ni_ext' placeholder='0.00'></td>
		            <td style='display: none;' data-label='".$row['ref_1_label']."'><input class='form-control input-sm line_ref_1' readonly='readonly' type='text' name='' placeholder='0.00' value ='".$row['ref_1']."'></td>
					<td colspan='2' id = 'check_collumn'>
						<div class='btn-group'>
							<a class='btn-flat danger pull-right line_item_unsubmit' style='padding: 3px;margin-left:2px;'>
								<i class='fa fa-minus fa-4' aria-hidden='true'></i>
							</a>
							
							<a class='btn-flat success pull-right line_item_submit' style='padding: 3px;'>
								<i class='fa fa-save fa-4' aria-hidden='true'></i>
							</a>
						</div>
					</td>
				</tr>";
		}
		return $row_out;
	}
	
	//This function will append to the table any changes made while on the page
	function append_row($mode){ 
		
		//Get the posted values from the form
		$search = grab('search');
		$date = grab('date');
		$qty = grab('qty','0');
		$uPrice = grab('unitPrice','0');
		$line = grab('line');
		$id = grab('id','NULL');
		$warranty = grab('warranty','NULL');
		$conditionid = grab('conditionid');
		$available = grab('available');
		$ref_1 = grab("line_ref_1");
		$ref_1_label = grab("line_ref_1_label");
		//Store all caught data into the standard array and build the row.
		$row = array(
			'id' => $id,
			'search' => $search,
			'display' => $display,
			'date' => $date,
			'qty' => $qty,
			'uPrice' => $uPrice,
			'line' => $line,
			'warranty' => $warranty,
			'conditionid' => $conditionid,
			'available' => $available,
			'ref_1' => $ref_1,
			'ref_1_label' => $ref_1_label
			);
		$row_out = build_row($row);
		
		echo json_encode($row_out);
	}
	
	//The initial table output method will call on the load of the page. It
	//accesses the database and outputs the current rows of the database.
	function initialTableOutput ($mode){ 
		
		//Prep the initial 
		$table = '';
		//Determine from the post to the page what we are working on. Assume new if there is no number.
	    
	    
	    $order_type = grab("type","purchase");// $order_type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : 'Purchase';
	    $order_number = grab("number","new"); // $order_number = isset($_REQUEST['number']) ? trim($_REQUEST['number']) : 'New';
		$o = o_params($order_type);

	    //If this is not a new order, load the already existing information from the table.
	    if ($order_number != 'New' && $mode != 'build'){
				$q_form = "SELECT * FROM ".$o['item']." WHERE ".$o['id']." = '$order_number';";
				$old = qdb($q_form);
				
				foreach ($old as $r){
					
					$new_row = array(
					'id' => $r['id'],
					'line' => $r['line_number'],
					'search' => $r['partid'],
					'date' => $r[$o['date_field']], //This is Aaron's cheater answer to an if statement. It will break when these are part of the same table
					'qty' => $r['qty'],
					'uPrice' => $r['price'],
					'warranty' => $r['warranty'],
					'conditionid' => $r['conditionid'],
					'ref_1' => $r['ref_1'],
					'ref_1_label' => $r['ref_1_label'],
					'ref_2' => $r['ref_2'],
					'ref_2_label' => $r['ref_2_label'],
					);
					$table .= build_row($new_row);			
				}
		} else if ($mode == 'rtv') {
			$row_num = 0;
			//Debug decode
			// $rows = json_decode($_REQUEST['rtv_array']);
			foreach($_REQUEST['rtv_array'] as  $lineid => $item) {
				$qty_grab = "SELECT SUM(`qty`) total FROM `inventory` where `purchase_item_id` = ".prep($lineid).";";
				$qty_res = qdb($qty_grab) or die(qe()." $qty_grab");
				$qty_res = mysqli_fetch_assoc($qty_res);
				$qty = $qty_res['total'];
				$row_num++;
				$new_row = array(
					'id' => 'new',
					'line' => $row_num, 
					'search' => current($item), //
					'date' => date("n/j/Y"), 
					'qty' => $qty, //This blew Andrew's Brain when we were using Key as qty
					'uPrice' => 0.00,
					'ref_1' => $lineid,
					'ref_1_label' => 'purchase_item_id',
					'warranty' => 0,
					'conditionid' => 2
				);
				$table .= build_row($new_row);
				//$table .= key($item) . "<br>";
			}
		} else if ($mode == 'invoice') {
			$row_num = 0;
			foreach($_REQUEST['rtv_array'] as  $lineid => $item) {
				$row_num++;
				$new_row = array(
					'id' => 'new',
					'line' => $row_num, 
					'search' => current($item), //
					'date' => date("n/j/Y"), 
					'qty' => key($item), //This blows Andrew's Brain
					'uPrice' => 0.00,
					'ref_1' => $lineid,
					'ref_1_label' => 'purchase_item_id',
					'warranty' => 0,
					'conditionid' => 2
				);
				$table .= build_row($new_row);
				//$table .= key($item) . "<br>";
			}
		} else if ($mode == 'repair') {
			$qty = 1;

			// $query_repair = "SELECT ro_number FROM repair_items WHERE id = ".prep($_REQUEST['repair']).";";
			// $repair_result = qdb($query_repair) or die(qe() . ' ' . $query_repair);

			// if(mysqli_num_rows($repair_result)) {
			// 	$repair_item = mysqli_fetch_assoc($repair_result);
			// 	$query = "SELECT * FROM purchase_requests WHERE partid = ".prep($_REQUEST['search'])." AND ro_number = ".prep($repair_item['ro_number'])." ORDER BY requested DESC LIMIT 1;";
			// 	$result_request = qdb($query);

			// 	if(mysqli_num_rows($result_request)) {
			// 		$request_item = mysqli_fetch_assoc($result_request);
			// 		$qty = $request_item['qty'];
			// 		//print_r($request_item);
			// 	}
			// }

			//echo $qty . 'test';
			$parts = $_REQUEST['part'];

			//foreach($parts as $partid => $row) {
				//foreach ($row as $ro_number => $row2) {
					//foreach ($row2 as $qty => $checks) {
						$new_row = array(
							'id' => 'new',
							'line' => '',
							'search' => '322405',
							'date' => date("n/j/Y"), //This is Aaron's cheater answer to an if statement. It will break when these are part of the same table
							'qty' => $qty,
							'uPrice' => 0.00,
							'warranty' => 0,
							'conditionid' => 2,
							//'ref_1' => $r['ref_1'],
							'ref_1_label' => 'repair_item_id',
						);

						$table .= build_row($new_row);
					//}
				//}
			//}

				
		} else if ($mode == 'build') {
			$qty = 1;

			$query_build = "SELECT * FROM builds WHERE id = ".prep($_REQUEST['number']).";";
			$build_result = qdb($query_build) or die(qe() . ' ' . $query_build);

			if(mysqli_num_rows($build_result)) {
				$build_item = mysqli_fetch_assoc($build_result);
				$partid = $build_item['partid'];
				$qty = $build_item['qty'];
			}

			if($partid) {
				$new_row = array(
					'id' => 'new',
					'line' => '',
					'search' => $partid,
					'date' => date("n/j/Y"), //This is Aaron's cheater answer to an if statement. It will break when these are part of the same table
					'qty' => $qty,
					'uPrice' => 0.00,
					'warranty' => 0,
					'conditionid' => 2,
					//'ref_1' => $r['ref_1'],
					// 'ref_1_label' => 'repair_item_id',
				);
				$table .= build_row($new_row);	
			}
		}
		
		echo json_encode($table); 
		exit;
	}
//------------------------------------------------------------------------------
//------------------------------------ Main ------------------------------------ 
//------------------------------------------------------------------------------
	if ($mode == "append" || $mode == "update"){
		append_row($mode);
	} else if ($mode == "load" || $mode == "rtv" || $mode == "repair" || $mode == "build") {
		initialTableOutput($mode);
	} else {
		"Permanent";
	}
?>
