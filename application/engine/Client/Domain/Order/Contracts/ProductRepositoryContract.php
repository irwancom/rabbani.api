<?php declare(strict_types=1);

namespace Andri\Engine\Client\Domain\Order\Contracts;

interface ProductRepositoryContract {
    public function detailItem(array $options);
}