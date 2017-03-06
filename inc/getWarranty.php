<?php
	include_once $_SERVER["ROOT_DIR"].'/inc/dbconnect.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/form_handle.php';
	include_once $_SERVER["ROOT_DIR"].'/inc/format_date.php';
	
	function getWarranty($id = '%', $field = 'all'){
    
        $id = prep($id);
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
		$date;
		$warranty;
		$warranty_lines;
		$query;
		
		$today = date($date_format);

		//If querying our warranty
		if($type == 'sales') {
			$query = "SELECT w.days, o.created FROM sales_items as s, warranties as w, sales_orders as o, inventory as i WHERE i.id = ".prep($invid)." AND i.sales_item_id = s.id AND s.warranty = w.id AND o.so_number = s.so_number;";
		//If querying vendor warranty
		} else {
			$query = "SELECT w.days, o.created FROM purchase_items as p, warranties as w, purchase_orders as o, inventory as i WHERE i.id = ".prep($invid)." AND i.purchase_item_id = p.id AND p.warranty = w.id AND o.po_number = p.po_number;";
		}
		
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
				$warranty_lines = "<span class='expired_warranty'>";
			} else {
				$warranty_lines = "<span class='in_warranty'>";
			}
			$warranty_lines .= $date_text;
			$warranty_lines .= "</span>";
		}
		
		return $date_text;
	}
