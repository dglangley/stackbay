<?php
    // From getCompany and this says Aaron code so use with caution

    function companyMap($service_companyid,$customer='') {
        $companyid = 0;
		$customer = trim($customer);

        $query = "SELECT companyid FROM company_maps WHERE service_companyid = ".res($service_companyid).";";
        $result = qdb($query) OR die(qe().'<BR>'.$query);

        //echo $query . '<BR><BR>';

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);
            $companyid = $r['companyid'];
        } else if ($customer) {
			$query = "SELECT * FROM companies WHERE name = '".res($customer)."'; ";
        	$result = qdb($query) OR die(qe().'<BR>'.$query);
			if (mysqli_num_rows($result)>0) {
				$r = mysqli_fetch_assoc($result);
				$companyid = $r['id'];
			} else {
				$query = "INSERT INTO companies (name) VALUES ('".res($customer)."'); ";
   		     	$result = qdb($query) OR die(qe().'<BR>'.$query);
				$companyid = qid();
			}

			$query = "INSERT INTO company_maps (companyid, service_companyid) VALUES ('".res($companyid)."','".res($service_companyid)."'); ";
        	$result = qdb($query) OR die(qe().'<BR>'.$query);
		}

        return $companyid;
    }
?>
