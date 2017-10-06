<?php

//=============================== Address Picker ===============================
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/order_parameters.php';
	include_once $rootdir.'/inc/default_addresses.php';
	
	$q = '';
	$output = array();
    $q = grab('q');
    $companyid = grab('limit');
    $page = strtolower(grab('page'));
    $box = strtolower(grab('id'));
    $o = o_params($page);

    //$id = $o[$box];
    $short = $o['short'];
    $order = $o['order'];

	if($companyid){
	    //If there is a value set for the company, load their defaults to the top result always.
	    $default = default_addresses($companyid, $o['type']);
	    $not_in = array();
	    foreach ($default[$box] as $id => $row){
	        $line = array(
                'id' => $id, 
                'text' => $row['street'].'<br>'.$row['city'].' '.$row['state'].' '.$row['postal_code'],/*.' <br> '.$row['name'],*/
                );
            $not_in[] = $id;
	        $output[] = $line;
	    }
	} 
        
	if (strlen($q) > 1) { 
	    $pq = prep($q);
        $not_in = implode(",",$not_in);
        $query = "SELECT * FROM `addresses` WHERE (name RLIKE $pq OR street RLIKE $pq OR city RLIKE $pq) ".($not_in ? " AND id NOT in ($not_in)":"").";";
        $results = qdb($query) or die(qe().$query);
        // exit($query);
        if(mysqli_num_rows($results)){
            foreach($results as $id => $row){
                $line = array(
                    'id' => $row['id'], 
                    'text' => $row['street'].'<br>'.$row['city'].' '.$row['state'].' '.$row['postal_code'],/*.' <br> '.$row['name'],*/
                    );
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
