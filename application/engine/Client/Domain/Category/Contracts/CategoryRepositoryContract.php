<?php declare(strict_types=1);

namespace Andri\Engine\Client\Domain\Category\Contracts;

interface CategoryRepositoryContract {
    public function list(array $options);
}