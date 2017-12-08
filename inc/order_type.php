<?php
	function order_type($order_type) {
		$T = array();

		switch ($order_type) {
			case 'Invoice':
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
				$T['delivery_date'] = '';
				$T['charges'] = '';//sales_charges';
				$T['cust_ref'] = '';
				$T['description'] = 'memo';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = 'AR';
				$T['confirmation'] = false;
				$T['support'] = false;
				break;

			case 'Bill':
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
				$T['delivery_date'] = '';
				$T['charges'] = '';//sales_charges';
				$T['cust_ref'] = '';
				$T['description'] = 'memo';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = 'AP';
				$T['confirmation'] = false;
				$T['support'] = false;
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
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = '';
				$T['confirmation'] = false;
				$T['support'] = false;
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
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = 'rma_number';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Bill To';
				$T['account'] = 'AR';
				$T['confirmation'] = false;
				$T['support'] = false;
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
				$T['delivery_date'] = 'receive_date';
				$T['charges'] = 'purchase_charges';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Remit To';
				$T['account'] = 'AP';
				$T['confirmation'] = false;
				$T['support'] = false;
				break;

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
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['confirmation'] = true;
				$T['support'] = true;
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
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = 'description';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['confirmation'] = false;
				$T['support'] = false;
				break;

			case 'service_quote':
				$T['orders'] = 'service_quotes';
				$T['order'] = 'id';
				$T['items'] = 'service_quote_items';
				$T['item_label'] = 'service_item_id';
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
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = 'description';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['confirmation'] = false;
				$T['support'] = false;
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
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Remit To';
				$T['account'] = 'AP';
				$T['confirmation'] = false;
				$T['support'] = false;
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
				$T['delivery_date'] = 'delivery_date';
				$T['charges'] = 'sales_charges';
				$T['cust_ref'] = 'cust_ref';
				$T['description'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AR';
				$T['confirmation'] = true;
				$T['support'] = true;
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
				$T['abbrev'] = 'Outside Order';
				$T['datetime'] = 'datetime';
				$T['addressid'] = 'bill_to_id';
				$T['alert'] = 'info';
				$T['condition'] = '';
				$T['warranty'] = 'warrantyid';
				$T['warrantyid'] = 7;
				$T['amount'] = 'price';
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = 'notes';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Billing';
				$T['account'] = 'AP';
				$T['confirmation'] = false;
				$T['support'] = false;
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
				$T['delivery_date'] = '';
				$T['charges'] = '';
				$T['cust_ref'] = '';
				$T['description'] = '';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				$T['account'] = 'AR';
				$T['confirmation'] = false;
				$T['support'] = false;
				break;
		}

		return ($T);
	}
?>
