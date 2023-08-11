<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Auth\UseCases;

use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Shared\Response;


class Login {

    private $userRepo;

    public function __construct($userModel) {    
        $this->userRepo = $userModel;
    }

    public function execute($credentials, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $credentials;

        if (!$this->validate($response)) return $presenter->present($response);

        $cred = $response->incomingData;
        $result = $this->userRepo->auth($cred['uname'], $cred['paswd']);

        $response->data = $result;
        $presenter->present($response);
    }

    /**
     * Validate Paramters
     * 
     * @param \Engine\Shared\Response $response;
     * @return bool
     */
    public function validate(Response $response) {
        $credentials = $response->incomingData;

        if (!isset($credentials['paswd']) || !isset($credentials['uname'])) {
            $response->addError('paswd', 'Invalid username/password');
        }

        if ($response->hasError()) return false;

        return true;
    }

}
