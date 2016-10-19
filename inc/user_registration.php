<?php

	class venRegistration extends venPriv {

  		//Register a User Function
		function registerMember() {
			//Not that I don't trust you but lets prevent any Sequel Injections no matter what :)
			//Using $_POST vs $_REQUEST the performance difference is negligible but POST > REQUEST and for better readability 
			//Since emails is a possible login name we don't want it to be case sensitive
			$register_email = strtolower($this->Sanitize(trim($_POST["email"])));
			//Make username case insenitive
			$register_username = strtolower($this->Sanitize(trim($_POST["username"])));
			$register_type = $this->Sanitize(trim($_POST["type"]));
			$register_company = $this->Sanitize(trim($_POST["companyid"]));
			$generated_pass = $this->Sanitize(trim($_POST["generated_pass"]));
			$privilege = $this->Sanitize($_REQUEST['privilege']);
			$phone = $this->Sanitize($_REQUEST['phone']);

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

			//Check if the user Email already exists Sanitize for sql add in mysql escape
			$query = "SELECT * FROM emails WHERE email = '". res($register_email) ."'; ";
			$result = qdb($query);

			//If user exists log an error else run and encrypt the user
			if (mysqli_num_rows($result)>0) {
				//print_r($result);
				$this->setError("User: $register_email already exists in the database.");
			} else {
				//Set First and Last Name into the global variables
				$this->setFirst($this->Sanitize(trim($_POST["firstName"])));
				$this->setLast($this->Sanitize(trim($_POST["lastName"])));

				//Test Bench Password
				// $this->setFirst($this->Sanitize(trim('Andrew')));
				// $this->setLast($this->Sanitize(trim('Kuan')));

				//Future Dev: Think of a way to handle Company Name

				//Because Admin is creating the user and can change the passwords of any user we will not validate to make sure they match.
				$register_password = $this->Sanitize(trim($_POST["password"]));

				$this->setTempPass($register_company);
			
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
