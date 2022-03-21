<?php
/*
** @Name: EasyAPP
** @Author: Glitch3R(YoYo)
** @Created: 23.01.2022
** @Version: 1.0.0
*/
include 'APP.php';
include 'app/config.php';
include 'app/global.php';

spl_autoload_register('APP::loader');

APP::RENDER_PAGES();

?>
