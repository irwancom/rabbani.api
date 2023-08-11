<?php declare(strict_types=1);

namespace Andri\Engine\Admin\Domain\NewsLetter\Contracts;

interface NewsLetterRepositoryContract {
    public function list(array $options);
    public function store(array $data);
    public function detailBy($field, $id);
    public function detailByFields(array $conditions);
    public function update($object, array $data);
}