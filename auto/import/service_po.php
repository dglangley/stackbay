<?php
exit;
    include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/getCompany.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/svcs_pipe.php';
    include_once $_SERVER["ROOT_DIR"].'/inc/imports.php';

    function mapTerms($termid) {
        // Just straight map it manually because there isn't a lot of records
        $terms = array(1 => '10', 2 => '6', 3 => '12', 4 => '4', 5 => '14', 6 => '13', 7 => '7', 8 => '3', 9 => '2', 10 => '11', 11 => '8', 12 => '1', 13 => '1', 14 => '9');

        return $terms[$termid];
    }

    function mapWarranty($warranty_days) {
        $warrantyid = 0;

        $query = "SELECT id FROM warranties WHERE days = ".fres(trim($warranty_days)).";";
        $result = qedb($query);

        if(mysqli_num_rows($result)) {
            $r = mysqli_fetch_assoc($result);

            $warrantyid = $r['id'];
        }

        return $warrantyid;
    }

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

	$query = "DELETE FROM purchase_orders WHERE po_number IN (SELECT po_number FROM maps_PO_tools); ";
	$result = qedb($query);

    $query = "DELETE FROM packages WHERE order_number IN (SELECT po_number FROM maps_PO_tools) AND order_type = 'Purchase'; ";
    $result = qedb($query);

	$query = "TRUNCATE maps_PO_tools; ";
	$result = qedb($query);
    
    // Reset data and import code for jobs within the set range
    $DATA = array();
    
    $query = "SELECT *, si.date as date_ship FROM services_purchaseorder sp ";
    $query .= "LEFT JOIN services_purchasequote sq ON sq.id = sp.purchasequote_ptr_id ";
    $query .= "LEFT JOIN services_purchaseinvoice si ON si.po_id = sp.purchasequote_ptr_id ";
	$query .= ";";
    $result = qdb($query,'SVCS_PIPE') OR die(qe('SVCS_PIPE').'<BR>'.$query);

    while($r = mysqli_fetch_assoc($result)) {
        $DATA[] = $r;
    }

    // print "<pre>" . print_r($DATA, true) . "</pre>";
    // die();

    // Import Job Data
    foreach($DATA as $order) {
        // Add 1000 to the PO number as specified to avoid conflicts with the live data
        $purchase_order_num = intval($order['purchasequote_ptr_id']) + 1000;

        // Map all the needed variables to created the purchase and item
        $companyid = companyMap($order['company_id']);

        if(! $companyid) {
            $companyid = 6;
        }

		$userid = mapUser($order['purchase_rep_id']);
        $termsid = mapTerms($order['terms_id']);
        $warrantyid = mapWarranty($order['warranty_period_id']);

        // Straight map of the freight carriers
        $freight_carrier = array(5 => "3", 6 => "3", 9 => "1", 10 => "1", 11 => "1");
        $freight_service = array(5 => "13", 6 => "14", 9 => "2", 10 => "1", 11 => "12");

        $query = "REPLACE INTO purchase_orders (po_number, created, created_by, sales_rep_id, companyid, ship_to_id, freight_carrier_id, freight_services_id, termsid, private_notes, status) VALUES (";
            $query .= fres($purchase_order_num). ", ";
            $query .= fres($order['po_date']). ", ";
            $query .= fres($userid). ", ";
            $query .= fres($userid). ", ";
            $query .= fres($companyid). ", ";
            $query .= "NULL, ";

            // Freight ID
            $query .= fres($freight_carrier[intval($order['freight_carrier_id'])]) . ", ";
            $query .= fres($freight_service[intval($order['freight_carrier_id'])]) . ", ";

            $query .= fres($termsid). ", ";
            $query .= fres($order['memo']). ", ";
            $query .= "'Active'";
        $query .= ");";
        qedb($query);

        // if($purchase_order_num) {
            // $query = "INSERT INTO purchase_items (partid, po_number, line_number, qty, received, price, receive_date, warranty, conditionid) VALUES (";
            //     $query .= fres() ", ";
            //     $query .= fres($po_number) ", ";
            //     $query .= "'1', ";
            //     $query .= "'1', ";
            //     $query .= fres($order['delivery_due']) ", ";
            //     $query .= fres($order['freight_cogs'])) ", ";
            //     $query .= fres($warrantyid) ", ";
            //     $query .= "'2'";
            // $query .= ");";
            // qedb($query);

            // This query for for creating the package information
            // This table as freight_amount, but freight_cogs is being used in purchase items
            if($order['tracking_no'] AND $order['tracking_no'] != "No Ship") {
                $query = "INSERT INTO packages (order_number, order_type, package_no, tracking_no, datetime, freight_amount) VALUES (";
                    $query .= fres($purchase_order_num). ", ";
                    $query .= "'Purchase', ";
                    $query .= "'1', ";
                    $query .= fres($order['tracking_no']). ", ";
                    $query .= fres($order['date_ship']).", ";
                    $query .= fres($order['freight_cogs']);
                $query .= ");";
                qedb($query);
            }
        // }

        $query = "INSERT INTO maps_PO_tools (BDB_poid, po_number) VALUES (".res($order['purchasequote_ptr_id']).", ".res($purchase_order_num).");";
        qedb($query);
    }

    echo "IMPORT COMPLETE!";
