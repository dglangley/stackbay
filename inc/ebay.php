<?php
    //The Ebay API script, searches for parts, pulls their basic information, 
    //and passes it into the database, and returns success

    //Limitations and assumptions:
    //This function will work for a maximum of 100 items in a single call.

    
    //Includes:
    
    include_once $_SERVER["DOCUMENT_ROOT"]."/inc/dbconnect.php";
    include_once $_SERVER["DOCUMENT_ROOT"]."/inc/getPartId.php";
    include_once $_SERVER["DOCUMENT_ROOT"]."/inc/setContact.php";
    include_once $_SERVER["DOCUMENT_ROOT"]."/inc/getContact.php";
    include_once $_SERVER["DOCUMENT_ROOT"]."/inc/getCompany.php";
    include_once $_SERVER["DOCUMENT_ROOT"]."/inc/logRemotes.php";
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/logSearchMeta.php';
    include_once $_SERVER["DOCUMENT_ROOT"].'/inc/insertMarket.php';

//    header("Content-type: text/xml");

    
    //Input: Keyword string
    //Output: Formatted ebay string
    function ebayKeyWordSearch($keyword_string){
        //Create the search string
        $ebay_search =  '<?xml version="1.0" encoding="utf-8"?>';
        $ebay_search .= '<findItemsByKeywordsRequest  xmlns="http://www.ebay.com/marketplace/search/v1/services">';
        $ebay_search .= '<AvailableItemsOnly>true</AvailableItemsOnly>';
        $ebay_search .= '<HideDuplicateItems>false</HideDuplicateItems>';
        $ebay_search .= '<keywords>('.$keyword_string.')</keywords>';
        $ebay_search .= '</findItemsByKeywordsRequest>';
        
        //Initialize CURL call
        $headers = array(
            'X-EBAY-SOA-OPERATION-NAME: findItemsByKeywords',
            'X-EBAY-SOA-SERVICE-VERSION: 1.3.0',
            'X-EBAY-SOA-REQUEST-DATA-FORMAT: XML',
            'X-EBAY-SOA-GLOBAL-ID: EBAY-US',
            'X-EBAY-SOA-SECURITY-APPNAME: Ventel-ventelin-PRD-03ebd57c2-4ce91f52',
            'Content-Type: text/xml;charset=utf-8'
        );
        
        $call = curl_init('http://svcs.ebay.com/services/search/FindingService/v1');
        curl_setopt($call, CURLOPT_POST, true);
        curl_setopt($call, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($call, CURLOPT_POSTFIELDS, ($ebay_search));
        curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
        
        //Send and receive the request and receive the file
        $result = curl_exec($call);
        curl_close($call);
        
        //After ending the call, verify the data is as expected.
//        //echo("Results of the call: ".gettype($results)."<br>");
                //Convert the returned value into an xml object and verify the conversion
        $formatted = new SimpleXMLElement($result);
//       //echo('Xml Object Check: '.gettype($formatted)."<br>");
        $matchingResults = $formatted->paginationOutput->totalEntries;
        
        //Verify the call was a success
        if ($formatted->ack == "Success"){
            //echo("Call was successful and returned ".$matchingResults.
            //" item(s) <br>");
        }
        else{
            //echo("Something went wrong with the call, please retry");
        }
        return $formatted;
    }
    
    //Input: Ebay Object
    //Output: Array of Item ID's
    function ebayGetItemIDList($ebay_object){
        //For multiple items, it returns the values as an array, each of the 
        //values in this array are indexed starting at zero. A loop through any 
        //of these results would need to return the itemId, so that I can pull 
        //the description.
        $id_list = array();
        $count = 0;
        $items = $ebay_object->searchResult->item;
        //echo("Items is currently returning: ".gettype($items)."<br>");
        //echo("The following are the Item ID's for the matching results:<br>");
        foreach($items as $identifier){
            $id_list[] = $identifier->itemId;
            $count++;
        }
        //echo("Number of Item ID's pulled: ".$count."<br>");
        foreach($id_list as $test){
            //echo($test."<br>");
            
        }
        return $id_list;
        
    }
    
    //Input: ItemID (Array)
    //Does: Pulls single item descriptions from eBay's
    //Output: Array of descriptions, quantities, prices 
    function ebayGetDescriptions($itemIDs){
        
        //Loop through each of the passed itemID's to get the single item data
        foreach($itemIDs as $id){
            
            //Prep the cURL call
            $url = 'open.api.ebay.com/shopping';
            $url .= '?callname=GetSingleItem';
            $url .= '&responseencoding=XML';
            $url .= '&appid=Ventel-ventelin-PRD-03ebd57c2-4ce91f52';
            $url .= '&siteid=0';
            $url .= '&IncludeSelector=TextDescription,Details';
            $url .= '&version=963';
            $url .= '&ItemID='.$id;

            //Make the curl call
            $call = curl_init();
            curl_setopt($call, CURLOPT_URL, $url);
            curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
            
            $result = curl_exec($call);
            
            //Receive the results, and format it as a simpleXML element to parse
            $formatted = new SimpleXMLElement($result);
            ////print_r ($formatted);
            
            //Combine the title and description into a string for parsing
            $title = $formatted->Item->Title;
            $description = $formatted->Item->Description;
            $quantity = (($formatted->Item->Quantity)-($formatted->Item->QuantitySold));
            $price = $formatted->Item->CurrentPrice;
            $searchable = $title.' '.$description;
            $seller = $formatted->Item->Seller->UserID;
            $expiration = $formatted->Item->EndTime;
                        
            //Strip non-space punctuation from the string 
            $pattern = "/[[:punct:]^'-']/";
            $searchable = preg_replace($pattern,'',$searchable);
            
            $item = array(
                'id' => $id,
                'qty' => $quantity,
                'price' => $price,
                'match' => array(),
                'description' => $searchable,
                'seller' => $seller,
                'expires' => $expiration
                );
            //Append the paired ID, QTY, and price into an array for searching through
            $s_array[] = $item;
        }
    ////print_r($s_array);

    return ($s_array);
    }

    
    //Input: Description list from eBay titles
    //Does: Compares the values of the descriptions to the initial kw search,
    //      makes a call to the database to submit the value to the database
    //Output: Object containing: Item Name, Quantity, parts it matches, and prices
    function searchDescriptions($keys,$items){
        global $SEARCH_IDS,$META_EXISTS;

        //Loop through the description of each item and match the description on
        //key word.
		foreach ($items as $k => $item) {
            foreach($keys as $key){
				// strip non-alphanumeric chars just in case, because for ebay sometimes we use punctuated strings
				$key = preg_replace('/[^[:alnum:]]+/','',$key);

                if (stristr($item['description'],$key)) {

                    //Store to the 'match' array the value of the keyword it matched
                    $items[$k]['match'][] = $key;
					$item['seller'] = (string)$item['seller'];
                    
                    //If the value is matched, return the following
                    $contactid = getContact($item['seller'],'ebayid','id');
					if ($contactid) {
	                    $companyid = getContact($item['seller'],'ebayid','companyid');
					} else {
						// get default ebay companyid
                        $companyid = getCompany('eBay seller','name','id');
						// get contactid associated with this ebay seller id
                        $contactid = setContact($item['seller'], $companyid,'', '',$item['seller']);
                    }
                    $partid = getPartId($key);
                    if (!$partid) { continue; }

                    $metaid = logSearchMeta($companyid,false,'',$item['id']);
					// $META_EXISTS is a global var within logSearchMeta() to help us know if this same record
					// was already logged today; in this case, we do NOT want to duplicate results for this same
					// ebay item, so only insert market data if this item hasn't already been logged
					if (! $META_EXISTS) {
						$searchid = false;
						if (isset($SEARCH_IDS[$key])) { $searchid = $SEARCH_IDS[$key]; }
	                    insertMarket($partid, $item['qty'], $item['price'], false, false, $metaid, 'availability',$searchid);
					}
                }

				unset($items[$k]['description']);
            }
        }
        return $items;
    }


    //In: Items with match update, keyword array
    //Does: Reorders by the parts they match
    //Out: List of parameters by part
    function reorderByMatchedPart($keys,$pairs){
        //Create a key array with each of the
        foreach($keys as $k => $key){
            $part[$key];
            foreach($pairs as $pair){
                if (stristr($key, $pairs['match'])){
                    //Key: The Part Number
                    $part[$key][] = $pair;
                    //Secondary Key: Ebay Part Number
                    //Data: Price, Quantity
                }
            }
        }
        //print_r ($part);
    }
    
    //In: Ebay Seller ID
    //Does: Compares the ID against our database to make a record of the 
    //Out:

   
//============================================================================//
//=========================== Main Function ==================================//    
//============================================================================//
function ebay($search){
    //Take the input of the search parameter
//    $keywords = 'psx010,NT7E05JC,nt3x73a,PWPQ20EAXX,NT7E02PB';
    $kw_array = explode(',',$search);
    
    //print_r($kw_array);
    //echo("<br>");
    
    $KW_results = ebayKeyWordSearch($search);
    $id_list = ebayGetItemIDList($KW_results);
    $descArray = ebayGetDescriptions($id_list);
    $pairedItems = searchDescriptions($kw_array, $descArray);
    //print_r ($pairedItems);
    //reorderByMatchedPart($kw_array,$pairedItems);
}

//For Debugging Purposes
ebay('090-58022-01');

//============================================================================//
//============================Legacy Code=====================================//
//============================================================================//
/*
        //Iterate through the itemID's
        foreach($itemIDs as $object){
            
            //Grab the String from the text
            //echo(($object));
            $id = $object->object;
            //echo('Pulled the object, retrieved: '.$id.'<br>');
            ////echo (gettype($id));
            
            $singleItem .= '<GetSingleItemRequest xmlns="urn:ebay:apis:eBLBaseComponents">';
            $singleItem .= '<ItemID>'.'360718167667'.'</ItemID>';
            $singleItem .= '</GetSingleItemRequest>';

            $headers = array(
                'X-EBAY-API-APP-ID:Ventel-ventelin-PRD-03ebd57c2-4ce91f52',
                'X-EBAY-API-VERSION:963',
                'X-EBAY-API-SITE-ID:0',
                'X-EBAY-API-CALL-NAME:GetSingleItem',
                'X-EBAY-API-REQUEST-ENCODING:XML',
                'Content-Type: text/xml;charset=utf-8'
            );
            $call = curl_init();
            curl_setopt($call, CURLOPT_URL, 'http://open.api.ebay.com/shopping?');
            curl_setopt($call, CURLOPT_POST, true);
            curl_setopt($call, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($call, CURLOPT_POSTFIELDS, $singleItem);
            curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
            
            //Send and receive the request and receive the file
            $results = curl_exec($call);
            curl_close($call);
            ////echo gettype($results)."<br>";
            //echo($results);    

        }
*/        

/*---------Old function to rearrange the part numbers on order of their matches


*/
?>
