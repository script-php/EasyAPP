<?php

define('PATH', __DIR__ . DIRECTORY_SEPARATOR);

chdir(PATH);

include PATH . 'system/Framework.php';

// Bootstrap the web application
bootstrap();