<?php

/**
* @package      EasyAPP
* @version      1.1.0
* @author       YoYoDeveloper / Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

include 'core/APP.php';
include 'app/config.php';
include 'app/global.php';

# classes
spl_autoload_register(function($className) {
    $directory = new RecursiveDirectoryIterator(APP::$folder_classes, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = NULL;
    if (is_null($iterator)) { $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY); }
    foreach ($iterator as $file) {
        if (strtolower($file->getFilename()) === strtolower($className . '.php')) {
            if ($file->isReadable()) { include_once $file->getPathname(); }
            break;
        }
    }
});

# plugins
spl_autoload_register(function($className) {
    $directory = new RecursiveDirectoryIterator(APP::$folder_plugins, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = NULL;
    if (is_null($iterator)) { $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY); }
    foreach ($iterator as $file) {
        if (strtolower($file->getFilename()) === strtolower(APP::Class2File($className, 'Plugin') . '.php')) {
            if ($file->isReadable()) { include_once $file->getPathname(); }
            break;
        }
    }
});

# pages
spl_autoload_register(function($className) {
    $directory = new RecursiveDirectoryIterator(APP::$folder_pages, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = NULL;
    if (is_null($iterator)) { $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY); }
    foreach ($iterator as $file) {
        if (strtolower($file->getFilename()) === strtolower(APP::Class2File($className, 'Page') . '.php')) {
            if ($file->isReadable()) { include_once $file->getPathname(); }
            break;
        }
    }
});

// APP::REGISTRY(['START']);

APP::HOOK('START');

// APP::RENDER_PAGES(); 
?>
