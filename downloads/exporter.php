<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getQty.php';

	$filename = str_replace('/downloads/','',$_SERVER['REQUEST_URI']);
	if (! $filename OR $filename=='exporter.php') { $filename = 'inventory-export-'.$today.'.csv'; }

	$partids = array();
	if (isset($_REQUEST['partids'])) { $partids = $_REQUEST['partids']; }

	$searches = array();
	if (isset($_REQUEST['searches'])) { $searches = $_REQUEST['searches']; }

	$purchase_request = array();
	if (isset($_REQUEST['purchase_request'])) { $purchase_request = $_REQUEST['purchase_request']; }

	header("Content-type: text/csv; charset=utf-8");
	header("Cache-Control: no-store, no-cache");
	header('Content-Disposition: attachment; filename="'.$filename.'"');

	$outstream = fopen("php://output",'w');

	if(! empty($purchase_request) AND empty($partids)) {
		// Using the sourcing page to populate
		$header = array('Manufacturer','Part Number','Description','Quantity','UOM','Quantity Available','Price Per Unit', 'Lead Time (in # of days, 0 for stock)', 'Notes', 'Alternative Part Number');
		fputcsv($outstream, $header, ',', '"');

		// Get the data
		foreach($purchase_request as $id) {
			$query = "SELECT * FROM purchase_requests WHERE id=".res($id).";";
			$result = qedb($query);

			if(mysqli_num_rows($result)) {
				$r = mysqli_fetch_assoc($result);

				$partids[$r['partid']] += $r['qty'];
			}
		}

		foreach($partids as $partid => $qty) {
			$H = hecidb($partid,'id');
			$part_strs = explode(' ',$H[$partid]['part']);
			$aliases = '';
			foreach ($part_strs as $i => $str) {
				if ($i==0) { continue; }

				if ($aliases) { $aliases .= ' '; }
				$aliases .= $str;
			}

			$row = array(
				$H[$partid]['manf'],
				$part_strs[0],
				$H[$partid]['description'],
				$qty,
				'EA',
				'',
				'',
				'',
				'',
				$aliases,
			);
			fputcsv($outstream, $row, ',', '"');
		}
	} else {
		$header = array('Qty','Part','HECI','Aliases','Manf','System','Description');
		fputcsv($outstream, $header, ',', '"');

		if (! empty($searches) AND empty($partids)) {
			foreach ($searches as $search) {
				$H = hecidb($search);

				$qty = 0;
				$part = '';
				$heci = '';
				$aliases = '';
				$manf = '';
				$system = '';
				$descr = '';

				foreach ($H as $partid => $r) {
					$qty += getQty($partid);

					if (! $heci AND $r['heci']) { $heci = substr($r['heci'],0,7); }

					// once we've established the part and other data, all we need is summing qtys above
					if ($part) { continue; }

					$part_strs = explode(' ',$r['part']);
					$part = format_part($part_strs[0]);
					foreach ($part_strs as $i => $str) {
						if ($i==0) { continue; }

						if ($aliases) { $aliases .= ' '; }
						$aliases .= $str;
					}

					$manf = $r['manf'];
					$system = $r['system'];
					$descr = $r['description'];
				}

				// build csv
				$row = array(
					$qty,
					$part,
					$heci,
					$aliases,
					$manf,
					$system,
					$descr,
				);
				fputcsv($outstream, $row, ',', '"');
			}
		} else {
			foreach ($partids as $partid) {
				$H = hecidb($partid,'id');
				$part_strs = explode(' ',$H[$partid]['part']);
				$aliases = '';
				foreach ($part_strs as $i => $str) {
					if ($i==0) { continue; }

					if ($aliases) { $aliases .= ' '; }
					$aliases .= $str;
				}

				$row = array(
					getQty($partid),
					$part_strs[0],
					$H[$partid]['heci'],
					$aliases,
					$H[$partid]['manf'],
					$H[$partid]['system'],
					$H[$partid]['description'],
				);
				fputcsv($outstream, $row, ',', '"');
			}
		}
	}

	fclose($outstream);
	exit;
?>
