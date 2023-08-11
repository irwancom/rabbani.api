<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\FlashSale\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\RequestExtraction;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\FlashSale\Contracts\FlashSaleRepositoryContract;


class ListFlashSale {
    private $saleRepo;

    public function __construct(FlashSaleRepositoryContract $saleModel) {
        $this->saleRepo = $saleModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        $options = self::queryExtraction($options);

        $response = new Response;
        
        $totalItem = $this->saleRepo->totalItem($options);
        $totalPage = ceil($totalItem/$options['perPage']);

        $data = $this->saleRepo->list($options);
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
    public static function queryExtraction(array $options) {
        $query = RequestExtraction::default($options);
        
        if (RequestExtraction::check('start_time', $options)) {
            $query['start_time >='] = $options['start_time'];
        } else {
            $query['start_time >='] = date('Y-m-d h:i:s');
        }

        if (RequestExtraction::check('end_time', $options)) {
            $query['end_time <='] = $options['end_time'];
        }

        if (RequestExtraction::check('id_product_detail', $options)) {
            $query['id_product_detail'] = $options['id_product_detail'];
        }

        if (RequestExtraction::check('status', $options)) {
            $query['status'] = $options['status'];
        }

        if (RequestExtraction::check('discount_type', $options)) {
            $query['discount_type'] = $options['discount_type'];
        }

        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }
        
        return $query;
    }


}