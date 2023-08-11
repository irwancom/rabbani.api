<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\Product\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;
use Andri\Engine\Admin\Domain\Category\Contracts\CategoryRepositoryContract;

use Andri\Engine\Admin\Domain\Product\Entities\ProductDetail as ProductDetailEntity;

class UpdateProductDetail {
    private $productRepo;
    private $categoryRepo;

    public function __construct(ProductRepositoryContract $productModel) {
        $this->productRepo = $productModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $product = $this->getProduct((int)$data['id_auth'], (int)$data['id_product']);
        if (!$product) {
            $response->addError('product', 'error.product.global.not_found');
            return $presenter->present($response);
        }

        $detail = $this->getProductDetail((int)$data['id_product_detail']);
        if (!$detail) {
            $response->addError('product_detail', 'error.product_detail.global.not_found');
            return $presenter->present($response);
        }

        ProductDetailEntity::reformat((array)$product, $response);
        $data = $response->incomingData;
       
        $result = $this->productRepo->updateDetail($detail, $data);
        if (!$result) return $response->addError('model', $result['message']);
        
        $detail = $this->productRepo->detailItemByFields(['id_product_detail' => $detail->id_product_detail ]);
        $response->data = $result ? $detail : null;
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
            $response->addError('model', 'error.product.id_auth.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Check Product
     */
    public function getProduct(int $id_auth, int $id_product) {
        $product = $this->productRepo->detailByFields([
                    'id_auth' => $id_auth,
                    'id_product' => $id_product,
                    
                ]);
        return $product;
    }


     /**
     * Check Product Detail
     */
    public function getProductDetail(int $id_product_detail) {
        $detail = $this->productRepo->detailItemByFields([
                    'id_product_detail' => $id_product_detail,
                    'deleted_at' => null
                ]);
        return $detail;
    }

}