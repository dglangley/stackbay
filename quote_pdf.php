<?php

    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/format_price.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getPart.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

    //print "<pre>".print_r($_REQUEST,true)."</pre>";

    $submit_type = 'demand';

    if (isset($_REQUEST['submit_type']) AND ($_REQUEST['submit_type']=='availability' OR $_REQUEST['submit_type']=='demand')) { $submit_type = $_REQUEST['submit_type']; }
//  if (isset($_REQUEST['save-availability'])) { $submit_type = 'availability'; }

    $companyid = setCompany();//uses $_REQUEST['companyid'] if passed in
    $searchlistid = 0;
    if (isset($_REQUEST['searchlistid']) AND is_numeric($_REQUEST['searchlistid'])) { $searchlistid = trim($_REQUEST['searchlistid']); }
    $contactid = 0;
    if (isset($_REQUEST['contactid']) AND is_numeric($_REQUEST['contactid'])) { $contactid = trim($_REQUEST['contactid']); }

    $items = array();
    if (isset($_REQUEST['items']) AND is_array($_REQUEST['items'])) { $items = $_REQUEST['items']; }

    $userid = 1;
    if ($U['id']) { $userid = $U['id']; }

    $display_str = '';
    $display_html = '';

    foreach ($items as $ln => $row) {
        if (! is_numeric($ln)) { $ln = 0; }//default in case of corrupt data

        $searchid = 0;
        $search_str = '';
        if (isset($_REQUEST['searches'][$ln])) { $search_str = strtoupper(trim($_REQUEST['searches'][$ln])); }

        if ($search_str AND $companyid) {
            $query = "SELECT id FROM searches WHERE search = '".$search_str."' AND userid = '".$userid."' ";
            $query .= "AND datetime LIKE '".$today."%' ORDER BY id DESC; ";//get most recent
            $result = qdb($query);
            if (mysqli_num_rows($result)>0) {
                $r = mysqli_fetch_assoc($result);
                $searchid = $r['id'];
            }
        }

     //print "<pre>".print_r($row,true)."</pre>";
        $list_qty = 1;
        if (isset($_REQUEST['search_qtys'][$ln]) AND is_numeric($_REQUEST['search_qtys'][$ln]) AND $_REQUEST['search_qtys'][$ln]>0) { $list_qty = trim($_REQUEST['search_qtys'][$ln]); }
        $list_price = false;
        if (isset($_REQUEST['list_price'][$ln])) { $list_price = trim($_REQUEST['list_price'][$ln]); }

        $sellqty[$ln] = array();
        if (isset($_REQUEST['sellqty'][$ln]) AND is_array($_REQUEST['sellqty'][$ln])) { $sellqty[$ln] = $_REQUEST['sellqty'][$ln]; }
        $sellprice[$ln] = array();
        if (isset($_REQUEST['sellprice'][$ln]) AND is_array($_REQUEST['sellprice'][$ln])) { $sellprice[$ln] = $_REQUEST['sellprice'][$ln]; }

        $bid_qty[$ln] = array();
        if (isset($_REQUEST['bid_qty']) AND is_array($_REQUEST['bid_qty'])) { $bid_qty = $_REQUEST['bid_qty']; }
        $bid_price[$ln] = array();
        if (isset($_REQUEST['bid_price']) AND is_array($_REQUEST['bid_price'])) { $bid_price = $_REQUEST['bid_price']; }

        $quote_str = '';
        $quote_html = '';

        foreach ($row as $n => $partid) {
            //defaults
            $response_qty = 0;
            if ($submit_type=='availability') { $response_qty = $list_qty; }
            else if (isset($sellqty[$ln][$n]) AND is_numeric($sellqty[$ln][$n]) AND $sellqty[$ln][$n]>0) { $response_qty = $sellqty[$ln][$n]; }
            $response_price = false;
            // if (isset($sellprice[$ln][$n])) { $response_price = $sellprice[$ln][$n]; }
            if (isset($sellprice[$ln][$n])) { $response_price = $sellprice[$ln][$n]; }

            if ($submit_type=='availability' && isset($bid_qty[$ln])) { $response_qty = $bid_qty[$ln]; }
            if ($submit_type=='availability' && isset($bid_price[$ln])) { $response_price = $bid_price[$ln]; }

            if ($response_qty>0) {
                $quote_str .= ' qty '.$response_qty.'- '.getPart($partid).' '.format_price($response_price);
                if ($response_qty>1) { $quote_str .= ' ea'; }
                $quote_str .= chr(10);
                $quote_html .= '<tr><td class="text-left">'.($ln+1).'</td><td class="text-left">'.getPart($partid,'part').' '.getPart($partid,'heci').'</td>'.
                    '<td>'.$response_qty.'</td><td class="text-right">'.format_price($response_price).'</td>'.
                    '<td class="text-right">'.format_price($response_qty*$response_price).'</td></tr>';
            }
        }
        if ($quote_str) {
            $display_str .= $search_str.chr(10).$quote_str;
            $display_html .= $quote_html;
        }
    }

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
        <title>Sales Quote <?=$_REQUEST['metaid'];?></title>
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
    <body>
        <div id="ps_bold">
            <h3>Sales Quote <?=$_REQUEST['metaid'];?></h3>
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

    </div>
    
    </body>
</html>