<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\Warehouse\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Warehouse\Contracts\WarehouseRepositoryContract;

class StoreWarehouse {
    private $warehouseRepo;

    public function __construct(WarehouseRepositoryContract $warehouseModel) {
        $this->warehouseRepo = $warehouseModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $data = $response->incomingData;
        
        $result = $this->warehouseRepo->store($data);
        
        if (!$result) return $response->addError('model', $result['message']);
        
        $warehouse = $this->warehouseRepo->detailBy('id_warehouse', $result);
        $response->data = $result ? $warehouse : null;
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
            $response->addError('model', 'error.id_auth.is_required');
            return false;
        }

        if (!key_exists('area_id', $data) || strlen($data['area_id']) === 0) {
            $response->addError('warehouse_area_id', 'error.warehouse.area_id.is_required');
        }

        if (!key_exists('name', $data) || strlen($data['name']) === 0) {
            $response->addError('warehouse_name', 'error.warehouse.name.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }


}