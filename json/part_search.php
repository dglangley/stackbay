<?php
    include '../inc/dbconnect.php';
    include '../inc/format_date.php';
    include '../inc/keywords.php';
    include_once '../inc/form_handle.php';
    
    //if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
    $search = (isset($_REQUEST['search']) ? $_REQUEST['search'] : '');
    $filter = (isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '');
    $companyid = (isset($_REQUEST['companyid']) ? $_REQUEST['companyid'] : '');
    $order_type = (isset($_REQUEST['order_type']) ? $_REQUEST['order_type'] : '');

    // This function filters out an array element (For Multidimensional Arrays)
    function filterOut($array, $key, $value){
        foreach($array as $subKey => $subArray){
            if($subArray[$key] == $value){
                unset($array[$subKey]);
            }
        }
        return $array;
    }

    // $remove = 'component' = only grab component or 'equipment', leave blank for all
    function searchParts($search, $remove = '', $companyid=0, $order_type='') {
        $results = array();

		if ($companyid AND $order_type) {
			$items = array();
			if ($order_type=='Sale') {
				$query = "SELECT * FROM demand items, ";
			} else if ($order_type=='Purchase') {
				$query = "SELECT * FROM availability items, ";
			}
			$query .= "search_meta m, parts p ";
			$query .= "WHERE m.datetime >= '".format_date($today,'Y-m-d 00:00:00',array('d'=>-30))."' AND m.id = items.metaid ";
			$query .= "AND m.companyid = '".res($companyid)."' AND p.id = items.partid ";
			$query .= "GROUP BY p.id ORDER BY m.datetime DESC; ";
			$result = qdb($query) OR jsonDie(qe().'<BR>'.$query);
			while ($r = mysqli_fetch_assoc($result)) {
				$items[$r['partid']] = hecidb($r['partid'],'id');
			}
			foreach ($items as $partid => $r) {
				$results[$partid] = $r[$partid];
			}
		} else {
	        $results = hecidb($search);

	        // Repair utilizes partids somtimes, so add the hecidb id fallback just in case
			if(empty($results)) {
				$results = hecidb($search, 'id');
			}

			// Filter option if ever needs to be used it is here
			if(! empty($remove) && $remove != 'all') {
				$results = filterOut($results, 'classification', $remove);
			}
		}

        // Stop JSON from sorting itself as the key currently is just the part ID
        // Also add in the current stock in the inventory to alleviate the amount of ajax queries needed on the component request page
        foreach($results as $key => $value) {
            $stock = 0;

            // Key is also the partid
            $query = "SELECT SUM(qty) as stock FROM inventory WHERE partid = '".res($key)."' AND (status = 'shelved' OR status = 'received');";
            $result = qdb($query) OR die(qe() .' '. $query);
            if (mysqli_num_rows($result)>0) {
                $result = mysqli_fetch_assoc($result);
                $stock = ($result['stock'] ? $result['stock'] : 0);
            }

            $results[$key]['stock'] = $stock;

            $value = explode(' ',trim($value['part']));
            $results[$value[0].'.'.$key] = $results[$key];
            unset($results[$key]);
        }

        return $results;
    }
    
    $results = array();

    $results = searchParts($search, $filter, $companyid);

    //print "<pre>".print_r($results,true)."</pre>";

    echo json_encode($results);
    exit;
