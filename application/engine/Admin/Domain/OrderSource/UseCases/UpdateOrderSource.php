<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 8th, 2020
 */

namespace Andri\Engine\Admin\Domain\OrderSource\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\OrderSource\Contracts\OrderSourceRepositoryContract;

class UpdateOrderSource {

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
        $orderSource = $this->checkOrderSource((int)$data['id_order_source']);
        if (!$orderSource) {
            $response->addError('order_source', 'error.order_source.not_found');
            return $presenter->present($response); 
        }

        $result = $this->orderSourceRepo->update($orderSource, $data);
        if ($result) $result = $orderSource->id_order_source;
        
        $orderSource = $this->orderSourceRepo->detailBy('id_order_source', $orderSource->id_order_source);
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

        if (!key_exists('id_order_source', $data) || strlen("{$data['id_order_source']}") === 0 ) {
            $response->addError('id_order_source', 'error.id_order_source.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }




    /**
     * Check is order source already stored
     * 
     * @param int $id_order_source
     * @return object
     */
    public function checkOrderSource(int $id_order_source) {

        $fields = [
            'id_order_source'   => $id_order_source
        ];

        $orderSource = $this->orderSourceRepo->detailByFields($fields);
        return $orderSource;
    }

    

    /**
     * Reformat Property Value
     */
    public function reformat(Response $response) {
        if (!isset($response->incomingData['name']))
            return false;

        $name = $response->incomingData['name'];
        $response->incomingData['name'] = $name;
    }
}