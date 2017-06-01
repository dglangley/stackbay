<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/getPartId.php';
	include_once $rootdir.'/inc/setPart.php';
	include_once $rootdir.'/inc/getPipeIds.php';
     
        $INVENTORY_IDS = array();
    	function translateID($inventory_id){
            global $INVENTORY_IDS;
    	    if (!isset($INVENTORY_IDS[$inventory_id])){
    	        $query = "SELECT i.heci, i.clei, i.part_number, i.short_description, im.name manf
    	        FROM inventory_inventory i, inventory_manufacturer im 
    	        WHERE i.id = ".prep($inventory_id)." AND i.manufacturer_id_id = im.id;";
    	        $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    	        $r = mysqli_fetch_assoc($result);
    	        $proc = part_process($r);
    	        if($proc){
    	        	$INVENTORY_IDS[$inventory_id] = $proc;
    	        }
    	    }
    	    return($INVENTORY_IDS[$inventory_id]);
    	}
        function part_process($r){
			//Function takes in a row from Brian's inventory, checks to see if it exists in our system, and sets part if it doesn't
			//Requires the inclusion of both getPartId.php and setPartId.php
			if ($r['clei']) { $r['heci'] = $r['clei']; }
			else if (strlen($r['heci'])<>7 OR is_numeric($r['heci']) OR preg_match('/[^[:alnum:]]+/',$r['heci'])) { $r['heci'] = ''; }
			else { $r['heci'] .= 'VTL'; }//append fake ending to make the 7-digit a 10-digit string
	        
			$partid = getPartId($r['part_number'],$r['heci']);
			if (! $partid) {
				$partid = setPart(array('part'=>$r['part_number'],'heci'=>$r['heci'],'manf'=>$r['manf'],'descr'=>$r['short_description']));
			}
			return $partid;
		}
    	function memo_walk($memo){
        //Brian has Item/Description values parsing through his memo column
            $result = array();
            //$desc = explode(";",$memo);
            $desc = explode("\n",$memo);
            foreach($desc as $label){
            	$instance = explode(":", $label);
            	$result[trim(strtolower($instance[0]))] = trim($instance[1]);
            }
            return $result;
	    }
	    function desc_walk($desc){
	    	$first = explode("\n", $desc);
	    	$return = array();
	    	foreach($first as $line){
	    		$ex = array();
	    		if(strpos($line, ":") > 0){
	    			$ex = explode(":",$line);
	    		} else if (strpos($line,"#")){
	    			$ex = explode("#",$line);
	    		}
	    		$return[strtolower(trim($ex[0]))] = trim($ex[1]);
	    	}
			if(!is_numeric($return["item"])){
				$return['part_number'] = $return['item'];
				unset($return['item']);
			}
	    	return $return;
	    }
        function splitDesc($data, $start, $end){
            $data = ' ' . $data;
            $initial = strpos($data, $start);
            if ($initial == 0)
                return '';                
            $initial += strlen($start);
            $length = strpos($data, $end, $initial) - $initial;
            return substr($data, $initial, $length);
        }
        function desc_weight_value($desc,$inv){
            $weight = 0;
            $desc = explode(" ",$desc);
            $inv = explode(" ",$inv);
            foreach($desc as $d){
                foreach($inv as $i){
                    if($i == $d){
                        $weight++;
                    }
                }
            }
            return $weight;
        }


	$WARRANTY_MAPS = array(
		0 =>0,
		1 =>4,/*30 days*/
		2 =>1,/*AS IS*/
		3 =>2,/*5 days*/
		4 =>5,/*45 days*/
		5 =>7,/*90 days*/
		6 =>8,/*120 days*/
		7 =>10,/*1 year*/
		8 =>11,/*2 years*/
		9 =>12,/*3 years*/
		10 =>13,/*lifetime*/
		11 =>9,/*6 months*/
		12 =>6,/*60 days*/
		13 =>3,/*14 days*/
		14 =>9,/*180 days*/
	);

	$USER_MAPS = array(
		1=>1398,/*brian*/
		2=>2,/*sam*/
		3=>3,/*chris*/
		4=>16,/*rathna*/
		5=>3,/*accounting => chris*/
		9=>1401,/*mike*/
		11=>3,/*sabedra*/
		13=>1399,/*vicky*/
		18=>1,/*david*/
		21=>3,/*juan*/
	);
	function mapUser($id) {
		global $USER_MAPS;

		if (! $id) { return false; }

		return ($USER_MAPS[$id]);
	}
	$upsid = 1;
	$fedexid = 2;
	$otherid = 3;
	$CARRIER_MAPS = array(
		1 => $upsid,
		2 => $fedexid,
		3 => $otherid,
		4 => $fedexid,
		5 => $fedexid,
		6 => $fedexid,
		7 => $upsid,
		8 => $upsid,
		9 => $upsid,
		10 => $otherid,
		11 => $otherid,
		12 => $upsid,
		13 => $upsid,
	);
	$SERVICE_MAPS = array(
		1 => 1,/*UPS GROUND*/
		2 => 7,/*FedEx GROUND*/
		3 => 13,/*Other LTL*/
		4 => 12,/*FedEx Standard Overnight*/
		5 => 9,/*FedEx 2Day*/
		6 => 8,/*FedEx Express Saver*/
		7 => 4,/*UPS Overnight*/
		8 => 3,/*UPS 2nd Day Air*/
		9 => 2,/*UPS 3 Day Select*/
		10 => 14,/*Other Other*/
		11 => 14,/*Other Other*/
		12 => 6,/*UPS Red Saver*/
		13 => 4,/*UPS Overnight*/
	);
	$TERMS_MAPS = array(
		1 => 10,
		2 => 6,
		3 => 12,
		4 => 4,
		5 => 14,
		6 => 13,
		7 => 7,
		8 => 3,
		9 => 2,
		10 => 11,
		11 => 8,
		12 => 1,
		13 => 6,
		14 => 9,
	);
	
	function mapFreight($id){
		global $FREIGHT_MAPS;
		if (! $FREIGHT_MAPS[$id]) { return false; }
		return ($FREIGHT_MAPS[$id]);
	}
	function address_translate($address_string){
		$address_string = str_replace(chr(160),'',trim($address_string));
		if (! $address_string) { return false; }

		$address_fields = explode(chr(10),$address_string);
		$last_field = count($address_fields)-1;
		$csz = explode('|',preg_replace('/^([^,]+)[[:space:],]+([A-Z]{2})[[:space:].]+([0-9]{5})$/','$1|$2|$3',$address_fields[$last_field]));
		$city = trim($csz[0]);
		$state = trim($csz[1]);
		$zip = trim($csz[2]);
		$street = trim($address_fields[($last_field-1)]);

		$query = "SELECT `id` FROM `addresses` where (street = '".res($address_string)."') ";
		if ($city AND $state) {
			$query .= "OR (street = '".res($street)."' AND city = '".res($city)."' AND state = '".res($state)."' AND postal_code = '".res($zip)."') ";
		} else {
/*
for ($i=0; $i<strlen($address_string); $i++) {
	echo $address_string[$i].' ('.ord($address_string[$i]).')<BR>';
}
*/
		}
		$query .= "; ";
		$result = qdb($query) or die(qe()." | $query");
		if(mysqli_num_rows($result)){
			$r = mysqli_fetch_assoc($result);
			return($r['id']);
		} else {
			return 'NULL';
		}
		
	}
?>
