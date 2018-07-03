<?php
	function getISOPackages($order_number, $order_type) {
		$packages = array();

		// For ISO we only want the unshipped packages to be checked
		// So only if datetime is null do we pass back the package
		$query = "SELECT * FROM packages p WHERE order_type = ".fres($order_type)." AND order_number = ".res($order_number)." AND datetime IS NULL;";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$packages[] = $r;
		}

		return $packages;
	}

	// Get the Package Info based on the package id
	function getISOPackage($packageid) {
		$package = array();

		$query = "SELECT * FROM packages p WHERE id = ".res($packageid).";";
		$result = qedb($query);

		if(mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);
			$package[] = $r;
		}

		return $package;
	}

	function getISOPackageContents($packageid) {
		$contents = array();
		$serials = array();

		$query = "SELECT serialid FROM package_contents pc WHERE pc.packageid = ".res($packageid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$serials[] = $r['serialid'];
		}

		if($serials){
    		$content = implode(",",$serials);
    		$query = "SELECT part, heci, p.id as partid, serial_no, i.id, i.qty, i.sales_item_id FROM inventory AS i, parts AS p WHERE i.id IN ($content) AND i.partid = p.id;";
            $result = qedb($query);

    		
            if (mysqli_num_rows($result) > 0) {
    		    foreach($result as $row) {
                    $contents[$row['part'].'.'.$row['sales_item_id']]['serial'][] = $row['serial_no'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['partid'] = $row['partid'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['part'] = $row['part'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['heci'] = $row['heci'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['qty'] = $row['qty'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['inventoryid'] = $row['serialid'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['id'] = $row['id'];
                    $contents[$row['part'].'.'.$row['sales_item_id']]['sales_item_id'] = $row['sales_item_id'];
        		}
    		}
        }

		return $contents;
	}
