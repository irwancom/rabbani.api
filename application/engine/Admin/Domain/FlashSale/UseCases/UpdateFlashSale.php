<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\FlashSale\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\FlashSale\Contracts\FlashSaleRepositoryContract;

class UpdateFlashSale {
    private $saleRepo;

    private $flashsale;
    private $product;

    public function __construct(FlashSaleRepositoryContract $saleModel) {
        $this->saleRepo = $saleModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        
        if (!$this->getFlashsale($response))
            return $presenter->present($response);

        
        if ($this->validateData($response))
            return $presenter->present($response);
        
        $data = $response->incomingData;
        
        $result = $this->saleRepo->update($this->flashsale, $data);
        
        if (!$result) return $response->addError('model', $result['message']);

        $flashsale = $this->saleRepo->detailBy('id_flash_sale', $this->flashsale->id_flash_sale);

        
        $response->data = $result ? $flashsale : null;
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
            $response->addError('model', 'error.flashsale.id_auth.is_required');
            return false;
        }

        if (key_exists('discount_type', $data)) {
            if (!in_array($data['discount_type'], [1,2])) {
                $response->addError('discount_type', 'error.discount.discount_type.is_invalid');
            }
        }

        
        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Check Product
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function checkProductDetail(Response $response) {
        if (!isset($response->incomingData['id_product_detail']))
            return true;

        $result = $this->productRepo->detailItemByFields([
            'id_product_detail' => (int)$response->incomingData['id_product_detail'],
            'deleted_at' => null
        ]);

        if ($result)
            return $this->product = $result;

        $response->addError('flashsale', 'error.flashsale.global.product_not_found');
        return false;
    }

    /**
     * Valdiate data
     * @param \Andri\Engine\Shared\Response $response
     */
    public function validateData(Response $response) {
        $data = [];
        foreach($this->flashsale as $key => $val) {
            if (key_exists($key, $response->incomingData)) {
                $data[$key] = $response->incomingData[$key];
            } else {
                $data[$key] = $this->flashsale->{$key};
            }
        }

        $response->incomingData = $data;

        $this->validateRangeDate($response);
        $this->validateQuantity($response);
        
        if (!$response->hasError()) return false;
        return true;
    }

    /**
     * Validataion Quantity
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return bool
     */
    public function validateQuantity($response) {
        $minQty = (int)$response->incomingData['min_qty'];
        $maxQty = (int)$response->incomingData['max_qty'];

        if ($maxQty >= $minQty) return true;
        
        $response->addError('flashsale', 'error.flashsale.global.invalid_quantity');
        return false;
    }

    /**
     * Validataion Range date
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return bool
     */
    public function validateRangeDate($response) {
        $startTime = $response->incomingData['start_time'];
        $endTime = $response->incomingData['end_time'];

        if (strtotime($startTime) <= strtotime($endTime)) 
            return true;

        $response->addError('flashsale', 'error.flashsale.global.start_time_gt_end_time');
        return false;
    }


    /**
     * Get flashsale
     * @param \Andri\Engine\Shared\Response $response
     */
    public function getFlashsale($response) {
        if (!isset($response->incomingData['id_flash_sale'])) {
            $response->addError('id_flash_sale', 'error.flashsale.id_flash_sale.is_invalid_or_required');
            return false;
        }

        $result = $this->saleRepo->detailByFields([
                    'id_auth'       => $response->incomingData['id_auth'],
                    'id_flash_sale' => $response->incomingData['id_flash_sale']
                ]);
        if ($result)
            return $this->flashsale = $result;

        $response->addError('flashsale', 'error.flashsale.global.not_found');
        return false;
    }


    



}