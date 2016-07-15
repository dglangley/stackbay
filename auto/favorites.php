<?php
//============================================================================
//The favorites script will take the information from the availability tables
//and display the part information, its Heci, the user information of any
//user who marked it, and the data about the change
//=============================================================================

//------------------------------------Main------------------------------------

//Include the requisite files
//include_once($_SERVER["ROOT_DIR"].'/inc/.php');
include_once($_SERVER["ROOT_DIR"]."/inc/dbconnect.php");
include_once($_SERVER["ROOT_DIR"]."/inc/getSupply.php");
include_once($_SERVER["ROOT_DIR"]."/inc/getPartId.php");
include_once($_SERVER["ROOT_DIR"]."/inc/getPart.php");
include_once($_SERVER["ROOT_DIR"]."/inc/keywords.php");
include_once($_SERVER["ROOT_DIR"]."/inc/send_gmail.php");

$U['id'] = 5;
$U['name'] = 'Amea Cabula';
$U['email'] = 'amea@ven-tel.com';
$U['phone'] = '(805) 212-4959';
setGoogleAccessToken(5);

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

//gets added globally to email header within format_email() (inside send_gmail)
$EMAIL_CSS = file_get_contents($_SERVER["ROOT_DIR"].'/css/favorites.css');

//Establish the initial declaration of the html
$email_str = "";
//$email_str .= "<!DOCTYPE html>";
//$email_str .= "<html>";
//$email_str .= "<head>";
//$email_str .= "</head>";
//$email_str .= "<body>";
$email_str .= "Hey there! I found the following changes to the availibility of";
$email_str .= " your favorited items since last time they were searched! -Amea<br/><br/>";
$email_str .= "<table style='margin:auto; padding:5px; width: 98%; min-width:500px; border-collapse:collapse; border-bottom:2px solid #EDF2F7;'>";
$email_str .= "    <tr style='vertical-align:top; min-height:28px;'>";
$email_str .= "        <td style='border-bottom: 1px solid #EDF2F7; padding:5px;'>Description</td>";
$email_str .= "        <td style='width:5%; padding-right:15;'>Users</td>";
$email_str .= "        <td class = 'end'>Available</td>";
$email_str .= "    </tr>";

$bgc = array('#ffffff','#f7f7f7');

$rownum = 0;

//Take in iteritavely the values of the part ids
foreach ($results as $k => $row) {
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
    
	$no_new_result = false;
	$no_old_result = false;

    //Take in the list of partids from the initial search
    $resultSet = getSupply($partids,1);    
    
    //Reset the day counter
    $i = 0;
    
    //If there are no results in the array, then set that day's values to zero

    //Now take the results of the get supply and take in the 
    foreach($resultSet['results'] as $date => $days_results){
        
        //We don't care about any results more than 3 days ago (monday backwards to friday)
        if ($i > 3){
            $no_new_result = false;
            $no_old_result = false;
            break;
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

//	if ($k>5) { break; }
    
    if (!$any_delta){
        continue;
    }
    
    //If there is still no entry into the availability script, skip.
    if(empty($output['availability'])){continue;}

    //Start the new line
    $email_str .= "<tr style='background-color:".$bgc[$rownum].";'>";
	$rownum = !$rownum;
    
    //Print the description for each of the items.
    $email_str .= "  <td style='padding-left:6px; padding-right:6px; min-width:400px; width:20%;'>".$output['pname']." &nbsp; ".$output['heci']."</td>";
    
    //Echo the curated list of the user output information
    $email_str .= "  <td style='width:5%; padding-right:15;'>".$output['users']."</td>";
    
    //Print the end-piece of the line
    $email_str .= "  <td class = 'end'>";
    
    //For each item of availible stock by quantity, print the value
    foreach($output['availability']  as $company => $ava){
        $email_str .= '<div class="item">';
        
        //Stack for showing an empty value in the available table if there is none
        if($ava['new']){$email_str .= '<div style="width:20px; font-weight:bold; display:inline-block; position:relative; padding-bottom:1px;">'.$ava['new'].'</div>';}
        else{$email_str .= '      <div style="width:20px; font-weight:bold; display:inline-block; position:relative; padding-bottom:1px;">&nbsp;</div>';}
        
        //Show the appropriate Arrow for the changed value
        if ($ava['chg']>0){$email_str .= '<div class="posdelta" style="color:green; display:inline-block; position:relative; padding-bottom:1px;">&#9650;</div>';}
        else if($ava['chg']<0){$email_str .= '<div class="negdelta" style="color:red; display:inline-block; position:relative; padding-bottom:1px;">&#9660;</div>';}
        else {$email_str .= '<div style=" display:inline-block; position:relative; padding-bottom:1px;">&nbsp;&nbsp;&nbsp;&nbsp;</div>';}
        
        //Print out the 
        $email_str .= '      <div style="text-align:left; width:30px; display:inline-block; position:relative; padding-bottom:1px;">'.$ava['old'].'</div>';
        
        //Print the name of the supplier
        $email_str .= '      <div class="supplier" style="display:inline-block; position:relative; padding-bottom:1px; min-width:247px;">'.$company.'</div>';

        //Output each of the sources iteratively. There is currently no case for
        //a missing image. If I would want to make the exceptional case, David
        //might have already solved one for his system.
        $email_str .= '      <div class="source" style="width:40; display:inline-block; position:relative; padding-bottom:1px;">';
        foreach ($ava['source'] as $sc) {
            $email_str .= '<img src="http://www.ven-tel.com/img/'.strtolower($sc).'.png" style="width:12px;"></img>';
        }
        $email_str .= '      </div>';
        
        //Echo the price of the item.
        $email_str .= '      <div style="width:28%; display:inline-block; position:relative; padding-bottom:1px; width:10%; position:absolute; right:0;">'.$ava['price'].'</div>';
        $email_str .= '    </div>';
        
        
}

    $email_str .= "</td>";
    $email_str .= "</tr>";
}

$email_str .= "</table>";
//$email_str .= "</body>";
//$email_str .= "</html>";

	$send_success = send_gmail($email_str,'Favorites Daily '.date("M j, y"),array('david@ven-tel.com','sam@ven-tel.com'),'aaron@ven-tel.com');
	if ($send_success) {
		echo json_encode(array('message'=>'Success'));
	} else {
		echo json_encode(array('message'=>$SEND_ERR));
	}
?>
