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
	include_once $rootdir.'/inc/getAddresses.php';
	include_once $rootdir.'/inc/form_handle.php';
	include_once $rootdir.'/inc/dropPop.php';
	include_once $rootdir.'/inc/display_part.php';
	include_once $rootdir.'/inc/invoice.php';
	include_once $rootdir.'/inc/renderOrder.php';
	include_once $rootdir.'/inc/credit_creation.php';
	include_once $rootdir.'/inc/setPart.php';
	
	
	function split_components($invid, $new_qty, $id_type = "", $id_number = ""){
	//Function which takes an old inventory_id and returns a new record.
	    $pinvid = prep($invid);
	    $grab_components = "SELECT i.*, p.classification FROM `inventory` `i`, `parts` p where p.`id` = `partid` and i.`id` = $pinvid;";
        $results = qdb($grab_components) or die(qe()." | $grab_components");
        if(mysqli_num_rows($results)){
            $r = mysqli_fetch_assoc($results);
            if($r['classification'] == "component"){
                if(!$r['qty']){
                    return("No Quantity To Be Split!");
                }
                $orig_qty = $r['qty']; //Alias for the beginning quantity
                $adj_qty = $orig_qty - $new_qty; //This is the quantity which will adjust the initial record.
                if ($orig_qty != 0 && $new_qty != 0){
                    $new_rate = (($new_qty < $orig_qty)? ($new_qty / $orig_qty) : ($orig_qty/$new_qty)); //This rate is applied to all cloned rows
                }
                $orig_rate = 1 - $new_rate; //This is the rate which affects the original record: changing initial costs
                
                $order_line = '';

                $inv_insert = "INSERT INTO `inventory` (`qty`, `partid`, `conditionid`, `status`, `locationid`, `purchase_item_id`, `sales_item_id`, `returns_item_id`, `repair_item_id`, `userid`, `date_created`, `notes`) 
                SELECT $new_qty, `partid`, `conditionid`, `status`, `locationid`, `purchase_item_id`, `sales_item_id`, `returns_item_id`, `repair_item_id`, `userid`, `date_created`, concat('SPLIT: ',`notes`) 
                FROM `inventory` WHERE id = $pinvid;";
                qdb($inv_insert) or die(qe()." | $inv_insert");
                echo($inv_insert."<br>");
                $new_invid = qid();
                $pnew_invid = prep($new_invid);
                
                $qty_update = "UPDATE `inventory` SET `qty` = ".prep($adj_qty)." WHERE `id` = $pinvid;";
                qdb($qty_update) or die(qe()." | $qty_update");
                echo($qty_update."<br>");
                
                $inv_history = "INSERT INTO `inventory_history`(`date_changed`, `userid`, `invid`, `field_changed`, `value`, `changed_from`) 
                SELECT `date_changed`, `userid`, $pnew_invid, `field_changed`, `value`, `changed_from` FROM `inventory_history` WHERE  invid = $pinvid;";            
                qdb($inv_history) or die(qe()." | $inv_history");
                echo($inv_history."<br>");
                
                $inv_cost ="INSERT INTO `inventory_costs` (`inventoryid`, `datetime`, `actual`, `average`, `notes`)
                SELECT $pnew_invid, `datetime`, `actual`, `average`, `notes` FROM `inventory_costs` where `inventoryid` = $pinvid;";
                qdb($inv_cost) or die(qe()." | $inv_cost");
                echo($inv_cost."<br>");
                
                $icl_insert = "INSERT INTO `inventory_costs_log`(`inventoryid`, `eventid`, `event_type`, `amount`)
                SELECT $pnew_invid, `eventid`, `event_type`, `amount` FROM `inventory_costs_log` where `inventoryid` = $pinvid";
                qdb($icl_insert) or die(qe()." | $icl_insert");
                echo($icl_insert."<br>");
                
                $pc_insert = "INSERT INTO `package_contents` (`packageid`, `serialid`)
                SELECT `packageid`, $pnew_invid FROM `package_contents` where `serialid` = $pinvid;";
                qdb($pc_insert) or die(qe()." | $pc_insert");
                echo($pc_insert."<br>");
                
                $o = array();
                if($id_type){
                    $o = o_params($id_type); //If there is an instigating type, then take in the id type so we know which field we are lookin at
                    $inv_id_update = "UPDATE `inventory` SET ".$o['inv_item_id']." = ".prep($id_number)." WHERE id = $pnew_invid;";//Automatically updates the inventory_history table
                    qdb($inv_id_update) or die(qe()." | $inv_id_update");
                    echo($inv_id_update."<br>");
                }
                $update_old_costs = "UPDATE `inventory_costs` SET 
                `actual` = (`actual` * $orig_rate),
                `average` = (`average` * $orig_rate)
                WHERE `inventoryid` = $pinvid;";
                qdb($update_old_costs) or die(qe()." | $update_old_costs");
                echo($update_old_costs."<br>");
                
                
                $update_new_costs = "UPDATE `inventory_costs` SET 
                `actual` = (`actual` * $new_rate),
                `average` = (`average` * $new_rate)
                WHERE `inventoryid` = $pnew_invid;";
                qdb($update_new_costs) or die(qe()." | $update_new_costs");
                echo($update_new_costs."<br>");
                
                // "INSERT INTO `inventory`(`qty`, `partid`, `conditionid`, `status`, `locationid`, `purchase_item_id`, `sales_item_id`, `returns_item_id`, `repair_item_id`, `userid`, `date_created`, `notes`) ";
                // "VALUES (".prep($new_qty).", ".prep($r['partid']).", ".prep($r['conditionid']).", ".prep($r['status']).", ".prep($r['locationid']).", ".prep($r['purchase_item_id'])."
                // ".(($o['sales'])? prep($id_number) : prep($r['sales_item_id'])).", 
                // ".(($o['rma'])? prep($id_number) : "").", 
                // ".(($o['repair'])? prep($id_number) : "").", 
                // `userid`, `date_created`, `notes`) ;";
            } else {
                echo "Part cannot be split (is not a component)";
                return;
            }
            
            
        } else {
            echo "No Such Inventory Record Found";
            return;
        }
        return $new_invid;
	}

?>