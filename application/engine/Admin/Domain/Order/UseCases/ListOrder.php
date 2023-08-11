<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 10th, 2020
 */

namespace Andri\Engine\Admin\Domain\Order\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\RequestExtraction;
use Andri\Engine\Shared\Contracts\PresenterContract;

use Andri\Engine\Admin\Domain\Order\Contracts\OrderRepositoryContract;
use Andri\Engine\Admin\Domain\OrderSource\Contracts\OrderSourceRepositoryContract;

class ListOrder {

    private $orderRepo;
    private $orderSourceRepo;

    public function __construct(OrderRepositoryContract $orderModel, OrderSourceRepositoryContract $orderSourceRepositoryContract) {
        $this->orderRepo = $orderModel;
        $this->orderSourceRepo = $orderSourceRepositoryContract;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        $options = self::queryExtraction($options, $this->orderRepo::TABLE);
        $response = new Response;

        $data = $this->orderRepo->list($options);
        $totalItem = $this->orderRepo->totalItem($options);
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
    public static function queryExtraction(array $options) {
        $query = RequestExtraction::default($options);
        
        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }

        foreach($options as $key => $value) {
            if (!in_array($key, ['q', 'sorted', 'page', 'perPage'])) {
                $query["{$key}"] = $value;
            } else {
                $query[$key] = $value;
            }
        }
        return $query;
    }

}