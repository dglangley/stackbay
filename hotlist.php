<?php
//============================================================================
//The hotlist script will take the information from the availability tables
//and display the part information, its Heci, the user information of any
//user who marked it, and the data about the change
//=============================================================================

//------------------------------------Main------------------------------------

//Include the requisite files
include_once($_SERVER["DOCUMENT_ROOT"]."/inc/getSupply.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/inc/dbconnect.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/inc/getPartId.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/inc/getPart.php");
include_once($_SERVER["DOCUMENT_ROOT"]."/inc/keywords.php");

#$credentials = new mysqli('127.0.0.1', 'aaronventel', '', 'c9');


//Pull the values of the parts we want to search for in the last day
$query = "SELECT `partid`, p.`id`, `heci` "; 
$query .= "FROM  `favorites`, `parts` p ";
$query .= "Where `partid` = p.`id` ";
$query .= "Order By ID DESC ";
$query .= "LIMIT 10;";
echo ("Initial Query: ".$query."<br>");
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
echo("        <td class = 'part'>Part &nbsp;<i> HECI </i></td>");
//echo("        <td class = 'heci'>HECI</td>");
//echo("        <td class = 'price'>Price</td>");
echo("        <td class = 'user'>Users</td>");
echo("        <td class = 'end'>Availible</td>");


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
            echo ('It is related to: '.$part['id']."<br>");
            array_push($partids, $part['id']);
        }
    }
    //If it does not have a Heci, find the results which it is similar to
    else {
        //Find the part names of all the related parts without part information 
        $partName = getPart($partid);
        $output['pname'] = $partName;
        echo("Aliases Output: ");
        echo($partName);
        echo "<br>";
        
        //Find if there the parts related as a list of partids
        $related = hecidb($partName);
        //print $related;
        echo ("Found these related part ids: ");
        echo("<br>");
        foreach($related as $partID => $days_results){
            print_r($days_results['id']);
            echo ("<br>");
            //echo ($partID."<br>");
            array_push($partids, $partID);
        }
    }
    
    echo($output['pname']."<br>");
    
    //Take in the list of partids from the initial search
    $resultSet = getSupply($partids);    
    
    print_r ($partids);
    //var_dump($results);
    echo ("<pre>");
    print_r($resultSet);
    echo ("</pre>");
    
    //Reset the day counter
    $i = 0;
    
    //If there are no results in the array, then set that day's values to zero
    $no_new_result = false;
    $no_old_result = false;
    
    //Now take the results of the get supply and take in the 
    foreach($resultSet['results'] as $date => $days_results){
        
        //We don't care about any results more than two days ago
        if ($i == 2){break;}
        
        echo "TODAY'S DATE: ".$date."<br>";
        echo ("<pre>");
        print_r($days_results);
        echo ("</pre>");

        //Each day, go through the individual returned values
        foreach($days_results as $item){
            
            //If the results don't return anything, mark the comparative value 
            //for each company to zero for either the old or the new and 
            //continue to the next iteration of the loop
            if (empty($days_results)){

                if ($i == 0){
                    $no_new_result = true;
                    continue;
                }
                else{
                    $no_old_result = true;
                    continue;
                }
            }
            
            //If both are null, exit out of the loop
            if($no_new_result and $no_old_result){break;}
            
            //Otherwise, make a note of this particular result's company
            $company = $item['cid'];
            
            //If the company doesn't already have an entry for this item, make a
            //new line item for the company
            if(!array_key_exists($company,$output['availability'])){
                $output['availability'][$company] = array(
                    'new' => '',
                    'old' => '',
                    'chg' => '',
                    'price' => ''
                    );
            }
            print_r($item);
            
            //If there is only no new result, then set the value of new to zero
            if($no_new_result){
                $output['availability'][$company]['new'] = 0;
            }
            
            //same if there is no old result
            if($no_old_result){
                $output['availability'][$company]['old'] = 0;
            }
            
            //If there is a value, then save the company values by increasing the quantity
            if($i == 0){
                $output['availability'][$company]['new'] += $item['qty'];
                $price = $item['price'];    
            }
            else{
                $output['availability'][$company]['old'] += $item['qty'];
                $price = $item['price'];    
            }
        }
        $i++;
    }
    //f
    var_dump($output);
    $access = $output['availability'];
//    print_r($company);
    
    //Calculate the change. If there is no change, mark the flag
    //This covers the case where if the value of both old AND new is matched, the
    //loop will not output (earlier just covered the case that both were null)
    $no_change = false;
    foreach($access as $co){

        $co['chg'] = $co['new'] - $co['old'];
        if ($co['chg'] == 0) {
            $no_change = true;
            break;
        }
    }
    
    //Print the line into the table for this item.
    echo("<tr>");
    echo("<td class = 'part'>".$output['pname']." &nbsp; <i>".$output['heci']."</i></td>");
//    echo("<td class = 'heci'>65432196876487968</td>");
//    echo("<td class = 'price'>\$100.00</td>");
    echo("<td class = 'user'>Sam</td>");
    echo("<td class = 'end'>");
            foreach($access as $company => $ava){
                echo("<pre>");
                print_r($ava);
                echo("</pre>");
                echo("<br>");
            }
    echo("</td>");
    echo("</tr>");

    echo("<br><br>");
}

echo("</table>");
echo("</body>");
echo("</html>");
?>
