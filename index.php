<?php
include 'APP.php';
include 'app/config.php';
include 'app/global.php';

spl_autoload_register('APP::loader');

APP::RENDER_PAGES();

?>
