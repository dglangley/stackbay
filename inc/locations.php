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
	include_once $rootdir.'/inc/pipe.php';
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
    function loc_dropdowns($type = '', $selcted = '',$limit = '',$default = ''){
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
    	        if ($row['id'] == $selcted){$output .= ' selected ';}
    	        $output .= "value = '".$row['id']."'> ".$row['name']."</option>";
    	    }
    	    $output .= "
    			    </select>
    	        </div>";
        }    
        else if ($type == "place") {
            
            if ($selcted){
                $locations = mysqli_fetch_assoc(getLocation($selected));
                $selcted = $locations['place'];
            }
            
            $results = getplaces();
            
                
                $output = "<select class='form-control input-sm $type' style='padding-left:0px;'>";
    	        if(!$selcted and !$default){
                    $output .= "<option selected value = 'null'>Column</option>";
    	        }

        	    foreach($results as $row){
        	        $output .= "<option ";
        	        if ($row['place'] == $selcted){$output .= ' selected ';}
        	        $output .= "value = '".strtoupper($row['place'])."'> ".strtoupper($row['place'])."</option>";
        	    }
        	    $output .= "</select>";
        }
        else if ($type == "instance") {
            // 
            if ($selcted){
                $locations = mysqli_fetch_assoc(getLocation($selected));
                $selcted = $locations['instance'];
                $limit = $locations['place'];
            }
            $select = "Select DISTINCT instance From `locations`";
            $results = qdb($select);
                
            $output .= "<select class='form-control input-sm $type' style='padding-left:0px;'>";
            if(!$selcted and !$default){
                    $output .= "<option selected value = 'null'>Shelf</option>";
    	        }
            //Takes in the location ID and returns the 
    	    if ($results){
        	    foreach($results as $row){
        	        $output .= "<option ";
        	        $output .= " data-place = '".$row['place']."' ";
        	        if ($row['instance'] == $selcted && $row['instance']){$output .= ' selected ';}
        	        $output .= "value = '".strtoupper($row['instance'])."'> ".strtoupper($row['instance'])."</option>";
        	    }
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
            if($row['instance']){$display .= " - ".$row['instance'];}
        } else if($type == 'place') {
            $display = $row['place'];
        } else {
            $display = $row['instance'];
        }
        return $display;
    }


?>