<?php
//write your logical code here
//example:



// do something for logged users
if(APP::VAR('logged')) {
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



//use a class 
$class = APP::CLASS("NameOfTheClass", "123", "321", "aaa");
$class->a("using a class");
echo '<br/>';



//use a static class
echo APP::CLASS("staticClass")::a("test static class");
echo '<br/>';



//use a function
$body = APP::FUNCTION("testFunction", "Home page content");



//populate the template with datas and show it. 
echo APP::HTML('assets/layout/test/test.html', [
	'title'	=> 'Home page title',
	'body'	=> $body . ' / ' . $message
]);
?>