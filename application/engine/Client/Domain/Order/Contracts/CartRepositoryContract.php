<?php declare(strict_types=1);

namespace Andri\Engine\Client\Domain\Order\Contracts;

interface CartRepositoryContract {
    public function list(array $options);
    public function store(array $detail);
    public function detail($field, $value);
    public function update($objet, $data);
}