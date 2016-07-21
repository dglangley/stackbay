<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';

	$debug = false;

	$query = "SELECT inventory_company.id, TRIM(name) name FROM inventory_company, inventory_incoming_quote ";
	$query .= "WHERE company_id = inventory_company.id AND name NOT LIKE 'AAATEST%' ";
	$query .= "GROUP BY inventory_company.id ORDER BY name ASC; ";
	$result = qdb($query,'PIPE') OR die(qe().' '.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$companies[$r['id']] = array($r['name']);
		$query2 = "SELECT TRIM(alias) name FROM inventory_companyalias ";
		$query2 .= "WHERE company_id = '".$r['id']."' ";
		$query2 .= "GROUP BY name HAVING name <> '".res($r['name'],'PIPE')."'; ";
		$result2 = qdb($query2,'PIPE') OR die(qe().' '.$query2);
		while ($r2 = mysqli_fetch_assoc($result2)) {
			$companies[$r['id']][] = $r2['name'];
		}
	}

	foreach ($companies as $id => $arr) {
		$companyid = 0;
		if ($debug) { echo $id.'<BR>'; }
		foreach ($arr as $name) {
			if ($debug) { echo $name; }
			$query = "SELECT * FROM companies WHERE name = '".res($name)."'; ";
			$result = qdb($query);
			if (mysqli_num_rows($result)==0) { if ($debug) { echo '<BR>'; } continue; }

			$r = mysqli_fetch_assoc($result);
			$companyid = $r['id'];
			if ($debug) { echo ': '.$companyid.'<BR>'; }
			break;
		}

		// now depending on if we have this company in our new db or not, either add the alias(es)
		// or create a new company record and set mapping id's
		foreach ($arr as $name) {
			// if no companyid from above, add company to db; all ensuing iterations will be aliases
			if (! $companyid) {
				$query = "INSERT INTO companies (name, notes) ";
				$query .= "VALUES ('".res($name)."','Added from db 1.0, 7/21/16'); ";
if ($debug) { echo $query.'<BR>'; }
				$result = qdb($query) OR die(qe().' '.$query);
				$companyid = qid();
				continue;
			}
			$query = "SELECT * FROM companies WHERE id = '".$companyid."' AND name = '".res($name)."'; ";
if ($debug) { echo $query.'<BR>'; }
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) { continue; }// alias already exists as company name, even if not an import from old db

			$query = "SELECT * FROM company_aliases WHERE companyid = '".$companyid."' AND name = '".res($name)."'; ";
if ($debug) { echo $query.'<BR>'; }
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)>0) { continue; }// alias already exists, even if not an import from old db

			$query = "INSERT INTO company_aliases (name, companyid, notes) ";
			$query .= "VALUES ('".$name."','".$companyid."','Added from db 1.0, 7/21/16'); ";
if ($debug) { echo $query.'<BR>'; }
			$result = qdb($query) OR die(qe().' '.$query);
		}

		$query = "INSERT INTO company_maps (companyid, inventory_companyid) ";
		$query .= "VALUES ('".$companyid."','".$id."'); ";
if ($debug) { echo $query.'<BR>'; }
		$result = qdb($query) OR die(qe().' '.$query);
		if ($debug) { echo '<BR>'; }
	}

	echo 'Import complete!<BR>'.chr(10);
?>
