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
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getWarranty.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/pipe.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/dropPop.php';
		
	//Mode expects one of the following: update, append, load
	//Load: only the database, output the values associated ot the order number
	//Append: Gets a new instance of the row, submits it as an insert, and 
	$mode = ($_REQUEST['mode'])? ($_REQUEST['mode']) : "load";
	
//------------------------------------------------------------------------------
//---------------------------- Function Declarations ---------------------------
//------------------------------------------------------------------------------
	//The general purpose array-to-row output
	function build_row($row = array()){
		//Re-access the mode, just to prevent uncertainty of it's arrival.
		
		$mode = ($_REQUEST['mode'])? ($_REQUEST['mode']) : "load";
		
		//Process the search ID into readable text.
		$display = '';
		$partid = $row['search'];
		$p = hecidb($partid,'id');
		foreach ($p as $r){
             $display = $r['part']." &nbsp; ".$r['heci'].' &nbsp; '.$r['Manf'].' '.$r['system'].' '.$r['Descr'];
		}
		
		
		
		//Gather and process the date into a readable date string
		$date = date("m/d/Y",strtotime($row['date']));
		
		//Build the display row
	   	$row_out = "
		<tr class='easy-output' data-record='".$row['id']."'>
	        <td class = 'line_line' data-line-number = ".$row['line'].">".$row['line']."</td>
            <td class = 'line_part' data-search='".$partid."' data-record='".$row['id']."'>".$display."</td>
            <td class = 'line_date' data-date = '$date'>".$date."</td>
            <td class = 'line_cond'  data-cond = ".$row['condition'].">".ucwords($row['condition'])."</td>
            <td class = 'line_war'  data-war = ".$row['warranty'].">".getWarranty($row['warranty'],'name')."</td>
            <td class = 'line_price'>".format_price($row['uPrice'])."</td>
            <td class = 'line_qty'  data-qty = ".$row['qty'].">".$row['qty']."</td>
            <td class = 'line_linext'>".format_price($row['qty']*$row['uPrice'])."</td>
			<td class='forms_edit'><i class='fa fa-pencil fa-4' aria-hidden='true'></i></td>
			<td class='forms_trash'><i class='fa fa-trash fa-4' aria-hidden='true'></i></td>
		</tr>";
	
	//If the row is being updated, this information would be duplicated, so ignore it
	   if ($mode != 'update'){
		   $row_out .= "<tr class='lazy-entry' style='display:none;'>
					<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='".$row['line']."' style='height:28px;padding:0;text-align:center;'></td>
		            <td id='search_collumn'>
		            	<div class = 'item-selected'>
							<select class='item_search'>
								<option data-search = '$partid'>".$display."</option>
							</select>
						</div>
					</td>
		            <td>				
		            	<div class='input-group date datetime-picker-line'>
				            <input type='text' name='ni_date' class='form-control input-sm' value='$date' style = 'min-width:50px;'/>
				            <span class='input-group-addon'>
				                <span class='fa fa-calendar'></span>
				            </span>
			            </div>
				    </td>
		            <td>".dropdown('condition',$row['condition'],'','',false)."</td>
		            <td>".dropdown('warranty',$row['warranty'],'','',false)."</td>
		            <td><input class='form-control input-sm' type='text' name = 'ni_price' placeholder='UNIT PRICE' value='".$row['uPrice']."'></td>
		            <td><input class='form-control input-sm' type='text' name='ni_qty' placeholder='QTY' value = '".$row['qty']."'></td>
		            <td><input class='form-control input-sm' readonly='readonly' type='text' name='ni_ext' placeholder='ExtPrice'></td>
					<td colspan='2' id = 'check_collumn'>
						<a class='btn-flat success pull-right line_item_submit' >
							<i class='fa fa-check fa-4' aria-hidden='true'></i>
						</a>
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
		$condition = grab('condition');

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
			'condition' => $condition,
			);
		$row_out = build_row($row);
		
		echo json_encode($row_out);
	}
	
	//The initial table output method will call on the load of the page. It
	//accesses the database and outputs the current rows of the database.
	function initalTableOutput (){ 
		
		//Prep the initial 
		$table = '';
		
		//Determine from the post to the page what we are working on. Assume new if there is no number.
	    $order_type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : 'Purchase';
	    $order_number = isset($_REQUEST['number']) ? trim($_REQUEST['number']) : 'New';

	    //If this is not a new order, load the already existing information from the table.
	    if ($order_number != 'New'){
	    	
			$q_form = "SELECT * FROM ";
			$q_form .= ($order_type == 'Purchase') ? 'purchase_items' : 'sales_items';
			$q_form .= " WHERE ";
			$q_form .= ($order_type == 'Purchase') ? 'po_number' : 'so_number';
			$q_form .= " = '$order_number';";
			
			$old = qdb($q_form);
			foreach ($old as $r){
				$new_row = array(
				'id' => $r['id'],
				'line' => $r['line_number'],
				'search' => $r['partid'],
				'date' => $r['delivery_date'],
				'qty' => $r['qty'],
				'uPrice' => $r['price'],
				'warranty' => $r['warranty'],
				'condition' => $r['cond']
				);
				$table .= build_row($new_row);			
				
			}
	    	
			echo json_encode($table); 
			exit;
			
		// 	$old = qdb($q_line);
			

	 //   }
		// echo json_encode($table);
		// exit;
	}
	}
//------------------------------------------------------------------------------
//------------------------------------ Main ------------------------------------ 
//------------------------------------------------------------------------------
	if ($mode == "append" || $mode == "update"){
		append_row($mode);
	}
	elseif ($mode == "load"){
		initalTableOutput();
	}
	else{
		"Permanent";
	}
?>
