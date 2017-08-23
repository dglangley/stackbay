<?php

	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	
	function getAddresses($addressid,$field = '') {
        
        $result = array();
        
        $result = qdb("Select * From addresses where id = '$addressid';") or die(qe());
        foreach ($result as $row){
            if($field){
                return $row[$field];
            }
            else{
    		    return($row);
            }
        }
	}
	function address_out($address_id,$include_name=true,$sep='<BR>'){
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
		if($name AND $include_name){$address .= $name.$sep;}
		if($street){$address .= $street.$sep;}
		if($addr2){$address .= $addr2.$sep;}
		if($addr3){$address .= $addr3.$sep;}
		if($city && $state){$address .= $city.", ".$state;}
		else if ($city || $state){ ($address .= $city.$state);}
		if($zip){$address .= "  $zip";}
		
		return $address;
	}

?>
