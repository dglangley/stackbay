<?php

	//class for page permissions that has all the functions of venPriv
	class venEdit extends venPriv {
		function editPage() {
			global $WLI;

			$pageName = $this->Sanitize($_REQUEST['pagename']);
			$privileges = $this->Sanitize($_REQUEST['privilege']);

			$this->setPrivilege($privileges);

			// echo $pageName;
			// print_r($privileges);

			//Delete are previous page permissions and update with the new set
			$query = "DELETE FROM page_roles WHERE page='". res($pageName) ."'";
			$result = qdb($query);

			// Running for loop to get all privileges
			foreach($this->getPrivilege() as $priv) {
				// Prepare and Bind for Privileges
				$stmt = $WLI->prepare('
					INSERT INTO page_roles (page, privilegeid) 
						VALUES (?, ?) 
				');
				//s = string, i - integer, d = double, b = blob for params of mysqli
				$stmt->bind_param("si", $pageName, $privid);
				//Package it all and execute the query
				$privid = $priv;
				$stmt->execute();
				$stmt->close();
			}
		}

		//Get all the page names that have a privilege attached to them
		function getPrivPages() {
			$pages = '';
	    	$query = "SELECT DISTINCT page from page_roles";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $pages[] = $row;
			}

			return $pages;
		}

		function getPrivsofPage($page) {
			$pageRoles = '';
	    	$query = "SELECT privilegeid from page_roles WHERE page = '" . res($page) . "'";
	    	$result = qdb($query);

	    	//Check is any rows exists then populate all the results into an array
			while ($row = $result->fetch_assoc()) {
			  $pageRoles[] = $row;
			}

			foreach($pageRoles as $privID) {
				$query = "SELECT privilege from user_privileges WHERE id = '" . res($privID['privilegeid']) . "'";
		    	$result = qdb($query);

		    	//Check is any rows exists then populate all the results into an array
				while ($row = $result->fetch_assoc()) {
				  $pageRoleNames[] = $row['privilege'];
				}
			}
			return $pageRoleNames;
		}

		function deletePage($pageName) {
			//Delete are previous page permissions and update with the new set
			$query = "DELETE FROM page_roles WHERE page='". res($pageName) ."'";
			$result = qdb($query);
		}
	}
