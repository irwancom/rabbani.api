<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 8th, 2020
 */

namespace Andri\Engine\Admin\Domain\Category\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Category\Contracts\CategoryRepositoryContract;

class UpdateCategory {

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
        $category = $this->checkCategory((int)$data['id_auth'], (int)$data['id_category']);
        if (!$category) {
            $response->addError('category', 'error.category.not_found');
            return $presenter->present($response); 
        }

        $result = $this->categoryRepo->update($category, $data);
        if ($result) $result = $category->id_category;
        
        $category = $this->categoryRepo->detailBy('id_category', $category->id_category);
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

        if (!key_exists('id_category', $data) || strlen("{$data['id_category']}") === 0 ) {
            $response->addError('id_category', 'error.id_category.is_required');
            return false;
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
     * @param int $id_user
     * @param int $id_category
     * @return object
     */
    public function checkCategory(int $id_auth, int $id_category) {

        $fields = [
            'id_auth'       => $id_auth,
            'id_category'   => $id_category
        ];

        $category = $this->categoryRepo->detailByFields($fields);
        return $category;
    }

    

    /**
     * Reformat Property Value
     */
    public function reformat(Response $response) {
        if (!isset($response->incomingData['category_name']))
            return false;

        $name = $response->incomingData['category_name'];
        $response->incomingData['category_name'] = strtoupper($name);
    }
}