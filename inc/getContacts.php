<?php
	function getContacts($companyid,$q='') {
		$empty_email = array('email'=>'','type'=>'','id'=>0);
		$empty_phone = array('phone'=>'','type'=>'','id'=>0);
		$contacts = array();
		$q = trim($q);

		$query = "SELECT contacts.* FROM contacts ";
		if ($q) { $query .= "LEFT JOIN emails ON contacts.id = emails.contactid "; }
		$query .= "WHERE companyid = '".res($companyid)."' ";
		if ($q) { $query .= "AND (emails.email RLIKE '".res($q)."' OR contacts.name RLIKE '".res($q)."') GROUP BY contacts.id "; }
		$query .= "ORDER BY name ASC; ";
		$result = qdb($query);
		while ($r = mysqli_fetch_assoc($result)) {
			$r['emails'] = array();
			$query2 = "SELECT email, type, id FROM emails WHERE contactid = '".$r['id']."'; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$r['emails'][] = $r2;
			}
			$r['emails'][] = $empty_email;

			$r['phones'] = array();
			$query2 = "SELECT phone, type, id FROM phones WHERE contactid = '".$r['id']."'; ";
			$result2 = qdb($query2);
			while ($r2 = mysqli_fetch_assoc($result2)) {
				$r['phones'][] = $r2;
			}
			$r['phones'][] = $empty_phone;

			$contacts[$r['id']] = $r;
		}

		// one more row as blank to add new
		$contacts[0] = array('name'=>'','title'=>'','emails'=>array($empty_email),'phones'=>array($empty_phone),'im'=>'','notes'=>'');
		return ($contacts);
	}
?>
