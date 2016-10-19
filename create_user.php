<?php 

    //Must have db file otherwise site will break
    require_once 'inc/dbconnect.php';
    require_once 'inc/user_access.php';
    require_once 'inc/user_registration.php';

    $registered = false;

    //Create new object for instance to class Ven Reg that extends Ven Priveleges
    $venReg = new venRegistration;

    //Get all the company names from the database
    $companies = $venReg->getCompanyNames();

    //Form Invoking self with POST method doing very surface validation to make sure email is valid and required fields are set
    //This is a quick screen that will probably be built into the class soon
    $error = false;
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        if (empty($_POST["username"])) {
            $userErr = "Username is required";
            $error = true;
        } else {
            //Check if the user exists already
            $exists = $venReg->checkUsername($_POST["username"]);
            if($exists) {
                $userErr = "User " . $_POST["username"] . " already exists.";
                $error = true;
            }
        }

        //Company ID check
        //print_r($venReg->companyCheck($_POST["company"]));

        if (empty($_POST["firstName"])) {
            $firstErr = "First Name is required";
            $error = true;
        } else {
            // check if name only contains letters and whitespace
            if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
                $firstErr = "Only letters and white space allowed"; 
                $error = true;
            }
        }

        if (empty($_POST["password"])) {
            $passwordErr = "Password is required";
            $error = true;
        }

        if (empty($_POST["lastName"])) {
            $lastErr = "Last Name is required";
            $error = true;
        } else {
        // check if name only contains letters and whitespace
            if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
                $lastErr = "Only letters and white space allowed"; 
                $error = true;
            }
        }

        if (empty($_POST["email"])) {
            $emailErr = "Email is required";
            $error = true;
        } else {
            // check if e-mail address is well-formed
            if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
                $emailErr = "Invalid email format"; 
                $error = true;
            }
        }

        //If no surface error persists then create a new object and pass in the data to create a new user
        if(!$error) {
            //echo 'No Errors and processing request';
            //Run thru the user registration
            $venReg->registerMember();

            //Check and see if any of the errors was flagged during the user registration, otherwise display success message and clear the form
            if($venReg->getError()) {
                $registerErr =  $venReg->getError();
            } else {
                 $registerErr =  '<strong>Success</strong>: User has been created - ' . $_POST['username'];
                 //Clearing $_Post, be aware tho that a refresh will still invoke the data but will be caught in a user exists error
                 $_POST = array();
                 $registered = true;
                 //unset the object after the user is created
                 unset($userErr);
                 unset($venReg);
            }
        }

    }

    //Test Bench

?>
<!DOCTYPE html>
<html class="login-bg">
<head>
	<title>Admin - Create User</title>
    <?php
        include_once 'inc/scripts.php';
    ?>

    <!-- Test Bench CSS for look and feel -->
    <!-- <link rel="stylesheet" href="css/compiled/signup.css" type="text/css" media="screen" /> -->
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
        .create-user {
            text-transform: uppercase;
            font-size: 13px;
            padding: 8px 30px;
            color: #fff;
            background-color: rgb(60, 91, 121);
            border-color: #000;
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
            <div class="box">
                <!-- Check if the user had been successfully created and display a message set above -->
                <?php if($registered) { ?>
                    <div class="alert alert-success">
                        <?php echo $registerErr; ?>
                    </div>
                <?php } else if($registerErr) { ?>
                    <div class="alert alert-danger">
                        <?php echo $registerErr; ?>
                    </div>
                <?php } ?>

                <div class="content-wrap">
                    <h3 class="pb-20 text-center">Create New User</h3>
                     <!-- Just reload the page with PHP_SELF -->
                    <form action='<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>' method='post' accept-charset='UTF-8'>
                        <div class="row">
                            <div class="col-md-12 pb-20">
                                <span class="error"><?php echo $userErr;?></span>
                                <input name="username" class="form-control" type="text" placeholder="Username"  value="<?php echo $_POST['username']; ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 pb-20">
                                <span class="error"><?php echo $firstErr;?></span>
                                <input name="firstName" class="form-control" type="text" placeholder="First Name" value="<?php echo $_POST['firstName']; ?>">
                            </div>
                            <div class="col-md-6 pb-20">
                                <span class="error"><?php echo $lastErr;?></span>
                                <input name="lastName" class="form-control" type="text" placeholder="Last Name" value="<?php echo $_POST['lastName']; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 pb-20">
                                <span class="error"><?php echo $emailErr;?></span>
                                <input name="email" class="form-control" type="text" placeholder="E-mail Address"  value="<?php echo $_POST['email']; ?>">
                            </div>
                            <div class="col-md-6 pb-20">
                                <span class="error"><?php echo $phoneErr;?></span>
                                <input name="phone" class="form-control phone_us" type="text" placeholder="Phone Number"  value="<?php echo $_POST['phone']; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12 pb-30">
                                <span class="error"><?php echo $passwordErr;?></span>
                                <div class="input-group">
                                    <input id="pass" type="text" name="password" class="form-control" rel="gp" data-size="10" data-character-set="a-z,A-Z,0-9,#" placeholder="Password"  value="<?php echo $_POST['password']; ?>">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default getNewPass"><span class="fa fa-refresh"> Generate</span></button>
                                    </span>
                                    <!-- This is a hidden field that will toggle if the password is generated and will change if the admin changes the password in the textbox -->
                                    <input id="gen" type="checkbox" name="generated_pass" hidden>
                                </div>
                                <label class="pull-right">*Generated passwords require user to reset password</label>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 pb-20">
                                <input id="company" name="company" class="form-control" type="text" placeholder="Company Name"  value="<?php echo ($_POST['company'] ? $_POST['company'] : "Ventura Telephone" ); ?>">
                            </div>
                            <div class="col-md-6 pb-20">
                                <input name="type" class="form-control" type="text" placeholder="Type"  value="<?php echo ( $_POST['type'] ? $_POST['type'] : "Work" ); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <select name="privilege[]"  size="6" class="form-control" multiple>
                                        <?php foreach($venReg->getPrivileges() as $type): ?>
                                            <!-- Create Options which on submit will pass in the value of the privilege based on the database -->
                                            <option value="<?php echo $type['id']; ?>"><?php echo $type['privilege']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="checkbox">
                                    <label><input name="status" type="checkbox" value="Active" checked>Active (User Status)</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button class="btn btn-lg btn-primary create-user pull-right" type='submit' name='Submit'>Sign up</button>
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
    <?php include_once 'inc/jquery-fileupload.php'; ?>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript">
        (function(b){"function"===typeof define&&define.amd?define(["jquery"],b):"object"===typeof exports?module.exports=b(require("jquery")):b(jQuery||Zepto)})(function(b){var y=function(a,e,d){var c={invalid:[],getCaret:function(){try{var r,b=0,e=a.get(0),d=document.selection,f=e.selectionStart;if(d&&-1===navigator.appVersion.indexOf("MSIE 10"))r=d.createRange(),r.moveStart("character",-c.val().length),b=r.text.length;else if(f||"0"===f)b=f;return b}catch(g){}},setCaret:function(r){try{if(a.is(":focus")){var c,
b=a.get(0);b.setSelectionRange?(b.focus(),b.setSelectionRange(r,r)):(c=b.createTextRange(),c.collapse(!0),c.moveEnd("character",r),c.moveStart("character",r),c.select())}}catch(e){}},events:function(){a.on("keydown.mask",function(c){a.data("mask-keycode",c.keyCode||c.which)}).on(b.jMaskGlobals.useInput?"input.mask":"keyup.mask",c.behaviour).on("paste.mask drop.mask",function(){setTimeout(function(){a.keydown().keyup()},100)}).on("change.mask",function(){a.data("changed",!0)}).on("blur.mask",function(){n===
c.val()||a.data("changed")||a.trigger("change");a.data("changed",!1)}).on("blur.mask",function(){n=c.val()}).on("focus.mask",function(a){!0===d.selectOnFocus&&b(a.target).select()}).on("focusout.mask",function(){d.clearIfNotMatch&&!p.test(c.val())&&c.val("")})},getRegexMask:function(){for(var a=[],c,b,d,f,l=0;l<e.length;l++)(c=g.translation[e.charAt(l)])?(b=c.pattern.toString().replace(/.{1}$|^.{1}/g,""),d=c.optional,(c=c.recursive)?(a.push(e.charAt(l)),f={digit:e.charAt(l),pattern:b}):a.push(d||
c?b+"?":b)):a.push(e.charAt(l).replace(/[-\/\\^$*+?.()|[\]{}]/g,"\\$&"));a=a.join("");f&&(a=a.replace(new RegExp("("+f.digit+"(.*"+f.digit+")?)"),"($1)?").replace(new RegExp(f.digit,"g"),f.pattern));return new RegExp(a)},destroyEvents:function(){a.off("input keydown keyup paste drop blur focusout ".split(" ").join(".mask "))},val:function(c){var b=a.is("input")?"val":"text";if(0<arguments.length){if(a[b]()!==c)a[b](c);b=a}else b=a[b]();return b},getMCharsBeforeCount:function(a,c){for(var b=0,d=0,
f=e.length;d<f&&d<a;d++)g.translation[e.charAt(d)]||(a=c?a+1:a,b++);return b},caretPos:function(a,b,d,h){return g.translation[e.charAt(Math.min(a-1,e.length-1))]?Math.min(a+d-b-h,d):c.caretPos(a+1,b,d,h)},behaviour:function(d){d=d||window.event;c.invalid=[];var e=a.data("mask-keycode");if(-1===b.inArray(e,g.byPassKeys)){var m=c.getCaret(),h=c.val().length,f=c.getMasked(),l=f.length,k=c.getMCharsBeforeCount(l-1)-c.getMCharsBeforeCount(h-1),n=m<h;c.val(f);n&&(8!==e&&46!==e&&(m=c.caretPos(m,h,l,k)),
c.setCaret(m));return c.callbacks(d)}},getMasked:function(a,b){var m=[],h=void 0===b?c.val():b+"",f=0,l=e.length,k=0,n=h.length,q=1,p="push",u=-1,t,w;d.reverse?(p="unshift",q=-1,t=0,f=l-1,k=n-1,w=function(){return-1<f&&-1<k}):(t=l-1,w=function(){return f<l&&k<n});for(;w();){var x=e.charAt(f),v=h.charAt(k),s=g.translation[x];if(s)v.match(s.pattern)?(m[p](v),s.recursive&&(-1===u?u=f:f===t&&(f=u-q),t===u&&(f-=q)),f+=q):s.optional?(f+=q,k-=q):s.fallback?(m[p](s.fallback),f+=q,k-=q):c.invalid.push({p:k,
v:v,e:s.pattern}),k+=q;else{if(!a)m[p](x);v===x&&(k+=q);f+=q}}h=e.charAt(t);l!==n+1||g.translation[h]||m.push(h);return m.join("")},callbacks:function(b){var g=c.val(),m=g!==n,h=[g,b,a,d],f=function(a,b,c){"function"===typeof d[a]&&b&&d[a].apply(this,c)};f("onChange",!0===m,h);f("onKeyPress",!0===m,h);f("onComplete",g.length===e.length,h);f("onInvalid",0<c.invalid.length,[g,b,a,c.invalid,d])}};a=b(a);var g=this,n=c.val(),p;e="function"===typeof e?e(c.val(),void 0,a,d):e;g.mask=e;g.options=d;g.remove=
function(){var b=c.getCaret();c.destroyEvents();c.val(g.getCleanVal());c.setCaret(b-c.getMCharsBeforeCount(b));return a};g.getCleanVal=function(){return c.getMasked(!0)};g.getMaskedVal=function(a){return c.getMasked(!1,a)};g.init=function(e){e=e||!1;d=d||{};g.clearIfNotMatch=b.jMaskGlobals.clearIfNotMatch;g.byPassKeys=b.jMaskGlobals.byPassKeys;g.translation=b.extend({},b.jMaskGlobals.translation,d.translation);g=b.extend(!0,{},g,d);p=c.getRegexMask();!1===e?(d.placeholder&&a.attr("placeholder",d.placeholder),
a.data("mask")&&a.attr("autocomplete","off"),c.destroyEvents(),c.events(),e=c.getCaret(),c.val(c.getMasked()),c.setCaret(e+c.getMCharsBeforeCount(e,!0))):(c.events(),c.val(c.getMasked()))};g.init(!a.is("input"))};b.maskWatchers={};var A=function(){var a=b(this),e={},d=a.attr("data-mask");a.attr("data-mask-reverse")&&(e.reverse=!0);a.attr("data-mask-clearifnotmatch")&&(e.clearIfNotMatch=!0);"true"===a.attr("data-mask-selectonfocus")&&(e.selectOnFocus=!0);if(z(a,d,e))return a.data("mask",new y(this,
d,e))},z=function(a,e,d){d=d||{};var c=b(a).data("mask"),g=JSON.stringify;a=b(a).val()||b(a).text();try{return"function"===typeof e&&(e=e(a)),"object"!==typeof c||g(c.options)!==g(d)||c.mask!==e}catch(n){}};b.fn.mask=function(a,e){e=e||{};var d=this.selector,c=b.jMaskGlobals,g=c.watchInterval,c=e.watchInputs||c.watchInputs,n=function(){if(z(this,a,e))return b(this).data("mask",new y(this,a,e))};b(this).each(n);d&&""!==d&&c&&(clearInterval(b.maskWatchers[d]),b.maskWatchers[d]=setInterval(function(){b(document).find(d).each(n)},
g));return this};b.fn.masked=function(a){return this.data("mask").getMaskedVal(a)};b.fn.unmask=function(){clearInterval(b.maskWatchers[this.selector]);delete b.maskWatchers[this.selector];return this.each(function(){var a=b(this).data("mask");a&&a.remove().removeData("mask")})};b.fn.cleanVal=function(){return this.data("mask").getCleanVal()};b.applyDataMask=function(a){a=a||b.jMaskGlobals.maskElements;(a instanceof b?a:b(a)).filter(b.jMaskGlobals.dataMaskAttr).each(A)};var p={maskElements:"input,td,span,div",
dataMaskAttr:"*[data-mask]",dataMask:!0,watchInterval:300,watchInputs:!0,useInput:function(a){var b=document.createElement("div"),d;a="on"+a;d=a in b;d||(b.setAttribute(a,"return;"),d="function"===typeof b[a]);return d}("input"),watchDataMask:!1,byPassKeys:[9,16,17,18,36,37,38,39,40,91],translation:{0:{pattern:/\d/},9:{pattern:/\d/,optional:!0},"#":{pattern:/\d/,recursive:!0},A:{pattern:/[a-zA-Z0-9]/},S:{pattern:/[a-zA-Z]/}}};b.jMaskGlobals=b.jMaskGlobals||{};p=b.jMaskGlobals=b.extend(!0,{},p,b.jMaskGlobals);
p.dataMask&&b.applyDataMask();setInterval(function(){b.jMaskGlobals.watchDataMask&&b.applyDataMask()},p.watchInterval)});
    </script>

    <script type="text/javascript">

        // Generate a password string function
        function randString(id){
            var dataSet = $(id).attr('data-character-set').split(',');
            var possible = '';

            if($.inArray('a-z', dataSet) >= 0){
                possible += 'abcdefghijklmnopqrstuvwxyz';
            }

            if($.inArray('A-Z', dataSet) >= 0){
                possible += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            }

            if($.inArray('0-9', dataSet) >= 0){
                possible += '0123456789';
            }

            if($.inArray('#', dataSet) >= 0){
                possible += '![]{}()%&*$#^<>~@|';
            }

            var text = '';
            for(var i=0; i < $(id).attr('data-size'); i++) {
                text += possible.charAt(Math.floor(Math.random() * possible.length));
            }
            return text;
        }

        (function($){
            //Allow users to select without having to CTRL + Click
            $('option').mousedown(function(e) {
                e.preventDefault();
                $(this).prop('selected', $(this).prop('selected') ? false : true);
                return false;
            });

            //Function to randomly generate a difficult password for user
            // Create a new password
            $(".getNewPass").click(function(){
                var field = $(this).closest('div').find('input[rel="gp"]');
                field.val(randString(field));
                $( "#gen" ).prop( "checked", true );
            });

            $('#pass').change(function(){
                $( "#gen" ).prop( "checked", false );
            });

            var availableTags = [
                <?php 
                    $init = 0;
                    foreach ($companies as $cn) {
                        if($init == 0) {
                            echo '"' . htmlspecialchars ($cn['name']) . '"';
                        } else {
                            echo ', "' . htmlspecialchars ($cn['name']) . '"';
                        }
                        $init++;
                    } 
                ?>
            ];
            $( "#company" ).autocomplete({
                source: availableTags,
                delay: 0
            });
            $('.phone_us').mask('(000) 000-0000');
        })(jQuery);
    </script>

    <!-- This is for multi select feature, if we like it lets pull down the library and input it into our system to avoid external url calls -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.11.2/js/bootstrap-select.min.js"></script> -->

</body>
</html>