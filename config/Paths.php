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

    public $dir_system;

    public $dir_framework;

    public $dir_library;

    public $main_controller;

    public $error_controller;

    public $storag_dir;

    public $dir_app;

    public $dir_controller;

    public $dir_model;

    public $dir_view;
    
    public $dir_language;

    public function __construct(string $path = '') {

        $this->query = (defined('APP_QUERY')) ? $path.APP_QUERY : '';
        $this->dir_system = (defined('DIR_SYSTEM')) ? $path.DIR_SYSTEM : '';
        $this->dir_framework = (defined('DIR_FRAMEWORK')) ? $path.DIR_FRAMEWORK : '';
        $this->dir_library = (defined('DIR_LIBRARY')) ? $path.DIR_LIBRARY : '';
        $this->main_controller = (defined('MAIN_CONTROLLER')) ? $path.MAIN_CONTROLLER : '';
        $this->error_controller = (defined('ERROR_CONTROLLER')) ? $path.ERROR_CONTROLLER : '';
        $this->dir_storage = (defined('DIR_STORAGE')) ? $path.DIR_STORAGE : '';
        $this->dir_app = (defined('DIR_APP')) ? $path.DIR_APP : '';
        // $this->app_classes = (defined('APP_CLASSES')) ? $path.APP_CLASSES : '';
        $this->dir_controller = (defined('DIR_CONTROLLER')) ? $path.DIR_CONTROLLER : '';
        $this->dir_model = (defined('DIR_MODEL')) ? $path.DIR_MODEL : '';
        $this->dir_view = (defined('DIR_VIEW')) ? $path.DIR_VIEW : '';
        $this->dir_language = (defined('DIR_LANGUAGE')) ? $path.DIR_LANGUAGE : '';
        
    }

}
