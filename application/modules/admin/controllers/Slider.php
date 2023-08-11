<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';

use Andri\Engine\Shared\RequestExtraction;

class Slider extends REST_Controller {

    private $presenter;

    public function __construct() {
        parent::__construct();

        $this->load->model('Sliders');
    }

    public function index_get($id = null) {
        if ($id) return $this->_detail($id);
        $this->_index();
    }


    // GET
    // ===========================================================================

    private function _index() {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        
        $options = $this->queryExtraction();
        $data = $this->Sliders->list($options);
        
        $this->response(success_format($data), 200);
    }


    private function queryExtraction() {
        $options = $_GET;
        $query = RequestExtraction::default($options);
        
        if (RequestExtraction::check('start_time', $options)) {
            $query['start_time >='] = $options['start_time'];
        } else {
            $query['start_time >='] = date('Y-m-d 00:00:00');
        }

        if (RequestExtraction::check('end_time', $options)) {
            $query['end_time <='] = $options['end_time'];
        }
        
        return $query;
    }

   
    



    // POST
    // ===========================================================================

    public function index_post($id = null) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);

        $data = $this->input->post();
        $image = upload_image('image');
        $data['image_path'] = $image['full_path'];
        
        if ($image) {
            if ($result = $this->Sliders->store($data)) {
                $message = success_format(
                                [
                                    'success' => true,
                                    'id_slider' => $result
                                ], 
                                'success.slider.global.successfully_uploaded'
                            );
                return $this->response($message);
            }
        }

        $error = failed_format(403, 
                        ['slider' => 'error.slider.global.failed_to_upload']
                );
        return $this->response($error);
    }

    


    // DELETE
    // ===========================================================================

    public function index_delete($id) {
        if(!$user = validate_token()) return $this->response(failed_format(403), 403);
        
        if ($this->Sliders->delete($id)) {
            $message = success_format(
                ['success' => true], 
                'success.slider.global.successfully_deleted'
            );
            return $this->response($message);
        }

        $error = failed_format(403, 
                        ['slider' => 'error.slider.global.failed_to_delete']
                );
        return $this->response($error);
        
    }

}
