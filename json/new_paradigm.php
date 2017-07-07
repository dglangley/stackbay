
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
// 	header('Content-Type: application/json');
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
		include_once $rootdir.'/inc/display_part.php';
		include_once $rootdir.'/inc/filter.php';
		
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


function is_component($row){return ($row['classification'] == "component");}
function not_component($row){return ($row['classification'] != "component");}

function search_as_heci($search_str){
		$page = grab('page');
        $type = grab('type');
		$heci7_search = false;

        if($type =="repair") {
            $results = hecidb($search_str, 'id');
        } else {

    		if (strlen($search_str)==10 AND ! is_numeric($search_str) AND preg_match('/^[[:alnum:]]{10}$/',$search_str)) {
    			$query = "SELECT heci FROM parts WHERE heci LIKE '".substr($search_str,0,7)."%'";
    // 			$query .= (? " AND classification = 'component'" : "");
    			$query .= "; ";
    			$result = qdb($query);
    			if (mysqli_num_rows($result)>0) { $heci7_search = true; }
    		} else {
    		    $query = "SELECT heci FROM parts WHERE heci LIKE '".$search_str."%'";
    		  //  $query .= (($page =="Repair" || $page == "Tech")? " AND classification = 'component'" : "");
    		    $query .= "; ";
    			$result = qdb($query) or jsonDie(qe()." | $query");
    			if (mysqli_num_rows($result)) {
    			    $result = mysqli_fetch_assoc($result);
    			    $search_str = $result['heci'];
    			    $heci7_search = true;
    			}
    		}

    		if ($heci7_search) {
    			$results = hecidb(substr($search_str,0,7));
    		} else {
    			$results = hecidb(format_part($search_str));
    		}
        }
        //$page =="Repair" || $order_number!='New'

        // if($page=="Tech"){
        //     $results = array_filter($results, "is_component");
        // } 
        // else {
        //     $results = array_filter($results, "not_component");
        // }
		return $results;
}

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

}

//==================== Build the individual version output ====================
function sub_rows($search = ''){
    $page = grab('page');
    $show = grab('show');
    //On Click of the "GO!" Button, populate a dropped down list of each of the parameters.
    $any_hidden = false;
    //Declare general collection variables
    $row = '';
    $stock = array();
    $inc = array();
    $matches = array();
    //General Collumn information
        //Item information
        
		$rows = "";
        $multi = explode(' ',$search);
        $items = array();
        foreach($multi as $search_s){
            $items = search_as_heci(trim($search_s));
            foreach ($items as $id => $data){
                // Grab all the matches from whatever search was passed in.
                $matches[$id] += 1;
            }
        }
        if($matches){
            arsort($matches);
            // $match_string = implode(", ",$matches);
            $match_string = '';
            foreach($matches as $match => $weight){
                $match_string .= prep($match).", ";
            }
            $match_string = rtrim($match_string, ", ");
            
            
            
            //Get all the currently in hand
            $inventory = "SELECT SUM(qty) total, partid FROM inventory WHERE partid in ($match_string)
            ".($page =="Repair" || $page=="Tech" || $page=='build' ?" AND (`status` = 'shelved' OR `status` = 'received') ":"")."
            GROUP BY partid;";
            $in_stock  = qdb($inventory) or jsonDie(qe()." | $inventory");
            if (mysqli_num_rows($in_stock)){
                foreach ($in_stock as $r){
                    $stock[$r['partid']] = $r['total'];
                }
            }
            
            // Get all the ordered quantities
            $purchased = "
            SELECT (SUM( pi.qty ) - SUM(pi.qty_received)) total, pi.`partid` 
            FROM  `purchase_items` pi 
            WHERE pi.partid in ($match_string) 
            GROUP BY pi.partid LIMIT 10;"; //All values ever purchased
            //Use an inventory join method here at some point
                
            $incoming = qdb($purchased) or jsonDie(qe()." | $purchased");
            if (mysqli_num_rows($incoming)){
                foreach ($incoming as $i){
                    if($i['total'] > 0){
                        $inc[$i['partid']] = $i['total'];
                    }
                }
            }
                
            if(mysqli_num_rows($in_stock) || mysqli_num_rows($incoming) || (! mysqli_num_rows($in_stock) && ! mysqli_num_rows($in_stock))){
                //build the results rows
                $rows = "
                    <!-- Created from $search -->
                    <tr class = 'items_label'>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page =="Repair" || $page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page =="Repair" || $page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td></td>
                        <td></td>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"").">
                            <div class='row-fluid'>
                                <div class='".($page=="Tech" || $page=='build' ?"col-md-12":"col-md-6")."' style='padding:0%;text-align:center;'>Stock</div>
                                <div ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")." class='col-md-6' style='padding:0%;text-align:center;'>Order</div>
                            </div>
                        </td>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                    </tr>";
    
                foreach ($items as $id => $info){
                    $sellable = false;
                    $qty_in = 0;
                
                    $text = "<div class='row-flud'>";
                    $text .= "<div title='Stocked' class='col-md-6 new_stock' style='text-align:center;height:100%;color:green;padding:0%;'><b>";
                    //Output the quantity of items sellable
                    if(array_key_exists($id, $stock)){
                        $sellable = true;
                        $text .= $stock[$id];
                        $qty_in = (is_numeric($stock[$id]) ? $stock[$id] : '0');
                    }
                    else{
                        $text .= "&nbsp;";
                    }
                    $text .= "</b></div>";
                    if($page!="Tech") {
                        $text .= "<div title='Ordered' class='col-md-6 new_stock' style='text-align:center;color:red;padding-left:0%;padding-right:0%;'>";
                        if(array_key_exists($id, $inc)){
                            $sellable = true;
                            $text .= $inc[$id];
                        }
                        else{
                            $text .= "&nbsp;";
                        }
                        $text .= "</div>";
                    }
                    $text .= "</div>";
                    if (($page == 'Sales' || $page == 's') && !$sellable && !$show){
                        $text = '';
                        $any_hidden = true;
                        continue;
                    }
                    $rows .= "
                    <tr class = 'search_lines' data-line-id = $id>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td>";

                    $rows .=(display_part($info));
                    $rows .= "</td>
                        <td".($page =="Repair" || $page =="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page =="Repair" || $page =="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td><input class='form-control input-sm search_line_qty' type='text' name='ni_qty' placeholder='QTY' value = ''></td>
                        <td ".($page=="Tech" || $page=='build' ?"style='display:none;'":"")."></td>
                        <td ".($page=='build' ?"style='display:none;'":"")." class='data_stock' data-stock='$qty_in'>". $text."</td>
                        <td ".($page=='build' ?"style='display:none;'":"")."></td>
                    </tr>
                    ";
                //Ask David about the line-level control with each of these.
                //Delivery Date

                //Condition | conditionid can be set per each part. Will play around with the tactile
                //Warranty    
                //Qty | Each of the qty inputs had supplimental inventory information
                //Price 
                //EXT price
                }//END foreach item allowed
            }
            if (($page == 'Sales' || $page == 's') && !$show && $any_hidden){
                $rows .= "
                    <tr class = 'items_label'>
                        <td></td>
                        <td colspan='6' style=''>";
                        $rows .= ((mysqli_num_rows($in_stock) == 0 && mysqli_num_rows($incoming) == 0) ? "No parts in stock." : "");
                        $rows .= "<span id='show_more' style='color: #428bca; cursor: pointer;'>Click here to show more</span></td>
                        <td style=''></td>
                    </tr>
                ";
            }
        } else {
            $rows .= "
                <tr class = 'items_label' data-line-id = $id>
                    <td></td>
                    <td colspan='6' style=''>No Matches</td>
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
$output .= sub_rows($search_string);

//echo $output;exit;

echo json_encode($output);
exit;

?>
