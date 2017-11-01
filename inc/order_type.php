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
				$T['memo'] = 'memo';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				break;

			case 'Bill':
			case 'bill_item_id':
				$T['orders'] = 'bills';
				$T['order'] = 'bill_no';
				$T['items'] = 'bill_items';
				$T['item_label'] = 'bill_item_id';
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
				$T['memo'] = 'memo';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				break;

			case 'Return':
			case 'return_item_id':
			case 'returns_item_id':
				$T['orders'] = 'returns';
				$T['order'] = 'rma_number';
				$T['items'] = 'return_items';
				$T['item_label'] = 'return_item_id';
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
				$T['memo'] = '';
				$T['collection'] = '';
				$T['collection_no'] = '';
				break;

			case 'Purchase':
			case 'purchase_item_id':
				$T['orders'] = 'purchase_orders';
				$T['order'] = 'po_number';
				$T['items'] = 'purchase_items';
				$T['item_label'] = 'purchase_item_id';
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
				$T['memo'] = '';
				$T['collection'] = 'bills';
				$T['collection_no'] = 'bill_no';
				$T['collection_term'] = 'Remit To';
				break;

			case 'Repair':
			case 'repair_item_id':
				$T['orders'] = 'repair_orders';
				$T['order'] = 'ro_number';
				$T['items'] = 'repair_items';
				$T['item_label'] = 'repair_item_id';
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
				$T['memo'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				break;

			case 'Service':
			case 'service_item_id':
				$T['orders'] = 'service_orders';
				$T['order'] = 'so_number';
				$T['items'] = 'service_items';
				$T['item_label'] = 'service_item_id';
				$T['inventory_label'] = 'service_item_id';
				$T['abbrev'] = 'SO';
				$T['datetime'] = 'datetime';
				$T['addressid'] = 'bill_to_id';
				$T['alert'] = 'info';
				$T['condition'] = '';
				$T['warranty'] = '';
				$T['warrantyid'] = 7;
				$T['amount'] = 'amount';
				$T['delivery_date'] = 'due_date';
				$T['charges'] = '';
				$T['cust_ref'] = 'cust_ref';
				$T['memo'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				break;

			case 'Sale':
			case 'sales_item_id':
				$T['orders'] = 'sales_orders';
				$T['order'] = 'so_number';
				$T['items'] = 'sales_items';
				$T['item_label'] = 'sales_item_id';
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
				$T['memo'] = '';
				$T['collection'] = 'invoices';
				$T['collection_no'] = 'invoice_no';
				$T['collection_term'] = 'Billing';
				break;

			case 'IT':
			default:
				$T['orders'] = '';
				$T['order'] = '';
				$T['items'] = '';
				$T['item_label'] = '';
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
				$T['memo'] = '';
				$T['collection'] = '';
				$T['collection_no'] = '';
				$T['collection_term'] = '';
				break;
		}

		return ($T);
	}
?>
