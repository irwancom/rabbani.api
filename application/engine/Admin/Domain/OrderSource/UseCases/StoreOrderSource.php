<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\OrderSource\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\OrderSource\Contracts\OrderSourceRepositoryContract;

class StoreOrderSource {
    private $orderSourceRepo;

    public function __construct(OrderSourceRepositoryContract $orderSourceModel) {
        $this->orderSourceRepo = $orderSourceModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $this->reformat($response);

        $data = $response->incomingData;
        
        $result = $this->orderSourceRepo->store($data);
        
        if (!$result) return $response->addError('model', $result['message']);
        
        $orderSource = $this->orderSourceRepo->detailBy('id_order_source', $result);
        $response->data = $result ? $orderSource : null;
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

        if (!key_exists('name', $data) || strlen($data['name']) === 0) {
            $response->addError('order_source', 'error.order_source.name.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Reformat Property Value
     */
    public function reformat(Response $response) {
        $name = $response->incomingData['name'];
        $response->incomingData['name'] = $name;
    }


}