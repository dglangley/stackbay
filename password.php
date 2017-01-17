<?php 

    //Must have db file otherwise site will break
    require_once 'inc/dbconnect.php';
    require_once 'inc/user_access.php';
    require_once 'inc/password_policy.php';

    $venPolicy = new venPolicy;

    //print_r($policy);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $venPolicy->savetoDatabase('password_policy');
    }

    $policy = $venPolicy->getPolicy();
?>
<!DOCTYPE html>
<html class="login-bg">
<head>
    <title>Admin - Password Policy</title>
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
        @media screen and (max-width: 700px) {
            .mt-42 {
                margin-top: 0;
            }
        }

        .greyscale {
            width: 20px;    
            position: relative;
        }

        .greyscale label {
            cursor: pointer;
            position: absolute;
            width: 20px;
            height: 20px;
            top: 0;
            border-radius: 4px;

            -webkit-box-shadow: inset 0px 1px 1px rgba(0,0,0,0.5), 0px 1px 0px rgba(255,255,255,.4);
            -moz-box-shadow: inset 0px 1px 1px rgba(0,0,0,0.5), 0px 1px 0px rgba(255,255,255,.4);
            box-shadow: inset 0px 1px 1px rgba(0,0,0,0.5), 0px 1px 0px rgba(255,255,255,.4);

            background: #FFF;
            filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#222', endColorstr='#45484d',GradientType=0 );
        }

        .greyscale label:after {
            -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";
            filter: alpha(opacity=0);
            opacity: 0;
            content: '';
            position: absolute;
            width: 11px;
            height: 7px;
            background: transparent;
            top: 5px;
            left: 5px;
            border: 3px solid #285e8e;
            border-top: none;
            border-right: none;
            -webkit-transform: rotate(-45deg);
            -moz-transform: rotate(-45deg);
            -o-transform: rotate(-45deg);
            -ms-transform: rotate(-45deg);
            transform: rotate(-45deg);
        }

        .greyscale label:hover::after {
            -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=30)";
            filter: alpha(opacity=30);
            opacity: 0.3;
        }

        .greyscale input[type=checkbox]:checked + label:after {
            -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
            filter: alpha(opacity=100);
            opacity: 1;
        }

        input[type=checkbox] {
            display: none;
        }

        .checkLabel {
            margin-left: 30px;
        }
        .row {
            margin: 0;
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
                <div class="col-md-2">
                    <?php include_once 'inc/user_dash_sidebar.php'; ?>
                </div>
                 <div class="col-md-10">
                    <h2 class="pb-20">Password Policy</h2>
                    <p>A password that is difficult to detect by both humans and computer programs, effectively protecting data from unauthorized access. A strong password consists of at least six characters (and the more characters, the stronger the password) that are a combination of letters, numbers and symbols (@, #, $, %, etc.) if allowed.</p>
                    <p>Currently, this Server account does not have a password policy. Specify a password policy below.</p>
                    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8' class="form-inline">
                        <div class="row">
                            <div class="col-md-12 pb-20">
                                <label for="passlength" style="font-weight: normal;">Minimum password length: </label>
                                <div class="form-group">
                                    <input name="policy['length']" class="form-control" type="text" placeholder="(Default 6)" value="<?php echo (isset( $policy['length']) ? $policy['length'] : '' ); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">       
                            <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input type="checkbox" value="1" id="greyscale_1" name="policy['uppercase']" <?php echo (isset($policy['uppercase']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_1"></label>
                                </div>
                                <span class="checkLabel">Require at least one upper case letter</span>
                            </div>
                            <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input type="checkbox" value="1" id="greyscale_2" name="policy['lowercase']" <?php echo (isset($policy['lowercase']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_2"></label>
                                </div>
                                <span class="checkLabel">Require at least one lowercase letter</span>
                            </div>
                            <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input type="checkbox" value="1" id="greyscale_3" name="policy['number']" <?php echo (isset($policy['number']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_3"></label>
                                </div>
                                <span class="checkLabel">Require at least one number</span>
                            </div>
                            <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input type="checkbox" value="1" id="greyscale_4" name="policy['special']" <?php echo (isset($policy['special']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_4"></label>
                                </div>
                                <span class="checkLabel">Require at least one non-alphanumeric character</span>
                            </div>
                            <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input class="expiration" type="checkbox" value="1" id="greyscale_5" name="policy['expiration']" <?php echo (isset($policy['expiration']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_5"></label>
                                </div>
                                <span class="checkLabel">Enable Password Expiration</span>
                            </div>
                            <div class="row">
                            <!-- This field attaches with the enable password expiration to create a conditional statement  -->
                                <div class="col-md-12 pb-20" style="padding-left: 60px;">
                                    <label for="passlength" style="font-weight: normal;">Expiration Time: </label>
                                    <div class="form-group">
                                        <input name="policy['expiration_time']" class="form-control expiration_time" type="text" placeholder="Days (e.g. '14')" value="<?php echo (isset( $policy['expiration_time']) ? $policy['expiration_time'] : '' ); ?>" disabled>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input type="checkbox" value="1" id="greyscale_6" name="policy['user_edit']" <?php echo (isset($policy['user_edit']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_6"></label>
                                </div>
                                <span class="checkLabel">Allow users to change their own password</span>
                            </div>

                             <div class="col-md-12 pb-20">
                                <div class="greyscale">
                                    <input type="checkbox" value="1" id="greyscale_7" name="policy['gen_expiry']" <?php echo (isset($policy['gen_expiry']) ? 'checked' : ''); ?>/>
                                    <label for="greyscale_7"></label>
                                </div>
                                <span class="checkLabel">Generate password expiration (24Hr)</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button class="btn btn-lg btn-primary create-permission pull-right" type='submit' name='Submit'>Apply Policy</button>
                            </div>
                        </div>

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

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

     <script type="text/javascript">
            (function($){
                //Allow users to select without having to CTRL + Click
                $('option').mousedown(function(e) {
                    e.preventDefault();
                    $(this).prop('selected', $(this).prop('selected') ? false : true);
                    return false;
                });

                //Initiial Check
                if ($('input.expiration').is(':checked')) {
                    $("input.expiration_time").prop('disabled', false);
                } 

                //Add in a enabler field for inputs
                $('input.expiration').click(function() {
                    //Check if on click the input is checked or not to determine if the expiration time field should be enabled
                    if ($('input.expiration').is(':checked')) {
                        $("input.expiration_time").prop('disabled', false);
                    } else {
                         $("input.expiration_time").prop('disabled', true);
                    }
                });
            })(jQuery)
        </script>
    
</body>
</html>
