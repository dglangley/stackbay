<?php
    
    $rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/getWarranty.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/getTerms.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/order_parameters.php';

    
    function getEnumValue( $table = 'inventory', $field = 'status' ) {
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

            $output = "<div class='$size' style='padding-right: 0;'>";
            if($label) {
        	    $output .=  "<label for='$id'>Carrier</label>";
            }
            $output .="
        			    <select id = '$id' class='form-control input-sm'>
        				    $carrier_options
        			    </select>
        	        </div>";
        }
        else if (strtolower($field) == 'services'){
        //Services outputs service values based off a passed in carrier limit.
            $service = getFreight('services',$limit);
    		if ($service && $limit){
    			foreach ($service as $s){
    				if($s['id'] == $selected){
    					$service_options .=	"<option selected value=".$s['id']." data-days=".$s['days'].">".$s['method']."</option>";
    				}
    				else{
    					$service_options .= "<option value = ".$s['id']." data-test='".$selected."' data-days=".$s['days'].">".$s['method']."</option>";
    				}
    			}
    	   	}
            $id = ($custom_id) ? $custom_id : "service";
            $output = "<div class='$size' id = '".$id."_div'>	            	
    			    <label for='services'>Service</label>
    			    <select id = '$id' class='required form-control input-sm'>
    				    $service_options
    			    </select>
    	        </div>";
        }
        else if ($field == 'warranty'){
            $id = ($custom_id) ? $custom_id : "warranty";
            
            $warranty = getWarranty();
    		if ($warranty){
    		    $init = false;
    			foreach ($warranty as $w){
    			    if($id == 'warranty_global') {
    			        $init = true;
    			    }
    			    
    				if($w['id'] == $selected || ($selected == '' && $w['warranty'] == '30 Days' && !$init)){
    					$warranty_options .= "<option selected value=".$w['id'].">".$w['warranty']."</option>";
    				}
    				else{
    					$warranty_options .= "<option value = ".$w['id'].">".$w['warranty']."</option>";
    				}
    			}
    	   	}
        $output = "<div class='$size'>";          	
        $output .= ($label)? "<label for='warranty'>Warranty:</label>" : '';
        $output .= "<select id = '$id' class='form-control input-sm warranty $limit'>";
    	if($id == 'warranty_global'){
    	    $output .= "<option selected value=''>Warranty</option>";
    	}
    	$output .= "	    $warranty_options
    			    </select>
    	        </div>";
        }
        else if ($field == 'terms'){
            //Here limit will be used as a companyid
            $limit = explode("-", $limit);
            $companyid = prep($limit[0], "'%'");
            $o = o_params($limit[1]);
            
            if ($companyid != "'%'"){
                //Find the company's most popular option (IF THE SELECTED FIELD IS NOT ALREADY SELECTED)            
                if (!$selected){
                    $default = "SELECT `termsid`, COUNT(`termsid`) n, `created` 
                    FROM ".$o['order']."
                    WHERE `companyid` LIKE $companyid
                    GROUP BY `termsid`
                    ORDER BY IF(DATE_SUB(CURDATE(),INTERVAL 365 DAY)<MAX(created),0,1), n DESC
                    limit 1";
                    $preselected = qdb($default) or die(qe()." $default");
                    if (mysqli_num_rows($preselected)){
                        $row = mysqli_fetch_assoc($preselected);
                        $selected = $row['termsid'];
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
                $range .= ",15)";
                }
            }
			if (! $selected) { $selected = 12; }//david 2/9/17 to default all orders to CC if none selected
                    
                    
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
        $output .= ($label)? "<label for='$id'>Terms</label>" : '';
        $output .= "<select id = '$id' class='form-control input-sm'>";
    	$output .= "	    $terms_options
    			    </select>
    	        </div>";
        }
        else if ($field == 'conditionid'){
            // Grab all the variations of the enum into an iterable array
            
            getCondition('', $limit);//initialize global $CONDITIONS
			global $CONDITIONS;

            $id = ($custom_id) ? $custom_id : "conditionid";
		    
		    //If the condition value returns any results
	        $init = false;
			$cond_list = '';
			foreach ($CONDITIONS as $conditionid => $cond) {
   			    if($id == 'condition_global') {
   			        $init = true;
   			    }
    			    
   				if($conditionid == $selected || ($selected == '' && $conditionid == '2' && !$init)){
   					$cond_list .= "<option selected value=$conditionid>".$cond."</option>";
   				}
   				else{
   					$cond_list .= "<option value=$conditionid>".$cond."</option>";
    			}
    	   	}
    	   	
            $output = "<div class=''>";
            $output .= ($label)? "<label for='condition'>Condition:</label>" : '';
            $output .= "<select id = '$id' name = 'condition' class='form-control input-sm conditionid'>";
                	if($id == 'condition_global'){
                        $output .= "<option selected value=''>Condition</option>";
                	}
    	    $output .= "    $cond_list
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
            $output .= "<select id = '$id' class='form-control input-sm status'>";
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
