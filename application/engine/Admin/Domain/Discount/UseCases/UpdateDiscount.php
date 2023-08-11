<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\Discount\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Discount\Contracts\DiscountRepositoryContract;

class UpdateDiscount {
    private $discountRepo;

    private $discount;

    public function __construct(DiscountRepositoryContract $discountModel) {
        $this->discountRepo = $discountModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        if (!$this->getDiscount($response))
            return $presenter->present($response);

        if (!$this->validateData($response))
            return $presenter->present($response);
        
        $data = $response->incomingData;
        // $result = $this->discountRepo->update($this->discount, $data);
        $result = true;
        if (!$result) return $response->addError('discount', 'error.discount.global.failed_to_save_discount');
        
        $discount = $this->discountRepo->detailBy('id_discount', $this->discount->id_discount);
        $response->data = $result ? $discount : null;
        $presenter->present($response);
    }


    /**
     * Valdiate data
     * @param \Andri\Engine\Shared\Response $response
     */
    public function validateData(Response $response) {
        $data = [];
        foreach($this->discount as $key => $val) {
            if (key_exists($key, $response->incomingData)) {
                $data[$key] = $response->incomingData[$key];
            } else {
                $data[$key] = $this->discount->{$key};
            }
        }

        $response->incomingData = $data;

        $this->validateRangeDate($response);
        $this->validateQuantity($response);
        
        if (!$response->hasError()) return false;
        return true;
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
        
        if (key_exists('discount_type', $data)) {
            if (!in_array($data['discount_type'], ["1","2"])) {
                $response->addError('discount_type', 'error.discount.discount_type.is_invalid');
            }
        }

        if (key_exists('discount_value', $data)) {
            if ((float)$data['discount_value'] < 1) 
                $data['discount_value'] = 0;
        }

        $response->incomingData = $data;
        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Get flashsale
     * @param \Andri\Engine\Shared\Response $response
     */
    public function getDiscount($response) {
        if (!isset($response->incomingData['id_discount'])) {
            $response->addError('id_discount', 'error.discount.id_discount.is_invalid_or_required');
            return false;
        }

        $result = $this->discountRepo->detailByFields([
                    'id_auth'       => $response->incomingData['id_auth'],
                    'id_discount'   => $response->incomingData['id_discount']
                ]);
        if ($result)
            return $this->discount = $result;

        $response->addError('discount', 'error.discount.global.not_found');
        return false;
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