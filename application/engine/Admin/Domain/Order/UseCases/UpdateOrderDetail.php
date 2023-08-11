<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 13th, 2020
 */

namespace Andri\Engine\Admin\Domain\Order\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Order\Contracts\OrderRepositoryContract;

class UpdateOrderDetail {
    private $orderRepo;
    private $order;
    private $detail;
    
    public function __construct(OrderRepositoryContract $orderModel) {
        $this->orderRepo = $orderModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);


        if (!$result = $this->getOrder((int)$data['id_auth'], $data['order_code'])) {
            $response->addError('order', 'error.order.global.not_found');
            return $presenter->present($response);
        }

        if (!$result = $this->getOrderDetail((int)$data['id_order_detail']) ) {
            $response->addError('order', 'error.order.order_detail.not_found');
            return $presenter->present($response);
        }

        $data = $response->incomingData;

        $result = $this->orderRepo->updateDetail($this->detail, $data);
        if (!$result) {
            $response->addError('model', 'error.order.global.failed_to_update_order');
            return $presenter->present($response);
        }
        
        $detail = $this->getOrderDetail((int)$this->detail->id_order_detail);
        $order  = $this->getOrder((int)$this->order->id_auth, $this->order->order_code);
        
        $response->data = ['detail' => $detail, 'order' => $order];
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
            $response->addError('model', 'error.product.id_auth.is_required');
            return false;
        }

        if (!key_exists('order_code', $data) || empty($data['order_code'])) {
            $response->addError('order', 'error.order.order_code.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Order Detail
     */
    public function getOrderDetail(int $id) {
        $this->detail = $this->orderRepo->detailItemByFields([
            'id_order_detail' => $id,
            'order_code' => $this->order->order_code
        ]);

        return $this->detail;
    }


    /**
     * Check Order
     */
    public function getOrder(int $id_auth, $order_code) {
        $this->order = $this->orderRepo->detailByFields([
                    'id_auth' => $id_auth,
                    'order_code' => $order_code
                ]);

        return $this->order;
    }

}