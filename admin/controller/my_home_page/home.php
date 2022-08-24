<?php

class ControllerMyHomePageHome extends Controller {

    function index() {
        // echo 'admin pageee';
        $this->load->controller('template');

        $data = [];

        echo $this->controller_template->base($data);
    }

}