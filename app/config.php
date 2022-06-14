<?php

APP::$route                 = "route"; //query parameter name which its use to select the page
APP::$home_page             = "page_home"; //the page that it will be showed like home page if there is no page selected
APP::$error_page            = "page_error"; //the error page showed when its selected a page that doesn't exists
APP::$folder_pages          = "app/pages"; //the location of the page files folder
APP::$folder_functions      = "app/functions"; //the location of the functions files folder
APP::$folder_classes	    = "app/classes"; //the location of the classes files folder

//set a connection to a database called "main" or whatever name you want to have.
// APP::PDO('main','localhost','main','root','');

//we can set multiple database connections if we need that. Just use a different name for every connection
// APP::PDO('db2','localhost','database2','root','');
// APP::PDO('db3','localhost','database3','root','');
// APP::PDO('backup','localhost','backup','root','');

?>