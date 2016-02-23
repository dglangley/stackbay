<?php
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/dbconnect.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/format_price.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getPart.php';
	include_once $_SERVER["DOCUMENT_ROOT"].'/inc/getCompany.php';

//	print "<pre>".print_r($_REQUEST,true)."</pre>";
	$submit_type = 'demand';
	if (isset($_REQUEST['save-availability'])) { $submit_type = 'availability'; }

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $companyid = trim($_REQUEST['companyid']); }
	$searchlistid = 0;
	if (isset($_REQUEST['searchlistid']) AND is_numeric($_REQUEST['searchlistid'])) { $searchlistid = trim($_REQUEST['searchlistid']); }

	$items = array();
	if (isset($_REQUEST['items']) AND is_array($_REQUEST['items'])) { $items = $_REQUEST['items']; }

	$userid = 1;

	$display_str = '';
	$display_html = '';

	// save data to db if companyid is passed in
	if ($companyid) {
		$metaid = 0;
		// have we already posted this page? replace instead of create
		$query = "SELECT id FROM search_meta WHERE companyid = '".$companyid."' ";
		$query .= "AND datetime LIKE '".$today."%' AND searchid = '".$searchlistid."'; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)==1) {
			$r = mysqli_fetch_assoc($result);
			$metaid = $r['id'];
		}

		// save meta data
		$query = "REPLACE search_meta (companyid, datetime, source, searchid";
		if ($metaid) { $query .= ", id"; }
		$query .= ") VALUES ('".$companyid."','".$now."','".$userid."','".$searchlistid."'";
		if ($metaid) { $query .= ",'".$metaid."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (! $metaid) { $metaid = qid(); }
	}
	// process items for output to screen, and save to db if $companyid
	foreach ($items as $ln => $row) {
		if (! is_numeric($ln)) { $ln = 0; }//default in case of corrupt data

		$searchid = 0;
		$search_str = '';
		if (isset($_REQUEST['searches'][$ln])) { $search_str = strtoupper(trim($_REQUEST['searches'][$ln])); }

		if ($search_str AND $companyid) {
			$query = "SELECT id FROM searches WHERE search = '".$search_str."' AND userid = '".$userid."' ";
			$query .= "AND datetime LIKE '".$today."%'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$searchid = $r['id'];
			}
		}

//		print "<pre>".print_r($row,true)."</pre>";
		$list_qty = 1;
		if (isset($_REQUEST['search_qtys'][$ln]) AND is_numeric($_REQUEST['search_qtys'][$ln]) AND $_REQUEST['search_qtys'][$ln]>0) { $list_qty = trim($_REQUEST['search_qtys'][$ln]); }
		$list_price = false;
		if (isset($_REQUEST['search_prices'][$ln])) { $list_price = trim($_REQUEST['search_prices'][$ln]); }

		$sellqty[$ln] = array();
		if (isset($_REQUEST['sellqty'][$ln]) AND is_array($_REQUEST['sellqty'][$ln])) { $sellqty[$ln] = $_REQUEST['sellqty'][$ln]; }
		$sellprice[$ln] = array();
		if (isset($_REQUEST['sellprice'][$ln]) AND is_array($_REQUEST['sellprice'][$ln])) { $sellprice[$ln] = $_REQUEST['sellprice'][$ln]; }

		$quote_str = '';
		$quote_html = '';
		foreach ($row as $n => $partid) {
			//defaults
			$response_qty = 0;
			if (isset($sellqty[$ln][$n]) AND is_numeric($sellqty[$ln][$n]) AND $sellqty[$ln][$n]>0) { $response_qty = $sellqty[$ln][$n]; }
			$response_price = false;
			if (isset($sellprice[$ln][$n])) { $response_price = $sellprice[$ln][$n]; }

			// get id if already saved
			if ($companyid) {
				$itemid = 0;
				$query = "SELECT id FROM ".$submit_type." WHERE partid = '".$partid."' AND metaid = '".$metaid."' AND line_number = '".$line_number."'; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)==1) {
					$r = mysqli_fetch_assoc($result);
					$itemid = $r['id'];
				}

				if ($submit_type=='demand') {
					$q1 = 'request_qty';
					$p1 = 'request_price';
					$q2 = 'quote_qty';
					$p2 = 'quote_price';
				} else if ($submit_type=='availability') {
					$q1 = 'avail_qty';
					$p1 = 'avail_price';
					$q2 = 'offer_qty';
					$p2 = 'offer_price';
				}

				// if user is recording demand, or if it's the first item of an availability, or if the user actually has a qty entered
				if ($submit_type=='demand' OR ($n==0 OR $response_qty>0)) {
					$query = "REPLACE ".$submit_type." (partid, ".$q1.", ".$p1.", ".$q2.", ".$p2.", metaid, searchid, line_number";
					if ($itemid) { $query .= ", id"; }
					$query .= ") VALUES ('".$partid."','".$list_qty."',";
					if ($list_price) { $query .= "'".$list_price."',"; } else { $query .= "NULL,"; }
					if ($response_qty) { $query .= "'".$response_qty."',"; } else { $query .= "NULL,"; }
					if ($response_qty>0 AND $response_price) { $query .= "'".$response_price."',"; } else { $query .= "NULL,"; }
					$query .= "'".$metaid."',";
					if ($searchid) { $query .= "'".$searchid."',"; } else { $query .= "NULL,"; }
					$query .= "'".($ln+1)."'";//always save it incremented by one since it's initialized in array starting at 0
					if ($itemid) { $query .= ",'".$itemid."'"; }
					$query .= "); ";
					$result = qdb($query) OR die(qe().' '.$query);
				}
			}

			if ($response_qty>0) {
				$quote_str .= ' qty '.$response_qty.'- '.getPart($partid).' '.format_price($response_price).chr(10);
				$quote_html .= '<tr><td class="text-left">'.($ln+1).'</td><td class="text-left">'.getPart($partid,'part').'</td>'.
					'<td class="text-left">'.getPart($partid,'heci').'</td><td class="text-left">'.getPart($partid,'full_descr').'</td>'.
					'<td>'.$response_qty.'</td><td class="text-right">'.format_price($response_price).'</td>'.
					'<td class="text-right">'.format_price($response_qty*$response_price).'</td></tr>';
			}
		}
		if ($quote_str) {
			$display_str .= $search_str.chr(10).$quote_str;
			$display_html .= $quote_html;
		}
	}

	if ($display_str) {
		if ($submit_type=='demand') { $display_str = 'We have the following available:'.chr(10).chr(10).$display_str; }
		else if ($submit_type=='availability') { $display_str = 'I\'m interested in the following:'.chr(10).chr(10).$display_str; }

		$display_html = '<table class="table table-condensed"><tr><th>Line#</th><th>Part#</th><th>HECI/CLEI</th><th>Description</th>'.
			'<th>Qty</th><th>Unit Price</th><th>Ext Price</th></tr>'.$display_html.'</table>';
	} else {
		header('Location: /');
		exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>VenTel Market Manager</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body>

	<?php include 'inc/navbar.php'; ?>

    <div id="pad-wrapper">

		<div class="row">
			<div class="col-md-4">
<?php if ($companyid) { ?>
				<form class="inline-form order-form">
				<h5>Create Order</h5>
				<div class="field-box">
					<div class="ui-select">
						<select>
							<option value="">- Select Contact -</option>
						</select>
					</div>
					<div class="ui-select">
						<select>
							<option value="">- Select Terms -</option>
							<option value="">Net 15</option>
							<option value="">Net 30</option>
							<option value="">COD</option>
						</select>
					</div>
				</div>
				<div class="field-box">
					<div class="ui-select">
						<select>
							<option value="">UPS</option>
						</select>
					</div>
					<div class="ui-select">
						<select>
							<option value="">Ground</option>
						</select>
					</div>
				</div>
				<div class="field-box">
					<div class="ui-select addresses">
						<select>
							<option value="">Default Shipping Address</option>
						</select>
					</div>
				</div>
				<div class="field-box">
					<textarea class="form-control" placeholder="Private Notes" rows="4"></textarea>
				</div>
				<div class="field-box">
					<textarea class="form-control" placeholder="Public Notes" rows="4"></textarea>
				</div>
				</form>
                <a href="javascript:void(0);" class="btn btn-default btn-sm" id="dd1" data-date-format="mm-dd-yyyy" data-date="<?php echo $today; ?>"><span id="dd1label"><?php echo $today; ?></span></a>
                <input type="hidden" name="delivdate1" id="delivdate1" value="<?php echo $today; ?>">
				Send to Test
<?php } ?>
			</div>
			<div class="col-md-4 text-center">
				<?php if ($companyid) { echo '<h2>'.getCompany($companyid).'</h2>'; } ?>

				<textarea class="freeform-text"><?php echo $display_str; ?></textarea>
			</div>
			<div class="col-md-4"> </div>
		</div>

		<br/>

		<div class="row">
			<div class="col-md-2"> </div>
			<div class="col-md-8 text-center">
				<?php echo $display_html; ?>
			</div>
			<div class="col-md-2"> </div>
		</div>
	</div>

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
