<?php
//=============================================================================
//=========================== Part Selection Output  ==========================
//=============================================================================
// I will be redoing the original table output function to include a part     |
// search function. This will be my general use case for the variation in     |
// each version. Essentially, this will aim to remove and reduce clicks from  |
// each entry. This will be a search and creation page. This is built with the|
// Sales page in mind at first, but the purchase can be a similar process.    |
//                                                                            | 
// Last intention update: Aaron Morefield - December 5th, 2016                |
//=============================================================================
	header('Content-Type: application/json');
	//Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
		include_once $rootdir.'/inc/dbconnect.php';
		include_once $rootdir.'/inc/format_date.php';
		include_once $rootdir.'/inc/format_price.php';
	    include_once $rootdir.'/inc/dictionary.php';
		include_once $rootdir.'/inc/getCompany.php';
		include_once $rootdir.'/inc/getWarranty.php';
		include_once $rootdir.'/inc/getPart.php';
		include_once $rootdir.'/inc/pipe.php';
		include_once $rootdir.'/inc/keywords.php';
		include_once $rootdir.'/inc/getRecords.php';
		include_once $rootdir.'/inc/getRep.php';
		include_once $rootdir.'/inc/form_handle.php';
		include_once $rootdir.'/inc/dropPop.php';


//------------------------------------------------------------------------------
//---------------------------- Function Declarations ---------------------------
//------------------------------------------------------------------------------

//There will be a mode parameter to determine if it needs to pull any information
    $mode = grab('mode'); //Expects: 'Search', 
    $search_string = grab('item'); 

//Function order:
//  Output the header
//  If there is already info entered for this order:
//      - Output the sub-rows
//  Output the search row.

    

//Output Header Row
function head_out(){
    //Static string for the present, but there is a chance that I can add
    //User hideable collumns later.
    $head = "<thead>";
    $head .= "<th>#</th>";
    $head .= "<th class='col-md-5'>Item Information</th>";
    $head .= "<th class='col-md-2'>Delivery Date</th>";
    $head .= "<th class='col-md-1'>Condition</th>";
    $head .= "<th class='col-md-1'>".dropPop("conditionid","","","",false,"warranty_global")."</th>";
    $head .= "<th class='col-md-1'>Qty</th>";
    $head .= "<th class='col-md-1'>Price</th>";
    $head .= "<th class='col-md-1'>Ext. Price</th>";
    $head .= "<th></th>";
    $head .= "</thead>";
    
    return $head;
}

//========================= Build the search function =========================
function search_row(){
    //The macro row will carry the same information as the sub rows, but will be
    //a global-set matching row. It will mirror David's Item output page

        //Default is ground aka 4 days
        $default_add = 4;
        $date = addBusinessDays(date("Y-m-d H:i:s"), $default_add);
        //Condition | conditionid can be set per each part. Will play around with the tactile (DROPPOP, BABY)
        //Aaron is going to marry Aaron 2036 
        $condition_dropdown = dropdown('conditionid','','','',false);
        //Warranty
        $warranty_dropdown = dropdown('warranty',$warranty,'','',false,'new_warranty');

/*
        $line = "
            <tr id = 'totals_row' style='display:none;'>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td style='text-align:right;'>Total:</td>
                <td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' id ='total' name='np_total' placeholder='0.00'></td>
                <td></td>
            </tr>
            <tr class ='search_row' style = 'padding:50px;background-color:#eff0f6;'>
        		<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='' style='height:28px;padding:0;text-align:center;'></td>
		        <td id = 'search'>
		            <div class='input-group'>
		              <input type='text' class='form-control input-sm' id = 'go_find_me' placeholder='SEARCH FOR...'>
		              <span class='input-group-btn'>
		                <button class='btn btn-sm btn-primary li_search_button'><i class='fa fa-search'></i></button>              
		            </span>
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
        		<td>".$condition_dropdown."</td>
        		<td>".$warranty_dropdown."</td>
        		<td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_qty' placeholder='QTY' value = ''></td>
            	<td>
	                <div class='input-group'>
	                    <span class='input-group-addon'>$</span>
	                    <input class='form-control input-sm' type='text' name = 'ni_price' placeholder='0.00' value=''>
	                </div>
	            </td>
        		<td><input class='form-control input-sm' readonly='readonly' tabIndex='-1' type='text' name='ni_ext' placeholder='0.00'></td>
                <td colspan='2' id = 'check_collumn'> 
                    <a class='btn-sm btn-flat success pull-right multipart_sub' >
                    <i class='fa fa-save fa-4' aria-hidden='true'></i></a>
                </td>
			</tr>
		    <!-- Adding load bar feature here -->
	   	 	<tr class='search_loading'><td colspan='12'><span style='text-align:center; display: none; padding-top: 10px;'>Loading...</span></td></tr>
    
			<!-- dummy line for nothing found -->
	   	 	<tr class='nothing_found' style='display: none;'><td colspan='12'><span style='text-align:center; display: block; padding-top: 10px; font-weight: bold;'>Nothing Found</span></td></tr>
		";
    
	    return $line;
*/
	    //The macro row will /NOT/ be stored, and will dissappear after each new item is added.
}

function format($parts){
    $name = "<span class = 'descr-label'>".$parts['part']." &nbsp; ".$parts['heci']."</span>";
    $name .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($parts['manf'])." &nbsp; ".dictionary($parts['system']).'</span> <span class="description-label">'.dictionary($parts['description']).'</span></div>';

    return $name;
}


//==================== Build the individual version output ====================
function sub_rows($search = ''){
    $page = grab('page');
    $show = grab('show');
    //On Click of the "GO!" Button, populate a dropped down list of each of the parameters.
    
    //Declare general collection variables
    $row = '';
    $stock = array();
    $inc = array();
    
    //General Collumn information
        //Item information
        $items = hecidb($search);
            foreach ($items as $id => $data){
                // Grab all the matches from whatever search was passed in.
                $matches[] = $id;
            }
            if($matches){
                $match_string = implode(", ",$matches);
            
                //Get all the currently in hand
                $inventory = "SELECT SUM(qty) total, partid FROM inventory WHERE partid in ($match_string) GROUP BY partid;";
                $in_stock  = qdb($inventory);
                if (mysqli_num_rows($in_stock) > 0){
                    foreach ($in_stock as $r){
                        $stock[$r['partid']] = $r['total'];
                    }
                }
                
                // Get all the ordered quantities
                $purchased = "SELECT SUM(qty) total, partid FROM purchase_items WHERE partid in ($match_string) GROUP BY partid;";
                $incoming = qdb($purchased);
                if (mysqli_num_rows($incoming) > 0){
                    foreach ($incoming as $i){
                        $inc[$i['partid']] = $i['total'];
                    }
                }
                
                
                
                if (mysqli_num_rows($in_stock) == 0 && mysqli_num_rows($incoming) == 0 && ($page == 'Sales' || $page == 's') && !$show){
                    $rows = "
                        <tr class = 'items_label'>
                            <td></td>
                            <td colspan='6' style=''>No parts in stock. <span id='show_more' style='color: #428bca; cursor: pointer;'>Click here to show all</span></td>
                            <td style=''></td>
                        </tr>
                    ";
                }
                else{
                        $rows = "
                        <tr class = 'items_label'>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>
                                <div class='row-fluid'>
                                    <div class='col-md-6' style='padding:0%;text-align:center;'>Stock</div>
                                    <div class='col-md-6' style='padding:0%;text-align:center;'>Order</div>
                                </div>
                            </td>
                            <td></td>
                        </tr>";
        
                    foreach ($items as $id => $info){
                        $sellable = false;
                    
                        $text = "<div class='row-flud'>";
                        $text .= "<div title='Stocked' class='col-md-6 new_stock' style='text-align:center;height:100%;color:green;padding:0%;'><b>";
                        //Output the quantity of items sellable
                        if(array_key_exists($id, $stock)){
                            $sellable = true;
                            $text .= $stock[$id];
                        }
                        else{
                            $text .= "&nbsp;";
                        }
                        $text .= "</b></div>";
                        
                        $text .= "<div title='Ordered' class='col-md-6 new_stock' style='text-align:center;color:red;padding-left:0%;padding-right:0%;'>";
                        if(array_key_exists($id, $inc)){
                            $sellable = true;
                            $text .= $inc[$id];
                        }
                        else{
                            $text .= "&nbsp;";
                        }
                        $text .= "</div>";
                        $text .= "</div>";
                        if (($page == 'Sales' || $page == 's') && !$sellable && !$show){
                            $text = '';
                            continue;
                        }
                        $rows .= "
                        <tr class = 'search_lines' data-line-id = $id>
                            <td></td>
                            <td>";
            
                        $rows .=(format($info));
                        $rows .= "</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td><input class='form-control input-sm' type='text' name='ni_qty' placeholder='QTY' value = ''></td>
                            <td></td>
                            <td>$text</td>
                            <td></td>
                        </tr>
                        ";
                    //Ask David about the line-level control with each of these.
                    //Delivery Date
                    
                    //Condition | conditionid can be set per each part. Will play around with the tactile
                    //Warranty    
                    //Qty | Each of the qty inputs had supplimental inventory information
                    //Price 
                    //EXT price
                    }
                }
        }//End the "If there are matches" check
            else{
                $rows .= "
                    <tr class = '' data-line-id = $id>
                        <td></td>
                        <td colspan='6' style=''>Nothing Found</td>
                        <td style=''></td>
                    </tr>
                ";
            }
    return $rows;
}

//==============================================================================
//==================================== Main ====================================
//==============================================================================
$output = '';

//$output .= head_out();
if ($mode == "search"){
    $output .= sub_rows($search_string);
}
else{
//david 3/1/17 not using no mo
//    $output .= search_row();
}
//echo $output;exit;

echo json_encode($output);
exit;

?>
