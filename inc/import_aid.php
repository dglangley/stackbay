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
    	        $query = "SELECT i.heci, i.clei, i.short_description, im.name manf
    	        FROM inventory_inventory i, inventory_manufacturer im 
    	        WHERE i.id = ".prep($inventory_id)." AND i.manufacturer_id_id = im.id;";
    	        $result = qdb($query,"PIPE") or die(qe("PIPE")." ".$query);
    	        $r = mysqli_fetch_assoc($result);
    	        $INVENTORY_IDS[$inventory_id] = part_process($r);
    	    }
    	    return($INVENTORY_IDS[$inventory_id]);
    	}
        function part_process($r){
			//Function takes in a row from Brian's inventory, checks to see if it exists in our system, and sets part if it doesn't
			//Requires the inclusion of both getPartID.php and setPartId.php
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
        //Brian has Item/Description values parsing through his memo collumn
            $result = array();
            $desc = explode(";",$memo);
            foreach($desc as $label){
            	$instance = explode(":", $label);
            	$result[trim(strtolower($instance[0]))] = trim($instance[1]);
            }
            return $result;
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
	
?>