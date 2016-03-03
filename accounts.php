<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getCompany.php';
	include_once 'inc/getPart.php';

	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { $companyid = $_REQUEST['companyid']; }
	$report_type = 'summary';
	if (isset($_REQUEST['report_type']) AND ($_REQUEST['report_type']=='summary' OR $_REQUEST['report_type']=='detail')) { $report_type = $_REQUEST['report_type']; }
	else if (isset($_COOKIE['report_type']) AND ($_COOKIE['report_type']=='summary' OR $_COOKIE['report_type']=='detail')) { $report_type = $_COOKIE['report_type']; }

	setcookie('report_type',$report_type);
?>
<!DOCTYPE html>
<html>
<head>
	<title>VMM Accounts Home</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="sub-nav accounts-body">

	<?php include 'inc/navbar.php'; ?>

	<form class="form-inline" method="get" action="/accounts.php">

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
                <input type="text" class="search order-search" id="accounts-search" placeholder="Order#, Part#, HECI..." autofocus />
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-3">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
<?php if ($companyid) { echo '<option value="'.$companyid.'" selected>'.getCompany($companyid).'</option>'.chr(10); } else { echo '<option value="">- Select a Company -</option>'.chr(10); } ?>
					</select>
					<input class="btn btn-primary btn-sm" type="submit" value="Go">
				</div>
			</td>
		</tr>
	</table>


    <div id="pad-wrapper">

            <!-- orders table -->
            <div class="table-wrapper">
<?php if ($companyid) { ?>
                <div class="row head text-center">
                    <div class="col-md-12">
                        <h2><?php echo getCompany($companyid); ?></h2>
                    </div>
                </div>
<?php } ?>

                <div class="row filter-block">
                    <div class="col-md-6">
                        <div class="btn-group">
                            <button class="glow left large btn-report<?php if ($report_type=='summary') { echo ' active'; } ?>" type="submit" data-value="summary">Summary</button>
							<input type="radio" name="report_type" value="summary" class="hidden"<?php if ($report_type=='summary') { echo ' checked'; } ?>>
                            <button class="glow right large btn-report<?php if ($report_type=='detail') { echo ' active'; } ?>" type="submit" data-value="detail">Detail</button>
							<input type="radio" name="report_type" value="detail" class="hidden"<?php if ($report_type=='detail') { echo ' checked'; } ?>>
                        </div>
					</div>
                    <div class="col-md-6 text-right">
                        <div class="btn-group pull-right">
                            <button class="glow left large active">All</button>
                            <button class="glow middle large">Pending</button>
                            <button class="glow right large">Completed</button>
                        </div>
                    </div>
                </div>

<?php
	// format col widths based on content (company column, items detail, etc)
	if ($companyid) {
		if ($report_type=='summary') {
			$widths = array(3,3,2,2,2);
		} else {
			$widths = array(2,2,5,2,1);
		}
	} else {
		if ($report_type=='summary') {
			$widths = array(2,4,2,1,2,1);
		} else {
			$widths = array(2,3,1,4,1,1);
		}
	}
	$c = 0;
?>

                <div class="row">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                            <tr>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    Date
                                </th>
<?php if (! $companyid) { ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Company
                                </th>
<?php } ?>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Order#
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Items
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Total amount
                                </th>
                                <th class="col-md-<?php echo $widths[$c++]; ?>">
                                    <span class="line"></span>
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
<?php
	$results = array();
	$query = "SELECT * FROM sales_orders ";
	if ($companyid) { $query .= "WHERE companyid = '".$companyid."' "; }
	$query .= "ORDER BY datetime DESC, id DESC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$amt = 0;
		$num_items = 0;

		$company_col = '';
		if (! $companyid) {
			$company_col = '
                                <td>
                                    <a href="#">'.getCompany($r['companyid']).'</a>
                                </td>
			';
		}

		$query2 = "SELECT qty, price, partid FROM sales_items WHERE sales_orderid = '".$r['id']."'; ";
		$result2 = qdb($query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$this_amt = $r2['qty']*$r2['price'];
			$amt += $this_amt;
			$num_items += $r2['qty'];

			if ($report_type=='detail') {
				$descr = getPart($r2['partid'],'part').' &nbsp; '.getPart($r2['partid'],'heci');
				$row = array('datetime'=>$r['datetime'],'company_col'=>$company_col,'id'=>$r['id'],'detail'=>$descr,'amt'=>$this_amt,'status'=>'<span class="label label-success">Completed</span>');
			}
		}

		if ($report_type=='summary') {
			$row = array('datetime'=>$r['datetime'],'company_col'=>$company_col,'id'=>$r['id'],'detail'=>$num_items,'amt'=>$amt,'status'=>'<span class="label label-success">Completed</span>');
		}

		$results[] = $row;
	}

	$rows = '';
	foreach ($results as $r) {
		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.format_date($r['datetime'],'M j, Y').'
                                </td>
								'.$r['company_col'].'
                                <td>
                                    <a href="#">'.$r['id'].'</a>
                                </td>
                                <td>
                                    '.$r['detail'].'
                                </td>
                                <td class="text-right">
                                    '.format_price($r['amt'],true,' ').'
                                </td>
                                <td class="text-right">
									'.$r['status'].'
                                </td>
                            </tr>
		';
	}
?>
							<?php echo $rows; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- end orders table -->


	</div>
	</form>

<?php include_once 'inc/footer.php'; ?>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#accounts').dataTable({
                "sPaginationType": "full_numbers"
            });
			$('.btn-report').click(function() {
				var btnValue = $(this).data('value');
				$(this).closest("div").find("input[type=radio]").each(function() {
					if ($(this).val()==btnValue) { $(this).attr('checked',true); }
				});
			});
        });
    </script>

</body>
</html>
