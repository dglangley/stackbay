<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getDefaultEmail.php';

	$EMBEDDED_SOURCES = array('reply'=>'../img/reply.png');// used for embedding images in email

	function format_favorites($favs,$N,$email_format=true) {
		global $EMBEDDED_SOURCES;

		//Establish the initial declaration of the html
		$fav_str = '

		<div class="table-responsive">
		<table class="table table-striped table-condensed table-fav" style="max-width:1024px">
			<thead>
			<tr>
				<th class="col-xs-3">Description</th>
				<th class="col-xs-8">Supply</th>
				<th class="col-xs-1">User</th>
			</tr>
			</thead>
		';

		$bgc = array('#ffffff','#f5f5f5');
		$rownum = 0;

		foreach ($favs as $partid => $r) {
			//Reset the day counter
			$i = 0;
			$rownum = !$rownum;

			// no new result is a flag for today's results
			$no_today_result = false;
			// no old result is a flag for previous day's results
			$no_past_result = false;
    
			$supply = array();

			// take the supply results
			foreach($r['supply'] as $results_date => $results){
				//We don't care about any results beyond the last day's results (monday from tuesday, or friday from monday)
				if ($i > 1){ break; }

				// if results are empty (nothing at all), are we on today's result ("new"), or a previous day? ("old")
				if (empty($results)){
					if ($i == 0){//today
						$no_today_result = true;
					} else {
						$no_past_result = true;
					}
				}

				//If both flags are already tripped, exit out of the loop
				if($no_today_result AND $no_past_result){ break; }  

				//Each day, go through the individual returned values
				foreach($results as &$item){
					//If the results don't return anything, mark the comparative value 
					//for each company to zero for either the old or the new and 

					//Otherwise, make a note of this particular result's company
					$company = $item['company'];
					$companyid = $item['cid'];

					//If the company doesn't already have an entry for this item, make a
					//new line item for the company
					if(!array_key_exists($companyid,$supply)){
						$supply[$companyid] = array(
							'new' => '',
							'old' => '',
							'chg' => '',
							'price' => '',
							'source' => '',
							'company' => $company,
						);
					}

					//If today has no result, set value of "new" to 0
					if($no_today_result){
						$supply[$companyid]['new'] = 0;
					}

					//same if there is no past result
					if($no_past_result){
						$supply[$companyid]['old'] = 0;
					}

					//If there is a value, then save the company values by increasing the quantity
					if($i == 0){
						$supply[$companyid]['new'] += $item['qty'];
					} else {
						$supply[$companyid]['old'] += $item['qty'];
					}
					$supply[$companyid]['price'] = $item['price'];
					$supply[$companyid]['source'] = $item['sources'];
				}
				$i++;
			}

			//Calculate the change. If there is no change, mark the flag
			//This covers the case where if the value of both old AND new is matched, the
			//loop will not output (earlier just covered the case that both were null)
			$any_delta = false;
			foreach($supply as $options => &$co){
				$co['chg'] = $co['new'] - $co['old'];
				if ($co['chg'] != 0) { $any_delta = true; }
			}

//			if ($k>5) { break; }

			// on Friday, disregard deltas or empty availabilities; all other days of the week, it matters
			if ($N<>5) {
				if (!$any_delta){ continue; }

				//If there is still no entry into the availability script, skip.
				if(empty($supply)){continue;}
			}

			//Start the new line
			$fav_str .= '
			<tr style="background-color:'.$bgc[$rownum].'">
				<td>'.$r['part'].'<br/>'.$r['heci'].'</td>
				<td>
			';

			//For each item of availible stock by quantity, print the value
			foreach($supply as $companyid => $ava){
				$delta_dir = '&nbsp;&nbsp;&nbsp;&nbsp;';
				$delta_cls = '';
				if ($ava['chg']>0) {
					$delta_dir = '&#9650;';
					$delta_cls = ' pos';
				} else if ($ava['chg']<0) {
					$delta_dir = '&#9660;';
					$delta_cls = ' neg';
				}
				$sources = '';
				foreach ($ava['source'] as $sc) {
					$img_url = '../img/'.strtolower($sc).'.png';
					if ($email_format) {
						$sources .= '<img src="cid:'.$sc.'" style="width:11px"/>';
					} else {
						$sources .= '<img src="'.$img_url.'" style="width:11px"/>';
					}
					if (! isset($EMBEDDED_SOURCES[$sc])) { $EMBEDDED_SOURCES[$sc] = $img_url; }
				}

				$company_email = getDefaultEmail($companyid);
				$mail_lk = '';
				if ($company_email) {
					$mail_lk = '<a href="mailto:'.$company_email[0].'?subject='.$r['part'].'&body=Please quote:<br/><br/>'.$r['part'].'">';
					if ($email_format) {
						$mail_lk .= '<img src="cid:reply" style="width:11px" /></a>';
					} else {
						$mail_lk .= '<img src="../img/reply.png" style="width:11px" /></a>';
					}
				}

				$fav_str .= '
				<div class="row">
					<div class="col-xs-2" style="display:inline-block; min-width:80px; max-width:80px">
						<span class="delta" style="width:20px">'.($ava['new'] ? $ava['new'] : '&nbsp;').'</span>
						<span class="delta'.$delta_cls.'" style="width:20px">'.$delta_dir.'</span>
						<span class="delta" style="width:20px">'.($ava['old'] ? $ava['old'] : '&nbsp;').'</span>
					</div>
					<div class="col-xs-8" style="display:inline-block">'.$ava['company'].' &nbsp; '.$sources.' &nbsp; &nbsp; '.$mail_lk.'</div>
					<div class="col-xs-2" style="display:inline-block">'.$ava['price'].'</div>
				</div>
				';
			}

			$fav_str .= '
				</td>
				<td>'.$r['user'].'</td>
			</tr>
			';
		}

		$fav_str .= '</table></div>';

		return ($fav_str);
	}
?>
