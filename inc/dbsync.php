<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	class DBSync  {
		var $database_one_db_user;
		var $database_one_db_pass;
		var $database_one_db_host;
		var $database_one_db_name;

		var $table_one_name;
		var $table_two_name;

		var $database_two_db_user;
		var $database_two_db_pass;
		var $database_two_db_host;
		var $database_two_db_name;

		// var $database_one_tables = array();
		// var $database_one_temp = array();

		// var $database_two_tables = array();

		var $database_one_columns = array();
		var $database_two_columns = array();
		var $database_one_types = array();
		var $database_two_types = array();

		var $database_one_link;
		var $database_two_link;

		var $company;

		var $_isDEBUG;


		function __construct($DEBUG = true) {
			// Debug toggle
			$this->_isDEBUG = !$DEBUG;
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
			$order_types = array("Sales", "Repair", "Purchase", "Service", "Return", "purchase_request", "Outsourced" , "Outsourced Quote", "service_quote", "Invoice", "Bill", "Credit", "Supply", "Demand");

			// Remove Constraint

			// $query = "ALTER TABLE company_activity
			// DROP FOREIGN KEY activity_companyids;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// $query = "ALTER TABLE company_terms
			// 		DROP FOREIGN KEY terms_companyid;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// $query = "ALTER TABLE search_meta
			// 		DROP FOREIGN KEY search_meta_ibfk_1;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			foreach($order_types as $order_type) {
				$T = order_type($order_type);

				if($T['orders']) {
					// Truncate the orders table
					$query = "TRUNCATE ".$T['orders'].";";
					mysql_query($query) or die(mysql_error().'<BR>'.$query); 
				}

				if($T['items']) {
					// Truncate the items table
					$query = "TRUNCATE ".$T['items'].";";
					mysql_query($query) or die(mysql_error().'<BR>'.$query); 
				}

				if($T['charges']) {
					$query = "TRUNCATE ".$T['charges'].";";
					mysql_query($query) or die(mysql_error().'<BR>'.$query);
				}
			}

			// MISC Missing Truncates
			$query = "TRUNCATE bill_shipments;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE activity_log;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Builds
			$query = "TRUNCATE builds;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE build_items;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// ISO
			$query = "TRUNCATE iso;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Add in Materials and Components truncate for services and repairs
			$query = "TRUNCATE service_materials;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE repair_components;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE service_quote_materials;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Truncate all Packages
			$query = "TRUNCATE packages;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE package_contents;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Truncate Cost / Cogs
			$query = "TRUNCATE average_costs;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE repair_components;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE inventory_costs;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE inventory_costs_log;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Truncate Inventory
			$query = "TRUNCATE inventory;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE inventory_history;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Truncate Commissions
			$query = "TRUNCATE commissions;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE commission_payouts;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Consigment
			$query = "TRUNCATE consignment;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Expenses
			$query = "TRUNCATE expenses;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Finance Accounts
			$query = "TRUNCATE finance_accounts;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Freight Accounts
			$query = "TRUNCATE freight_accounts	;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Invoice Lumps
			$query = "TRUNCATE invoice_lumps;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE invoice_lump_items;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE invoice_shipments;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Journal Entries
			$query = "TRUNCATE journal_entries;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Market
			$query = "TRUNCATE market;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Messages and Notifications
			$query = "TRUNCATE messages;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE notifications;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE prices;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Page Roles
			$query = "TRUNCATE page_roles;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Payments 
			$query = "TRUNCATE payments;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE payment_details;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// QB
			$query = "TRUNCATE qb_log;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Reimbursements
			$query = "TRUNCATE reimbursements;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Remotes and Sessions
			$query = "TRUNCATE remotes;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE remote_sessions;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// RFQS
			$query = "TRUNCATE rfqs;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Assignments
			$query = "TRUNCATE service_assignments;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Services
			$query = "TRUNCATE service_bom;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE service_docs;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Templates
			$query = "TRUNCATE templates;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE template_items;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Timesheets
			$query = "TRUNCATE timesheets;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 
			$query = "TRUNCATE timesheet_approvals;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Uploads
			$query = "TRUNCATE uploads;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// COMPANIES

			$query = "TRUNCATE companies;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE company_activity;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Add Constraints
			// $query = "ALTER TABLE company_activity
			// 		ADD CONSTRAINT activity_companyids
			// 		FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query);

			// $query = "ALTER TABLE company_aliases
			// 		ADD CONSTRAINT alias_companyid
			// 		FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query);

			// $query = "ALTER TABLE company_aliases
			// 		ADD CONSTRAINT company_terms
			// 		FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query);

			// $query = "ALTER TABLE search_meta
			// 		ADD CONSTRAINT search_meta_ibfk_1
			// 		FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
			// mysql_query($query) or die(mysql_error().'<BR>'.$query);

			$query = "TRUNCATE company_addresses;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE company_aliases;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE company_maps;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE company_terms;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Contacts including emails phone numbers etc
			$query = "TRUNCATE contacts;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE emails;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "TRUNCATE phones;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			// Addresses
			$query = "TRUNCATE addresses;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 


		// Section to set User Tables

			// Truncate Users, Username, User_roles, Etc.
			$user_tables = array("users", "usernames", "userlog", "user_tokens", "user_salts", "user_roles", "user_classes");
			foreach($user_tables as $table) {
				$T = order_type($order_type);

				// Truncate the table
				$query = "TRUNCATE ".$table.";";
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
			$query = "TRUNCATE profile;";
			mysql_query($query) or die(mysql_error().'<BR>'.$query); 

			$query = "INSERT INTO profile (logo, companyid) VALUES
					('img/logo-white.png', ".res($companyid).");";
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