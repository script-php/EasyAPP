<?php

//write your logical code here



//populate the de template with datas and show it. 
echo APP::RENDER_TEMPLATE('assets/layout/test/test.html', [
	'title'	=> 'Home page title',
	'body'	=> 'Home page content'
]);

?>