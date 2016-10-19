<?php

	class venPolicy extends venPriv {

  		//Set Password Policy Features
		function getPolicy() {
			$query = "SELECT * from password_policy";
	    	$result = qdb($query);

	    	//return associative array of all the set policies for the user password
			while ($row = $result->fetch_assoc()) {
			  $setPolicies[$row['policy']] = $row['setting'];
			}
			return $setPolicies;
		}

	}
