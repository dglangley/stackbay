<?php
    include '../inc/dbconnect.php';
    include '../inc/format_date.php';
    include '../inc/keywords.php';
    include_once '../inc/form_handle.php';
    include_once '../inc/getCondition.php';
    include_once '../inc/getLocation.php';
    
    //if (isset($_REQUEST['q'])) { $q = trim($_REQUEST['q']); }
    $partid = (isset($_REQUEST['partid']) ? $_REQUEST['partid'] : 0);
    $itemid = (isset($_REQUEST['itemid']) ? $_REQUEST['itemid'] : 0);
    $type = (isset($_REQUEST['type']) ? $_REQUEST['type'] : 'repair');

    // Using a combination of the itemid (Repair, Build, Service id etc) find the current status of the order and pull floating free items
    function searchStock($partid, $itemid, $type) {
        $results = array();

        // Query to only grab the ones linked to the purchase request (PO) Also must be in stock
        // Then with the union grab the rest
        $query = ($itemid != 0 ? "SELECT i.*, 'true' as requested FROM purchase_items pi, inventory i WHERE pi.ref_1_label = 'repair_item_id' AND pi.ref_1='".res($itemid)."' AND pi.id = i.purchase_item_id AND i.partid = '".res($partid)."' AND i.qty > 0 AND (status = 'shelved' OR status = 'received')
                UNION" : "") . "
            SELECT i.*, '' as requested FROM purchase_items pi, inventory i WHERE i.partid = '".res($partid)."' AND i.purchase_item_id = pi.id AND (pi.ref_1_label <> 'repair_item_id' OR pi.ref_1_label IS NULL) AND i.qty > 0 AND (status = 'shelved' OR status = 'received')
            UNION 
            SELECT i.*, '' as requested FROM inventory i WHERE i.partid = '".res($partid)."' AND i.purchase_item_id IS NULL AND i.qty > 0 AND (status = 'shelved' OR status = 'received');";
        $result = qdb($query) OR die(qe() . '' . $query);

        //echo $query;

        while ($row = $result->fetch_assoc()) {
            $row['condition'] = getCondition($row['conditionid']);
            $row['location'] = getLocation($row['locationid']);

            $results[$row['id']] = $row;
        }

        return $results;
    }
    
    $results = array();

    $results = searchStock($partid, $itemid, $type);

    echo json_encode($results);
    exit;