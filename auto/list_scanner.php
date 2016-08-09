<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/pipe.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getPipeIds.php';
	include_once 'inc/getPipeQty.php';
	include_once 'inc/getRecords.php';
	include_once 'inc/keywords.php';
	include_once 'inc/description.php';
	include_once 'inc/logSearch.php';
	include_once 'inc/getQty.php';

	$listid = 0;
	if (isset($_REQUEST['listid']) AND is_numeric($_REQUEST['listid']) AND $_REQUEST['listid']>0) { $listid = $_REQUEST['listid']; }
	if (! $listid) {
		die("No list passed in");
	}

	$yesterday = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-1));
	$lastWeek = format_date(date("Y-m-d"),'Y-m-d',array('d'=>-7));
	$lastYear = format_date(date("Y-m-d"),'Y-m-01',array('m'=>-11));

	$lines = array();

	$search_index = 0;
	$qty_index = 1;
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

	foreach ($lines as $ln => $line) {
		// split into words/terms on white-space separations, whether space or tab
		$terms = preg_split('/[[:space:]]+/',$line);
		// trim search string and convert to uppercase
		$search_str = strtoupper(trim($terms[$search_index]));
		if (strlen($search_str)<=1) { continue; }

		$search_qty = 1;//default
		if (isset($terms[$qty_index])) {
			$qty_text = trim($terms[$qty_index]);
			$qty_text = preg_replace('/^(qty|qnty|quantity)?([.]|-)?0?([0-9]+)([.]|-)?(x|ea)?/i','$3',$qty_text);

			if (is_numeric($qty_text) AND $qty_text>0) { $search_qty = $qty_text; }
		}

		$search_price = "0.00";//default
		if ($price_index!==false AND isset($terms[$price_index])) {
			$price_text = trim($terms[$price_index]);
			$price_text = preg_replace('/^([$])([0-9]+)([.][0-9]{0,2})?/i','$2$3',$price_text);

			if ($price_text) { $search_price = number_format($price_text,2,'.',''); }
		}

		// if 10-digit string, detect if qualifying heci, determine if heci so we can search by 7-digit instead of full 10
		$heci7_search = false;
		if (strlen($search_str)==10 AND ! is_numeric($search_str) AND preg_match('/^[[:alnum:]]{10}$/',$search_str)) {
			$query = "SELECT heci FROM parts WHERE heci LIKE '".substr($search_str,0,7)."%'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)>0) { $heci7_search = true; }
		}

		if ($heci7_search) {
			$results = hecidb(substr($search_str,0,7));
		} else {
			$results = hecidb(format_part($search_str));
		}

		// gather all partid's first
		$partid_str = "";
		$partids = "";//comma-separated for data-partids tag

		$favs = array();
		$num_favs = 0;

		$pipe_ids = array();
		$pipe_id_assoc = array();
		// pre-process results so that we can build a partid string for this group as well as to group results
		// if the user is showing just favorites
		foreach ($results as $partid => $P) {
			if ($partid_str) { $partid_str .= "OR "; }
			$partid_str .= "partid = '".$partid."' ";
			if ($partids) { $partids .= ","; }
			$partids .= $partid;

			$results[$partid]['pipe_id'] = 0;
			if ($P['heci']) {
				$ids = getPipeIds(substr($P['heci'],0,7),'heci');
				foreach ($ids as $id => $arr) {
					if ($arr['heci']===$P['heci']) { $pipe_id_assoc[$id] = $partid; unset($pipe_ids[$id]); $results[$partid]['pipe_id'] = $id; }
					else if (! isset($pipe_id_assoc[$id])) { $pipe_ids[$id] = $arr; }
				}
			}
			$ids = getPipeIds($P['part'],'part');
			foreach ($ids as $id => $arr) {
				if (! isset($pipe_id_assoc[$id])) { $pipe_ids[$id] = $arr; }
			}

			// check favorites
			$favs[$partid] = 'star-o';
			$query = "SELECT * FROM favorites WHERE partid = '".$partid."'; ";
			$result = qdb($query);
			$num_favs += mysqli_num_rows($result);
			while ($r = mysqli_fetch_assoc($result)) {
				if ($r['userid']==$U['id']) { $favs[$partid] = 'star text-danger'; }
				else if ($favs[$partid]<>'star text-danger') { $favs[$partid] = 'star-half-o text-danger'; }
			}
		}
if ($ln>100) { break; }
echo $search_str.'<BR>';
	}
print_r($favs);
exit;


	while (1 == 2) {
		if ($favorites AND $num_favs==0) { continue; }

		$num_results = count($results);
		$s = '';
		if ($num_results<>1) { $s = 's'; }

		$avg_cost = '';
		$results_rows = '';
		$k = 0;
		foreach ($results as $partid => $P) {
			$itemqty = 0;
			if ($P['pipe_id']) {
				$itemqty = getPipeQty($P['pipe_id']);
			} else {
				foreach ($pipe_ids as $pipe_id => $arr) {
					$itemqty += getPipeQty($pipe_id);
				}
				$pipe_ids = array();
			}

			//$itemqty = getQty($partid);
			$rowcls = '';
			if ($itemqty>0) { $rowcls = ' info'; }

			$itemprice = "0.00";
			$fav_flag = $favs[$partid];

			$partstrs = explode(' ',$P['part']);
			$primary_part = $partstrs[0];

			$chkd = '';
			if ($k==0 OR $itemqty>0) { $chkd = ' checked'; }

			$results_rows .= '
                        <!-- row -->
                        <tr class="product-results" id="row-'.$partid.'">
                            <td class="descr-row'.$rowcls.'">
								<div class="product-action text-center">
                                	<div><input type="checkbox" class="item-check" name="items['.$ln.']['.$k.']" value="'.$partid.'"'.$chkd.'></div>
<!--
<div class="action-items">
-->
                                    <a href="javascript:void(0);" data-partid="'.$partid.'" class="fa fa-'.$fav_flag.' fa-lg fav-icon"></a>
<!--
</div>
-->
								</div>
								<div class="qty">
									<div class="form-group">
										<input name="sellqty['.$ln.'][]" type="text" value="'.$itemqty.'" size="2" placeholder="Qty" class="input-xs form-control" />
									</div>
								</div>
                                <div class="product-img">
                                    <img src="http://www.ven-tel.com/img/parts/'.format_part($primary_part).'.jpg" alt="pic" class="img" data-part="'.$primary_part.'" />
                                </div>
                                <div class="product-descr" data-partid="'.$partid.'">
									<span class="descr-label"><span class="part-label">'.$P['Part'].'</span> &nbsp; <span class="heci-label">'.$P['HECI'].'</span></span>
                                   	<div class="description descr-label"><span class="manfid-label">'.dictionary($P['manf']).'</span> <span class="systemid-label">'.dictionary($P['system']).'</span> <span class="description-label">'.dictionary($P['description']).'</span></div>

									<div class="descr-edit hidden">
										<p>
		        							<button type="button" class="close parts-edit"><span>&times;</span></button>
											<input type="text" value="'.$P['Part'].'" class="form-control" data-partid="'.$partid.'" data-field="part" placeholder="Part Number">
										</p>
										<p>
											<input type="text" value="'.$P['HECI'].'" class="form-control" data-partid="'.$partid.'" data-field="heci" placeholder="HECI/CLEI">
										</p>
										<p>
											<input type="text" name="descr[]" value="'.$P['description'].'" class="form-control" data-partid="'.$partid.'" data-field="description" placeholder="Description">
										</p>
										<p>
											<div class="form-group">
												<select name="manfid[]" class="manf-selector" data-partid="'.$partid.'" data-field="manfid">
													<option value="'.$P['manfid'].'">'.$P['manf'].'</option>
												</select>
											</div>
											<div class="form-group">
												<select name="systemid[]" class="system-selector" data-partid="'.$partid.'" data-field="systemid">
													<option value="'.$P['systemid'].'">'.$P['system'].'</option>
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
											<input type="text" name="sellprice['.$ln.'][]" value="'.$itemprice.'" size="6" placeholder="0.00" class="input-xs form-control price-control sell-price" />
										</div>
									</div>
								</div>
                            </td>
			';

			$results_rows .= '
                            <td class="product-actions text-right">
								<div class="price">
									<div class="form-group">
<!--
										<div class="input-group buy">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button"><i class="fa fa-lock"></i></button>
											</span>
											<input name="buyprice['.$ln.'][]" type="text" value="0.00" size="6" placeholder="Buy" class="input-xs form-control price-control" />
										</div>
-->
									</div>
								</div>
                            </td>
                        </tr>
			';
		}
?>

                    <tbody>
                        <!-- row -->
                        <tr class="first">
                            <td>
								<div class="product-action text-center">
	                                <div><input type="checkbox" class="checkAll" checked></div>
<div class="action-meta">
					           		<a href="javascript:void(0);" class="parts-merge" title="merge two selected part(s) into one"><i class="fa fa-chain fa-lg"></i></a>
					           		<a href="javascript:void(0);" class="parts-edit" title="edit selected part(s)"><i class="fa fa-pencil fa-lg"></i></a>
</div>
								</div>
								<div class="qty">
									<input type="text" name="search_qtys[<?php echo $ln; ?>]" value="<?php echo $search_qty; ?>" class="form-control input-xs search-qty input-primary" /><br/>
									<span class="info">their qty</span>
								</div>
								<div class="product-descr">
									<input type="text" name="searches[<?php echo $ln; ?>]" value="<?php echo $search_str; ?>" class="product-search text-primary" /><br/>
									<span class="info"><?php echo $num_results.' result'.$s; ?></span>
								</div>
								<div class="price pull-right">
									<div class="form-group target text-right">
										<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" />
										<span class="info">their price</span>
									</div>
								</div>
							</td>
                            <td>
								<div class="row">
									<div class="col-sm-3 text-center"><br/><span class="info">market pricing</span></div>
									<div class="col-sm-3 text-center"><?php echo format_price($avg_cost); ?><br/><span class="info">avg cost</span></div>
									<div class="col-sm-3 text-center"><br/><span class="info">shelflife</span></div>
									<div class="col-sm-3 text-center"><br/><span class="info">quotes-to-sale</span></div>
								</div>
							</td>
<!--
                            <td class="text-right">
								<div class="price">
									<div class="form-group target">
										<input name="list_price[<?php echo $ln; ?>]" type="text" value="<?php echo $search_price; ?>" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" />
										<span class="info">their price</span>
									</div>
								</div>
							</td>
-->
						</tr>

						<?php echo $results_rows; ?>

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
        </div>
<?php
	}//end if ($s)
?>
