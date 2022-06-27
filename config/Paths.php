<?php

namespace Config;

class Paths {

    public $query;

    public $system_dir;

    public $home_page;

    public $error_page;

    public $storag_dir;

    public $app_dir;

    public $app_pages;

    public function __construct(string $path = '') {
        $this->query = (defined('APP_QUERY')) ? $path.APP_QUERY : '';
        $this->system_dir = (defined('APP_SYSTEM_DIR')) ? $path.APP_SYSTEM_DIR : '';
        $this->home_page = (defined('APP_HOME_PAGE')) ? $path.APP_HOME_PAGE : '';
        $this->error_page = (defined('APP_ERROR_PAGE')) ? $path.APP_ERROR_PAGE : '';
        $this->storage_dir = (defined('APP_STORAGE_DIR')) ? $path.APP_STORAGE_DIR : '';
        $this->app_dir = (defined('APP_DIR')) ? $path.APP_DIR : '';
        $this->app_pages = (defined('APP_PAGES')) ? $path.APP_PAGES : '';
    }

}
