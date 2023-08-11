<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\User\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\User\Contracts\UserRepositoryContract;

class StoreUser {
    private $userRepo;

    public function __construct(UserRepositoryContract $userModel) {
        $this->userRepo = $userModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        if (!$this->validateUniqField($response)) 
            return $presenter->present($response);

        $this->hashPassword($response);
        $this->reformat($response);

        $data   = $response->incomingData;
        $result = $this->userRepo->store($data);
        
        if (!$result) return $response->addError('model', $result['message']);
        
        $user = $this->userRepo->detailBy('id_auth_user', $result);
        if ($result) {
            $response->data = [
                'id_auth_user' => $user->id_auth_user,
                'uname' => $user->uname,
                'email' => $user->email,
                'phone' => $user->phone
            ];
        }
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
            $response->addError('model', 'error.user.id_auth.is_required');
            return false;
        }

        if (!key_exists('password', $data) || strlen($data['name']) === 0) {
            $response->addError('password', 'error.user.password.is_required');
        }

        if (!key_exists('password_confirm', $data) || strlen($data['name']) === 0) {
            $response->addError('password_confirm', 'error.user.password_confirm.is_required');
        }

        if (!$response->hasError()) {
            if ($data['password'] !== $data['password_confirm']) {
                $response->addError('password', 'error.user.password.is_not_equal');
                return false;
            }
        }
 
        if (!key_exists('email', $data) || strlen($data['email']) === 0) {
            $response->addError('email', 'error.user.email.is_required');
        }

        if (!key_exists('phone', $data) || strlen($data['phone']) === 0) {
            $response->addError('phone', 'error.user.phone.is_required');
        }

        if (!key_exists('username', $data) || strlen($data['username']) === 0) {
            $response->addError('username', 'error.user.username.is_required');
        }

        if (!key_exists('name', $data) || strlen($data['name']) === 0) {
            $response->addError('name', 'error.user.name.is_required');
        }

        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Check for Uniq fields
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function validateUniqField(Response $response) {

        if ($this->isEmailUsed($response)) {
            $response->addError('email', 'error.user.email.already_used');
            return false;
        }

        if ($this->isPhoneUsed($response)) {
            $response->addError('phone', 'error.user.phone.already_used');
            return false;
        }

        if ($this->isUsernameUsed($response)) {
            $response->addError('username', 'error.user.username.already_used');
            return false;
        }

        if ($response->hasError()) return false;
        return true;
    }


    /**
     * Check Uniq Email
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function isEmailUsed(Response $response) {
        $email = $response->incomingData['email'];

        $result = $this->userRepo->detailByFields(['email' => $email]);
        return $result ? true : false;
    }


    /**
     * Check Uniq Phone
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function isPhoneUsed(Response $response) {
        $phone = $response->incomingData['phone'];

        $result = $this->userRepo->detailByFields(['phone' => $phone]);
        return $result ? true : false;
    }

    /**
     * Check Uniq Username
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function isUsernameUsed(Response $response) {
        $username = $response->incomingData['username'];

        $result = $this->userRepo->detailByFields(['uname' => $username]);
        return $result ? true : false;
    }

    /**
     * Encrypt Password
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return void
     */
    public function hashPassword(Response $response) {
        $password = $response->incomingData['password'];
        $password = password_hash($password, PASSWORD_DEFAULT);
        $response->incomingData['password'] = $password;
    }

    /**
     * Reformat array for repository
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return void
     */
    public function reformat(Response $response) {
        $data = $response->incomingData;

        $data['uname'] = $data['username'];
        $data['paswd'] = $data['password'];

        unset($data['username']);
        unset($data['password']);
        unset($data['password_confirm']);
        
        $data['account_type'] = 1;
        $response->incomingData = $data;
    }


}