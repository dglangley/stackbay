<?php
    include_once '../inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getMaterials.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

    // Getters
    include_once $_SERVER["ROOT_DIR"].'/inc/getLocation.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCondition.php';

    $partid = '';
    if (isset($_REQUEST['partid'])) { $partid = trim($_REQUEST['partid']); }
    $taskid = '';
    if (isset($_REQUEST['taskid'])) { $taskid = trim($_REQUEST['taskid']); }
    $type = '';
    if (isset($_REQUEST['type'])) { $type = trim($_REQUEST['type']); }

    $T = order_type($type);

    $output = array();
    
    $data = getAvailable($partid, $taskid, $T);
    
    foreach($data as $r) {
        $output[] = array(
            'id' => 'partids['.$partid.']'.($r['locationid'] ? '['.$r['locationid'].']' : '').($r['conditionid'] ? '['.$r['conditionid'].']' : '').($r['serial'] ? '['.$r['serial'].']' : ''), 
            'text' => ($r['locationid'] ? getLocation($r['locationid']).' ' : 'N/A ').($r['conditionid'] ? getCondition($r['conditionid']).' ' : 'N/A ').($r['serial'] ? $r['serial'] : ''),
            'available' => $r['available'],
        );
    }
    
	echo json_encode($output);
	exit;
