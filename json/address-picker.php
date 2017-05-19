<?php

//=============================== Address Picker ===============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	
	$q = '';
	$output = array();
    $q = grab('q');
    $companyid = grab('limit');
    $page = strtolower(grab('page'));
    $box = strtolower(grab('id'));
    $o = o_params($page);
    $id = $o[$box];
    $short = $o['short'];
    $order = $o['order'];
    
	if($companyid AND ! $q){
	    //If there is a value set for the company, load their defaults to the top result always.
	    $companyid = prep($companyid,"'25'");
	    
	    //
	    $d_bill = "Select count(`$id`) mode, max(`created`) recent, `$id`, a.`name`, 
			a.`street`, a.`city`, a.`state`,a.`postal_code`
    	    FROM $order $short, addresses a
    	    WHERE $short.`$id` = a.`id` AND `companyid` = $companyid
    	    GROUP BY `$id` 
			ORDER BY mode, recent, IF(created>=DATE_SUB(CURDATE(),INTERVAL 365 DAY),0,1)
    	    LIMIT 15;";
	    $default = qdb($d_bill);
	    foreach ($default as $row){
	        $line = array(
                'id' => $row[$id], 
                'text' => $row['street'].'<br>'.$row['city'].' '.$row['state'].' '.$row['postal_code'],/*.' <br> '.$row['name'],*/
                );
	        $output[] = $line;
	    }
	}
        
	if ($q) { 
        
        $query = "SELECT * FROM `addresses` WHERE name RLIKE '".$q."' OR street RLIKE '".$q."' OR city RLIKE '".$q."'; ";
        $results = qdb($query);
        foreach($results as $id => $row){
            $line = array(
                'id' => $row['id'], 
                'text' => $row['street'].'<br>'.$row['city'].' '.$row['state'].' '.$row['postal_code'],/*.' <br> '.$row['name'],*/
                );
            if (strpos(strtolower($line['text']),strtolower($q)) !== false){
                $output[] = $line;
            }
        }
        $output[] = array(
            'id' => "Add $q",
            'text' => "Add $q..."
            );
	}

    
	echo json_encode($output);//array('results'=>$companies,'more'=>false));
	exit;
?>
