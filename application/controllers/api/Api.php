<?php

use Restserver\Libraries\REST_Controller;
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

// extends class dari REST_Controller
class Api extends REST_Controller {
	function __construct($config = '') {
        parent::__construct($config);

        date_default_timezone_set('Asia/Singapore');
        $this->load->database();
        $this->load->model('mdl_api');
    }

    public function user_get()
    {
    	$this->response("ts",200);
    }
}	