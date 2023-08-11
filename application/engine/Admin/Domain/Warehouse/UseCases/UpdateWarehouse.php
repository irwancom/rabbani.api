<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 8th, 2020
 */

namespace Andri\Engine\Admin\Domain\Warehouse\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Warehouse\Contracts\WarehouseRepositoryContract;

class UpdateWarehouse {

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
        $warehouse = $this->checkWarehouse((int)$data['id_auth'], (int)$data['id_warehouse']);
        if (!$warehouse) {
            $response->addError('warehouse', 'error.warehouse.not_found');
            return $presenter->present($response); 
        }

        $result = $this->warehouseRepo->update($warehouse, $data);
        if ($result) $result = $warehouse->id_warehouse;
        
        $warehouse = $this->warehouseRepo->detailBy('id_warehouse', $warehouse->id_warehouse);
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

        if (!key_exists('id_warehouse', $data) || strlen("{$data['id_warehouse']}") === 0 ) {
            $response->addError('id_warehouse', 'error.id_warehouse.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Check is warehouse already stored
     * 
     * @param int $id_user
     * @param int $id_warehouse
     * @return object
     */
    public function checkWarehouse(int $id_auth, int $id_warehouse) {

        $fields = [
            'id_auth'       => $id_auth,
            'id_warehouse'   => $id_warehouse
        ];

        $warehouse = $this->warehouseRepo->detailByFields($fields);
        return $warehouse;
    }

}