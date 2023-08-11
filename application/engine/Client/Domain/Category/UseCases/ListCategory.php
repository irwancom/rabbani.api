<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Client\Domain\Category\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Client\Domain\Category\Contracts\CategoryRepositoryContract;


class ListCategory {
    private $categoryRepo;

    public function __construct(CategoryRepositoryContract $categoryModel) {
        $this->categoryRepo = $categoryModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $options;

        if (!$this->validation($response)) 
            return $presenter->present($response);
        
        $options = $response->incomingData;
        $result = $this->categoryRepo->list($options);
        
        $response->data = array_map(function($item) {
            $item = [
                'id_category'   => $item->id_category,
                'id_parent'     => $item->id_parent,
                'category_name' => $item->category_name
            ];
            return $item;
        }, $result);

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

}