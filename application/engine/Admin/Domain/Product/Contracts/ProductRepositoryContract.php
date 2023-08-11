<?php declare(strict_types=1);

namespace Andri\Engine\Admin\Domain\Product\Contracts;

interface ProductRepositoryContract {
    public function list(array $options);
    public function totalItem(array $options);
    public function store(array $data);
    public function detailByFields(array $conditions, bool $with_detail);
    public function detailItemByFields(array $condition);
    public function update($object, array $data);
    public function updateDetail($object, array $data);
    
}