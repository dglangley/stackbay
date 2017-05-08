<?php
	set_time_limit(0);
	ini_set('memory_limit', '5000M');
exit;
	
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_part.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPartId.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/setPart.php';

	//Temp array to hold Brian's data
	$inventory = array();

	//This array holds all the part items that contain no heci to be used as an array intersect explode (prevents the use of a like %% query)
	// $noHeci = array();
	$confirmedAlias = array();

	//Hold inventory alias
	$inventoryalias = array();
	//$aliasExploded = array();
	
	//Query Grab inventory data	//For now we will ignore items without a clei, but will later incoporate the anomalies
	$query = "SELECT id, clei, manufacturer_id_id, part_number, short_description, heci FROM inventory_inventory;";

	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$inventory[] = $r;
	}

	foreach($inventory as $key => $value) {
		
		//trim the entire array
		$value = array_map('trim', $value);

		//Set the values we need
		$clei = $value['clei'];
		$heci = $value['heci'];
		$part_number = $value['part_number'];
		$desc = $value['short_description'];
		$manfid = $value['manufacturer_id_id'];
		
		$partid = 0;
		$part = '';
		$manfid = null;
		
		//Special case for when no HECI or CLEI exists that we will create a part with the aliases preappended
		$noHeci = false;
		//This variable is used to see if we need to edit it 'false' or no edit is required 'true'
		$no_edit = false;
		//Exists in our database or not
		$existing = false;

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
		
		//First one checks if Clei Exists 2nd part checks and makes sure the clei is valid (If Clei is invalid then check heci)
		if($clei && !is_numeric($clei) && strlen ($clei) == 10) {
			//Make clei the 10 digit the new heci
			$heci = $clei;
			$pheci = prep($heci);

			$query = "SELECT id FROM parts WHERE heci = $pheci;";
            $result = qdb($query) OR die(qe().'<BR>'.$query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $partid = $r['id'];
            } else {
            	$partid = 0;
            }

			//$partid = getPartId($part_number, $heci);
			echo '<b>Using PartID:</b>' . ($partid ? $partid : "NULL") . "<br>";

			if($partid) {
				$existing = true;
			} else {
				echo "<b>Failed to find the part</b> (Part Name Below)<br>";
				echo $part_number . '<br>';	
				echo "<b>Searching Alias for the part</b> (Aliases Below)<br>";
				foreach($inventoryalias as $aliasSearch) {
					echo $aliasSearch . ' ';
					$partid = getPartId($aliasSearch);

					//If a partid is found then break out of the foreach loop
					if($partid) {
						echo "<br><b>Alias Part Found!</b> ".$partid."<br>";
						$existing = true;
						break;
					}
				}
				echo '<br>';
			}
			
		//Checks if heci is set and makes sure the heci is actually a valid heci else push to next if invalid
		} else if($heci && !is_numeric($heci) && strlen ($heci) == 7) {
			//Add in and make the Heci a 10 digit
			$heci .= 'VTL';

			$query = "SELECT id FROM parts WHERE heci = $pheci;";
            $result = qdb($query) OR die(qe().'<BR>'.$query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $partid = $r['id'];
            } else {
            	$partid = 0;
            }

			//$partid = getPartId($part_number, $heci);
			echo '<b>Using PartID:</b>' . ($partid ? $partid : "NULL") . "<br>";

			if($partid) {
				$existing = true;
			} else {
				echo "<b>Failed to find the part</b> (Part Name Below)<br>";
				echo $part_number . '<br>';	
				echo "<b>Searching Alias for the part</b> (Aliases Below)<br>";
				foreach($inventoryalias as $aliasSearch) {
					echo $aliasSearch . ' ';
					$partid = getPartId($aliasSearch);

					//If a partid is found then break out of the foreach loop
					if($partid) {
						echo "<br><b>Alias Part Found!</b> ".$partid."<br>";
						$existing = true;
						break;
					}
				}
				echo '<br>';
			}
			
		//None of the above exists so run the format_part() by David	
		} else {
			//Declare no heci
			$noHeci = true;
			$partid = 0;

			$part_number = format_part($part_number);

			$partPieces = array();

			if (preg_match('/\s/', $part_number)) {
				$partPieces = explode(' ', $part_number);

				foreach($partPieces as $single) {
					$partid = getPartId($single);

					//If a partid is found then break out of the foreach loop
					if($partid) {
						break;
					}
				}
			} else {
				$partid = getPartId($part_number);

				if(!$partid) {
					$query = "SELECT id, part FROM parts WHERE LOWER(part) LIKE ".prep(strtolower($part_number) . '%').";"; 
					$result = qdb($query) OR die(qe().'<BR>'.$query); 
					//Checking if the result is 1 otherwise multiple items found 
					if (mysqli_num_rows($result) > 0) { 
						$r = mysqli_fetch_assoc($result);
						$partid = $r['id'];
					}
					//echo $query;
				}
			}

			echo '<b>Using PartID:</b>' . ($partid ? $partid : "NULL") . "<br>";

			if(!$partid) {
				echo "<b>Failed to find the part</b><br>";
				echo "<b>Searching Alias for the part</b> (Aliases Below)<br>";
				foreach($inventoryalias as $aliasSearch) {
					echo $aliasSearch . ' ';
					$partid = getPartId($aliasSearch);

					//If a partid is found then break out of the foreach loop
					if($partid) {
						echo "<br><b>Alias Part Found!</b> ".$partid."<br>";
						break;
					}
				}
				echo '<br>';
			}

			//After all that if still not found then assume its a new item
			if(!$partid) {
				echo "<b>Nothing Found (Assuming new Part):</b>".$part_number."<br>";
			} else {
				//Flag the part as existing due to partid found
				$existing = true;
			}
		}
		
		//Item Exists
		if($existing) {
			//Check part_number and see if the name for the part already exists... If not set the partnumber as an alias to the part
			//This is the part number from the new DB
			$part = getPart($partid, 'part');
			
			//This is to find a heci for an item that does not have a heci (Attempt to)
			if(!$heci)
				$heci = getPart($partid, 'heci');

			$explode = explode(' ', $part);
			//Explode will be empty if no ' '
			if(empty($explode)) {
				//Reset as using array[] will just append
				unset($explode);

				$explode[] = $part;
			}
			
			//Check and store and array elements that do not match as they are alias
			$confirmedAlias = array_diff($inventoryalias, $explode);
			echo '<b>Part Number</b>: ' . $part . '<br>';
			echo '<b>Aliases that are unique to the Part</b>:<br>';
			print_r($confirmedAlias);
			echo '<br>';
			
			//If the item exists and there are no unique Aliases then let there is nothing to be done
			if(empty($confirmedAlias)) {
				$no_edit = true;
			}

		//Item has no HECI but a partid is found
		} else if (!$existing && $noHeci) {
			$brianAlias = explode(' ', $part_number);
			
			$part_number = '';
			
			foreach($inventoryalias as $newPart) {
				//Statement just makes sure that we dont add a space in the beginning or we can just trim it later
				$part_number .= (empty($part_number) ? $newPart : ' ' . $newPart);
			}
		}
		echo '<b>Part Number</b>: ' . $part_number . ' <b>HECI</b>: ' . ($heci ? $heci : 'NULL') . '<br>';

		
		//This signifies that the part exists but the the part name does not exist so we need to alias
		if($existing && !$no_edit) {
			//add a space then add the alias as the new part name
			foreach($confirmedAlias as $alias) {
				echo '<b>Current Part:</b> '.$part.' <b>Missing Alias:</b>'.$alias.'<br>';
				$part .= ' ' . $alias;
			}
			
			mergePart($part, $partid, $existing);
			echo '<b>New Aliases was Found</b><br>';
			echo '<b>New Full Part Number: </b> '.$part.'<br>';
			
			//indexer($partid);
		//Main item was not found but the alias exists in the database (Just update the partname with the alias concatenated name)
		} else if (!$existing) {
			//Convert to our manf id as a last resort from brian's database as we do not have this information
			$manfid = dbTranslateManf($manfid);
			mergePart($part_number, $partid, $existing, $heci, $desc, $manfid);
			echo '<b>Item Does Not Exist</b><br>';
			echo '<b>Adding Part:</b> '.$part_number.'<br>';

			//indexer($partid);
		//This is just here to signify non of the above conditions are met so do nothing, most likely because part exists and the alias/part name already exists in the database	
		} else {
			echo '<b>No Changes Required</b><br>';
		}
		
		echo '<br><br>';
		
		//empty the alias for this inventory id to make room for the next inventory id item aliases
		unset($inventoryalias);
		unset($confirmedAlias);
		unset($aliasExploded);
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

		//echo $query;
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
