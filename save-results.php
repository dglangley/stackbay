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

//			if ($submit_type=='availability' && isset($bid_qty[$ln])) { $response_qty = $bid_qty[$ln]; }
//			if ($submit_type=='availability' && isset($bid_price[$ln])) { $response_price = $bid_price[$ln]; }


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
		//header("Refresh:0");
		header('Location: /');
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

	<div class="table-header" id="filter_bar" style="width: 100%; min-height: 48px;">
		<div class="row" style="padding: 8px;" id="filterBar">
			<div class="col-md-4 mobile-hide" style="max-height: 30px;">
				<form action="quote_pdf.php" method="post" target="_blank">
					<input type="hidden" name="metaid" value="<?=$metaid;?>">
					<input type="hidden" name="START_DATE" value="<?=$_REQUEST['START_DATE'];?>">
					<input type="hidden" name="END_DATE" value="<?=$_REQUEST['END_DATE'];?>">
					<input type="hidden" name="companyid" value="<?=$_REQUEST['companyid'];?>">
					<input type="hidden" name="searchlistid" value="<?=$_REQUEST['searchlistid'];?>">
					<input type="hidden" name="submit_type" value="<?=$_REQUEST['submit_type'];?>">

					<?php foreach($_REQUEST['manfid'] as $manf) {
						echo '<input type="hidden" name="manfid[]" value="' . $manf . '">';
					} ?>
					<?php foreach($_REQUEST['systemid'] as $systemid) {
						echo '<input type="hidden" name="systemid[]" value="' . $systemid . '">';
					} ?>
					<?php foreach($_REQUEST['searches'] as $searches) {
						echo '<input type="hidden" name="searches[]" value="' . $searches . '">';
					} ?>
					<?php foreach($_REQUEST['search_qtys'] as $search_qtys) {
						echo '<input type="hidden" name="search_qtys[]" value="' . $search_qtys . '">';
					} ?>
					<?php foreach($_REQUEST['descr'] as $descr) {
						echo '<input type="hidden" name="descr[]" value="' . $descr . '">';
					} ?>
					<?php foreach($_REQUEST['bid_qty'] as $bid_qty) {
						echo '<input type="hidden" name="bid_qty[]" value="' . $bid_qty . '">';
					} ?>
					<?php foreach($_REQUEST['bid_price'] as $bid_price) {
						echo '<input type="hidden" name="bid_price[]" value="' . $bid_price . '">';
					} ?>
					<?php $counter = 0; foreach($_REQUEST['items'] as $items) {
						$counter2 = 0;
						foreach($items as $item){
							echo '<input type="hidden" name="items['.$counter.']['.$counter2.']" value="' . $item . '">';
							$counter2 ++;
						}
						$counter ++;
					} ?>
					<?php $counter = 0; foreach($_REQUEST['sellqty'] as $sellqtys) {
						$counter2 = 0;
						foreach($sellqtys as $sellqty){
							echo '<input type="hidden" name="sellqty['.$counter.']['.$counter2.']" value="' . $sellqty . '">';
							$counter2 ++;
						}
						$counter ++;
					} ?>
					<?php $counter = 0; foreach($_REQUEST['sellprice'] as $sellprices) {
						$counter2 = 0;
						foreach($sellprices as $sellprice){
							echo '<input type="hidden" name="sellprice['.$counter.']['.$counter2.']" value="' . $sellprice . '">';
							$counter2 ++;
						}
						$counter ++;
					} ?>

					<button type="submit" class="btn-flat pull-left"><i class="fa fa-file-pdf-o"></i></button>
				</form>
			</div>

			<div class="text-center col-md-4">
				<h2 class="minimal">Sales Quote <?=$metaid;?></h2>
			</div>

			<div class="col-md-4">
				
			</div>
		</div>
	</div>

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
