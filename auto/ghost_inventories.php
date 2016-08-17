<?php
//Ghost Inventory script will run on CRON with the goal of pulling values from
//the avalibilty table with random weight. There will be a simplified UI which
//interacts with the script which will inform the values by percentage of the
//total required parts for each company we wish to show inventory for. We will
//show values of the companies that have posted products in the previous week.

//=============================================================================
//---------------------------Include requisite files---------------------------
//=============================================================================
include_once $_SERVER["ROOT_DIR"]."/inc/dbconnect.php";

//For testing purposes, I am running this on local mySQL connection, this will 
//Need to be fixed before pushing to live.
// $conn = new mysqli('127.0.0.1', 'aaronventel', '', 'c9');


$query = "TRUNCATE TABLE staged_qtys; ";
$result = qdb($query) OR die(qe().' '.$query);


//Get list of parts, with meta id, qty, and company from database

$getPairedData = "SELECT ";
$getPairedData .= "availability.`partid`, ";
$getPairedData .= "MAX(availability.`avail_qty`) avail_qty,";
$getPairedData .= "search_meta.`companyid` ";
$getPairedData .= "FROM search_meta,availability,companies ";
$getPairedData .= "WHERE  `datetime` > DATE_SUB(CURDATE( ) , INTERVAL 1 WEEK) ";
$getPairedData .= "AND search_meta.`id`= availability.`metaid` ";
$getPairedData .= "AND companies.`id` = search_meta.companyid ";
$getPairedData .= "GROUP BY partid, companyid; ";



$results = qdb($getPairedData) OR die(qe());

//Declare the value of the Organized array, which will contain the values of the
//returned rows in the following loop
$organized = array();

//Declare the percentage value of each of the API's (curated for now)
$weights = array(
	"Default" => .30,
	/*"34" => .80,*/
	"36" => .25,
	"3" => .25,
	"1414" => .25,
	"1264" => .33,
	"4" =>.26
);


//Parse the result set by row, sort out the data by company
foreach($results as $row){
    
    //Grab company information
    $companyid = $row['companyid'];
    $partid = $row['partid'];
    $quantity = $row['avail_qty'];

	// at this point in time, only add to db where we have pre-set weights for given companies
	if (! isset($weights[$companyid])) { continue; }
    
    //If the company doesn't already have a record, make the record
    if(!array_key_exists($companyid,$organized)){
        $organized[$companyid] = array();   
    }
	//initialize qty to 0 for this partid
	if (! isset($organized[$companyid][$partid])) { $organized[$companyid][$partid] = 0; }

    //Update the total quantity of the stock we have availible
    $organized[$companyid][$partid] += $quantity;
}

//Declare the array catch for the randomized ID's
$parsed = array();

//Loop through the organization list and parse out the appropriate part ID's
foreach ($organized as $companyid => $item) {
    
    //Count the number of unique items each company has
    $numItems = count($item);

    //If the company has a weight value declared for it, multiply by that company's
    //percentage, in order to have the appropriate number to return
    if (array_key_exists($companyid,$weights)){
        $return = round(($weights[$companyid])*$numItems);
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
        $parsed[$companyid] = array($singleItem => $organized[$companyid][$singleItem]);
    }
    else{
            foreach ((array_rand($item,$return)) as $singleItem){
                $parsed[$companyid][$singleItem] = $organized[$companyid][$singleItem];
            }
        }
    
    
}

//Loop through the items we randomly selected and insert the rows

foreach ($parsed as $companyid => $r) {
	foreach ($r as $partid => $qty){
        $insert = "INSERT INTO staged_qtys ";
        $insert .= "(partid , companyid , qty) VALUES ('";
        $insert .= $partid."', '".$companyid."', '".$qty."');";
        qdb($insert) OR die(qe());
	}
    
}
    //print_r("Ghost Sunday Completed: ".date("m-d-y|g:i a"));

?>
