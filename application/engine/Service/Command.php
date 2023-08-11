<?php
namespace Service;

use Service\Entity;
use Service\Calculator;

class Command {

	private $repository;
	private $validator;
	private $entity;

	public function __construct ($repository) {
		$this->repository = $repository;
		$this->validator = new Validator($repository);
		$this->entity = new Entity;
	}

	public function sendInvoice () {
		$currentDate = date('Y-m-d H:i:s');
		$args = [
			'DATE(next_invoice_at)' => $currentDate
		];
		$expiredItems = $this->repository->find('service_user_collections', $args);
		$results = [];
		foreach ($expiredItems as $item) {
			$args = [
				'id_auth_user' => $item->id_auth_user
			];
			$auth = $this->repository->findOne('auth_user', $args);
			$auth = $this->validator->validateAuth($auth->secret);
			$handler = new Handler($this->repository, $auth->data);
			if ($item->service_product_type == Entity::TYPE_HOSTING) {
				$payload = [
					'code' => $item->service_product_code,
					'period_code' => $item->payment_period,
					'payment_method' => $item->payment_method_code
				];
				$result = $handler->createHostingTransaction($payload, true, $item->expired_at, $item->id);
				$results[] = $result;
			} else if ($item->service_product_type == Entity::TYPE_DOMAIN) {
				$payload = [
					'sld' => $item->sld,
					'tld' => $item->tld,
					'payment_method' => $item->payment_method_code
				];
				$result = $handler->createDomainTransaction($payload, true, $item->expired_at, $item->id);
				$results[] = $result;
			}
		}
	}

	public function suspendCollection () {
		// find suspended => terminate
		$args = [
			'status' => Entity::STATUS_SUSPEND,
			sprintf('DATEDIFF("%s",expired_at) >=', date('Y-m-d H:i:s'), ) => 7
		];
		$update = [
			'status' => Entity::STATUS_TERMINATE
		];
		$collections = $this->repository->find('service_user_collections', $args);
		foreach ($collections as $collection) {
			$this->repository->update('service_user_collections', $update, ['id' => $collection->id]);
		}

		// find active => suspend

		$args = [
			'status' => Entity::STATUS_ACTIVE,
			'expired_at <=' => date('Y-m-d H:i:s')
		];
		$update = [
			'status' => Entity::STATUS_SUSPEND
		];
		$collections = $this->repository->find('service_user_collections', $args);
		foreach ($collections as $collection) {
			$this->repository->update('service_user_collections', $update, ['id' => $collection->id]);
		}
	}

	public function cancelTransaction () {
		$args = [
			'payment_status' => Calculator::TYPE_PAYMENT_STATUS_PENDING,
			'expired_at <=' => date('Y-m-d H:i:s')
		];
		$update = [
			'payment_status' => Calculator::TYPE_PAYMENT_STATUS_CANCELED
		];
		$transactions = $this->repository->find('service_transactions', $args);
		foreach ($transactions as $transaction) {
			$this->repository->update('service_transactions', $update, ['id' => $transaction->id]);
		}

	}

	public function generateUserServiceInvoice () {
		$args = [];
		$authUsers = $this->repository->find('auth_user', $args);
		foreach ($authUsers as $authUser) {
			$argsInvoice = [
				'id_auth_user' => $authUser->id_auth_user,
				'MONTH(created_at)' => date('m') 
			];
			$existsInvoiceThisMonth = $this->repository->find('service_invoices', $argsInvoice);
			if (!empty($existsInvoiceThisMonth)) {
				continue;
			}
			$services = $this->entity->formatAuth($authUser->services);
			$totalAmount = 0;
			$details = [];
			foreach ($services as $service) {
				if ($service['is_active']) {
					$totalAmount += $service['fee'];
					$details[] = [
						'service_type' => $service['type'],
						'amount' => $service['fee'],
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s')
					];
				}
			}

			if (!empty($details)) {
				$dataInvoice = [
					'id_auth_user' => $authUser->id_auth_user,
					'invoice_no' => sprintf('INV-%s%s', date('YmdHi'), time()),
					'amount' => $totalAmount,
					'payment_status' => Calculator::TYPE_PAYMENT_STATUS_WAITING,
					'expired_at' => Entity::modifyDate('7D', date('Y-m-d H:i:s'), 'add'),
					'created_at' => date('Y-m-d H:i:s'),
					'updated_at' => date('Y-m-d H:i:s')
				];
				$invoiceId = $this->repository->insert('service_invoices', $dataInvoice);
				foreach ($details as $detail) {
					$detail['service_invoice_id'] = $invoiceId;
					$detailInvoiceId = $this->repository->insert('service_invoice_details', $detail);
				}
			}
		}
	}

}