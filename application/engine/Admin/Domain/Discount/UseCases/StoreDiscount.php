<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\Discount\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Discount\Contracts\DiscountRepositoryContract;

class StoreDiscount {
    private $discountRepo;

    public function __construct(DiscountRepositoryContract $discountModel) {
        $this->discountRepo = $discountModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        if (!$this->validateQuantity($response))
            return $presenter->present($response);

        if (!$this->validateRangeDate($response))
            return $presenter->present($response);

        $data = $response->incomingData;
        $result = $this->discountRepo->store($data);
        if (!$result) return $response->addError('discount', 'error.discount.global.failed_to_save_discount' );
        
        $discount = $this->discountRepo->detailBy('id_discount', $result);
        
        $response->data = $result ? $discount : null;
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
            $response->addError('model', 'error.discount.id_auth.is_required');
            return false;
        }
        
        if (!key_exists('title', $data) || strlen($data['title']) === 0) {
            $response->addError('title', 'error.discount.title.is_required');
        }

        if (!key_exists('start_time', $data) || strlen($data['start_time']) === 0) {
            $response->addError('start_time', 'error.discount.start_time.is_required');
        }

        if (!key_exists('end_time', $data) || strlen($data['end_time']) === 0) {
            $response->addError('end_time', 'error.discount.end_time.is_required');
        }

        if (!key_exists('min_qty', $data) || strlen($data['min_qty']) === 0) {
            $response->addError('min_qty', 'error.discount.min_qty.is_required');
        } else {
            if ((int)$data['min_qty'] < 1) $data['min_qty'] = 1; 
        }

        if (!key_exists('max_qty', $data) || strlen($data['max_qty']) === 0) {
            $response->addError('max_qty', 'error.discount.max_qty.is_required');
        }

        if (!key_exists('discount_type', $data) || strlen($data['discount_type']) === 0) {
            $response->addError('discount_type', 'error.discount.discount_type.is_required');
        } else {
            if (!in_array($data['discount_type'], [1,2])) {
                $response->addError('discount_type', 'error.discount.discount_type.is_invalid');
            }
        }

        if (!key_exists('discount_value', $data) || (float)$data['discount_value'] < 1) {
            $response->addError('discount_value', 'error.discount.discount_invalid.is_invalid');
        } else {
            if ((float)$data['discount_value'] < 0)  $data['discount_value'] = 0;
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
        
        $response->addError('discount', 'error.discount.global.invalid_quantity');
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

}