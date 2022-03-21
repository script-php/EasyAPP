<?php
include 'app/APP.php';
include 'app/config.php';
include 'app/global.php';

spl_autoload_register('APP::loader');

APP::RENDER_PAGES(); //show the selected page

?>
