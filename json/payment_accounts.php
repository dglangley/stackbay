<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/format_price.php';

	header("Content-Type: application/json", true);

	$orders_table = $_REQUEST['orders_table'];
	if ($orders_table=='purchases') { $orders_table = 'Purchase'; }
	else if ($orders_table=='sales') { $orders_table = 'Sale'; }
	else if ($orders_table=='repairs') { $orders_table = 'Repair'; }

    $order_number = $_REQUEST['order_number'];
    $q_ordernum = prep($order_number);

	$query = "";
    $invoice_items = array();
    $bill_items = array();
    $credit_items = array();

    $output = "";

    if($orders_table == 'Purchase') {
        $query = "SELECT * FROM bills i, bill_items t WHERE i.bill_no = t.bill_no AND i.po_number = $q_ordernum;";
        
        $result = qdb($query) OR die(qe().' '.$query);
    
        if(mysqli_num_rows($result) > 0){
            foreach ($result as $row) {
                if(!$bill_items[$row['bill_no']]){
                    $bill_items[$row['bill_no']] = 0.00;
                }
        	    $bill_items[$row['bill_no']] += $row['amount'] * $row['qty'];
            }
    	}
    } else if($orders_table == 'Sale' OR $orders_table=='Repair') {
        $query = "SELECT * FROM invoices i, invoice_items t WHERE i.invoice_no = t.invoice_no AND i.order_number = $q_ordernum AND i.order_type = '".$orders_table."';";
        $result = qdb($query) OR die(qe ().' '.$query);
    	while ($rows = mysqli_fetch_assoc($result)) {
        	$invoice_items[] = $rows;
        }

		if ($orders_table=='Sale') {
			$query = "SELECT * FROM sales_credits i, sales_credit_items t WHERE i.id = t.cid AND i.order_num = $q_ordernum AND i.order_type = '".$orders_table."'; ";//AND i.companyid = '".res(25)."';";
			$result = qdb($query) OR die(qe().' '.$query);
			while ($rows = mysqli_fetch_assoc($result)) {
				$credit_items[] = $rows;
			}
		}
    } else {
        //Future space for Returns or other forms
    }

    if(!empty($invoice_items)) {
        foreach($invoice_items as $radio_item):
        $output .= '<tr>
            <td><input type="radio" name="reference_button" value="invoice '.$radio_item['invoice_no'].'"></td>
            <td>Invoice</td>
            <td>'.$radio_item['invoice_no'].'</td>
            <td>'.format_price($radio_item['qty'] * $radio_item['amount']).'</td>
        </tr>';
        endforeach;
    }
    
    if(!empty($bill_items)) {
        foreach($bill_items as $bill_no => $total):
        $output .= '<tr>
            <td><input type="radio" name="reference_button" value="bill '.$bill_no.'" '.((count($bill_items)==1)?" CHECKED":"").'> </td>
            <td>Bill</td>
            <td>'.$bill_no.'</td>
            <td>'.format_price($total).'</td>
        </tr>';
        endforeach;
    } 

    if(!empty($credit_items)) {
        foreach($credit_items as $radio_item):
        $output .= '<tr>
            <td><input type="radio" name="reference_button" value="credit '.$radio_item['order_num'].'"></td>
            <td>Credit</td>
            <td>'.$radio_item['order_num'].'</td>
            <td>-'.format_price($radio_item['qty'] * $radio_item['amount']).'</td>
        </tr>';
        endforeach; 
    }

    $result = '<div class="row">
				    <div class="col-md-12">
					    <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sel</th>
                                    <th>Type</th>
                                    <th>Number</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>';
    $result .= $output;                     
                                
	$result .=	'			</tbody>
					    </table>
				    </div>
				</div>
				<!--Hidden Required Fields-->
				<div class="row" style="display: none;">
				    <div class="col-md-6">
				        <input class="form-control input-sm" type="text" name="accounting_page" value="true">
				    </div>
				    <div class="col-md-6">
				        <input class="form-control input-sm" type="text" name="'.strtolower(substr($orders_table,0,1)).'o_order" value="'.$order_number.'">
				    </div>
				</div>
            </div>';

	echo json_encode($result);
	exit;
?>
