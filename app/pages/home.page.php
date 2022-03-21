<?php
//write your page logical code here
//example:

// prevent csrf on get
if(APP::GET_CSRF()) {
	echo 'get ok';
}
else {
	echo 'get not ok';
}

echo '<br/>';

// prevent csrf on post
if(APP::POST_CSRF()) {
	echo 'post ok';
}
else {
	echo 'post not ok';
}

echo '<br/>';


// do something for logged users
if(APP::VAR('we_are_logged')) {
	$message = "We are logged on our account";
}
else {
	$message = "We are not logged.";
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

$class = new NameOfTheClass("123", "321", "aaa");
$class->a("using a class");

$aaaa = new aaaa();
$aaaa->aaaa();

echo staticClass::a("test static class");

echo "<br/>";

//use a function
$body = APP::FUNCTION("testFunction")("Home page content");

//populate the template with datas and show it. 
echo APP::HTML('app/layout/test/test.html', [
	'title'	=> 'Home page title',
	'body'	=> $body . ' / ' . $message
]);
?>