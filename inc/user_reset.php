<?php

	//Reset class for the overall user access portal
	class venReset extends venPriv {

  		//User Function to Reset the Users password
		function resetMember() {
			//Get the users entered password and sanitize it for injections
			//Never bad to be too safe
			$newPassword = $this->Sanitize(trim($_POST["new-password"]));

			//Get user id from token and set variables
			$usertoken = $this->Sanitize($_SESSION["user_token"]);
			$this->setToken($usertoken);
			$userid = $this->getTokenID();
			$this->setUserID($userid);
			$this->setTempPass($newPassword);


			if($this->validPassword($newPassword)) {
				$this->bCrypt($this->getTempPass());

				$this->savetoDatabase('reset');

				//Send Verfication Email to User
				//Incomplete
				$this->SendUserConfirmationEmail();

				// Unset all of the session variables.
				$_SESSION = array();

				// If it's desired to kill the session, also delete the session cookie.
				// Note: This will destroy the session, and not just the session data!
				if (ini_get("session.use_cookies")) {
				    $params = session_get_cookie_params();
				    setcookie(session_name(), '', time() - 42000,
				        $params["path"], $params["domain"],
				        $params["secure"], $params["httponly"]
				    );
				}

    			session_destroy(); // Destroy the session

				header('Location: /marketmanager/index.php?reset=true');
				exit;
			}
		}

	}
