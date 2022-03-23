<?php
//write your page logical code here
//example:

// prevent csrf on get
if(!APP::GET_CSRF()) {
	// posible not safe 
}

// prevent csrf on post
if(APP::POST_CSRF()) {
	// 
}

/*
How to use a database:
$users = APP::QUERY("main", "SELECT * FROM users WHERE validated='1' AND active=:active", [
	':active'		=> '1',
	':something'	=> 'something'
]);

How to use a different database:
$files = APP::QUERY("db2", "SELECT * FROM files");
*/

// all classes from classes folder are autoloaded, ready to be used
$class = new NameOfTheClass("123", "321", "aaa");
$class->a("using a class");

$aaaa = new aaaa();
$aaaa->aaaa();

echo staticClass::a("test static class<br/>");

//use a function
$body = APP::FUNCTION("testFunction")("Home page content");

// do something for logged users
$message = "We are not logged.";
if(APP::VAR('we_are_logged')) {
	$message = "We are logged on our account";
}

//populate the template with datas and show it. 
echo APP::HTML('app/layout/test/test.html', [
	'title'	=> 'Home page title',
	'body'	=> $body . ' / ' . $message
]);
?>