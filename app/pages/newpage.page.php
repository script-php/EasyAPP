<?php

//write your logical code here



//populate the de template with datas and show it. 
echo APP::RENDER_TEMPLATE('assets/layout/test/test.html', [
	'title'	=> 'new page title',
	'body'	=> 'new page content'
]);

?>