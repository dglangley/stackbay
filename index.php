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

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$lastWeek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$lastYear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));
	function format_dateTitle($order_date,$dated_qty) {
		global $today,$yesterday;

		if ($order_date==$today) { $date = 'Today'; }
		else if ($order_date==$yesterday) { $date = 'Yesterday'; }
		else if ($order_date>$lastWeek) { $date = format_date($order_date,'D'); }
		else if ($order_date>=$lastYear) { $date = format_date($order_date,'M j'); }
		else { $date = format_date($order_date,'M j, y'); }

		$dtitle = '<div class="date-group"><a href="#" class="modal-results">'.$date.': '.
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

	<?php include_once 'modal/results.php'; ?>
	<?php include_once 'modal/notes.php'; ?>
	<?php include_once 'inc/navbar.php'; ?>
	<?php include_once 'inc/keywords.php'; ?>
	<?php include_once 'inc/dictionary.php'; ?>
	<?php include_once 'inc/logSearch.php'; ?>
	<?php include_once 'inc/format_price.php'; ?>
	<?php include_once 'inc/format_price.php'; ?>
	<?php require('vendor/autoload.php'); ?>

<?php
    // this will simply read AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY from env vars
	if (! $DEV_ENV) {
		$s3 = Aws\S3\S3Client::factory();
		$bucket = getenv('S3_BUCKET')?: die('No "S3_BUCKET" config var in found in env!');
	}

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['invfile']) && $_FILES['invfile']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['invfile']['tmp_name'])) {
        try {
			$cid = 0;
			if (isset($_REQUEST['inv-companyid']) AND is_numeric($_REQUEST['inv-companyid'])) { $cid = $_REQUEST['inv-companyid']; }

            // key the filename on aws using today's date, companyid and the filename
            $filename = 'inv'.date("Ymd").'_'.$cid.'_'.$_FILES['invfile']['name'];

            // check for file existing already
			$keyExists = false;
			if (! $DEV_ENV) {
	            $s3->registerStreamWrapper();
				$keyExists = file_exists("s3://".$bucket."/".$filename);
			}

            if ($keyExists) {//file has already been uploaded
                $ALERT = array('code'=>14,'message'=>$E[14]['message']);
die('file already is uploaded');
            } else {
				if ($DEV_ENV) {
					$temp_dir = sys_get_temp_dir();
					$temp_file = $temp_dir.preg_replace('/[^[:alnum:]]+/','-',$_FILES['invfile']['name']);

					// store uploaded file in temp dir so we can use it later
					if (move_uploaded_file($_FILES['invfile']['tmp_name'], $temp_file)) {
						echo "File is valid, and was successfully uploaded.\n";
					} else {
						die('file did not save');
					}

                	$query = "INSERT INTO uploads (filename, userid, companyid, datetime, processed, link) ";
                	$query .= "VALUES ('".res($_FILES['invfile']['name'])."','1','".res($cid)."',";
                	$query .= "'".res($GLOBALS['now'])."',NULL,'".htmlspecialchars($temp_file)."'); ";
				} else {
	                $upload = $s3->upload($bucket, $filename, fopen($_FILES['invfile']['tmp_name'], 'rb'), 'public-read');

	                $query = "INSERT INTO uploads (filename, userid, companyid, datetime, processed, link) ";
	                $query .= "VALUES ('".res($_FILES['invfile']['name'])."','".res($U['id'])."','".res($cid)."',";
	                $query .= "'".res($GLOBALS['now'])."',NULL,'".htmlspecialchars($upload->get('ObjectURL'))."'); ";
				}
                $result = qdb($query) OR die(qe().' '.$query);

/*
                $ALERT = array('code'=>0,'message'=>'Success! Processing can take up to 20 mins...');
*/
            }
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
				<div id="remote-warnings"><a class="btn btn-danger btn-sm hidden" id="remote-bb"><img src="/img/bb.png" /></a></div>
			</td>
			<td class="text-center col-md-5">
			</td>
			<td class="col-md-4">
				<div class="pull-right form-group">
					<input class="btn btn-success btn-sm" type="submit" name="save-demand" value="SALES REQUEST">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
					</select>
					<input class="btn btn-warning btn-sm" type="submit" name="save-availability" value="AVAILABILITY">
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

		$results = hecidb(format_part($search_str));
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
					           		<a href="#" class="parts-edit"><i class="fa fa-pencil fa-lg"></i></a>
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
											<input name="buyprice[<?php echo $n; ?>]" type="text" value="0.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
										<span class="info">target price</span>
									</div>
								</div>
							</td>
						</tr>
<?php
		// gather all partid's first
		$partid_str = "";
		$partids = "";//comma-separated for data-partids tag
		foreach ($results as $partid => $P) {
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
			if ($partids) { $partids .= ","; }
			$partids .= $partid;
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
                                <div class="product-descr" data-partid="<?php echo $partid; ?>">
									<span class="descr-label"><span class="part-label"><?php echo $P['Part']; ?></span> &nbsp; <span class="heci-label"><?php echo $P['HECI']; ?></span></span>
                                   	<div class="description descr-label"><span class="manf-label"><?php echo dictionary($P['manf']); ?></span> <?php echo dictionary($P['system']); ?></span> <span class="description-label"><?php echo dictionary($P['description']); ?></span></div>

									<div class="descr-edit hidden">
										<p>
		        							<button type="button" class="close parts-edit"><span>&times;</span></button>
											<div class="form-group">
												<input type="text" value="<?php echo $P['Part']; ?>" class="form-control" data-partid="<?php echo $partid; ?>" data-field="part">
											</div>
											<div class="form-group">
												<input type="text" value="<?php echo $P['HECI']; ?>" class="form-control" data-partid="<?php echo $partid; ?>" data-field="heci">
											</div>
										</p>
										<p>
											<input type="text" name="descr[]" value="<?php echo $P['description']; ?>" class="form-control" data-partid="<?php echo $partid; ?>" data-field="description">
										</p>
										<p>
											<div class="form-group">
												<select name="manfid[]" class="manf-selector" data-partid="<?php echo $partid; ?>" data-field="manfid">
													<option value="<?php echo $P['manfid']; ?>"><?php echo $P['manf']; ?></option>
												</select>
											</div>
											<div class="form-group">
												<select name="systemid[]" class="system-selector" data-partid="<?php echo $partid; ?>" data-field="systemid">
													<option value="<?php echo $P['systemid']; ?>"><?php echo $P['system']; ?></option>
												</select>
											</div>
										</p>
									</div>
								</div>
								<div class="price">
									<div class="form-group">
										<div class="input-group sell">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input type="text" name="sellprice[<?php echo $n; ?>][]" value="<?php echo $itemprice; ?>" size="6" placeholder="Sell" class="input-xs form-control price-control sell-price" />
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
											<div class="market-results" id="<?php echo $n.'-'.$partid; ?>" data-partids="<?php echo $partids; ?>" data-ln="<?php echo $n; ?>"></div>
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
											<input name="buyprice[<?php echo $n; ?>][]" type="text" value="0.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
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
