<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';

	$debug = true;

	$query = "SELECT services_company.id, TRIM(name) name FROM services_company ";
	$query .= "WHERE name NOT LIKE 'AAATEST%' ";
	$query .= "GROUP BY services_company.id ORDER BY name ASC; ";
	$result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query);

	echo $query;
	while ($r = mysqli_fetch_assoc($result)) {
		$companies[$r['id']] = array($r['name']);
		$query2 = "SELECT TRIM(alias) name FROM services_companyalias ";
		$query2 .= "WHERE company_id = '".$r['id']."' ";
		$query2 .= "GROUP BY name HAVING name <> '".res($r['name'],'SVCS_PIPE')."'; ";
		$result2 = qdb($query2,'SVCS_PIPE') OR die(qe('SVCS_PIPE').' '.$query2);
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
			$result = qdb($query) OR die(qe().' '.$query);
			if (mysqli_num_rows($result)==0) {

				$query = "SELECT companyid id FROM company_aliases WHERE name = '".res($name)."'; ";
				$result = qdb($query) OR die(qe().' '.$query);
				if (mysqli_num_rows($result)==0) {
					if ($debug) { echo '<BR>'; }
					continue;
				}
			}

			$r = mysqli_fetch_assoc($result);
			$companyid = $r['id'];
			if ($debug) { echo ': '.$companyid.'<BR>'; }
			break;
		}

		// now depending on if we have this company in our new db or not, either add the alias(es)
		// or create a new company record and set mapping id's

		print_r($arr);

		foreach ($arr as $name) {
			// if no companyid from above, add company to db; all ensuing iterations will be aliases
			if (! $companyid) {
				$query = "INSERT INTO companies (name, notes) ";
				$query .= "VALUES ('".res($name)."','Added from db 1.0, ".date("n/j/y")."'); ";
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
			$query .= "VALUES ('".$name."','".$companyid."','Added from db 1.0, ".date("n/j/y")."'); ";
if ($debug) { echo $query.'<BR>'; }
			$result = qdb($query) OR die(qe().' '.$query);
		}

		$query = "SELECT * FROM company_maps WHERE service_companyid = '".$id."' AND companyid = '".$companyid."'; ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (mysqli_num_rows($result)>0) { continue; }

		$query = "INSERT INTO company_maps (companyid, service_companyid) ";
		$query .= "VALUES ('".$companyid."','".$id."'); ";
if ($debug) { echo $query.'<BR>'; }
		$result = qdb($query) OR die(qe().' '.$query);
		if ($debug) { echo '<BR>'; }
	}

	echo 'Import complete!<BR>'.chr(10);
?>
