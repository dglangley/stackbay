<?php
//============================================================================
//The hotlist script will take the information from the availability tables
//and display the part information, its Heci, the user information of any
//user who marked it, and the data about the change
//=============================================================================

//------------------------------------Main------------------------------------

//Include the requisite files
include_once($_SERVER["ROOT_DIR"].'/inc/.php');
include_once($_SERVER["ROOT_DIR"]."/inc/getSupply.php");
include_once($_SERVER["ROOT_DIR"]."/inc/dbconnect.php");
include_once($_SERVER["ROOT_DIR"]."/inc/getPartId.php");
include_once($_SERVER["ROOT_DIR"]."/inc/getPart.php");
include_once($_SERVER["ROOT_DIR"]."/inc/keywords.php");

#$credentials = new mysqli('127.0.0.1', 'aaronventel', '', 'c9');


//Pull the values of the parts we want to search for in the last day
$query = "SELECT favorites.`userid`,`partid`, p.`id`, `heci` "; 
$query .= "FROM  `favorites`, `parts` p ";
$query .= "Where `partid` = p.`id` ";
$query .= "Order By ID DESC ";
$query .= "LIMIT 12;";
//echo ("Initial Query: ".$query."<br>");

//Grab the search results from the database
$results = qdb($query);


//Establish the initial declaration of the html
echo("<!DOCTYPE html>");
echo("<html>");
echo("<head>");
echo("<link href='/css/hotlist.css' rel='stylesheet' type='text/css'>");
echo("</head>");
echo("<body>");
echo("<table>");
echo("    <tr class = 'tableHead'>");
echo("        <td class = 'part'>Description</td>");
//echo("        <td class = 'heci'>HECI</td>");
//echo("        <td class = 'price'>Price</td>");
echo("        <td class = 'user'>Users</td>");
echo("        <td class = 'end'>Available</td>");


echo("    </tr>");

//Take in iteritavely the values of the part ids
foreach ($results as $row) {
    $partids = array();
    
    //Prepare the output array to seperate out the output from the processing
    $output = array(
        'pname' => '',
        'heci' => '',
        'users' => '',
        'availability' => array()
        );
    
    switch ($row['userid']) {
        case 1:
            $output['users'] = 'David';
            break;
        case 2:
            $output['users'] = 'Sam';
            break;
        default:
            $output['users'] = 'Aaron';
            break;
    }
    //Pull the heci and/or the Part ID
    $partid = $row['partid'];
    $heci = $row['heci'];
    
    
    //If the part has a HECI, do the following
    if ($heci) {
        //Shorten the Heci to the shortened heci seven
        $heci7 = substr($heci,0,7);
        
        //Pull a list of related part numbers to get market information
        $parts = "Select `part`,`id` FROM `parts` ";
        $parts .= "WHERE `heci` like '".$heci7."%';";
        $multiParts = qdb($parts);
        
        //Make note of the seven digit heci 
        $output['heci'] = $heci7;
        
        //For each of the parts that the results returned, get the part information
        foreach ($multiParts as $part) {
            if(!$output['pname']){
                $output['pname'] = getPart($part['id'], 'part');
            }
//            echo ('It is related to: '.$part['id']."<br>");
            array_push($partids, $part['id']);
        }
    }
    //If it does not have a Heci, find the results which it is similar to
    else {
        //Find the part names of all the related parts without part information 
        $partName = getPart($partid);
        $output['pname'] = $partName;

        
        //Find if there the parts related as a list of partids
        $related = hecidb($partName);
        foreach($related as $partID => $days_results){
            array_push($partids, $partID);
        }
    }
    

    //Take in the list of partids from the initial search
    $resultSet = getSupply($partids,1);    
    
    //Reset the day counter
    $i = 0;
    
    //If there are no results in the array, then set that day's values to zero

    //Now take the results of the get supply and take in the 
    foreach($resultSet['results'] as $date => $days_results){
        
        //We don't care about any results more than two days ago
        if ($i == 2){
            $no_new_result = false;
            $no_old_result = false;
            continue;
        }
        $i += 1;

//        echo "TODAY'S DATE: ".$date."<br>";
//        echo ("<pre>");//
//        print_r($days_results);
//        echo ("</pre>");
//        echo ("^this is a day's results.");

        if (empty($days_results)){
            if ($i == 1){
                $no_new_result = true;
//                echo("There is no new result");
                }
            else{
                $no_old_result = true;
//                echo("There is no old result");
                }
        }

        //If both are null, exit out of the loop
        if($no_new_result and $no_old_result){continue;}  
        
        //Each day, go through the individual returned values
        foreach($days_results as &$item){
            
            //If the results don't return anything, mark the comparative value 
            //for each company to zero for either the old or the new and 
            //continue to the next iteration of the loop

            //Otherwise, make a note of this particular result's company
            $company = $item['company'];
            
            //If the company doesn't already have an entry for this item, make a
            //new line item for the company
            if(!array_key_exists($company,$output['availability'])){
                $output['availability'][$company] = array(
                    'new' => '',
                    'old' => '',
                    'chg' => '',
                    'price' => '',
                    'source' => ''
                    );
            }


            //If there is only no new result, then set the value of new to zero
            if($no_new_result){
                $output['availability'][$company]['new'] = 0;
            }
            
            //same if there is no old result
            if($no_old_result){
                $output['availability'][$company]['old'] = 0;
            }
            
            //If there is a value, then save the company values by increasing the quantity
            if($i == 1){
                $output['availability'][$company]['new'] += $item['qty'];
                $price = $item['price'];    
            }
            else{
                $output['availability'][$company]['old'] += $item['qty'];
            }
            $output['availability'][$company]['price'] = $item['price'];
            $output['availability'][$company]['source'] = $item['sources'];
        }
    }

    
    //Calculate the change. If there is no change, mark the flag
    //This covers the case where if the value of both old AND new is matched, the
    //loop will not output (earlier just covered the case that both were null)
    $any_delta = false;
    foreach($output['availability'] as $options => &$co){
        $co['chg'] = $co['new'] - $co['old'];
        if ($co['chg'] != 0) {
            $any_delta = true;
        }
    }
    
    if (!$any_delta){
        continue;
    }
    
    //If there is still no entry into the availability script, skip.
    if(empty($output['availability'])){continue;}
    
    //Start the new line
    echo("<tr>");
    
    //Print the description for each of the items.
    echo("  <td class = 'part'>".$output['pname']." &nbsp; ".$output['heci']."</td>");
    
    //Echo the curated list of the user output information
    echo("  <td class = 'user'>".$output['users']."</td>");
    
    //Print the end-piece of the line
    echo("  <td class = 'end'>");
    
    //For each item of availible stock by quantity, print the value
    foreach($output['availability']  as $company => $ava){
        echo('<div class="item">');
        
        //Stack for showing an empty value in the available table if there is none
        if($ava['new']){echo('<div class="new">'.$ava['new'].'</div>');}
        else{echo('      <div class="new">&nbsp;</div>');}
        
        //Show the appropriate Arrow for the changed value
        if ($ava['chg']>0){echo('<div class="posdelta">&#9650;</div>');}
        else if($ava['chg']<0){echo('<div class="negdelta">&#9660;</div>');}
        else {echo('<div>&nbsp;&nbsp;&nbsp;&nbsp;</div>');}
        
        //Print out the 
        echo('      <div class="old">'.$ava['old'].'</div>');
        
        //Print the name of the supplier
        echo('      <div class="supplier">'.$company.'</div>');

        //Output each of the sources iteratively. There is currently no case for
        //a missing image. If I would want to make the exceptional case, David
        //might have already solved one for his system.
        echo('      <div class="source">');
        foreach ($ava['source'] as $sc) {
            echo('<img src="http://www.ven-tel.com/img/'.strtolower($sc).'.png"></img>');
        }
        echo('      </div>');
        
        //Echo the price of the item.
        echo('      <div class="price">'.$ava['price'].'</div>');
        echo('    </div>');
        
        
}

    echo("</td>");
    echo("</tr>");
}

echo("</table>");
echo("</body>");
echo("</html>");
?>
