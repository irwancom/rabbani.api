<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Shared;

use Andri\Engine\Shared\Contracts\PresenterContract;

class Presenter implements PresenterContract {

    public $data;
    public $errors = [];
    public $messages = [];

    public function present(Response $response) {
        $data = $response->getData();
        $this->data = $data;
        
        $notification = $response->notification();

        $this->errors = $notification['errors'];
        $this->messages = $notification['messages'];
    }

    /**
     * Check if response has Error
     */
    public function hasError() {
        return (sizeOf($this->errors) > 0);
    }
   
}