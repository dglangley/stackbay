<?php
	$rootdir = $_SERVER['ROOT_DIR'];
    include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getRep.php';
	//include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/setCost.php';
    include_once $rootdir.'/inc/setCostsLog.php';
    include_once $rootdir.'/inc/setCommission.php';
    include_once $rootdir.'/inc/setInvoiceCOGS.php';
    include_once $rootdir.'/inc/getCOGS.php';
    include_once $rootdir.'/inc/getCostsLog.php';

	function box_drop($order_number, $associated = '', $first = '',$selected = '', $serial = ''){
		$select = "SELECT * FROM `packages`  WHERE  `order_number` = '$order_number'";
		$results = qedb($select);
		
		$drop = '';
		foreach ($results as $item) {
			//print_r($item);
			$it[$item['id']] = $item['datetime'];	
			$drop .= "<option data-boxno='".$item['package_no']."' value='".$item['id']."'";
			if ($selected == $item['id']){
				$drop .= ' selected';
			}
			$drop .= ($item['datetime'] != '' ? ' disabled': '');
			$drop .= ">Box ".$item['package_no']."</option>";
		}
		$drop .= "</select>";
		$drop .= "</div>";
		if ($first){
				$f = "<div>
				<select class='form-control input-sm active_box_selector' data-associated = '$associated' data-serial = '$serial'>";
			}
			else{
				$f = "<div>
					<select class='form-control box_drop input-sm ".($it[$selected] != '' ? '': 'box_selector')."' data-associated = '$associated' data-serial = '$serial' ".($it[$selected] != '' ? ' disabled ': '').">";
			}
			$f .= $drop;
		return $f;
	}
    
    function package_edit($action,$packageid=0,$order_number ='',$order_type ='',$name =''){
        if ($action == 'addition'){
            $q_number = prep($order_number);
			$q_type = prep($order_type,'Sale');

            $name = prep($name);
            if($order_number) {
                $insert = "INSERT INTO `packages`(`order_number`,`order_type`,`package_no`) VALUES ($q_number,$q_type, $name);";
                qedb($insert);

                return qid();
            }
            return false;   
        }
        elseif($action == "update"){
            $pid = prep($packageid);
			$freight = grab("freight");
			if (! $order_type) {
				$query = "SELECT order_type FROM packages WHERE id = $pid; ";
				$result = qedb($query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$order_type = $r['order_type'];
				}
			}

            $update = "UPDATE packages SET ";
            $update .= updateNull("width",grab("width"));
            $update .= updateNull("height",grab("height"));
            $update .= updateNull("length",grab("length"));
            $update .= updateNull("weight",grab("weight"));
            $update .= updateNull("tracking_no",strtoupper(grab("tracking")));
            $update .= rtrim(updateNull("freight_amount",$freight),',');
            $update .= " WHERE id = $pid;";
			qedb($update);

			// get all serialid's (inventoryid's) in this package, and let setCost() do its thing,
			// which finds any difference in existing costs, and re-updates its inventory costs records
			// and re-averages costs for this partid
			if ($order_type=='Purchase') {
				$query = "SELECT serialid FROM package_contents WHERE packageid = $pid; ";
				$result = qedb($query);
				$num_rows = mysqli_num_rows($result);
				// note that freight per unit is per RECORD in inventory; the qty per inventory record is irrelevant at this
				// point because inventory costs get associated per RECORD anyway, not per piece
				$freight_per = 0;
				if ($num_rows>0) { $freight_per = $freight/$num_rows; }//calc for the sake of updating sold stock cogs below

				while ($r = mysqli_fetch_assoc($result)) {
					// check inventory status on this unit so we can determine if the cost gets allocated
					// to inventory current AVERAGE cost, or COGS on sale of unit
					$query2 = "SELECT status FROM inventory WHERE id = '".$r['serialid']."'; ";
					$result2 = qedb($query2);
					if (mysqli_num_rows($result2)==0) { continue; }//really should not be a real scenario, but hey, account for it
					$r2 = mysqli_fetch_assoc($result2);

					// has this freight already been applied, or possibly in part? if so, we're only taking the difference
					$existing_freight = getCostsLog($r['serialid'],$packageid,'packageid');
					$unit_freight_per = $freight_per-$existing_freight;
//					$freight_per -= $existing_freight;
					if ($unit_freight_per==0) { continue; }

					// sets cost, costs log, and average cost
					setCost($r['serialid']);

					// neither of these statuses have COGS applied to them yet, so best practice is to update the average cost
					if ($r2['status']=='received' OR $r2['status']=='manifest') {
						/***** IN STOCK AVERAGE COST *****/

					} else {
						/***** SOLD STOCK COGS *****/

						// get PO associated with package so we can get purchase_item_id, and in turn the datetime from inventory_history
						// so that finally we can get the next ensuing sale AFTER the point of purchase, and update the related COGS
						$base_date = '0000-00-00 00:00:00';
						$query3 = "SELECT date_changed FROM inventory_history h, purchase_items pi, packages p ";
						$query3 .= "WHERE p.id = $pid AND p.order_number = pi.po_number AND pi.id = h.value AND h.field_changed = 'purchase_item_id' ";
						$query3 .= "AND h.invid = '".$r['serialid']."' ";
						$query3 .= "GROUP BY h.invid ORDER BY date_changed DESC LIMIT 0,1; ";
						$result3 = qedb($query3);
						if (mysqli_num_rows($result3)>0) {
							$r3 = mysqli_fetch_assoc($result3);
							$base_date = $r3['date_changed'];
						}

						// get the next sale that was impacted by the freight charges being added
						$query3 = "SELECT h.value, h.field_changed FROM inventory_history h, sales_cogs sc ";
						$query3 .= "WHERE h.invid = sc.inventoryid AND sc.inventoryid = '".$r['serialid']."' ";
						$query3 .= "AND (field_changed = 'sales_item_id') ";
						$query3 .= "AND date_changed >= '".$base_date."' ";
						$query3 .= "ORDER BY h.field_changed ASC LIMIT 0,1; ";
						$result3 = qedb($query3);
						if (mysqli_num_rows($result3)>0) {
							$r3 = mysqli_fetch_assoc($result3);

							$existing_cogs = getCOGS($r['serialid'],$r3['value'],$r3['field_changed']);
							$new_cogs = $existing_cogs+$unit_freight_per;

							$cogsid = setCogs($r['serialid'], $r3['value'], $r3['field_changed'], $new_cogs);

							// if already sold and shipped, then we will have an associated invoice, which we need for updating commissions
							$query4 = "SELECT invoice_no, invoice_item_id FROM commissions WHERE inventoryid = '".$r['serialid']."' ";
							$query4 .= "AND item_id = '".$r3['value']."' AND item_id_label = '".$r3['field_changed']."' AND cogsid = '".$cogsid."' ";
							$query4 .= "GROUP BY invoice_no, invoice_item_id; ";
							$result4 = qedb($query4);
							while ($r4 = mysqli_fetch_assoc($result4)) {
								// this function should be called every time COGS is updated after invoicing!
								setCommission($r4['invoice_no'],$r4['invoice_item_id'],$r['serialid']);
							}
						}

						// get all related invoices and update COGS to Journal Entries
						$query2 = "SELECT i.invoice_no, i.order_type ";
						$query2 .= "FROM invoices i, invoice_items ii, invoice_shipments s, package_contents pc ";
						$query2 .= "WHERE pc.serialid = '".$r['serialid']."' AND i.invoice_no = ii.invoice_no ";
						$query2 .= "AND s.invoice_item_id = ii.id AND pc.packageid = s.packageid ";
						$query2 .= "GROUP BY i.invoice_no; ";
						$result2 = qedb($query2);
						while ($r2 = mysqli_fetch_assoc($result2)) {
							setInvoiceCOGS($r2['invoice_no'],$r2['order_type']);
						}
					}
/*
1) if item is in stock, adds cost and adjusts average cost
2) if item is in a manifest state, adds cost and adjusts average cost
	1 in stock, 1 in manifest
*/


				}
			}

            return $update;
        }
        elseif($action == "change"){
            $assoc = grab('assoc');
            $new = prep(grab('package'));
            $update = "Not Updated";
            if($assoc && $new){
                $update = "UPDATE package_contents SET packageid = $new WHERE serialid = $assoc";
                qedb($update);
            }
            return $update;
            
        }
        elseif($action == "delete"){
            $assoc = grab('assoc');
            $new = prep(grab('package'));
            $update = "Not Deleted";
            if($assoc && $new){
                $update = "DELETE FROM package_contents WHERE packageid = $new AND serialid = $assoc";
                qedb($update);
            }
            return $update;
            
        }
        else{
            return "Nothing.";
        }
    }
	
	//Get the freight total for a shipment returned as a float
	function shipment_freight($order_number,$order_type,$datetime = ''){
	    $return = '';
	    $select = "SELECT SUM(freight_amount) total FROM `packages` 
	    WHERE `order_number` = $order_number AND `order_type` = '$order_type'
	    ".($datetime? "AND `datetime` = ".prep($datetime) : "")."
	    ;";
	    $result = qedb($select);
	    if (mysqli_num_rows($result)){
	        $result = mysqli_fetch_assoc($result);
	        $return = $result['total'];
	    } else {
	        $return = 0.00;
	    }
	    return $return;
	}
	
	//Take as an input the box number and the order number and pass back the identifier
    function package_id($order_number, $package_number) {
        $package_items;
		$order_number = prep($order_number);
		
        $query = "SELECT * FROM packages WHERE package_no = '". res($package_number) ."' AND order_number = $order_number;";
        $result = qedb($query);
        
        if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$package_items = $result['id'];
		}
		
        return $package_items;
    
    }
    

    function getPackageContents($packageid) {
		$contents = array();
		$serials = array();

		$query = "SELECT serialid FROM package_contents pc WHERE pc.packageid = ".res($packageid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$serials[] = $r['serialid'];
		}

		if($serials){
    		$content = implode(",",$serials);
    		$query = "SELECT part, serial_no, i.id, i.qty FROM inventory AS i, parts AS p WHERE i.id IN ($content) AND i.partid = p.id;";
            $result = qedb($query);

            while($r = mysqli_fetch_assoc($result)) {
				$contents[$r['id']]['serial'] = $r['serial_no'];
                $contents[$r['id']]['part'] = $r['part'];
                $contents[$r['id']]['qty'] = $r['qty'];
			}
   
        }

		return $contents;
	}
    
    //Function which returns the list of Master tracking boxes based off the order number
    function master_packages($order_number, $order_type){
        $result = array();
        $order_number = prep($order_number);
        $query_result = qedb("SELECT min(`package_no`) masters FROM `packages` where order_number = $order_number AND order_type = '$order_type' group by `datetime`");
        if (mysqli_num_rows($query_result) > 0){
            foreach($query_result as $row){
                $result[] = $row['masters'];
            }
        } else {
            $result[] = 1;
        }

        return $result;
    }
    
	//Grab all packages by order number    
	// The flag if true filters out the shipped packages and only produces packages that have NOT been shipped out
	// AKA no datetime stamped on the package 
	function getPackages($order_number, $order_type, $flag = false) {
		$packages = array();

		$query = "SELECT * FROM packages p WHERE order_type = ".fres($order_type)." AND order_number = ".res($order_number);
		if($flag) {
			$query .= " AND datetime IS NULL";
		}
		$query .= ";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$packages[] = $r;
		}

		return $packages;
	}
	
	//When one has a package ID, output the relevant package information
	function getPackageInfo($package_id, $info = 'name'){
	    if($info == "name"){
	        $select = "Select package_no as name FROM packages WHERE id = ".prep($package_id).";";
	    }
	    $result = qedb($select);
	    $result = mysqli_fetch_assoc($result);
	    return $result[$info];
	}

    function deletePackage($package_id) {
        $delete = "DELETE FROM packages WHERE id = ".prep($package_id).";";
        qedb($delete);
    }
?>
