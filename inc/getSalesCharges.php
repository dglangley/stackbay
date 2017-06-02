<?php
    function getSalesCharges($so_number){
        $pson = prep($so_number);
        $select = "SELECT * FROM sales_charges where so_number = $pson order by id asc;";
        $result = qdb($select) or die(qe()." $select");
        if(mysqli_num_rows($result)){ 
            return($result);
        } else {
            return null;
        }
    }
?>