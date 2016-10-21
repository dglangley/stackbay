<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/setTokens.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/signout.php';
//	include_once $_SERVER["ROOT_DIR"].'/inc/mailer.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/salt.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';

    $user_token = md5(uniqid(rand(), TRUE)); // CSRF protection

	$test = 0;
	if (isset($_REQUEST['test']) AND $SUPER_ADMIN) { $test = $_REQUEST['test']; }

	$support_email = 'info@lunacera.com';

	function signin($remember_me=false) {
		global $user_token,$subject,$main_body;

		$U = $GLOBALS['U'];

		// delete the user's token if already set in this session, so long as we're not
		// updating passwords (new_password1)
		if (! isset($_REQUEST['new_password1'])) {
			if ($U['user_token'] OR $_COOKIE['user_token']) {
				$signout_err = signout($U['user_token']);
			}
		}

		// for valid user login, set cookie accordingly
		$to_sec = time()+((60*60*24)*365.25);// one year, and will refresh every time user is active on site
		$token_type = false;

		$signin_email = '';
		if (isset($_REQUEST['signin_email'])) { $signin_email = trim($_REQUEST['signin_email']); }
		$signin_password = '';
		if (isset($_REQUEST['signin_password'])) { $signin_password = trim($_REQUEST['signin_password']); }

		if ($signin_email AND $signin_password) {
			$signin_email = trim($_REQUEST['signin_email']);
			$valid_email = filter_var($signin_email, FILTER_VALIDATE_EMAIL);

			// invalid emails must be stopped!
			if (! $signin_email OR ! $valid_email) { return (1); }

			$query = "SELECT * FROM users WHERE login_email = '".res($signin_email)."' ";
			$result = qdb($query.";");
			if (mysqli_num_rows($result)==0) { return (1); }

			// get salt
			$temp_user = mysqli_fetch_assoc($result);
			$encrypted_pw = crypt($signin_password,salt($temp_user['id']));
			$query .= "AND encrypted_pass = '".res($encrypted_pw)."' LIMIT 0,1; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) { return (3); }
			$U = mysqli_fetch_assoc($result);
			$userid = $U["id"];

			// if the user wants to remain signed in, remember for 14 days
			$rem = '';
/*
			if (isset($_REQUEST['remember_me'])) {
				$to_sec = time()+((60*60*24)*14);//14 days  //365.25);// one year, and will refresh every time user is active on site
				$rem = $_REQUEST["remember_me"];
			} else if ($remember_me) {
*/
				$to_sec = time()+((60*60*24)*365.25);// one year, and will refresh every time user is active on site
				$rem = $remember_me;
//			}

//			setcookie('user_remember',$rem,$to_sec);

			$mfield = 'signin_email'; // where to place tokens error below
		} else if ((isset($_REQUEST['current_password']) OR isset($_REQUEST['reset_token']))
		AND isset($_REQUEST['new_password1']) AND isset($_REQUEST['new_password2'])) {
			if (isset($_REQUEST['current_password'])) {
				$userid = $U["id"];

				// confirm the current password
				$current_pass = trim($_REQUEST['current_password']);
				$encrypted_current = crypt($current_pass,salt($userid));
				$query = "SELECT * FROM users WHERE encrypted_pass = '".res($encrypted_current)."' AND id = '".res($userid)."'; ";
				if ($GLOBALS['test']) { echo "$query<BR>"; }
				$result = qdb($query);
				if (mysqli_num_rows($result)==0) {
					return (3);
				}
			} else {
				$userid = 0;
				$today = date("Y-m-d");
				$query = "SELECT id FROM users, resets WHERE resets.token = '".res($_REQUEST['reset_token'])."' ";
				$query .= "AND users.login_email = resets.email AND resets.datetime LIKE '".res($today)."%'; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)==0) {
					return (15);
				}
				$r = mysqli_fetch_assoc($result);
				$userid = $r['id'];
			}

			$new_pass1 = trim($_REQUEST["new_password1"]);
			$new_pass2 = trim($_REQUEST["new_password2"]);
			// passwords don't match, can't have that!
			if ($new_pass1<>$new_pass2) {
				return (5);
			} else if (strlen($new_pass1)<6) { // password is too short
				return (6);
			}

			$encrypted_pass = crypt($new_pass1,salt($userid));

			$query = "UPDATE users SET encrypted_pass = '".res($encrypted_pass)."' WHERE id = '".res($userid)."'; ";
			$result = qdb($query);

			if (isset($_REQUEST['reset_token'])) {
				$query = "DELETE FROM resets WHERE token = '".res($_REQUEST['reset_token'])."'; ";
				$result = qdb($query);
			}

			return false; // this means success since all other returns are numeric errors
		} else if (isset($_REQUEST['register_email']) AND isset($_REQUEST['register_password'])) {
			$register_email = trim($_REQUEST["register_email"]);
			$register_email2 = false;
			if (isset($_REQUEST["register_email2"])) { $register_email2 = trim($_REQUEST["register_email2"]); }
			$valid_email = filter_var($register_email, FILTER_VALIDATE_EMAIL);

			// invalid emails must be stopped!
			if (! $register_email OR ! $valid_email) {
				return (1);
			} else if ($register_email<>$register_email2 AND $register_email2!==false) {
				return (2);
			}
//			$phone = format_phone($_REQUEST['register_phone']);

/*
			if (strlen($phone)>0 AND strlen($phone)<10) { return (8); }
*/

			$fn = trim($_REQUEST["register_firstname"]);
			$ln = trim($_REQUEST["register_lastname"]);
			$cn = '';
			if (isset($_REQUEST["register_company"])) { $cn = trim($_REQUEST["register_company"]); }
			$cid = 0;
			if (is_numeric($cn)) {//user entered a company in the dropdown, which should produce an existing company
				if ($cn==getCompany($cn,'id','id')) { $cid = $cn; }
			} else {
				$cid = getCompany($cn,'name','id');
			}
			if ($cn AND ! $cid) {
				$cid = setCompany(array('name'=>$cn));
			}
			$register_date = $GLOBALS['now'];//default

			// check for existing user
			$query = "SELECT * FROM users WHERE login_email = '".res($register_email)."'; ";
			$result = qdb($query);
			// user already exists
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				if ($r["encrypted_pass"]) {
					return (4);
				}
				$userid = $r["id"];
				if (! $fn AND $r["first_name"]) { $fn = $r["first_name"]; }
				if (! $ln AND $r["last_name"]) { $ln = $r["last_name"]; }
				if (! $cid AND $r["companyid"]) { $cid = $r["companyid"]; }
				if ($r["register_date"]) { $register_date = $r["register_date"]; }
				if (! $ln AND $r["last_name"]) { $ln = $r["last_name"]; }
			}

			$register_pass1 = trim($_REQUEST["register_password"]);
			$register_pass2 = trim($_REQUEST["register_password2"]);
			// passwords don't match, can't have that!
			if ($register_pass1<>$register_pass2) {
				return (5);
			} else if (strlen($register_pass1)<6) { // password is too short
				return (6);
			}

			$postcode = trim($_REQUEST['postal_code']);

			if (strlen($fn)<=1 OR strlen($ln)<=1) { return (20); }
			if (! $cid) { return (11); }

			$salt = salt($userid,false);//user id might not be set already so get existing OR generated $salt here
			$saltWithLog = "$2a$10$".$salt."$";
			$encrypted_pass = crypt($register_pass1,$saltWithLog);
			$query = "REPLACE users (first_name, last_name, login_email, encrypted_pass, register_date, companyid";
			if ($userid) { $query .= ",id"; }
			$query .= ") VALUES ('".res($fn)."',";//'".res($ln)."',";
			if ($ln) { $query .= "'".res($ln)."',"; } else { $query .= "NULL,"; }
			if ($register_email) { $query .= "'".res($register_email)."',"; } else { $query .= "NULL,"; }
			$query .= "'".res($encrypted_pass)."',";
			if ($register_date) { $query .= "'".res($register_date)."',"; } else { $query .= "'".$GLOBALS['now']."',"; }
			if ($cid) { $query .= "'".res($cid)."'"; } else { $query .= "NULL"; }
			if ($userid) { $query .= ",'".res($userid)."'"; }
			$query .= "); ";
			if ($GLOBALS['test']) {
				$userid = 999999;
			} else {
				$query_failed = false;
				$result = qdb($query) OR $query_failed = true;
				if ($query_failed===true) { return(9); }
				if (! $userid) {
					$userid = qid();
					setSalt($userid,$salt);
				}
			}
			if (! $userid) { return (9); }

			$query = "SELECT * FROM users WHERE id = '".res($userid)."'; ";
			$result = qdb($query);
			$U = mysqli_fetch_assoc($result);

			$left_banner = array('Your Info',$fn.' '.$ln.'<br>'.$register_email.'<br>'.$phone);
			$subject = 'Your LunaCera account has been created';
			$teaser = '<strong>Welcome to LunaCera!</strong> Remember to bookmark '.
				'<a href="http://www.lunacera.com" title="lunacera.com">http://www.lunacera.com</a>.';
			$main_body = '<ul>Here are a couple tips to get you started:<br/>'.
				'<li><strong>Build your Hot List!</strong> LunaCera will auto-notify you when updates are available.</li>'.
				'<li><strong>Search your own inventory</strong> using the powerful LunaCera search engine.</li>'.
				'<li><strong>Share LunaCera</strong> and get paid with credits towards your account.</li>'.
				'</ul><br/>'.
				'If you ever have problems or questions, we\'re always here. <a href="mailto:info@lunacera.com">info@lunacera.com</a>';
			$html_body = formatEmail($subject,$main_body,$teaser);
			if (! $GLOBALS['test']) {
//				$sendTo = array($register_email,$fn.' '.$ln);
//				$mailer_err = mailer($sendTo,$subject,$html_body,'','','',$GLOBALS['support_email']);
			}
			// set the tutorial to open for the user since they just registered; this will be deleted after completion
//			setcookie('tutorial','1',$to_sec,'/');

			$mfield = 'register_firstname'; // where to place tokens error below
		} else {
			return (15);
		}

		// update token
		$token_set = setTokens($userid,$user_token,$to_sec,$token_type);
		if (! $token_set) {
			return (9);
		}

		return false; // this means success since all other returns are numeric errors
	}
?>
