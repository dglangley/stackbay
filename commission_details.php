<?php 

    //Must have db file otherwise site will break
    require_once 'inc/dbconnect.php';
    require_once 'inc/getContact.php';
    require_once 'inc/user_access.php';
    require_once 'inc/user_edit.php';
    require_once 'inc/format_price.php';
    require_once 'inc/getCompany.php';
    
    function getSalesOrders() {
        $query = '';
        $sales = array();
        
        $query = "SELECT * FROM sales_orders;";
        $result = qdb($query) OR die(qe().' '.$query);
        
        while ($row = $result->fetch_assoc()) {
		  $sales[] = $row;
		}
        
        return $sales;
    }
    
    function getTotalCost($so_number) {
        $cost = 0;
        
        $query = "SELECT SUM(price) as total FROM sales_items WHERE so_number = $so_number;";
        $result = qdb($query) OR die(qe().' '.$query);
        
        if (mysqli_num_rows($result)>0) {
        	$r = mysqli_fetch_assoc($result);
			$cost = ($r['total'] ? $r['total'] : '0.00');
        }
        
        return $cost;
    }
    
    $userid = $_REQUEST['user'];
    
    $venEdit = new VenEdit;
     
    $orders = getSalesOrders();
    //Quick and dirty get all users under admin and usernames
    $usernames = $venEdit->getAllSalesUsers();
    
?>
<!DOCTYPE html>
<html class="login-bg">
<head>
	<title>Admin - User Commission Details</title>
    <?php
        include_once 'inc/scripts.php';
    ?>

    <!-- Test Bench CSS for look and feel -->
<!--     <link rel="stylesheet" href="css/compiled/signup.css" type="text/css" media="screen" /> -->
    <link rel="stylesheet" href="css/padding.css" type="text/css" media="screen" />
    <style>
        .error {color: #FF0000;}

        /*Styling for Autocomplete*/
        .ui-autocomplete {
            background: transparent;
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            float: left;
            display: none;
            min-width: 160px;   
            padding: 4px 0;
            margin: 0 0 10px 25px;
            list-style: none;
            background-color: #ffffff;
            border-color: #ccc;
            border-color: rgba(0, 0, 0, 0.2);
            border-style: solid;
            border-width: 1px;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            border-radius: 5px;
            -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            -webkit-background-clip: padding-box;
            -moz-background-clip: padding;
            background-clip: padding-box;
            *border-right-width: 2px;
            *border-bottom-width: 2px;
        }

        .ui-menu-item > a.ui-corner-all {
            display: block;
            padding: 3px 15px;
            clear: both;
            font-weight: normal;
            line-height: 18px;
            color: #555555;
            white-space: nowrap;
            text-decoration: none;
        }

        .ui-state-hover, .ui-state-active {
            color: #ffffff;
            text-decoration: none;
            background-color: #0088cc;
            border-radius: 0px;
            -webkit-border-radius: 0px;
            -moz-border-radius: 0px;
            background-image: none;
        }

        .login-wrapper .content-wrap {
            padding: 0 40px;
        }

        .bg-white {
            background: #FFF;
        }

        .box-wrap {
            padding-left: 40px !important;
            padding-right: 40px !important;
        }
        .create-user {
            text-transform: uppercase;
            font-size: 13px;
            padding: 8px 30px;
            color: #fff;
            background-color: rgb(60, 91, 121);
            border-color: #000;
        }
        .mt-42 {
            margin-top: -42px;
        }
        /*.inactive {*/
        /*    display: none;*/
        /*}*/
        .inactive, .inactive:hover td {
            background: #E7E7E7 !important;
        }
        .inactive .username a {
            color: #999;
        }
        
        .row {
            margin: 0;
        }
        @media screen and (max-width: 700px) {
            .mt-42 {
                margin-top: 0;
            }
        }
    </style>
</head>
<body class="sub-nav">

    <!-- Include Needed Files -->
    <?php include_once 'inc/keywords.php'; ?>
    <?php include_once 'inc/dictionary.php'; ?>
    <?php include_once 'inc/logSearch.php'; ?>
    <?php include_once 'inc/format_price.php'; ?>
    <?php include_once 'inc/getQty.php'; ?>

    <?php include_once 'inc/navbar.php'; ?>

    <!-- Class 'pt' is used in padding.css to simulates (p)adding-(t)op: (x)px -->
    <div class="row pt-70">
        <div class="login-wrapper">
            <div class="box box-wrap">
                <div class="col-md-2">
                    <?php include_once 'inc/user_dash_sidebar.php'; ?>
                </div>
                <div class="col-md-10">
                    <!--<form class="form-inline" method="POST" action="/save-commission.php">-->
                        <div style="display: inline-block; width: 100%;">
                            <h2>Commission Details</h2>
                            
                            <a href="/commission_details.php" class="btn btn-default btn-sm pull-right mb-20 mt-42">Show All</a>
                            <a href="/commission.php" style="margin-right: 10px;" class="btn btn-primary btn-sm pull-right mb-20 mt-42">Users</a>
                        </div>
                        <!-- <a href='create_user.php' class="btn btn-primary pull-right mb-20">Add User</a> -->
                        
                        <!-- This table creates a list of all the users on file in the system that way the admin can pick and choose which user to update/edit -->
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Sales Order</th>
                                    <th>Company</th>
                                    <th>Sales Rep</th>
                                    <th>Total Sale</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    foreach($orders as $order): 
                                        if(getTotalCost($order['so_number']) == 0)
                                            continue;
                                ?>
                                <tr style="display: none;">
                                    <td>
                                        <?= date("m/d/Y", strtotime($order['created'])); ?>
                                    </td>
                                    <td>
                                        <?= $order['so_number']; ?>
                                    </td>
                                    <td>
                                        <?= getCompany($order['companyid']); ?>
                                    </td>
                                    <td>
                                        <?= getContact($order['sales_rep_id']); ?>
                                    </td>
                                    <td>
                                        <?= getTotalCost($order['so_number']); ?>
                                    </td>
                                </tr>
                                <tr style="display: none;">
                                    <td colspan='12'>
                                    <table class="table table-condensed commission-table">
                                        <tbody>
                                            <?php foreach($usernames as $user) { 
                                                $privNames = $venEdit->getPrivilegeTitle($user['userid']);
                                                $total = getTotalCost($order['so_number']);
                                                $commission = floatval(round(getTotalCost($order['so_number']) * ($user['commission_rate'] / 100), 2));
                                                
                                                //For single user only show commission for sales rep for sales order they took care of
                                                if(!empty($userid)) {
                                                    if($user['contactid'] == $userid && $user['contactid'] == $order['sales_rep_id']) {
                                                        //echo $user['contactid'] . ' ' . $order['sales_rep_id'] . '<br>';
                                                    } else {
                                                        continue;
                                                    }
                                                }
                                                
                                                //For Sales rep (Commission given only if they are the sales rep)
                                                if(!in_array('Administration', $privNames) && $user['contactid'] != $order['sales_rep_id']) {
                                                    continue;
                                                }
                                            ?>
                                                <tr>
                                                    <td class="col-md-4"><?= ($user['contactid'] == $order['sales_rep_id'] ? $user['name'] ." (Rep)" : $user['name']); ?></td>
                                                    <td class="col-md-4">
                                                        <?php if(in_array('Administration', $privNames)) {
                                                            echo 'Administrator';
                                                        } else {
                                                            echo 'Sales';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="col-md-2">
                                                        <?=$user['commission_rate'];?>%
                                                    </td>
                                                    <td class="col-md-2">
                                                        <?=format_price($commission);?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <!--</form>-->
                </div>
            </div>
        </div>
    </div>

    <!-- Include Needed Files -->
	<?php include_once 'inc/footer.php'; ?>
    <?php include_once 'modal/results.php'; ?>
    <?php include_once 'modal/notes.php'; ?>
    <?php include_once 'modal/remotes.php'; ?>
    <?php include_once 'modal/image.php'; ?>
    <?php include_once 'inc/jquery-fileupload.php'; ?>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <script>
        (function($) {
            //Fastest and probably worst dirty hack
            $(".commission-table").each(function() {
                if($(this).find('tr').length > 0) {
                    $(this).closest('tr').show();
                    $(this).closest('tr').prev().show();
                }
            });
        })(jQuery);
    </script>
    <!-- This is for multi select feature, if we like it lets pull down the library and input it into our system to avoid external url calls -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.11.2/js/bootstrap-select.min.js"></script> -->

</body>
</html>
