<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';
	include_once $_SERVER["ROOT_DIR"]."/inc/send_gmail.php";

	setGoogleAccessToken(5);//5 is amea’s userid, this initializes her gmail session

	class DBSync  {
		private $database_one_db_user;
		private $database_one_db_pass;
		private $database_one_db_host;
		private $database_one_db_name;

		private $table_one_name;
		private $table_two_name;

		private $database_two_db_user;
		private $database_two_db_pass;
		private $database_two_db_host;
		private $database_two_db_name;

		private $database_one_columns = array();
		private $database_two_columns = array();
		private $database_one_types = array();
		private $database_two_types = array();

		private $database_one_link;
		private $database_two_link;

		var $company;

		var $erp_token;
		var $token_length;
		var $erp_id;
		var $erp_init;

		var $user_name;
		var $user_email;
		var $user_company;
		var $user_phone;

		var $_isDEBUG;


		function __construct($DEBUG = true) {
			// Debug toggle
			$this->_isDEBUG = !$DEBUG;

			$this->reqTables();
		}

		// Generate req tables if the function is called upon
		function reqTables() {
			global $WLI;
			// ERP TOKENS TABLE
			$stmt = $WLI->prepare('
				CREATE TABLE IF NOT EXISTS `erp_tokens` (
					`token` blob NOT NULL,
					`expiry` int(15) unsigned DEFAULT NULL,
					`init` tinyint(1) unsigned DEFAULT NULL,
					`id` mediumint(9) unsigned NOT NULL AUTO_INCREMENT,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;
			');
			$stmt->execute();
			$stmt->close();

			$stmt = $WLI->prepare('
				CREATE TABLE IF NOT EXISTS `erp` (
					`namespace` varchar(255) DEFAULT NULL,
					`company` varchar(255) DEFAULT NULL,
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
			');
			$stmt->execute();
			$stmt->close();

			$stmt = $WLI->prepare('
				CREATE TABLE IF NOT EXISTS `erp_users` (
					`name` varchar(255) DEFAULT NULL,
					`email` varchar(255) DEFAULT NULL,
					`company` varchar(255) DEFAULT NULL,
					`phone` varchar(100) DEFAULT NULL,
					`tokenid` int(11) unsigned DEFAULT NULL,
					`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			');
			$stmt->execute();
			$stmt->close();
		}

		// EVERYTHING BELOW HERE IS DEALING WITH THE AUTHENTICATION AND TOKEN CHECKING OF SOMEONE USING THE ERP GENERATION FEATURE
		// THAT IS NOT USING THE ADMIN PAGE

		function generateToken() {
			global $WLI;

			$tokenid = 0;

			//Set the ERPs token globally within this class but if token exists it will be replaced by the token function
			$this->erp_token = md5(uniqid(rand(), TRUE));

			//Set the expiration length globally to 1 week
			$this->token_length = time() + (7 * 86400); //* 86400

			// Insert into the ERP tokens table
			$stmt = $WLI->prepare('
				INSERT INTO erp_tokens (token, expiry, init) 
					VALUES (?, ?, 1)
			');

			//s = string, i - integer, d = double, b = blob for params of mysqli
			$stmt->bind_param("si", $this->erp_token, $this->token_length);
			//Package it all and execute the query
			$stmt->execute();
			$tokenid = $stmt->insert_id;
			$stmt->close();

			return $tokenid;
		}

		// Sets all the variables needed for the token
		// Also verfies if it actually exists
		function authenticateToken($token) {
			global $ALERT;

			// Set the global variable to what is being passed in
			$this->erp_token = $token;

			$query = "SELECT * FROM erp_tokens WHERE token = ".fres($this->erp_token).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_assoc($result);

				$this->erp_init = $row['init'];
				$this->erp_token = $row['token'];
				$this->token_length = $row['expiry'];
				$this->erp_id = $row['id'];
			} else {
				$ALERT = 'Token is invalid. Please contact an admin for assistance.';
			}

			// Get all the users information stored here
			$query = "SELECT * FROM erp_users WHERE tokenid = ".res($this->erp_id).";";
			$result = qedb($query);

			if(mysqli_num_rows($result) > 0){
				$row = mysqli_fetch_assoc($result);

				$this->user_name = $row['name'];
				$this->user_company = $row['company'];
				$this->user_phone = $row['phone'];
				$this->user_email = $row['email'];
			}


			if(! $this->erp_init) {
				$ALERT = 'Token has already been used. Please contact an admin for assistance.';
			}
		}

		function tokenERP($token) {
			global $ALERT, $WLI;

			if($this->token_length < time()) {
				$ALERT = 'ERP access expired. Please contact an admin for assistance.';
			} else if($this->erp_init == 1) {
				// Disbale creation page for the end user by setting the init to false
				$stmt = $WLI->prepare('
					UPDATE erp_tokens SET init = false WHERE id = ?
				');
				$stmt->bind_param("i", $this->erp_id);
				$stmt->execute();
				$stmt->close();	
			} else {
				$ALERT = 'Access Denied.';
			}
		}

		function verifyEmail($email) {
			global $ALERT;

			if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$ALERT = $email . ' is an invalid email.';
			}
		}

		function erpRegistration($name, $email, $company = '', $phone = '') {
			global $ALERT, $WLI;

			// First check and see if the user already exists
			$query = "SELECT * FROM erp_users WHERE name=".fres($name)." AND email = ".fres($email)." AND company = ".fres($company)." AND phone = ".fres($phone).";";
			$result = qedb($query);
			
			if(qnum($result)) {
				$ALERT = "User already exists in out system.";
				return 0;
			}

			// set all the needed variables
			$this->user_name = $name;
			$this->user_company = $company;
			$this->user_email = $email;
			$this->user_company = $company;
			$this->user_phone = $phone;

			// Generate a token
			$tokenid = $this->generateToken();

			// Add this user to our database
			$stmt = $WLI->prepare('
				INSERT INTO erp_users (name, email, company, phone, tokenid) 
					VALUES (?, ?, ?, ?, ?)
			');

			//s = string, i - integer, d = double, b = blob for params of mysqli
			$stmt->bind_param("ssssi", $this->user_name, $this->user_email, $this->user_company, $this->user_phone, $tokenid);
			//Package it all and execute the query
			$stmt->execute();
			$stmt->close();

			// Fire off an email for the user that just registered
			$this->sendERPConfirmationEmail();

			if(! $ALERT) {
				return true;
			}

			return false;
		}

		function sendERPConfirmationEmail() {
	        global $ALERT, $SEND_ERR;
			
			$email_body_html = "Greetings ". $this->user_name .",<br><br>";
			$email_body_html .= "Welcome to Stackbay! Click on the link to begin your demo:<br><br>";
			$email_body_html .= "Link: <a href ='".$_SERVER['HTTP_HOST']."/signup/installer.php?token=". $this->erp_token ."'>".$_SERVER['HTTP_HOST']."/signup/installer.php?token=". $this->erp_token ."</a><br>";
			$email_body_html .= "<br>";
			$email_body_html .= "If you have any problems, please contact an admin at admin@ven-tel.com.";
			$email_subject = 'Stackbay Demo Registration';
			$recipients = $this->user_email;

			$bcc = 'dev@ven-tel.com';

			$send_success = send_gmail($email_body_html,$email_subject,$recipients,$bcc);
			if (! $send_success) {
			    $ALERT = 'Email failed to send.';
			} 
		}
		
		// EVERYTHING BELOW HERE DEALS WITH THE DATABASE SYNCING MECHANISM AND CREATION

		function generateDB($database) {
			$query = "CREATE DATABASE ".res($database).";";
			qedb($query);
			// global $WLI;

			// $stmt = $WLI->prepare('
			// 	CREATE DATABASE ?
			// ');
			// $stmt->bind_param("s", $database);
			// $stmt->execute();
			// $stmt->close();	
		}

		// DB Setters
		function setDBOneConnection($host, $user, $pass, $name) {
			$this->database_one_db_host = $host;
			$this->database_one_db_user = $user;
			$this->database_one_db_pass = $pass;
			$this->database_one_db_name = $name;
		}

		// DB Setters
		function setDBTwoConnection($host, $user, $pass, $name) {
			$this->database_two_db_host = $host;
			$this->database_two_db_user = $user;
			$this->database_two_db_pass = $pass;
			$this->database_two_db_name = $name;
		}

		function setCompany($company) {
			$this->company = $company;
		}

		// Begin matching tables
		function matchTables($table1 = '', $table2 = '') {
			if(isset($this->database_one_db_pass)) {
				$this->db_connect('ONE');
			}
			foreach($this->getTables() as $table_name) {
				// Matching tables possibly
				$this->table_one_name = ($table1?:$table_name);
				$this->table_two_name = ($table2?:$table_name);

				if(isset($this->database_one_db_pass)) {
					$this->db_connect('ONE');
				}

				// Extract all the columns in the table from the OG database
				list($this->database_one_columns,$this->database_one_types) = $this->getColumns($this->table_one_name);

				// Connect now to db 2
				if(isset($this->database_two_db_pass)) {
					$this->db_connect('TWO');
				}
				

				// Invoke table check function to check if the table exists in the db2
				$this->tableCheck($this->table_two_name);

				// Extract all the columns from 2nd database with specified table name
				list($this->database_two_columns,$this->database_two_types) = $this->getColumns($this->table_two_name);
				$this->addDetectedColumns($this->detectNewColumns());
			}

			$this->resetDB();
		}

		function db_connect($database) {
			switch(strtoupper($database)) {
				case 'ONE':
					$host = $this->database_one_db_host;
					$user = $this->database_one_db_user;
					$pass = $this->database_one_db_pass;
					$name = $this->database_one_db_name;
					$link = $this->database_one_link = mysql_connect($host, $user, $pass, true);
					mysql_select_db($name) or die(mysql_error().'<BR>'.$query);
				break;
				case 'TWO';
					$host = $this->database_two_db_host;
					$user = $this->database_two_db_user;
					$pass = $this->database_two_db_pass;
					$name = $this->database_two_db_name;
					$link = $this->database_two_link = mysql_connect($host, $user, $pass, true);
					mysql_select_db($name) or die(mysql_error().'<BR>'.$query);
				break;
				default:
					die('Failed to Connect to Either DB 1 or 2. Please Check Contact Admin for Assistance');
				break;
				
			}
			if (!$link) {
				die('Could not connect: ' . mysql_error().'<BR>'.$query);
			}
		}

		function getTables($database) {
			$tables = array();

			$query = "SHOW TABLES;";

			$result = mysql_query($query) or die(mysql_error().'<BR>'.$query);

			while($row = mysql_fetch_assoc($result)) {
				$table = reset($row);

				array_push($tables, $table);
			}

			return $tables;
		}

		function tableCheck($table_name) {
			// Right here check if the table actually exists.... 
			// If it doesnt then generate it
			// Quick query stab to check if there is a table like this
			$query = "SHOW TABLES LIKE '".$table_name."';";

			$result = mysql_query($query);
			if(mysql_num_rows($result) == 0) {
				// Table does not exists let us create it now
				$this->addTable($table_name);
			}
		}

		function getColumns($table_name) {
			$columns = array();
			$types = array();

			$query = "SHOW COLUMNS FROM ".$table_name.";";
			$result = mysql_query($query) or die(mysql_error().'<BR>'.$query);

			while($row = mysql_fetch_assoc($result)) {
				$field = $row['Field'];
				$type = $row['Type'];
				$types[$field] = $type;

				array_push($columns, $field);
			}

			$data = array($columns, $types);

			return $data;
		}

		function addTable($table_name) {
			$query = "CREATE TABLE ".$table_name." (id int(11) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (id)) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
		}

		function detectNewColumns() {
			$parsed_results = array_diff($this->database_one_columns,$this->database_two_columns);

			return $parsed_results;
		}

		function addDetectedColumns($parsed_results) {
			$query = '';
			foreach($parsed_results as $field) {
				$query = 'ALTER TABLE '.$this->table_two_name.' ADD `'.$field.'` '.$this->database_one_types[$field].'; ';
				if($this->_isDEBUG) {
					echo $query.'<br><br>';
				} else {
					mysql_query($query) or die(mysql_error().'<BR>'.$query);
				}
			}
		}

		function resetOneDB() {
			if(isset($this->database_one_db_pass)) {
				$this->db_connect('ONE');
			}

			$this->resetDB();
		}

		function resetDB() {
			// V2.0 truncate tables with scalar abilities vs hardcoded before
			foreach($this->getTables() as $table_name) {
				$query = "TRUNCATE ".$table_name.";";
				mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			}

			// Set a default Company
			$query = "INSERT INTO companies (name, website, phone, corporateid, default_email, notes) VALUES
			('".$this->company."', NULL, NULL, NULL, NULL, NULL);";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$companyid = mysql_insert_id();

			// Emails and Contacts 1 and 2 are used so set them here
			// ADMIN
			$query = "INSERT INTO contacts (name, title, notes, ebayid, status, companyid, aim) VALUES 
					('Admin', NULL, NULL, NULL, 'Active', ".res($companyid).", NULL);";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$admin_contact = mysql_insert_id();

			$query = "INSERT INTO emails (email, type, contactid) VALUES ('david@ven-tel.com', 'Work', ".res($admin_contact).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$admin_email = mysql_insert_id();

			// GUEST
			$query = "INSERT INTO contacts (name, title, notes, ebayid, status, companyid, aim) VALUES 
					('Guest', NULL, NULL, NULL, 'Active', ".res($companyid).", NULL);";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$guest_contact = mysql_insert_id();

			$query = "INSERT INTO emails (email, type, contactid) VALUES ('david@ven-tel.com', 'Work', ".res($guest_contact).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$guest_email = mysql_insert_id();

			// Generate a admin user with password admin
			$query = "INSERT INTO users (contactid, login_emailid, encrypted_pass, encrypted_pin, init, expiry, commission_rate, hourly_rate) VALUES
					(".res($admin_contact).", ".res($admin_email).", ".fres('$2y$10$ea38ddf9affc1bcab6a8aOr3peIYGdInRtUc8l5CN6xNsXaZ2mEBu').", NULL, 0, NULL, NULL, NULL);";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$adminid = mysql_insert_id();

			// Add in the pre-generated salt for the set password for the admin user
			$query = "INSERT INTO user_salts (salt, userid, log) VALUES (".fres('$2y$10$ea38ddf9affc1bcab6a8aa5d715177d1f').", ".res($adminid).", '10');";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Set a default admin user
			$query = "INSERT INTO usernames (username, emailid, userid) VALUES ('admin', ".res($admin_email).", ".res($adminid).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$admin_permissions = array(1,4,7);

			// Set admin permissions
			foreach($admin_permissions as $permission) {
			$query = "REPLACE INTO user_roles (userid, privilegeid) VALUES (".res($adminid).", ".res($permission).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			}

			$query = "
				INSERT INTO `user_privileges` (`privilege`, `id`)
				VALUES
					('Administration', 1),
					('IT', 2),
					('Operations', 3),
					('Management', 4),
					('Sales', 5),
					('Guest', 6),
					('Accounting', 7),
					('Technician', 8),
					('Logistics', 9);
			";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Generate a guest user with password guest
			$query = "INSERT INTO users (contactid, login_emailid, encrypted_pass, encrypted_pin, init, expiry, commission_rate, hourly_rate) VALUES
					(".res($guest_contact).", ".res($guest_email).", ".fres('$2y$10$1b90eac864a4e389efcaeukaVjFJcw3u.2U6pAcriGXoH7aNSULtS').", NULL, 0, NULL, NULL, NULL);";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$guestid = mysql_insert_id();

			// Add in the pre-generated salt for the set password for the admin user
			$query = "INSERT INTO user_salts (salt, userid, log) VALUES (".fres('$2y$10$1b90eac864a4e389efcae866c1a6c049d39c0415610f').", ".res($guestid).", '10');";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Set a default guest user
			$query = "INSERT INTO usernames (username, emailid, userid) VALUES ('guest', ".res($guest_email).", ".res($guestid).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$guest_permissions = array(6);

			// Set admin permissions
			foreach($guest_permissions as $permission) {
			$query = "REPLACE INTO user_roles (userid, privilegeid) VALUES (".res($guestid).", ".res($permission).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			}

			// Set the first page role
			$query = "INSERT INTO page_roles (page, privilegeid) VALUES ('user_management.php', 1);";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);

			// Truncate and set default for profile
			// $query = "TRUNCATE profile;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "INSERT INTO profile (logo, companyid) VALUES
					('img/logo.png', ".res($companyid).");";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);

			// Drop TRIGGERS
			$query = "DROP TRIGGER IF EXISTS `inv_new`;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
			$query = "DROP TRIGGER IF EXISTS `inv_update`;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);

			// Create Inventory Triggers
			$query = "CREATE TRIGGER inv_new AFTER INSERT
			ON inventory
			FOR EACH ROW
			BEGIN
			SET
				@id = NEW.id,
				@userid = NEW.userid,
				@date = now();
			
			INSERT INTO inventory_history VALUES (@date,@userid, @id, 'new', 'new', NULL);

			IF (NEW.purchase_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @id, 'purchase_item_id', NEW.purchase_item_id, NULL);
			END IF;

			IF (NEW.sales_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @id, 'sales_item_id', NEW.sales_item_id, NULL);
			END IF;

			IF (NEW.returns_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @id, 'returns_item_id', NEW.returns_item_id, NULL);
			END IF;
			
			IF (NEW.repair_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @id, 'repair_item_id', NEW.repair_item_id, NULL);
			END IF;
			END;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);

			$query = "CREATE TRIGGER inv_update AFTER UPDATE
			ON inventory
			FOR EACH ROW		
			BEGIN
			SET
				@inv_id = OLD.id,
				@userid = OLD.userid,
				@date = now();
			
			IF (OLD.serial_no <> NEW.serial_no) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'serial_no', NEW.serial_no, OLD.serial_no);
			END IF;

			IF (OLD.qty <> NEW.qty) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'qty', NEW.qty, OLD.qty);
			END IF;

			IF (OLD.partid <> NEW.partid) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'partid', NEW.partid, OLD.partid);
			END IF;

			IF (OLD.conditionid <> NEW.conditionid) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'conditionid', NEW.conditionid, OLD.conditionid);
			END IF;

			IF (OLD.locationid <> NEW.locationid) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'locationid', NEW.locationid, OLD.locationid);
			END IF;

			IF (OLD.purchase_item_id <> NEW.purchase_item_id) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'purchase_item_id', NEW.purchase_item_id, OLD.purchase_item_id);
			END IF;

			IF (OLD.purchase_item_id IS NULL && NEW.purchase_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'purchase_item_id', NEW.purchase_item_id, OLD.purchase_item_id);
			END IF;

			IF (OLD.sales_item_id <> NEW.sales_item_id) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'sales_item_id', NEW.sales_item_id, OLD.sales_item_id);
			END IF;

			IF (OLD.sales_item_id IS NULL && NEW.sales_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'sales_item_id', NEW.sales_item_id, OLD.sales_item_id);
			END IF;

			IF (OLD.returns_item_id <> NEW.returns_item_id) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'returns_item_id', NEW.returns_item_id, OLD.returns_item_id);
			END IF;

			IF (OLD.returns_item_id IS NULL && NEW.returns_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'returns_item_id', NEW.returns_item_id, OLD.returns_item_id);
			END IF;

			IF (OLD.repair_item_id <> NEW.repair_item_id) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'repair_item_id', NEW.repair_item_id, OLD.repair_item_id);
			END IF;

			IF (OLD.repair_item_id IS NULL && NEW.repair_item_id IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'repair_item_id', NEW.repair_item_id, OLD.repair_item_id);
			END IF;
			
			IF (OLD.status <> NEW.status) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'status', NEW.status, OLD.status);
			END IF;

			IF (OLD.notes <> NEW.notes) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'notes', NEW.notes, OLD.notes);
			END IF;

			IF (OLD.notes IS NULL && NEW.notes IS NOT NULL) THEN
				INSERT INTO inventory_history VALUES (@date, @userid, @inv_id, 'notes', NEW.notes, OLD.notes);
			END IF;
			END;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query);
		}
	}