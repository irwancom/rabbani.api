<?php declare(strict_types=1);

/**
 * Andri Engine <andri.dot.py2018@gmail.com>
 * @date July 7th, 2020
 */

namespace Andri\Engine\Shared;

class Response {

    protected $notification = [
        'errors' => [],
        'messages' => []
    ];

    public $data;
    public $incomingData;
    
    public function notification() {
        return $this->notification;
    }

    /**
     * Push Error Message into bucket list
     * 
     * @param string $key
     * @param string $message
     * 
     * @return void
     */
    public function addError($key, $message) {
        $errors = $this->notification['errors'];
        if (!isset($errors[$key])) {
            $errors[$key] = [$message];
        } else {
            $errors[$key][] = $message;
        }
        $this->notification['errors'] = $errors;
    }

    
    /**
     * Push Whatever message into bucket list
     * 
     * @param string $key
     * @param string $message
     */
    public function addMessage($key, $message) {
        $messages = $this->notification['messages'];
        if (!isset($messages[$key])) {
            $messages[$key] = [$message];
        } else {
            $messages[$key][] = $message;
        }
        $this->notification['messages'] = $messages;
    }


    /**
     * Check if bucket errors is not empty
     */
    public function hasError() {
        return (sizeOf($this->notification['errors']) > 0);
    }


    /**
     * Get Data
     * 
     * its required in presenter so dont remove it 
     * if you dont know what you do.
     * It could be break something
     */
    public function getData() {
        return $this->data;
    }

}