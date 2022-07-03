<?php

/**
* @package      Paths
* @version      v1.0.0
* @author       YoYoDeveloper / Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

namespace Config;

class Paths {

    public $query;

    public $system_dir;

    public $main_controller;

    public $error_controller;

    public $storag_dir;

    public $app_dir;

    public $app_controller;

    public $app_model;

    public function __construct(string $path = '') {

        $this->query = (defined('APP_QUERY')) ? $path.APP_QUERY : '';
        $this->system_dir = (defined('APP_SYSTEM_DIR')) ? $path.APP_SYSTEM_DIR : '';
        $this->main_controller = (defined('APP_MAIN_CONTROLLER')) ? $path.APP_MAIN_CONTROLLER : '';
        $this->error_controller = (defined('APP_ERROR_CONTROLLER')) ? $path.APP_ERROR_CONTROLLER : '';
        $this->storage_dir = (defined('APP_STORAGE_DIR')) ? $path.APP_STORAGE_DIR : '';
        $this->app_dir = (defined('APP_DIR')) ? $path.APP_DIR : '';
        $this->app_controller = (defined('APP_CONTROLLER')) ? $path.APP_CONTROLLER : '';
        $this->app_model = (defined('APP_MODEL')) ? $path.APP_MODEL : '';
        
    }

}
