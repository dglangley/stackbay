<?php
	$rootdir = $_SERVER['ROOT_DIR'];
	
	include_once $rootdir.'/inc/dbconnect.php';
	include_once $rootdir.'/inc/keywords.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/format_date.php';
	include_once $rootdir.'/inc/format_price.php';
	include_once $rootdir.'/inc/getCompany.php';
	include_once $rootdir.'/inc/getPart.php';
	include_once $rootdir.'/inc/pipe.php';
	include_once $rootdir.'/inc/getPipeIds.php';
	include_once $rootdir.'/inc/calcQuarters.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/terms.php';
	
	//=========================================================================================
	//==================================== FILTERS SECTION ====================================
	//=========================================================================================
	
	//Company Id is grabbed from the search field at the top, but only if one has been passed in
	$company_filter = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { 
		$company_filter = $_REQUEST['companyid']; 
	}
	//Report type is set to summary as a default. This is where the button functionality comes in to play
	if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
	else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }
	//This is saved as a cookie in order to cache the results of the button function within the same window
	setcookie('report_type',$report_type);
	//$report_type = 'detail';

	//For local testing
	$report_type = $_GET['report_type'];

	$order = '';
	if (isset($_REQUEST['order']) AND $_REQUEST['order']){
		$report_type = 'detail';
		$order = $_REQUEST['order'];
	}
	
	$keyword = '';
	$part_string = '';
/*
	if (isset($_REQUEST['part']) AND $_REQUEST['part']){
    	$keyword = $_REQUEST['part'];
	}
*/
	if (isset($_REQUEST['s']) AND $_REQUEST['s']) {
		$report_type = 'detail';
		$keyword = $_REQUEST['s'];
		$order = $keyword;
	}

	if ($keyword) {
    	$part_list = getPipeIds($keyword);
    	foreach ($part_list as $id => $array) {
    	    $part_string .= $id.',';
    	}
    	$part_string = rtrim($part_string, ",");
	}

	$startDate = '';
	if (isset($_REQUEST['START_DATE']) AND $_REQUEST['START_DATE']) {
		$startDate = format_date($_REQUEST['START_DATE'], 'm/d/Y');
	}
	//if no start date passed in, or invalid, set to beginning of previous month
	if (!$startDate) {
		$y = date("Y");
		// set date to beginning of the first month of the *previous* quarter
		$q = ceil(date("m")/3);
		$last_q = $q-1;
		if ($last_q==0) { $last_q = 4; $y -= 1; }
		$start_m = ($last_q*3)-2;
		//$startDate = format_date($today,'m/01/Y',array('m'=>-3));
		$startDate = format_date($y.'-'.$start_m.'-01','m/01/Y');
	}

	$endDate = date('m/d/Y');
	if (isset($_REQUEST['END_DATE']) AND $_REQUEST['END_DATE']){
		$endDate = format_date($_REQUEST['END_DATE'], 'm/d/Y');
	}

	$orders_table = 'sales';
	if (isset($_GET['orders_table']) AND $_GET['orders_table']=='purchases') { $orders_table = 'purchases'; }
?>

<!------------------------------------------------------------------------------------------->
<!-------------------------------------- HEADER OUTPUT -------------------------------------->
<!------------------------------------------------------------------------------------------->
<!DOCTYPE html>
<html>
<!-- Declaration of the standard head with Accounts home set as title -->
<head>
	<title>Accounts Home</title>
	<?php
		//Standard headers included in the function
		include_once $rootdir.'/inc/scripts.php';
	?>
</head>

<body class="sub-nav accounts-body">
	
	<?php include 'inc/navbar.php'; ?>
	<?php include_once 'inc/getRecords.php'; ?>
	<?php require_once 'modal/payments_account.php';  ?>

	<?php if($_REQUEST['payment']): ?>
		<div id="item-updated-timer" class="alert alert-success fade in text-center" style="position: fixed; width: 100%; z-index: 9999; top: 48px;">
		    <a href="#" class="close" data-dismiss="alert" aria-label="close" title="close">×</a>
		    <strong>Success!</strong> Payment has been updated.
		</div>
	<?php endif; ?>

	<!-- Wraps the entire page into a form for the sake of php trickery -->
	<form class="form-inline" method="get" action="/accounts.php">

    <table class="table table-header table-filter">
		<tr>
		<td class = "col-md-2">
		<?php //echo '<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR>' . $report_type . '<br>';?>
		    <div class="btn-group">
		        <button class="glow left large btn-radio<?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary" data-toggle="tooltip" data-placement="bottom" title="summary">
		        <i class="fa fa-ticket"></i>	
		        </button>
				<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
		        <button class="glow right large btn-radio<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail" data-toggle="tooltip" data-placement="bottom" title="details">
		        	<i class="fa fa-list"></i>	
	        	</button>
				<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
		    </div>
<!-- 			<div class="btn-group">
			        <button class="glow left large btn-radio <?php if ($orders_table=='sales') { echo ' active'; } ?>" type="submit" data-value="sales">Sales</button>
					<input type="radio" name="orders_table" value="sales" class="hidden"<?php if ($orders_table=='sales') { echo ' checked'; } ?>>
			        <button class="glow right large btn-radio<?php if ($orders_table=='purchases') { echo ' active'; } ?>" type="submit" data-value="purchases">Purchases</button>
			        <input type="radio" name="orders_table" value="purchases" class="hidden"<?php if ($orders_table=='purchases') { echo ' checked'; } ?>>
		    </div> -->
		</td>

		<td class = "col-md-3">
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY">
		            <input type="text" name="START_DATE" class="form-control input-sm" value="<?php echo $startDate; ?>">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
		        </div>
			</div>
			<div class="form-group">
				<div class="input-group datepicker-date date datetime-picker" data-format="MM/DD/YYYY" data-maxdate="<?php echo date("m/d/Y"); ?>">
		            <input type="text" name="END_DATE" class="form-control input-sm" value="<?php echo $endDate; ?>">
		            <span class="input-group-addon">
		                <span class="fa fa-calendar"></span>
		            </span>
			    </div>
			</div>
			<div class="form-group">
					<button class="btn btn-primary btn-sm" type="submit" ><i class="fa fa-filter" aria-hidden="true"></i></button>
					<div class="btn-group" id="dateRanges">
						<div id="btn-range-options">
							<button class="btn btn-default btn-sm">&gt;</button>
							<div class="animated fadeIn hidden" id="date-ranges">
						        <button class="btn btn-sm btn-default left large btn-report" type="button" data-start="<?php echo date("m/01/Y"); ?>" data-end="<?php echo date("m/d/Y"); ?>">MTD</button>
<?php
	$quarters = calcQuarters();
	foreach ($quarters as $qnum => $q) {
		echo '
				    			<button class="btn btn-sm btn-default center small btn-report" type="button" data-start="'.$q['start'].'" data-end="'.$q['end'].'">Q'.$qnum.'</button>
		';
	}

	for ($m=1; $m<=5; $m++) {
		$month = format_date($today,'M m/t/Y',array('m'=>-$m));
		$mfields = explode(' ',$month);
		$month_name = $mfields[0];
		$mcomps = explode('/',$mfields[1]);
		$MM = $mcomps[0];
		$DD = $mcomps[1];
		$YYYY = $mcomps[2];
		echo '
								<button class="btn btn-sm btn-default right small btn-report" type="button" data-start="'.date($MM."/01/".$YYYY).'" data-end="'.date($MM."/".$DD."/".$YYYY).'">'.$month_name.'</button>
		';
	}
?>
							</div><!-- animated fadeIn -->
						</div><!-- btn-range-options -->
					</div><!-- btn-group -->
			</div><!-- form-group -->
		</td>
		<td class="col-md-2 text-center">
			<h2 class="minimal"><?=ucwords($orders_table);?></h2>
			<a href="/accounts.php?report_type=summary&orders_table=<?=($orders_table == 'sales' ? 'purchases': 'sales');?>">Switch to <?=($orders_table == 'sales' ? 'Purchases': 'Sales');?></a>
			<input type="radio" name="orders_table" value="<?=$orders_table;?>" class="hidden" checked>
		</td>
		<td class="col-md-2 text-center">
			<input type="text" name="order" class="form-control input-sm" value ='<?php echo $order?>' placeholder = "Order #"/>
<!--
			<input type="text" name="part" class="form-control input-sm" value ='<?php echo $part?>' placeholder = 'Part/HECI'/>
-->
		</td>
		<td class="col-md-3">
			<div class="pull-right form-group">
			<select name="companyid" id="companyid" class="company-selector">
					<option value="">- Select a Company -</option>
				<?php 
				if ($company_filter) {echo '<option value="'.$company_filter.'" selected>'.(getCompany($company_filter)).'</option>'.chr(10);} 
				else {echo '<option value="">- Select a Company -</option>'.chr(10);} 
				?>
				</select>
					<button class="btn btn-primary btn-sm" type="submit" >
						<i class="fa fa-filter" aria-hidden="true"></i>
					</button>
			</div>
			</td>
		</tr>
	</table>
	<!-- If the summary button is pressed, inform the page and depress the button -->
	
<!------------------------------------------------------------------------------------>
<!---------------------------------- FILTERS OUTPUT ---------------------------------->
<!------------------------------------------------------------------------------------>
    <div id="pad-wrapper">
	
		<?php if ($company_filter) { echo '<h3 class="text-center minimal">'.getCompany($company_filter).'</h3>'; } ?>

		<div class="row filter-block">

		<br>
            <!-- orders table -->
            <div class="table-wrapper">

			<!-- If the summary button is pressed, inform the page and depress the button -->

<?php
	// format col widths based on content (company column, items detail, etc)
	// If there is a company declared, do not show the collumn for the company data. Set width by array
	if ($company_filter) {
		$footer_span2 = 2;
		if ($report_type=='summary') {
			$footer_span1 = 2;
			$widths = array(1,3,3,1,1,1,1,1);
		} else {
			$footer_span1 = 3;
			$widths = array(1,3,3,1,1,1,1,1);
		}
	} else {
		$footer_span2 = 2;
		if ($report_type=='summary') {
			$footer_span1 = 3;
			$widths = array(1,3,3,1,1,1,1,1);
		} else {
			$footer_span1 = 4;
			$widths = array(1,3,3,1,1,1,1,1);
		}
	}
	$c = 0;
?>
<!--================================================================================-->
<!--=============================   PRINT TABLE ROWS   =============================-->
<!--================================================================================-->
<?php
	//Establish a blank array for receiving the results from the table
	$results = array();
	$oldid = 0;
//	echo getCompany($company_filter,'id','oldid');
//	echo('Value Passed in: '.$company_filter);
	//If there is a company id, translate it to the old identifier
	if($company_filter != 0){$oldid = dbTranslate($company_filter, false);}
//	echo '<br>The value of this company in the old database is: '.$oldid;

	$rows = '';
	$total_pcs = 0;
	$total_amt = 0;
	$total_sub = 0;
	$total_cred = 0;
	$total_payments = 0;
	
	//Write the query for the gathering of Pipe data
	$record_start = $startDate;
	$record_end = $endDate;
	$result = getRecords($part_string,'','',$orders_table);

/*
	$query = "SELECT ";
    $query .= "s.so_date 'datetime', c.`id` 'cid', c.name, q.company_id 'company', ";
    $query .= "q.quantity 'qty', i.clei 'heci', q.inventory_id, i.part_number 'part', q.quote_id, q.price price ";
    $query .= "From inventory_inventory i, inventory_salesorder s, inventory_outgoing_quote q, inventory_company c ";
    $query .= "WHERE q.inventory_id = i.`id` AND q.quote_id = s.quote_ptr_id AND c.id = q.company_id AND q.quantity > 0 ";
   	if ($company_filter) { $query .= "AND q.company_id = '".$oldid."' "; }
   	if ($startDate) {
   		$dbStartDate = format_date($startDate, 'Y-m-d');
   		$dbEndDate = format_date($endDate, 'Y-m-d');
   		//$dbStartDate = date("Y-j-m", strtotime($startDate));
   		//$dbEndDate = date("Y-j-m", strtotime($endDate));
   		$query .= "AND s.so_date between CAST('".$dbStartDate."' AS DATE) and CAST('".$dbEndDate."' AS DATE) ";}
   	if ($order){ $query .= "AND q.quote_id = '".$order."' ";}
   	if ($part_string){ $query .= "AND i.id IN (".$part_string.") ";}
   	//if ($endDate) { $query .= "AND s.so_date <'".$endDate."'  ";}
    $query .= "Order By s.so_date DESC;";
	
##### UNCOMMENT IF THE DATA IS BEING PULLED FROM THE NEW DATABASE INSTEAD OF THE PIPE
	//$query = "SELECT * FROM sales_orders ";
	//if ($company_filter) { $query .= "WHERE companyid = '".$company_filter."' "; }
	//$query .= "ORDER BY datetime DESC, id DESC; ";
#####

//Search for the results. Leave the second parameter null if the pipe is not being used

	$result = qdb($query,'PIPE');
*/

	//The aggregation method of form processing. Take in the information, keyed on primary sort field,
	//will prepare the results rows to make sorting and grouping easier without having to change the results
	$summary_rows = array();

	//print_r($result);
	//if($report_type == 'summary'){
	    foreach ($result as $row){
            $id = $row['order_num'];
			if ($order AND $order<>$id) { continue; }

			$r['price'] = format_price($r['price'],true,'',true);
			$ext_amt = $row['price']*$row['qty'];
			$total_pcs += $row['qty'];
			$total_sub += $ext_amt;

            if(!array_key_exists($id, $summary_rows)){
                $summary_rows[$id] = array(
                    'date' => '',
                    'company' => '',
                    'items' => '',
                    'summed' => ''
                    );
            }

            //Query to get the credit per item
            //test for sales first
            $item_id = 0;
            if($orders_table == 'sales') {
	            $query = "SELECT id FROM sales_items WHERE partid = ".prep($row['partid'])." AND so_number = ".prep($id).";";
	            $result = qdb($query) OR die(qe().'<BR>'.$query);
                if (mysqli_num_rows($result)>0) {
                    $query_row = mysqli_fetch_assoc($result);
                    $item_id = $query_row['id'];
                }
	        }

	        $credit = 0;
	        $credit_total = 0;
	        if($item_id && $orders_table == 'sales') {
	            $query = "SELECT amount, SUM(amount) as total FROM sales_credit_items WHERE sales_item_id = ".prep($id).";";
	            $result = qdb($query) OR die(qe().'<BR>'.$query);
                if (mysqli_num_rows($result)>0) {
                    $query_row = mysqli_fetch_assoc($result);
                    $credit = $query_row['amount'];
                    $credit_total = $query_row['total'];
                }
	        } else if($orders_table == 'purchases') {
				// $query = "SELECT i.amount FROM bills b, bill_items i WHERE po_number = ".prep($id)." AND b.bill_no = i.bill_no AND i.partid = ".prep($row['partid']).";";
				// echo $query; 
	   //          $result = qdb($query) OR die(qe().'<BR>'.$query);
    //             if (mysqli_num_rows($result)>0) {
    //                 $query_row = mysqli_fetch_assoc($result);
    //                 $credit = $query_row['amount'];
    //                 //$credit_total = $query_row['total'];
    //             }
	        }

			$summary_rows[$id]['date'] = $row['datetime'];
			$summary_rows[$id]['cid'] = $row['cid'];
            $summary_rows[$id]['items'] += $row['qty'];
            $summary_rows[$id]['summed'] += $ext_amt;
            $summary_rows[$id]['company'] = $row['name'];
            $summary_rows[$id]['credit'] = ($credit_total == '' ? 0 : $credit_total);
            $summary_rows[$id]['partids'][] = ['partid' => $row['partid'], 'price' => $row['price'], 'qty' => $row['qty'], 'credit' => ($credit == '' ? 0 : $credit)];


        }

        //print_r($summary_rows);
        $init = true;

        foreach ($summary_rows as $id => $info) {
        	$invoice_no = 0;
	    	$paymentTotal = 0;

	    	if($item_id && $orders_table == 'sales') {
	         	//Grab the invoice number using the order number
				$query = "SELECT invoice_no FROM invoices WHERE order_number = ".prep($id).";";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
	            if (mysqli_num_rows($result)>0) {
	                $row = mysqli_fetch_assoc($result);
	                $invoice_no = $row['invoice_no'];
	            }
	        } else {
	        	//Grab the invoice number using the order number
				$query = "SELECT bill_no FROM bills WHERE po_number = ".prep($id).";";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
	            if (mysqli_num_rows($result)>0) {
	                $row = mysqli_fetch_assoc($result);
	                $invoice_no = $row['bill_no'];
	            }
	        }

            if($invoice_no && $orders_table == 'sales') {
            	$query = "SELECT amount FROM payments WHERE number = ".prep("Invoice" . $invoice_no).";";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
                if (mysqli_num_rows($result)>0) {
                    $row = mysqli_fetch_assoc($result);
                    $payments = $row['amount'];
                }
            } else if($invoice_no) {
            	$query = "SELECT amount FROM payments WHERE number = ".prep("Bill" . $invoice_no).";";
				$result = qdb($query) OR die(qe().'<BR>'.$query);
                if (mysqli_num_rows($result)>0) {
                    $row = mysqli_fetch_assoc($result);
                    $payments = $row['amount'];
                }
            }

            $query = 'SELECT * FROM payment_details WHERE order_number = '.prep($id).' AND order_type = "'.($orders_table == 'sales' ? 'so' : 'po').'";';
            //echo $query;
				$prows = qdb($query);
				$output = '
				<div class ="btn-group">
					<button type="button" class="dropdown-toggle" data-toggle="dropdown">
                      <span class="caret"></span>
                    </button>';
                    
					$output .= '<ul class="dropdown-menu">';
					if(mysqli_num_rows($prows) > 0){
						//echo $query;
						foreach ($prows as $payment) {
							$p_number = 0;
							$p_amount = 0;
							$p_type = '';
							$p_notes = '';
							$p_date = '';
							
							$query = 'SELECT * FROM payments p, payment_details d WHERE id = '.$payment['paymentid'].' AND paymentid = p.id;';
							$result = qdb($query) OR die(qe().' '.$query);

							if (mysqli_num_rows($result)>0) {
					        	$r = mysqli_fetch_assoc($result);
								$p_number = $r['number'];
								$p_amount = $r['amount'];
								$paymentTotal += $r['amount'];
								$p_type = $r['payment_type'];
								$p_notes = $r['notes'];
								$p_ref = $r['ref_type'].' '.$r['ref_number'];
								$p_date = format_date($r['date']);
					        }
							
							$output .= '
								<li>
									<a style="cursor: pointer" class="paid-data" data-date="'.$p_date.'" data-ref="'.$p_ref.'" data-notes="'.$p_notes.'" data-type="'.$p_type.'" data-number="'.$p_number.'" data-amount="'.$p_amount.'" data-orders_table="'.$orders_table.'" data-orders_number="'.$id.'" data-toggle="modal" data-target="#modal-payment">
										Payment #'.$payment['paymentid'].'
									</a>
								</li>';
						}
					}
					$output .= '<li>
						<a style="cursor: pointer" data-toggle="modal" class="new-payment" data-target="#modal-payment" data-orders_table="'.$orders_table.'" data-orders_number="'.$id.'">
							<i class="fa fa-plus"></i> Add New Payment
						</a>
						
						</li>';
                    $output .= "</ul>";
					$output .= "</div>";
					//echo $output;

					//Remove output for live
					//$output = '';

        	$rows .= '
                <tr>
                    <td>'.format_date($info['date'], 'M j, Y').'</td>';
                    if(!$company_filter){$rows .= '<td>'.$info['company'].'  <a href="/profile.php?companyid='.$info['cid'].'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></td>';}
            $rows .='
            		<td>'.$id.' <a href="/'.($orders_table == 'sales' ? 'SO':'PO').$id.'"><i class="fa fa-arrow-right" aria-hidden="true"></i></a></td>
                    <td class="text-right">'.format_price($info['summed']).'</td>
                    <td class="text-right">-'.format_price($info['credit']).'</td>
                    <td class="text-right">-'.format_price($paymentTotal).$output.'</td>
                    <td>'.terms_calc($id, $orders_table).'</td>
                    <td class="text-right">'.format_price($info['summed'] - $info['credit'] - $paymentTotal).'</td>
                </tr>
            ';

            $total_amt += ($info['summed'] - $info['credit'] - $paymentTotal);
            $total_payments += $paymentTotal;
            $total_cred += $info['credit'];

            //Add in the dropdown element into the accounts page
            $rows .= '<tr style="'.($report_type=='summary' ? 'display: none;' : '').'">
                <td colspan="12">
                <table class="table table-condensed commission-table">
                    <tbody>';
            if($init)
            	$rows .= '<tr>
                            <th class="col-md-4">Part Description</th>
                            <th class="col-md-2">Qty</th>
                            <th class="col-md-2 text-right">Price (ea)</th>
                            <th class="col-md-2 text-right">Ext Price</th>
                            <th class="col-md-2 text-right">Credits</th>
            		</tr>';

            $init = false;

            foreach($info['partids'] as $part) {
            	$rows .= '<tr>
            		<td>'.display_part(current(hecidb($part['partid'], 'id'))).'</td>
            		<td>'.$part['qty'].'</td>
            		<td class="text-right">'.format_price($part['price']).'</td>
            		<td class="text-right">'.format_price($part['price'] * $part['qty']).'</td>
            		<td class="text-right">-'.format_price($part['credit']).'</td>
            	</tr>';
            }

            $rows .= '</tbody>
                </table>
                </td>
            </tr>';
        }
// 	} else if ($report_type=='detail') {
// 		foreach ($result as $r){
// 			if ($order AND $order<>$r['order_num']) { continue; }

// 			$r['price'] = format_price($r['price'],true,'',true);

// 			//Set the amount to zero for the number of items and the total price
// 			$amt = 0;
// 			$num_items = 0;
		
// 			//Set the value of the company to the individual row if there is no company ID preset
// 			if (! $company_filter) {
// 				$company_col = '
//                                 <td>
// 	                                    <a href="#">'.$r['name'].'</a>
//                                 </td>
// 				';
// 			}
// 			$this_amt = $r['qty']*$r['price'];
// 			$amt += $this_amt;
// 			$num_items += $r['qty'];

// 			$total_pcs += $r['qty'];
// 			$total_amt += $amt;

// 			$qty_col = '
//                             <td>
//                                 '.$r['qty'].'
//                             </td>
// 			';
// 			$price_col = '
//                             <td class="text-right">
//                                 '.format_price($r['price']).'
//                             </td>
// 			';

// 			// legacy (pipe) support
// //			if (isset($r['part_number'])) { $r['part'] = $r['part_number']; }
// //			if (isset($r['clei'])) { $r['heci'] = $r['clei']; }

// 			$part = $r['part'];
// 			if (strlen($part)>40) { $part = substr($r['part'],0,38).'...'; }
// 			$descr = $part.' &nbsp; '.$r['heci'];
// 			$row = array('datetime'=>$r['datetime'],'company_col'=>$company_col,'id'=>$r['order_num'],'detail'=>$descr,'qty_col'=>$qty_col,'price_col'=>$price_col,'amt'=>$this_amt,'status'=>'<span class="label label-success">Completed</span>');
// 			$results[] = $row;
// 		}

		// foreach ($results as $r) {
		// 	$ln = '#';
		// 	if ($orders_table=='sales') { $ln = '/SO'.$r['id']; }
		// 	else if ($orders_table=='purchases') { $ln = 'PO'.$r['id']; }
		// 	$rows .= '
  //                           <!-- row -->
  //                           <tr>
  //                               <td>
  //                                   '.format_date($r['datetime'],'M j, Y').'
  //                               </td>
		// 						'.$r['company_col'].'
  //                               <td>
  //                                   <a href="'.$ln.'" target="_new">'.$r['id'].'</a>
  //                               </td>
  //                               <td>
  //                                   '.$r['detail'].'
  //                               </td>
		// 						'.$r['qty_col'].'
		// 						'.$r['price_col'].'
  //                               <td class="text-right">
  //                                   '.format_price($r['amt'],true,' ').'
  //                               </td>

  //                           </tr>
		// 	';
		// }
// 	}/* end $report_type=='detail' */

	if ($keyword) {
		echo '
	<div class="alert alert-warning text-center">
		<i class="fa fa-'.$alert_icon.' fa-2x"></i>
		Results limited to "'.$keyword.'"
	</div>
		';
	}
?>


	<!-- Declare the class/rows dynamically by the type of information requested (could be transitioned to jQuery) -->
                <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    Date 
                                    <br>

                                </th>
<?php if (! $company_filter) { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Company
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Order#
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Subtotal
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Credits
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Payments
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                	<span class="line"></span>
                                    Due Date
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?> text-right">
                                	<span class="line"></span>
                                    Amount Due
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                        	<?php echo $rows; ?>
							<?php if ($rows) { ?>
                            <!-- row -->
                            <tr class="nohover" style="background: #EEE;">
                            	<td colspan=""> </td>
                            	<td colspan=""> </td>
                            	<td colspan="" class="text-right"><strong><?php echo format_price($total_sub,true,' '); ?></strong></td>
                            	<td colspan="" class="text-right"><strong>-<?php echo format_price($total_cred,true,' '); ?></strong></td>
                            	<td colspan="" class="text-right"><strong>-<?php echo format_price($total_payments,true,' '); ?></strong></td>
                                <td><strong></strong></td>
                                <td class="text-right" colspan="<?php echo $footer_span2; ?>">
                                    <strong><?php echo format_price($total_amt,true,' '); ?></strong>
                                </td>
                            </tr>
							<?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end orders table -->


	</div>
	</form>
<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">

    (function($){
    	$(document).on("change", ".payment-type", function() {
			var placeholder = '';
			
			if($(this).val() == "Check") {
				placeholder = "Check #";
			} else if($(this).val() == "Wire Transfer") {
				placeholder = "Ref #";
			} else if($(this).val() == "Credit Card") {
				placeholder = "Appr Code";
			} else if($(this).val() == "Paypal") {
				placeholder = "Transaction #";
			} else {
				placeholder = "Other";
			}
			
            $('.payment-placeholder').attr('placeholder', placeholder);
        });
        
        $(document).on("click", ".paid-data", function() {
			var number = $(this).data('number');
			var amount = $(this).data('amount');
			var type = $(this).data('type');
			var ref = $(this).data('ref');
			var notes = $(this).data('notes');
			var date = $(this).data('date');

			var order_number = $(this).data('orders_number');
			var orders_table = $(this).data('orders_table');
			$('select[name="payment_type"]').val(type);
			$('input[name="payment_ID"]').val(number);
			$('input[name="payment_amount"]').val(amount);
			$('input[name="payment_date"]').val(date);
			
			$('textarea[name="notes"]').val(notes);

			$('.payment-data').empty();
			
			$('input[name="reference_button"][value="' + ref + '"]').prop('checked', true);
			$.ajax({
				type: "POST",
				url: '/json/payment_accounts.php',
				data: {
					"orders_table": orders_table,
					"order_number": order_number,
				}, // serializes the form's elements.
				dataType: 'json',
				success: function(result) {
					$('.payment-data').append(result);
				},					
				error: function(xhr, status, error) {
					console.log("JSON | Initial table load | order table out.php: "+error);
				}
			});
        });
        
        $(document).on("click", ".new-payment", function() {

        	var order_number = $(this).data('orders_number');
			var orders_table = $(this).data('orders_table');

			$('select[name="payment_type"]').val('Wire Transfer');
			$('input[name="payment_ID"]').val('');
			$('input[name="payment_amount"]').val('');
			$('input[name="payment_date"]').val($('input[name="payment_date"]').data('date'));
			
			$('textarea[name="notes"]').val('');

			$('.payment-data').empty();
			
			$('input[name="reference_button"]').prop('checked', false);

			$.ajax({
				type: "POST",
				url: '/json/payment_accounts.php',
				data: {
					"orders_table": orders_table,
					"order_number": order_number,
				}, // serializes the form's elements.
				dataType: 'json',
				success: function(result) {
					$('.payment-data').append(result);
				},					
				error: function(xhr, status, error) {
					console.log("JSON | Initial table load | order table out.php: "+error);
				}
			});
        });

        $('#item-updated-timer').delay(1000).fadeOut('fast');
        
    })(jQuery);
/*

        $(document).ready(function() {
			$('.btn-report').click(function() {
				var btnValue = $(this).data('value');
				$(this).closest("div").find("input[type=radio]").each(function() {
					if ($(this).val()==btnValue) { $(this).attr('checked',true); }
				});
			});
        });
*/
    </script>

</body>
</html>
