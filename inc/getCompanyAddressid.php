<?php
	 function getCompanyAddressid($companyid){
	 	$addressid;

        $query = "SELECT addressid FROM company_addresses WHERE companyid = ".res($companyid).";";
        $result = qedb($query);

        if(mysqli_num_rows($result)){
            $r = mysqli_fetch_assoc($result);

            $addressid = $r['addressid'];
        }

        return $addressid;
        
    }