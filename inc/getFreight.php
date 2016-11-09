<?php
    
    
    //Get Freight - The function which makes each of the 
    
    $rootdir = $_SERVER['ROOT_DIR'];
	
	    include_once $rootdir.'/inc/dbconnect.php';	
	    
		function getFreight($type = 'max',$value = '0'){
            
    //         if ($type == 'max'){
    // 		    $select = "
    // 		    SELECT companyid, fc.id, fs.method, fs.notes, fa.account_no 
    // 		    FROM freight_carriers fc, companies, freight_services fs, freight_accounts fa  
    // 		    WHERE fc.companyid = companies.id AND fs.carrierid = fc.id AND fa.freight_co_id = fc.id;";
    // 		}
    // 		else 
    		if($type == 'max'){
    		    $select = "
    		    SELECT freight_carriers.companyid, companies.name, freight_accounts.account_no,
    		     freight_accounts.companyid, freight_services.method, freight_services.notes 
                FROM freight_carriers
                LEFT JOIN (freight_services) ON (freight_carriers.id = freight_services.carrierid)
                LEFT JOIN (companies) ON (companies.id = freight_carriers.companyid)
                LEFT JOIN (freight_accounts) ON (freight_accounts.freight_co_id = freight_carriers.id)";
    		}
            else if('carrier'){
                $select = "SELECT freight_carriers.id, name FROM freight_carriers, companies where companies.id = companyid;";
            }
            else if('services'){
                $select = "SELECT * FROM freight_services WHERE carrierid = $value;";
            }
            else if('account'){
                $select = "SELECT * FROM freight_accounts WHERE freight_co_id = $value;";
            }
            $results = qdb($select);
            return $results;
	}
?>
