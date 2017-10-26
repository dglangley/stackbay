<?php

	class venPin extends venPriv {

  		//Register a User Function
		function registerPin() {
			//Not that I don't trust you but lets prevent any Sequel Injections no matter what :)
			//Using $_REQUEST vs $_REQUEST the performance difference is negligible but POST > REQUEST and for better readability 
			$pin = '';
			$userid = 0;

			// Clean up mechanism for user entered values
			$pin = $this->Sanitize(trim($_REQUEST["pin"]));
			$userid = $this->Sanitize(trim($GLOBALS['U']['id']));

			//Test Bench Email
			// $register_email = trim("andrew@ven-tel.com");

			//Check if the user already has a pin set or not
			$query = "SELECT encrypted_pin FROM users WHERE id = '". res($userid) ."'; ";
			$result = qdb($query);

			//If user exists log an error else run and encrypt the user
			if (mysqli_num_rows($result)) {
				//print_r($result);
				$r = mysqli_fetch_assoc($result);

				if(empty($r['encrypted_pin'])) {
					$this->setUserID($userid);
					$this->bCrypt($pin, 'pin');

					$this->savePin();

					//$this->SendUserConfirmationEmail();
				}
			}
		}

	}
