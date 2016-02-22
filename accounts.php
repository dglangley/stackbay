<?php
	include_once 'inc/dbconnect.php';
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

	<form class="form-inline">

    <table class="table table-header">
		<tr>
			<td class="col-md-2">
			</td>
			<td class="text-center col-md-6">
			</td>
			<td class="col-md-3">
				<div class="pull-right form-group">
					<select name="companyid" id="companyid" style="width:280px">
						<option value="">- Select a Company -</option>
					</select>
					<input class="btn btn-primary btn-sm" type="submit" value="Save Data">
				</div>
			</td>
		</tr>
	</table>

    <div id="pad-wrapper" class="datatables-page">
            
            <div class="row">
                <div class="col-md-12">

                    <table id="accounts">
                        <thead>
                            <tr>
                                <th tabindex="0" rowspan="1" colspan="1">Date
                                </th>
                                <th tabindex="0" rowspan="1" colspan="1">PO#
                                </th>
                                <th tabindex="0" rowspan="1" colspan="1">Amount
                                </th>
                                <th tabindex="0" rowspan="1" colspan="1">Balance
                                </th>
                                <th tabindex="0" rowspan="1" colspan="1">Age
                                </th>
                            </tr>
                        </thead>
                        
                        <tfoot>
                            <tr>
                                <th rowspan="1" colspan="1">Date</th>
                                <th rowspan="1" colspan="1">PO#</th>
                                <th rowspan="1" colspan="1">Amount</th>
                                <th rowspan="1" colspan="1">Balance</th>
                                <th rowspan="1" colspan="1">Age</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <td>1/16/2016</td>
                                <td>12345</td>
                                <td class="text-right">$ 350.00</td>
                                <td class="text-right">$ 350.00</td>
                                <td class="center">X</td>
                            </tr>
                            <tr>
                                <td>1/23/2016</td>
                                <td>23456</td>
                                <td class="text-right">$ 450.00</td>
                                <td class="text-right">$ 450.00</td>
                                <td class="center">X</td>
                            </tr>
						</tbody>
					</table>
				</div>
			</div>
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
