<?php
exit;
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/pipe.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
	
	//David's awesome mapping creation
	$upsid = 1;
	$fedexid = 2;
	$otherid = 3;
	$CARRIER_MAPS = array(
		1 => $upsid,
		2 => $fedexid,
		3 => $otherid,
		4 => $fedexid,
		5 => $fedexid,
		6 => $fedexid,
		7 => $upsid,
		8 => $upsid,
		9 => $upsid,
		10 => $otherid,
		11 => $otherid,
		12 => $upsid,
		13 => $upsid,
	);

	//Temp array to hold Brian's data
	$freightHolder = array();
	
	//For testing purposes and compile time adding LIMIT 30
	$query = "SELECT * FROM inventory_companyfreightaccounts ORDER BY id ASC; ";
	$result = qdb($query,'PIPE') OR die(qe('PIPE').'<BR>'.$query);
	while ($r = mysqli_fetch_assoc($result)) {
		$freightHolder[] = $r;
	}
	
	foreach ($freightHolder as $key => $value) {
		//trim the entire array
		$value = array_map('trim', $value);
		
		//Skip unneeded values
		if( $value['account_no'] == 'oifhoiehoi' || $value['account_no'] == 'n/a' )
			continue;
		
		//Translate the companyid to ours using David's awesome function dbTranslate()
		$value['companyid'] = dbTranslate($value['company_id']);
		//Renaming the column
		unset($value['company_id']);
		
		$value['carrierid'] = $CARRIER_MAPS[$value['carrier_id']];
		//Renaming the column
		unset($value['carrier_id']);
		
		unset($value['default']);
			
		print_r($value);
		echo '<br><br>';
		
		$id = 0;
		
		//Check for freight account ID
		$phoneQuery = "SELECT * FROM freight_accounts WHERE LOWER(account_no) = '".res(strtolower($value['account_no']))."' AND companyid = ".res($value['companyid'])." LIMIT 1; ";
		$result2 = qdb($phoneQuery) OR die(qe().' '.$phoneQuery);
		if (mysqli_num_rows($result2)>0) {
			$r2 = mysqli_fetch_assoc($result2);
			$id = $r2['id'];
		}
		
		setFreightAccount($value['account_no'], $value['carrierid'], $value['companyid'], $id);
	}
	
	
	function setFreightAccount($account_no, $carrierid, $companyid, $id = 0) {
		$account_no = (string)$account_no;
		$account_no = trim($account_no);
		
		$carrierid = (int)$carrierid;
		$companyid = (int)$companyid;

		$query = "REPLACE freight_accounts (account_no, carrierid, companyid";
		if ($id) { $query .= ", id"; }
		$query .= ") VALUES ('".res($account_no)."',";
		$query .= " ".res($carrierid)."";
		$query .= ", ".res($companyid)."";
		if ($id) { $query .= ",'".res($id)."'"; }
		$query .= "); ";
		$result = qdb($query) OR die(qe().' '.$query);
		if (!$id) {$id = qid();}
		

		return ($id);
	}
?>
