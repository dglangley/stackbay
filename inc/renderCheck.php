<?php
    // include dompdf autoloader
    include_once $_SERVER['ROOT_DIR'].'/dompdf/autoload.inc.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/dbconnect.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/format_date.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getAddresses.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getCompany.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getPart.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/keywords.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getContact.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/locations.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/format_address.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/format_price.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getCarrier.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getFreightService.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getFreight.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getWarranty.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getCondition.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/order_type.php';
    include_once $_SERVER['ROOT_DIR'].'/inc/getDisposition.php';

    $TOTAL = 0;

    function getBillInfo($order_number, $T) {
        $bill = array();

        $query = "SELECT *, (SUM(i.qty * i.amount)) total FROM ".$T['orders']. " o, ".$T['items']." i WHERE i.".$T['order']." = ".fres($order_number)." AND i.bill_no = o.bill_no GROUP BY o.bill_no;";
        $result = qedb($query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);
            $bill = $r;
        }

        return $bill;
    }

    function getOrderAddress($companyid) {
        $addressid = '';

        $query = "SELECT addressid FROM company_addresses WHERE companyid = ".fres($companyid)." ORDER BY id ASC LIMIT 1;";
        $result = qedb($query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);

            $addressid = $r['addressid'];
        }

        return $addressid;
    }

    function buildStubTable($payments, $T, $check_date, $company_name) {
        $totalPayment = 0;

        $html = '<h4 style="margin-bottom: 10px; margin-left: 0px; font-size: 14px;">VENTURA TELEPHONE LLC</h4>';
        $html .= '<div class="table-container" style="height: 262px;">';
        $html .= '<table style="width: 700px; margin: 0 auto;">';

        $html .= '   <tr>';
        $html .= '       <th scope="colgroup" colspan="3" class="text-center">'.$company_name.'</th>';
        $html .= '       <td colspan="2"></td>';
        $html .= '       <th scope="colgroup" rowspan="1" class="text-right">'.$check_date.'</th>';
        $html .= '       <td colspan="1"></td>';
        $html .= '   </tr>';

        $html .= '   <tr>';
        $html .= '       <th scope="col" class="text-left">Date</th>';
        $html .= '       <th scope="col" class="text-left">Type</th>';
        $html .= '       <th scope="col" class="text-left">Reference</th>';
        $html .= '       <th scope="col" class="text-right">Original Amt.</th>';
        $html .= '       <th scope="col" class="text-right">Balance Due</th>';
        $html .= '       <th scope="col" class="text-right">Discount</th>';
        $html .= '       <th scope="col" class="text-right">Payment</th>';
        $html .= '   </tr>';

        foreach($payments as $line) {
            // Get the invoice number of the bill to post as the reference#
            $bill = getBillInfo($line['ref_number'], $T);
            $html .= '<tr>';
            $html .= '  <td>'.format_date($bill['date_created']).'</td>';
            $html .= '  <td>'.$line['ref_type'].'</td>';
            $html .= '  <td>'.$bill['invoice_no'].'</td>';
            $html .= '  <td class="text-right">'.number_format($bill['total'], 2).'</td>';
            $html .= '  <td class="text-right">'.number_format($bill['total'], 2).'</td>';
            // Future discounts column here
            $html .= '  <td class="text-right"></td>';
            $html .= '  <td class="text-right">'.number_format($line['amount'], 2).'</td>';
            $html .= '</tr>';

            $totalPayment += $bill['total'];
        }

        $html .= '<tr>';
        $html .= '  <td colspan="5"></td>';
        $html .= '  <td>Check Amount</td>';
        $html .= '  <td class="text-right">'.number_format($totalPayment, 2).'</td>';
        $html .= '</tr>';

        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div style="width: 700px; margin: 0 auto;">';
        $html .= '  <p>Chase Checking 8883<span class="pull-right">'.number_format($totalPayment, 2).'</span></p>';
        $html .= '</div>';

        return $html;

    }

    function getPaymentDetails($paymentid) {
        global $TOTAL;
        $payments = array();

        $query = "SELECT * FROM payments, payment_details WHERE id = ".fres($paymentid)." AND id = paymentid;";
        $result = qedb($query);

        while($r = mysqli_fetch_assoc($result)) {
            $payments[] = $r;

            if($r['ref_type'] == 'Bill') {
                $TOTAL += $r['amount'];
            } else if ($r['ref_type'] == 'Credit') {
                $TOTAL -= $r['amount'];
            }
        }

        return $payments;
    }

    function renderCheck($paymentid) {
        global $TOTAL;

        $payments = getPaymentDetails($paymentid);

        // Get the first payment 
        $payment = reset($payments);

        $T = order_type($payment['ref_type']);

        // Build out the stub details
        $stubHTML = buildStubTable($payments, $T, format_date($payment['date']), getCompany($payment['companyid']));

        $total = 0;

        $remit_to_id = getOrderAddress($payment['companyid']);
        
        $html_page_str = '
<!DOCTYPE html>
<html>
    <head>
        <title>'.$T['abbrev'].'# '.$bill['bill_no'].'</title>
        <link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet"> 
        <style type="text/css">
            body {
                font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
                font-size: 12px;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .text-left {
                text-align: left;
            }

            .pull-right {
                float: right;
            }

            .pull-left {
                float: left;
            }

            th {
                font-weight: normal;
            }

            tr {
                line-height: 11.5px;
            }

            .check-container {
                margin-top: 80px;
                margin-bottom: 17px;
            }

            @media print {
              @page { margin: 0; }
              body { margin: 1.2cm; }
            }
        </style>
    </head>
    <body>';

        // Generate the check here
        $html_page_str .= '
            <div class="check-container">
                <div class="cdate-box text-right" style="margin-right: 55px; margin-top: -5px; margin-bottom: 5px;">
                    '.format_date($payment['date']).'
                </div>
                <br>
                <div class="amount-nbr-box pull-right" style="margin-right: 45px; margin-top: 15px;">
                    **'.number_format($TOTAL,2,'.','').'
                </div>
                <div class="pay-to-box" style="margin-left: 70px; margin-top: 20px;">
                    '.getCompany($payment['companyid']).'
                </div>
                <br>
                <div class="amount-txt-box" style="margin-left: 0px; margin-top: 2px;">
                    '.(convertNumber($TOTAL)).'
                </div>
                <br>
                <div class="pay-to-address-box">
                    <pre style="margin-top: 5px; margin-left: 60px;">'.address_out($remit_to_id).'</pre>
                </div>
                <div class="check-1-memo-box" style="margin-top: 25px; margin-left: 55px;">
                    '.(count($payments) ==1 ? $payment['ref_number'].'-E' : '12345-E').'
                </div>
        ';

        $html_page_str .= '
            </div>
            <br>
            <br>
            ';
        // Generate 2 stubs
        $html_page_str .= $stubHTML;  
        $html_page_str .= $stubHTML;        
        $html_page_str .= '        
            </body>
        </html>
             ';


        return ($html_page_str);
    }