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

class ListProduct {

    private $productRepo;

    public function __construct(ProductRepositoryContract $productModel) {
        $this->productRepo = $productModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        
        $options = self::queryExtraction($options, $this->productRepo::TABLE);
        $response = new Response;

        $data = $this->productRepo->list($options);
        $totalItem = $this->productRepo->totalItem($options);
        $totalPage = ceil($totalItem/$options['perPage']);

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

        if (RequestExtraction::check('id_category', $options)) {
            $query['id_category'] = $options['id_category'];
        }

        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }
        if ($alias) {
            foreach($options as $key => $value) {
                if (!in_array($key, ['q', 'sorted', 'page', 'perPage'])) {
                    $query["{$alias}.{$key}"] = $value;
                } else {
                    $query[$key] = $value;
                }
            }
        }
        return $query;
    }

}