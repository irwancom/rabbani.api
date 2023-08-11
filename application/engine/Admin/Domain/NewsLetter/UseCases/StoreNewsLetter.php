<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\NewsLetter\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\NewsLetter\Contracts\NewsLetterRepositoryContract;

class StoreNewsLetter {
    private $newsRepo;

    public function __construct(NewsLetterRepositoryContract $newsModel) {
        $this->newsRepo = $newsModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        $data = $response->incomingData;
        $result = $this->newsRepo->store($data);
        
        if (!$result) return $response->addError('model', $result['message']);
        
        $category = $this->newsRepo->detailBy('id_letter', $result);
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
            $response->addError('model', 'error.newsletter.id_auth.is_required');
            return false;
        }
        
        if (!key_exists('title', $data) || strlen($data['title']) === 0) {
            $response->addError('title', 'error.newsletter.title.is_required');
        }

        if (!key_exists('content', $data) || strlen($data['content']) === 0) {
            $response->addError('content', 'error.newsletter.content.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }





}