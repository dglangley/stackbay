<?php
	$rootdir = $_SERVER['ROOT_DIR'];

	include_once $rootdir . '/inc/dbconnect.php';
	include_once $rootdir . '/inc/getRecords.php';
	include_once $rootdir . '/inc/format_market.php';
	include_once $rootdir . '/inc/keywords.php';
	include_once $rootdir . '/inc/getQty.php';
	include_once $rootdir . '/inc/dictionary.php';
	include_once $rootdir . '/inc/getCompany.php';
	include_once $rootdir . '/inc/format_price.php';
	include_once $rootdir . '/inc/format_date.php';
	include_once $rootdir . '/inc/getUser.php';
	include_once $rootdir . '/inc/getShelflife.php';
	include_once $rootdir . '/inc/getDQ.php';

	header("Content-Type: application/json", true);

    if (! isset($startDate)) { $startDate = ''; }//format_date($today,'m-d-Y',array('d'=>-7)); }
    else { $startDate = format_date($startDate,'m/d/Y'); }
    if (isset($_REQUEST['startDate']) AND preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/',$_REQUEST['startDate'])) { $startDate = $_REQUEST['startDate']; }
	if (! isset($endDate)) { $endDate = format_date($today,'m/d/Y'); }
    if (isset($_REQUEST['endDate']) AND preg_match('/^[0-9]{2}.[0-9]{2}.[0-9]{4}$/',$_REQUEST['endDate'])) { $endDate = $_REQUEST['endDate']; }

	/* FILTERS */
	$sales_count = false;
	if (isset($_REQUEST['sales_count']) AND trim($_REQUEST['sales_count'])<>'') { $sales_count = trim($_REQUEST['sales_count']); }
	$sales_min = false;
	if (isset($_REQUEST['sales_min']) AND trim($_REQUEST['sales_min'])<>'') { $sales_min = trim($_REQUEST['sales_min']); }
	$sales_max = false;
	if (isset($_REQUEST['sales_max']) AND trim($_REQUEST['sales_max'])<>'') { $sales_max = trim($_REQUEST['sales_max']); }
	$stock_min = false;
	if (isset($_REQUEST['stock_min']) AND trim($_REQUEST['stock_min'])<>'') { $stock_min = trim($_REQUEST['stock_min']); }
	$stock_max = false;
	if (isset($_REQUEST['stock_max']) AND trim($_REQUEST['stock_max'])<>'') { $stock_max = trim($_REQUEST['stock_max']); }
	$demand_min = false;
	if (isset($_REQUEST['demand_min']) AND trim($_REQUEST['demand_min'])<>'') { $demand_min = trim($_REQUEST['demand_min']); }
	$demand_max = false;
	if (isset($_REQUEST['demand_max']) AND trim($_REQUEST['demand_max'])<>'') { $demand_max = trim($_REQUEST['demand_max']); }
	$favorites = 0;
	if (isset($_REQUEST['favorites']) AND $_REQUEST['favorites']) { $favorites = 1; }
	$dq_count = false;
	if (isset($_REQUEST['dq_count']) AND trim($_REQUEST['dq_count'])<>'') { $dq_count = trim($_REQUEST['dq_count']); }

	// check for any filters to be set
	$filtersOn = false;
	if ($sales_count!==false OR $sales_min!==false OR $sales_max!==false OR $stock_min!==false OR $stock_max!==false OR $demand_min!==false OR $demand_max!==false OR $startDate<>'' OR $endDate<>$today OR $dq_count!==false) {
		$filtersOn = true;
	}

	//toggle between repair / sales table view / or toggle new partid
	//Get and Set Variables
	$type = (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'partid');
	$market_table = (isset($_REQUEST['market_table']) ? $_REQUEST['market_table'] : '');
	$search_strs = (isset($_REQUEST['search_strs']) ? $_REQUEST['search_strs'] : '');
	$partid_csv = (isset($_REQUEST['partid_csv']) ? $_REQUEST['partid_csv'] : '');

	$ln = (isset($_REQUEST['ln']) ? $_REQUEST['ln'] : 0);

	//Used for list updates specifically
	$listid = (isset($_REQUEST['listid']) ? $_REQUEST['listid'] : '');
	$sort = (isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'line');

	$start = (isset($_REQUEST['start']) ? $_REQUEST['start'] : '');
	$end = (isset($_REQUEST['end']) ? $_REQUEST['end'] : '');

	$equipment_filter = (isset($_REQUEST['equipment_filter']) ? $_REQUEST['equipment_filter'] : '');

	//This function determines and pools together all the needed html based on the ajax call
	function htmlCompiler($partid_csv, $search_strs, $type, $ln, $market_table, $equipment_filter, $start, $end, $listid, $sort){
		$html = '';

		//Add in the needed html to replace an entire table of data
		if($type == 'partid') {
			$html .= partTable($search_strs, $ln, $equipment_filter);
		} else if($type== 'records') {
			$html = array();
			$html = getRecords($search_strs, $partid_csv, 'csv', $market_table);
		} else if($type == 'modal') {
			//Technically not HTML data but an array of data pulled from getRecords()
			$html = salesModal($partid_csv, $search_strs, $market_table);
		} else if($type == 'list') {
			//Technically not HTML data but an array of data pulled from getRecords()
			$html .= listLoad($listid, $ln, $equipment_filter, $sort);
		} else {
			$html .= marketTable($partid_csv, $search_strs, $type, $ln, '0.00', $start, $end);	
		}

		return $html;
	}

	//Used for the sales modal to return data for the sales pop up modal (Includes purchases and Repairs)
	function salesModal($partid_csv, $search_strs, $market_table) {
		$data = array();

		$data = getRecords($search_strs, $partid_csv, 'csv', $market_table);

		//Filter data records to company instead of cid etc
		foreach($data as $key => $row){
			//$data[$key]['company'] = getCompany($row['cid']);
			$data[$key]['format_price'] = format_price($row['price']);
			if($row['userid']){
				$fullName = explode(' ',trim(getUser($row['userid'])));
				$data[$key]['username'] = $fullName[0];;
			}
			$data[$key]['date'] = format_date($row['datetime']);
		}

		return $data;
	}

/*
	function getQtyPatch($partid=0) { // Creating a temporary fix to no-stock and never-stock to prevent getQty usage from breaking on other pages
		global $QTYS;

		$qty = 0;

		if (! $partid OR ! is_numeric($partid)) { return ($qty); }

		$QTYS[$partid] = 0;
		$query = "SELECT SUM(qty) qty FROM inventory WHERE partid = '".$partid."';";
		//$query .= "AND conditionid >= 0 AND (status = 'shelved' OR status = 'received'); ";//status <> 'scrapped' AND status <> 'in repair'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)==0) { return ('null'); }
		$r = mysqli_fetch_assoc($result);
		$qty = $r['qty'];

		return ($qty);
	}
*/

	//This function loads in the entire line item
	function listLoad($listid, $last_ln, $equipment_filter, $sort) {
		global $favorites,$filtersOn;

		$html = '';

		$lines = array();
		$rows = array();

		$search_index = 0;
		$qty_index = 1;
		$line_index = 2;
		$stock_index = 3;

		$query = "SELECT search_meta.id metaid, uploads.type, processed, filename FROM search_meta, uploads ";
		$query .= "WHERE uploads.id = '".res($listid)."' AND uploads.metaid = search_meta.id; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$title = $r['filename'];

			if ($r['processed']) {
				if ($r['type']=='demand') { $table_qty = 'request_qty'; }
				else { $table_qty = 'avail_qty'; }

				$query2 = '';

				if($sort == 'line') {
					$query2 = "SELECT search, ".$table_qty." qty, line_number, '' stock FROM parts, ".$r['type'].", searches ";
					$query2 .= "WHERE metaid = '".$r['metaid']."' AND parts.id = partid AND ".$r['type'].".searchid = searches.id; ";
				} else {
					//Other query to sort by (Stock, No Stock, Never Had)
					$query2 = "SELECT search, ".$table_qty." qty, line_number, 'in' stock FROM parts, ".$r['type'].", searches WHERE metaid = '".$r['metaid']."' AND parts.id = ".$r['type'].".partid AND ".$r['type'].".searchid = searches.id AND EXISTS (SELECT partid FROM inventory WHERE ".$r['type'].".partid = inventory.partid AND inventory.status IN ('shelved', 'received') AND inventory.conditionid >= 0 HAVING SUM(inventory.qty) > 0)
							UNION
								SELECT search, ".$table_qty." qty, line_number, 'out' stock FROM parts, ".$r['type'].", searches WHERE metaid = '".$r['metaid']."' AND parts.id = ".$r['type'].".partid AND ".$r['type'].".searchid = searches.id AND EXISTS (SELECT partid FROM inventory WHERE ".$r['type'].".partid = inventory.partid AND inventory.status NOT IN ('shelved', 'received') HAVING SUM(inventory.qty) > 0)
							UNION
								SELECT search, ".$table_qty." qty, line_number, 'out' stock FROM parts, ".$r['type'].", searches WHERE metaid = '".$r['metaid']."' AND parts.id = ".$r['type'].".partid AND ".$r['type'].".searchid = searches.id AND EXISTS (SELECT partid FROM inventory WHERE ".$r['type'].".partid = inventory.partid HAVING SUM(inventory.qty) = 0)
							UNION
								SELECT search, ".$table_qty." qty, line_number, 'never' stock FROM parts, ".$r['type'].", searches WHERE metaid = '".$r['metaid']."' AND parts.id = ".$r['type'].".partid AND ".$r['type'].".searchid = searches.id AND NOT EXISTS (SELECT partid FROM inventory WHERE ".$r['type'].".partid = inventory.partid);";
				}
				$result2 = qdb($query2);
				while ($r2 = mysqli_fetch_assoc($result2)) {
					// does this search string (followed by an appended space, as in the following 'search qty' format) already
					// exist in the array? if so, don't add to list for duplication of calculations below
					if (array_stristr($lines,$r2['search'].' ')!==false) { continue; }

					$lines[] = $r2['search'].' '.$r2['qty'].' '.$r2['line_number'].' '.$r2['stock'];
				}
			} else {
				// if list is not processed, alert the user
				$ALERTS[] = "Please wait while I process your list. If you do not have an email from me within 10 or 15 minutes, ".
					"you may have unorganized data in your list that I cannot handle.";
			}
		}
	
		foreach ($lines as $n => $line) {
			$line = trim($line);
			if (! $line) { continue; }

			$rows[] = $line;
		}
		unset($lines);

		//Unset all rows from 0 -> the last LN on the page
		//If LN is 0 then dont slice anything out of the line items
		if($last_ln > 0) {
			$rows = array_slice($rows, $last_ln);
		}

		$per_pg = 10;
		$min_ln = 0;
		$max_ln = ($min_ln+$per_pg)-1;
		$num_rows = count($rows);

		$x = 0;//line number index for tracking under normal circumstances, but also for favorites-only views
		foreach ($rows as $ln => $line) {
			$terms = preg_split('/[[:space:]]+/',$line);
			$search_str = strtoupper(trim($terms[$search_index]));
			$stock = $terms[$stock_index];

			if (! $search_str) {
				$num_rows--;//mostly impacting pagination
				continue;
			}

			$search_qty = 1;//default
			if (isset($terms[$qty_index])) {
				$qty_text = trim($terms[$qty_index]);
				$qty_text = preg_replace('/^(qty|qnty|quantity)?([.]|-)?(\\()?0?([0-9]+)(\\))?([.]|-)?(x|ea)?/i','$4',$qty_text);

				if (is_numeric($qty_text) AND $qty_text>0) { $search_qty = $qty_text; }
			}

			$line_number = strtoupper(trim($terms[$line_index])) - 1;

			$search_price = "0.00";//default
			if ($price_index!==false AND isset($terms[$price_index])) {
				$price_text = trim($terms[$price_index]);
				$price_text = preg_replace('/^([$])([0-9]+)([.][0-9]{0,2})?/i','$2$3',$price_text);

				if ($price_text) { $search_price = number_format($price_text,2,'.',''); }
			}

			// if favorites is NOT set, we're counting rows based on global results and don't need to waste pre-processing in first loop below
			if (! $favorites AND ! $filtersOn) {
				if ($x<$min_ln) { $x++; continue; }
				else if ($x>$max_ln) { 
					$html .= '<div class="row infinite_scroll spinner_lock" data-page="'.(isset($ln) ? ($ln + $last_ln) + 1 : '1').'" data-list="'.$listid.'"><i style="display: block; text-align: center;" class="fa fa-circle-o-notch fa-spin"></i></div>';
					break; 
				}
				$x++;
			}

			$html .= '<div class="table part_info line_'.($line_number+1).' '.($sort != 'line' ?  $stock . '-stock' : '').'">' . partTable($search_str, $line_number, $equipment_filter, $search_qty, $search_price) . '</div>';
		}

		return $html;
	}

	//Generates the left portion of the sales tables (AKA the revisions, but also generates the first row)
	function partTable($search_str, $ln, $equipment_filter, $search_qty = 1, $search_price = '0.00'){
		global $demand_min,$demand_max,$dq_count;

		//Grabbing code from original sales view to generate results for the parts table
		// can contain additional info about the results, if set; presents itself after the "X results" row below the row's search field
		$explanation = '';
		$counter = 0;

		$rev_total = 0;
		$partid_csv = "";
		$search_strs = array();

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

		//If both leave it as is
		if($equipment_filter != 'both' && !empty($equipment_filter)){
			//array filter by row (multi array) and only return the rows that have the correct classification (equipment or component)
			$results = array_filter($results, function($row) { global $equipment_filter; return ($row['classification'] == $equipment_filter); });
		}

		// the LARGE majority of items don't have more than 25 results within a certain group of 7-digit hecis
		if (count($results)>25) {
			$start = 0;
			$end = 25;
			$explanation = '<i class="fa fa-warning fa-lg"></i> '.count($results).' results found, limited to first 25!';
			// take the top 25 results
			$results = array_slice($results,$start,$end,true);
		}

		$html = '';

		$num_results = count($results);
		$last_element = $num_results - 1;
		$num_favs = 0;

		foreach ($results as $partid => $P) {
			$exploded_strs = explode(' ',$P['part']);
			$search_strs = array_merge($search_strs,$exploded_strs);
			if ($P['heci']) {
				$search_strs[] = substr($P['heci'],0,7);
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

			$itemqty = 0;
			$results[$partid]['notes'] = '';

			// change to this after migration, remove ~7-10 lines above
			if(getQty($partid) !== false) {
				$itemqty = getQty($partid);
				$lineqty += $itemqty;
			} else {
				$itemqty = '';
			}

			$results[$partid]['qty'] = $itemqty;

			if ($partid_csv) { $partid_csv .= ","; }
				$partid_csv .= $partid;
		}

		if ($favorites AND $num_favs==0) { return ($html); }

		$demand = getRecords($search_str,$partid_csv,'csv','demand');
		$demand_count = count($demand);

		// exclude results not meeting minimum demand count or exceeding max count, if set
		if (($demand_min!==false AND $demand_count<$demand_min) OR ($demand_max!==false AND $demand_count>$demand_max)) { return ($html); }

		$shelflife = getShelflife($partid_csv);
		$DQ = getDQ($partid_csv);
		if ($dq_count!==false AND $DQ<$dq_count) { return ($html); }

		if ($DQ<0) { $DQ = '<span class="text-danger">'.$DQ.'</span>'; }

		$avg_cost = getCost($partid_csv);

		//Generate first row
		$html .= '<div class="row first" style="padding: 8px;">
					<div class="col-sm-4">
						<div class="product-action action-hover text-left">
							<div>
								<input type="checkbox" class="checkAll" checked><br/>
							</div>
							<div class="action-items">
					           	<!--<a href="javascript:void(0);" class="parts-edit" title="edit selected part(s)"><i class="fa fa-pencil"></i></a><br/>-->
					           	<a href="javascript:void(0);" class="parts-merge" title="merge two selected part(s) into one"><i class="fa fa-chain"></i></a><br/>';

		if ($num_results==0) { // add link to create a new part 
			$html .= '			<a href="javascript:void(0);" class="add-part" title="add to parts db"><i class="fa fa-plus"></i></a>';
		} else {
			$html .= '			<a href="javascript:void(0);" class="parts-index" title="re-index db (reloads page)"><i class="fa fa-cog"></i></a>';
		}

		$html .= '			</div>
						</div>
						<div class="qty">
							<input type="text" name="search_qtys['.$ln.']" value="'.$search_qty.'" class="form-control input-xs search-qty input-primary" data-toggle="tooltip" data-placement="top" title="customer request qty or supplier available qty" /><br/>
						</div>
						<div class="product-descr action-hover">
		                	<div class="input-group">
								<input type="text" name="searches['.$ln.']" value="'.$search_str.'" data-ln="'.$ln.'" class="product-search text-primary" tabindex="-1" />
							</div>
							<span class="info">'.$num_results.' result'.($num_results > 0 ? 's' : '').'</span> &nbsp; <span class="text-danger">'.$explanation.'</span>

							'.($num_results == 0 ? '<a href="javascript:void(0);" class="part-modal-show" data-partid="">
								<i class="fa fa-plus"></i></a>' : '').'
						</div>
						';

		$html .= '	</div>';

		$html .= '	<div class="col-sm-8 action-hover slider-box">';

		$html .= '	<div class="row">
						<div class="col-sm-2 text-center">
							<div id="marketpricing-'.$ln.'" class="header-text">&nbsp;</div>
							<div class="form-group target text-right">
								<input name="list_price['.$ln.']" type="text" value="'.$search_price.'" size="6" placeholder="0.00" class="input-xs form-control price-control input-primary" data-toggle="tooltip" data-placement="top" title="customer target price or vendor asking price" />
							</div>
							
						</div>
						<div class="col-sm-2 text-center"><span class="header-text">'.format_price($avg_cost).'</span><br/><span class="info">avg cost</span></div>
						<div class="col-sm-2 text-center"><span class="header-text">'.$shelflife.'</span><br/><span class="info">shelflife</span></div>
						<div class="col-sm-2 text-center"><span class="header-text">'.$DQ.'</span><br/><span class="info">usage rating</span></div>
						<div class="col-sm-2 text-center">
							<span class="header-text"></span><br/><span class="info">summary</span>
						</div>
						<div class="col-sm-2 text-center">
							<div class="pull-right">
								<a class="line-number-toggle toggle-up" style="cursor: pointer;">
									<i class="fa fa-list-ol"></i>
									<sup><i class="fa options-toggle fa-sort-asc"></i></sup>
								</a>
							</div>

							<div class="slider-frame primary pull-right" data-onclass="default" data-offclass="primary">
								<input type="radio" name="line_number['.($ln+1).']" class="row-status line-number hidden" value="Ln '.($ln+1).'">
								<input type="radio" name="line_number['.($ln+1).']" class="row-status line-number hidden" value="Off">
								<span data-on-text="Ln '.($ln+1).'" data-off-text="Off" class="slider-button" data-toggle="tooltip" data-placement="top" title="enable/disable results for this row">Ln '.($ln+1).'</span>
							</div>
						</div>
					</div>';

		$html .= '	</div>';

		$html .= '</div>';

		//Generate the information following the data
		$html .= '<div class="product-results animated row" id="row-'.$partid.'">
					<div class="col-md-4 remove-pad parts-container">';

		foreach ($results as $partid => $P) {
			$itemqty = $P['qty'];
			$notes = $P['notes'];
			$pipeids_str = '';//$P['pipeids_str'];

			// if no notes through pipe, check new db (this is just for the notes flag)
			if (! $notes) {
				$query2 = "SELECT * FROM prices WHERE partid = '".$partid."'; ";
				$result2 = qdb($query2);
				if (mysqli_num_rows($result2)>0) {
					$notes = true;
				}
			}

			$rowcls = '';
			if ($itemqty>0) { $rowcls = ' info'; }

			$itemprice = "0.00";
			$fav_flag = $favs[$partid];

			$partstrs = explode(' ',$P['part']);
			$primary_part = $partstrs[0];

			$chkd = '';
			if (($counter==0 OR $itemqty>0) && !$page) { $chkd = ' checked'; }

			$notes_icon = '';
			if ($notes) {
				if (isset($NOTIFICATIONS[$partid])) {
					$notes_icon = 'text-danger fa-warning fa-lg';
				} else {
					$notes_icon = 'fa-sticky-note text-warning';
				}
			} else {
				$notes_icon = 'fa-sticky-note-o';
			}

			$notes_flag = '<span class="item-notes"><i class="fa '.$notes_icon.'"></i></span>';

			$html .= '
                        <div class="row descr-row '.($itemqty > 0 ? 'in-stock' : ($itemqty == null ? 'never-stock' : 'out-stock')).'" style="padding:8px;">
	                        <div class="col-sm-3 remove-pad">
								<div class="product-action text-center">
	                            	<div class="action-box"><input type="checkbox" class="item-check" name="items['.$ln.']['.$counter.']" value="'.$partid.'"'.$chkd.'></div>
	                                <a href="javascript:void(0);" data-partid="'.$partid.'" class="fa fa-'.$fav_flag.' fa-lg fav-icon" data-toggle="tooltip" data-placement="right" title="Add/Remove as a Favorite"></a>
								</div>
								<div class="qty">
									<div class="form-group">
										<input name="sellqty['.$ln.'][]" type="text" value="'.$itemqty.'" size="2" placeholder="Qty" class="input-xs form-control" />
									</div>
								</div>
	                            <div class="product-img">
	                                <img src="/img/parts/'.format_part($primary_part).'.jpg" alt="pic" class="img" data-part="'.$primary_part.'" />
	                            </div>
	                        </div>

	                        <div class="col-sm-7 remove-pad">
	                            <div class="product-descr" data-partid="'.$partid.'" data-pipeids="'.$pipeids_str.'">
									<span class="descr-label"><span class="part-label">'.$P['Part'].'</span> &nbsp; <span class="heci-label">'.$P['HECI'].'</span> &nbsp; '.$notes_flag.'</span><a style="margin-left: 5px;" href="javascript:void(0);" class="part-modal-show" data-partid="'.$partid.'" data-ln="'.($ln+1).'" style="cursor:pointer;"><i class="fa fa-pencil"></i></a>
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
							</div>

							<div class="col-sm-2">
								<div class="price">
									<div class="form-group">
										<div class="input-group sell">
											<span class="input-group-btn">
												<button class="btn btn-default input-xs control-toggle" type="button" tabindex="-1" data-toggle="tooltip" data-placement="left" title="group/ungroup prices for item"><i class="fa fa-lock"></i></button>
											</span>
											<input type="text" name="sellprice['.$ln.'][]" value="'.$itemprice.'" size="6" placeholder="0.00" class="input-xs form-control price-control sell-price" />
										</div>
									</div>
								</div>
							</div>
                        </div>
			';

			//Sum up all the revision cost per line number
			$rev_total += $itemprice * $itemqty;

			// if on the first result, build out the market column that runs down all rows of results
			if ($counter==$last_element) {
				// Close Class  'parts-container'
				$html .= '</div>';

				$html .= '<div class="market-row col-md-8 remove-pad">
							<div class="table market-table" data-partids="'.$partid_csv.'" style="min-height: 140px;">';

				$html .= 		marketTable($partid_csv, $search_strs, '', $ln, $rev_total);

				$html .= '	</div>';
				$html .= '</div>';
			}
			$counter++;
		}

		if(empty($results)) {
			$html .= '	</div>';
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	//Generates and toggles information for the market tables
	function marketTable($partid_csv, $search_strs, $type = '', $ln, $rev_total = 0, $start = '', $end = ''){
		$html = '';
		$partid = reset(explode(',', $partid_csv));

		if($type == 'repairs') {
			$first_col = format_market($partid_csv,'in_repair',$search_strs, $start, $end);
			$second_col = format_market($partid_csv,'repairs_quoted',$search_strs, $start, $end);
			$third_col = format_market($partid_csv,'repair_sources',$search_strs, $start, $end);
			$fourth_col = format_market($partid_csv,'repairs_completed',$search_strs, $start, $end);
			$summar_col = format_market($partid_csv,'sales_summary',$search_strs, $start, $end);
		} else {
			$first_col = '<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Supply Results" data-type="supply">Supply <i class="fa fa-window-restore"></i></a> <a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="force re-download"><i class="fa fa-download"></i></a>
			<div class="market-results" id="'.$ln.'-'.$partid.'" data-ln="'.$ln.'" data-type="supply"></div>
			<div class="btn-group btn-resultsmode action-items pull-right">
				<button class="btn btn-primary btn-xs" type="button" data-results="0" data-toggle="tooltip" data-placement="top" title="all market results"><i class="fa fa-globe"></i></button>
				<button class="btn btn-default btn-xs" type="button" data-results="1" data-toggle="tooltip" data-placement="top" title="priced results"><i class="fa fa-dollar"></i></button>
				<button class="btn btn-default btn-xs" type="button" data-results="2" data-toggle="tooltip" data-placement="top" title="ghosted inventories"><i class="fa fa-magic"></i></button>
			</div>';
			$second_col = format_market($partid_csv,'purchases',$search_strs, $start, $end);
			$third_col = format_market($partid_csv,'sales',$search_strs, $start, $end);
			$fourth_col = '<a href="javascript:void(0);" class="market-title modal-results" data-target="marketModal" data-title="Demand Results" data-type="demand">Demand <i class="fa fa-window-restore"></i></a> <a href="javascript:void(0);" class="market-download" data-toggle="tooltip" data-placement="top" title="force re-download"><i class="fa fa-download"></i></a>
			<div class="market-results" id="'.$ln.'-'.$partid.'" data-ln="'.$ln.'" data-type="demand"></div>';
			$summar_col = format_market($partid_csv,'repairs',$search_strs, $start, $end);
		}

		$html .= '<div class="col-sm-2 bg-availability">
			'.$first_col.'
		</div>

		<div class="col-sm-2 bg-purchases">
			'.$second_col.'
		</div>

		<div class="col-sm-2 bg-sales">
			'.$third_col.'
		</div>

		<div class="col-sm-2 bg-demand">
			'.$fourth_col.'
		</div>

		<div class="col-sm-2 bg-repairs">
			'.$summar_col.'
		</div>
		<div class="col-sm-2 slider-box" style="height: 100%;">
			<br>
			<div class="row price">
				<div class="col-sm-3">
					<span class="seller_qty" style="display: none;"></span>
				</div>
				<div class="col-sm-1 remove-pad"><span class="seller_x" style="display: none;">x</span></div>
				<div class="col-sm-4 remove-pad">
					<span class="seller_price" style="display: none;"></span>
				</div>
				<div class="col-sm-4 remove-pad">
					<span class="total_price_text" data-total="'.$rev_total.'">'.format_price($rev_total).'</span>
				</div>
			</div>
			<br/>

			<div class="row bid_inputs" style="display: none;">
				<div class="col-sm-3 remove-pad">
					<input type="text" name="bid_qty['.$ln.']" data-type="bid_qty" class="form-control input-xxs bid-input text-center" value="" placeholder="Qty">
				</div>
				<div class="col-sm-1 remove-pad">x</div>
				<div class="col-sm-4 remove-pad">
					<input type="text" name="bid_price['.$ln.']" data-type="bid_price" class="form-control input-xxs bid-input text-center" value="" placeholder="Price">
				</div>
				<div class="col-sm-4 remove-pad">
					<span class="buy_text" data-buy_total="0">
						$0.00
					<span>
				</div>
			</div>
		</div>';	
			
		return $html;
	}

	$results = htmlCompiler($partid_csv, $search_strs, $type, $ln, $market_table, $equipment_filter, $start, $end, $listid, $sort);

	//print_r($results); exit;
	echo json_encode($results);
	exit;

