<?php
//Main
    //Standard includes section
	$rootdir = $_SERVER['ROOT_DIR'];
	header('Content-Type: application/json');

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
		$content = implode(",",$content_id);
		$query = "SELECT part, serial_no FROM inventory AS i, parts AS p WHERE i.id IN ($content) AND i.partid = p.id;";
        $result = qdb($query);
        
        if (mysqli_num_rows($result) > 0) {
		    foreach($result as $row) {
                $contents[$row['part']][] = $row['serial_no'];
    		}
		}

        return $contents;
    
    }

    $order_number = grab('order_number');
    $package_number = grab('package_number');
    
    $packageid = package_id($order_number, $package_number);
    $package_contents = package_contents($packageid);
    
    echo json_encode($package_contents);
?>