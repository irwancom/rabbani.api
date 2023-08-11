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

class UpdateProduct {
    private $productRepo;
    private $categoryRepo;

    public function __construct(
        ProductRepositoryContract $productModel,
        CategoryRepositoryContract $categoryModel
    ) {
        $this->productRepo = $productModel;
        $this->categoryRepo = $categoryModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $this->reformat($response);
        $data = $response->incomingData;

        $product = $this->getProduct((int)$data['id_auth'], (int)$data['id_product']);
        if (!$product) {
            $response->addError('product', 'error.product.global.not_found');
            return $presenter->present($response);
        }

        $result = $this->productRepo->update($product, $data);
        if (!$result) {
            $response->addError('model', 'error.product.global.failed_to_add_new_product');
            return $presenter->present($response);
        }
        
        $product = $this->productRepo->detailByFields(['id_product' => $product->id_product ]);
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

        if (!isset($data['id_auth'])) {
            $response->addError('model', 'error.product.id_auth.is_required');
            return false;
        }

        if (key_exists('product_name', $data) && empty($data['product_name'])) {
            unset($data['product_name']);
        }

        if (key_exists('sku', $data) && empty($data['sku'])) {
            unset($data['sku']);
        }

        if (key_exists('id_category', $data)) {
            if (strlen("{$data['id_category']}") === 0) {
                unset($data['id_category']);
            } else {
                if (!$this->getCategory($response)) return false;    
            }
        }
        
        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Get Category
     * 
     * @param Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function getCategory(Response $response) {
        $data = $response->incomingData;
        
        $result = $this->categoryRepo->detailByFields([
            'id_auth' => $data['id_auth'],
            'id_category' => $data['id_category']
        ]);

        if ($result) return $result;
        $response->addError('category', 'error.category.global.not_found');
        return false;
    }


    /**
     * Check Product
     */
    public function getProduct(int $id_auth, int $id_product) {
        $product = $this->productRepo->detailByFields([
                    'id_auth' => $id_auth,
                    'id_product' => $id_product
                ]);
        return $product;
    }



    /**
     * Reformat Data
     * 
     * @param Andri\Engine\Shared\Response $response
     * @return void
     */
    public function reformat(Response $response) {
        $data = $response->incomingData;
        
        if (isset($data['sku'])) {
            $data['sku'] = strtoupper($data['sku']);
        }
        $response->incomingData = $data;
    }

}