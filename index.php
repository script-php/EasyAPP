<?php

/**
* @package      EasyAPP
* @version      1.1.1
* @author       YoYoDeveloper / Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

include 'APP.php';
include 'app/config.php';
include 'app/global.php';

# auto load core classes
APP::LOADER('core', function($file, $classname) {
	if (strtolower($file->getFilename()) === strtolower($classname . '.php')) {
        if ($file->isReadable()) {
            include_once $file->getPathname();
            return true;
        }
    }
});

# auto load app classes
APP::LOADER('app/classes', function($file, $classname) {
    if (strtolower($file->getFilename()) === strtolower($classname . '.php')) {
        if ($file->isReadable()) {
            include_once $file->getPathname();
            return true;
        }
    }
});

# auto load plugins
APP::LOADER('app/plugins', function($file, $classname) {
    if (strtolower($file->getFilename()) === strtolower(APP::Class2File($classname, 'Plugin') . '.php')) {
        if ($file->isReadable()) {
            include_once $file->getPathname();
            return true;
        }
    }
});

# autoload pages
APP::LOADER('app/pages', function($file, $classname) {
    if (strtolower($file->getFilename()) === strtolower(APP::Class2File($classname, 'Page') . '.php')) {
        if ($file->isReadable()) {
            include_once $file->getPathname();
            return true;
        }
    }
});

// TODO: register default actions in registry
// TODO: set default actions in hook

PLUGINS::HOOK('START');

?>
