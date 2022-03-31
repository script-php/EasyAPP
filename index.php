<?php
/*
** @Name: EasyAPP
** @Author: YoYoDeveloper
** @Created: 23.01.2022
** @Version: 1.0.0
*/
include 'core/APP.php';
include 'app/config.php';
include 'app/global.php';

spl_autoload_register(function($className) {
    $directory = new RecursiveDirectoryIterator(APP::$folder_classes, RecursiveDirectoryIterator::SKIP_DOTS);
    if (is_null(APP::$fileIterator)) {
        APP::$fileIterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);
    }
    $filename = $className . APP::$fileExt;
    foreach (APP::$fileIterator as $file) {
        if (strtolower($file->getFilename()) === strtolower($filename)) {
            if ($file->isReadable()) {
                include_once $file->getPathname();
            }
            break;
        }
    }
});

APP::RENDER_PAGES();

?>
