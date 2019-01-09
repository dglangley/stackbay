<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getSupply.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/keywords.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';

	function listFavorites($fav_partids,$attempt=0,$max_lines=0) {
		$all_partids = array();//used to avoid duplicates
		$favs = array();

		$i = 0;
		foreach ($fav_partids as $partid => $users) {
			$i++;
			if ($max_lines>0 AND $i>$max_lines) { break; }

			//don't hammer the sites too hard, I think the barrage is kicking out our PS session
			if ($i>1 AND $attempt) { sleep(4); }

			// if this has been already shown under another grouping, don't use it
			if (isset($all_partids[$partid])) { continue; }
			$all_partids[$partid] = true;

			$r = hecidb($partid,'id')[$partid];

			$user = '';
			foreach ($users as $userid => $user_dt) {
				$user_str = explode(' ',getUser($userid));
				$user = $user_str[0].' '.substr($user_str[1],0,1);
				break;// all we need is the first
			}

			//If the part has a HECI, do the following
			if ($r['heci']) {
				$related = hecidb($r['heci7']);
			} else {
				$related = hecidb(format_part($r['primary_part']));
			}

			// add all partids related to this one partid
			$partids = array();
			foreach ($related as $id => $H) {
				$partids[$id] = $id;
				$all_partids[$id] = true;
			}

			//Take in the list of partids from the initial search
			$supply = getSupply($partids,$attempt);    

			// build array for processing in format_favorites()
			$favs[$partid] = array(
				'supply' => $supply['results'],
				'part' => $r['primary_part'],
				'heci' => $r['heci7'],
				'user' => $user,
			);
		}

		return ($favs);
	}
?>
