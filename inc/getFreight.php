<?php
    
    
    //Get Freight - The function which makes each of the 
    
    $rootdir = $_SERVER['ROOT_DIR'];
	
	    include_once $rootdir.'/inc/dbconnect.php';	
	    
		function getFreight($type = 'max',$value = "",$id = "",$field = ""){
            
            $value = prep($value,"'%'");
            $id = prep($id,"'%'");
    		if($type == 'max'){
    		    $select = "
    		    SELECT freight_accounts.id accountid, freight_accounts.account_no, freight_carriers.companyid car_co_id, companies.name carrier_co,
    		     freight_accounts.companyid, freight_services.method, freight_services.notes 
                FROM freight_carriers
                LEFT JOIN (freight_services) ON (freight_carriers.id = freight_services.carrierid)
                LEFT JOIN (companies) ON (companies.id = freight_carriers.companyid)
                LEFT JOIN (freight_accounts) ON (freight_accounts.carrierid = freight_carriers.id);";
    		}
            else if($type == 'carrier'){
                $select = "SELECT freight_carriers.id id, name FROM freight_carriers, companies where companies.id = companyid AND freight_carriers.id LIKE $value;";
            }
            else if($type == 'services'){
                $select = "SELECT * FROM freight_services WHERE `id` LIKE $id AND `carrierid` LIKE $value;";
            }
            else if($type == 'account'){
                $select = "SELECT * FROM freight_accounts WHERE `id` LIKE $id;";
            }
            $results = qdb($select);
            
            if (!$field){
                return $results;
            }
            else{
                $result = mysqli_fetch_row($results);
                return $result[$field];
            }
            
	}
?>