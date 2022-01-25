<?php

//write your logical code here
//e.g.

//use a class 
APP::CLASS("NameOfTheClass", "123", "321", "aaa")->a("plm");

//use a function
$body = APP::FUNCTION("testFunction", "Home page content");


//populate the template with datas and show it. 
echo APP::RENDER_TEMPLATE('assets/layout/test/test.html', [
	'title'	=> 'Home page title',
	'body'	=> $body
]);

?>