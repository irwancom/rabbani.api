<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\OrderSource\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\RequestExtraction;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\OrderSource\Contracts\OrderSourceRepositoryContract;


class ListOrderSource {
    private $orderSourceRepo;

    public function __construct(OrderSourceRepositoryContract $orderSourceModel) {
        $this->orderSourceRepo = $orderSourceModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        $options = self::queryExtraction($options);

        $response = new Response;
        $result = $this->orderSourceRepo->list($options);
        
        $response->data = $result ? $result : [];
        $presenter->present($response);
    }

    /**
     * Query Extraction
     * 
     * @param array $options
     */
    public static function queryExtraction(array $options, $alias = null) {
        $query = RequestExtraction::default($options);

        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }

        return $query;
    }

}