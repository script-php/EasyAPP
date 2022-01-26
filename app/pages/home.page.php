<?php

//write your logical code here
//e.g.

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
echo APP::RENDER_TEMPLATE('assets/layout/test/test.html', [
	'title'	=> 'Home page title',
	'body'	=> $body
]);

?>