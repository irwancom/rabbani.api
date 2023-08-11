<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\NewsLetter\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\NewsLetter\Contracts\NewsLetterRepositoryContract;

class UpdateNewsLetter {
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

        $letter = $this->checkLetter((int)$data['id_auth'], (int)$data['id_letter']);
        if (!$letter) {
            $response->addError('newsletter', 'error.newsletter.global.not_found');
            return $presenter->present($response);
        }

        $result = $this->newsRepo->update($letter, $data);
        if (!$result) return $response->addError('model', $result['message']);
        
        $letter = $this->newsRepo->detailBy('id_letter', $letter->id_letter);
        $response->data = $result ? $letter : null;
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
        
        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Check Letter
     */
    public function checkLetter(int $id_auth, int $id_letter) {
        $letter = $this->newsRepo->detailByFields([
                    'id_auth' => $id_auth,
                    'id_letter' => $id_letter
                ]);
        return $letter;
    }

}