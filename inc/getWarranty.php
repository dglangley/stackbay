<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/order_parameters.php';
	
	function getWarranty($id = '%', $field = 'all'){
    
        $id = fres($id);
        $select = "Select * FROM warranties Where id LIKE $id;";
        $results = qdb($select);
        
        if ($field == 'all'){
            return $results;
        }
        elseif($field == 'warranty' || $field == 'name'){
            if ($id != '%'){
                $name = '';
                foreach ($results as $r){
                    $name = $r['warranty'];
                }
            }
            else{
                foreach ($results as $r){
                    $name = array();
                    $name[] = $r['warranty'];
                }
            }
            return $name;
        }
        elseif('idkyet'){
            return 'something';
        }
	}
	function calcWarranty($invid, $type = 'sales') {
		global $today;

		$date;
		$warranty;
		$warranty_lines;
		$query;
		
		//$today = date($date_format);

		//If querying our warranty
		if($type == 'sales') {
			$query = "SELECT w.days, o.created FROM sales_items as s, warranties as w, sales_orders as o, inventory as i ";
			$query .= "WHERE i.id = ".fres($invid)." AND i.sales_item_id = s.id AND s.warranty = w.id AND o.so_number = s.so_number;";
		//If querying vendor warranty
		} else if($type == 'history'){
		    //In the history case, the inventory ID is not what is passed in, rather we get the PO line item id
		    $query = "SELECT w.days, o.created FROM purchase_items p, warranties w, purchase_orders o WHERE p.id = ".fres($invid)." AND p.warranty = w.id AND o.po_number = p.po_number;";
		} else {
			$query = "SELECT w.days, o.created FROM purchase_items as p, warranties as w, purchase_orders as o, inventory as i WHERE i.id = ".fres($invid)." AND i.purchase_item_id = p.id AND p.warranty = w.id AND o.po_number = p.po_number;";
		}
		
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$date = $result['created'];
			$warranty = $result['days'];
		
			//Create the date
			$warranty_date = format_date($result['created'],'Y-m-d', array("d"=>$result['days']));
			$date_text = format_date($warranty_date);
            
			//Add warranty days
			// $date = date($date_format, strtotime($date. ' + '.$warranty.' days'));
			
			//Expired
			if($today < $warranty_date) {
				$warranty_lines = "<span class='in_warranty'>";
			} else {
				$warranty_lines = "<span class='expired_warranty'>";
			}
			$warranty_lines .= $date_text;
			$warranty_lines .= "</span>";
		}
		
		return $warranty_lines;
	}
	function getWarrByDays($days){
		//Function returns a warrantyId by days of warr length
		$query = "SELECT id FROM warranties where days like ".fres($days).";";
		$result = qdb($query) or die(qe()." | Couldn't get warranties by days | ".$query);
		$result = mysqli_fetch_assoc($result);
		return $result['id'];
	}
	
	//Temporary function to calculate Purchase Receive (Inventory Add) Vendor Warranty
	function calcPOWarranty($orderid, $warranty) {
		$date;
		$warranty;
		$warranty_lines;
		$query;
		
		$today = date($date_format);

		//If querying our warranty

		$query = "SELECT w.days, o.created FROM purchase_items as p, warranties as w, purchase_orders as o WHERE p.id = ".fres($orderid)." AND p.warranty = w.id AND o.po_number = p.po_number;";
		
		$result = qdb($query) or die(qe());
		
		if (mysqli_num_rows($result)>0) {
			$result = mysqli_fetch_assoc($result);
			$date = $result['created'];
			$warranty = $result['days'];
		
			//Create the date
			$warranty_date = format_date($result['created'],'Y-m-d', array("d"=>$result['days']));
			$date_text = summarize_date($warranty_date);
            
			//Add warranty days
			// $date = date($date_format, strtotime($date. ' + '.$warranty.' days'));
			
			//Expired
			if($date < $warranty_date) {
				$warranty_lines = "<span class='in_warranty'>";
			} else {
				$warranty_lines = "<span class='expired_warranty'>";
			}
			$warranty_lines .= $date_text;
			$warranty_lines .= "</span>";
		}
		
		return $warranty_lines;
	}

	function getDefaultWarranty($cid,$use_default=true){
		if ($use_default) {
			$warrantyid = (int)"7";//default for all companies unless something is entered below
		} else {
			$warrantyid = 0;
		}

		if (! $cid) { return ($warrantyid); }

		$query = "SELECT default_warrantyid FROM companies WHERE id = ".res($cid).";";
		$result = qedb($query);

		if(qnum($result)) {
			$r = qrow($result);

			if ($r['default_warrantyid']) {
				$warrantyid = $r['default_warrantyid'];
			}
		}

		return $warrantyid;
	}
