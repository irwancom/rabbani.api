<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\Category\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Category\Contracts\CategoryRepositoryContract;

class StoreCategory {
    private $categoryRepo;

    public function __construct(CategoryRepositoryContract $categoryModel) {
        $this->categoryRepo = $categoryModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $this->reformat($response);

        $data = $response->incomingData;
        
        $category = $this->checkCategory($data);
        if (!$category) {
            $result = $this->categoryRepo->store($data);
        } else {
            $result = $this->categoryRepo->update($category, $data);
            if ($result) $result = $category->id_category;
        }
        
        if (!$result) return $response->addError('model', $result['message']);
        
        $category = $this->categoryRepo->detailBy('id_category', $result);
        //$slug = $category->category_name.'-'.$category->id_category;
        //$nextAction = $this->categoryRepo->update($category, ['category_slug' => slugify($slug)]);
        $response->data = $result ? $category : null;
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
        
        if (key_exists('id_parent', $data) && (int)$data['id_parent'] > 0) {
            if (!$this->checkParentCategory((int)$data['id_parent'])) {
                $response->addError('id_parent', 'error.id_parent.not_found');
                return false;
            }
        }

        if (!key_exists('category_name', $data) || strlen($data['category_name']) === 0) {
            $response->addError('category_name', 'error.category_name.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }

    /**
     * Check parent category
     * 
     * @param int $id_parent
     * @return bool
     */
    public function checkParentCategory(int $id_parent) {
        $parent = $this->categoryRepo->detailBy('id_parent', $id_parent);

        if ($parent) return true;
        return false;
    }

    /**
     * Check is category already stored
     * 
     * @param array $condition
     * @return object
     */
    public function checkCategory($condition) {
        $fields = [
            'id_auth' => $condition['id_auth'],
            'category_name' => strtoupper($condition['category_name'])
        ];

        $category = $this->categoryRepo->detailByFields($fields);
        return $category;
    }

    /**
     * Reformat Property Value
     */
    public function reformat(Response $response) {
        $name = $response->incomingData['category_name'];
        $response->incomingData['category_name'] = strtoupper($name);
    }


}