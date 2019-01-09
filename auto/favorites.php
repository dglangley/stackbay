<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSupply.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getSubEmail.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/send_gmail.php';

	$DEBUG = 0;

	$FAVS = true;
	$senderid = 5;

	$recipients = getSubEmail('favorites');

	//gets added globally to email header within format_email() (inside send_gmail)
	$EMAIL_CSS = file_get_contents($_SERVER["ROOT_DIR"].'/css/favorites.css');

	//Establish the initial declaration of the html
	$email_str = '
		Hey there! I found the following changes to market supply of favorited items since last time they were searched!<BR><BR>
		<div class="table-responsive">
		<table class="table table-striped table-condensed table-fav">
			<thead>
			<tr>
				<th class="col-xs-3">Description</th>
				<th class="col-xs-8">Supply</th>
				<th class="col-xs-1">User</th>
			</tr>
			</thead>
	';

	$test_mode = 0;

	$attempt = 1;//this forces a download from remote sites; sets to 0 for test/static mode
	if ($test_mode) { $attempt = 0; }

	$all_partids = array();//used to avoid duplicates
	$all_sources = array();// used for embedding images in email

	$query = "SELECT f.userid, f.partid, p.id, p.part, p.heci ";
	$query .= "FROM favorites f, parts p ";
	$query .= "WHERE f.partid = p.id ";
	$query .= "GROUP BY f.partid ";
	$query .= "ORDER BY p.part, p.heci ";
	if ($test_mode) {
		$query .= "LIMIT 0,5 ";
	}
	$query .= "; ";
	$result = qdb($query);
	foreach ($result as $k => $r) {
		//don't hammer the sites too hard, I think the barrage is kicking out our PS session
		if ($k>0 AND ! $test_mode) { sleep(4); }

		// if this has been already shown under another grouping, don't use it
		if (isset($all_partids[$r['partid']])) { continue; }
		$all_partids[$r['partid']] = true;

		$partids = array();

		//Pull the heci and/or the Part ID
		$partid = $r['partid'];
		$part = explode(' ',$r['part'])[0];
		$heci = $r['heci'];

		$user_str = explode(' ',getUser($r['userid']));
		$user = $user_str[0].' '.substr($user_str[1],0,1);
    
		//Prepare the output array to seperate out the output from the processing
		$output = array(
			'pname' => $part,
			'heci' => substr($heci,0,7),
			'user' => $user,
			'supply' => array(),
		);

		//If the part has a HECI, do the following
		if ($heci) {
			$related = hecidb(substr($heci,0,7));
		} else {
			$related = hecidb($part);
		}

		// add all partids related to this one partid
		foreach ($related as $id => $H) {
			$partids[$id] = $id;
			$all_partids[$id] = true;
		}

		// no new result is a flag for today's results
		$no_today_result = false;
		// no old result is a flag for previous day's results
		$no_past_result = false;

		//Take in the list of partids from the initial search
		$supply = getSupply($partids,$attempt);    

		//added 2/10/17 by david so that we can show entire list (not just delta) on Fridays
		$N = date("N");
    
		//Reset the day counter
		$i = 0;

		// take the supply results
		foreach($supply['results'] as $results_date => $results){
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

				//If the company doesn't already have an entry for this item, make a
				//new line item for the company
				if(!array_key_exists($company,$output['supply'])){
					$output['supply'][$company] = array(
						'new' => '',
						'old' => '',
						'chg' => '',
						'price' => '',
						'source' => '',
					);
				}

				//If today has no result, set value of "new" to 0
				if($no_today_result){
					$output['supply'][$company]['new'] = 0;
				}

				//same if there is no past result
				if($no_past_result){
					$output['supply'][$company]['old'] = 0;
				}

				//If there is a value, then save the company values by increasing the quantity
				if($i == 0){
					$output['supply'][$company]['new'] += $item['qty'];
				} else {
					$output['supply'][$company]['old'] += $item['qty'];
				}
				$output['supply'][$company]['price'] = $item['price'];
				$output['supply'][$company]['source'] = $item['sources'];
			}
			$i++;
		}

		//Calculate the change. If there is no change, mark the flag
		//This covers the case where if the value of both old AND new is matched, the
		//loop will not output (earlier just covered the case that both were null)
		$any_delta = false;
		foreach($output['supply'] as $options => &$co){
			$co['chg'] = $co['new'] - $co['old'];
			if ($co['chg'] != 0) { $any_delta = true; }
		}

//		if ($k>5) { break; }

		// on Friday, disregard deltas or empty availabilities; all other days of the week, it matters
		if ($N<>5) {
			if (!$any_delta){ continue; }

			//If there is still no entry into the availability script, skip.
			if(empty($output['supply'])){continue;}
		}

		//Start the new line
		$email_str .= '
			<tr>
				<td>'.$output['pname'].'<br/>'.$output['heci'].'</td>
				<td>
		';

		//For each item of availible stock by quantity, print the value
		foreach($output['supply'] as $company => $ava){
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
				$sources .= '<img src="cid:'.$sc.'" style="width:11px"></img>';
				if (! isset($all_sources[$sc])) {
					$all_sources[$sc] = $img_url;
				}
			}

			$email_str .= '
				<div class="row">
					<div class="col-xs-2">
						'.($ava['new'] ? $ava['new'] : '&nbsp;').'
						<div class="delta'.$delta_cls.'">'.$delta_dir.'</div>
						'.($ava['old'] ? $ava['old'] : '&nbsp;').'
					</div>
					<div class="col-xs-8">'.$company.' &nbsp; '.$sources.'</div>
					<div class="col-xs-2">'.$ava['price'].'</div>
				</div>
			';
		}

		$email_str .= '
				</td>
				<td>'.$output['user'].'</td>
			</tr>
		';
	}

	$email_str .= '</table></div>';

	$sbj = 'Favorites Daily '.date("M j, Y");

	if ($DEBUG OR $test_mode) { echo format_email($sbj,$email_str); exit; }

	// initializes gmail API session
	setGoogleAccessToken($senderid);

	$send_success = send_gmail($email_str,$sbj,$recipients,'','','','',$all_sources);
	if ($send_success) {
		echo json_encode(array('message'=>'Success'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
	}
?>
