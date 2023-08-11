<?php declare(strict_types=1);

namespace Andri\Engine\Client\Domain\Order\Contracts;

interface FlashSaleRepositoryContract {
    public function detail(array $options);
}