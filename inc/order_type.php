<?php
	function order_type($order_type) {
		$T = array();

		switch ($order_type) {
			case 'IT':
				$T['orders'] = '';
				$T['order'] = '';
				$T['items'] = '';
				$T['item_label'] = '';
				$T['inventory_label'] = '';
				$T['abbrev'] = 'IT';
				break;

			case 'Return':
				$T['orders'] = 'returns';
				$T['order'] = 'rma_number';
				$T['items'] = 'return_items';
				$T['item_label'] = 'return_item_id';
				$T['inventory_label'] = 'returns_item_id';
				$T['abbrev'] = 'RMA';
				break;

			case 'Purchase':
				$T['orders'] = 'purchase_orders';
				$T['order'] = 'po_number';
				$T['items'] = 'purchase_items';
				$T['item_label'] = 'purchase_item_id';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'PO';
				break;

			case 'Repair':
				$T['orders'] = 'repair_orders';
				$T['order'] = 'ro_number';
				$T['items'] = 'repair_items';
				$T['item_label'] = 'repair_item_id';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'RO';
				break;

			case 'Sale':
			default:
				$T['orders'] = 'sales_orders';
				$T['order'] = 'so_number';
				$T['items'] = 'sales_items';
				$T['item_label'] = 'sales_item_id';
				$T['inventory_label'] = $T['item_label'];
				$T['abbrev'] = 'SO';
				break;
		}

		return ($T);
	}
?>
