<html>
    <head>
    	<title>VMM Accounts Home</title>
    	<?php
    		include_once $rootdir.'inc/scripts.php';
    	?>
    </head>
    <body class="sub-nav accounts-body">
    
    	<?php include $rootdir.'inc/navbar.php'; ?>
    
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
    </body>
</html>
<?php
    $rootdir = getenv('Home');
	include_once $rootdir.'inc/dbconnect.php';
    include_once $rootdir.'inc/pipe.php';
	include_once $rootdir.'inc/format_price.php';
    
    
    $query = "SELECT ";
    $query .= "s.so_date 'date', i.id 'inventory', c.`id` 'company', c.name 'company_name', q.company_id, ";
    $query .= "q.quantity 'qty', i.clei, q.inventory_id, i.part_number, q.quote_id 'order', q.price price ";
    $query .= "From inventory_inventory i, inventory_salesorder s, inventory_outgoing_quote q, inventory_company c ";
    $query .= "WHERE q.inventory_id = i.`id` AND q.quote_id = s.quote_ptr_id AND c.id = q.company_id ";
    $query .= "Order By s.so_date DESC;";
    
    $result = qdb($query,'PIPE');
    
    echo "<br><br><br><br><br><br><br>".$query;
    echo "
        <table>
            <tr>
                <td class = 'Date'> Date </td>
                <td class = 'Comp'> Company </td>
                <td class = 'Order'> Order # </td>
                <td class = 'Qty'> Qty. </td>
                <td class = 'Item Qty'> Item Qty. </td>
                <td class = 'Ind_Price'> Individual Price </td>
                <td class = 'Tot_Price'> Total Price </td>
                <td class = 'Status'> Status </td>
            </tr>
    ";

echo "<br><br>";
print_r($result);
foreach ($result as $row){
    echo "<br>";
    $date = $row['date'];
    $company = $row['company_name'];
    $clei = $row['clei'];
    $order = $row['order'];
    $price = format_price($row['price']);
    $qty = $row['qty'];
    $part = $row['part_number'];
    $total = $qty * $price;
    
    echo "  
            <tr>
                <td class = 'Date'> $date </td>
                <td class = 'Comp'> $company </td>
                <td class = 'Order'> $order </td>
                <td class = 'Qty'> $qty </td>
                <td class = 'Item Qty'> $qty </td>
                <td class = 'Ind_Price'> $price </td>
                <td class = 'Tot_Price'> $total </td>
                <td class = 'Status'> $status </td>
            </tr>
            ";
}
?>