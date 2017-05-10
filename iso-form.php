<?php
    // Standard includes section (We really need to condense this in a way which makes sense)
    $rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/dictionary.php';
	include_once $rootdir.'/inc/getCompany.php';
    include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/getRecords.php';
	include_once $rootdir.'/inc/getContact.php';
	include_once $rootdir.'/inc/locations.php';
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/getFreight.php';
	include_once $rootdir.'/inc/form_handle.php';

	
	
	function getComments($invid) {
        $comment;
        
        $query = "SELECT * FROM inventory WHERE id = ". res($invid) .";";
        $result = qdb($query);
        
        if (mysqli_num_rows($result)>0) {
            $result = mysqli_fetch_assoc($result);
            $comment = $result['notes'];
        }
        
        return $comment;
    }
	

    // Grab the order number
    $order_number = prep(grab('on'));
    $datetime = grab('date');
    
    //Bunch of unneeded data for ISO need to rewrite this later as the performance is still fast and works
    $order = "SELECT * FROM sales_orders WHERE so_number = $order_number;";
    $items = "
    SELECT i.id, i.serial_no, i.partid, s.ship_date FROM sales_items as s, inventory as i WHERE s.so_number = $order_number AND i.sales_item_id = s.id ORDER BY s.ship_date ASC";

	$order_result = qdb($order);
	$items_results = qdb($items);

	$order_info = array();
	if (mysqli_num_rows($order_result) > 0){
	    $order_info = mysqli_fetch_assoc($order_result);
	}

    $item_info = array();
    while ($r = mysqli_fetch_assoc($items_results)) {
        $item_info[] = $r;
    }

    $approve_date = '';

    $comments = '';
    foreach ($item_info as $serial) {
        if(!$approve_date) {
            $approve_date = $serial['ship_date'];
        }
        $comments .= (getComments($serial['id']) ? getPart($serial['partid']) . ' (' . $serial['serial_no'] . ') : ' . getComments($serial['id']) . '<br>' : '');
    }

    $iso = array();

    $query = "SELECT * FROM iso WHERE so_number = $order_number;";
    $result = qdb($query);
    if (mysqli_num_rows($result) > 0){
        $iso = mysqli_fetch_assoc($result);
    }
?>

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
            td,th{
                border:thin black solid;
                padding:5px;
            }
            @media print{
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
                    right: 0;
                    font-size:14pt;
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
            }
            
        </style>
    </head>
    <body>
        <div>
            <div style="float: right;"><b>OUTBOUND QUALITY CHECKLIST</b></div>
            <div id = "letter_head"><b>
                <img src="img/logo.png" style="width:1in;"></img><br>
                Ventura Telephone, LLC <br>
                3037 Golf Course Drive <br>
                Unit 2 <br>
                Ventura, CA 93003
            </b></div>
        </div>
        <br><br><br>

        <div id = 'ps_bold'><b>SO#<?=grab('on');?></b></div>
        
        <div style="width:100%;height:60px;">&nbsp;</div>
        <!-- Shipping info -->
        <?php 
        if(getContact($order_info['contactid'])) {
            echo '<b>Customer:</b> ' . getCompany($order_info['companyid']) . '<br>';
        }
        ?>
        <b>Customer PO:</b> <?=$order_info['cust_ref']?><br><br>

        <table id="iso">
            <tr>
                <th>Checkpoint</th>
                <th colspan='3'>Check Mark Appropiate Column</th>
            </tr>
            <tr>
                <td></td>
                <td style="text-align: center;">Yes</td>
                <td style="text-align: center;">No</td>
                <td style="text-align: center;">N/A</td>
            </tr>
            <tr>
                <td>Does part number match customer PO requirements?</td>
                <td style="text-align: center;"><?=($iso['part'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['part'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['part'] == 'n/a' ? 'X' : '')?></td>
            </tr>
            <tr>
                <td>Does HECI/CLEI match customer PO requirements?</td>
                <td style="text-align: center;"><?=($iso['heci'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['heci'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['heci'] == 'n/a' ? 'X' : '')?></td>
            </tr>
            <tr>
                <td>Any visible cosmetic damage to unit(s)?</td>
                <td style="text-align: center;"><?=($iso['cosmetic'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['cosmetic'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['cosmetic'] == 'n/a' ? 'X' : '')?></td>
            </tr>
            <tr>
                <td>Any visible component damage to unit(s)?</td>
                <td style="text-align: center;"><?=($iso['component'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['component'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['component'] == 'n/a' ? 'X' : '')?></td>
            </tr>
            <tr>
                <td>All customer PO special requirements?</td>
                <td style="text-align: center;"><?=($iso['special_req'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['special_req'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['special_req'] == 'n/a' ? 'X' : '')?></td>
            </tr>
            <tr>
                <td>Is all ship to / contact info correct?</td>
                <td style="text-align: center;"><?=($iso['shipping_info'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['shipping_info'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['shipping_info'] == 'n/a' ? 'X' : '')?></td>
            </tr>
            <tr>
                <td>Appropriate transit time service level requirements?</td>
                <td style="text-align: center;"><?=($iso['transit_time'] == 'yes' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['transit_time'] == 'no' ? 'X' : '')?></td>
                <td style="text-align: center;"><?=($iso['transit_time'] == 'n/a' ? 'X' : '')?></td>
            </tr>
        </table>

        <table id="iso">
            <tr>
                <th>Comments</th>
            </tr>
            <tr>
                <td><?=($comments == '' ? 'None' : $comments);?></td>
            </tr>
        </table>
        <b>Approved by:</b> <?=getContact($U['contactid']);?> <?=format_date($approve_date);?>
        <br><br>

        <div id="footer">If you have any questions, please call us at (805)212-4959</div>
        <br><br>
        Form QF-8240<br>
        Approved by: SS <br>
    </body>
</html>
