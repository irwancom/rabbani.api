<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 10th, 2020
 */

namespace Andri\Engine\Admin\Domain\Product\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;
use Andri\Engine\Admin\Domain\Category\Contracts\CategoryRepositoryContract;

class StoreProduct {
    private $productRepo;

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

        if (!$this->getCategory($response))
            return $presenter->present($response);

        $this->reformat($response);

        $data = $response->incomingData;
        $result = $this->productRepo->store($data);
        
        if (!$result)
            return $response->addError('product', 'error.product.global.failed_to_store_new_product');

        $product = $this->productRepo->detailByFields(['id_product' => $result]);
        $slug = $product->product_name.'-'.$product->id_product;
        $nextAction = $this->productRepo->update($product, ['product_slug' => slugify($slug)]);

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
            $response->addError('model', 'error.newsletter.id_auth.is_required');
            return false;
        }
        
        if (!key_exists('product_name', $data) || empty("{$data['product_name']}")) {
            $response->addError('product_name', 'error.product.product_name.is_required');
        }

        if (!key_exists('id_category', $data) || empty("{$data['id_category']}")) {
            $response->addError('id_category', 'error.product.id_category.is_required');
        }

        if (!key_exists('sku', $data) || empty("{$data['sku']}")) {
            $response->addError('sku', 'error.product.sku.is_required');
        }

        if (!key_exists('weight', $data) || empty("{$data['weight']}")) {
            $response->addError('weight', 'error.product.weight.is_required');
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
     * Reformat Data
     * 
     * @param Andri\Engine\Shared\Response $response
     * @return void
     */
    public function reformat(Response $response) {
        $data = $response->incomingData;
        
        $data['sku'] = strtoupper($data['sku']);
        $response->incomingData = $data;
    }





}
