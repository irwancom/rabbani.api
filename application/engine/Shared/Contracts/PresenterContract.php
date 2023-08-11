<?php declare(strict_types=1);

namespace Andri\Engine\Shared\Contracts;

use Andri\Engine\Shared\Response;

interface PresenterContract {

    public function present(Response $object);

}