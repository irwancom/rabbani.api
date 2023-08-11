<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 9th, 2020
 */

namespace Andri\Engine\Client\Domain\Wishlist\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Client\Domain\Wishlist\Contracts\WishListRepositoryContract;


class ListWishlist {
    private $wishlistRepo;

    public function __construct(WishListRepositoryContract $wishlistModel) {
        $this->wishlistRepo = $wishlistModel;
    }
    
    public function execute(array $options, PresenterContract $presenter ) {
        $response = new Response;
        $response->incomingData = $options;

        if (!$this->validation($response))
            return $presenter->present($response);
        
        $result = $this->wishlistRepo->list($options);
        $wishlists = $result ? $result : [];

        $response->data = $wishlists;
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

        if (!isset($data['id_auth_user'])) {
            $response->addError('model', 'error.user.id_auth_user.is_required');
            return false;
        }

        if ($response->hasError()) return false;
        $response->incomingData = $data;
        return true;
    }


}