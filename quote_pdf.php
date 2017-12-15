<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

    //print "<pre>".print_r($_REQUEST,true)."</pre>";

    $submit_type = 'demand';

    if (isset($_REQUEST['submit_type']) AND ($_REQUEST['submit_type']=='availability' OR $_REQUEST['submit_type']=='demand')) { $submit_type = $_REQUEST['submit_type']; }

//    $companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
    $metaid = 0;
    if (isset($_REQUEST['metaid']) AND is_numeric($_REQUEST['metaid'])) { $metaid = trim($_REQUEST['metaid']); }

	$companyid = 0;
	$query = "SELECT companyid FROM search_meta WHERE id = '".res($metaid)."'; ";
	$result = qedb($query);
	if (mysqli_num_rows($result)>0) {
		$r = mysqli_fetch_assoc($result);
		$companyid = $r['companyid'];
	}
/*
    $contactid = 0;
    if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = trim($_REQUEST['contactid']); }

    $items = array();
    if (isset($_REQUEST['items']) AND is_array($_REQUEST['items'])) { $items = $_REQUEST['items']; }
*/

	$items = array();
	$query = "SELECT partid, ";
	if ($submit_type=='demand') { $query .= "quote_qty response_qty, quote_price response_price "; }
	else if ($submit_type=='availability') { $query .= "offer_qty response_qty, offer_price response_price "; }
	$query .= "FROM ".$submit_type." WHERE metaid = '".res($metaid)."'; ";
	$result = qedb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$items[$r['line_number']][] = $r;
	}

    $userid = 1;
    if ($U['id']) { $userid = $U['id']; }

    $display_str = '';
    $display_html = '';

	$total = 0;
	$ln = 1;
    foreach ($items as $i => $row) {
        if (! is_numeric($i)) { $i = 0; }//default in case of corrupt data

        $searchid = 0;
        $search_str = '';
        if (isset($_REQUEST['searches'][$i])) { $search_str = strtoupper(trim($_REQUEST['searches'][$i])); }

        if ($search_str AND $companyid) {
            $query = "SELECT id FROM searches WHERE search = '".$search_str."' AND userid = '".$userid."' ";
            $query .= "AND datetime LIKE '".$today."%' ORDER BY id DESC; ";//get most recent
            $result = qdb($query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $searchid = $r['id'];
            }
        }

/*
     //print "<pre>".print_r($row,true)."</pre>";
        $list_qty = 1;
        if (isset($_REQUEST['search_qtys'][$i]) AND is_numeric($_REQUEST['search_qtys'][$i]) AND $_REQUEST['search_qtys'][$i]>0) { $list_qty = trim($_REQUEST['search_qtys'][$i]); }
        $list_price = false;
        if (isset($_REQUEST['list_price'][$i])) { $list_price = trim($_REQUEST['list_price'][$i]); }

        $sellqty[$i] = array();
        if (isset($_REQUEST['sellqty'][$i]) AND is_array($_REQUEST['sellqty'][$i])) { $sellqty[$i] = $_REQUEST['sellqty'][$i]; }
        $sellprice[$i] = array();
        if (isset($_REQUEST['sellprice'][$i]) AND is_array($_REQUEST['sellprice'][$i])) { $sellprice[$i] = $_REQUEST['sellprice'][$i]; }

        $bid_qty[$i] = array();
        if (isset($_REQUEST['bid_qty']) AND is_array($_REQUEST['bid_qty'])) { $bid_qty = $_REQUEST['bid_qty']; }
        $bid_price[$i] = array();
        if (isset($_REQUEST['bid_price']) AND is_array($_REQUEST['bid_price'])) { $bid_price = $_REQUEST['bid_price']; }
*/

        $quote_str = '';
        $quote_html = '';

        foreach ($row as $n => $r) {
			$partid = $r['partid'];
            //defaults
/*
            $response_qty = 0;
            if ($submit_type=='availability') { $response_qty = $list_qty; }
            else if (isset($sellqty[$i][$n]) AND is_numeric($sellqty[$i][$n]) AND $sellqty[$i][$n]>0) { $response_qty = $sellqty[$i][$n]; }
            $response_price = false;
            // if (isset($sellprice[$i][$n])) { $response_price = $sellprice[$i][$n]; }
            if (isset($sellprice[$i][$n])) { $response_price = $sellprice[$i][$n]; }

            if ($submit_type=='availability' && isset($bid_qty[$i])) { $response_qty = $bid_qty[$i]; }
            if ($submit_type=='availability' && isset($bid_price[$i])) { $response_price = $bid_price[$i]; }
*/
			$response_qty = $r['response_qty'];
			$response_price = $r['response_price'];

			$total += ($response_qty*$response_price);

            if ($response_qty>0) {
                $quote_str .= ' qty '.$response_qty.'- '.getPart($partid).' '.format_price($response_price);
                if ($response_qty>1) { $quote_str .= ' ea'; }
                $quote_str .= chr(10);
                $quote_html .= '<tr><td class="text-left">'.($ln).'</td><td class="text-left">'.getPart($partid,'part').' '.getPart($partid,'heci').'</td>'.
                    '<td>'.$response_qty.'</td><td class="text-right">'.format_price($response_price).'</td>'.
                    '<td class="text-right">'.format_price($response_qty*$response_price).'</td></tr>';
            }
        }
		$ln++;
        if ($quote_str) {
            $display_str .= $search_str.chr(10).$quote_str;
            $display_html .= $quote_html;
        }
    }

	$display_html .= '<tr style=""><td class="text-left"></td><td class="text-left"></td>'.
		'<td></td><td class="text-right" style="padding-top:40px">TOTAL</td>'.
		'<td class="text-right" style="padding-top:40px">'.format_price($total,2).'</td></tr>';

    if ($display_str) {
        if ($submit_type=='demand') { $display_str = 'We have the following available:'.chr(10).chr(10).$display_str; }
        else if ($submit_type=='availability') { $display_str = 'I\'m interested in the following:'.chr(10).chr(10).$display_str; }

        $display_html = '<table class="table-full"><tr><th>Line#</th><th>Description</th>'.
            '<th>Qty</th><th>Unit Price</th><th>Ext Price</th></tr>'.$display_html.'</table>';
    }
?>

<!DOCTYPE html>

<html>
    <head>
        <style id="stndz-style"></style>
        <title>Sales Quote <?=$metaid;?></title>
        <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet"> 
        <style type="text/css">
            body{
                font-size:11px;
            }
            body, table, td {
                font-family: "Lato", sans-serif;
            }
            table {
                border-collapse: collapse;
                margin-bottom:40px;
            }
            table.table-condensed {
                margin:0px;
            }
            th {
                text-transform:uppercase;
                background-color:#eee;
            }
            td{
                padding:5px;
                vertical-align:top;
            }
            a {
                color:black;
                text-decoration:none;
            }
            ul {
                list-style-type: none;
                padding: 0;
            }
            .text-right {
                text-align:right;
            }
            .text-center {
                text-align: center;
            }
            .text-price {
                width:100px;
                text-align:right;
            }
            .description {
                font-size:6pt;
                color:#aaa;
            }
            .credit_memo {
                float: left; 
                margin-bottom: -35px;
                width: 50%;
            }
            .hidden {
                visibility: hidden;
            }
            .remove {
                display: none;
            }
            table.table-full {
                width:100%;
            }
            table.table-modified {
                width:75%;
            }
            table.table-striped tr:nth-child(even) td {
                background-color: #ffffff;
            }
            table.table-striped tr:nth-child(odd) td {
                background-color: #f9f9f9;
            }
            .half {
                width:50%;
            }
            body{
                /*margin:0.5in;*/
            }
            td{
                text-align:center;
            }
            #ps_bold{
                float:right;
                font-size:13pt;
                text-align:right;
                width:50%;
            }
            #ps_bold h3 {
                padding-top:0px;
                margin-top:0px;
            }
            #letter_head{
                margin-bottom:40px;
                font-size:9pt;
            }
            
            #vendor_add{
                font-size:11px;
                text-align:left;
            }
            .total td {
                background-color:#eee;
            }
            #spacer {
                width:100%;
                height:100px;
            }
        </style>
    </head>
    <body style="width:1024px; margin-left:auto; margin-right:auto">
        <div id="ps_bold">
            <h3>Sales Quote <?=$metaid;?></h3>
            <table class="table-full" id="vendor_add">
                <tbody><tr>
                    <th class="text-center">Company</th>
                </tr>
                <tr>
                    <td class="half">
                        <?=getCompany($companyid);?>
                    </td>
                </tr>
            </tbody></table>
        </div>
        
        <div id="letter_head"><b>
            <img src="https://www.stackbay.com/img/logo.png" style="width:1in;"><br>
            Ventura Telephone, LLC <br>
            3037 Golf Course Drive <br>
            Unit 2 <br>
            Ventura, CA 93003<br>
            (805) 212-4959
            </b>
        </div>

    <!-- Items Table -->
    <?=$display_html;?>
        
    <div id="footer">

<h4 style="margin-top:200px">Terms & Conditions</h4>
<p>By entering into a business transaction with Ventura Telephone LLC, you are agreeing to the following terms:</p>

<p>Ventura Telephone LLC ("VenTel") provides a limited warranty ("Warranty") against defects, as related to the functionality of the item, that occur within the established term of the Warranty, as described in the aforementioned Warranty options (Premium, Plus or Economy). The term of the Warranty begins on the date as printed on VenTel's invoice(s).</p>

<p>The Warranty also covers physical damage, only if discovered and reported to VenTel within five (5) business days from the delivery date, and so long as such claims can be established as pre-existing conditions prior to shipment. VenTel offers no insurance on damage to products during shipping transit, and VenTel is released from all liability of such damage to products after they leave VenTel's possession. Freight charges are not eligible for a credit or refund, or any other form of reimbursement, in the case a product is covered under this Warranty.</p>

<p>Software licensing or similar compatibility problems (ie, software/firmware version mismatch) are not covered under this Warranty. Products covered under the Warranty can be replaced, credited or refunded at VenTel's sole discretion. RMA or order cancellation requests not covered under the Warranty can, and will be, declined at VenTel's sole discretion, and such items remain billable in full (or subject to a Restocking Fee at VenTel's sole discretion). VenTel reserves the right to require documentation of equipment defect to determine Warranty eligibility.</p>

    </div>
    
    </body>
</html>
