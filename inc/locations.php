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
        if ($location_id){
        }
        
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
        // echo $select;
        $results = qdb($select);
        return $results;    
    }
    
    function getplaces(){
        $select = "SELECT DISTINCT place FROM locations;";
        $results = qdb($select);
        return $results;
    }
    
    function loc_dropdowns($type = '', $selcted = '',$limit = ''){
        // Populates the location dropdown with the accurate value/paired information
        $type = strtolower($type);
        
        //Warehouse dropdown
        if($type == "warehouse"){
            $result = getWarehouse();
            $output = "<div>";
            // $output .= ($label)? "<label for='warehouse'>Warehouse:</label>" : '';
            $output .= "<select class='form-control warehouse'>";
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
            $output = "<div>";
                
                $output .= "<select class='form-control $type'>";
        	    foreach($results as $row){
        	        $output .= "<option ";
        	        if ($row['place'] == $selcted){$output .= ' selected ';}
        	        $output .= "value = '".$row['place']."'> ".$row['place']."</option>";
        	    }
        	    $output .= "
        			    </select>
        	        </div>";
        }
        else if ($type == "instance") {
            
            //Takes in the location ID and returns the 
            if ($selcted){
                $locations = mysqli_fetch_assoc(getLocation($selected));
                $selcted = $locations['instance'];
                $limit = $locations['place'];
            }
            if($limit){
                $results = getLocation('',$limit);
            }
            
            $output = "<div>";
                
            $output .= "<select class='form-control $type'>";
    	    if ($results){
        	    foreach($results as $row){
        	        $output .= "<option ";
        	        if ($row['instance'] == $selcted){$output .= ' selected ';}
        	        $output .= "value = '".$row['instance']."'> ".$row['instance']."</option>";
        	    }
    	    }
    	    $output .= "
    			    </select>
    	        </div>";
        }
        return $output;
    }
    
    function dropdown_processor($place = '', $location = ''){
        //This will take a location and a place and return a single ID
        $select = "SELECT id FROM locations WHERE place LIKE $place";
        if ($location){
            $select .= " AND location = $location";
        }
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
    // function getLocation($instance_ids = '',$type='',$warehouse = ''){
    //     $results = array();
    //     // Get location returns the location shortcodes paired with their instance
    //         $select = "SELECT w.name warehouse, w.addressid, warehouseid whid, l.name name, l.id locationid, lt.name type, lt.short_code short";
    //         $select .= " FROM `locations` l, warehouses w, location_type lt ";
    //         $select .= "WHERE lt.id = l.typeid AND w.id = l.warehouseid";
    //     if ($instance_ids){
    //         $instance_ids = prep($instance_ids);
    //         $select .= " AND l.id IN ($instance_ids)";
    //     }
    //     if ($type){
    //         $type = prep($type);
    //         $select .= " AND lower(lt.name) LIKE $type ";
    //     }
    //     if ($warehouse){
    //         $warehouse = prep($warehouse);
    //         $select .= " AND warehouseid = $warehouse ";
    //     }
    //     $select .= ";";
    //     $locations = qdb($select);
    //     //Loop through locations results and make an associative array
    //     if(mysqli_num_rows($locations) == 0){return;}
    //     foreach($locations as $loc){
    //         $results[$loc['locationid']] = array(
    //             'warehouse' => $loc['warehouse'],
    //             'address' => $loc['addressid'],
    //             'whid' => $loc['whid'],
    //             'name' => $loc['name'],
    //             'type' => $loc['type'],
    //             'short' => $loc['short']
    //             );
    //     }
    //     return $results;
    // }
    
    
    // function get_relations($locationid,$whereis = 'either'){
    //     //GET_RELATIONS takes a single location id and returns the relation information
    //     $results = array(
    //         "children" => array(),
    //         "parents" => array()
    //         );
    //     if ($locationid){
    //         if (is_numeric($locationid)){
                
    //             //Prep the location ID for insertion
    //             $locationid = prep($locationid);
                
    //             if($whereis == "parent" or $whereis == "either"){
    //                 //Get all results for everything this is a PARENT of
    //                 $children_select = "SELECT DISTINCT contains FROM location_relation where instance = $locationid;";
    //                 $children = qdb($children_select);
    //                 if (mysqli_num_rows($children)){
    //                     foreach($children as $child){
    //                         $results["children"][] = $child["contains"];
    //                     }
    //                 }
    //             }
                
    //             if($whereis == "child" or $whereis == "either"){
    //                 //Get everything this ID is a child of.
    //                 $parents_select = "SELECT DISTINCT instance FROM location_relation where contains = $locationid;";
    //                 $parents = qdb($parents_select);
    //                 if (mysqli_num_rows($parents)){
    //                     foreach($parents as $parent){
    //                         $results["parents"][] = $parent["instance"];
    //                     }
    //                 }
    //             }
    //         }
    //         else{
    //             //Perform text-to-locationid conversion
    //         }
    //     }
    //     return $results;
    // }
    
    // function loc_dropdowns($type = '', $selcted = '',$warehouse = '',$limit = ''){
    //     // Populates the location dropdown with the accurate value/paired information
    //     $type = strtolower($type);
        
    //     //Warehouse dropdown
    //     if($type == "warehouse"){
    //         $result = getWarehouse();
    //         $output = "<div>";
    //         // $output .= ($label)? "<label for='warehouse'>Warehouse:</label>" : '';
    //         $output .= "<select class='form-control warehouse'>";
    //         $output .= "<option value = 'none'>Warehouse</option>";
    // 	    foreach($result as $row){
    // 	        $output .= "<option ";
    // 	        if ($row['id'] == $selcted){$output .= ' selected ';}
    // 	        $output .= "value = '".$row['id']."'> ".$row['name']."</option>";
    // 	    }
    // 	    $output .= "
    // 			    </select>
    // 	        </div>";
    //     }    
    //     else if ($type) {
            
    //         $children = '';
    //         if ($limit){
    //             $relations = get_relations($limit);
    //             $children = implode(", ", $relations['children']);
    //         }
    //         $results = getLocation($children,$type,$warehouse);
    //         $output = "<div>";
    //             // $output .= ($label)? "<label for='warehouse'>Warehouse:</label>" : '';
    //             $output .= "<select class='form-control $type'>";
    //             $output .= "<option value = 'none'>".ucwords($type)." </option>";
    //     	    foreach($results as $id => $row){
    //     	        $output .= "<option ";
    //     	        if ($id == $selcted){$output .= ' selected ';}
    //     	        $output .= "value = '$id'> ".$row['name']."</option>";
    //     	    }
    //     	    $output .= "
    //     			    </select>
    //     	        </div>";
    //     }
    //     return $output;
    // }
    
    // function display_location($locationid){
    //     //This funciton will be a textual representation of every portion of a nested
    //     //location. It will be a comma-separated function outputting the location from
    //     //broadest to least broad.
        
    //     //Initialize the chain
    //     $chain = array();
    //     $chain[] = $locationid;
        
    //     //Get the first parent
    //     $result = get_relations($locationid,"child");
    //     $parent = $result["parents"][0];
        
    //     //For each parent, get the value of the level immediately above it
    //     while ($parent){
    //         $chain[] = $parent;
    //         $result = get_relations($parent,"child");
    //         $parent = $result["parents"][0];
    //     }
        
    //     //Recursively climb upward to build a sensical output statement
    //     $chain = array_reverse($chain);
    //     $output = '';
    //     foreach($chain as $locationid){
    //         $loc = getLocation($locationid);
    //         $output .= $loc[$locationid]['name'].", ";
    //     };
    //     $output = rtrim($output,", ");
        
    //     return $output;
        
    // }
    
    // function getPartLocations($partid){
    //     //Part Locations searches for the inventory information of a part based 
    //     //off a given part id.
        
    //     $partid = prep($partid);
    //     $select = "SELECT Distinct locationid FROM inventory where partid = $partid;";
        
    // }

?>