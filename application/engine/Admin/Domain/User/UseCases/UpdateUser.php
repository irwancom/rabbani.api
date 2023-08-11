<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\User\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\User\Contracts\UserRepositoryContract;

class UpdateUser {
    private $userRepo;

    public function __construct(UserRepositoryContract $userModel) {
        $this->userRepo = $userModel;
    }
    
    public function execute(array $data, PresenterContract $presenter) {
        $response = new Response;
        $response->incomingData = $data;

        if (!$this->validation($response)) 
            return $presenter->present($response);

        if (!$this->checkUniqueError($response))
            return $presenter->present($response);


        $user = $this->getUser((int)$data['id_auth_user']);
        if (!$user) {
            $response->addError('user', 'error.user.global.not_found');
            return $presenter->present($response);
        }

        $this->reformat($user, $response);
        $data = $response->incomingData;

        $result = $this->userRepo->update($user, $data);
        if (!$result) return $response->addError('model', $result['message']);
        
        $user = $this->userRepo->detailBy('id_auth_user', $user->id_auth_user);
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
     * Check for user is exists
     */
    public function getUser(int $id_auth_user) {
        $result = $this->userRepo->detailByFields([
            'id_auth_user' => $id_auth_user
        ]);
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

        unset($data['account_type']);
        unset($data['password']);
        unset($data['password_confirm']);

        if (!isset($data['id_auth'])) {
            $response->addError('model', 'error.user.id_auth.is_required');
            return false;
        }

        if ($response->hasError()) return false;

        $response->incomingData = $data;
        return true;
    }


    /**
     * Check for Uniq fields
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return bool
     */
    public function checkUniqueError(Response $response) {

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
        if (!isset($response->incomingData['email']))
            return false;

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
        if (!isset($response->incomingData['phone']))
            return false;

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
        if (!isset($response->incomingData['username']))
            return false;

        $username = $response->incomingData['username'];

        $result = $this->userRepo->detailByFields(['uname' => $username]);
        return $result ? true : false;
    }


    /**
     * Reformat array for repository
     * 
     * @param \Andri\Engine\Shared\Response $response
     * @return void
     */
    public function reformat($user, Response $response) {
        $data = $response->incomingData;

        $data['uname'] = $user->uname;

        if (isset($data['username'])) {
            $data['uname'] = $data['username'];
            unset($data['username']);
        }

        $response->incomingData = $data;
    }

}