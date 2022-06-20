<?php

APP::$route                 = "route"; //query parameter name which its use to select the page
APP::$home_page             = "PageHome"; //the page that it will be showed like home page if there is no page selected
APP::$error_page            = "PageError"; //the error page showed when its selected a page that doesn't exists
APP::$folder_functions      = "app/functions"; //the location of the functions files folder

//set a connection to a database called "main" or whatever name you want to have.
// APP::PDO('main','localhost','main','root','');

//we can set multiple database connections if we need that. Just use a different name for every connection
// APP::PDO('db2','localhost','database2','root','');
// APP::PDO('db3','localhost','database3','root','');
// APP::PDO('backup','localhost','backup','root','');

?>