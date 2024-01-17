<?php

define('PATH', __DIR__ . DIRECTORY_SEPARATOR);

chdir(PATH);

include PATH . 'system/Framework.php';

new System\Framework\Router(); // You can change it with another Router, if you find something compatible