<?php 

    //Must have db file otherwise site will break
    require_once 'inc/dbconnect.php';
    require_once 'inc/user_access.php';
    require_once 'inc/edit_page_permissions.php';

    //Create new object for instance
    $venEdit = new venEdit;
?>
<!DOCTYPE html>
<html class="login-bg">
<head>
    <title>Admin - Page Permissions</title>
    <?php
        include_once 'inc/scripts.php';
    ?>

    <!-- Test Bench CSS for look and feel -->
    <!-- <link rel="stylesheet" href="css/compiled/signup.css" type="text/css" media="screen" /> -->
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
        .create-permission {
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

    <div class="row pt-70">
        <div class="login-wrapper">
            <div class="box box-wrap">

            <?php if(isset($_REQUEST['permission'])) { ?>
                <?php 
                    //This function grabs all the file names that have .php extension to populate a dropdown select in a textfield to better help the user select a page permission
                    $datalist = '';
                    if ($handle = opendir('.')) {
                        while (false !== ($entry = readdir($handle))) {
//                            if ($entry != "." && $entry != ".." && strpos($entry, '.php') !== false) {
							if (substr($entry,(strlen($entry)-4),4)=='.php') {
                                $datalist .= "<option value='$entry'/>";
                            }
                        }
                        closedir($handle);
                    }
                ?>

                <h2 class="text-center pb-20">Add Page Permissions</h2>
				<?php if (get_browser_name()=='Safari') { echo '<div class="alert alert-warning text-center"><h5>Please use a different browser for advanced options on this page!</h5></div>'; } ?>

                <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                    <div class="row">
                        <div class="col-md-6 pb-20">
                            <label>Page Name</label>
                            <input name="pagename" list="permission-list" class="form-control" type="text" placeholder="Page name or use drop down">
 
                            <datalist id="permission-list">
                                <?php echo $datalist; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6 pb-20">
                            <div class="form-group">
                                <label>Privileges</label>
                                <select name="privilege[]"  size="6" class="form-control" multiple>
                                    <?php foreach($venEdit->getPrivileges() as $type): ?>
                                        <!-- Create Options which on submit will pass in the value of the privilege based on the database -->
                                        <option value="<?php echo $type['id']; ?>"><?php echo $type['privilege']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-lg btn-primary create-permission pull-right" type='submit' name='Submit'>Add Permission</button>
                        </div>       
                    </div>
                </form>   

            <?php 
                } else { 
                    if ($_SERVER["REQUEST_METHOD"] == "POST") {
                        $venEdit->editPage();
                    }
                    if (!empty($_REQUEST["delete"])) {
                            $venEdit->deletePage($_REQUEST["delete"]);
                    }
                    $pages = $venEdit->getPrivPages();
            ?>
                <div class="col-md-2">
                    <?php include_once 'inc/user_dash_sidebar.php'; ?>
                </div>
                <div class="col-md-10">
                    <div style="display: inline-block; width: 100%;">
                        <h2>Page Permissions</h2>
                        <a href='?permission=new' class="btn btn-primary pull-right mb-20 create-permission mt-42">Add Permission</a>
                    </div>

                    <!-- This table creates a list of all the users on file in the system that way the admin can pick and choose which user to update/edit -->
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Page Name</th>
                                <th>Privilege Levels</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- Parse all the pages that contain a privilege -->
                        <?php if(!empty($pages)) { foreach($pages as $page_name) { 
                            echo '<tr>';
                                echo '<td>';
                                    echo $page_name['page'];
                                echo '</td>';

                                echo '<td>';
                                    //Get the page privilages
                                    $privs = $venEdit->getPrivsofPage($page_name['page']);
                                    foreach ($privs as $pageRoles) {
                                        echo $pageRoles . ' ';
                                    }
                                echo '</td>';
                                
                                echo '<td>';
                                    echo '<a href="?delete=' . $page_name['page'] . '" onclick="return confirm(\'Are you sure you want to remove this permission?\')">Delete</a>';
                                echo '</td>';
                            echo '</tr>';
                        }} ?>

                        </tbody>
                    </table>
                </div>
            <?php } ?>
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

     <script type="text/javascript">
            (function($){
                //Allow users to select without having to CTRL + Click
                $('option').mousedown(function(e) {
                    e.preventDefault();
                    $(this).prop('selected', $(this).prop('selected') ? false : true);
                    return false;
                });
            })(jQuery)
        </script>
    
</body>
</html>
