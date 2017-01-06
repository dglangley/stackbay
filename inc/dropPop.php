<?php
    
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
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getTerms.php';

    
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
	
    
    //This function works to prepopulate the dropdowns and output their selected option
    function dropdown($field, $selected = '', $limit = '',$size ='col-sm-6',$label=true,$custom_id=false){
    //Fields Allowed: Carrier, Services, Warranty
        $field = strtolower($field);
        if (strtolower($field) == 'carrier'){
        //Carrier outputs the carrier information based off of no selector parameters
            $carrier = getFreight('carrier');
    		
    		//if there is any value returned from the carrier function
    		if ($carrier){
    			foreach ($carrier as $c){
    				if($c['id'] == $selected){
    					$carrier_options .=	"<option selected value=".$c['id'].">".$c['name']."</option>";
    				}
    				else{
    					$carrier_options .= "<option value = ".$c['id'].">".$c['name']."</option>";
    				}
    			}
    	   	}
            
            //Check to see if there is a particular id set, if not default to carrier
            $id = ($custom_id) ? $custom_id : "carrier";
            
            //Output the final dropdown menu
            $output = "<div class='$size'>	            	
        			    <label for='$id'>Carrier:</label>
        			    <select id = '$id' class='form-control'>
        				    $carrier_options
        			    </select>
        	        </div>";
        }
        else if (strtolower($field) == 'services'){
        //Services outputs service values based off a passed in carrier limit.
            $service = getFreight('services',$limit);
    		if ($service){
    			foreach ($service as $s){
    				if($s['id'] == $selected){
    					$service_options .=	"<option selected value=".$s['id']." data-days=".$s['days'].">".$s['method']."</option>";
    				}
    				else{
    					$service_options .= "<option value = ".$s['id']." data-days=".$s['days'].">".$s['method']."</option>";
    				}
    			}
    	   	}
            $id = ($custom_id) ? $custom_id : "service";
            $output = "<div class='$size' id = '".$id."_div'>	            	
    			    <label for='services'>Service:</label>
    			    <select id = '$id' class='form-control'>
    				    $service_options
    			    </select>
    	        </div>";
        }
        else if ($field == 'warranty'){
            $warranty = getWarranty();
    		if ($warranty){
    			foreach ($warranty as $w){
    				if($w['id'] == $selected){
    					$warranty_options .= "<option selected value=".$w['id'].">".$w['warranty']."</option>";
    				}
    				else{
    					$warranty_options .= "<option value = ".$w['id'].">".$w['warranty']."</option>";
    				}
    			}
    	   	}
        $id = ($custom_id) ? $custom_id : "warranty";
        $output = "<div class='$size'>";          	
        $output .= ($label)? "<label for='warranty'>Warranty:</label>" : '';
        $output .= "<select id = '$id' class='form-control warranty $limit'>";
    	if($id == 'warranty_global'){
    	    $output .= "<option selected value='no'>Warranty</option>";
    	}
    	$output .= "	    $warranty_options
    			    </select>
    	        </div>";
        }
        else if ($field == 'terms'){
            //Here limit will be used as a companyid
            $companyid = prep($limit, "'%'");

            if ($companyid != "'%'"){
                //Find the company's most popular option (IF THE SELECTED FIELD IS NOT ALREADY SELECTED)            
                if (!$selected){
                    $default = "SELECT `termsid`, COUNT(`termsid`) n FROM sales_orders 
                    WHERE `companyid` LIKE $companyid AND `created` BETWEEN NOW() - INTERVAL 30 DAY AND NOW()
                    GROUP BY `termsid`
                    ORDER BY n DESC
    				LIMIT 1;";
                    $preselected = qdb($default);
                    if (isset($preselected)){
                        foreach ($preselected as $row){
                            $selected = $row['termsid'];
                        }
                    }
                }
                
                //Pull anything /explicitly allowed/ from the company terms table
                $company_specific = "SELECT `termsid` From company_terms WHERE `companyid` LIKE $companyid;";
                $c_terms = qdb($company_specific);
                
                //If there Is a result from the company table, return their results
                if (mysqli_num_rows($c_terms) > 0){
                    $range = " WHERE `id` IN (";
                    foreach($c_terms as $r){
                        $range .= "'".$r['termsid']."', ";
                    }
                $range = rtrim($range, ', ');
                $range .= ")";
                }
            }
                    
                    
                $terms = "Select * FROM terms";
                
                //Add in any limiting parameters
                $terms = $terms.$range.";";
                $results = qdb($terms);
                
                //Third: populate their dropdown with the terms item
        		if ($results){
        			foreach ($results as $t){
        				if($t['id'] == $selected){
        					$terms_options .= "<option selected value=".$t['id'].">".$t['terms']."</option>";
        				}
        				else{
        					$terms_options .= "<option value = ".$t['id'].">".$t['terms']."</option>";
        				}
        			}
        	   	}
        $id = ($custom_id) ? $custom_id : "terms";
        $output = "<div class='$size' id = '".$id."_div'>";          	
        $output .= ($label)? "<label for='$id'>Terms:</label>" : '';
        $output .= "<select id = '$id' class='form-control'>";
    	$output .= "	    $terms_options
    			    </select>
    	        </div>";
        }
        else if ($field == 'condition'){
            
            // Grab all the variations of the enum into an iterable array
            $condition = getEnumValue();
		    
		    //If the condition value returns any results
		    if ($condition){
    			foreach ($condition as $c){
    				if($c == $selected){
    					$cond .= "<option selected value=$c>".ucwords($c)."</option>";
    				}
    				else{
    					$cond .= "<option value=$c>".ucwords($c)."</option>";
    				}
    			}
    	   	}
   	        $id = ($custom_id) ? $custom_id : "condition";
            $output = "<div class=''>";
            $output .= ($label)? "<label for='condition'>Condition:</label>" : '';
            $output .= "<select id = '$id' class='form-control condition'>";
                	if($id == 'condition_global'){
                        $output .= "<option selected value='no'>Condition</option>";
                	}
    	    $output .= "    $cond
    			    </select>
    	        </div>";

    	   	
        }
        else if ($field == 'status'){
            
            // Grab all the variations of the enum into an iterable array
            $status = getEnumValue("inventory","status");
		    
		    //If the condition value returns any results
		    if ($status){
    			foreach ($status as $s){
    				if($s == $selected){
    					$status .= "<option selected value=$s>".ucwords($s)."</option>";
    				}
    				else{
    					$status .= "<option value=$s>".ucwords($s)."</option>";
    				}
    			}
    	   	}
   	        $id = ($custom_id) ? $custom_id : "status";
            $output = "<div class=''>";
            $output .= ($label)? "<label for='status'>Status:</label>" : '';
            $output .= "<select id = '$id' class='form-control status'>";
                	if($id == 'status_global'){
                        $output .= "<option selected value='no'>Status</option>";
                	}
    	    $output .= "    $status
    			    </select>
    	        </div>";
        }
        else{
            $output = 'dicks and a half';
        }
        return $output;
    }


?>