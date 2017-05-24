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
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/form_handle.php';
    
	function box_drop($order_number, $associated = '', $first = '',$selected = '', $serial = ''){
		$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
		$results = qdb($select);
		
		$drop = '';
		foreach ($results as $item) {
			//print_r($item);
			$it[$item['id']] = $item['datetime'];	
			$drop .= "<option data-boxno='".$item['package_no']."' value='".$item['id']."'";
			if ($selected == $item['id']){
				$drop .= ' selected';
			}
			$drop .= ($item['datetime'] != '' ? ' disabled': '');
			$drop .= ">Box ".$item['package_no']."</option>";
		}
		$drop .= "</select>";
		$drop .= "</div>";
		if ($first){
				$f = "<div>
				<select class='form-control input-sm active_box_selector' data-associated = '$associated' data-serial = '$serial'>";
			}
			else{
				$f = "<div>
					<select class='form-control box_drop input-sm ".($it[$selected] != '' ? '': 'box_selector')."' data-associated = '$associated' data-serial = '$serial' ".($it[$selected] != '' ? ' disabled ': '').">";
			}
			$f .= $drop;
		return $f;
	}
    
    function package_edit($action,$id='',$order ='',$type ='',$name =''){
    
    if ($action == 'addition'){
        $order_number = prep($order);
        $name = prep($name);
        $order_type = prep($type,'Sales');
        $insert = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`) VALUES ($order_number,$order_type, $name);";
        qdb($insert) OR die(qe().": $insert");

        return qid();
        
    }
    elseif($action == "update"){
        $row_id = prep($id);
        
        $update = "UPDATE packages SET ";
        $update .= updateNull("width",grab("width"));
        $update .= updateNull("height",grab("height"));
        $update .= updateNull("length",grab("length"));
        $update .= updateNull("weight",grab("weight"));
        $update .= updateNull("tracking_no",grab("tracking"));
        $update .= rtrim(updateNull("freight_amount",grab("freight")),',');
        $update .= " WHERE ";
        $update .= "id = $row_id;";
        
        qdb($update) or die(qe()." $update");
        return $update;
    }
    elseif($action == "change"){
        $assoc = grab('assoc');
        $new = prep(grab('package'));
        $update = "Not Updated";
        if($assoc && $new){
            $update = "UPDATE package_contents SET packageid = $new WHERE serialid = $assoc";
            qdb($update) or die(qe()." $update");
        }
        return $update;
        
    }
    elseif($action == "delete"){
        $assoc = grab('assoc');
        $new = prep(grab('package'));
        $update = "Not Deleted";
        if($assoc && $new){
            $update = "DELETE FROM package_contents WHERE packageid = $new AND serialid = $assoc";
            qdb($update) or die(qe()." $update");
        }
        return $update;
        
    }
    else{
        return "Nothing.";
    }
}
	
	//Get the freight total for a shipment returned as a float
	function shipment_freight($order_number,$order_type,$datetime = ''){
	    $select = "SELECT SUM(freight_amount) total FROM `packages` 
	    WHERE `order_number` = $order_number AND `order_type` = '$order_type'
	    ".($datetime? "AND `datetime` = ".prep($datetime) : "")."
	    ;";
	    $result = qdb($select) or die(qe()." | $select");
	    if (mysqli_num_rows($result)){
	        $result = mysqli_fetch_assoc($result);
	        $return = $result['total'];
	    } else {
	        $return = 0.00;
	    }
	    return $return;
	}
	
	//Take as an input the box number and the order number and pass back the identifier
    function package_id($order_number, $package_number) {
        $package_items;
		$order_number = prep($order_number);
		
        $query = "SELECT * FROM packages WHERE package_no = '". res($package_number) ."' AND order_number = $order_number;";
        $result = qdb($query);
        
        if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$package_items = $result['id'];
		}
		
        return $package_items;
    
    }
    
    //gets a complete list of the serial numbers which are a part of the package (based of package id)
    function package_contents($id) {
        $content_id = array();
        $contents = array();
        $part;
        $parts = array();
		
        $query = "SELECT DISTINCT serialid FROM package_contents WHERE packageid = '". res($id) ."';";
        $result = qdb($query);
        
        foreach($result as $row){
			$content_id[] = $row['serialid'];
		}
		if($content_id){
    		$content = implode(",",$content_id);
    		$query = "SELECT part, serial_no FROM inventory AS i, parts AS p WHERE i.id IN ($content) AND i.partid = p.id;";
            $result = qdb($query);
    		
            if (mysqli_num_rows($result) > 0) {
    		    foreach($result as $row) {
                    $contents[$row['part']][] = $row['serial_no'];
        		}
    		}
        }
        else{
            $contents = false;
        }
        return $contents;
    
    }
    
    //Function which returns the list of Master tracking boxes based off the order number
    function master_packages($order_number, $order_type){
        $result = array();
        $order_number = prep($order_number);
        $query_result = qdb("SELECT min(`package_no`) masters FROM `packages` where order_number = $order_number AND order_type = '$order_type' group by `datetime`") or die("masters fail");
        if (mysqli_num_rows($query_result) > 0){
            foreach($query_result as $row){
                $result[] = $row['masters'];
            }
        } else {
            $result[] = 1;
        }
        return $result;
    }
    
    //Grab all packages by order number    
	function getPackages($order_number){
		$order_number = prep($order_number);
		$query = "Select * From packages WHERE order_number = $order_number;";
		$result = qdb($query);
		return $result;
	}
	
	//When one has a package ID, output the relevant package information
	function getPackageInfo($package_id, $info = 'name'){
	    if($info == "name"){
	        $select = "Select package_no as name FROM packages WHERE id = ".prep($package_id).";";
	    }
	    $result = qdb($select) or die(qe()." | ".$select);
	    $result = mysqli_fetch_assoc($result);
	    return $result[$info];
	}
?>