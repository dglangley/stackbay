<?php
    function o_params($type){
		$info = array();
		if($type == "p" || strtolower($type) == "purchase" || strtolower($type) == "purchases"){
			$info['type'] = "Purchase";
			$info['order'] = "purchase_orders";
			$info['header'] = "Purchase Order";
			$info['item'] = "purchase_items";
			$info['tables'] = " purchase_orders o, purchase_items i WHERE o.po_number = i.po_number ";
			$info['short'] = "po";
			$info['id'] = "po_number";
			$info['active'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) > 0 ";
			$info['inactive'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) <= 0 ";
			$info['status_empty'] = "Void";
			$info['url'] = "inventory_add";
			$info['color'] = '#f5dfba';
			
			//Render Order Parameters
			$info['date_label'] = "PO";
			$info['rep_type'] = "Purchase";
			$info['client'] = "Vendor";
			$info['client_address'] = 'bill_to_id';
			$info['contact_col'] = "Sales Rep";
			$info['edit_mode'] = 'order';
			$info['date_field'] = 'receive_date';
			$info['endpoint_label'] = 'Ship To';
			
		}
		else if ($type == "s" || strtolower($type) == "sale" || strtolower($type) == "sales"){
			$info['type'] = "Sales";
			$info['order'] = "sales_orders";
			$info['header'] = "Sales Order";
			$info['item'] = "sales_items";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "so";
			$info['id'] = "so_number";
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['status_empty'] = "Void";
			$info['url'] = "shipping";
			$info['color'] = '#f7fff0';
			
			//Render Order Parameters
			$info['date_label'] = "SO";
			$info['rep_type'] = "Purchase";
			$info['client'] = "Vendor";
			$info['client_address'] = 'bill_to_id';
			$info['contact_col'] = "Sales Rep";
			$info['edit_mode'] = 'order';
			$info['date_field'] = 'delivery_date';
			$info['endpoint_label'] = 'Ship To';
			

		}
		else if (strtolower($type) == "rma"){
			//RMA acts as a purchase order
			$info['type'] = "RMA";
			$info['order'] = "returns";
			$info['header'] = "RMA";
			$info['item'] = "return_items";
// 			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "po";
			$info['id'] = "po";
// 			$info['active'] = " AND i.ship_date IS NULL ";
// 			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['status_empty'] = "Void";
			$info['url'] = "inventory_add";
            $info['color'] = '#f5dfba';
            
            //Render Order Parameters
			$info['date_label'] = "RMA";
			$info['rep_type'] = "Sales";
			$info['client'] = "Customer";
			$info['client_address'] = 'bill_to_id';
			$info['contact_col'] = "Contact";
            $info['edit_mode'] = 'order';
            $info['date_field'] = 'receive_date';
            $info['endpoint_label'] = 'Ship To';
            

		}
		else if (strtolower($type) == "rtv"){
			// Remember: RTV acts like a Sales Order
			$info['type'] = "RTV";
			$info['order'] = "";
			$info['header'] = "RTV Order";
			$info['item'] = "";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "so";
			$info['id'] = "so_number";
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['status_empty'] = "Void";
			$info['url'] = "inventory_add";
            $info['color'] = '#f7fff0';
            
            //Render Order Parameters
			$info['date_label'] = "PO";
			$info['rep_type'] = "Purchase";
			$info['client'] = "Vendor";
			$info['client_address'] = 'bill_to_id';
			$info['contact_col'] = "Sales Rep";
            $info['edit_mode'] = 'order';
            $info['date_field'] = 'receive_date';
            $info['endpoint_label'] = 'Ship To';
            
		}
		else if (strtolower($type) == "invoice" || strtolower($type) == "inv" || strtolower($type) == "i"){
			// Remember: Invoice has few edits, but is built from a Sales Order
			$info['type'] = "Invoice";
			$info['order'] = "invoices";
			$info['header'] = "Invoice ";
			$info['item'] = "invoice_items";
			$info['short'] = "INV";
			$info['id'] = "invoice_no";
			$info['active'] = " AND i.status = 'Pending' ";
			$info['inactive'] = " AND i.status = 'Completed' ";
			$info['status_empty'] = "Void";
			$info['url'] = "shipping";
            $info['color'] = '#94b4b5';
            
            //Render Order Parameters
			$info['tables'] = " invoices i, invoice_items ii WHERE i.invoice_no = ii.invoice_no ";
			$info['date_label'] = "Invoice";
			$info['client'] = "Customer";
			$info['client_address'] = 'bill_to_id';
			$info['contact_col'] = "SO #";
			$info['rep_type'] = "Sales";
            $info['edit_mode'] = 'display';
            $info['date_field'] = 'receive_date';
            $info['endpoint_label'] = 'Ship To';
            
		}
		else{
				$info['case'] = $type;
		}
		return $info;
    }
   
?>