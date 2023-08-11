<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 10th, 2020
 */

namespace Andri\Engine\Admin\Domain\Product\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;

use Andri\Engine\Admin\Domain\Product\Entities\ProductDetail as ProductDetailEntity;
use Picqer\Barcode\BarcodeGeneratorPNG as BarcodePNG;

class StoreProductDetail {
    private $productRepo;

    private $product;

    public function __construct(ProductRepositoryContract $productModel) {
        $this->productRepo = $productModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response))
            return $presenter->present($response);

        if (!$this->getProduct($response))
            return $presenter->present($response);

        ProductDetailEntity::reformat((array)$this->product, $response);

        $data = $response->incomingData;
        $result = $this->productRepo->storeDetail($data);
        
        if (!$result)
            return $response->addError('product', 'error.product.global.failed_to_store_new_product');
        
        $product = $this->productRepo->detailItemByFields(['id_product_detail' => $result]);
        $response->data = $result ? $product : null;
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

        if (!key_exists('id_product', $data) || empty($data['id_product'])) {
            $response->addError('id_product', 'error.product_detail.id_product.is_required');
            return false;
        }
        
        if (!key_exists('sku_code', $data) || empty("{$data['sku_code']}")) {
            $response->addError('sku_code', 'error.product.sku_code.is_required');
        }

        if (!key_exists('price', $data) || strlen("{$data['price']}") === 0) {
            $response->addError('price', 'error.product.price.is_required');
        } 

        if (!key_exists('stock', $data) || strlen("{$data['stock']}") === 0) {
            $response->addError('stock', 'error.product.stock.is_required');
        } 

        $existsSku = $this->productRepo->detailItemByFields(['sku_code' => $data['sku_code']]);
        if (!empty($existsSku)) {
            $response->addError('sku_code', 'error.product.sku_code.exists');
        }
        
        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Get Product
     * 
     * @param Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function getProduct(Response $response) {
        $data = $response->incomingData;
        
        $result = $this->productRepo->detailByFields([
            'id_auth'    => $data['id_auth'],
            'id_product' => $data['id_product']
        ]);

        if ($result)
            return $this->product = $result;
        
        $response->addError('product', 'error.product.global.not_found');
        return false;
    }

}
