<?php
	function getISOPackages($order_number, $order_type) {
		$packages = array();

		$query = "SELECT * FROM packages p WHERE order_type = ".fres($order_type)." AND order_number = ".res($order_number).";";
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
    		$query = "SELECT part, heci, p.id as partid, serial_no, i.id, i.qty FROM inventory AS i, parts AS p WHERE i.id IN ($content) AND i.partid = p.id;";
            $result = qedb($query);
    		
            if (mysqli_num_rows($result) > 0) {
    		    foreach($result as $row) {
                    $contents[$row['part']]['serial'] = $row['serial_no'];
                    $contents[$row['part']]['partid'] = $row['partid'];
                    $contents[$row['part']]['heci'] = $row['heci'];
                    $contents[$row['part']]['qty'] = $row['qty'];
                    $contents[$row['part']]['inventoryid'] = $row['serialid'];
                    $contents[$row['part']]['id'] = $row['id'];
        		}
    		}
        }

		return $contents;
	}