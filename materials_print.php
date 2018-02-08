<?php
	include_once $_SERVER["ROOT_DIR"] . '/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/order_type.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/getInventoryCost.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/getQty.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/format_part.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'] . '/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getItemOrder.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/getServiceClass.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"] . '/inc/dictionary.php';

	$taskid = (isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : '');
	$order_type = (isset($_REQUEST['order_type']) ? ucwords($_REQUEST['order_type']) : '');

	$T = order_type($order_type);

	$materials = getMaterials($taskid, $T['item_label'], $order_type);

	$ORDER = getOrder(getItemOrder($taskid, $T['item_label']), $order_type);
	
	if($ORDER['classid']) {
		$service_class = getServiceClass($ORDER['classid']);
	}

	function partDescription($partid, $desc = true){
		$r = reset(hecidb($partid, 'id'));
		$parts = explode(' ',$r['part']);

	    $display = "<span class = 'descr-label'>".$parts[0]." &nbsp; ".$r['heci']."</span>";
	    if($desc)
    		$display .= '<div class="description desc_second_line descr-label" style = "color:#aaa;">'.dictionary($r['manf']).' &nbsp; '.dictionary($r['system']).
				'</span> <span class="description-label">'.dictionary($r['description']).'</span></div>';

	    return $display;
	}

	function getMaterials($item_id, $item_id_label, $order_type = 'Repair') {
		$materials = array();
		
		if ($order_type == 'Repair' OR $order_type == 'Service') {
			// first build list of all partids for this task, primarily through `service_bom` (bill of materials)
			// but also check purchase_requests and repair_components/service_materials for any gaps of data
			$query = "SELECT *, charge quote FROM service_bom WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($item_id_label)."' ";
			$query .= "GROUP BY partid; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$r['purchase_request_id'] = false;
				$r['materials_id'] = false;
				$r['items'] = array();

				$materials[$r['partid']] = $r;
				$mat_profit += $r['quote'];
			}

			$query = "SELECT partid, qty, item_id, item_id_label, id purchase_request_id FROM purchase_requests ";
			$query .= "WHERE item_id = ".fres($item_id)." AND item_id_label = ".fres($item_id_label)." ";
			$query .= "GROUP BY partid; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				$r['materials_id'] = false;

				if (isset($materials[$r['partid']])) {
					$materials[$r['partid']]['purchase_request_id'] = $r['purchase_request_id'];
				} else {
					$r['amount'] = false;
					$r['profit_pct'] = false;
					$r['charge'] = false;
					$r['type'] = false;
					$r['id'] = false;
					$r['items'] = array();

					$materials[$r['partid']] = $r;
				}
			}

			if ($order_type=='Repair') {
				$query = "SELECT i.partid, c.qty, c.item_id, c.item_id_label, c.id materials_id ";
				$query .= "FROM repair_components c, inventory i ";
				$query .= "WHERE c.item_id = '".res($item_id)."' AND c.invid = i.id ";
			} else if ($order_type=='Service') {
				$query = "SELECT i.partid, m.qty, m.service_item_id item_id, 'service_item_id' item_id_label, m.id materials_id ";
				$query .= "FROM service_materials m, inventory i ";
				$query .= "WHERE m.service_item_id = '".res($item_id)."' AND m.inventoryid = i.id ";
			}
			$query .= "GROUP BY partid; ";
			$result = qedb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				if (isset($materials[$r['partid']])) {
					$materials[$r['partid']]['materials_id'] = $r['materials_id'];
				} else {
					$r['amount'] = false;
					$r['profit_pct'] = false;
					$r['charge'] = false;
					$r['type'] = false;
					$r['id'] = false;
					$r['purchase_request_id'] = false;
					$r['items'] = array();

					$materials[$r['partid']] = $r;
				}
			}

			foreach ($materials as $partid => $P) {
//				echo $partid.'<BR>';
				$ids = array();
				$query = "SELECT partid, po_number, status, requested datetime, SUM(qty) as totalOrdered FROM purchase_requests ";
				$query .= "WHERE item_id = ".fres($item_id)." AND item_id_label = ".fres($item_id_label)." AND partid = '".$partid."' ";
				$query .= "GROUP BY po_number, status ORDER BY requested DESC; ";
				$result = qedb($query);

				while ($r = mysqli_fetch_assoc($result)) {
					$r['available'] = 0;
					$r['pulled'] = 0;

					if ($r['po_number']) {
						$query2 = "SELECT * FROM purchase_items pi WHERE po_number = '".$r['po_number']."' AND partid = '".$r['partid']."' ";
						$query2 .= "AND ((pi.ref_1 = '".res($item_id)."' AND pi.ref_1_label = '".res($item_id_label)."') ";
						$query2 .= "OR (pi.ref_2 = '".res($item_id)."' AND pi.ref_2_label = '".res($item_id_label)."')); ";
						$result2 = qedb($query2);
						while ($r2 = mysqli_fetch_assoc($result2)) {

							$query3 = "SELECT * FROM inventory WHERE purchase_item_id = '".$r2['id']."' AND partid = '".$r['partid']."'; ";
							$result3 = qedb($query3);
							if (mysqli_num_rows($result3)>0) {
								while ($r3 = mysqli_fetch_assoc($result3)) {
									$ids[$r3['id']] = true;
									if ($r3['status']=='received') {
										$r['available'] += $r3['qty'];
									} else if ($r3['status']=='installed') {
										$r['pulled'] += $r3['qty'];
									}
									$cost = getInventoryCost($r3['id']);
									$mat_total_cost += $cost;
									$r['cost'] += $cost;
								}
							}
						}
					}
					$materials[$partid]['items'][] = $r;
				}

				// Check to see what has been received and sum it into the total Ordered
				$id_csv = '';
				foreach ($ids as $invid => $bool) {
					if ($id_csv) { $id_csv .= ','; }
					$id_csv .= $invid;
				}
				if ($order_type=='Repair') {
					$query = "SELECT *, c.qty pulled, i.id inventoryid, c.datetime, i.qty as totalReceived FROM repair_components c, inventory i ";
					$query .= "WHERE c.item_id = '".res($item_id)."' AND c.invid = i.id ";
				} else if ($order_type=='Service') {
					$query = "SELECT *, m.qty pulled, i.id inventoryid, m.datetime, i.qty as totalReceived FROM service_materials m, inventory i ";
					if ($po_number) { $query .= "LEFT JOIN purchase_items pi ON pi.id = i.purchase_item_id "; }
					$query .= "WHERE m.service_item_id = '".res($item_id)."' AND m.inventoryid = i.id ";
				}
				if ($id_csv) { $query .= "AND i.id NOT IN (".$id_csv.") "; }
				$query .= "AND i.partid = '".$partid."' ";
				$query .= "; ";
				$result2 = qdb($query) OR die(qe().' '.$query);
				while ($row2 = mysqli_fetch_assoc($result2)) {
					$row = array('partid'=>$row2['partid'],'datetime'=>$row2['datetime']);

					$inventoryid = $row2['inventoryid'];

					$row['po_number'] = '';
					$row['totalOrdered'] = 0;
					// go back and try to re-populate PO info using purchase requests; the above query didn't cover this
					// because here we're starting with repair_components / service_materials, which the above query assumes
					// that purchase requests is the starting point
					if ($row2['purchase_item_id']) {
						$query3 = "SELECT SUM(r.qty) qty, r.po_number FROM purchase_requests r, purchase_items i ";
						$query3 .= "WHERE item_id = '".res($item_id)."' AND item_id_label = '".res($item_id_label)."' ";
						$query3 .= "AND r.partid = '".$row2['partid']."' AND r.po_number = i.po_number AND i.id = '".$row2['purchase_item_id']."'; ";
						$result3 = qdb($query3) OR die(qe().'<BR>'.$query3);
						if (mysqli_num_rows($result3)>0) {
							$r3 = mysqli_fetch_assoc($result3);
							if ($r3['qty']>0) {//SUM() in query above always produces a result, so check if qty>0
								$row['totalOrdered'] = $r3['qty'];
								$row['po_number'] = $r3['po_number'];
							}
						}
					}

					// Grab actual available quantity for the requested component
					if ($row['status']=='received') {
						$row['available'] = $row['totalReceived'];
					} else {
						$row['available'] = getQty($row2['partid']);
					}
//					$row['available'] = getAvailable($row['partid'], $item_id);
					$row['pulled'] = $row2['pulled'];//getPulled($row['partid'], $item_id);

					$cost = getInventoryCost($inventoryid);
					$mat_total_cost += $cost;
					$row['cost'] = $cost;

//					$row['total'] = $total;
					$row['status'] = '';

					$materials[$partid]['items'][] = $row;
				}
			}
//			print "<pre>".print_r($materials,true)."</pre>";
//			exit;

		} else if($order_type == 'service_quote') {
			$query = "SELECT *, '' status FROM service_quote_materials WHERE $item_id_label = ".res($item_id).";";
			$result = qdb($query) OR die(qe().' '.$query);

			//echo $query;

			while($row = mysqli_fetch_assoc($result)) {
				$materials[$row['partid']] = array(
					'partid' => $row['partid'],
					'qty' => $row['qty'],
					'item_id' => $row['quote_item_id'],
					'item_id_label' => 'quote_item_id',
					'amount' => $row['amount'],
					'profit_pct' => $row['profit_pct'],
					'charge' => $row['quote'],
					'type' => 'Material',
					'id' => $row['id'],
					'purchase_request_id' => false,
					'items' => array(),
				);
				$mat_total_charge += $row['quote'];
				$mat_total_cost += $row['qty']*$row['amount'];
			}
		} 

		return $materials;
	}

	function buildRows($materials) {
		$html_rows = '';

		foreach($materials as $material) {
			foreach($material['items'] as $P)
				if(! empty($P)) {

					//print_r($P);
					// $primary_part = getPart($P['partid'],'part');
					// $fpart = format_part($primary_part);

					$html_rows .= '<tr>';
					$html_rows .= '	<td class="col-md-5">'.trim(partDescription($P['partid'], true)).'</td>';
					$html_rows .= '	<td class="col-md-1">'.$P['totalOrdered'].'</td>';
					$html_rows .= '	<td class="col-md-2">'.format_date($P['datetime']).'</td>';
					$html_rows .= '	<td class="col-md-2">'.$P['po_number'].'</td>';
					$html_rows .= '	<td class="col-md-1">'.$P['available'].'</td>';
					$html_rows .= '	<td class="col-md-1">'.$P['pulled'].'</td>';
					$html_rows .= '</tr>';
				}
		}

		return $html_rows;
	}

	$item_details = $ORDER['items'][$taskid];

	// Popluate the order_number
	$full_order_number = ($item_details['so_number'] ?: $item_details['ro_number']) . ($item_details['line_number'] ? '-' . $item_details['line_number'] : '');

	// Determine here what kind of line item this is...
	if($item_details['os_number']) {
		// If it has this then it must be an Outsourced Order
		$full_order_number = 'Outside Order# ' . $item_details['os_number'] .($item_details['line_number'] ? '-' . $item_details['line_number'] : '');
	} else if($item_details['ref_2_label'] == 'service_item_id') {
		$co_name = $item_details['task_name'];
		$masterid = $item_details['ref_2'];

		// detect if it is an ICO or CCO
		// ICO should not show a cost towards the customer but a cost towards ourselves
		if($item_details['amount'] == 0) {
			$ICO = true;
		}

		// Get the master information here
		$master_title = $ORDER['items'][$masterid]['task_name'] . ' ' . $ORDER['items'][$masterid]['so_number'] . '-' . $ORDER['items'][$masterid]['line_number'];
	}

	$class = '';

	if ($item_details['task_name']) { $class = $item_details['task_name']; }
	else { $class = getClass($ORDER['classid']); }

	$pageTitle = '';

	if($new) {
		$pageTitle = 'New ';
	}

	if(strtolower($order_type) == "service" OR $quote) {
		if($quote) {
			if (! $new) {
				$pageTitle .= $service_class." Quote ". $full_order_number;
			} else {
				$pageTitle .= " Quote";
			}
		} else if($new) {
			$pageTitle .= "for Order# ".$order_number;
		} else {
			if($master_title) {
				$pageTitle = $master_title . ' CO' . $item_details['task_name'];
			} else if ($item_details['task_name']) {
				$pageTitle = $item_details['task_name'].' '. $full_order_number;
			} else {
				$pageTitle = getClass($ORDER['classid']).' '. $full_order_number;
			}
		}
	} else if(strtolower($order_type) == "repair" OR $class == "repair") {
		if($quote) {
			$pageTitle .= "Repair Quote for Order# ". $full_order_number;
		} else {
			$pageTitle = 'Repair '. $full_order_number;
		}
	}

	$TITLE = $pageTitle;
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo $TITLE . ' Materials List'; ?></title>
	<?php
		/*** includes all required css includes ***/
		include_once 'inc/scripts.php';
	?>

	<!-- any page-specific customizations -->
	<style type="text/css">
		.printonly {
			display: none;
		}

		@media print{
			.printonly {
				display: block;
			}

		    .noprint {
		    	display:none;
		    }

		    #pad-wrapper {
		    	margin-top: 0;
		    }
		}
	</style>
</head>
<body>

<?php include_once 'inc/navbar.php'; ?>

<!-- FILTER BAR -->
<div class="table-header noprint" id="filter_bar" style="width: 100%; min-height: 48px; max-height:60px;">
	<form class="form-inline" method="get" action="" enctype="multipart/form-data" id="filters-form" >

	<div class="row" style="padding:8px">
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-2 text-center">
			<h2 class="minimal"><?php echo $TITLE; ?></h2>
			<span class="info"></span>
		</div>
		<div class="col-sm-2">
		</div>
		<div class="col-sm-1">
		</div>
		<div class="col-sm-2">
		</div>
	</div>

	</form>
</div>

<div id="pad-wrapper">
<h3 class="text-center printonly" style="margin-bottom: 25px;"><?=$TITLE;?></h3>
<form class="form-inline" method="get" action="" enctype="multipart/form-data" >
	<div class="table-responsive">
		<table class="table table-condensed table-striped">

			<thead>
				<tr>
					<th class="col-md-5">Material</th>
					<th class="col-md-1"><span class="hidden-md hidden-lg">Reqd</span><span class="hidden-xs hidden-sm">Requested</span></th>
					<th class="col-md-2">Date</th>
					<th class="col-md-2">Source</th>
					<th class="col-md-1"><span class="hidden-md hidden-lg">Avail</span><span class="hidden-xs hidden-sm">Available</span></th>
					<th class="col-md-1">Pulled</th>
				</tr>
			</thead>

			<tbody>
				<?=buildRows($materials);?>
			</tbody>
		</table>
	</div>
</form>
</div><!-- pad-wrapper -->

<?php include_once $_SERVER["ROOT_DIR"].'/inc/footer.php'; ?>

<script type="text/javascript">
	$(document).ready(function() {
	});
</script>

</body>
</html>
