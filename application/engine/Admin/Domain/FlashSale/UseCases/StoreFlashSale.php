<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\FlashSale\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\FlashSale\Contracts\FlashSaleRepositoryContract;
use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;

class StoreFlashSale {
    private $saleRepo;
    private $productRepo;

    public function __construct(
        FlashSaleRepositoryContract $saleModel,
        ProductRepositoryContract $productModel
    ) {
        $this->saleRepo = $saleModel;
        $this->productRepo = $productModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        if(!$this->validateQuantity($response))
            return $presenter->present($response);

        if (!$this->validateRangeDate($response))
            return $presenter->present($response);
        
        

        $data = $response->incomingData;

        if (!$this->checkProductDetail($response))
            return $presenter->present($response);

        $result = $this->saleRepo->store($data);
        if (!$result) return $response->addError('model', $result['message']);
        
        $sale = $this->saleRepo->detailBy('id_flash_sale', $result);
        $sale = (array)$sale;
        unset($sale['status']);

        $response->data = $result ? $sale : null;
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
        
        if (!key_exists('id_product_detail', $data) || strlen($data['id_product_detail']) === 0) {
            $response->addError('id_product_detail', 'error.flashsale.id_product_detail.is_required');
        }

        if (!key_exists('discount_type', $data) || strlen($data['discount_type']) === 0) {
            $response->addError('discount_type', 'error.flashsale.discount_type.is_required');
        } else {
            if (!in_array($data['discount_type'], [1,2])) {
                $response->addError('discount_type', 'error.flashsale.discount_type.invalid_discount_type');
            }
        }

        if (!key_exists('discount_value', $data) || strlen($data['discount_value']) === 0) {
            $response->addError('discount_value', 'error.flashsale.discount_value.is_required');
        } else {
            if ((float)$data['discount_value'] < 0) {
                $data['discount_value'] = 0;
            }
        }
        

        if (!key_exists('min_qty', $data) || strlen($data['min_qty']) === 0) {
            $response->addError('min_qty', 'error.flashsale.min_qty.is_required');
        }

        if (!key_exists('max_qty', $data) || strlen($data['max_qty']) === 0) {
            $response->addError('max_qty', 'error.flashsale.max_qty.is_required');
        }

        if (!key_exists('end_time', $data) || strlen($data['end_time']) === 0) {
            $response->addError('end_time', 'error.flashsale.end_time.is_required');
        }

        $response->incomingData = $data;

        if ($response->hasError()) return false;
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
     * Check Product
     * 
     * @param int $product_id
     * @return bool
     */
    public function checkProductDetail(Response $response) {
        $result = $this->productRepo->detailItemByFields([
            'id_product_detail' => (int)$response->incomingData['id_product_detail'],
            'deleted_at' => null
        ]);

        if ($result)
            return $this->product = $result;

        $response->addError('flashsale', 'error.flashsale.global.product_not_found');
    }

}