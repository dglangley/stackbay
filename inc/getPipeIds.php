<?php
	$PIPE_IDS = array();
	$NOTES = array();
	$avg_cost = '';
	function getPipeIds($search_str,$search_by='') {
		global $PIPE_IDS,$avg_cost,$NOTES;

		// search strings are passed in as space-separated due to our current db using aliases this way (8/2/16)
		$searches = explode(' ',$search_str);

		$pipe_ids = array();//all ids for the search string passed in

		foreach ($searches as $search) {
			// strip out non-alphanumeric chars, convert to uppercase and then trim it to be sure no spaces are wrapping it
			$search = trim(strtoupper(preg_replace('/[^[:alnum:]]+/','',$search)));

			$search_upper = strtoupper($search);
			// only strings over 2-chars in length
			if (strlen($search)<=2 OR $search_upper=='REV' OR $search_upper=='ISS' OR $search_upper=='REL') { continue; }

			$search_by = strtolower($search_by);

			// key each search string within global array so we don't duplicate lookups; also, call the search type 'part'
			// if it doesn't qualify as a heci in any way (not 7-digits, not 10-digits, or all-numeric)
			$keysearch = $search;
			if ($search_by) { $keysearch .= '.'.$search_by; }
			else if ((strlen($search)<>7 AND strlen($search)<>10) OR is_numeric($search)) { $keysearch .= '.part'; }

			$ids = array();
			if (isset($PIPE_IDS[$keysearch])) {
				foreach ($PIPE_IDS[$keysearch] as $id => $r) {
					$pipe_ids[$id] = $r;
				}
			} else {
				$results = array();
				$query = "SELECT id, avg_cost, notes, part_of notes2, clei heci FROM inventory_inventory WHERE (";
				$subquery = "";
				if ($search_by<>'heci') {
					$subquery .= "clean_part_number LIKE '".res($search,'PIPE')."%' ";
				}
				if ((strlen($search)==7 OR strlen($search)==10) AND $search_by<>'part' AND ! is_numeric($search)) {
					if ($subquery) { $subquery .= "OR "; }
					if ($search_by=='heci' AND strlen($search)==10) {
						$subquery .= "clei = '".res($search,'PIPE')."' ";
					} else {
						$subquery .= "clei LIKE '".res(substr($search,0,7),'PIPE')."%' OR heci LIKE '".res(substr($search,0,7),'PIPE')."%' ";
					}
				}
				if (! $subquery) { $subquery .= "1 = 2 "; }
				$query .= $subquery.") LIMIT 0,20; ";

				$result = qdb($query,'PIPE');// OR die(qe('PIPE'));
				if (mysqli_num_rows($result)>0) {
					while ($r = mysqli_fetch_assoc($result)) {
						$results[] = $r;
					}
				}

				// get ids from aliases
				if ($search_by<>'heci') {
					$query = "SELECT inventory_inventory.id, avg_cost, notes, part_of notes2, clei heci ";
					$query .= "FROM inventory_inventory, inventory_inventoryalias ";
					$query .= "WHERE inventory_inventoryalias.clean_part_number LIKE '".res($search,'PIPE')."%' ";
					$query .= "AND inventory_inventory.id = inventory_inventoryalias.inventory_id LIMIT 0,20; ";
					$result = qdb($query,'PIPE');// OR die(qe('PIPE'));
					if (mysqli_num_rows($result)>0) {
						while ($r = mysqli_fetch_assoc($result)) {
							$results[] = $r;
						}
					}
				}

				foreach ($results as $r) {
					if ($r['avg_cost']>0) { $avg_cost = $r['avg_cost']; }
					$ids[$r['id']] = $r;//ids for just this sub-divided search str
					$pipe_ids[$r['id']] = $r;//ids for all results of exploded search string

					$notes = trim(str_ireplace('Bot Generated','',$r['notes']));
					if ($notes AND $r['notes2']) { $notes .= chr(10).$r['notes2']; }
					$NOTES[$r['id']] = $notes;//trim(str_ireplace('Bot Generated','',$r['notes']));
				}
				$PIPE_IDS[$keysearch] = $ids;
			}
		}

		return ($pipe_ids);
	}
?>
