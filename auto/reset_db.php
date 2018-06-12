<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_type.php';

	if ($_SERVER["DEFAULT_DB"]=='vmmdb') { die("Wrong database mister!"); }

	// Dont allow any of these to run for now
	exit;

	$DEBUG = 1;
	if (! $DEBUG) { exit; }

	echo "EXECUTING <BR>";

		// Remove Constraint
		// $query = "ALTER TABLE company_aliases
		// 			DROP FOREIGN KEY alias_companyid;";
		// qedb($query);

		 $query = "ALTER TABLE company_activity
		 			DROP FOREIGN KEY activity_companyids;";
		 qedb($query);

		 $query = "ALTER TABLE company_terms
		 			DROP FOREIGN KEY terms_companyid;";
		 qedb($query);

		 $query = "ALTER TABLE search_meta
		 			DROP FOREIGN KEY search_meta_ibfk_1;";
		 qedb($query);

	// Truncate all ORDERS and ITEMS etc based on order type to get all the required fields
	// In other words truncate almost everything that order_type has to offer based on preference
		$order_types = array("Sales", "Repair", "Purchase", "Service", "Return", "purchase_request", "Outsourced" , "Outsourced Quote", "service_quote", "Invoice", "Bill", "Credit", "Supply", "Demand");

		foreach($order_types as $order_type) {
			$T = order_type($order_type);

			if($T['orders']) {
				// Truncate the orders table
				$query = "TRUNCATE ".$T['orders'].";";
				qedb($query); 
			}

			if($T['items']) {
				// Truncate the items table
				$query = "TRUNCATE ".$T['items'].";";
				qedb($query); 
			}

			if($T['charges']) {
				$query = "TRUNCATE ".$T['charges'].";";
				qedb($query);
			}
		}

		// MISC Missing Truncates
		$query = "TRUNCATE bill_shipments;";
		qedb($query); 

		$query = "TRUNCATE activity_log;";
		qedb($query); 

		// Builds
		$query = "TRUNCATE builds;";
		qedb($query); 
		$query = "TRUNCATE build_items;";
		qedb($query); 

		// ISO
		$query = "TRUNCATE iso;";
		qedb($query); 

		// Add in Materials and Components truncate for services and repairs
		$query = "TRUNCATE service_materials;";
		qedb($query); 
		$query = "TRUNCATE repair_components;";
		qedb($query); 
		$query = "TRUNCATE service_quote_materials;";
		qedb($query); 

		// Truncate all Packages
		$query = "TRUNCATE packages;";
		qedb($query); 
		$query = "TRUNCATE package_contents;";
		qedb($query); 

		// Truncate Cost / Cogs
		$query = "TRUNCATE average_costs;";
		qedb($query); 
		$query = "TRUNCATE repair_components;";
		qedb($query); 
		$query = "TRUNCATE inventory_costs;";
		qedb($query); 
		$query = "TRUNCATE inventory_costs_log;";
		qedb($query); 

		// Truncate Inventory
		$query = "TRUNCATE inventory;";
		qedb($query); 
		$query = "TRUNCATE inventory_history;";
		qedb($query); 

		// Truncate Commissions
		$query = "TRUNCATE commissions;";
		qedb($query); 
		$query = "TRUNCATE commission_payouts;";
		qedb($query); 

		// Consigment
		$query = "TRUNCATE consignment;";
		qedb($query); 

		// Expenses
		$query = "TRUNCATE expenses;";
		qedb($query); 

		// Finance Accounts
		$query = "TRUNCATE finance_accounts;";
		qedb($query); 

		// Freight Accounts
		$query = "TRUNCATE freight_accounts	;";
		qedb($query); 

		// Invoice Lumps
		$query = "TRUNCATE invoice_lumps;";
		qedb($query); 
		$query = "TRUNCATE invoice_lump_items;";
		qedb($query); 
		$query = "TRUNCATE invoice_shipments;";
		qedb($query); 

		// Journal Entries
		$query = "TRUNCATE journal_entries;";
		qedb($query); 

		// Market
		$query = "TRUNCATE market;";
		qedb($query); 

		// Messages and Notifications
		$query = "TRUNCATE messages;";
		qedb($query); 
		$query = "TRUNCATE notifications;";
		qedb($query); 

		$query = "TRUNCATE prices;";
		qedb($query); 

		// Page Roles
		$query = "TRUNCATE page_roles;";
		qedb($query); 

		// Payments 
		$query = "TRUNCATE payments;";
		qedb($query); 
		$query = "TRUNCATE payment_details;";
		qedb($query); 

		// QB
		$query = "TRUNCATE qb_log;";
		qedb($query); 

		// Reimbursements
		$query = "TRUNCATE reimbursements;";
		qedb($query); 

		// Remotes and Sessions
		$query = "TRUNCATE remotes;";
		qedb($query); 
		$query = "TRUNCATE remote_sessions;";
		qedb($query); 

		// RFQS
		$query = "TRUNCATE rfqs;";
		qedb($query); 

		// Assignments
		$query = "TRUNCATE service_assignments;";
		qedb($query); 

		// Services
		$query = "TRUNCATE service_bom;";
		qedb($query); 
		$query = "TRUNCATE service_docs;";
		qedb($query); 

		// Templates
		$query = "TRUNCATE templates;";
		qedb($query); 
		$query = "TRUNCATE template_items;";
		qedb($query); 

		// Timesheets
		$query = "TRUNCATE timesheets;";
		qedb($query); 
		$query = "TRUNCATE timesheet_approvals;";
		qedb($query); 

		// Uploads
		$query = "TRUNCATE uploads;";
		qedb($query); 

		// COMPANIES

		$query = "TRUNCATE companies;";
		qedb($query); 

		$query = "TRUNCATE company_activity;";
		qedb($query); 

		// Add Constraint back in
		$query = "ALTER TABLE company_activity
					ADD CONSTRAINT activity_companyids
					FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
		qedb($query);

		// Add Constraint back in
		$query = "ALTER TABLE company_aliases
					ADD CONSTRAINT alias_companyid
					FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
		qedb($query);

		// Add Constraint back in
		$query = "ALTER TABLE company_aliases
					ADD CONSTRAINT company_terms
					FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
		qedb($query);

		$query = "ALTER TABLE search_meta
					ADD CONSTRAINT search_meta_ibfk_1
					FOREIGN KEY (companyid) REFERENCES companies(id) ON UPDATE NO ACTION ON DELETE NO ACTION;";
		qedb($query);

		$query = "TRUNCATE company_addresses;";
		qedb($query); 

		$query = "TRUNCATE company_aliases;";
		qedb($query); 

		$query = "TRUNCATE company_maps;";
		qedb($query); 

		$query = "TRUNCATE company_terms;";
		qedb($query); 

		// Contacts including emails phone numbers etc
		$query = "TRUNCATE contacts;";
		qedb($query); 

		$query = "TRUNCATE emails;";
		qedb($query); 

		$query = "TRUNCATE phones;";
		qedb($query); 

		// Addresses
		$query = "TRUNCATE addresses;";
		qedb($query); 


	// Section to set User Tables

		// Truncate Users, Username, User_roles, Etc.
		$user_tables = array("users", "usernames", "userlog", "user_tokens", "user_salts", "user_roles", "user_classes");
		foreach($user_tables as $table) {
			$T = order_type($order_type);

			// Truncate the table
			$query = "TRUNCATE ".$table.";";
			qedb($query); 
		}

		// Set a default Company
		$query = "INSERT INTO companies (name, website, phone, corporateid, default_email, notes) VALUES
					('Stackbay', NULL, NULL, NULL, NULL, NULL);";
		qedb($query);
		$companyid = qid();

		// Emails and Contacts 1 and 2 are used so set them here
		// ADMIN
		$query = "INSERT INTO contacts (name, title, notes, ebayid, status, companyid, aim) VALUES 
					('Admin', NULL, NULL, NULL, 'Active', ".res($companyid).", NULL);";
		qedb($query);
		$admin_contact = qid();

		$query = "INSERT INTO emails (email, type, contactid) VALUES ('david@ven-tel.com', 'Work', ".res($admin_contact).");";
		qedb($query);
		$admin_email = qid();

		// GUEST
		$query = "INSERT INTO contacts (name, title, notes, ebayid, status, companyid, aim) VALUES 
					('Guest', NULL, NULL, NULL, 'Active', ".res($companyid).", NULL);";
		qedb($query);
		$guest_contact = qid();

		$query = "INSERT INTO emails (email, type, contactid) VALUES ('david@ven-tel.com', 'Work', ".res($guest_contact).");";
		qedb($query);
		$guest_email = qid();

		// Generate a admin user with password admin
		$query = "INSERT INTO users (contactid, login_emailid, encrypted_pass, encrypted_pin, init, expiry, commission_rate, hourly_rate) VALUES
					(".res($admin_contact).", ".res($admin_email).", ".fres('$2y$10$ea38ddf9affc1bcab6a8aOr3peIYGdInRtUc8l5CN6xNsXaZ2mEBu').", NULL, 0, NULL, NULL, NULL);";
		qedb($query);
		$adminid = qid();

		// Add in the pre-generated salt for the set password for the admin user
		$query = "INSERT INTO user_salts (salt, userid, log) VALUES (".fres('$2y$10$ea38ddf9affc1bcab6a8aa5d715177d1f').", ".res($adminid).", '10');";
		qedb($query); 

		// Set a default admin user
		$query = "INSERT INTO usernames (username, emailid, userid) VALUES ('admin', ".res($admin_email).", ".res($adminid).");";
		qedb($query); 

		$admin_permissions = array(1,4,7);

		// Set admin permissions
		foreach($admin_permissions as $permission) {
			$query = "REPLACE INTO user_roles (userid, privilegeid) VALUES (".res($adminid).", ".res($permission).");";
			qedb($query); 
		}

		// Generate a guest user with password guest
		$query = "INSERT INTO users (contactid, login_emailid, encrypted_pass, encrypted_pin, init, expiry, commission_rate, hourly_rate) VALUES
					(".res($guest_contact).", ".res($guest_email).", ".fres('$2y$10$1b90eac864a4e389efcaeukaVjFJcw3u.2U6pAcriGXoH7aNSULtS').", NULL, 0, NULL, NULL, NULL);";
		qedb($query);
		$guestid = qid();

		// Add in the pre-generated salt for the set password for the admin user
		$query = "INSERT INTO user_salts (salt, userid, log) VALUES (".fres('$2y$10$1b90eac864a4e389efcae866c1a6c049d39c0415610f').", ".res($guestid).", '10');";
		qedb($query); 

		// Set a default guest user
		$query = "INSERT INTO usernames (username, emailid, userid) VALUES ('guest', ".res($guest_email).", ".res($guestid).");";
		qedb($query); 

		$guest_permissions = array(6);

		// Set admin permissions
		foreach($guest_permissions as $permission) {
			$query = "REPLACE INTO user_roles (userid, privilegeid) VALUES (".res($guestid).", ".res($permission).");";
			qedb($query); 
		}

		// Set the first page role
		$query = "INSERT INTO page_roles (page, privilegeid) VALUES ('user_management.php', 1);";
		qedb($query);

		// Truncate and set default for profile
		$query = "TRUNCATE profile;";
		qedb($query); 

		$query = "INSERT INTO profile (logo, companyid) VALUES
					('img/logo-white.png', ".res($companyid).");";
		qedb($query);


	echo 'COMPLETED';
