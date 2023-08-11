<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\Category\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\RequestExtraction;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Category\Contracts\CategoryRepositoryContract;


class ListCategory {
    private $categoryRepo;

    public function __construct(CategoryRepositoryContract $categoryModel) {
        $this->categoryRepo = $categoryModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $options['sorted'] = RequestExtraction::sorted($options);
        $options = self::queryExtraction($options);

        $response = new Response;
        $response->incomingData = $options;

        if (!$this->validation($response)) 
            return $presenter->present($response);
        
        $options = $response->incomingData;
        $result = $this->categoryRepo->list($options);
        
        $response->data = $result ? $result : [];
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
            $response->addError('model', 'error.id_auth.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
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

        if (RequestExtraction::check('id_auth', $options)) {
            $query['id_auth'] = $options['id_auth'];
        }

        if (RequestExtraction::check('deleted', $options)) {
            $query['deleted_at <>'] = null;
            if ($options['deleted'] === "0") $query['deleted_at'] = null;
        }

        return $query;
    }

}