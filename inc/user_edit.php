<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/getUser.php';

	class venEdit extends venPriv {

  		//Edit a User Function
		function editMember() {
			//Initiate the variables if the user exists and is being edited
			$userid = 0;
			if(isset($_REQUEST["user"])) {
				$userid = strtolower($this->Sanitize(trim($_REQUEST["user"])));
			}

			//Check if the userid is empty, if so lets check if the user token from the session exists to determine if the page is personal user information editing
			if(empty($userid)) {
				$usertoken = $this->Sanitize($_SESSION["user_token"]);
				$this->setToken($usertoken);
				$userid = $this->getTokenID();

				$userEdited = true;
			}

			$this->setUserID($userid);
			$this->getUserInfo();
		}

		//These functions below I dont see it being used alot so keeping it within this file to offload the amount of data user_access has
		//This function is to grab all usernames
	    function getAllUsers() {
	    	$username = array();
	    	$query = "SELECT usernames.username, usernames.emailid, usernames.userid, users.contactid, contacts.status FROM usernames JOIN users ON usernames.userid = users.id JOIN contacts ON contacts.id = users.contactid ORDER BY contacts.status ASC, usernames.id ASC";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $usernames[] = $row;
			}

			return $usernames;
	    }
	    
	    function getAllSalesUsers() {
	    	$username = array();
	    	//This is dirty and seriously needs cleaning its horrific and will burn your eyes out...
	    	$query = "SELECT usernames.username, usernames.emailid, usernames.userid, users.contactid, user_privileges.privilege, contacts.name, users.commission_rate ";
			$query .= "FROM usernames ";
			$query .= "JOIN users ON usernames.userid = users.id ";
			$query .= "JOIN contacts ON contacts.id = users.contactid ";
			$query .= "JOIN user_roles ON usernames.userid = user_roles.userid ";
			$query .= "JOIN user_privileges ON user_roles.privilegeid = user_privileges.id ";
			$query .= "WHERE (user_privileges.privilege = 'Management' OR user_privileges.privilege = 'Sales') ";
			$query .= "AND contacts.status = 'Active' ";
			$query .= "GROUP BY users.id ORDER BY contacts.status ASC, usernames.id ASC; ";
	    	$result = qdb($query) OR die(qe().'<BR>'.$query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = mysqli_fetch_assoc($result)) {
			  $usernames[] = $row;
			}

			return $usernames;
	    }

	    //Get specific email from emailid, or vice versa
	    function chkEmail($searchstr,$input_field='id',$output_field='email') {
	    	$email = ''; 
			$id = 0;

			// some garbage request, should only be email or id
			if ($output_field<>'email' AND $output_field<>'id') { return false; }

	    	$query = "SELECT * FROM emails WHERE ".$input_field."='". res($searchstr) ."'";
			$result = qdb($query);
			//get the users email and return it
			if (mysqli_num_rows($result)>0) {
				$row = $result->fetch_assoc();
			    $email = $row['email'];
				$id = $row['id'];
			}

			//Save all IDS 
			$this->setEmailID($id);
	    	$this->setEmail($email);
	    	return $$output_field;
	    }

		function getName($str) {
			$name = getUser($str);

			$this->setName($name);
			return ($name);
		}

		function getTitle($str) {
			$title = getUser($str,'id','title');

			$this->setTitle($title);
			return ($title);
		}

	    //Get the user information based on their ID
	    function idtoUser() {
	    	$userInfo = array();
	    	//Don't allow query to pull the encrypted password 
	    	$query = "SELECT contactid, login_emailid from users WHERE id = '" . res($this->getUserID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userInfo[] = $row;
			}
			//Get the users email and store it
			$this->chkEmail($userInfo[0]['login_emailid']);
			$this->setContactID($userInfo[0]['contactid']);
	    }

	   	//Set the variables for the user first and last name and company
	    function getContactInfo() {
	    	//Get the users contact information
	    	$query = "SELECT name, title, companyid, status from contacts WHERE id = '" . res($this->getContactID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $contactInfo[] = $row;
			}

			if($contactInfo[0]['status'] == 'Active') {
				$this->setStatus('checked');
			}

			$nameSplit = explode(' ', $contactInfo[0]['name']);

			$this->setFirst($nameSplit[0]);
			$this->setLast($nameSplit[1]);
			$this->setTitle($contactInfo[0]['title']);
	    }

	    function getUsernameInfo() {
	    	 $usernameInfo = array();
	    	//Get the users contact information
	    	$query = "SELECT username, id from usernames WHERE userid = '" . res($this->getUserID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $usernameInfo[] = $row;
			}

			$this->setUsername($usernameInfo[0]['username']);
			$this->setUsernameID($usernameInfo[0]['id']);
	    }

	    function getUserPrivileges() {
	    	$userPrivileges = array();
	    	//Get all of the users privileges
	    	$query = "SELECT privilegeid from user_roles WHERE userid = '" . res($this->getUserID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userPrivileges[] = $row['privilegeid'];
			}

			$this->setPrivilege($userPrivileges);
	    }

	    function getUserServiceClasses() {
	    	$userServiceClasses = array();
	    	//Get all of the users privileges
	    	$query = "SELECT classid from user_classes WHERE userid = '" . res($this->getUserID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userServiceClasses[] = $row['classid'];
			}

			$this->setServiceClass($userServiceClasses);
	    }

	    function getPrivilegeTitle($userID) {
	    	$userPrivileges = array();
	    	$query = "SELECT privilegeid from user_roles WHERE userid = '" . res($userID) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userPrivileges[] = $row['privilegeid'];
			}

			$userPrivilegesName = array();
			foreach($userPrivileges as $privID) {
				$query = "SELECT privilege from user_privileges WHERE id = '" . res($privID) . "'";
		    	$result = qdb($query);

		    	//Check is any rows exists then populate all the results into an array
				while ($row = $result->fetch_assoc()) {
				  $userPrivilegesName[] = $row['privilege'];
				}
			}
			return $userPrivilegesName;
	    	
	    }
	    
	    //Function to get the users status based on the users id -> Users -> Contactid to contact contacts.status
	    function getUserStatus($userid) {
	    	$status = '';
			
	    	$query = "SELECT status FROM contacts WHERE id= (SELECT contactid FROM users WHERE id='" . res($userid) . "');";
	    	$result = qdb($query);

	    	//if the user edit policy exsts then allow user to edit their own password
	    	if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$status = $r['status'];
			}
			
	    	return $status;
	    }

	    function hourlyRate($userid) {
	    	$hourly_rate = '';
			
	    	$query = "SELECT hourly_rate FROM users WHERE id = '" . res($userid) . "';";
	    	$result = qdb($query);

	    	//if the user edit policy exsts then allow user to edit their own password
	    	if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$hourly_rate = $r['hourly_rate'];
			}
			
	    	return $hourly_rate;
	    }

	    //Check the password policy and see if the user has access to change the password
	    function checkPasswordPolicy() {
	    	$query = "SELECT policy from password_policy WHERE policy = 'user_edit'";
	    	$result = qdb($query);

	    	//if the user edit policy exsts then allow user to edit their own password
	    	if (mysqli_num_rows($result)>0) {
				return true;
			}

			return false;
	    }

	    function getUserPhone() {
	    	$phone = '';
	    	//Get the users phone number
	    	$query = "SELECT phone from phones WHERE contactid = '" . res($this->getContactID()) . "'";
	    	$result = qdb($query);

	    	if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$phone = $r['phone'];
			}
			$this->setPhone($phone);
	    }

	    //Grab all the users information to be used on the edit the user field
	    //All the information will be pushed into the global variables set by user_access.php
	    function getUserInfo() {
	    	//Get all the defined user variables
	    	$this->idtoUser();
	    	$this->getContactInfo();
	    	$this->getUsernameInfo();
	    	$this->getUserPrivileges();
	    	$this->getUserServiceClasses();
	    	$this->getUserPhone();
	    }

	    function editUser($userEdited = false) {
	    	$new_username = '';
	    	$new_email = '';
	    	$new_password = '';
	    	$new_pin = '';
	    	$new_status = '';
	    	$new_privilege = '';
	    	$new_class = '';
	    	$new_phone = '';
	    	$generated_pass = 0;

	    	$this->getUserInfo();
	    	//Define the new settings and see which ones have changed
	    	if(isset($_REQUEST["username"])) {
		    	$new_username = $this->Sanitize($_REQUEST['username']);
		    }
		    if(isset($_REQUEST["hourly_rate"])) {
		    	$hourly_rate = $this->Sanitize($_REQUEST['hourly_rate']);
		    }
	    	if(isset($_REQUEST["email"])) {
		    	$new_email = $this->Sanitize($_REQUEST['email']);
		    }
	    	if(isset($_REQUEST["password"])) {
		    	$new_password = $this->Sanitize($_REQUEST['password']);
		    }
		    if(isset($_REQUEST["pin"])) {
		    	$new_pin = $this->Sanitize($_REQUEST['pin']);
		    }

	    	$new_company = ( isset($_REQUEST['companyid']) ? $this->Sanitize( $_REQUEST['companyid']) : 25);
	    	
	    	if(isset($_REQUEST["status"])) {
		    	$new_status = $this->Sanitize($_REQUEST['status']);
		    }
		    if(isset($_REQUEST["privilege"])) {
		    	$new_privilege = $this->Sanitize($_REQUEST['privilege']);
		    }
		     if(isset($_REQUEST["service_class"])) {
		    	$new_class = $this->Sanitize($_REQUEST['service_class']);
		    }
		    if(isset($_REQUEST["phone"])) {
		    	$new_phone = $this->Sanitize($_REQUEST['phone']);
		    }

	    	if(isset($_REQUEST["generated_pass"])) {
	    		$generated_pass = $this->Sanitize(trim($_REQUEST["generated_pass"]));
	    	}

	    	$this->setGenerated((!empty($generated_pass) ? 1 : 0));
	    	$this->setPhone($new_phone);
	    	$this->setHourly($hourly_rate);

	    	//Check for first name and the last name
	    	if(isset($_REQUEST['firstName']) && $_REQUEST['firstName'] != '') {
	    		$this->setFirst($this->Sanitize($_REQUEST['firstName']));
	    	}

	    	if(isset($_REQUEST['lastName']) && $_REQUEST['lastName'] != '') {
	    		$this->setLast($this->Sanitize($_REQUEST['lastName']));
	    	}

	    	if(isset($_REQUEST['title']) && $_REQUEST['title'] != '') {
	    		$this->setTitle($this->Sanitize($_REQUEST['title']));
	    	}

	    	//Set Company Name
	    	$this->setCompany($new_company);

	    	//If the New Username inputted is not the same as the current and it does not exists then update the username
	    	if(!empty($new_username) && $new_username != $this->getUsername() && !$this->checkUsername($new_username)) {
	    		$this->setUsername($new_username);
	    	} else if(!empty($new_username) && $new_username != $this->getUsername()) {
	    		//User Exists! Set Error Verbage
	    		$this->setError("User <strong>" . $new_username . "</strong> already exists.");
	    	}

	    	if($new_email != $this->getEmail() && filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
	    		$this->setEmail($new_email);
	    	}

	    	//Check if there is a new password and valid that it is strong
	    	if(isset($new_password) && $new_password != '') {
	    		if($this->validPassword($new_password)) {
	    			$this->bCrypt($new_password);
	    		}
	    	}

	    	if(! empty($new_pin)) {
	    		// Adding 2nd parameter sets the pin instead of the password in the encrypted variables
	    		// Valid pin a simple function that allows future advancements in pin requirements (Currently must be a digit and a minimum of 4 characters)
	    		if($this->validPin($new_pin)) {
					$this->bCrypt($new_pin, 'pin');
				}
			}

	    	//Check if the user status is still active or not
	    	//If blank then is inactive, remove the checked from active status and update database accordingly
	    	if($new_status == '') {
	    		$this->setStatus('');
	    	} else {
	    		$this->setStatus('checked');
	    	}

	    	//Set the user privileges if it is set and not empty
	    	if(isset($new_privilege) && $new_privilege != '') {
	    		//Run function set an array of the selected Privilege IDs to user privileges
	    		$this->setPrivilege($new_privilege);
	    	}

	    	//Set the user privileges if it is set and not empty
	    	if(isset($new_class) && $new_class != '') {
	    		//Run function set an array of the selected Privilege IDs to user privileges
	    		$this->setServiceClass($new_class);
	    	}

	    	//If no errors are present
	    	$errors = $this->getError();
	    	if(!isset($errors)) {
	    		// savePin saves the pin post encryption
				$this->savePin();
				
	    		$this->savetoDatabase('update');

	    		return true;
	    	}

	    	return false;
	    }

	    //Function to kill a user or remove a user, whichever verbage you prefer works
	    function deactivateUser() {
	    	//Set userid and set all the ID's needed to delete the user from contact id to email id
	    	$userid = strtolower($this->Sanitize(trim($_GET["deactivate"])));
			$this->setUserID($userid);
			$this->getUserInfo();

			//Test bench to make sure all the data is being populated correctly
	    	// echo $this->getUserID() . '<br>';
	    	// echo $this->getContactID() . '<br>';
	    	// echo $this->getUsernameID() . '<br>';
	    	// echo $this->getEmailID() . '<br>';

	    	//Invoke the function to delete and destroy the user
	    	$this->savetoDatabase('deactivate');
	    }
	    
	    function activateUser() {
	    	//Set userid and set all the ID's needed to delete the user from contact id to email id
	    	$userid = strtolower($this->Sanitize(trim($_GET["activate"])));
			$this->setUserID($userid);
			$this->getUserInfo();

			//Test bench to make sure all the data is being populated correctly
	    	// echo $this->getUserID() . '<br>';
	    	// echo $this->getContactID() . '<br>';
	    	// echo $this->getUsernameID() . '<br>';
	    	// echo $this->getEmailID() . '<br>';

	    	//Invoke the function to delete and destroy the user
	    	$this->savetoDatabase('activate');
	    }
	    
	    //Register a User Function
		function registerMember() {
			//Not that I don't trust you but lets prevent any Sequel Injections no matter what :)
			//Using $_REQUEST vs $_REQUEST the performance difference is negligible but POST > REQUEST and for better readability 
			$register_email = '';
			$register_username = '';
			$register_type = '';
			$generated_pass = '';
			$privilege = '';
			$phone = '';

			//Since emails is a possible login name we don't want it to be case sensitive
			$register_email = strtolower($this->Sanitize(trim($_REQUEST["email"])));
			//Make username case insenitive
			$register_username = strtolower($this->Sanitize(trim($_REQUEST["username"])));
			
			if(isset($_REQUEST["type"])) {
				$register_type = $this->Sanitize(trim($_REQUEST["type"]));
			}

			$register_company = ( isset($_REQUEST['companyid']) ? $this->Sanitize( $_REQUEST['companyid']) : 25);
			
			if(isset($_REQUEST["generated_pass"])) {
				$generated_pass = $this->Sanitize(trim($_REQUEST["generated_pass"]));
			}
			$privilege = $this->Sanitize($_REQUEST['privilege']);

			if(isset($_REQUEST["phone"])) {
				$phone = $this->Sanitize($_REQUEST['phone']);
			}

			if(isset($_REQUEST["hourly_rate"])) {
		    	$hourly_rate = $this->Sanitize($_REQUEST['hourly_rate']);
		    }

			//Test Bench Email
			// $register_email = trim("andrew@ven-tel.com");

			//Set all user admin variables
			$this->setEmail($register_email);
			$this->setUsername($register_username);
			$this->setType($register_type);
			$this->setCompany($register_company);
			//If the admin generated the password then set 1 for true as a tinyint stored in database
			$this->setGenerated(($generated_pass ? 1 : 0));

			//Run function set an array of the selected Privilege IDs to user privileges
    		$this->setPrivilege($privilege);
    		$this->setPhone($phone);
    		$this->setHourly($hourly_rate);

			//Check if the user Email already exists Sanitize for sql add in mysql escape
			$query = "SELECT * FROM emails WHERE email = '". res($register_email) ."'; ";
			$result = qdb($query);

			//If user exists log an error else run and encrypt the user
			if (mysqli_num_rows($result)>0) {
				//print_r($result);
				$this->setError("User: $register_email already exists in the database.");
			} else {
				//Set First and Last Name into the global variables
				$this->setFirst($this->Sanitize(trim($_REQUEST["firstName"])));
				$this->setLast($this->Sanitize(trim($_REQUEST["lastName"])));
				$this->setTitle($this->Sanitize(trim($_REQUEST["title"])));

				//Test Bench Password
				// $this->setFirst($this->Sanitize(trim('Andrew')));
				// $this->setLast($this->Sanitize(trim('Kuan')));

				//Future Dev: Think of a way to handle Company Name

				//Because Admin is creating the user and can change the passwords of any user we will not validate to make sure they match.
				$register_password = $this->Sanitize(trim($_REQUEST["password"]));

				$this->setTempPass($register_password);
			
				//Test Bench Password
				// $register_password = "A12@fdhlkj";

				//If the password is set and good to go lets encrypt the password and set it into the global private variable
				if(isset($register_password) && $this->validPassword($register_password)) {
					$this->bCrypt($register_password);

					$this->savetoDatabase('insert');

					//Send Verfication Email to User
					//Incomplete
					$this->SendUserConfirmationEmail();
				}
			}
		}

	}
