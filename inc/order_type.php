<?php
	function order_type($order_type) {
		$T = array();

		switch ($order_type) {
			case 'Invoice':
			case 'invoices':
			case 'invoice_item_id':
				$T['orders'] = 'invoices';
				$T['order'] = 'invoice_no';
				$T['items'] = 'invoice_items';
				$T['item_label'] = 'invoice_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'INV';
				$T['datetime'] = 'date_invoiced';
				$T['addressid'] = '';
				$T['alert'] = 'default';
				$T['condition'] = '';
				$T['warranty'] = 'warranty';
				$T['warrantyid'] = 4;
				$T['amount'] = 'amount';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = 'invoice_charges';
				$T['cust_ref'] = '';
				$T['description'] = 'memo';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = 'AR';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Invoice';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'Bill':
			case 'bills':
			case 'bill_item_id':
				$T['orders'] = 'bills';
				$T['order'] = 'bill_no';
				$T['items'] = 'bill_items';
				$T['item_label'] = 'bill_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'BILL';
				$T['datetime'] = 'date_created';
				$T['addressid'] = '';
				$T['alert'] = 'default';
				$T['condition'] = '';
				$T['warranty'] = 'warranty';
				$T['warrantyid'] = 4;
				$T['amount'] = 'amount';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = 'bill_charges';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = 'memo';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Bill';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'Return':
			case 'return_item_id':
			case 'returns_item_id':
				$T['orders'] = 'returns';
				$T['order'] = 'rma_number';
				$T['items'] = 'return_items';
				$T['item_label'] = 'return_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = 'returns_item_id';
				$T['abbrev'] = 'RMA';
				$T['datetime'] = 'created';
				$T['addressid'] = '';
				$T['alert'] = 'default';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 14;
				$T['amount'] = '';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = '';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Return';
				$T['labor_cost'] = false;
				$T['icon'] = 'fa-exchange';
				$T['status_code'] = '';
				break;

			case 'Credit':
				$T['orders'] = 'credits';
				$T['order'] = 'id';
				$T['items'] = 'credit_items';
				$T['item_label'] = 'credit_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'CM';
				$T['datetime'] = 'date_created';
				$T['addressid'] = '';
				$T['alert'] = 'default';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 14;
				$T['amount'] = 'amount';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = 'rma_number';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Bill To';
				$T['account'] = 'AR';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Credit';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'Purchase':
			case 'purchase_item_id':
				$T['orders'] = 'purchase_orders';
				$T['order'] = 'po_number';
				$T['items'] = 'purchase_items';
				$T['item_label'] = 'purchase_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'PO';
				$T['datetime'] = 'created';
				$T['addressid'] = 'remit_to_id';
				$T['alert'] = 'warning';
				$T['condition'] = 'conditionid';
				$T['warranty'] = 'warranty';
				$T['warrantyid'] = 7;
				$T['amount'] = 'price';
				$T['qty'] = 'qty';
				$T['delivery_date'] = 'receive_date';
				$T['charges'] = 'purchase_charges';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Remit To';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Purchase';
				$T['labor_cost'] = false;
				$T['icon'] = 'fa-qrcode';
				$T['status_code'] = '';
				break;

			case 'Build':
			case 'Repair':
			case 'repair_item_id':
				$T['orders'] = 'repair_orders';
				$T['order'] = 'ro_number';
				$T['items'] = 'repair_items';
				$T['item_label'] = 'repair_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'RO';
				$T['datetime'] = 'created';
				$T['addressid'] = 'bill_to_id';
				$T['alert'] = 'info';
				$T['condition'] = '';
				$T['warranty'] = 'warrantyid';
				$T['warrantyid'] = 10;
				$T['amount'] = 'price';
				$T['qty'] = 'qty';
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['je_debit'] = 'Repair COGS';
				$T['je_credit'] = 'Component Inventory Asset';
				$T['confirmation'] = true;
				$T['support'] = 'Support';
				$T['type'] = 'Repair';
				$T['labor_cost'] = false;
				$T['icon'] = 'fa-wrench';
				$T['status_code'] = 'repair_code_id';
				break;

			case 'Service':
			case 'service_item_id':
				$T['orders'] = 'service_orders';
				$T['order'] = 'so_number';
				$T['items'] = 'service_items';
				$T['item_label'] = 'service_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = 'service_item_id';
				$T['abbrev'] = 'SO';
				$T['datetime'] = 'datetime';
				$T['addressid'] = 'bill_to_id';
				$T['alert'] = 'purple';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'amount';
				$T['qty'] = 'qty';
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = 'description';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['je_debit'] = 'EFI Service Inventory Sale COGS';
				$T['je_credit'] = 'EFI Service Inventory Asset';
				$T['confirmation'] = false;
				$T['support'] = 'Maintenance';
				$T['type'] = 'Service';
				$T['labor_cost'] = true;
				$T['icon'] = 'fa-wrench';
				$T['status_code'] = 'status_code';
				break;

			case 'service_quote':
			case 'service_quote_item_id':
				$T['orders'] = 'service_quotes';
				$T['order'] = 'quoteid';
				$T['items'] = 'service_quote_items';
				$T['item_label'] = 'service_quote_item_id';
				$T['record_type'] = 'quote';
				$T['order_type'] = 'Service';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'Quote';
				$T['datetime'] = 'datetime';
				$T['addressid'] = '';
				$T['alert'] = 'purple';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 0;
				$T['amount'] = 'amount';
				$T['qty'] = 'qty';
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = 'description';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'service_quote';
				$T['labor_cost'] = false;
				break;

			case 'purchase_request':
				$T['orders'] = 'purchase_requests';
				$T['order'] = 'id';
				$T['items'] = 'purchase_requests';
				$T['item_label'] = 'purchase_item_id';
				$T['record_type'] = 'quote';
				$T['order_type'] = 'Purchase';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'PR';
				$T['datetime'] = 'requested';
				$T['addressid'] = '';
				$T['alert'] = 'warning';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 0;
				$T['amount'] = '';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Remit To';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = '';
				$T['labor_cost'] = false;
				break;

			case 'Sale':
			case 'sales_item_id':
				$T['orders'] = 'sales_orders';
				$T['order'] = 'so_number';
				$T['items'] = 'sales_items';
				$T['item_label'] = 'sales_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'SO';
				$T['datetime'] = 'created';
				$T['addressid'] = 'bill_to_id';
				$T['alert'] = 'success';
				$T['condition'] = 'conditionid';
				$T['warranty'] = 'warranty';
				$T['warrantyid'] = 4;
				$T['amount'] = 'price';
				$T['qty'] = 'qty';
				$T['delivery_date'] = 'delivery_date';
				$T['charges'] = 'sales_charges';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['je_debit'] = 'Inventory Sale COGS';
				$T['je_credit'] = 'Inventory Asset';
				$T['confirmation'] = true;
				$T['support'] = 'Support';
				$T['type'] = 'Sale';
				$T['labor_cost'] = false;
				$T['icon'] = 'fa-truck';
				$T['status_code'] = '';
				break;

			case 'Outsourced':
			case 'outsourced_item_id':
				$T['orders'] = 'outsourced_orders';
				$T['order'] = 'os_number';
				$T['items'] = 'outsourced_items';
				$T['item_label'] = 'outsourced_item_id';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = '';
				// $T['abbrev'] = 'Outside Order';
				$T['abbrev'] = 'OS';
				$T['datetime'] = 'datetime';
				$T['addressid'] = 'bill_to_id';
				$T['alert'] = 'info';
				$T['condition'] = '';
				$T['warranty'] = 'warrantyid';
				$T['warrantyid'] = 7;
				$T['amount'] = 'price';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = 'notes';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Outsourced';
				$T['labor_cost'] = false;
				$T['icon'] = 'fa-wrench';
				$T['status_code'] = '';
				break;

			case 'Outsourced Quote':
			case 'service_quote_outsourced_id':
				$T['orders'] = 'service_quote_outsourced';
				$T['order'] = 'id';
				$T['items'] = 'service_quote_outsourced';
				$T['item_label'] = 'service_quote_outsourced_id';
				$T['record_type'] = 'quote';
				$T['order_type'] = '';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'OS Quote';
				$T['datetime'] = '';
				$T['addressid'] = '';
				$T['alert'] = 'info';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'amount';
				$T['qty'] = '';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = 'description';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Outsourced Quote';
				$T['labor_cost'] = false;
				$T['icon'] = 'fa-wrench';
				$T['status_code'] = '';
				break;

			case 'Supply':
			case 'Buy':
			case 'availability':
				$T['orders'] = 'search_meta';
				$T['order'] = 'metaid';
				$T['items'] = 'availability';
				$T['item_label'] = 'id';
				$T['record_type'] = 'quote';
				$T['order_type'] = 'Supply';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'Supply';
				$T['datetime'] = 'datetime';
				$T['addressid'] = '';
				$T['alert'] = 'orange';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'avail_price';
				$T['qty'] = 'avail_qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Remit To';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Supply';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'Demand':
			case 'Sell':
			case 'demand':
				$T['orders'] = 'search_meta';
				$T['order'] = 'metaid';
				$T['items'] = 'demand';
				$T['item_label'] = 'id';
				$T['record_type'] = 'quote';
				$T['order_type'] = 'Demand';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'Demand';
				$T['datetime'] = 'datetime';
				$T['addressid'] = '';
				$T['alert'] = 'green';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'quote_price';
				$T['qty'] = 'request_qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Bill To';
				$T['account'] = 'AR';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Demand';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'Repair Quote':
			case 'repair_quotes':
				$T['orders'] = 'search_meta';
				$T['order'] = 'metaid';
				$T['items'] = 'repair_quotes';
				$T['item_label'] = 'id';
				$T['record_type'] = 'quote';
				$T['order_type'] = 'Repair Quote';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'Repair Quote';
				$T['datetime'] = 'datetime';
				$T['addressid'] = '';
				$T['alert'] = 'green';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'price';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Bill To';
				$T['account'] = 'AR';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Repair Quote';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'Repair Source':
			case 'Repair Vendor':
			case 'repair_sources':
				$T['orders'] = 'search_meta';
				$T['order'] = 'metaid';
				$T['items'] = 'repair_sources';
				$T['item_label'] = 'id';
				$T['record_type'] = 'quote';
				$T['order_type'] = 'Repair Vendor';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'Repair Vendor';
				$T['datetime'] = 'datetime';
				$T['addressid'] = '';
				$T['alert'] = 'green';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'price';
				$T['qty'] = 'qty';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Bill To';
				$T['account'] = 'AP';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'Repair Vendor';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;

			case 'IT':
			default:
				$T['orders'] = '';
				$T['order'] = '';
				$T['items'] = '';
				$T['item_label'] = '';
				$T['record_type'] = 'order';
				$T['order_type'] = '';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'IT';
				$T['datetime'] = '';
				$T['addressid'] = '';
				$T['alert'] = 'default';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 0;
				$T['amount'] = '';
				$T['qty'] = '';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = 'AR';
				$T['je_debit'] = '';
				$T['je_credit'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
				$T['type'] = 'IT';
				$T['labor_cost'] = false;
				$T['icon'] = '';
				$T['status_code'] = '';
				break;
		}

		return ($T);
	}
?>
