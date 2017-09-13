<?php
    //This script interprets the locations to allow for easy tracking
    //This script parses the data, display it, and re-enter it into the database
    
    //Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/form_handle.php';
    
    function getLocation($location_id = '',$place = ''){
        
        $select = "SELECT * FROM locations";
        
        if ($location_id or $place){
            $select .= " WHERE ";
        }
        if ($location_id){
            $location_id = prep($location_id);
            $select .= " id = $location_id ";
        }
        if ($location_id && $place){
            $select .= " AND ";
        }
        if ($place){
            $place = prep($place);
            $select .= "place LIKE $place ";
        }
        $select .= ";";
        
        $results = qdb($select);
        return $results;    
    }
    
    function getplaces(){
        $select = "SELECT DISTINCT place FROM locations;";
        $results = qdb($select);
        return $results;
    }
    
    //Expects: Warehouse, Place, or Instance
    function loc_dropdowns($type = '', $selected = '',$limit = '',$default = ''){
        // Populates the location dropdown with the accurate value/paired information
        $type = strtolower($type);

        //Warehouse dropdown
        if($type == "warehouse"){
            $result = getWarehouse();
            $output = "<div>";
            // $output .= ($label)? "<label for='warehouse'>Warehouse:</label>" : '';
            $output .= "<select class='form-control input-xs warehouse'>";
            $output .= "<option value = 'none'>Warehouse</option>";
    	    foreach($result as $row){
    	        $output .= "<option ";
    	        if ($row['id'] == $selected){$output .= ' selected ';}
    	        $output .= "value = '".$row['id']."'> ".$row['name']."</option>";
    	    }
    	    $output .= "
    			    </select>
    	        </div>";
        }    
        else if ($type == "place") {

            if (is_numeric($selected)){
                $locations = mysqli_fetch_assoc(getLocation($selected));
                $selected = $locations['place'];
            }
            
            $results = getplaces();
            
                
                $output = "<select name='$type'class='form-control input-sm $type' style='padding-left:0px;'>";
    	        if(!$selected and !$default){
                    $output .= "<option selected value = 'null'>Column</option>";
    	        }

        	    foreach($results as $row){
        	        $output .= "<option ";
        	        if (strtoupper($row['place']) == $selected){$output .= ' selected ';}
        	        $output .= "value = '".strtoupper($row['place'])."'> ".strtoupper($row['place'])."</option>";
        	    }
        	    $output .= "</select>";
        }
        else if ($type == "instance" || $type =="bin") {
            // 
            if ($selected){
                $locations = mysqli_fetch_assoc(getLocation($selected));
                $selected = $locations['instance'];
                $limit = $locations['place'];
            }
            //WE WILL LIMIT LATER BY THE DROPDOWN VALUE
            $select = "Select * From `locations`;";
            // echo $select; exit;
            $results = qdb($select);
            
            $output .= "<select name='$type' class='form-control input-sm $type' style='padding-left:0px;'>";
            if(!$selected and !$default and !$locations){
                    $output .= "<option selected value = 'null'>".($type == "instance" ? '' : 'Bin')."</option>";
    	        }  else if($locations) {
    	            $output .= "<option selected value = 'null'></option>";
    	        }
            //Takes in the location ID and returns the 
    	    if ($results && $type == "instance"){
        	    foreach($results as $row){
        	        $output .= "<option data-place = '".strtoupper($row['place'])."' 
        	        ".(($row['instance'] == $selected && $row['instance'])? " selected" : "")."
        	        ".(($limit != $row['place'])? "style='display:none;'" : "")."
        	        value = '".strtoupper($row['instance'])."'> ".strtoupper($row['instance'])."</option>";
        	    }
    	    } else {
                for($i = 1; $i <= 10; $i++)
                    $output .= "<option value='".$i."''>".$i."</option>";
            }
    	    $output .= "
    			    </select>
    			    ";
        }
        return $output;
    }
    
    function dropdown_processor($place = '', $location = ''){
        //This will take a location and a place and return a single ID
        $select = "SELECT `id` FROM locations WHERE `place` LIKE ".prep($place);
        if ($location){
            $select .= " AND `instance` = ".prep($location).";";
        }
        // echo $select; exit;
        $result = qdb($select);
        
        $row = mysqli_fetch_assoc($result);
        return $row['id'];
    }
    
    function getWarehouse($whid = ''){
        //Function returns a list of mysql warehouse results
        $select = "SELECT * FROM warehouses";
        if($whid){
            $whid = prep($whid);
            $select .= " WHERE id = $whid";
        }
        $select .= ";";
        
        $results = qdb($select);
        return $results;
        
    }
    
    function display_location($location_id, $type =''){
        $display;
        
        $results = getLocation($location_id);
        $row = mysqli_fetch_assoc($results);
        
        if($type == '') {
            $display = $row['place'];
            if($row['instance']){$display .= "-".$row['instance'];}
        } else if($type == 'place') {
            $display = $row['place'];
        } else {
            $display = $row['instance'];
        }
        return $display;
    }
    
    function run_through_old($inventory_id){
        $new = "SELECT * FROM `inventory`";
        
        foreach($results as $row){
            $old = "SELECT * FROM `inventory_inventory` WHERE `serial_no` ;";
            $row['serial_no'] = $row[''];
        }
        
    }
    
    $LOCATION_MAP = array();
    function convert_locations($location_id, $old_to_new = true){
        global $LOCATION_MAP;
        
        $pipe_select = "SELECT `id`,`location` FROM `inventory_location` where `id` = $location_id;";
        $result = qdb($pipe_select, "PIPE") or die(qe()." | ".$pipe_select);
        $row = mysqli_fetch_assoc($result);
        $parsed = explode("-",$row['location']);
        
        $select = "SELECT * FROM location WHERE `place` LIKE ".prep($parsed[0]);
        $select = ($parsed[1] ? " AND `instance` LIKE ".prep($parsed[1]): "");
        $select .= ";";
    }
?>
