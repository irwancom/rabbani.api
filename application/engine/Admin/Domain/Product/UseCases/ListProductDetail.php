<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 10th, 2020
 */

namespace Andri\Engine\Admin\Domain\Product\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\RequestExtraction;
use Andri\Engine\Shared\Contracts\PresenterContract;

use Andri\Engine\Admin\Domain\Product\Contracts\ProductRepositoryContract;

class ListProductDetail {

    private $productRepo;

    public function __construct(ProductRepositoryContract $productModel) {
        $this->productRepo = $productModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        
        $options = self::queryExtraction($options, $this->productRepo::TABLE_DETAIL);
        $response = new Response;

        $totalItem = $this->productRepo->totalItem($options, true);
        $totalPage = ceil($totalItem/$options['perPage']);

        $data = $this->productRepo->list($options, true);
        $response->data = [
            'data'      => $data,
            'totalItem' => $totalItem,
            'totalPage' => $totalPage,
            'page'      => $options['page'],
            'perPage'   => $options['perPage']
        ];
        $presenter->present($response);
    }

    /**
     * Query Extraction
     * 
     * @param array $options
     */
    public static function queryExtraction(array $options, $alias = null) {
        $query = RequestExtraction::default($options);
        
        if (RequestExtraction::check('status', $options)) {
            $query['status'] = $options['status'];
        }

        if (RequestExtraction::check('is_ready_stock', $options)) {
            $query['is_ready_stock'] = $options['is_ready_stock'];
        }

        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }
        
        if ($alias) {
            $options = [];
            foreach($query as $key => $value) {
                if (!in_array($key, ['q', 'sorted', 'page', 'perPage'])) {
                    $options["{$alias}.{$key}"] = $value;
                } else {
                    $options[$key] = $value;
                }
            }
            $query = $options;
        }

        return $query;
    }

}