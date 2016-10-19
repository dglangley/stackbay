<?php

	//Reset class for the overall user access portal
	class venReset extends venPriv {

  		//User Function to Reset the Users password
		function resetMember() {
			//Get the users entered password and sanitize it for injections
			//Never bad to be too safe
			$newPassword = $this->Sanitize(trim($_POST["new-password"]));

			//Set Global Class Variables and since we have a sesssion set the username and get the id using checkUsername function
			$this->setTempPass($newPassword);
			$this->setUsername($_SESSION['username']);

			if($this->validPassword($newPassword)) {
				$this->checkUsername($this->getUsername());
				$this->bCrypt($this->getTempPass());

				$this->savetoDatabase('reset');

				//Send Verfication Email to User
				//Incomplete
				$this->SendUserConfirmationEmail();

				session_start();
    			session_destroy(); // Destroy the session

				header('Location: /marketmanager/index.php?reset=true');
				exit;
			}
		}

	}
