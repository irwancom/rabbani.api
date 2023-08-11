<?php
namespace Service\Fulfillment;

use Service\Entity;
use Service\Delivery;


class FulfillmentOrderHandler {

	private $auth;
	private $delivery;
	private $repository;
	
	public function __construct ($repository, $auth = null) {
		$this->auth = $auth;
		$this->repository = $repository;
		$this->delivery = new Delivery;
	}
    
    public function saveOrder ($salesOrderId) {
        if ( ! isset($salesOrderId) || empty($salesOrderId)) {
            $this->delivery->addError(400, 'Order ID is required');
            return $this->delivery;
        }

		$existsOrder = $this->repository->findOne('transaction', ['salesorder_id' => $salesOrderId]);
		if ( ! $existsOrder) {
            $this->delivery->addError(400, 'Transaction not found');
            return $this->delivery;
		}

		$existsFullfiledOrder = $this->repository->findOne('fulfillment_orders', ['salesorder_id' => $salesOrderId]);
		if ($existsFullfiledOrder) {
            $this->delivery->addError(422, 'Transaction already fulfilled');
            return $this->delivery;
		}

		$data['salesorder_id'] = $salesOrderId;
		$data['accepted_by'] = $this->auth['id_auth'];
		$data['created_at'] = date('Y-m-d H:i:s');
		$action = $this->repository->insert('fulfillment_orders', $data);
		$result = $this->repository->findOne('fulfillment_orders', ['id' => $action]); 
		$this->delivery->data = $result;
		return $this->delivery;
    }

	public function getOrders ($filters = null, $findPaginated = true) {

		$args = [];
		$argsOrWhere = [];

		$args['fulfillment_orders.id'] = [
			'condition' => 'not_null',
		];

		$args['fulfillment_orders.deleted_at'] = [
			'condition' => 'custom',
			'value' => 'fulfillment_orders.deleted_at IS NULL',
		];

		$args['transaction.id'] = [
			'condition' => 'not_null',
		];

		if (isset($filters['picklist_id'])) {
			$args['fulfillment_orders.fulfillment_picklist_id'] = $filters['picklist_id'];
		}

		if (isset($filters['q'])) {
			$argsOrWhere['transaction.tracking_number'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['transaction.invoice_no'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['transaction.salesorder_no'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['transaction.tracking_number'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['transaction.store_name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
			$argsOrWhere['transaction.source_name'] = [
				'condition' => 'like',
				'value' => $filters['q']
			];
		}

		if (isset($filters['start_date'])) {
			$startDate = date('Y-m-d', strtotime($filters['start_date']));
			$args['fulfillment_picklists.created_at'] = [
				'condition' => 'custom',
				'value' => "
					DATE(fulfillment_picklists.created_at) >= '$startDate'
				"
			];
		}

		if (isset($filters['end_date'])) {
			$endDate = date('Y-m-d', strtotime($filters['end_date']));
			$args['fulfillment_picklists.created_at'] = [
				'condition' => 'custom',
				'value' => "
					DATE(fulfillment_picklists.created_at) <= '$endDate'
				"
			];
		}

		if (isset($filters['salesorder_ids'])) {
			$salesorderIds = implode(',', $filters['salesorder_ids']);
			$args['transaction.salesorder_id'] = [
				'condition' => 'custom',
				'value' => "
					transaction.salesorder_id IN ($salesorderIds)
				"
			];
		}

		$limit = 20;
		if (isset($filters['data']) && !empty($filters['data'])) {
			$limit = (int)$filters['data'];
		}

		$offset = 0;
		if (isset($filters['page']) && !empty($filters['page'])) {
			$offset = ((int)($filters['page'])-1) * $limit;
		}

		$select = [
			'fulfillment_orders.id as fulfillment_order_id',
			'transaction.id as transaction_id',
			'transaction_item.id as transaction_item_id',
			'transaction_item.price as transaction_item_price',
			'inventory_racks.code as rack_code',
			'transaction_item.qty as sales_qty',
			'fulfillment_picklists.code as picklist_code',
            'transaction_item.*',
			'product_details.*',
            'transaction.*',
			'fulfillment_orders.*',
			'inventory_products.*'
		];

		$orderValue = 'DESC';
		if (isset($filters['order_key']) && !empty($filters['order_key'])) {
			$orderKey = $filters['order_key'];
		}

		$orderKey = 'fulfillment_orders.id';
		if (isset($filters['order_value']) && !empty($filters['order_value'])) {
			$orderValue = $filters['order_value'];
		}

		$join = [
			'transaction' => [
				'value' =>'transaction_item.salesorder_id = transaction.salesorder_id' ,
				'type' => 'left'
			],
			'fulfillment_orders' => [
				'value' => 'transaction_item.salesorder_id = fulfillment_orders.salesorder_id',
				'type' => 'left'
			],
			'fulfillment_picklists' => [
				'value' => 'fulfillment_orders.fulfillment_picklist_id = fulfillment_picklists.id',
				'type' => 'left'
			],
			'product_details' => [
				'value' => 'transaction_item.item_code = product_details.sku_code',
				'type' => 'left'
			],
			'inventory_products' => [
				'value' => 'product_details.sku_code = inventory_products.product_detail_sku_code',
				'type' => 'left'
			],
			'inventory_boxes' => [
				'value' => 'inventory_products.id_inventory_box = inventory_boxes.id',
				'type' => 'left'
			],
			'inventory_rooms' => [
				'value' => 'inventory_boxes.id_inventory_room = inventory_rooms.id',
				'type' => 'left'
			],
			'inventory_racks' => [
				'value' => 'inventory_rooms.id_inventory_rack = inventory_racks.id',
				'type' => 'left'
			],
		];
		
        $transactionItems = $this->repository->find('transaction_item', $args, $argsOrWhere, $join, $select);

		$fullfilledOrders = [];

		foreach ($transactionItems as $transactionItem) {

			$transaction_item = [
				'transaction_item_id' => $transactionItem->transaction_item_id,
				'sku_code' => $transactionItem->sku_code,
				'sku_barcode' => $transactionItem->sku_barcode,
				'image_path' => $transactionItem->image_path,
				'variable' => $transactionItem->variable,
				'price' => $transactionItem->transaction_item_price,
				'stock' => $transactionItem->stock,
				'on_hand' => $transactionItem->on_hand,
				'on_order' => $transactionItem->on_order,
				'description' => $transactionItem->description,
				'unit' => $transactionItem->unit,
				'qty_in_base' => $transactionItem->qty_in_base,
				'disc' => $transactionItem->disc,
				'disc_amount' => $transactionItem->disc_amount,
				'tax_amount' => $transactionItem->tax_amount,
				'amount' => $transactionItem->amount,
				'qty' => $transactionItem->qty,
				'item_code' => $transactionItem->item_code,
				'item_name' => $transactionItem->item_name,
				'sell_price' => $transactionItem->sell_price,
				'original_price' => $transactionItem->original_price,
				'rate' => $transactionItem->rate,
				'tax_name' => $transactionItem->tax_name,
				'thumbnail' => $transactionItem->thumbnail,
				'weight_in_gram' => $transactionItem->weight_in_gram,
				'rack_code' => $transactionItem->rack_code,
				'salesorder_id' => $transactionItem->salesorder_id,
				'variable' => $transactionItem->variable,
				'unit' => $transactionItem->unit,
				'salesorder_no' => $transactionItem->salesorder_no,
				'location_name' => $transactionItem->location_name,
				'sales_qty' => $transactionItem->sales_qty,
			];

			$idx = array_search($transactionItem->transaction_id, array_column($fullfilledOrders, 'transaction_id'));
			if ($idx !== FALSE) {
				$idxItem = array_search($transactionItem->transaction_item_id, array_column($fullfilledOrders[$idx]['transaction_items'], 'transaction_item_id'));
				if ($idxItem === FALSE) {
					$fullfilledOrders[$idx]['transaction_items'][] = $transaction_item;
					$fullfilledOrders[$idx]['weight'] += $transactionItem->weight_in_gram;
				}
			} else {
				$fullfilledOrders[] = [
					'id' => $transactionItem->fulfillment_order_id,
					'transaction_id' => $transactionItem->transaction_id,
					'transaction_date' => $transactionItem->transaction_date,
					'salesorder_id' => $transactionItem->salesorder_id,
					'salesorder_no' => $transactionItem->salesorder_no,
					'customer_name' => $transactionItem->customer_name,
					'invoice_no' => $transactionItem->invoice_no,
					'sub_total' => $transactionItem->sub_total,
					'total_disc' => $transactionItem->total_disc,
					'grand_total' => $transactionItem->grand_total,
					'ref_no' => $transactionItem->ref_no,
					'shipping_cost' => $transactionItem->shipping_cost,
					'shipping_full_name' => $transactionItem->shipping_full_name,
					'shipping_phone' => $transactionItem->shipping_phone,
					'shipping_address' => $transactionItem->shipping_address,
					'shipping_area' => $transactionItem->shipping_area,
					'shipping_city' => $transactionItem->shipping_city,
					'shipping_province' => $transactionItem->shipping_province,
					'shipping_post_code' => $transactionItem->shipping_post_code,
					'shipping_country' => $transactionItem->shipping_country,
					'tracking_number' => $transactionItem->tracking_number,
					'courier' => $transactionItem->courier,
					'source_name' => $transactionItem->source_name,
					'store_name' => $transactionItem->store_name,
					'location_name' => $transactionItem->location_name,
					'payment_method' => $transactionItem->payment_method,
					'insurance_cost' => $transactionItem->insurance_cost,
					'weight' => $transactionItem->weight_in_gram,
					'picklist_code' => $transactionItem->picklist_code,
					'transaction_items' => [$transaction_item]
				];
			}
		}

		$this->delivery->data = $fullfilledOrders;
		return $this->delivery;
	}

	public function delete($id) {
		$existsOrder = $this->repository->findOne('fulfillment_orders', ['id' => $id]);
		if ( ! $existsOrder) {
            $this->delivery->addError(422, 'Transaction not found');
            return $this->delivery;
		}

		$result = $this->repository->update('fulfillment_orders', [
			'deleted_at' => date('Y-m-d H:i:s')
		], ['id' => $id]);

		if ($result) {
			$data = [
				'deleted' => true,
				'id' => $id,
			];
		} else {
			$data = [
				'deleted' => false,
				'id' => $id,
			];
		}

		$this->delivery->data = $data;
		return $this->delivery;
	}

	public function deleteBatch($ids) {
		$result = $this->repository->update('fulfillment_orders', [
			'deleted_at' => date('Y-m-d H:i:s')
		], ['id' => [
			'condition' => 'where_in',
			'value' => $ids,
		]]);

		$this->delivery->data = $result;		
		return $this->delivery;
	}
}