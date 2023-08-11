<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\Category\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\Category\Contracts\CategoryRepositoryContract;

use Andri\Engine\Admin\Exceptions\CategoryNotFoundException;

class PatchCategory {
    private $categoryRepo;

    public function __construct(CategoryRepositoryContract $categoryModel) {
        $this->categoryRepo = $categoryModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);
        
        if (!$category = $this->getCategory($response)) {
            throw new CategoryNotFoundException;
        }

        $this->patchCategory($category, $response);
        if ($response->hasError()) {
            return $presenter->present($response);
        }
        
        $response->data = [
            'success' => true,
            'id_category' => $category->id_category,
            'category_name' => $category->category_name
        ];
        $presenter->present($response);
    }


    /**
     * Check for user is exists
     * 
     * @param \Andri\Engine\Shared\Response $response
     */
    public function getCategory(Response $response) {
        $data = $response->incomingData;

        $condition = [
            'id_category' => $data['id_category']
        ];
        $result = $this->categoryRepo->detailByFields($condition);
        return $result;
    }


    /**
     * Validation
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function validation(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_category'])) {
            $response->addError('id_category', 'error.category.id_category.is_required');
            return false;
        }

        if (!isset($data['user'])) {
            $response->addError('user', 'error.category.user.not_found');
            return false;
        }

        $data['user'] = (array)$data['user'];

        if ($response->hasError()) return false;
        $response->incomingData = $data;
        return true;
    }


    /**
     * Main Patching
     * 
     * @param mixed $category
     * @param \Andri\Engine\Shared\Response $response
     */
    public function patchCategory($category, Response $response) {
        $data = $response->incomingData;

        // Patch Status
        if (key_exists('status', $data)) {
            if (in_array((int)$data['status'], [0,1])) {
               $result = $this->categoryRepo->update($category, ['status' => (int)$data['status']]);
               if (!$result) {
                   $response->addError('status', 'error.category.global.failed_to_update_status');
                   return false;
               }
            }
        }

        return true;

    }


}