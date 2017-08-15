<?php
	include_once 'dbconnect.php';
	include_once 'pipe.php';
	include_once 'format_date.php';
	include_once 'format_price.php';
	include_once 'getPipeIds.php';
	include_once 'getPipeQty.php';
	include_once 'getRecords.php';
	include_once 'getShelflife.php';
	include_once 'getCost.php';
	include_once 'array_stristr.php';
	include_once 'calcQuarters.php';
	include_once 'format_price.php';

	// frequent companies within scope of demand and supply
	$FREQS = array('demand'=>array(),'supply'=>array());

	$freq_min = 2;
	$query = "SELECT * FROM company_activity WHERE demand_volume > $freq_min OR supply_volume > $freq_min; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		if ($r['demand_volume']>$freq_min) {
			$FREQS['demand'][$r['companyid']] = $r['demand_volume'];
		}
		if ($r['supply_volume']>$freq_min) {
			$FREQS['supply'][$r['companyid']] = $r['supply_volume'];
		}
	}

	$SALES = false;
	$DEMAND = false;
	function format_market($partid_csv,$market_table,$search_strs, $start, $end) {
		global $FREQS,$SALES,$DEMAND;

		$last_date = '';
		$last_sum = '';
		$market_str = '';
		$dated_qty = 0;
		$monthly_totals = array();

		// re-use data within global scope, if previously set from use with filters
		// if ($market_table=='sales' AND $SALES!==false) {
		// 	$results = $SALES;
		// } else if ($market_table=='demand' AND $DEMAND!==false) {
		// 	$results = $DEMAND;
		// } else {
		$results = getRecords($search_strs,$partid_csv,'csv',$market_table, 0,$start, $end);
		// }

		$num_results = count($results);
		// number of detailed results instead of month-groups, which is normally only results within the past month
		// unless the only records available are outdated, in which case it would be good to show the first couple in detail
		$num_detailed = 0;

		$summary_past = $GLOBALS['summary_past'];

		$grouped = array();
		$last_date = '';
		foreach ($results as $r) {

			//print_r($r);
			$datetime = substr($r['datetime'],0,10);
			if ($num_detailed==0 OR ($market_table<>'demand' AND strpos($market_table, 'repair')!== false) OR $datetime>=$summary_past) {
				// append the companyid to the date to avoid grouping results on the same date
				// because otherwise we end up with averaged prices when we want to see breakouts instead
				$group_date = substr($r['datetime'],0,10).'-'.$r['cid'];

				$last_date = $group_date;
				if ($num_detailed==0 AND $datetime<$summary_past) {
					$summary_past = substr($datetime,0,7).'-01';
					$GLOBALS['summary_past'] = $summary_past;
				}
				$num_detailed++;
			} else {
				// update date baseline for summarize_date() below to show just month rather than full date

				$group_date = substr($r['datetime'],0,7);
			}
			if (! isset($grouped[$group_date])) {
				$grouped[$group_date] = array('count'=>0,'sum_qty'=>0,'sum_price'=>0,'total_qty'=>0,'datetime'=>'','companies'=>array(),'status'=>'');
				if ($market_table=='purchases' OR $market_table=='sales' OR strpos($market_table, 'repair')!== false) { $grouped[$group_date]['order_num'] = ''; }
			}

			$fprice = format_price($r['price'],true,'',true);
			$grouped[$group_date]['count']++;
			if ($fprice>0) {
				$grouped[$group_date]['sum_qty'] += $r['qty'];
				$grouped[$group_date]['sum_price'] += ($fprice*$r['qty']);
			}
			$grouped[$group_date]['datetime'] = $r['datetime'];
			$grouped[$group_date]['total_qty'] += $r['qty'];

			if ($r['cid']>0 AND ! isset($grouped[$group_date]['companies'][$r['cid']]) AND array_search($r['name'],$grouped[$group_date]['companies'])===false) {
				$grouped[$group_date]['companies'][$r['cid']] = $r['name'];
			}
			if ($market_table=='purchases' OR $market_table=='sales' OR strpos($market_table, 'repair')!== false) { $grouped[$group_date]['order_num'] = $r['order_num']; }
			if ($r['status']=='Void') { $grouped[$group_date]['status'] = 'Void'; }
		}
		ksort($grouped);

		$cls1 = '';
		$cls2 = '';
		foreach ($grouped as $order_date => $r) {
			// because we're grouping dates above with suffixed companyid's, we need to shorten them back to just the date
			$order_date = substr($order_date,0,10);

			// summarized date for heading line
			$sum_date = summarize_date($r['datetime']);

			// add group heading (date with summarized qty)
			if ($last_sum AND $sum_date<>$last_sum) {
				$market_str = $cls1.format_dateTitle($last_date,$dated_qty).$cls2.$market_str;
				$dated_qty = 0;//reset for next date
			}

			$cls1 = '';
			$cls2 = '';
			$summary_form = false;
			if ($r['datetime']<$GLOBALS['summary_lastyear']) {
				$cls1 = '<span class="archives">';
				$cls2 = '</span>';
				$summary_form = true;
			} else if ($r['datetime']<$summary_past) {
				$cls1 = '<span class="summary">';
				$cls2 = '</span>';
				$summary_form = true;
			}

			if (strlen($order_date)==10 AND strlen($last_date)==7) {
				$market_str = '<HR>'.$market_str;
			}

			$last_date = $order_date;
			$last_sum = $sum_date;
			$dated_qty += $r['total_qty'];

			$companies = '';
			$cid = 0;
			foreach ($r['companies'] as $cid => $name) {
				if ((($market_table=='demand' OR $market_table=='sales' OR $market_table=='sales_summary' OR $market_table=='in_repair' OR $market_table=='repairs' OR $market_table=='repairs_completed') AND (! $summary_form OR $r['count']==1 OR isset($FREQS['demand'][$cid])))
					OR (($market_table=='purchases') AND (! $summary_form OR $r['count']==1 OR isset($FREQS['supply'][$cid]))))
				{
					if ($companies) { $companies .= '<br> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;'; }
					$companies .= '<a href="profile.php?companyid='.$cid.'" class="market-company">'.$name.'</a>';
				}
			}
			$price_str = '';
			if (($r['sum_qty']>0) || strpos($market_table, 'repair') !== false) {
				$fprice = format_price(format_price(round($r['sum_price']/$r['sum_qty'])),false);
				if ($market_table=='purchases') {
					$price_str = '<a href="/PO'.$r['order_num'].'">'.$fprice.'</a>';
				} else if ($market_table=='sales') {
					$price_str = '<a href="/SO'.$r['order_num'].'">'.$fprice.'</a>';
				} else if (strpos($market_table, 'repair') !== false) {
					$price_str = '<a href="/RO'.$r['order_num'].'">'.($fprice ? $fprice : '0.00').'</a>';
				} else if($market_table!='sales_summary') {
					$price_str = '<input type="text" class="form-control input-xxs market-price" value="'.$fprice.'" data-date="" data-cid="">';
				}
			}

			$cls_add = '';
			if ($r['status']=='Void' OR $r['total_qty']==0) { $cls_add = ' strikeout'; }
			$line_str = '<div class="market-data'.$cls_add.' market-company-'.$cid.'">';
			if (strlen($order_date)==7) {
				$line_str .= '<span class="pa">'.$r['count'].'x</span> ';
			}
			$line_str .= '<span class="pa">'.round($r['total_qty']/$r['count']).'</span> &nbsp; '.
				$companies.' <span class="pa">'.$price_str.'</span></div>';

			// append to column string
			$market_str = $cls1.$line_str.$cls2.$market_str;
		}

		// append last remaining data
		if ($num_results>0) {
			$market_str = $cls1.format_dateTitle($order_date,$dated_qty).$cls2.$market_str;
		}

		if ($market_str) {
			$market_str = '<a href="javascript:void(0);" class="market-title modal-results" '.($market_table != 'repairs' && $market_table != 'sales_summary' ? 'data-target="marketModal"' : '').' data-title="'.ucwords(str_replace('_', ' ', $market_table)).'" data-type="'.$market_table.'" data-partid_csv="'.$partid_csv.'" data-type="'.$market_table.'">'.ucwords(str_replace('_', ' ', $market_table)) . ($market_table != 'repairs' && $market_table != 'sales_summary' ? ' <i class="fa fa-window-restore"></i>' : '') . (($market_table == 'repairs' OR $market_table == 'sales_summary') ? ' <i class="fa fa-plus" aria-hidden="true"></i>':'').'</a><div class="market-body">'.$market_str.'</i>
</div>';
		} else {
			$market_str = '<span class="info">- No '.ucwords(str_replace('_', ' ', $market_table)).' -</span>';
		}

		return ($market_str);
	}
