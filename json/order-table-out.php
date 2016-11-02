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
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/pipe.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';

	//Mode expects one of the following: initial, append, load
	$mode = ($_REQUEST['mode'])? ($_REQUEST['mode']) : "load";
	
//------------------------------------------------------------------------------
//---------------------------- Function Declarations ---------------------------
//------------------------------------------------------------------------------
	//The general purpose array-to-row output
	function build_row($row = array()){
	   	$row_out = "
			<tr class = 'easy-output'>
		        <td>".$row['line']."</td>
	            <td data-search='".$row['search']."'>".$row['display']."</td>
	            <td>".$row['date']."</td>
	            <td>".$row['qty']."</td>
	            <td>".format_price($row['uPrice'])."</td>
	            <td>".format_price($row['qty']*$row['uPrice'])."</td>
				<td class='forms_edit'><i class='fa fa-pencil fa-4' aria-hidden='true'></i></td>
				<td class='forms_trash'><i class='fa fa-trash fa-4' aria-hidden='true'></i></td>
		    </tr>
		    <tr class='lazy-entry' style='display:none;'>
				<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='".$row['line']."' style='height:28px;padding:0;text-align:center;'></td>
	            <td id='search_collumn'>
	            	<div class = 'item-selected'>
						<select class='item_search'>
							<option data-search = '".$row['search']."'>".$row['display']."</option>
						</select>
					</div>
				</td>
	            <td>				
	            	<div class='input-group date datetime-picker-line'>
			            <input type='text' name='ni_date' class='form-control input-sm' value='".$row['date']."' style = 'min-width:50px;'/>
			            <span class='input-group-addon'>
			                <span class='fa fa-calendar'></span>
			            </span>
		            </div>
			    </td>
	            <td><input class='form-control input-sm' type='text' name='ni_qty' placeholder='QTY' value = '".$row['qty']."'></td>
	            <td><input class='form-control input-sm' type='text' name = 'ni_price' placeholder='UNIT PRICE' value='".$row['uPrice']."'></td>
	            <td><input class='form-control input-sm' readonly='readonly' type='text' name='ni_ext' placeholder='ExtPrice'></td>
				<td colspan='2' id = 'check_collumn'>
					<a class='btn-flat success pull-right line_item_submit' >
						<i class='fa fa-check fa-4' aria-hidden='true'></i>
					</a>
				</td>
		    </tr>";
	    return $row_out;
	}
	
	//This function will append to the table any changes made while on the page
	function append_row(){
		
		//Get the posted values from the form
		$search = isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '0';
		$date = isset($_REQUEST['date']) ? trim($_REQUEST['date']) : '1/1/1991';
		$qty = isset($_REQUEST['qty']) ?  trim($_REQUEST['qty']) : '0';
		$uPrice = isset($_REQUEST['unitPrice']) ? trim($_REQUEST['unitPrice']) : '0';
		$line = isset($_REQUEST['line']) ? trim($_REQUEST['line']) : '';
		
		//Process the search ID into readable text.
		$display = '';
		$p = hecidb($search,'id');
		foreach ($p as $r){
             $display = $r['part']." &nbsp; ".$r['heci'].' &nbsp; '.$r['Manf'].' '.$r['system'].' '.$r['Descr'];
		}
		
		//Store all caught data into the standard array and build the row.
		$row = array(
			'search' => $search,
			'display' => $display,
			'date' => $date,
			'qty' => $qty,
			'uPrice' => $uPrice,
			'line' => $line,
			);
		$row_out = build_row($row);
		
		echo json_encode($row_out);
	}
	
	//The initial table output method will call on the load of the page. It
	//accesses the database and outputs the current rows of the database.
	function initalTableOutput (){ 
		$table = '';
		
		//Determine from the post to the page what we are working on. Assume new if there is no number.
	    $order_type = isset($_REQUEST['type']) ? trim($_REQUEST['type']) : 'po';
	    $order_number = isset($_REQUEST['number']) ? trim($_REQUEST['number']) : 'new';
	    
	    //If this is not a new order, load the already existing information from the table.
	    if ($order_number != 'New'){
			$q_line = "SELECT * FROM ";
			$q_line .= ($order_type == 'po') ? 'purchase_items' : 'sales_items';
			$q_line .= "WHERE ".($order_type == 'po') ? 'po_number' : 'so_number';
			$q_line .= " = '$number';";
			$old = qdb($q_line);
			
			//Parse the prexisting lines into the output format.
			foreach ($old as $db_row) {
				//Each of the rows will be built into the table here.
				$r = array(
				'line' => '',
				'number' => '',
				'search' => '',
				'date' => '',
				'qty' => '',
				'uPrice' => '',
				);
				$table .= build_row($r);
			}
	    }
		echo json_encode($table);
		exit;
	}
//------------------------------------------------------------------------------
//------------------------------------ Main ------------------------------------ 
//------------------------------------------------------------------------------
	if ($mode == "append" || $mode == "update"){
		append_row();
	}
	elseif ($mode == "load"){
		initalTableOutput();
	}
	else{
		"Permanent";
	}
?>
