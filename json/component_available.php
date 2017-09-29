<?php

	//Prepare the page as a JSON type
	header('Content-Type: application/json');

	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getCondition.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/operations_sidebar.php';
	include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/getOrderStatus.php';
	include_once $rootdir.'/inc/split_inventory.php';
	include_once $rootdir.'/inc/setInventory.php';

	$result;

	$partid = $_REQUEST['partid'];
	$request = $_REQUEST['request'];
	$received = $_REQUEST['received'];

	$type = $_REQUEST['type'];

	//For Inventory pull
	$components = $_REQUEST['pulled_items'];
	$order_number = $_REQUEST['order_number'];
	$repair_item_id = $_REQUEST['repair_item_id'];
	$pulled_stamp = $now;

	function format($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);
	    $display = "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary(substr($r['description'],0,30)).'</span></div>';

	    return $display;
	}

	function grabInventoryStock($partid, $request, $received){
		$html = "";
		$inventory = array();

		$query = "SELECT *, SUM(qty) as total FROM inventory WHERE partid = ".prep($partid)." AND (status = 'received') GROUP BY locationid;";
		$result = qdb($query) or die(qe());

		$html .= '<tr>
						<td>'.format($partid).'</td>
						<td>'.($request).'</td>
						
					</tr>';

		$html .= '<tr>
					<td colspan="2">
						<table class="table table-hover table-striped table-condensed">
							<thead><th>Location</th><th>Condition</th><th>Stock</th><th>Pull</th></thead>';
		while ($row = $result->fetch_assoc()) {
			//$inventory[] = $row;
			$location = display_location($row["locationid"]);
			if($row["total"]) {
				$html .= "<tr class='part' data-invid='".$row["id"]."' data-partid='".$partid."'>
							<td class='col-md-6'>".$location. " " .($row["bin"] ? "(Bin# " . $row["bin"] . ')' : '')."</td>
							<td class='col-md-3'>".getCondition($row["conditionid"])."</td>
							<td class='col-md-1'>".$row["total"]."</td>
							<td class='col-md-2'><input type='text' class='input-sm form-control inventory_pull' value=''></td>
						</tr>";
			}
		}
		$html .= '</table></td></tr>';

		return $html;
	}

	function repairComponent($order_number, $components, $repair_item_id){
		$result = true;
		foreach($components as $item) {
			if($item['qty']) {
//				$newID = split_components($item['invid'], $item['qty']);
				$newID = split_inventory($item['invid'], $item['qty']);

				$I = array('id'=>$item['invid'],'status'=>'installed');
				setInventory($I);

				$query = "INSERT INTO repair_components (invid, ro_number, datetime, qty) ";
				$query .= "VALUES ('".res($item['invid'])."', '".res($order_number)."', '".$GLOBALS['now']."', '".res($item['qty'])."');";
				$result = qdb($query) or die(qe());
			}
		}

		return $result;
	}

	if(!$components) {
		//This is for stock grabbing
		$result = grabInventoryStock($partid, $request, $received);
	} else {
		//used for component pull to repair
		$result = repairComponent($order_number, $components, $repair_item_id);
	}
		
	echo json_encode($result);
    exit;
