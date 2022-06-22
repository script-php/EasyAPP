<?php

/**
* @package      EasyAPP
* @version      v1.1.2
* @author       YoYoDeveloper / Smehh
* @copyright    2022 SMEHH - Web Software Development Company
* @link         https://smehh.ro
*/

# base of the framework
include 'APP.php';

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

include 'app/config.php';
include 'app/global.php';

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

// Register a hook to be able to use it later
HOOK::REGISTER('START');

// Attach the ROUTER action to this hook.
HOOK::SET('START', 'ROUTER/LOAD'); // here ROUTER is a class & LOAD is a method from ROUTER class

// Run the hook
HOOK::RUN('START');

?>
