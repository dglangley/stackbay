<?php
	include '../inc/dbconnect.php';
	include '../inc/getCondition.php';

	$partid = '';
	
	if (isset($_REQUEST['partid'])) { $partid = trim($_REQUEST['partid']); }
    
    $items = array();
    
    function getStock($conditionid, $partid) {
        global $items;
		$stock;
		
		$partid = res($partid);
		$conditionid = res($conditionid);
		
		$query = "SELECT SUM(qty) as total FROM inventory WHERE partid = $partid AND conditionid = '$conditionid';";
        $result = qdb($query);
        if (mysqli_num_rows($result)>0) { 
            $row = mysqli_fetch_assoc($result);
            $items[] = getCondition($conditionid) . ' ' . ($row['total'] != '' ? " &nbsp; " . $row['total'] . ")" : ' &nbsp; (0)');
        }
	}
	
	getCondition();//init for all conditions
	foreach ($CONDITIONS as $conditionid => $cond) {
	    getStock($conditionid, $partid);
	}
    
	echo json_encode($items);//array('results'=>$companies,'more'=>false));
	exit;
?>
