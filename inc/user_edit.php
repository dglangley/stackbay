<?php

	class venEdit extends venPriv {

  		//Edit a User Function
		function editMember() {
			//Initiate the variables if the user exists and is being edited
			$userid = strtolower($this->Sanitize(trim($_GET["user"])));

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
	    	$username = '';
	    	$query = "SELECT * from usernames";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $usernames[] = $row;
			}

			return $usernames;
	    }

	    //Get specific email from emailid
	    function idtoEmail($emailid) {
	    	$query = "SELECT * FROM emails WHERE id='". res($emailid) ."'";
			$result = qdb($query);
			//get the users email and return it
			while ($row = $result->fetch_assoc()) {
			  $email = $row['email'];
			}

			//Save all IDS 
			$this->setEmailID($emailid);
	    	$this->setEmail($email);
	    	return $email;
	    }

	    //Get the user information based on their ID
	    function idtoUser() {
	    	//Don't allow query to pull the encrypted password 
	    	$query = "SELECT contactid, login_emailid from users WHERE id = '" . res($this->getUserID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userInfo[] = $row;
			}
			//Get the users email and store it
			$this->idtoEmail($userInfo[0]['login_emailid']);
			$this->setContactID($userInfo[0]['contactid']);
	    }

	   	//Set the variables for the user first and last name and company
	    function getContactInfo() {
	    	//Get the users contact information
	    	$query = "SELECT name, companyid, status from contacts WHERE id = '" . res($this->getContactID()) . "'";
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
	    }

	    function getUsernameInfo() {
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
	    	//Get all of the users privileges
	    	$query = "SELECT privilegeid from user_roles WHERE userid = '" . res($this->getUserID()) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userPrivileges[] = $row['privilegeid'];
			}

			$this->setPrivilege($userPrivileges);
	    }

	    function getPrivilegeTitle($userID) {
	    	$query = "SELECT privilegeid from user_roles WHERE userid = '" . res($userID) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $userPrivileges[] = $row['privilegeid'];
			}

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

	    function getTokenID() {
	    	$query = "SELECT userid from user_tokens WHERE user_token = '" . res($this->getToken()) . "'";
	    	$result = qdb($query);

	    	if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$userid = $r['userid'];
			}

	    	return $userid;
	    }

	    function getUserPhone() {
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
	    	$this->getUserPhone();
	    }

	    function editUser($userEdited = false) {
	    	$generated_pass; 
	    	$new_username;
	    	$new_email;
	    	$new_password;
	    	$new_status;
	    	$new_privilege;
	    	$new_phone;
	    	$generated_pass;

	    	$this->getUserInfo();
	    	//Define the new settings and see which ones have changed
	    	if(isset($_REQUEST["username"])) {
		    	$new_username = $this->Sanitize($_REQUEST['username']);
		    }
	    	if(isset($_REQUEST["email"])) {
		    	$new_email = $this->Sanitize($_REQUEST['email']);
		    }
	    	if(isset($_REQUEST["password"])) {
		    	$new_password = $this->Sanitize($_REQUEST['password']);
		    }

	    	$new_company = ( isset($_REQUEST['companyid']) ? $this->Sanitize( $_REQUEST['companyid']) : 25);
	    	
	    	if(isset($_REQUEST["status"])) {
		    	$new_status = $this->Sanitize($_REQUEST['status']);
		    }
		    if(isset($_REQUEST["privilege"])) {
		    	$new_privilege = $this->Sanitize($_REQUEST['privilege']);
		    }
		    if(isset($_REQUEST["phone"])) {
		    	$new_phone = $this->Sanitize($_REQUEST['phone']);
		    }

	    	if(isset($_REQUEST["generated_pass"])) {
	    		$generated_pass = $this->Sanitize(trim($_REQUEST["generated_pass"]));
	    	}

	    	$this->setGenerated((!empty($generated_pass) ? 1 : 0));
	    	$this->setPhone($new_phone);

	    	//Check for first name and the last name
	    	if(isset($_REQUEST['firstName']) && $_REQUEST['firstName'] != '') {
	    		$this->setFirst($this->Sanitize($_REQUEST['firstName']));
	    	}

	    	if(isset($_REQUEST['lastName']) && $_REQUEST['lastName'] != '') {
	    		$this->setLast($this->Sanitize($_REQUEST['lastName']));
	    	}

	    	//Set Company Name
	    	$this->setCompany($new_company);

	    	//If the New Username inputted is not the same as the current and it does not exists then update the username
	    	if($new_username != $this->getUsername() && !$this->checkUsername($new_username) && !empty($new_username)) {
	    		$this->setUsername($new_username);
	    	} else if($new_username != $this->getUsername() && !empty($new_username)) {
	    		//User Exists! Set Error Verbage
	    		$this->setError("User <strong>" . $new_username . "</strong> already exists.");
	    	}

	    	if($new_email != $this->getEmail() && filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
	    		$this->setEmail($new_email);
	    	}

	    	//Check if there is a new password and valid that it is strong
	    	if(isset($new_password) && $new_password != '') {
	    		if($this->validPassword($new_password)) {
	    			$this->bcrypt($new_password);
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

	    	//If no errors are present
	    	$errors = $this->getError();
	    	if(!isset($errors)) {
	    		$this->savetoDatabase('update');
	    		return true;
	    	}

	    	return false;
	    }

	    //Function to kill a user or remove a user, whichever verbage you prefer works
	    function deleteUser() {
	    	//Set userid and set all the ID's needed to delete the user from contact id to email id
	    	$userid = strtolower($this->Sanitize(trim($_GET["delete"])));
			$this->setUserID($userid);
			$this->getUserInfo();

			//Test bench to make sure all the data is being populated correctly
	    	// echo $this->getUserID() . '<br>';
	    	// echo $this->getContactID() . '<br>';
	    	// echo $this->getUsernameID() . '<br>';
	    	// echo $this->getEmailID() . '<br>';

	    	//Invoke the function to delete and destroy the user
	    	$this->savetoDatabase('delete');
	    }

	}
