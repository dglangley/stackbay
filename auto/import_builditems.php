<?php

	$rootdir = $_SERVER["ROOT_DIR"];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/import_aid.php';
    include_once $rootdir.'/inc/order_parameters.php';
    include_once $rootdir.'/inc/getCompany.php';
    include_once $rootdir."/inc/setCostsLog.php";

//Declare used variables
$inventoryid;
$projectid;

$select ="
SELECT si.serial, p.id project_id FROM inventory_solditem si, inventory_project p WHERE si.project_id = p.id
UNION
SELECT il.serial, p.id project_id FROM inventory_itemlocation il, inventory_project p WHERE il.project_id = p.id;
";

$results = qdb($select,"PIPE") or die(qe("PIPE"));
echo($select."<br><BR>");

//These are all the results where there have been components ordered for a particular repair; All of these have related purchase orders and sales_orders.
foreach($results as $r){
	//reset inventory id upon each query
	$inventoryid = 0;
	$projectid = $r['project_id'];

	//Map serial to ours and get the inventory id
	$query = "SELECT id FROM inventory WHERE serial_no = ".prep($r['serial']).";";
	$result = qdb($query) or die(qe());

	if (mysqli_num_rows($result)) {
		$row = mysqli_fetch_assoc($result);
		$inventoryid = $row['id'];
	}

	print_r($r);
	echo '<br>';

	if($inventoryid) {
		echo "Inventory ID: " . $inventoryid;
		$query = "INSERT INTO build_items (buildid, inventoryid) VALUES (".prep($projectid).",".prep($inventoryid).");";
		//qdb($query) or die(qe());
		echo '<br>' . $query . '<br>';
	} else {
		echo "Inventory ID Not Found";
	}

	echo '<br><br>';
}