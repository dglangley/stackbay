<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';

	$summary_date = format_date($today,'Y-m-01',array('m'=>-2));
	function format_market($partid_str,$market_table) {
		$last_date = '';
		$market_str = '';
		$dated_qty = 0;
		$monthly_totals = array();

		switch ($market_table) {
			case 'demand':
				$query = "SELECT datetime, request_qty qty, quote_price price, name FROM demand, search_meta, companies ";
				$query .= "WHERE (".$partid_str.") AND demand.metaid = search_meta.id AND companies.id = search_meta.companyid ";
				$query .= "AND datetime >= '".$GLOBALS['summary_date']."' ";
				$query .= "ORDER BY datetime ASC; ";

				$query2 = "SELECT datetime, SUM(request_qty) qty, AVG(request_qty*quote_price) price FROM demand, search_meta";
				$query2 .= "WHERE (".$partid_str.") AND demand.metaid = search_meta.id ";
				$query2 .= "AND datetime < '".$GLOBALS['summary_date']."' ";
				$query2 .= "GROUP BY LEFT(datetime,7) ORDER BY datetime DESC; ";
				break;

			case 'purchases':
				$query = "SELECT datetime, name, purchase_orders.id, qty, price FROM purchase_items, purchase_orders, companies ";
				$query .= "WHERE (".$partid_str.") AND purchase_items.purchase_orderid = purchase_orders.id AND companies.id = purchase_orders.companyid ";
				$query .= "AND datetime >= '".$GLOBALS['summary_date']."' ";
				$query .= "ORDER BY datetime ASC; ";

				$query2 = "SELECT datetime, SUM(qty) qty, AVG(qty*price) price FROM purchase_items, purchase_orders";
				$query2 .= "WHERE (".$partid_str.") AND purchase_items.purchase_orderid = purchase_orders.id ";
				$query2 .= "AND datetime < '".$GLOBALS['summary_date']."' ";
				$query2 .= "GROUP BY LEFT(datetime,7) ORDER BY datetime DESC; ";
				break;

			case 'sales':
			default:
				$query = "SELECT datetime, name, sales_orders.id, qty, price FROM sales_items, sales_orders, companies ";
				$query .= "WHERE (".$partid_str.") AND sales_items.sales_orderid = sales_orders.id AND companies.id = sales_orders.companyid ";
				$query .= "AND datetime >= '".$GLOBALS['summary_date']."' ";
				$query .= "ORDER BY datetime ASC; ";

				$query2 = "SELECT datetime, SUM(qty) qty, AVG(qty*price) price FROM sales_items, sales_orders";
				$query2 .= "WHERE (".$partid_str.") AND sales_items.sales_orderid = sales_orders.id ";
				$query2 .= "AND datetime < '".$GLOBALS['summary_date']."' ";
				$query2 .= "GROUP BY LEFT(datetime,7) ORDER BY datetime DESC; ";
				break;
		}

		$result = qdb($query);
		$num_detailed = mysqli_num_rows($result);
		while ($r = mysqli_fetch_assoc($result)) {
			$order_date = substr($r['datetime'],0,10);
			if ($last_date AND $order_date<>$last_date) {
				$market_str = format_dateTitle($order_date,$dated_qty).$market_str;
				$dated_qty = 0;//reset for next date
			}
			$last_date = $order_date;
			$dated_qty += $r['qty'];

			// itemized data
			$market_str .= '<div class="market-data"><span class="pa">'.$r['qty'].'</span> &nbsp; '.
				'<a href="#">'.$r['name'].'</a> <span class="pa">'.format_price($r['price'],false).'</span></div>';
		}
		// append last remaining data
		if ($num_detailed>0) {
			$market_str = format_dateTitle($order_date,$dated_qty).$market_str;
		}

		$result = qdb($query2);
		$num_summaries = mysqli_num_rows($result);
		if ($num_detailed>0 AND $num_summaries>0) { $market_str .= '<hr>'; }
		while ($r = mysqli_fetch_assoc($result)) {
			$market_str .= '<div class="market-data"><span class="pa">'.$r['qty'].'x</span> &nbsp; '.format_date($r['datetime'],"M 'y").' '.$r['price'].'</div>';
		}

		if ($market_str) {
			$market_str = '<a href="#" class="market-title">'.ucfirst($market_table).'</a>'.$market_str;
		} else {
			$market_str = '- No '.ucfirst($market_table).' -';
		}

		return ($market_str);
	}
	function format_dateTitle($order_date,$dated_qty) {
		$dtitle = '<div class="date-group"><a href="#" class="modal-results">'.format_date($order_date,'M j').': '.
			$dated_qty.' <i class="fa fa-list-alt"></i></a></div>';
		return ($dtitle);
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
<body class="sub-nav">

	<?php include 'modal/results.php'; ?>
	<?php include 'modal/notes.php'; ?>
	<?php include 'inc/navbar.php'; ?>
	<?php include 'inc/keywords.php'; ?>
	<?php include 'inc/dictionary.php'; ?>
	<?php include 'inc/logSearch.php'; ?>
	<?php include 'inc/format_price.php'; ?>

<?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['invfile']) && $_FILES['invfile']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['invfile']['tmp_name'])) {
        try {
			$cid = 0;
			if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid'])) { $cid = $_REQUEST['companyid']; }

            // key the filename on aws using today's date, companyid and the filename
            $filename = 'inv'.date("Ymd").'_'.$cid.'_'.$_FILES['invfile']['name'];

            // check for file existing already
/*
            $s3->registerStreamWrapper();
            $keyExists = file_exists("s3://".$bucket."/".$filename);

            if ($keyExists) {//file has already been uploaded
                $ALERT = array('code'=>14,'message'=>$E[14]['message']);
            } else {
                $upload = $s3->upload($bucket, $filename, fopen($_FILES['invfile']['tmp_name'], 'rb'), 'public-read');

                $replace_inventory = 'T';
                if (isset($_REQUEST['replace_inventory']) AND $_REQUEST['replace_inventory']<>1) { $replace_inventory = 'F'; }
                $query = "INSERT INTO uploads (filename, userid, companyid, datetime, replace_inventory, processed, link) ";
                $query .= "VALUES ('".res($_FILES['invfile']['name'])."','".res($U['id'])."','".res($cid)."',";
                $query .= "'".res($GLOBALS['now'])."','".res($replace_inventory)."',";
                $query .= "NULL,'".htmlspecialchars($upload->get('ObjectURL'))."'); ";
*/

                $query = "INSERT INTO uploads (filename, userid, companyid, datetime, replace_inventory, processed, link) ";
                $query .= "VALUES ('".res($_FILES['invfile']['name'])."','0','".res($cid)."',";
                $query .= "'".res($GLOBALS['now'])."','N',";
                $query .= "NULL,'".$filename."'); ";
echo '<BR><BR><BR><BR><BR><BR>'.$query.'<BR>';
//                $result = qdb($query);

/*
                $ALERT = array('code'=>0,'message'=>'Success! Processing can take up to 20 mins...');
            }
*/
        } catch(Exception $e) {
//            $ALERT = array('code'=>18,'message'=>$E[18]['message']);
die('died');
        }
    }
?>

	<form class="form-inline results-form" method="post" action="save-results.php" enctype="multipart/form-data" >

<?php
	if (! $s) {
?>
    <div id="pad-wrapper">

    <table class="table">
		<tr>
			<td class="col-md-12 text-center">
				Enter your search above, or tap <i class="fa fa-list-ol"></i> for more options...
			</td>
		</tr>
	</table>
<?php
	} else {
		$searchlistid = logSearch($s,$search_field,$search_from_right,$qty_field,$qty_from_right,$price_field,$price_from_right);
?>
	<input type="hidden" name="searchlistid" value="<?php echo $searchlistid; ?>">
    <table class="table table-header">
		<tr>
			<td class="col-md-2">
			</td>
			<td class="text-center col-md-5">
			</td>
			<td class="col-md-4">
				<div class="pull-right form-group">
					<input class="btn btn-success btn-sm" type="submit" name="save-demand" value="Customer Request">
					<select name="companyid" id="companyid" style="width:280px">
						<option value="">- Select a Company -</option>
					</select>
					<input class="btn btn-warning btn-sm" type="submit" name="save-availability" value="Supplier Offer">
				</div>
			</td>
		</tr>
	</table>

    <div id="pad-wrapper">

        <!-- the script for the toggle all checkboxes from header is located in js/theme.js -->
        <div class="table-products">
            <div class="row">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th class="col-md-5">
								<span class="qty-header">Qty</span>
                                Product Description
								<span class="price-header">Sell</span>
                            </th>
                            <th class="col-md-6 text-center">
                                <span class="line"></span>Market
                            </th>
                            <th class="col-md-1">
                                <span class="line"></span>
								<!-- Buy -->
								<span class="pull-right">Response</span>
                            </th>
                        </tr>
                    </thead>
<?php
//	if (! $s) { $s = 'UN375F'.chr(10).'090-42140-13'.chr(10).'IXCON'; }

	$lines = explode(chr(10),$s);
	foreach ($lines as $n => $line) {
		$terms = preg_split('/[[:space:]]+/',$line);
		$search_str = trim($terms[$search_index]);
		$search_qty = 1;//default
		if (isset($terms[$qty_index]) AND is_numeric($terms[$qty_index]) AND $terms[$qty_index]>0) {
			$search_qty = trim($terms[$qty_index]);
		}

		$results = hecidb($search_str);
		$num_results = count($results);
		$s = '';
		if ($num_results<>1) { $s = 's'; }
?>
                    <tbody>
                        <!-- row -->
                        <tr class="first">
                            <td>
								<div class="product-action">
	                                <div><input type="checkbox" class="checkAll" checked></div>
					          		<i class="fa fa-star-o fa-lg"></i> 
					           		<i class="fa fa-pencil fa-lg"></i>
								</div>
								<div class="qty">
									<input type="text" name="search_qtys[<?php echo $n; ?>]" value="<?php echo $search_qty; ?>" class="form-control input-xs search-qty input-primary" /><br/>
									<span class="info">search qty</span>
								</div>
								<div class="product-descr">
									<input type="text" name="searches[<?php echo $n; ?>]" value="<?php echo $search_str; ?>" class="product-search text-primary" /><br/>
									<span class="info"><?php echo $num_results.' result'.$s; ?></span>
								</div>
							</td>
                            <td>
								<div class="row">
									<div class="col-sm-3 text-center"><?php echo rand(0,200); ?> day(s)<br/><span class="info">shelflife</span></div>
									<div class="col-sm-3 text-center"><?php echo rand(1,9); ?>:1<br/><span class="info">quotes-to-sale</span></div>
									<div class="col-sm-3 text-center">$ 2,087.41<br/><span class="info">avg cost</span></div>
									<div class="col-sm-3 text-center"><?php echo '$'.rand(200,400).'-$'.rand(550,1300); ?><br/><span class="info">market pricing</span></div>
								</div>
							</td>
                            <td class="text-right">
								<div class="price">
									<div class="form-group">
										<div class="input-group buy">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input name="buyprice[<?php echo $n; ?>][]" type="text" value="350.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
										<span class="info">target price</span>
									</div>
								</div>
							</td>
						</tr>
<?php
		// gather all partid's first
		$partid_str = "";
		foreach ($results as $partid => $P) {
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
		}

		$k = 0;
		foreach ($results as $partid => $P) {
			$itemqty = 0;
			$itemprice = "0.00";

//			print "<pre>".print_r($P,true)."</pre>";
//                                        <img src="/products/images/echo format_part($P['part']).jpg" alt="pic" class="img" />
			// check favorites
			$fav_flag = 'star-o';
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."'; ";
			$result = qdb($query);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['userid']==$userid) { $fav_flag = 'star text-danger'; }
				else if ($fav_flag<>'star text-danger') { $fav_flag = 'star-half-o text-danger'; }
			}

			$chkd = '';
			if ($k==0 OR $itemqty>0) { $chkd = ' checked'; }
?>
                        <!-- row -->
                        <tr class="product-results">
                            <td class="descr-row">
								<div class="product-action text-center">
                                	<div><input type="checkbox" class="item-check" name="items[<?php echo $n; ?>][]" value="<?php echo $partid; ?>"<?php echo $chkd; ?>></div>
                                    <i class="fa fa-<?php echo $fav_flag; ?> fa-lg"></i>
								</div>
								<div class="qty">
									<div class="form-group">
										<input name="sellqty[<?php echo $n; ?>][]" type="text" value="<?php echo $itemqty; ?>" size="2" placeholder="Qty" class="input-xs form-control" />
									</div>
								</div>
                                <div class="product-img">
                                    <img src="/products/images/090-42140-13.jpg" alt="pic" class="img" />
                                </div>
                                <div class="product-descr">
									<?php echo $P['Part']; ?> &nbsp; <?php echo $P['HECI']; ?><br/>
                                   	<div class="description"><?php echo dictionary($P['manf'].' '.$P['system'].' '.$P['description']); ?></div>
								</div>
								<div class="price">
									<div class="form-group">
										<div class="input-group sell">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input type="text" name="sellprice[<?php echo $n; ?>][]" value="<?php echo $itemprice; ?>" size="6" placeholder="Sell" class="input-xs form-control price-control" />
										</div>
									</div>
								</div>
                            </td>
<?php
			// if on the first result, build out the market column that runs down all rows of results
			if ($k==0) {
				$sales_col = format_market($partid_str,'sales');
				$demand_col = format_market($partid_str,'demand');
				$purchases_col = format_market($partid_str,'purchases');
?>
							<!-- market-row for all items within search result section -->
                            <td rowspan="<?php echo ($num_results+1); ?>" class="market-row">
								<table class="table market-table">
									<tr>
										<td class="col-sm-3 bg-sales">
											<?php echo $sales_col; ?>
										</td>
										<td class="col-sm-3 bg-demand">
											<?php echo $demand_col; ?>
										</td>
										<td class="col-sm-3 bg-purchases">
											<?php echo $purchases_col; ?>
										</td>
										<td class="col-sm-3 bg-availability">
											<a href="#" class="market-title">Availability</a>
											<div id="market-results"></div>
										</td>
									</tr>
								</table>
                            </td>
<?php
			}

			$k++;
?>
                            <td class="product-actions">
								<div class="price">
									<div class="form-group">
<!--
										<div class="input-group buy">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input name="buyprice[<?php echo $n; ?>][]" type="text" value="350.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
-->
									</div>
								</div>
                            </td>
                        </tr>
<?php
		}
?>
                        <!-- row -->
                        <tr>
                            <td> </td>
                            <td> </td>
                        </tr>
                    </tbody>
<?php
	}
?>
                </table>
            </div>
            <ul class="pagination">
                <li><a href="#">&laquo;</a></li>
                <li class="active"><a href="#">1</a></li>
                <li><a href="#">2</a></li>
                <li><a href="#">3</a></li>
                <li><a href="#">4</a></li>
                <li><a href="#">&raquo;</a></li>
            </ul>
        </div>
<?php
	}//end if ($s)
?>

    </div>

	</form>

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
