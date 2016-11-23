<?php
    
    
    //Get Freight - The function which makes each of the 
    
    $rootdir = $_SERVER['ROOT_DIR'];
	
	    include_once $rootdir.'/inc/dbconnect.php';	
	    
		function getFreight($type = 'max',$value = "",$id = ""){
            
            $value = prep($value,"'%'");
            $id = prep($id,"'%'");
    		if($type == 'max'){
    		    $select = "
    		    SELECT freight_carriers.companyid, companies.name, freight_accounts.account_no,
    		     freight_accounts.companyid, freight_services.method, freight_services.notes 
                FROM freight_carriers
                LEFT JOIN (freight_services) ON (freight_carriers.id = freight_services.carrierid)
                LEFT JOIN (companies) ON (companies.id = freight_carriers.companyid)
                LEFT JOIN (freight_accounts) ON (freight_accounts.freight_co_id = freight_carriers.id)";
    		}
            else if($type == 'carrier'){
                $select = "SELECT freight_carriers.id id, name FROM freight_carriers, companies where companies.id = companyid AND freight_carriers.id LIKE '%';";
            }
            else if($type == 'services'){
                $select = "SELECT * FROM freight_services WHERE `id` LIKE $id AND `carrierid` LIKE $value;";
            }
            else if($type == 'account'){
                $select = "SELECT * FROM freight_accounts WHERE `freight_co_id` LIKE $value;";
            }
            $results = qdb($select);
            
            
            return $results;
	}
?>
