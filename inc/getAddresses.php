<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	
	function getAddresses($addressid,$field = '') {
        
        $result = array();
        
        $result = qdb("Select * From addresses where id = '$addressid'");
        foreach ($result as $row){
            if($field){
                return $row[$field];
            }
            else{
    		    return($row);
            }
        }
	}
	function address_out($address_id){
		//General function for handling the standard display of addresses
		$address = '';
		//Address Handling
		$row = getAddresses($address_id);
		$name = $row['name'];
		$street = $row['street'];
		$addr2 = $row['addr2'];
		$addr3 = $row['addr3'];
		$city = $row['city'];
		$state = $row['state'];
		$zip = $row['postal_code'];
		$country = $row['country'];
		
		//Address Output
		if($name){$address .= $name."<br>";}
		if($street){$address .= $street."<br>";}
		if($addr2){$address .= $addr2."<br>";}
		if($addr3){$address .= $addr3."<br>";}
		if($city && $state){$address .= $city.", ".$state;}
		else if ($city || $state){ ($address .= $city.$state);}
		if($zip){$address .= "  $zip";}
		
		return $address;
	}

?>
