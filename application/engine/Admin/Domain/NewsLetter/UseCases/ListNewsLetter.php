<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Admin\Domain\NewsLetter\UseCases;

use Andri\Engine\Shared\Response;
use Andri\Engine\Shared\Contracts\PresenterContract;
use Andri\Engine\Admin\Domain\NewsLetter\Contracts\NewsLetterRepositoryContract;


class ListNewsLetter {
    private $newsRepo;

    public function __construct(NewsLetterRepositoryContract $newsModel) {
        $this->newsRepo = $newsModel;
    }
    
    public function execute(array $options, PresenterContract $presenter) {
        $response = new Response;

        $result = $this->newsRepo->list($options);
        $response->data = $result ? $result : [];
        $presenter->present($response);
    }

    
}