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
    $head .= "<th class='col-md-1'>Warranty</th>";
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
    //a global-set matching row. It will mirror David's Item output page?
        $line = "<tr class ='search_row' style = 'padding:50px;'>";
        
        //Line Number
        $line .= "<td style='padding:0;'><input class='form-control input-sm' type='text' name='ni_line' placeholder='#' value='".$row['line']."' style='height:28px;padding:0;text-align:center;'></td>";
        
        //Item Search Funciton
        $line .= "
        <td id = 'search'>
            <div class='input-group'>
              <input type='text' class='form-control' id = 'go_find_me' placeholder='Search for...'>
              <span class='input-group-btn'>
                <button class='btn btn-secondary li_search_button' type='button'>Go!</button>
              </span>
            </div>
        </td>";

        //Delivery Date
        $line .= "
        <td>				
		    <div class='input-group date datetime-picker-line'>
			    <input type='text' name='ni_date' class='form-control input-sm' value='$date' style = 'min-width:50px;'/>
				    <span class='input-group-addon'>
				        <span class='fa fa-calendar'></span>
				    </span>
            </div>
        </td>";
        
        //Condition | condition can be set per each part. Will play around with the tactile (DROPPOP, BABY)
        $line .= "<td>".dropdown('condition','','','',false)."</td>";
        //Warranty
        $line .= "<td>".dropdown('warranty',$warranty,'','',false)."</td>";

        
        //Price
        $line .= "<td><input class='form-control input-sm' type='text' name = 'ni_price' placeholder='UNIT PRICE' value='$price'></td>";
        
        //Qty | Each of the qty inputs had supplimental inventory information
        $line .="<td><input class='form-control input-sm' readonly='readonly' type='text' name='ni_qty' placeholder='QTY' value = '$qty'></td>";
        
        //EXT PRICE
        $line .= "<td><input class='form-control input-sm' readonly='readonly' type='text' name='ni_ext' placeholder='ExtPrice'></td>";
        
        //Submission button
        $line .= "<td colspan='2' id = 'check_collumn'> 
                    <a class='btn-flat success pull-right multipart_sub' >
                    <i class='fa fa-plus fa-4' aria-hidden='true'></i></a>
                </td>";

    $line .= "</tr>";
    
    return $line;
    //The macro row will /NOT/ be stored, and will dissappear after each new item is added.
}

function another_planet($parts){
    $name = $parts['part']." &nbsp; ".$parts['heci'].' &nbsp; '.$parts['Manf'].' '.$parts['system'].' '.$parts['Descr'];
    return $name;
}


//==================== Build the individual version output ====================
function sub_rows($search = ''){
    //On Click of the "GO!" Button, populate a dropped down list of each of the parameters.
    
    //Declare general collection variables
    $row = '';
    
    //General Collumn information
        //Item information
        $items = hecidb($search);
            foreach ($items as $id => $info){
                $rows .= "
                <tr class = 'search_lines' data-line-id = $id>
                    <td></td>
                    <td>".another_planet($info)."</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><input class='form-control input-sm' type='text' name='ni_qty' placeholder='QTY' value = ''></td>
                    <td>test | test | test</td>
                    <td></td>
                </tr>
                ";
            //Ask David about the line-level control with each of these.
            //Delivery Date
            
            //Condition | condition can be set per each part. Will play around with the tactile
            //Warranty    
            //Qty | Each of the qty inputs had supplimental inventory information
            //Price 
            //EXT price
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
    $output .= search_row();
}

echo json_encode($output);
exit;

?>