<?php
	//Login class for the overall user access portal
	class venLogin extends venPriv {

  		//User Function to login that extends the main class Ven Priveleges
		function loginMember() {			
			//Sanitize right away for any sequel injections before even dealing with user input values
			$username = '';
			$email = '';
			$type = 'password';
			
			//If no an email then user is using username otherwise login with an email check
			if (!filter_var($_POST["username"], FILTER_VALIDATE_EMAIL)) {
				$username = strtolower($this->Sanitize(trim($_POST["username"])));
			} else {
				$email = strtolower($this->Sanitize(trim($_POST["username"])));
			}

			//set up the userID and specify this is the login form being used
			$this->checkUsername($username, 'login');

			//Clean up the password field from any possible injections
			$user_password = $this->Sanitize(trim($_POST["password"]));

			//Clean up the password field from any possible injections
			$user_pin = $this->Sanitize(trim($_POST["pin"]));

			// $_SESSION['username'] = $_POST["username"];
			// $_SESSION['user_password'] = $_POST["password"];

			setcookie('sb_username', $_POST["username"]);
			setcookie('sb_user_password', $_POST["password"]);

			if(empty($user_password) && $user_pin) {
				// Passing off the pin as the users way of logging in
				$user_password = $user_pin;
				$type = 'pin';
			}

			//Set all user defined variables either email or username will be empty and null
			$this->setEmail($email);
			$this->setUsername($username);
			$this->setTempPass($user_password);

			//Run Encryption to get encrypted password of user entry
			$this->bCrypt($this->getTempPass());

			//Authenticate the user password and create a cookie/token/expiry for the user if the password is correct
			//Parameter sets the length of the token
			$this->authenticateUser($type);
		}

	}
