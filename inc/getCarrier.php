<?php
	$CARRIERS = array();
	function getCarrier($str=1) {
		global $CARRIERS;

		if (isset($CARRIERS[$str])) { return ($CARRIERS[$str]); }

		$query = "SELECT *, fc.id freight_carrier_id FROM freight_carriers fc, companies c ";
		$query .= "WHERE c.id = fc.companyid; ";
		$result = qdb($query) OR die(qe().'<BR>'.$query);
		while ($r = mysqli_fetch_assoc($result)) {
			$CARRIERS[$r['freight_carrier_id']] = $r['name'];
		}

		return ($CARRIERS[$str]);
	}
?>
