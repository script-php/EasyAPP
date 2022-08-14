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
    public $session             = true;

    // Framework config
    public $query               = 'route';
    public $dir_app             = 'app/';
    public $dir_controller      = 'controller/';
    public $dir_model           = 'model/';
    public $dir_view            = 'view/';
    public $dir_language        = 'language/';

    // System
    public $dir_system          = 'system/';
    public $dir_framework       = 'Framework/';
    public $dir_library         = 'Library/';
    public $dir_storage         = 'storage/'; # system/Storage

    public $debug               = false;

    public function __construct(string $path = '') {
        
        $this->dir_app              = $path . $this->dir_app;
        $this->dir_controller       = $this->dir_app . $this->dir_controller;
        $this->dir_model            = $this->dir_app . $this->dir_model;
        $this->dir_view             = $this->dir_app . $this->dir_view;
        $this->dir_language         = $this->dir_app . $this->dir_language;
        $this->dir_system           = $path . $this->dir_system;
        $this->dir_framework        = $this->dir_system . $this->dir_framework;
        $this->dir_library          = $this->dir_system . $this->dir_library;
        $this->dir_storage          = $path . $this->dir_storage;
        
    }

}
