<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 13th, 2020
 */

namespace Andri\Engine\Shared\Helpers;

use Andri\Engine\Client\Domain\Order\Contracts\ProductRepositoryContract;
use Andri\Engine\Client\Domain\Order\Contracts\DiscountRepositoryContract;
use Andri\Engine\Client\Domain\Order\Contracts\FlashsaleRepositoryContract;

class Calculator {

    private $discount;
    private $sale;
    private $data;
    private $product;

    public function __construct(
        array $data, 
        $product,
        DiscountRepositoryContract $discount, 
        FlashsaleRepositoryContract $sale
    ) {
        $this->data = $data;
        $this->discount = $discount;
        $this->sale = $sale;
        $this->product = $product;
    }

    /**
     * Discount Calculator
     */
    public static function discount($data, $product, $discount, $sale) {
        $self = new self($data, $product, $discount, $sale);
        return $self->checkDiscount();
    }


    
    public function checkDiscount() {
        $price = (float)$this->product->price * (int)$this->data['qty'];

        if ($discount = $this->getFlashsale()) {
            $price = $this->calculate($sale);
            return [
                'type' => $discount->discount_type,
                'value' => $discount->discount_value,
                'source' => 'flashsale',
                'price' => $price
            ];
        }

        if ($discount = $this->getDiscount()) {
            $price = $this->calculate($discount);
            return [
                'type'      => $discount->discount_type,
                'value'     => $discount->discount_value,
                'source'    => 'product_discount',
                'price'     => $price
            ];
        }

        return [
            'type' => null,
            'value' => null,
            'source' => null,
            'price' => $price
        ];
        
    }


    public function calculate($result) {
        $qty    = (int)$this->data['qty'];
        $price  = $this->product->price * $qty;

        if ($qty < $result->min_qty && $qty > $result->max_qty) 
            return $price;

        switch((int)$result->discount_type) {
            case 1:
                $price -= (float)$result->discount_value;
                return $price > 0 ? $price : 0;
                
            case 2:
                $discount = $price * ((float)$result->discount_value/100);
                return $price - $discount;
        }
        
        return $price;
    }


    /**
     * Get Discount by Product
     * 
     * @return object|null
     */
    public function getDiscount() {
        $date = date('Y-m-d h:i:s');
        $condition = [
            'id_product_detail' => $this->data['id_product_detail'],
            'start_time >'      => $date
        ];
        
        return $this->discount->detailItem($condition);        
    }


    /**
     * Get Flashsale by Product
     * 
     * @return object|null
     */
    public function getFlashsale() {
        $date = date('Y-m-d h:i:s');
        $condition = [
            'id_product_detail' => $this->data['id_product_detail'],
            'start_time >'      =>  $date
        ];
        return $this->sale->detail($condition);
    }
}