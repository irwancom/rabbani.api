<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 13th, 2020
 */

namespace Andri\Engine\Admin\Domain\Order\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Order\Contracts\OrderRepositoryContract;

class UpdateOrder {
    private $orderRepo;
    
    public function __construct(OrderRepositoryContract $orderModel) {
        $this->orderRepo = $orderModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $this->reformat($response);
        $data = $response->incomingData;

        $args = [
            'id_auth' => $data['id_auth'],
            'invoice_number' => $data['invoice_number']
        ];

        $result = $this->orderRepo->update($args, $data);
        if (!$result) {
            $response->addError('model', 'error.order.global.failed_to_update_order');
            return $presenter->present($response);
        }
        
        $order = $this->orderRepo->detailByFields(['invoice_number' => $data['invoice_number']]);
        $response->data = $order;
        $presenter->present($response);
    }


    /**
     * Validation
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return bool
     */
    public function validation(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_auth'])) {
            $response->addError('model', 'error.order.id_auth.is_required');
            return false;
        }

        if (!key_exists('invoice_number', $data) || empty($data['invoice_number'])) {
            $response->addError('order', 'error.order.invoice_number.is_required');
            return false;
        }

        if (empty($order = $this->getOrder((int)$data['id_auth'], (int)$data['invoice_number']))) {
            $response->addError('order', 'error.order.not_found');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Check Order
     */
    public function getOrder(int $id_auth, $invoiceNumber) {
        $order = $this->orderRepo->detailByFields([
                    'id_auth' => $id_auth,
                    'invoice_number' => $invoiceNumber
                ]);
        return $order;
    }


    /**
     * Reformat Data
     * 
     * @param Andri\Engine\Shared\Response $response
     * @return void
     */
    public function reformat(Response $response) {
        $data = $response->incomingData;
        
       
        $response->incomingData = $data;
    }

}