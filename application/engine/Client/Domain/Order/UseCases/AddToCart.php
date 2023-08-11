<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 13th, 2020
 */

namespace Andri\Engine\Client\Domain\Order\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Client\Domain\Order\Contracts\CartRepositoryContract;
use Andri\Engine\Client\Domain\Order\Contracts\ProductRepositoryContract;
use Andri\Engine\Client\Domain\Order\Contracts\DiscountRepositoryContract;
use Andri\Engine\Client\Domain\Order\Contracts\FlashsaleRepositoryContract;

use Andri\Engine\Shared\Helpers\Calculator;

class AddToCart {
    private $cartRepo;
    private $productRepo;
    private $discountRepo;
    private $saleRepo;

    private $productDetail;
    private $cart;

    public function __construct( 
        CartRepositoryContract $cartModel, 
        ProductRepositoryContract $productModel, 
        DiscountRepositoryContract $discountModel, 
        FlashsaleRepositoryContract $saleModel
    ) {
        $this->cartRepo         = $cartModel;
        $this->productRepo      = $productModel;
        $this->discountRepo     = $discountModel;
        $this->saleRepo         = $saleModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        if (!$this->getProduct($response))
            return $presenter->present($response);

        if ((int)$response->incomingData['qty'] > $this->productDetail->stock) {
            $response->addError('qty', 'error.order.qty.out_of_stock');
            return $presenter->present($response);
        }

        $this->getActiveCart($response);

        $item = [
            'id_product_detail' => $this->productDetail->id_product_detail,
            'id_auth_user'      => null,
            'discount_type'     => null,
            'discount_source'   => null,
            'discount_value'    => 0,
            'price'             => $this->productDetail->price,
            'qty'               => $response->incomingData['qty'],
            'total'             => 0
        ];

        if (isset($data['id_auth_user'])) $item['id_auth_user'] = $data['id_auth_user'];
        $discount = Calculator::discount(
                        $response->incomingData, 
                        $this->productDetail, 
                        $this->discountRepo, 
                        $this->saleRepo
                    );

        if ($discount) {
            $item['discount_source']      = $discount['source'];
            $item['discount_type']        = $discount['type'];
            $item['discount_value']       = $discount['value'];
            $item['total']                = $discount['price'];
        }
        

        if ($this->cart) {
            $result = $this->cartRepo->pushItem($this->cart, $item);
            $item['id_cart'] = $this->cart->id_cart;
        } else {
            $item['id_cart'] = $this->createId();
            $result = $this->cartRepo->store($item);
        }

        if (!$result) {
            $response->addError('cart', 'error.cart.global.failed_to_add_to_cart');
            return $presenter->present($response);
        }

        $response->data = ['status' => 'success', 'id_cart' => $item['id_cart']];
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

        if (!key_exists('id_product_detail', $data) || empty($data['id_product_detail'])) {
            $response->addError('id_product_detail', 'error.cart.id_product_detail.is_required');
        }

        if (!key_exists('id_auth_user', $data) || empty($data['id_auth_user'])) {
            $response->addError('id_auth_user', 'error.cart.id_auth_user.is_required');
        }

        if (!key_exists('qty', $data)) {
            $response->addError('qty', 'error.cart.qty.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Get Active Cart
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return object|null
     */
    public function getActiveCart(Response $response) {
        $data = $response->incomingData;
        
        if (!isset($data['id_cart'])) return false;
        
        $cart = $this->cartRepo->detail('id_cart', $data['id_cart']);
        if (!$cart) return false;
        
        $id = $data['id_product_detail'];
        $item = (array)json_decode($cart->items);
        
        if (isset($item[$id])) {
            $data['qty'] += (int)$item[$id]->qty;
        }
        $response->incomingData = $data;
        $this->cart = $cart;
    }
    

    /**
     * Ceate Uniqu ID for cart
     */
    public function createId() {
        return \Ramsey\Uuid\Uuid::uuid4()->toString();
    }


    /**
     * Get Product
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return bool
     */
    public function getProduct(Response $response) {
        $result = $this->productRepo->detailItem(
            [
                'id_product_detail' => $response->incomingData['id_product_detail']
            ]
        );

        if ($result) return $this->productDetail = $result;
        $response->addError('product', 'error.cart.product.not_found');
        return false;
    }

}