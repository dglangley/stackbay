<?php
	include_once 'inc/dbconnect.php';
	include_once 'inc/format_date.php';
	include_once 'inc/format_price.php';
	include_once 'inc/getCompany.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title>VMM Accounts Home</title>
	<?php
		include_once 'inc/scripts.php';
	?>
</head>
<body class="sub-nav">

	<?php include 'inc/navbar.php'; ?>

	<form class="form-inline" method="get" action="/accounts.php">

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-3">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" class="company-selector">
						<option value="">- Select a Company -</option>
					</select>
					<input class="btn btn-primary btn-sm" type="submit" value="Search">
				</div>
			</td>
		</tr>
	</table>


    <div id="pad-wrapper">

            <!-- orders table -->
            <div class="table-wrapper orders-table section">
                <div class="row head">
                    <div class="col-md-12">
                        <h4>Orders</h4>
                    </div>
                </div>

                <div class="row filter-block">
                    <div class="pull-right">
                        <div class="btn-group pull-right">
                            <button class="glow left large active">All</button>
                            <button class="glow middle large">Pending</button>
                            <button class="glow right large">Completed</button>
                        </div>
                        <input type="text" class="search order-search" id="accounts-search" placeholder="Order search..." autofocus />
                    </div>
                </div>

                <div class="row">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th class="col-md-2">
                                    Date
                                </th>
                                <th class="col-md-3">
                                    <span class="line"></span>
                                    Company
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Order#
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Total amount
                                </th>
                                <th class="col-md-2">
                                    <span class="line"></span>
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
<?php
	$companyid = 0;
	if (isset($_REQUEST['companyid']) AND is_numeric($_REQUEST['companyid']) AND $_REQUEST['companyid']>0) { $companyid = $_REQUEST['companyid']; }

	function getOrderTotal($orderid) {
		$total = 0;
		$query = "SELECT (qty*price) total FROM sales_items WHERE sales_orderid = '".res($orderid)."' GROUP BY sales_orderid; ";
		$result = qdb($query);
		if (mysqli_num_rows($result)>0) {
			$r = mysqli_fetch_assoc($result);
			$total = $r['total'];
		}
		return ($total);
	}

	$rows = '';
	$query = "SELECT * FROM sales_orders ";
	if ($companyid) { $query .= "WHERE companyid = '".$companyid."' "; }
	$query .= "ORDER BY datetime DESC, id DESC; ";
	$result = qdb($query);
	while ($r = mysqli_fetch_assoc($result)) {
		$amt = getOrderTotal($r['id']);

		$rows .= '
                            <!-- row -->
                            <tr>
                                <td>
                                    '.format_date($r['datetime'],'M j, Y').'
                                </td>
                                <td>
                                    <a href="#">'.getCompany($r['companyid']).'</a>
                                </td>
                                <td>
                                    <a href="#">'.$r['id'].'</a>
                                </td>
                                <td class="text-right">
                                    '.format_price($amt,true,' ').'
                                </td>
                                <td class="text-right">
                                    <span class="label label-success">Completed</span>
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
        });
    </script>

</body>
</html>
