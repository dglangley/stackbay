<?php
	function getPendingPackages($order_number, $order_type) {
		$packages = array();

		// For ISO we only want the pending/unshipped packages to be checked, so only if datetime is null do we pass back the package
		$query = "SELECT * FROM packages p WHERE order_type = ".fres($order_type)." AND order_number = ".res($order_number)." AND datetime IS NULL;";
		$result = qedb($query);
		while($r = mysqli_fetch_assoc($result)) {
			$packages[$r['id']] = $r;
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

	function getPackageContents($packageid,$idByPart=false) {
		$contents = array();
		$serials = array();

		$query = "SELECT serialid FROM package_contents pc WHERE pc.packageid = ".res($packageid).";";
		$result = qedb($query);

		while($r = mysqli_fetch_assoc($result)) {
			$serials[] = $r['serialid'];
		}

		if (count($serials)==0) { return ($contents); }

   		$ids = implode(",",$serials);
   		$query = "SELECT part, heci, p.id as partid, serial_no, i.id, i.qty, i.sales_item_id FROM inventory AS i, parts AS p WHERE i.id IN ($ids) AND i.partid = p.id;";
		$result = qedb($query);
		while ($r = qrow($result)) {
			$id = $r['id'];
			if ($idByPart) { $id = $r['part'].'.'.$r['sales_item_id']; }

			$contents[$id]['serial'][] = $r['serial_no'];
			$contents[$id]['partid'] = $r['partid'];
			$contents[$id]['part'] = $r['part'];
			$contents[$id]['heci'] = $r['heci'];
			$contents[$id]['qty'] = $r['qty'];
			$contents[$id]['inventoryid'] = $r['serialid'];
			$contents[$id]['id'] = $r['id'];
			$contents[$id]['sales_item_id'] = $r['sales_item_id'];
		}

		return $contents;
	}
