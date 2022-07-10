<?php

/**
* @package      Config
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System;

class Config {

    // App config
    public $url     = NULL;
    public $main_controller     = NULL;
    public $error_controller    = NULL;

    // Framework config
    public $query               = 'route';
    public $dir_app             = 'app/';
    public $dir_controller      = 'app/controller/';
    public $dir_model           = 'app/model/';
    public $dir_view            = 'app/view/';
    public $dir_language        = 'app/language/';

    // System
    public $dir_system          = 'system/';
    public $dir_framework       = 'system/Framework/';
    public $dir_library         = 'system/Library/';
    public $dir_storage         = 'storage/'; # system/Storage

    public function __construct(string $path = '') {

        $this->main_controller      = $path . $this->main_controller;
        $this->error_controller     = $path . $this->error_controller;
        $this->dir_app              = $path . $this->dir_app;
        $this->dir_controller       = $path . $this->dir_controller;
        $this->dir_model            = $path . $this->dir_model;
        $this->dir_view             = $path . $this->dir_view;
        $this->dir_language         = $path . $this->dir_language;
        $this->dir_system           = $path . $this->dir_system;
        $this->dir_framework        = $path . $this->dir_framework;
        $this->dir_library          = $path . $this->dir_library;
        $this->dir_storage          = $path . $this->dir_storage;
        
    }

}
