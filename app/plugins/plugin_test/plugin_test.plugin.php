<?php

/**
* @package      My first plugin
* @version      1.0.1
* @author       Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
* @see          https://docs.phpdoc.org/3.0/guide/references/phpdoc/tags/index.html
*/

class plugin_test {

    private $settings = [];
    
    function __construct() {
        $settings = APP::Settings($this); // load settings
        // do something when the class is initialized
    }

    function register() {
        return [
            // actions
            'actions'       => [
                'show_something'    => [
                    'name'              => 'Action name',
                    'description'       => 'This action show something'
                ],
                'do_other'          => [
                    'name'              => 'Do other',
                    'description'       => 'This action do other things'
                ]
            ],
            // hooks
            'hooks'       => [
                'HOOK_TEST'    => [
                    'name'              => 'HOOK TEST',
                    'description'       => 'This hook show the menu and blah blah'
                ]
            ],
        ];
    }

    function admin() {
        // here will be the settings page
    }

    function install() {
        // do something where this plugin is installed
    }

    function uninstall() {
        // do something when this plugin is uninstalled
    }
    
    function start() {
        // do something when this plugin is started
    }
    
    function stop() {
        // do something when this plugin is stopped
    } 

    function show_something() {
        echo APP::HTML('app/plugins/plugin_test/template/o_pagina_html.html');
    }

    function do_other() {
        echo 'DO IT!!';
    }
    
}