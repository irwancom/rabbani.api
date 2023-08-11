<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\User\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\User\Contracts\UserRepositoryContract;


class DetailUser {
    private $userRepo;

    public function __construct(UserRepositoryContract $userModel) {
        $this->userRepo = $userModel;
    }
    
    public function execute(int $id, PresenterContract $presenter, $excludes = null ) {
        if (!$excludes) {
            $excludes = ['phone', 'email', 'born'];
        }
        $response = new Response;
        $user = $this->userRepo->detailBy('id_auth_user', $id);
        if (!$user) {
            $response->addError('user', 'error.user.globa.not_found');
            return $presenter->present($response);
        }

        $user = (array)$user;
        unset($user['paswd']);
        unset($user['secret']);
        foreach($excludes as $key) {
            unset($user[$key]);
        }
        $user = (object)$user;

        $response->data = $user;
        $presenter->present($response);
    }


}