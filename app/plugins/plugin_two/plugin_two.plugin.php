<?php

/**
* @package      My 2nd plugin
* @version      1.0.0
* @author       YoYo
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://script-php.ro
* @see          https://docs.phpdoc.org/3.0/guide/references/phpdoc/tags/index.html
*/

class plugin_two {

    private $settings = [];
    
    function __construct() {
        $settings = APP::Settings($this);
        // do something when the class is initialized
    }

    function plugin() {
        return [
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
        echo 'show something';
    }

    function do_other() {
        echo 'DO IT!!';
    }
    
}