<?php
    $rootdir = $_SERVER['ROOT_DIR'];
    	
    include_once $rootdir.'/inc/dbconnect.php';
    include_once $rootdir.'/inc/format_date.php';
    include_once $rootdir.'/inc/format_price.php';
    include_once $rootdir.'/inc/getCompany.php';
    include_once $rootdir.'/inc/getPart.php';
    include_once $rootdir.'/inc/pipe.php';
    include_once $rootdir.'/inc/keywords.php';
    include_once $rootdir.'/inc/getRecords.php';
    include_once $rootdir.'/inc/getRep.php';
    include_once $rootdir.'/inc/getContact.php';
    include_once $rootdir.'/inc/getFreight.php';
    include_once $rootdir.'/inc/getAddresses.php';
    include_once $rootdir.'/inc/form_handle.php';
    include_once $rootdir.'/inc/dropPop.php';
    include_once $rootdir.'/inc/packages.php';
	include_once $rootdir.'/inc/order_parameters.php';


    function credit_creation($origin_number, $origin_type, $rma = '', $item_number = ''){
        //Create credit function works by taking in either an SO number or an RO number,
        //and outputs the results into a table with credited items and a macro credit table
        // User may also enter 
        
        //Variable declarations
        $o = o_params($origin_type);
        
        //Get the SO/RO macro order information
        $select = "SELECT * FROM ".$o['order']." WHERE ".$o['id']."=".prep($origin_number).";";
        $results = qdb($select) or die(qe()." | $select | ");
        $origin_meta = mysqli_fetch_assoc($results);
        
        $company = prep($origin_meta['companyid']);
        $contact = prep($origin_meta['contactid']);
        $rma_meta = array();
        if ($rma){
            $rma_o = o_params('rma');
            $rma = "
            SELECT r.*, GROUP_CONCAT(ri.id) items_ids
            FROM `returns` r, `return_items` ri
            WHERE r.rma_number = ".prep($rma)."
            AND r.rma_number = ri.rma_number
            GROUP BY r.rma_number
            ;";
            $rma_results = qdb($rma) or die(qe()." | $rma | ");
            $rma_meta = mysqli_fetch_assoc($rma_results);
        }
        $meta_insert = 
        "INSERT INTO `sales_credits`(`companyid`, `date_created`, `order_num`, `order_type`, `rma`, `repid`, `contactid`) 
        VALUES ($company,NOW(),".prep($origin_number).",'".$o['type']."',".prep($rma).",".$GLOBALS['U']['id'].",$contact);";
        
        qedb($meta_insert);
        $scid = qid();
        
        $sc_line = array();

        
        $line_item_select = "
        SELECT ".$o["item"].".* ".(($rma_meta['items_ids'])?", `returns_item_id` ":"")."
        FROM `".$o["item"]."`".(($rma_meta['items_ids'])?" LEFT JOIN `inventory` ON `sales_item_id` ":"")."
        WHERE ".$o['id']." = ".prep($origin_number).(($item_number)?" AND id = ".prep($item_number):"")."
        ".(($rma_meta['items_ids'])?"AND `sales_item_id` in (".$rma_meta['items_ids'].")":"").";";

        $line_items = qdb($line_item_select) or die(qe()."| ".$line_item_select);

        foreach($line_items as $row){
            $line_insert = "INSERT INTO `sales_credit_items`(`cid`,`sales_item_id`,`return_item_id`,`qty`,`amount`) 
            VALUES ($scid,".$row['id'].",".prep($row['returns_item_id'],"NULL").",".prep($row['qty'],"NULL").",".prep($row['price'],"NULL").");";
            qedb($line_insert);
        }

    }
    //Grab the order origin and 
    
?>