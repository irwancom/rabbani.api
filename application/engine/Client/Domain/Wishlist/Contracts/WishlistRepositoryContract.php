<?php declare(strict_types=1);

namespace Andri\Engine\Client\Domain\Wishlist\Contracts;

interface WishListRepositoryContract {
    public function list(array $options);
    public function detailByFields(array $conditions);
}