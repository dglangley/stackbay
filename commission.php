<?php 

    //Must have db file otherwise site will break
    require_once 'inc/dbconnect.php';
    require_once 'inc/user_access.php';
    require_once 'inc/user_edit.php';

    $edited = false;
    
    //Form Invoking self with POST method doing very surface validation to make sure email is valid and required fields are set
    //This is a quick screen that will probably be built into the class soon
    //These variables are specifically for User Registration
    $registered = false;
    $error = false;
    $registerErr = '';
    $userErr = '';
    $firstErr = '';
    $lastErr = '';
    $passwordErr = '';
    $emailErr = '';
    $phoneErr = '';
    //End User Reg variables
    
    $editedrErr = '';
    $password = '';
    $updatedUser = false;

    //Create new object for instance to class Ven Reg that extends Ven Priveleges
    $venEdit = new VenEdit;

    //Get all the company names from the database
    $companies = $venEdit->getCompanyNames();
    $usernames = $venEdit->getAllSalesUsers();
?>
<!DOCTYPE html>
<html class="login-bg">
<head>
	<title>Admin - User Commissions</title>
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
        
        .navbar-nav.pull-right > li > .dropdown-menu:after {
            left: auto !important;
            right: 13px !important;
        }
        .navbar-nav.pull-right > li > .dropdown-menu {
            left: auto !important;
            right: 0 !important;
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
                    <form class="form-inline" method="POST" action="/save-commission.php">
                        <div style="display: inline-block; width: 100%;">
                            <h2>Commissions</h2>
                            
                            <button name='submit' type="submit" class="btn btn-success btn-sm pull-right mb-20 mt-42" value="commission" title="Save Commission" style="margin-left: 10px;">Save</button>
                        </div>
                        <!-- <a href='create_user.php' class="btn btn-primary pull-right mb-20">Add User</a> -->
                        
                        <!-- This table creates a list of all the users on file in the system that way the admin can pick and choose which user to update/edit -->
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="col-sm-2">Name</th>
                                    <th class="col-sm-2">Username</th>
                                    <th class="col-sm-4">Email</th>
                                    <th class="col-sm-2">Position</th>
                                    <th class="col-sm-1">Rate</th>
                                    <th class="col-sm-1"> </th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach($usernames as $user) { 
                                $privNames = $venEdit->getPrivilegeTitle($user['userid']);
                            ?>
                                <tr>
                                    <td><?=$user['name']; ?></td>
                                    <td class='username'><?php echo $user['username']; ?></td>
                                    <td><?php echo $venEdit->chkEmail($user['emailid']); ?></td>
                                    <td>
                                        <?php if(in_array('Administration', $privNames)) {
                                            echo 'Administrator';
                                        } else {
                                            echo 'Sales Rep';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control input-sm inline static-form" name="commission[<?= $user['userid']; ?>]" value="<?=$user['commission_rate'];?>" placeholder="0.0">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </td>
                                    <td class="text-right">
										<a href="/commission_details.php?user=<?php echo $user['userid']; ?>"><i class="fa fa-search fa-2x"></i></a>
                                    </td>
                                </tr>
                            <?php } ?>
								<tr>
									<td colspan="6" class="text-right">
                            			<a href="/commission_details.php" title="Show All">Search All &nbsp; <i class="fa fa-search fa-2x"></i></a>
									</td>
								</tr>
                            </tbody>
                        </table>
                    </form>
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

    <!-- This is for multi select feature, if we like it lets pull down the library and input it into our system to avoid external url calls -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.11.2/js/bootstrap-select.min.js"></script> -->

</body>
</html>
