<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\User\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\User\Contracts\UserRepositoryContract;

class PatchUser {
    private $userRepo;

    public function __construct(UserRepositoryContract $userModel) {
        $this->userRepo = $userModel;
    }
    
    public function execute($user, array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;
        
        if (!$user) {
            $response->addError('user', 'error.user.global.not_found');
            return $presenter->present($response);
        }

        $result = false;
        if (key_exists('account_type', $data)) {
            $result = $this->checkAccountType($user, $response);
        }
        
        if ($response->hasError()) {
            return $presenter->present($response);
        }
        
        $presenter->present($response);
    }


    /**
     * Check for user is exists
     */
    public function getUser(int $id_auth, int $id_auth_user) {
        $result = $this->userRepo->detailBy('id_auth_user', $id_auth_user);
        return $result;
    }


    /**
     * Validation
     * @param \Andri\Engine\Shared\Response $response
     * 
     * @return bool
     */
    public function validation(Response $response) {
        $data = $response->incomingData;

        if (!isset($data['id_auth_user'])) {
            $response->addError('model', 'error.user.id_auth_user.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        $response->incomingData = $data;
        return true;
    }


    /**
     * Check Account Type
     * 
     * @param mixed $user
     * @param \Andri\Engine\Shared\Response $response
     */
    public function checkAccountType($user, Response $response) {
        $data = $response->incomingData;
        
        switch($data['account_type']) {
            case "0":
                $accountType = $this->userRepo::ADMIN;
                break;

            default:
                $accountType = $this->userRepo::STAFF;
        }

        $result = $this->userRepo->update($user, ['account_type' => $accountType]);
        if (!$result) {
            $response->addError('user', 'error.user.global.failed_update_account_type');
            return false;    
        }

        $response->data = [
            'success' => true,
            'user' => [
                'id_auth_user' => $user->id_auth_user,
                'account_type' => $accountType
            ]
        ];
        return true;
    }




}