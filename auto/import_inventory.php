<?php
	set_time_limit(0);
	ini_set('memory_limit', '5000M');
	
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';

	//Temp array to hold Brian's data
	$inventory = array();

	//This array holds all the part items that contain no heci to be used as an array intersect explode (prevents the use of a like %% query)
	$noHeci = array();

	//Hold inventory alias
	$inventoryalias = array();
	//$aliasExploded = array();
	
	//Query Grab inventory data	//For now we will ignore items without a clei, but will later incoporate the anomalies
	$query = "SELECT id, clei, manufacturer_id_id, part_number, short_description, heci FROM inventory_inventory WHERE heci IS NULL AND clei IS NULL ORDER BY id ASC LIMIT 100; ";

	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$inventory[] = $r;
	}

	//This query grabs all the null hecis
	$query = "SELECT part FROM parts WHERE (heci IS NULL OR heci = ''); ";
	$result = qdb($query) OR die(qe().'<BR>'.$query);
	//Checking if the result is 1 otherwise multiple items found
	while ($r = mysqli_fetch_assoc($result)) {
		$noHeci[] = $r['part'];
	}

	//explode spaces into more parts
	foreach($noHeci as $key => $str) {
		$noHeci[$key] = explode(' ', $str);
	}

	$noHeci = flatten($noHeci);

	foreach($inventory as $key => $value) {
		
		//trim the entire array
		$value = array_map('trim', $value);

		//Query Grab inventory alias to specific inventory ID to get all the aliases that associate with this item
		//Only grabbing the part number and pre trim it
		$query = "SELECT part_number FROM inventory_inventoryalias WHERE inventory_id = ".prep($value['id'])."; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$inventoryalias[] = $r['part_number'];
		}

		foreach($inventoryalias as $key => $str) {
			$inventoryalias[$key] = explode(' ', $str);
		}

		$inventoryalias = flatten($inventoryalias);
		
		//Remove all duplicates within the array after all is said and done
		$inventoryalias = array_unique($inventoryalias, SORT_REGULAR);

		//trim the entire array
		$inventoryalias = array_map('trim', $inventoryalias);

		$confirmedAlias = array();
		
		$clei = $value['clei'];
		$heci = $value['heci'];
		$part_number = $value['part_number'];
		$desc = $value['short_description'];
		
		//Exists in our database or not
		$existing = false;
		$aliasSwap = false;
		
		//This variable is used to see if we need to edit it 'false' or no edit is required 'true'
		$no_edit = false;
		$partid = 0;
		$check = '';
		$part = '';
		$manfid = null;
		
		//Store an alias if it is prefound already
		$aliasSwap_part = '';
		
		//Special case for when no HECI or CLEI exists that we will create a part with the aliases preappended
		$formatRequired = false;
		
		//First one checks if Clei Exists 2nd part checks and makes sure the clei is valid (If Clei is invalid then check heci)
		if($clei && !is_numeric($clei) && strlen ($clei) == 10) {
			//Make clei the 10 digit the new heci
			$heci = $clei;
			
			//Query to check if the HECI already exists in the database
			$query = "SELECT id, part FROM parts WHERE LOWER(heci) = ".prep(strtolower($heci))."; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			//Checking if the result is 1 otherwise multiple items found
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$partid = $r['id'];
				$part = $r['part'];
				//identify part as existing
				$existing = true;
			}
			
			$check = 'Valid CLEI';
		//Checks if heci is set and makes sure the heci is actually a valid heci else push to next if invalid
		} else if($heci && !is_numeric($heci) && strlen ($heci) == 7) {
			//Add in and make the Heci a 10 digit
			$heci .= 'VTL';
			$check = 'Valid HECI';
			
			//Query to check if the HECI already exists in the database
			$query = "SELECT id, part FROM parts WHERE LOWER(heci) = ".prep(strtolower($heci))."; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			//Checking if the result is 1 otherwise multiple items found
			if (mysqli_num_rows($result)==1) {
				$r = mysqli_fetch_assoc($result);
				$partid = $r['id'];
				$part = $r['part'];
				//identify part as existing
				$existing = true;
			} else if (mysqli_num_rows($result)>1) {
				//Deal with this issue later if it ever occurs
				echo '<b>Multiple of same HECI found</b><br>';
			}
			
		//None of the above exists so run the format_part() by David	
		} else {
			$part_number = format_part($part_number);
			$formatRequired = true;

			//Clear previous data
			//$aliasSwap_part = '';

			//Do a direct full name comparison if it is a precreated no HECI multiple part name
			//Somewhat a bypass
			$query = "SELECT id FROM parts WHERE LOWER(part) LIKE '".res(strtolower($part_number))."%'; ";
			$result = qdb($query) OR die(qe().'<BR>'.$query);
			//Checking if the result is 1 otherwise multiple items found
			//If this item exists then nothing needs to be done as this part has no HECI to match aliases
			if (mysqli_num_rows($result)>0) {
				$existing = true;
				$no_edit = true;
			}
			
			//If the bypass fails then attempt to find the part in a non heci part
			//Query to check if the part already exists in the database
			//prevent a massive like query by pre grabbing all common part names with no heci involved
			if(in_array($part_number, $noHeci) || !$no_edit) {
				$query = "SELECT id FROM parts WHERE LOWER(part) LIKE '".res(strtolower($part_number))."%'; ";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
				//Checking if the result is 1 otherwise multiple items found
				//If this item exists then nothing needs to be done as this part has no HECI to match aliases
				if (mysqli_num_rows($result)>0) {
					$existing = true;
					$no_edit = true;
				}
			//This item wasn't found so try and see if any aliases exists in the db
			//The code below is if you want to perform an alias check to see if an alias is actually on the meta level for parts with no HECI (x10)
			} else if(!$no_edit) {
				// foreach($inventoryalias as $alias) {
				// 	if(in_array($part_number, $noHeci)) {
				// 		$query = "SELECT id, part FROM parts WHERE LOWER(part) LIKE '".res(strtolower($alias))."%'; ";
				// 		$result = qdb($query) OR die(qe().'<BR>'.$query);

				// 		if (mysqli_num_rows($result)>0) {
				// 			$existing = true;
				// 			$aliasSwap = true;
				// 			$aliasSwap_part = $r['part'];
				// 			$partid = $r['id'];
				// 		}
				// 	}
				// }
				echo "<b>Exploded part check indicates that the part with no HECI does not Exists in the DB</b><br>";
			}
			
			if($aliasSwap) {
				//Remove the alias name from inventory alias
				if(($key = array_search($aliasSwap_part, $inventoryalias)) !== false) {
				    unset($inventoryalias[$key]);
				}
				
				//Create the part alias string from this item
				$aliasSwap_part .= ' ' . $part_number;
				
				foreach($inventoryalias as $alias) {
					$aliasSwap_part .= ' ' . $alias;
				}
			}
		}
		
		if($existing && !$formatRequired) {
			//Check part_number and see if the name for the part already exists... If not set the partnumber as an alias to the part
			//This is the part number from the new DB
			$explode = explode(' ', $part);
			//Explode will be empty if no ' '
			if(empty($explode)) {
				//Reset as using array[] will just append
				unset($explode);

				$explode[] = $part;
			}

			//This is the part_number for the meta level of Brian's DB
			$brianAlias = explode(' ', $part_number);
			if(empty($brianAlias)) {
				unset($brianAlias);

				$brianAlias[] = $part_number;
			}
			
			//Merge in the alias names too while removing all duplicates
			$brianAlias = array_unique(array_merge($brianAlias,$inventoryalias), SORT_REGULAR);
			
			//Check and store and array elements that do not match as they are alias
			$confirmedAlias = array_diff($brianAlias, $explode);
			//Part Exists in the part name
			if(empty($confirmedAlias)) {
				$no_edit = true;
			}
		//Item does not exist and is an item with no heci or clei so grab all aliases and create a part name
		} else if (!$existing && $formatRequired) {
			$brianAlias = explode(' ', $part_number);
			
			//Merge in the alias names too while removing all duplicates
			//$brianAlias = array_unique(array_merge($brianAlias,$inventoryalias), SORT_REGULAR);
			
			//$confirmedAlias = $brianAlias;
			
			$part_number = '';
			
			foreach($inventoryalias as $newPart) {
				//Statement just makes sure that we dont add a space in the beginning or we can just trim it later
				$part_number .= (empty($part_number) ? $newPart : ' ' . $newPart);
			}
		}
		
		if($check)
			echo "<b>Part $check </b><br>";
		echo "<b>Part ".($existing ? 'Exist' : 'Does Not Exist')." in our database</b><br>";
		echo '<b>Part Number</b>: ' . $part_number . ' <b>HECI</b>: ' . $heci . '<br>';
		
		//This signifies that the part exists but the the part name does not exist so we need to alias
		if($existing && !$no_edit && !$aliasSwap) {
			//add a space then add the alias as the new part name
			foreach($confirmedAlias as $alias) {
				echo '<b>Current Part: </b>'.$part.' <b>Missing Alias:</b> '.$alias.'<br>';
				$part .= ' ' . $alias;
			}
			
			mergePart($part, $partid, $existing);
			echo '<b>New Part Number: </b> '.$part.'<br>';
			echo '<b>ALIAS DOES NOT EXIST, ADDING ALIAS</b><br>';
		
		//Main item was not found but the alias exists in the database (Just update the partname with the alias concatenated name)
		} else if($aliasSwap) {
			echo '<b>ALIAS Swap Occured!</b><br>';
			//mergePart($aliasSwap_part, $partid, $existing);
		//Item does not exist at all in our database so lets add the new part	
		} else if (!$existing) {
			//Convert to our manf id as a last resort from brian's database as we do not have this information
			$manfid = dbTranslateManf($value['manufacturer_id_id']);
			mergePart($part_number, $partid, $existing, $heci, $desc, $manfid);
			echo '<b>ITEM DOES NOT EXIST ADDED TO DB</b><br>';
			echo '<b>Adding Part: </b>'.$part_number.'<br>';
		//This is just here to signify non of the above conditions are met so do nothing, most likely because part exists and the alias/part name already exists in the database	
		} else {
			echo '<b>NO CHANGES MADE DUE TO EXISTING</b><br>';
		}
		
		echo '<br><br>';
		
		
		//empty the alias for this inventory id to make room for the next inventory id item aliases
		unset($inventoryalias);
		unset($confirmedAlias);
		unset($aliasExploded);
		
		//Index the part indexer(partid) (DO NOT USE IF NOTHING HAS BEEN DONE TO THE PART)
	}
	
	//Function to add in or update parts from Brian's inventory table to our parts table
	function mergePart($part, $id = 0, $existing,$heci = null, $description = null, $manfid = null) {
		//Query to update the alias if the part exists
		if($existing) {
			$query = "UPDATE parts SET part = ".prep($part)." WHERE id = ".prep($id).";";
			$result = qdb($query) OR die(qe().' '.$query);
		//Query to add a new part with HECI, Description	
		} else if(!$existing) {
			$query = "INSERT parts (part, heci, manfid, description) VALUES (";
			$query .= prep($part).", ";
			$query .= prep($heci).", ";
			$query .= prep($manfid).", ";
			$query .= prep($description).");";
			$result = qdb($query) OR die(qe().' '.$query);
		}
	}

	function flatten($array) {
	    $return = array();
	    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
	    return $return;
	}
    
    function dbTranslateManf($manfidog) {

		$manfid = 0;
		$manf = '';
	
		//This query grabs the manfid name
		$query = "SELECT * FROM inventory_manufacturer WHERE id = ".prep($manfidog)."; ";
		$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$manf = $r['name'];
		}
	
		//Attempt to get the manf id from our system
		$query = "SELECT id FROM manfs WHERE LOWER(name) = ".prep(strtolower($manf))."; ";
		$result = qdb($query) OR die($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$manfid = $r['id'];
		}
	
		return $manfid;
	}
?>
