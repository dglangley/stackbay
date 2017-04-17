<?php
    function o_params($type){
		$info = array();
		if($type == "p" || strtolower($type) == "purchase" || strtolower($type) == "purchases"){
			$info['type'] = "Purchase";
			//convenient type check
			$info['purchase'] = true;
			$info['sales'] = false;
			$info['invoice'] = false;
			$info['rtv'] = false;
			$info['rma'] = false;
			$info['credit'] = false;
			
			
			$info['order'] = "purchase_orders";
			$info['header'] = "Purchase Order";
			$info['item'] = "purchase_items";
			$info['client'] = "Vendor";
			$info['price'] = 'Price';
			$info['ext'] = 'Ext Price';
			$info['rep_type'] = "Purchase";
			$info['date_label'] = "PO";
			$info['tables'] = " purchase_orders o, purchase_items i WHERE o.po_number = i.po_number ";
			$info['short'] = "po";
			$info['id'] = "po_number";
			$info['item_id'] = $info['id'];
			$info['active'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) > 0 ";
			$info['inactive'] = " AND (CAST(i.qty AS SIGNED) - CAST(i.qty_received AS SIGNED)) <= 0 ";
			$info['status_empty'] = "Void";
			$info['url'] = "inventory_add";
			$info['color'] = '#f5dfba';
			$info['edit_mode'] = 'order';
			$info['date_field'] = 'receive_date';
			//Field header information
			$info['due_date'] = true;
			$info['warranty'] = true;
			
		}
		else if ($type == "s" || strtolower($type) == "sale" || strtolower($type) == "sales" || strtolower($type) == "so"){
			$info['type'] = "Sales";
			//convenient type check
			$info['purchase'] = false;
			$info['sales'] = true;
			$info['invoice'] = false;
			$info['rtv'] = false;
			$info['rma'] = false;
			$info['credit'] = false;
			
			$info['order'] = "sales_orders";
			$info['header'] = "Sales Order";
			$info['item'] = "sales_items";
			$info['client'] = "Vendor";
			$info['price'] = 'Price';
			$info['ext'] = 'Ext Price';
			$info['rep_type'] = "Purchase";
			$info['date_label'] = "SO";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "so";
			$info['id'] = "so_number";
			$info['item_id'] = $info['id'];
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['status_empty'] = "Void";
			$info['url'] = "shipping";
			$info['color'] = '#f7fff0';
			$info['edit_mode'] = 'order';
			$info['date_field'] = 'delivery_date';
			//Field header information
			$info['due_date'] = true;
			$info['warranty'] = true;
			

		}
		else if (strtolower($type) == "invoice" || strtolower($type) == "inv" || strtolower($type) == "i"){
			// Remember: Invoice has few edits, but is built from a Sales Order
			$info['type'] = "Invoice";
			$info['purchase'] = false;
			$info['sales'] = false;
			$info['invoice'] = true;
			$info['rtv'] = false;
			$info['rma'] = false;
			$info['credit'] = false;
			
			$info['order'] = "invoices";
			$info['header'] = "Invoice ";
			$info['item'] = "invoice_items";
			$info['client'] = "Customer";
			$info['price'] = 'Price';
			$info['ext'] = 'Ext Price';
			$info['rep_type'] = "Sales";
			$info['date_label'] = "Invoice";
			$info['tables'] = " invoices i, invoice_items ii WHERE i.invoice_no = ii.invoice_no ";
			$info['short'] = "INV";
			$info['id'] = "invoice_no";
			$info['item_id'] = $info['id'];
			$info['active'] = " AND i.status = 'Pending' ";
			$info['inactive'] = " AND i.status = 'Completed' ";
			$info['status_empty'] = "Void";
			$info['url'] = "shipping";
            $info['color'] = '#94b4b5';
            $info['edit_mode'] = 'display';
            $info['date_field'] = 'receive_date';
            //Field header information
            $info['due_date'] = false;
            $info['warranty'] = true;
            
		}
		else if (strtolower($type) == "rtv"){
			// Remember: RTV acts like a Sales Order
			$info['type'] = "RTV";
			$info['rtv'] = true;
			$info['purchase'] = false;
			$info['sales'] = false;
			$info['invoice'] = false;
			$info['rtv'] = true;
			$info['rma'] = false;
			$info['credit'] = false;
			
			$info['order'] = "sales_orders";
			$info['header'] = "RTV Order";
			$info['item'] = "";
			$info['client'] = "Vendor";
			$info['price'] = 'Price';
			$info['ext'] = 'Ext Price';
			$info['rep_type'] = "Purchase";
			$info['date_label'] = "PO";
			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "so";
			$info['id'] = "so_number";
			$info['item_id'] = $info['id'];
			$info['active'] = " AND i.ship_date IS NULL ";
			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['status_empty'] = "Void";
			$info['url'] = "inventory_add";
            $info['color'] = '#f7fff0';
            $info['edit_mode'] = 'order';
            $info['date_field'] = 'receive_date';
            //Field header information
            $info['due_date'] = true;
            $info['warranty'] = true;
            
		}
		else if (strtolower($type) == "rma"){
			//RMA acts as a purchase order
			$info['type'] = "RMA";
			//Convenient type check
			$info['purchase'] = false;
			$info['sales'] = false;
			$info['invoice'] = false;
			$info['rtv'] = false;
			$info['rma'] = true;
			$info['credit'] = false;
			
			
			$info['order'] = "returns";
			$info['header'] = "RMA";
			$info['item'] = "return_items";
			$info['client'] = "Customer";
			$info['price'] = 'Reason';
			$info['ext'] = 'Ext Price';
			
			$info['rep_type'] = "Sales";
			$info['date_label'] = "RMA";
// 			$info['tables'] = " sales_orders o, sales_items i WHERE o.so_number = i.so_number ";
			$info['short'] = "po";
			$info['id'] = "po";
			$info['item_id'] = $info['id'];
// 			$info['active'] = " AND i.ship_date IS NULL ";
// 			$info['inactive'] = " AND i.ship_date IS NOT NULL  ";
			$info['status_empty'] = "Void";
			$info['url'] = "inventory_add";
            $info['color'] = '#f5dfba';
            $info['edit_mode'] = 'order';
            $info['date_field'] = 'receive_date';
            //Field header information
            $info['due_date'] = false;
            $info['warranty'] = false;
            

		}
		else if (strtolower($type) == "credit" || strtolower($type) == "cm" || strtolower($type) == "c"){
			// Remember: Invoice has few edits, but is built from a Sales Order
			$info['type'] = "Credit";
			$info['purchase'] = false;
			$info['sales'] = false;
			$info['invoice'] = false;
			$info['rtv'] = false;
			$info['rma'] = false;
			$info['credit'] = true;
			
			$info['order'] = "sales_credits";
			$info['id'] = "id";
			$info['item_id'] = 'rma';
			$info['header'] = "Credit Memo ";
			$info['item'] = "sales_credit_items";
			$info['client'] = "Customer";
			$info['price'] = 'Amount Ea.';
			$info['ext'] = 'Ext Price';
			$info['rep_type'] = "Sales";
			$info['date_label'] = "Invoice";
			$info['tables'] = " sales_credits sc, sales_credit_items sci WHERE sci.cid = sc.id ";
			$info['short'] = "INV";
			$info['active'] = " AND i.status = 'Pending' ";
			$info['inactive'] = " AND i.status = 'Completed' ";
			$info['status_empty'] = "Void";
			$info['url'] = "shipping";
            $info['color'] = '#94b4b5';
            $info['edit_mode'] = 'display';
            $info['date_field'] = 'receive_date';
            //Field header information
            $info['due_date'] = false;
            $info['warranty'] = false;
            
		}

		else{
			$info['case'] = $type;
		}
		return $info;
    }
?>