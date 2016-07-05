<?php
//Ghost Inventory script will run on CRON with the goal of pulling values from
//the avalibilty table with random weight. There will be a simplified UI which
//interacts with the script which will inform the values by percentage of the
//total required parts for each company we wish to show inventory for. We will
//show values of the companies that have posted products in the previous week.

//=============================================================================
//---------------------------Include requisite files---------------------------
//=============================================================================
include_once $_SERVER["DOCUMENT_ROOT"]."/inc/dbconnect.php";

//For testing purposes, I am running this on local mySQL connection, this will 
//Need to be fixed before pushing to live.
// $conn = new mysqli('127.0.0.1', 'aaronventel', '', 'c9');




//Get list of parts, with meta id, qty, and company from database

$getPairedData = "SELECT ";
$getPairedData .= "availability.`partid` as `Part`, ";
$getPairedData .= "availability.`avail_qty` as `Quantity`,";
$getPairedData .= "search_meta.`id` as `Meta_ID`, ";
$getPairedData .= "search_meta.`companyid` as `Company` ";
$getPairedData .= "FROM search_meta,availability,companies ";
$getPairedData .= "WHERE  `datetime` > DATE_SUB(CURDATE( ) , INTERVAL 1 WEEK)";
$getPairedData .= "AND search_meta.`id`= availability.`metaid` ";
$getPairedData .= "AND companies.`id` = search_meta.companyid; ";



$results = qdb($getPairedData) OR die(qe());

//Declare the value of the Organized array, which will contain the values of the
//returned rows in the following loop
$organized = array();


//Parse the result set by row, sort out the data by company
foreach($results as $row){
    
    //Grab company information
    $company = $row['Company'];
    $partID = $row['Part'];
    $quantity = $row['Quantity'];
    
    //If the company doesn't already have a record, make the record
    if(!array_key_exists($company,$organized)){
        $organized[$company] = array();   
    }
    //Update the total quantity of the stock we have availible
    $organized[$company][$partID] += $quantity;
}


//Declare the percentage value of each of the API's (curated for now)
$weights = array(
    "Default" => .30,
    "34" => .80,
    "36" => .25
    );

//Declare the array catch for the randomized ID's
$parsed = array();

//Loop through the organization list and parse out the appropriate part ID's
foreach ($organized as $company => $item) {
    
    //Count the number of unique items each company has
    $numItems = count($item);

    //If the company has a weight value declared for it, multiply by that company's
    //percentage, in order to have the appropriate number to return
    if (array_key_exists($company,$weights)){
        $return = round(($weights[$company])*$numItems);
    }
    
    //Otherwise, multiply by the predetermined default amount
    else {
        $return = round(($weights['Default'])*$numItems);
    }
    
    
    //Always show at least one result. This was an assumption made on my part,
    //this is not exactly the use case we want in production.
    if ($return == 0){
        $return = 1;
    }
    
    //Select the random itemID's from the array, store them back into the parsed amount
    if ($return == 1) {
        $singleItem = (array_rand($item));
        $parsed[$company] = array($singleItem => $organized[$company][$singleItem]);
    }
    else{
            foreach ((array_rand($item,$return)) as $singleItem){
                $parsed[$company][$singleItem] = $organized[$company][$singleItem];
            }
        }
    
    
}

//Loop through the items we randomly selected and insert the rows

foreach ($parsed[$company] as $item => $qty){
        $insert = "INSERT INTO staged_qtys ";
        $insert .= "(partid , companyid , qty) VALUES ('";
        $insert .= $item."', '".$company."', '".$qty."');";
        qdb($insert) OR die(qe());
    
}
    //print_r("Ghost Sunday Completed: ".date("m-d-y|g:i a"));

?>
