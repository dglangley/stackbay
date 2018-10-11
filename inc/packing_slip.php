<?php
	
	include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/dictionary.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getRecords.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/locations.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
	include_once $_SERVER['ROOT_DIR'].'/inc/getFreight.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';

    include_once $_SERVER['ROOT_DIR'].'/inc/getOrder.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getPackageContents.php';	

    function getLN($order_number,$partid){
        //Grabs the line number
        $select = "SELECT line_number FROM sales_items WHERE so_number = $order_number AND partid = $partid;";
        $results = qdb($select);
        
        $line_number = '';
        if (mysqli_num_rows($results) > 0){
            $result = mysqli_fetch_assoc($results);
            $line_number = $result['line_number'];
        }
        return $line_number;
    }

    function packing_slip($packageid) {

        // get the current package number and contents
        $package = reset(getISOPackage($packageid));
        $packageContents = getPackageContents($packageid);

        $T = order_type($package['order_type']);

        $ORDER = getOrder($package['order_number'], $package['order_type']);

        $htmlRows = '
        <!DOCTYPE html>
        <html>
            <head>
                <style type="text/css">
                    body{
                        font-family: "Arial";
                        font-size:12px;
                    }
                    table{
                        border-collapse: collapse;
                        margin-bottom:30px;
                    }
                    td{
                        border:thin black solid;
                        padding:5px;
                    }
                    table{
                        width:100%;
                    }
                    #addresses td{
                        width:50%;
                    }
                    body{
                        margin:0.5in;
                    }
                    #ps_bold{
                        position: fixed;
                        top: 0;
                        width:100%;
                        font-size:14pt;
                        text-align:right;
                        font-family:helvetica;
                        font-size:16pt;
                    }
                    #letter_head{
                        position:fixed;
                        top: 0;
                        left: 0;
                        font-size:10pt;
                    }

                    #footer{
                        text-align:center;
                    }
                
                    
                </style>
            </head>
            <body>
                <div id = "letter_head"><b>
                    <img src="img/logo.png" style="width:1in;"></img><br>
                    Ventura Telephone, LLC <br>
                    3037 Golf Course Drive <br>
                    Unit 2 <br>
                    Ventura, CA 93003
                </b></div>
                <div id="ps_bold">
                    '.$T['abbrev'].'# '.$package['order_number'].' Box# '.$package['package_no'].'
                </div>
                <br>
                <div style="width:100%;height:60px;">&nbsp;</div>
                <!-- Shipping info -->

                <table id="addresses">
                    <tr>
                        <td>Bill To</td>
                        <td>Ship To</td>
                    </tr>
                    <tr>
                        <td>'.address_out($ORDER['bill_to_id']).'</td>
                        <td>'.address_out($ORDER['ship_to_id']).'</td>
                    </tr>
                </table>
                
                <!-- Freight Carrier -->
                <table id = "order-info">
                    <tr>
                        <td>Sales Rep</td>
                        <td>Freight Carrier</td>
                        <td>Customer PO</td>
                        '.(($ro_number)? "<td>Ticket #</td>" : "").'
                        <td width="30%">Public Notes</td>
                    </tr>
                    <tr>
                        <td>
                            '.getContact($ORDER['sales_rep_id']).' <br>
                            '.getContact($ORDER['sales_rep_id'],'id','phone').'<br>
                            '.getContact($ORDER['sales_rep_id'],'id','email').'
                        </td>
                        
                        <td>'.getFreight('carrier',$ORDER['freight_carrier_id'],'','name').' '.strtoupper(getFreight('services','',$ORDER['freight_services_id'],'method')).'</td>
                        <td>'.$ORDER['cust_ref'].'</td>
                        '.(($ro_number)? "<td>".$ro_number."</td>" : "").'
                        <td>'.$public_notes.'</td>
                    </tr>
                </table>
        ';

        $htmlRows .="
        <table>
            <tr>
                <td colspan = '2' style = 'border:none;'><b>
                    Box #".$package['package_no']."
                </b></td>
                <td colspan = '4' style = 'text-align:right; border:none;'><b>
                    ".(($package['tracking_no'])? "Tracking #: ".$package['tracking_no'] : '')."
                </b></td>
            </tr>
            <tr>
                <td>Ln#</td>
                <td>Part</td>
                <td>HECI</td>
                <td>Ref No</td>
                <td>Qty</td>
                <td>Serials</td>
            </tr>";
            
            foreach ($packageContents as $part => $item) {
                 $htmlRows .="
                            <tr>
                                <td>".getLN($order_number,$packageid)."</td>
                                <td>".$part."</td>
                                <td>".$info['info']['heci']."</td>
                                <td></td>
                                <td>".$info['qty']."</td>
                                <td>".$item['serial'][0]."</td>";
                $htmlRows .= "</tr>";
            }
        $htmlRows .= "
                </tr>
            </table>";

        $htmlRows .='<div id="footer">If you have any questions, please call us at (805)212-4959</div>
            </body>
        </html>';

        echo $htmlRows;

        die();

        return $htmlRows;
    }
