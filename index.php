<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';

	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric($_REQUEST['listid']) AND $_REQUEST['listid']>0) { $listid = $_REQUEST['listid']; }
	$pg = 1;
	if (isset($_REQUEST['pg']) AND is_numeric($_REQUEST['pg']) AND $_REQUEST['pg']>0) { $pg = $_REQUEST['pg']; }

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

				$query2 = "SELECT datetime, SUM(request_qty) qty, ((SUM(request_qty)*quote_price)/SUM(request_qty)) price FROM demand, search_meta ";
				$query2 .= "WHERE (".$partid_str.") AND demand.metaid = search_meta.id ";
				$query2 .= "AND datetime < '".$GLOBALS['summary_date']."' ";
				$query2 .= "GROUP BY LEFT(datetime,7) ORDER BY datetime DESC; ";
				break;

			case 'purchases':
				$query = "SELECT datetime, name, purchase_orders.id, qty, price FROM purchase_items, purchase_orders, companies ";
				$query .= "WHERE (".$partid_str.") AND purchase_items.purchase_orderid = purchase_orders.id AND companies.id = purchase_orders.companyid ";
				$query .= "AND datetime >= '".$GLOBALS['summary_date']."' ";
				$query .= "ORDER BY datetime ASC; ";

				$query2 = "SELECT datetime, SUM(qty) qty, ((SUM(qty)*price)/SUM(qty)) price FROM purchase_items, purchase_orders ";
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

				$query2 = "SELECT datetime, SUM(qty) qty, ((SUM(qty)*price)/SUM(qty)) price FROM sales_items, sales_orders ";
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

		$result = qdb($query2) OR die(qe().' '.$query2);
		$num_summaries = mysqli_num_rows($result);
		if ($num_detailed>0 AND $num_summaries>0) { $market_str .= '<hr>'; }
		while ($r = mysqli_fetch_assoc($result)) {
			$market_str .= '<div class="market-data"><span class="pa">'.$r['qty'].'x</span>&nbsp; '.summarize_date($r['datetime']).'&nbsp; '.format_price($r['price'],false).'</div>';
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

/*
		if ($order_date==$today) { $date = 'Today'; }
		else if ($order_date==$yesterday) { $date = 'Yesterday'; }
		else if ($order_date>$lastWeek) { $date = format_date($order_date,'D'); }
		else if ($order_date>=$lastYear) { $date = format_date($order_date,'M j'); }
		else { $date = format_date($order_date,'M j, y'); }
*/
		$date = summarize_date($order_date);

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
	<?php include_once 'inc/keywords.php'; ?>
	<?php include_once 'inc/dictionary.php'; ?>
	<?php include_once 'inc/logSearch.php'; ?>
	<?php include_once 'inc/format_price.php'; ?>
	<?php include_once 'inc/getQty.php'; ?>

	<?php include_once 'inc/navbar.php'; ?>

	<form class="form-inline results-form" method="post" action="save-results.php" enctype="multipart/form-data" >

<?php
	if (! $s AND ! $listid) {
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
		$searchlistid = 0;
		if ($s) { $searchlistid = logSearch($s,$search_field,$search_from_right,$qty_field,$qty_from_right,$price_field,$price_from_right); }
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
								<span class="price-header">Price</span>
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

	$lines = array();
	if ($listid) {
		$search_index = 0;
		$qty_index = 1;
//		$query = "SELECT part, heci FROM market, parts WHERE source = '".res($listid)."' AND parts.id = market.partid; ";
		$query = "SELECT search_meta.id metaid, uploads.type FROM search_meta, uploads ";
		$query .= "WHERE uploads.id = '".res($listid)."' AND uploads.metaid = search_meta.id; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			if ($r['type']=='demand') { $table_qty = 'request_qty'; }
			else { $table_qty = 'avail_qty'; }

			$query2 = "SELECT search, ".$table_qty." qty FROM parts, ".$r['type'].", searches ";
			$query2 .= "WHERE metaid = '".$r['metaid']."' AND parts.id = partid AND ".$r['type'].".searchid = searches.id; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				if (array_search($r2['search'],$lines)!==false) { continue; }

				$lines[] = $r2['search'].' '.$r2['qty'];
			}
		}
	} else {
		$lines = explode(chr(10),$s);
	}

	foreach ($lines as $n => $line) {
		$line = trim($line);
		if (! $line) { continue; }

		$rows[] = $line;
	}
	unset($lines);

	$per_pg = 50;
	$min_ln = ($pg*$per_pg)-$per_pg;
	$max_ln = ($min_ln+$per_pg)-1;
	$num_rows = count($rows);

	$x = 0;//line number index for tracking under normal circumstances, but also for favorites-only views
	foreach ($rows as $ln => $line) {
		$terms = preg_split('/[[:space:]]+/',$line);
		$search_str = trim($terms[$search_index]);
		if (! $search_str) { continue; }

		$search_qty = 1;//default
		if (isset($terms[$qty_index])) {
			$qty_text = trim($terms[$qty_index]);
			$qty_text = preg_replace('/^(qty|qnty|quantity)-?0?([0-9]+)-?x?/i','$2',$qty_text);

			if (is_numeric($qty_text) AND $qty_text>0) { $search_qty = $qty_text; }
		}

		$search_price = "0.00";//default
		if (isset($terms[$price_index])) {
			$price_text = trim($terms[$price_index]);
			$price_text = preg_replace('/^([$])([0-9]+)([.][0-9]{0,2})?/i','$2$3',$price_text);

			if ($price_text) { $search_price = number_format($price_text,2,'.',''); }
		}

		$results = hecidb(format_part($search_str));

		// gather all partid's first
		$partid_str = "";
		$partids = "";//comma-separated for data-partids tag

		$favs = array();
		$num_favs = 0;

		// pre-process results so that we can build a partid string for this group as well as to group results
		// if the user is showing just favorites
		foreach ($results as $partid => $P) {
//			print "<pre>".print_r($P,true)."</pre>";
//                                        <img src="/products/images/echo format_part($P['part']).jpg" alt="pic" class="img" />
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
			if ($partids) { $partids .= ","; }
			$partids .= $partid;

			// check favorites
			$favs[$partid] = 'star-o';
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."'; ";
			$result = qdb($query);
			$num_favs += mysqli_num_rows($result);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['userid']==$userid) { $favs[$partid] = 'star text-danger'; }
				else if ($favs[$partid]<>'star text-danger') { $favs[$partid] = 'star-half-o text-danger'; }
			}
		}

		if ($favorites AND $num_favs==0) { continue; }

		if ($x<$min_ln) { $x++; continue; }
		else if ($x>$max_ln) { break; }
		$x++;

		$num_results = count($results);
		$s = '';
		if ($num_results<>1) { $s = 's'; }
?>

                    <tbody>
                        <!-- row -->
                        <tr class="first">
                            <td>
								<div class="product-action text-center">
	                                <div><input type="checkbox" class="checkAll" checked></div>
					           		<a href="javascript:void(0);" class="parts-merge" title="merge two selected part(s) into one"><i class="fa fa-chain fa-lg"></i></a>
					           		<a href="javascript:void(0);" class="parts-edit" title="edit selected part(s)"><i class="fa fa-pencil fa-lg"></i></a>
								</div>
								<div class="qty">
									<input type="text" name="search_qtys[<?php echo $ln; ?>]" value="<?php echo $search_qty; ?>" class="form-control input-xs search-qty input-primary" /><br/>
									<span class="info">their qty</span>
								</div>
								<div class="product-descr">
									<input type="text" name="searches[<?php echo $ln; ?>]" value="<?php echo $search_str; ?>" class="product-search text-primary" /><br/>
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
									<div class="form-group target">
										<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" />
										<span class="info">their price</span>
									</div>
								</div>
							</td>
						</tr>

<?php
		$k = 0;
		foreach ($results as $partid => $P) {
			$itemqty = getQty($partid);
			$rowcls = '';
			if ($itemqty>0) { $rowcls = ' info'; }

			$itemprice = "0.00";
			$fav_flag = $favs[$partid];

			$chkd = '';
			if ($k==0 OR $itemqty>0) { $chkd = ' checked'; }
?>
                        <!-- row -->
                        <tr class="product-results" id="row-<?php echo $partid; ?>">
                            <td class="descr-row<?php echo $rowcls; ?>">
								<div class="product-action text-center">
                                	<div><input type="checkbox" class="item-check" name="items[<?php echo $ln; ?>][<?php echo $k; ?>]" value="<?php echo $partid; ?>"<?php echo $chkd; ?>></div>
                                    <a href="javascript:void(0);" data-partid="<?php echo $partid; ?>" class="fa fa-<?php echo $fav_flag; ?> fa-lg fav-icon"></a>
								</div>
								<div class="qty">
									<div class="form-group">
										<input name="sellqty[<?php echo $ln; ?>][]" type="text" value="<?php echo $itemqty; ?>" size="2" placeholder="Qty" class="input-xs form-control" />
									</div>
								</div>
                                <div class="product-img">
                                    <img src="/products/images/090-42140-13.jpg" alt="pic" class="img" />
                                </div>
                                <div class="product-descr" data-partid="<?php echo $partid; ?>">
									<span class="descr-label"><span class="part-label"><?php echo $P['Part']; ?></span> &nbsp; <span class="heci-label"><?php echo $P['HECI']; ?></span></span>
                                   	<div class="description descr-label"><span class="manfid-label"><?php echo dictionary($P['manf']); ?></span> <span class="systemid-label"><?php echo dictionary($P['system']); ?></span> <span class="description-label"><?php echo dictionary($P['description']); ?></span></div>

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
											<input type="text" name="sellprice[<?php echo $ln; ?>][]" value="<?php echo $itemprice; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control sell-price" />
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
											<a href="javascript:void(0);" class="market-title">Availability</a>
											<div class="market-results" id="<?php echo $ln.'-'.$partid; ?>" data-partids="<?php echo $partids; ?>" data-ln="<?php echo $ln; ?>"></div>
										</td>
									</tr>
								</table>
                            </td>
<?php
			}

			$k++;
?>
                            <td class="product-actions text-right">
								<div class="price">
									<div class="form-group">
										<div class="input-group buy">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input name="buyprice[<?php echo $ln; ?>][]" type="text" value="0.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
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
<?php
		if ($num_rows>$per_pg) {
			$pages = ceil($num_rows/$per_pg);
			$paginates = '';
			$end_pg = '';
			if ($pages>4) {
				$paginates = '<li><a href="javascript:void(0);" data-pg="1" data-listid="'.$listid.'">&laquo;</a></li>'.chr(10);
                $end_pg = '<li><a href="javascript:void(0);" data-pg="'.$pages.'" data-listid="'.$listid.'">&raquo;</a></li>'.chr(10);
			}
			$pstart = 1;
			if ($pg>2) { $pstart = $pg-2; }
			for ($p=$pstart; $p<=($pstart+3); $p++) {
				$cls = '';
				if ($p==$pg) { $cls = ' class="active"'; }
                $paginates .= '<li'.$cls.'><a href="javascript:void(0);" data-pg="'.$p.'" data-listid="'.$listid.'">'.$p.'</a></li>'.chr(10);
			}
			$paginates .= $end_pg;
?>
            <ul class="pagination">
				<?php echo $paginates; ?>
            </ul>
<?php
		}
?>
        </div>
<?php
	}//end if ($s)
?>

    </div>

	</form>

<?php include_once 'inc/footer.php'; ?>

</body>
</html>
