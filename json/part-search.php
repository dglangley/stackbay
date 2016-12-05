<?php
	include '../inc/dbconnect.php';
	include '../inc/format_date.php';
	include '../inc/keywords.php';
    include_once '../inc/form_handle.php';
	
	//if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
	$q = grab('q');
	$qlower = strtolower(preg_replace('/[^[:alnum:]]+/','',$q));
	$page = grab("limit");
	
	function output_format($parts,$id){
        $name = $parts[$id]['part']." &nbsp; ".$parts[$id]['heci'].' &nbsp; '.$parts[$id]['Manf'].' '.$parts[$id]['system'].' '.$parts[$id]['Descr'];
        return $name;
	}
	
	if ($page == "purchase"){
	//Get all the part ID's, pair with 
    $items = array();
    if (strlen($q) > 1){
        $results = (hecidb($qlower));
        foreach($results as $id=> $row){
            $query = "SELECT SUM(qty) as total FROM inventory WHERE partid = $id;";
            $result = qdb($query);
            if (mysqli_num_rows($result)>0) {$r = mysqli_fetch_assoc($result);}
            $name = $row['part']." &nbsp; ".$row['heci'].' &nbsp; '.$row['Manf'].' '.$row['system'].' '.$row['Descr'] . '  &nbsp; ' . ($r['total'] > 0 ? '<span style="color: #4cae4c;">(IN STOCK)' : '<span style="color: #d43f3a;">(OUT OF STOCK)') . '</span>';
            $items[] = array('id' => $id, 'text' => $name);
        }
    }
	    echo json_encode($items);//array('results'=>$companies,'more'=>false));
	}
    else{
        echo(json_encode(sales_rep_view($q)));
    }
    
    function sales_rep_view($search = ''){
        
        //Declare gathering tools
        $matches = array();
        $match_string = '';
        $output = array();

        // There are four use cases for inventory recollection: In Stock, Out of Stock, Pending stock, Never In Stock
        $parts = hecidb($search);

        if (!$parts){
            echo json_encode(array(array("id" => "none",
                    "display" => "Please enter a valid search string")));
            exit;
        }
        foreach ($parts as $id => $data){
            //Here is where I will produce and maintain the display text
            $matches[] = $id;
        }
        //echo $match_string;
        // 1. In stock: Select all part ID's from the inventory where the part_id count is GREATER THAN zero  \\
            $match_string = implode(",",$matches);
            
            $in_stock = "Select SUM(qty) total, partid FROM inventory ";
            
            //If there is a search parameter, grab a list of related PART IDS to the one I am searching for
            if ($search){
                $in_stock .= "WHERE partid IN (".$match_string.")";
            }
            $in_stock .= " GROUP BY partid HAVING total > 0;";
            
            $stock = qdb($in_stock) OR die(qe());
            
            if(mysqli_num_rows($stock)>0){
                foreach ($stock as $s){
                    $key = array_search($s['partid'],$matches);
                    if(isset($key)){
                        unset($matches[$key]);
                    }
                    $output[] = array(
                        "id" => $s['partid'],
                        "text" => output_format($parts,$s['partid'])
                        );
                    //print_r($matches);
                }
            }
            else {
                // echo " none";
            }
            // echo ("<br>");
        
        // 2. Out of Stock : Select all part ID's from the inventory where the part_id count is exactly zero  
        if(!(empty($matches))){
            //Create a new match string if there were no 
            $match_string = implode(",",$matches);
            $out_of_stock = "Select SUM(qty) total, partid FROM inventory ";
            
            
            //If there is a search parameter, limit by each step of the matched value
            if ($search){
                $out_of_stock .= "WHERE partid IN (".$match_string.")";
            }
            $out_of_stock .= " GROUP BY partid HAVING total = 0;";
            $out = qdb($out_of_stock) OR die(qe());
            
            // echo("<br>Out of stock:<br>");
            if(mysqli_num_rows($out)>0){                
                foreach ($out as $o){
                    // echo("Part: ".$o['partid']." | Qty: ".$o['total']."<br>");
                    $key = array_search($o['partid'],$matches);
                    if(isset($key)){
                        unset($matches[$key]);
                    }
                    
                    $output[] = array(
                        "id" => $o['partid'],
                        "text" => "Out of Stock: ".output_format($parts,$o['partid'])  
                    );
                }
            }
            else{
                // echo("None<br>");
            }
        }

        // 3. Pending Stock
        // Search through every active purchase order and find any part which has been ordered but unprocessed
        if(!(empty($matches))){
            //Create a new match string if there were no 
            $match_string = implode(",",$matches);
            $pending = "SELECT partid, SUM(qty) total FROM purchase_orders po, purchase_items pi where po.status = 'Active' AND pi.po_number = po.po_number";
            
            
            //If there is a search parameter, limit by each step of the matched value
            if ($search){
                $pending .= " AND partid IN (".$match_string.")";
            }
            $pending .= " GROUP BY partid HAVING total > 0;";
            $pend = qdb($pending) OR die(qe());
            
            // echo("<br>Pending:<br>");
            if(mysqli_num_rows($pend)>0){                
                foreach ($pend as $p){
                    // echo("Part: ".$p['partid']." | Qty: ".$p['total']."<br>");
                    $key = array_search($p['partid'],$matches);
                    if(isset($key)){
                        unset($matches[$key]);
                    }
                    $output[] = array(
                        "id" => $p['partid'],
                        "text" => "Pending: ".output_format($parts, $p['partid'])
                    );
                }
            }
        }
        
        
        // 4. Never in Stock
        //Everything which has never been ordered, is not in our inventory at a zero quantity, and is not pending
        //would qualify as never in stock
        if(!(empty($matches))){
            // echo ("<br>Never in Stock: <br>");
            foreach($matches as $m){
                $output[] = array(
                    "id" => $m,
                    "text" => "Never in stock: ".output_format($parts,$m));
            }   
        }

        //print_r($stock);
        return $output;
    }
    
    //$sorted = $items;
    
    // $sorted = usort($items, function ($item1, $item2) {
    //     if ($item1['stock'] == $item2['stock']) return 0;
    //     return $item1['stock'] < $item2['stock'] ? -1 : 1;
    // });
    
    //echo("<br><br>");
	exit;
?>
