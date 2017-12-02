<?php
	//Include Gmail function
	include_once($_SERVER["ROOT_DIR"]."/inc/send_gmail.php");
	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session


	class venPriv {
		//Class Global Variables

		//Private = only seen within the class and you need a function get*() to retrieve the data vs $obj->value(); Use protected if you want to extend variables to extended classes
		private $username;
		private $user_email;
		private $user_password;
		private $user_pin;
		private $user_token;
		private $user_salt;
		//private $company;
		private $user_type;
		private $user_status;
		private $user_privilege;
		private $user_service_class;
		private $user_phone;

		//Have All the Ids on hand in case we need them to pull data
		private $user_ID;
		private $username_ID;
		private $contact_ID;
		private $email_ID;

		//Plaintext password
		private $temp_pass;

		//Set the user Token expiration time
		private $user_token_expiry;

		//user non-sensitive Information
		var $user_firstName;
		var $user_lastName;
		var $user_company;
		var $token_length;
		var $generated_pass;
		var $generated_pass_exp;

		//Error Handler
		var $error;

		/* ---------Initialize and set set functions ------------------------*/

		//Constructor - we can also use the class name venPriv again, but if venPriv is changed then this needs to be changes
		//This is a universal way to construct with a dynamic class name
		function __construct() {
			//Initialize whatever seems fit

			//Set the Users token globally within this class but if token exists it will be replaced by the token function
			$this->user_token = md5(uniqid(rand(), TRUE));
			//Set the expiration length globally to 1 week
			$this->token_length = time() + (7 * 86400); //* 86400
			//We are going to assume the user is an established user with his/her own password
			$this->generated_pass = 0;
			//Password expiration to 1 day
			$this->generated_pass_exp = time() + (86400);
		}

		function setUsername($username) {
			//set user specific username
			$this->username = $username;
		}

		function setUsernameID($usernameid) {
			//set user specific username ID
			$this->username_ID = $usernameid;
		}

		function setUserID($userid) {
			//set user specific userid
			$this->user_ID = $userid;
		}

		function setEmail($email) {
			//set user specific email
			$this->user_email = $email;
		}

		function setEmailID($emailid) {
			//set user specific emailID
			$this->email_ID = $emailid;
		}

		function setTempPass($password) {
			//set user specific password (This is the plaintext)
			$this->temp_pass = $password;
		}

		function setPassword($password) {
			//set user specific password (This will be the encrypted password for the user, not the plaintext)
			$this->user_password = $password;
		}

		function setPin($pin) {
			//set user specific password (This will be the encrypted password for the user, not the plaintext)
			$this->user_pin = $pin;
		}

		function setToken($token) {
			//set user specific token
			$this->user_token = $token;
		}

		function setSalt($salt) {
			//set user specific salt
			$this->user_salt = $salt;
		}

		function setType($type) {
			//set user specific type (work, ..... etc)
			$this->user_type = $type;
		}

		function setStatus($status) {
			//set user status (Inactive or Active)
			$this->user_status = $status;
		}

		function setPrivilege($privileges) {
			//set user privileges
			$this->user_privilege = $privileges;
		}

		function setServiceClass($classes) {
			//set user privileges
			$this->user_service_class = $classes;
		}

		function setPhone($phone) {
			//set user phone
			$this->user_phone = $phone;
		}

		function setFirst($fn) {
			//set user specific First Name
			$this->user_firstName = $fn;
		}

		function setLast($ln) {
			//set user specific Last Name
			$this->user_lastName = $ln;
		}

		function setCompany($cn) {
			//set user specific Company
			$this->user_company = $cn;
		}

		function setContactID($cid) {
			//set user Contact ID
			$this->contact_ID = $cid;
		}

		function setGenerated($gen_pass) {
			//set if the password was generated or not (0:1)
			$this->generated_pass = $gen_pass;
		}
		
		function setGeneratedExp($gen_pass_exp) {
			//set if the initial to have a expiration time for the user to reset his/her password
			$this->generated_pass_exp = $gen_pass_exp;
		}

		function setError($error) {
			//set any errors that come up during this process of running venPriv
			$this->error = $error;
		}

		/* ---------Initialize and set get functions ------------------------*/

		function getUsername() {
			//get user specific username
			return $this->username;
		}

		function getUsernameID() {
			//get user specific username id
			return $this->username_ID;
		}

		function getUserID() {
			//get user specific userid
			return $this->user_ID;
		}

		function getEmail() {
			//get user specific email
			return $this->user_email;
		}

		function getEmailID() {
			//get user specific emailID
			return $this->email_ID;
		}

		function getTempPass() {
			//get user specific unencrypted password 
			return $this->temp_pass;
		}

		function getPassword() {
			//get user specific encrypted password (We need this for private/protected variables)
			return $this->user_password;
		}

		function getPin() {
			//get user specific encrypted password (We need this for private/protected variables)
			return $this->user_pin;
		}

		function getToken() {
			//get user specific token (We need this for private/protected variables)
			return $this->user_token;
		}

		function getSalt() {
			//get user specific salt (We need this for private/protected variables)
			return $this->user_salt;
		}

		function getCompany() {
			//get user company
			return $this->user_company;
		}

		function getContactID() {
			//get user contact ID
			return $this->contact_ID;
		}

		function getType() {
			//get user specific type (work...)
			return $this->user_type;
		}

		function getStatus() {
			//get user specific status
			return $this->user_status;
		}

		function getPrivilege() {
			//get user specific status
			return $this->user_privilege;
		}

		function getServiceClass() {
			//get user specific status
			return $this->user_service_class;
		}

		function getPhone() {
			//get user specific status
			return $this->user_phone;
		}

		function getError() {
			//set any errors that come up during this process of running venPriv
			return $this->error;
		}

		/* --------- Begin the craziness and functions we need ------------------------*/

		//Generate user salt or get the user's salt
		function salt($userid = 0) {

			$salt = "";

			if ($userid AND is_numeric($userid) AND $userid>0) {
				$query = "SELECT * FROM user_salts WHERE userid = '".res($userid)."'; ";
				$result = qdb($query);
				if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$salt = $r['salt'];
				}
			}

			//Generate a ridiculously stupid hard salt using php mcrypt (make hackers extra salty)
			if (!$salt AND ! $GLOBALS['DEV_ENV']) { $salt = "$2y$10$". bin2hex(mcrypt_create_iv(22, MCRYPT_DEV_URANDOM)); }

			//If try catch error handling
			if (isset($salt)) {
				//10 letter salt string for each user
				$this->setSalt($salt);
			} else {
				//Set Error
				$this->setError('Failed to Generate or Retrieve User Salt');
			}

		}

		//This hashes password to bcrypt and generates a user salt
		function bCrypt($password, $type) {
	        $this->salt($this->getUserID());

	        $salt = $this->getSalt();

	        //Using php bcrypt and the generated salt to encrypt password
	        $encrypted = crypt($password, $salt);

	        // Add type to encrypt and designate data to determined areas
	        if($type == 'pin') {
	        	$this->setPin($encrypted);
	        } else {
	        	$this->setPassword($encrypted);
	        }
	    }

	    //This function checks if the password fits the strength meter required 8 Min words with at least 1 cap and 1 symbol
	    //Add functionality for Admin to change these values
	    function validPassword($password) {
	    	//get the password po0licy set by the admin
	    	$query = "SELECT * FROM password_policy";
			$result = qdb($query);

			while ($row = $result->fetch_assoc()) {
				$policy[$row['policy']] = $row['setting'];
			}

			//Tenary if to check if the length is set else if not default to 6
			$policyLength = (isset($policy['length']) ?  $policy['length'] : 6);

	    	$error = false;
	    	$errorListing = '';
	    	//Check and make sure all basic strong password requirements are met
			if( strlen($password) < $policyLength ) {
				$error = true;
				$errorListing .= "Requires $policyLength or more characters<br>";
			}

			//Must have at least 1 number
			if( !preg_match("#[0-9]+#", $password) && isset($policy['number']) ) {
				$error = true;
				$errorListing .= "Requires at least one number<br>";
			}

			//must contain at least 1 lowercase
			if( !preg_match("#[a-z]+#", $password) && isset($policy['lowercase']) ) {
				$error = true;
				$errorListing .= "Requires at least one lowercase letter<br>";
			}

			//Must contain at least one uppercase
			if( !preg_match("#[A-Z]+#", $password) && isset($policy['uppercase']) ) {
				$error = true;
				$errorListing .= "Requires at least one uppercase letter<br>";
			}

			//Must contain at least 1 special char
			if( !preg_match("#\W+#", $password) && isset($policy['special']) ) {
				$error = true;
				$errorListing .= "Requires at least one special character<br>";
			}

			if($error){
				$this->setError("<strong>Password validation failure</strong>: <br> $errorListing");
				return (false);
			} 
			return (true);
	    }

	    function validPin($pin) {
	    	$error = false;
	    	$errorListing = '';

	    	if( strlen($pin) < 4 ) {
				$error = true;
				$errorListing .= "Requires 4 or more characters<br>";
			}

			if(! is_numeric ($pin) ) {
				$error = true;
				$errorListing .= "Pin can only be numeric<br>";
			}

			if($error){
				$this->setError("<strong>Pin validation failure</strong>: <br> $errorListing");
				return (false);
			} 

			return (true);
	    }

	    //Save what is needed into the database, the parameter will determine if it is an insert, edit, delete
	    //Should I create more functions fo database edits or use this as a generic and branch it with if statements?
	    function savetoDatabase($op) {
	    	global $WLI;

	    	if($op == "insert") {

	    		//Prepare and Bind for Contact Info
				$stmt = $WLI->prepare('
					INSERT INTO contacts (name, status, companyid) 
						VALUES (?, ?, ?) 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("ssi", $name, $status, $companyid);
				//Package it all and execute the query
				$name = $this->user_firstName . ' ' . $this->user_lastName;
				$status = "Active";
				$companyid = $this->getCompany();
				$stmt->execute();
				//Get the contact ID to be used in emails and user tables
				$contactid = $stmt->insert_id;
				$stmt->close();


	    		//Prepare and Bind for emails
				$stmt = $WLI->prepare('
					INSERT INTO emails (email, contactid, type) 
						VALUES (?, ?, ?) 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("sis", $email, $contactid, $type);
				//Package it all and execute the query
				$email = $this->getEmail();
				$type = 'Work';//$this->getType();
				$stmt->execute();
				//Get the emailid to be used in User table
				$emailid = $stmt->insert_id;

				//Prepare and Bind for Users
				$stmt = $WLI->prepare('
					INSERT INTO users (contactid, login_emailid, encrypted_pass, init, expiry) 
						VALUES (?, ?, ?, ?, ?) 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("iisii", $contactid, $emailid, $encrypted_pass, $init, $expiry);
				//Package it all and execute the query
				$encrypted_pass = $this->getPassword();
				$init = 0;
				$expiry = null;
				if($this->generated_pass == '1') {
					$init = 1;
					//24 hour expiration
					$expiry = $this->generated_pass_exp;
				}
				$stmt->execute();
				//Get the emailid to be used in User table
				$userid = $stmt->insert_id;
				$stmt->close();

				$salt = $this->getSalt();

				//Prepare and Bind for Salt
				$stmt = $WLI->prepare('
					REPLACE user_salts (salt, userid) 
						VALUES (?, ?) 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("si", $salt, $userid);
				//Package it all and execute the query
				$salt = $this->getSalt();
				$stmt->execute();
				$stmt->close();

				//Prepare and Bind for Usernames
				$stmt = $WLI->prepare('
					INSERT INTO usernames (username, emailid, userid) 
						VALUES (?, ?, ?) 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("sii", $username, $emailid, $userid);
				//Package it all and execute the query
				$username = $this->getUsername();
				$stmt->execute();
				$stmt->close();

				//Prepare and Bind for Phone Numbers
				$stmt = $WLI->prepare('
					INSERT INTO phones (contactid, type, phone) 
						VALUES (?, ?, ?) 
						ON DUPLICATE KEY UPDATE
				        type = VALUES(type),
				        phone = VALUES(phone)
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("iss", $contactid, $type, $phone);
				//Package it all and execute the query
				$phone = $this->getPhone();
				$type = 'Office';
				$stmt->execute();

				// Running for loop to get all privileges
				if(!empty($this->getPrivilege())) {
					foreach($this->getPrivilege() as $priv) {
						// Prepare and Bind for Privileges
						$stmt = $WLI->prepare('
							INSERT INTO user_roles (userid, privilegeid) 
								VALUES (?, ?) 
						');
						//s = string, i - integer, d = double, b = blob for params of mysqli
						$stmt->bind_param("ii", $userid, $privid);
						//Package it all and execute the query
						$privid = $priv;
						$stmt->execute();
						$stmt->close();
					}
				} else {
					//Else if the user has no privileges then give him Guest Access
					
					$query = "SELECT id FROM user_privileges WHERE privilege='Guest'";
					$result = qdb($query);
		
					if(mysqli_num_rows($result) > 0){
						$exists = true;
		
						//Since this seems to be a very widely used ID lets make it globally available
						while ($row = $result->fetch_assoc()) {
						  $privid = $row['id'];
						}
						
						$stmt = $WLI->prepare('
							INSERT INTO user_roles (userid, privilegeid) 
								VALUES (?, ?) 
						');
						//s = string, i - integer, d = double, b = blob for params of mysqli
						$stmt->bind_param("ii", $userid, $privid);
						//Package it all and execute the query
						$stmt->execute();
						$stmt->close();
					}
				}

				// Running for loop to get all privileges
				if(!empty($this->getServiceClass())) {
					foreach($this->getServiceClass() as $priv) {
						// Prepare and Bind for Privileges
						$stmt = $WLI->prepare('
							INSERT INTO user_classes (userid, classid) 
								VALUES (?, ?) 
						');
						//s = string, i - integer, d = double, b = blob for params of mysqli
						$stmt->bind_param("ii", $userid, $privid);
						//Package it all and execute the query
						$privid = $priv;
						$stmt->execute();
						$stmt->close();
					}
				}

	    	} else if($op == 'update') {
	    		//Update function to update users
	    		//Very Commonly used varaible so declaring it up here
	    		$userid = $this->getUserID();

	    		//Prepare and Bind for Contact Info
				$stmt = $WLI->prepare('
					INSERT INTO contacts (id, name, status, companyid) 
						VALUES (?, ?, ?, ?) 
						ON DUPLICATE KEY UPDATE
				        name = VALUES(name),
				        status = VALUES(status),
				        companyid = VALUES(companyid)
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("issi", $contactid, $name, $status, $companyid);
				//Package it all and execute the query
				$contactid = $this->getContactID();
				$name = $this->user_firstName . ' ' . $this->user_lastName;
				if($this->getStatus() == 'checked') {
					$status = "Active";
				} else {
					$status = "Inactive";
				}
				$companyid = $this->getCompany();
				$stmt->execute();
				$stmt->close();


	    		//Prepare and Bind for emails
				$stmt = $WLI->prepare('
					INSERT INTO emails (id, email, type, contactid) 
						VALUES (?, ?, ?, ?) 
						ON DUPLICATE KEY UPDATE
				        email = VALUES(email),
				        type = VALUES(type),
				        contactid = VALUES(contactid)
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("issi", $emailid, $email, $type, $contactid);
				//Package it all and execute the query
				$emailid = $this->getEmailID();
				$email = $this->getEmail();
				$type = 'Work';//$this->getType();
				$stmt->execute();
				
				//Query to get the contacts phone id
				$phoneid = 0;
				
				$query = "SELECT id from phones WHERE contactid = '" . res($this->getContactID()) . "'";
		    	$result = qdb($query);
	
		    	if (mysqli_num_rows($result)>0) {
					$r = mysqli_fetch_assoc($result);
					$phoneid = $r['id'];
				}
				
				//Prepare and Bind for Phone Numbers
				$stmt = $WLI->prepare('
					INSERT INTO phones (id, contactid, type, phone) 
						VALUES (?, ?, ?, ?) 
						ON DUPLICATE KEY UPDATE
				        type = VALUES(type),
				        phone = VALUES(phone)
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("iiss", $phoneid, $contactid, $type, $phone);
				//Package it all and execute the query
				$phone = $this->getPhone();
				$type = 'Office';
				$stmt->execute();


				//If the password is null lets not update it
				$encrypted_pass = $this->getPassword();
				if(!empty($encrypted_pass)) {
					//Prepare and Bind for Users
					$stmt = $WLI->prepare('
						INSERT INTO users (id, contactid, login_emailid, encrypted_pass, init, expiry) 
							VALUES (?, ?, ?, ?, ?, ?) 
							ON DUPLICATE KEY UPDATE
					        contactid = VALUES(contactid),
					        login_emailid = VALUES(login_emailid),
					        encrypted_pass = VALUES(encrypted_pass),
					        init = VALUES(init),
					        expiry = VALUES(expiry)
					');
					//s = string, i - integer, d = double, b = blob for params of mysqli
					$stmt->bind_param("iiisii", $userid, $contactid, $emailid, $encrypted_pass, $init, $expiry);
					//Package it all and execute the query
					$init = 0;
					$expiry = null;
					if($this->generated_pass == '1') {
						$init = 1;
						//24 hour expiration
						$expiry = $this->generated_pass_exp;
					}
					$stmt->execute();
					$stmt->close();
				}

                //Prepare and Bind for Salt
                $stmt = $WLI->prepare('
                    REPLACE user_salts (salt, userid)
                        VALUES (?, ?)
                ');
                //s = string, i - integer, d = double, b = blob for params of mysqli
                $stmt->bind_param("si", $salt, $userid);
                //Package it all and execute the query
                $salt = $this->getSalt();
                $stmt->execute();
                $stmt->close();

				if(!empty($this->getUsername())) {
					//Prepare and Bind for Usernames
					$stmt = $WLI->prepare('
						INSERT INTO usernames (id, username) 
							VALUES (?, ?) 
							ON DUPLICATE KEY UPDATE
					        username = VALUES(username)
					');
					//s = string, i - integer, d = double, b = blob for params of mysqli
					$stmt->bind_param("is", $usernameid, $username);
					//Package it all and execute the query
					$usernameid = $this->getUsernameID();
					$username = $this->getUsername();
					$stmt->execute();
					$stmt->close();
				}

				if(!empty($this->getPrivilege())) {
					//Delete are previous user permissions and update with the new set
					$query = "DELETE FROM user_roles WHERE userid='". res($userid) ."'";
					$result = qdb($query);

					// Running for loop to get all privileges
					foreach($this->getPrivilege() as $priv) {
						// Prepare and Bind for Privileges
						$stmt = $WLI->prepare('
							INSERT INTO user_roles (userid, privilegeid) 
								VALUES (?, ?) 
						');
						//s = string, i - integer, d = double, b = blob for params of mysqli
						$stmt->bind_param("ii", $userid, $privid);
						//Package it all and execute the query
						$privid = $priv;
						$stmt->execute();
						$stmt->close();
					}
				} else {
					//Else if the user has no privileges then give him Guest Access
					$query = "DELETE FROM user_roles WHERE userid='". res($userid) ."'";
					$result = qdb($query);
					
					$query = "SELECT id FROM user_privileges WHERE privilege='Guest'";
					$result = qdb($query);
		
					if(mysqli_num_rows($result) > 0){
						$exists = true;
		
						//Since this seems to be a very widely used ID lets make it globally available
						while ($row = $result->fetch_assoc()) {
						  $privid = $row['id'];
						}
						
						$stmt = $WLI->prepare('
							INSERT INTO user_roles (userid, privilegeid) 
								VALUES (?, ?) 
						');
						//s = string, i - integer, d = double, b = blob for params of mysqli
						$stmt->bind_param("ii", $userid, $privid);
						//Package it all and execute the query
						$stmt->execute();
						$stmt->close();
					}
				}

				// Running for loop to get all privileges
				if(!empty($this->getServiceClass())) {
					$query = "DELETE FROM user_classes WHERE userid='". res($userid) ."'";
					$result = qdb($query);

					foreach($this->getServiceClass() as $priv) {
						// Prepare and Bind for Privileges
						$stmt = $WLI->prepare('
							INSERT INTO user_classes (userid, classid) 
								VALUES (?, ?) 
						');
						//s = string, i - integer, d = double, b = blob for params of mysqli
						$stmt->bind_param("ii", $userid, $privid);
						//Package it all and execute the query
						$privid = $priv;
						$stmt->execute();
						$stmt->close();
					}
				} 
				//Test Bench
				//echo $usernameid . ' ' . $emailid . ' ' . $name . ' ' . $username . ' ' . $contactid;

	    	} else if($op == 'reset') {

	    		//Reset function grabs the users current password and updates it to the new one
	    		$stmt = $WLI->prepare('
					UPDATE users 
						SET encrypted_pass = ?, init = ? WHERE id = ? 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("sii", $encrypted_pass, $init, $userid);
				//Package it all and execute the query
				$encrypted_pass = $this->getPassword();
				$init = 0;
				$userid = $this->getUserID();
				$stmt->execute();
				//Get the emailid to be used in User table
				$stmt->close();
	    	} else if($op == 'deactivate') {
	    		//Query to go thru all user data and delete them from the database
				
				$stmt = $WLI->prepare('
					INSERT INTO contacts (id, status) 
						VALUES (?, ?) 
						ON DUPLICATE KEY UPDATE
				        status = VALUES(status)
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("is", $contactid, $status);
				$contactid = $this->getContactID();
				$status = "Inactive";
				$stmt->execute();
				$stmt->close();
				
	    	} else if($op == 'activate') {
	    		//Query to go thru all user data and delete them from the database
				
				$stmt = $WLI->prepare('
					INSERT INTO contacts (id, status) 
						VALUES (?, ?) 
						ON DUPLICATE KEY UPDATE
				        status = VALUES(status)
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("is", $contactid, $status);
				$contactid = $this->getContactID();
				$status = "Active";
				$stmt->execute();
				$stmt->close();
				
			//This is the block that inserts or updates the value of each column to determine password policy
	    	} else if($op == 'password_policy') {
	    		//Delete all rows and update with the current rows
	    		$query = "TRUNCATE TABLE password_policy";
	    		$result = qdb($query);

	    		$policy = $this->Sanitize($_REQUEST['policy']);

	    		$keys = array_keys($policy);
	    		$keyCounter = 0;
	    		//Run a foreach loop to generate queries and update all the fields
	    		foreach($policy as $type => $value) {

	    			$query = "INSERT INTO password_policy (policy, setting)";
	    			$query .= "VALUES(" . $keys[$keyCounter]  . "," . $value . ")";
	    			$result = qdb($query);
	    			$keyCounter++;
	    		}
	    	}
	    }

	    function savePin() {
	    	global $WLI;

	    	//If the password is null lets not update it
			$encrypted_pin = $this->getPin();
			$userid = $this->getUserID();

			if(! empty($encrypted_pin)) {
				//Prepare and Bind for Users
				$stmt = $WLI->prepare('
					UPDATE users SET encrypted_pin = ? WHERE id = ?
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("si", $encrypted_pin, $userid);
				$stmt->execute();
				$stmt->close();
			}
	    }

	    //Function to check if the username already exists in the database
	    //Should add a new parameter to also allow email checks
	    function checkUsername($username = '', $form = '') {
	    	$exists = false;

	    	$query = "SELECT * FROM usernames WHERE username='". res($username) ."'";
			$result = qdb($query);

			if(mysqli_num_rows($result) > 0){
				$exists = true;

				//Since this seems to be a very widely used ID lets make it globally available
				$row = mysqli_fetch_assoc($result);
				$userid = $row['userid'];
				$emailid = $row['emailid'];
			}

			if($exists) {
				$this->setUserID($userid);
				$this->setEmailID($emailid);
			} else if($form == 'login') {
				$this->setError('Wrong Username or Password.');
			}

	    	return $exists;
	    }
	    
	    //Get the users email
	    //This runs together with checkUsername with the defined userID & emailID
	    function checkEmailtoUsername($email) {
	    	$emailid = $this->getEmailID();
	    	$emailDB = '';
	    	
	    	$query = "SELECT * FROM emails WHERE id='". res($emailid) ."'";
			$result = qdb($query);

			if (mysqli_num_rows($result)>0) {
				$row = mysqli_fetch_assoc($result);
				$emailDB = $row['email'];
			}
			
			$this->setEmail($emailDB);
			
			if(strtolower($email) == strtolower($emailDB)) {
				return true;
			}
			return false;
	    }

	    //Get the users id from user token
	    function getTokenID() {
	    	$query = "SELECT userid from user_tokens WHERE user_token = '" . res($this->getToken()) . "'";
	    	$result = qdb($query);

	    	if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$userid = $r['userid'];
			}

	    	return $userid;
	    }

	    //Function to get all Company Names from the database to be displayed by anywhere it is called
		//Creating a function so we dont need to do any database calls outside the classes
		function getCompanyNames() {

			$query = "SELECT name, id FROM companies ORDER BY id DESC";
			$result = qdb($query);

			while ($row = $result->fetch_assoc()) {
			  $results_array[] = $row;
			}

			return $results_array;
		}

		//Function to get all the available privileges
		function getPrivileges() {
			$query = "SELECT * FROM user_privileges ORDER BY id ASC";
			$result = qdb($query);

			while ($row = $result->fetch_assoc()) {
			  $results_array[] = $row;
			}

			return $results_array;
		}

		function getServiceClasses() {
			$query = "SELECT * FROM service_classes ORDER BY id ASC";
			$result = qdb($query);

			while ($row = $result->fetch_assoc()) {
			  $results_array[] = $row;
			}

			return $results_array;
		}

		//Currently this just returns the company id for usage
		//Check if the company exists in the database and decide what to do after
		function companyCheck($cn) {
			//This is for future development as the default will be Ventura Telephone
			$query = "SELECT * FROM companies WHERE name='". res($cn) ."'";
			$result = qdb($query);
			
			while ($row = $result->fetch_assoc()) {
			  $companyid = $row['id'];
			}

			return $companyid;
		}

		//Function used to authenticate the user
		//We will also use this to grab user information and see if this is the users first loggin of a admin generated password
		function authenticateUser($type = 'password') {
			$ePassword = '';
			$initLogin = '';
			$initexpiry = '';
			$gen_expiry = false;
			
			//Check if the password expiration for generated passwords is selected
			$query = "SELECT * FROM password_policy WHERE policy = 'gen_expiry'";
			$result = qdb($query);

			if(mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_assoc($result);
				$gen_expiry = true;
			}
				
			//Query to get the usernames login info only if the user exists
			$query = "SELECT encrypted_pass, init, expiry FROM users WHERE id ='" . res($this->getUserID()) . "'";
			$result = qdb($query);

			if(mysqli_num_rows($result) > 0){

				$row = mysqli_fetch_assoc($result);
				$ePassword = $row['encrypted_pass'];
				$initLogin = $row['init'];
				$initExpiry = $row['expiry'];

			}

			//Check credentials and if the passwords match up correctly move into the next phase of generating a user token or refresh a user token
			//Or set error message
			
			if($initLogin == 1 && $initExpiry < time() && $gen_expiry) {
				$this->setError('Password Expired. Please contact an admin for assistance.');
			} else if($this->getPassword() === $ePassword) {
				//Set if the user has a generated password
				$this->setGenerated($initLogin);
				$this->setGeneratedExp($initExpiry);
				$this->userToken();
			} else {
				$this->setError('Wrong Username or Password.');
			}
		}

		//Function used to generate the user token and refresh the token if they have logged on within a WEEK currently
		function userToken() {
			global $WLI;

			//Query to get the users token or generate a new token if there is none
			$query = "SELECT * FROM user_tokens WHERE userid ='" . res($this->getUserID()) . "'";
			$result = qdb($query);

			if(mysqli_num_rows($result) > 0){

				$r = mysqli_fetch_assoc($result);
				$userToken = $r['user_token'];
				$userTokenID = $r['id'];

				//If the user token exists then we will overwrite the randomly generated one and set that as the one we will be updating
				$this->setToken($userToken);

			} 

			//Insert or Update the User Token
			//Prepare and Bind for User Token
			$stmt = $WLI->prepare('
				INSERT INTO user_tokens (user_token, expiry, userid, id) 
					VALUES (?, ?, ?, ?) 
					ON DUPLICATE KEY UPDATE
			        expiry = VALUES(expiry)
			');

			//s = string, i - integer, d = double, b = blob for params of mysqli
			$stmt->bind_param("siii", $token, $token_length, $userid, $userTokenID);
			//Package it all and execute the query
			$token = $this->getToken();
			$token_length = $this->token_length;
			$userid = $this->getUserID();
			$stmt->execute();
			$stmt->close();

			//Set the User cookie for successful login and reload the page to redirect to homepage
			//setcookie('user_token',$this->getToken(), $this->token_length,'/');

			//Set User Session
			$_SESSION['loggedin'] = true;
			$_SESSION['user_token'] = $this->getToken();
			$_SESSION['expiry'] = $this->token_length;
			
			//Add a special variables for a user logging in for the first time with a admin generated password
			if($this->generated_pass == 1) {
				$_SESSION['init'] = true;
				$_SESSION['initexp'] = $this->generated_pass_exp;
			}
		}

		//Function generates an email for the user that is being created by the admin
		function SendUserConfirmationEmail() {
	        global $SEND_ERR;
	        
	        // send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			// $recipients can be either a single recipient string, or array($rec1,$rec2,etc)
			// $bcc is optional
			
			$email_body_html = "Greetings " . $this->user_firstName . " " . $this->user_lastName .",<br><br>";
			$email_body_html .= "Welcome to Stackbay! Here's how to log in:<br><br>";
			$email_body_html .= "Link: <a href ='https://www.stackbay.com'>Stackbay</a><br>";
			$email_body_html .= "Username: " . $this->getUsername() . "<br>";
			$email_body_html .= "Password: " . ($this->generated_pass == '1' ? htmlspecialchars($this->getTempPass()) : "User Preset") . "<br><br>";
			$email_body_html .= "If you have any problems, please contact an admin at admin@ven-tel.com.";
			$email_subject = 'Stackbay Registration';
			$recipients = $this->getEmail();
			$bcc = 'dev@ven-tel.com';
			
			$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			if ($send_success) {
			    // echo json_encode(array('message'=>'Success'));
			} else {
			    $this->setError(json_encode(array('message'=>$SEND_ERR)));
			}

	    }
	    
	    //Function generates an email for the user that is being created by the admin
		function resetPasswordEmail($username = '') {
	        global $SEND_ERR;
	        
	        // send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			// $recipients can be either a single recipient string, or array($rec1,$rec2,etc)
			// $bcc is optional
			
			$email_body_html = "Greetings " . $username .",<br><br>";
			$email_body_html .= "A request for a password recovery was placed on your account and verified Here is your new password:<br><br>";
			$email_body_html .= "Link: <a target='_blank' href ='" . $_SERVER['HTTP_HOST'] . "'>Stackbay</a><br>";
			$email_body_html .= "Username: " . $username . "<br>";
			$email_body_html .= "Password: Some Crazy Password Here<br><br>";
			$email_body_html .= "If you have any problems, please contact an admin at admin@ven-tel.com.";
			$email_subject = 'Stackbay Recovery';
			$recipients = $this->getEmail();
			$bcc = 'dev@ven-tel.com';
			
			$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			if ($send_success) {
			    // echo json_encode(array('message'=>'Success'));
			} else {
			    $this->setError(json_encode(array('message'=>$SEND_ERR)));
			}

	    }

		 /*
			Sanitize() function removes any potential threat from the
			data submitted. Prevents email injections or any other hacker attempts.
			if $remove_nl is true, newline chracters are removed from the input.
		*/
		function Sanitize($str,$remove_nl=true) {
		    $str = $this->StripSlashes($str);

		    if($remove_nl) {
		        $injections = array('/(\n+)/i',
		            '/(\r+)/i',
		            '/(\t+)/i',
		            '/(%0A+)/i',
		            '/(%0D+)/i',
		            '/(%08+)/i',
		            '/(%09+)/i'
		            );
		        $str = preg_replace($injections,'',$str);
		    }

		    return $str;
		}

		 function StripSlashes($str) {
	        if(get_magic_quotes_gpc()) {
	            $str = stripslashes($str);
	        }
	        return $str;
	    }        
	}
