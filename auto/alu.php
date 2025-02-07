<?php
	//The Alcatel Lucent script will parse through the results page of products
	//page by alcatel lucent. It will be fed a series of part numbers as a comma-
	//seperated string of searches, iterate through them, call a search function on
	//Alcatel Lucent's availibility, and return the part number, availibility,
	//price, and quantity.

	//Include all requisite files
    include_once $_SERVER["ROOT_DIR"]."/inc/dbconnect.php";
	include_once $_SERVER["ROOT_DIR"]."/inc/call_remote.php";
    include_once $_SERVER["ROOT_DIR"]."/inc/getPartId.php";
    include_once $_SERVER["ROOT_DIR"]."/inc/setContact.php";
    include_once $_SERVER["ROOT_DIR"]."/inc/setPart.php";
    include_once $_SERVER["ROOT_DIR"]."/inc/getContact.php";
    include_once $_SERVER["ROOT_DIR"]."/inc/getCompany.php";
    include_once $_SERVER["ROOT_DIR"]."/inc/logRemotes.php";
    include_once $_SERVER["ROOT_DIR"].'/inc/logSearchMeta.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/insertMarket.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
    
//=============================================================================
//---------------------------Connect to ALU's server---------------------------
//=============================================================================

	//Store the cookie file
	$cookiefile = $TEMP_DIR.'test-alu.txt';
	$cookiejarfile = $cookiefile;
	
	//Prepare the cURL session
	$ch = curl_init();

	//Initialize session
	$base = 'http://reuse.alcatel-lucent.com/ScoDotNet/Login.aspx';
	$res = call_remote($base,"",$cookiefile,$cookiejarfile,'GET',$ch);
	
	//Get the log in page
	$newDom = new domDocument;
	$newDom->loadHTML($res);
	$inputs = $newDom->getElementsByTagName('input');
	
	//Loop through the form inputs and make them parameters for the post call
	$params = array();
	foreach ($inputs as $input) {
		$params[$input->getAttribute('name')] = $input->getAttribute('value');
	}
	
	//Enter our Login parameters
	$params['Login'] = 'david@ven-tel.com';
	$params['PWord'] = 'vAlu2008!';
	$params['__CALLBACKLOADSCRIPT'] = 't';
	$params['__CALLBACKID'] = '';
	$params['__CALLBACKPARAM'] = '';
	$params['__EVENTVALIDATION'] = '';
	$params['__CALLBACKINDEX'] = '';

	//Make the post to the cookies file
	$res = call_remote($base,$params,$cookiefile,$cookiejarfile,'POST',$ch);
curl_close($ch);
die($res);

//=============================================================================
//---------------------------Run the search function---------------------------
//=============================================================================

	//Declare the constants to run in the search loop
	$friday = true;
	$ctl = 0;
	$params = array();
	$base = 'http://reuse.alcatel-lucent.com/ScoDotNet/Search.aspx?SearchData=%';
	
	//Grab the meta manufacture non-iteratively
	$manf = 'Alcatel-Lucent';
    $companyID = getCompany($manf,'name','id');
    $metaid = logSearchMeta($companyID,false,'','alu');

	//CLEI: _ctl0_PageContent_myProductInfoControl_CLEI
	//Comcode: _ctl0_PageContent_myProductInfoControl_Comcode
	//Name: _ctl0_PageContent_myProductInfoControl_DescAbbr
	//Descr: _ctl0_PageContent_myProductInfoControl_Descript
	//List Price: _ctl0_PageContent_myProductInfoControl_SuggPrice
	//Your Price: _ctl0_PageContent_myProductInfoControl_Price

	$product_url = 'http://reuse.alcatel-lucent.com/ScoDotNet/Product.aspx?ProductID=';
	for ($i=4151; $i<=4151; $i++) {
		$res = call_remote($product_url,$i,$cookiefile,$cookiejarfile,'GET',$ch);

		$dom = new domDocument;
		$dom->loadHTML($res);

		if (! $dom->getElementById('_ctl0_PageContent_myProductInfoControl_ProductInfoTable')) { continue; }
echo $product_url.$i.'<BR>';

		$clei = trim($dom->getElementById('_ctl0_PageContent_myProductInfoControl_CLEI')->nodeValue);
		$comcode = trim($dom->getElementById('_ctl0_PageContent_myProductInfoControl_Comcode')->nodeValue);
		$part = trim($dom->getElementById('_ctl0_PageContent_myProductInfoControl_DescAbbr')->nodeValue);
		$descr = trim($dom->getElementById('_ctl0_PageContent_myProductInfoControl_Descript')->nodeValue);
		$listprice = trim($dom->getElementById('_ctl0_PageContent_myProductInfoControl_SuggPrice')->nodeValue);
		$ourprice = trim($dom->getElementById('_ctl0_PageContent_myProductInfoControl_Price')->nodeValue);

		if ($dom->getElementById('_ctl0_PageContent_myProductInfoControl_NoInventoryTable')) {
			$noinv_table = $dom->getElementById('_ctl0_PageContent_myProductInfoControl_NoInventoryTable')->nodeValue;
			if (strstr($noinv_table,'There is no available inventory for this product.')) { continue; }
		}

	    $partid = getPartId($part,$clei);
echo $i.'. '.$part.':'.$clei.':'.$comcode.' '.$listprice.'/'.$ourprice.' = '.$partid.'<BR>';
echo $res.'<BR>';
echo '<BR>';
continue;
		if (! $partid) {
			$partid = setPart(array('part'=>$part,'heci'=>$clei,'manf'=>'','sys'=>'','descr'=>$descr));//,'manf'=>$manf,'sys'=>'','descr'=>$desc));
		}
	}
exit;


while ($friday){
	//While the search is still valid, loop through each page and store results


	//Call to the remote page, passing in blank params for the first iteration
	$res = call_remote($base,$params,$cookiefile,$cookiejarfile,'POST',$ch);
	
	//Convert the results into a DOM document for parsing's sake.
	$resDom = new domDocument;
	$resDom->loadHTML($res);
	
	//Find the inputs in order to get the hidden inputs of the events
	$inputs = $resDom->getElementsByTagName('input');
	
	//Reinitialize Parameters array for subsequent loops
	$params = array();
	
	//Set the event target post to the JS post function of the next page
	$ctl++;
	$eventTarget = '_ctl0$PageContent$mySearchControl$SearchGrid$_ctl54$_ctl'.$ctl;
	
	//Reinitialize the parameters
	foreach ($inputs as $input) {
		$params[$input->getAttribute('name')] = $input->getAttribute('value');
	}
	
	//Explicitly overwrite the event target function 
	$params["__EVENTTARGET"] = $eventTarget;
	
	//Use the Id value of the general table to navagate the DOM
	$id = '_ctl'.'0'.'_PageContent_mySearchControl_SearchGrid';
	$tables = $resDom->getElementById($id);

	//Explicit Failsafe: Make sure we don't burn their system
	if($ctl > 20){break;}

	//Store out rows from the table
	$rows = $tables->getElementsByTagName('tr');

	//Initialize the count for the new page for the sake of 'last_first' storage
	$count = 0;
	$check = '';
	
	//Loop through each row to parse out the values we want
	foreach ($rows as $row) {
		
		//Verify that we are not checking the first row, if not, enter statement
		if($row->getAttribute('style')!='background-color:#73737B;'){
			
			//Break out the data into rows
			$data = $row->nodeValue;
			$td = $row->getElementsByTagName('td');
			
			//Skip the last row
			if($td->length != 8){continue;}

			//Break out the row text into an array
			$datArr = preg_split('/[[:space:]]+/',$data);

			// find heci field, which is always preceded by "RE-USE" directly in front
			$heci_col = false;
			foreach ($datArr as $f => $field) {
				if ($field=='RE-USE') { $heci_col = $f+1; }
			}
			// heci not found in foreach() above
			if ($heci_col===false) { continue; }
			$part_col = $heci_col+3; 
                 
			//Grab the CLEI, PART NUMBER
			$heci = $datArr[$heci_col];
			$part = $datArr[$part_col];

			$desc = '';
			for($i = 6;$i<count($datArr)-2;$i++){
				$desc .= $datArr[$i].' ';
			}

			//Use the first value of the clei and the part in the row to confirm
			//that the table we pull is unique.
			if ($count == 0){
				$last_first = $heci.$part;
				$count++;
				if($last_first == $check){
					$friday = false;
					break;
				}
			}
			
			
			


			//Get the quantity element from the DOM
			$qty = $td->item(2);
			$q_path = $qty->getElementsByTagName('input')->item(0);
			$quantity = $q_path->getAttribute("value");
			$price = format_price($datArr[count($datArr)-2],true,'',true);

			//Check to see if the CLEI is a valid CLEI, if not, replace with ''
			if (preg_match('/^AL[0-9]{5}/',$heci)) {
				$heci = '';
			}

			//Debugging Value Verification
/*
			echo ('CLEI: '.$heci."<br>");
			echo ('Part #: '.$part."<br>");
			echo ('Price: '.$price."<br>");
			echo ('Quantity: '.$quantity."<br>");
			echo ('Description: '.$desc."<br>");
			echo("<br>");
*/
			
			//==================================================================
			//---------------------Iteratively Submit To DB---------------------
			//==================================================================

		    $partid = getPartId($part,$heci);
            //$res_err = insertMarket();
            if (! $partid) {
				$partid = setPart(array('part'=>$part,'heci'=>$heci,'manf'=>$manf,'sys'=>'','descr'=>$desc));
			}

			//dgl 11-18-16 added so that we can store *how* the supplier is posting their data, so when rfqing them
			//we can refer to their original posted search string instead of an alias they can't match
			$searchid = 0;
			if ($heci) {
				$heci7 = preg_replace('/[^[:alnum:]]+/','',substr($heci,0,7));
				// if not stored in our db, create the entry so we have record of their exact match
				if (! isset($SEARCH_IDS[$heci7]) OR ! $SEARCH_IDS[$heci7]) {
					logRemotes($heci7,$GLOBALS['REMDEF']);
				}
				$searchid = $SEARCH_IDS[$heci7];
			} else {
				$fpart = preg_replace('/[^[:alnum:]]+/','',$part);
				// if not stored in our db, create the entry so we have record of their exact match
				if (! isset($SEARCH_IDS[$fpart]) OR ! $SEARCH_IDS[$fpart]) {
					logRemotes($fpart,$GLOBALS['REMDEF']);
				}
				$searchid = $SEARCH_IDS[$fpart];
			}
            
            insertMarket($partid, $quantity, $price, false, false, $metaid, 'availability',$searchid);
		}
	}
	//Store the value of the check variable
	$check = $last_first;
}

?>
