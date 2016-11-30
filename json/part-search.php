<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/keywords.php';

	$q = '';
	$r;
	if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }

	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));
    
    $items = array();
    if (strlen($q) > 1){
         $results = (hecidb($qlower));
         foreach($results as $id=> $row){
             $query = "SELECT SUM(qty) as total FROM inventory WHERE partid = $id;";
             $result = qdb($query);
             if (mysqli_num_rows($result)>0) { $r = mysqli_fetch_assoc($result);}
             $name = $row['part']." &nbsp; ".$row['heci'].' &nbsp; '.$row['Manf'].' '.$row['system'].' '.$row['Descr'] . '  &nbsp; ' . ($r['total'] > 0 ? '<span style="color: #4cae4c;">(IN STOCK)' : '<span style="color: #d43f3a;">(OUT OF STOCK)') . '</span>';
             $items[] = array('id' => $id, 'text' => $name, 'stock' => $r['total']);
         }
         
    }
    
    //$sorted = $items;
    
    // $sorted = usort($items, function ($item1, $item2) {
    //     if ($item1['stock'] == $item2['stock']) return 0;
    //     return $item1['stock'] < $item2['stock'] ? -1 : 1;
    // });
    
    
	echo json_encode($items);//array('results'=>$companies,'more'=>false));
	exit;
?>
