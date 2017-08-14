<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';

	//print "<pre>".print_r($_REQUEST,true)."</pre>"; 
	$submit_type = 'demand';

	if (isset($_REQUEST['submit_type']) AND ($_REQUEST['submit_type']=='availability' OR $_REQUEST['submit_type']=='demand')) { $submit_type = $_REQUEST['submit_type']; }
//	if (isset($_REQUEST['save-availability'])) { $submit_type = 'availability'; }

	$companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
	$searchlistid = 0;
	if (isset($_REQUEST['searchlistid']) AND is_numeric($_REQUEST['searchlistid'])) { $searchlistid = trim($_REQUEST['searchlistid']); }
	$contactid = 0;
	if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = trim($_REQUEST['contactid']); }

	$items = array();
	if (isset($_REQUEST['items']) AND is_array($_REQUEST['items'])) { $items = $_REQUEST['items']; }

	$userid = 1;
	if ($U['id']) { $userid = $U['id']; }

	$display_str = '';
	$display_html = '';

	$metaid = logSearchMeta($companyid,$searchlistid);

	// process items for output to screen, and save to db if $companyid
	foreach ($items as $ln => $row) {
		if (! is_numeric($ln)) { $ln = 0; }//default in case of corrupt data

		$searchid = 0;
		$search_str = '';
		if (isset($_REQUEST['searches'][$ln])) { $search_str = strtoupper(trim($_REQUEST['searches'][$ln])); }

		if ($search_str AND $companyid) {
			$query = "SELECT id FROM searches WHERE search = '".$search_str."' AND userid = '".$userid."' ";
			$query .= "AND datetime LIKE '".$today."%' ORDER BY id DESC; ";//get most recent
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$searchid = $r['id'];
			}
		}

//		print "<pre>".print_r($row,true)."</pre>";
		$list_qty = 1;
		if (isset($_REQUEST['search_qtys'][$ln]) AND is_numeric($_REQUEST['search_qtys'][$ln]) AND $_REQUEST['search_qtys'][$ln]>0) { $list_qty = trim($_REQUEST['search_qtys'][$ln]); }
		$list_price = false;
		if (isset($_REQUEST['list_price'][$ln])) { $list_price = trim($_REQUEST['list_price'][$ln]); }

		$sellqty[$ln] = array();
		if (isset($_REQUEST['sellqty'][$ln]) AND is_array($_REQUEST['sellqty'][$ln])) { $sellqty[$ln] = $_REQUEST['sellqty'][$ln]; }
		$sellprice[$ln] = array();
		if (isset($_REQUEST['sellprice'][$ln]) AND is_array($_REQUEST['sellprice'][$ln])) { $sellprice[$ln] = $_REQUEST['sellprice'][$ln]; }

		$bid_qty[$ln] = array();
		if (isset($_REQUEST['bid_qty']) AND is_array($_REQUEST['bid_qty'])) { $bid_qty = $_REQUEST['bid_qty']; }
		$bid_price[$ln] = array();
		if (isset($_REQUEST['bid_price']) AND is_array($_REQUEST['bid_price'])) { $bid_price = $_REQUEST['bid_price']; }

		$quote_str = '';
		$quote_html = '';
		foreach ($row as $n => $partid) {
			//defaults
			$response_qty = 0;
			if ($submit_type=='availability') { $response_qty = $list_qty; }
			else if (isset($sellqty[$ln][$n]) AND is_numeric($sellqty[$ln][$n]) AND $sellqty[$ln][$n]>0) { $response_qty = $sellqty[$ln][$n]; }
			$response_price = false;
			// if (isset($sellprice[$ln][$n])) { $response_price = $sellprice[$ln][$n]; }
			if (isset($sellprice[$ln][$n])) { $response_price = $sellprice[$ln][$n]; }

			if ($submit_type=='availability' && isset($bid_qty[$ln])) { $response_qty = $bid_qty[$ln]; }
			if ($submit_type=='availability' && isset($bid_price[$ln])) { $response_price = $bid_price[$ln]; }


			// get id if already saved
			if ($companyid) {
				// if user is recording demand, or if it's the first item of an availability, or if the user actually has a qty entered
				if ($submit_type=='demand' OR ($n==0 OR $response_qty>0)) {
					insertMarket($partid,$list_qty,$list_price,$response_qty,$response_price,$metaid,$submit_type,$searchid,$ln);
				}
			}

			if ($response_qty>0) {
				$quote_str .= ' qty '.$response_qty.'- '.getPart($partid).' '.format_price($response_price);
				if ($response_qty>1) { $quote_str .= ' ea'; }
				$quote_str .= chr(10);
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
		header("Refresh:0");
		exit;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Stackbay</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body>

	<?php include 'inc/navbar.php'; ?>

    <div id="pad-wrapper">

		<div class="row">
			<div class="col-md-4">
			</div>
			<div class="col-md-4 text-center">
				<?php if ($companyid) { echo '<h3><a href="/profile.php?companyid='.$companyid.'" title="">'.getCompany($companyid).'</a></h3>'; } ?>

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
