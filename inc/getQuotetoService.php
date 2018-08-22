<?php
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php'; 
    
    function getQuotetoService($qouteid) {
        $query = "SELECT * FROM service_items WHERE quote_item_id = ".res($qouteid).";";
        $result = qedb($query);

        if(mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }

        return false;
    }