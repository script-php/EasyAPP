<?php

class LibraryControllers extends Library {

    function get($exclude = []) {
        $baseDir = realpath(CONFIG_DIR_CONTROLLER . '');
 
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir)
        );

        $controllers = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {

                $fullPath = $file->getRealPath();
                $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $fullPath);
                $controller = str_replace('.php', '', $relativePath);

                $replace = str_replace("\\","/",$controller);
                if (!in_array($replace, $exclude)) {
                    $controllers[] = str_replace(DIRECTORY_SEPARATOR, '/', $controller); // Ensure forward slashes
                }

            }
        }
        return $controllers;
    }

}