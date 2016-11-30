<?php
	include '../inc/dbconnect.php';

	$partid = '';
	
	if (isset($_REQUEST['partid'])) { $partid = trim($_REQUEST['partid']); }
    
    $items = array();
    
    function getStock($condition, $partid) {
        global $items;
		$stock;
		
		$partid = res($partid);
		$condition = res($condition);
		
		$query = "SELECT SUM(qty) as total FROM inventory WHERE partid = $partid AND item_condition = '$condition';";
        $result = qdb($query);
        if (mysqli_num_rows($result)>0) { 
            $row = mysqli_fetch_assoc($result);
            $items[] = ucwords($condition) . ' ' . ($row['total'] != '' ? " &nbsp; " . $row['total'] . ")" : ' &nbsp; (0)');
        }
	}
	
	function getEnumValue( $table = 'inventory', $field = 'item_condition' ) {
		$statusVals;
		
	    $query = "SHOW COLUMNS FROM {$table} WHERE Field = '" . res($field) ."';";
	    $result = qdb($query);
	    
	    if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$statusVals = $result;
		}
		
		preg_match("/^enum\(\'(.*)\'\)$/", $statusVals['Type'], $matches);
		
		$enum = explode("','", $matches[1]);
		
		return $enum;
	}
	
	$enums = getEnumValue();
	
	foreach($enums as $cond) {
	    getStock($cond, $partid);
	}
    
	echo json_encode($items);//array('results'=>$companies,'more'=>false));
	exit;
?>
