<?php
	include_once '../inc/dbconnect.php';

	// Service Type : FFR, repair, etc
    $q = '';
    if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }

	$type = strtolower($_REQUEST['type']);
    
    //array('id'=>$id,'text'=>$text));
    $output = array();
    
    $query = "SELECT u.id as userid, c.name 
    			FROM service_classes sc, user_classes uc, users u, contacts c 
    			WHERE LOWER(sc.class_name) = '".res($type)."' AND uc.classid = sc.id AND u.id = uc.userid AND c.id = u.contactid GROUP BY u.id;";

    if($type == 'service') {
        if (strlen($q)>1) {
            $query = "SELECT u.id as userid, c.name 
                FROM service_classes sc, user_classes uc, users u, contacts c 
                WHERE LOWER(sc.class_name) <> 'repair' AND uc.classid = sc.id AND u.id = uc.userid AND c.id = u.contactid AND c.name RLIKE '".res($q)."' GROUP BY u.id;";
        } else {
            $query = "SELECT u.id as userid, c.name 
                FROM service_classes sc, user_classes uc, users u, contacts c 
                WHERE LOWER(sc.class_name) <> '".res('repair')."' AND uc.classid = sc.id AND u.id = uc.userid AND c.id = u.contactid GROUP BY u.id;";
        }
    }

    $result = qdb($query) OR die(qe() . ' ' . $query);
    
    while($r = mysqli_fetch_assoc($result)) {
        $output[] = array(
            'id' => $r['userid'], 
            'text' => $r['name']
        );
    }
    
	echo json_encode($output);
	exit;
