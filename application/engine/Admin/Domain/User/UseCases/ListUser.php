<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Admin\Domain\User\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\User\Contracts\UserRepositoryContract;


class ListUser {
    private $userRepo;

    public function __construct(UserRepositoryContract $userModel) {
        $this->userRepo = $userModel;
    }
    
    public function execute(array $options, PresenterContract $presenter, $excludes = null ) {
        if (!$excludes) {
            $excludes = ['phone', 'email', 'born', 'account_type'];
        }
        $response = new Response;

        $result = $this->userRepo->list($options);
        $users = $result ? $result : [];

        $users = array_map(function($user) use ($excludes) {
            $user = (array)$user;
            unset($user['paswd']);
            unset($user['secret']);
            foreach($excludes as $key) {
                unset($user[$key]);
            }
            return (object)$user;
        }, $users);
        $response->data = $users;
        
        $presenter->present($response);
    }


}