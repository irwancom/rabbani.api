
<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Service\Delivery;
use Service\Validator;
use Service\CLM\Handler\CatalogHandler;

class Catalog extends REST_Controller {

    private $validator;
    private $delivery;

    public function __construct() {
        parent::__construct();
        $this->load->model('MainModel');
        $this->validator = new Validator($this->MainModel);
        $this->delivery = new Delivery;
    }

    public function list_get () {
        $filters = $this->input->get();
        if (!isset($filters['current_time']) || empty($filters['current_time'])) {
            $filters['current_time'] = date('Y-m-d H:i:s');
        }
        $handler = new CatalogHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $result = $handler->getCatalogs($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

    public function detail_get ($idCatalog) {
        $handler = new CatalogHandler($this->MainModel);
        // $handler->setUser($auth->data);
        $filters = [
            'id' => $idCatalog
        ];
        $result = $handler->getCatalog($filters);

        $this->response($result->format(), $result->getStatusCode());
    }

}
