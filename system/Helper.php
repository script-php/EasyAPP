<?php

/**
 * This file can contain additional functions that
 * you'd like to use throughout your entire application.
 * 
 * We will leve here some functions you can use for debugging your app.
 */


function pre($var, $exit = false) {
	echo "<pre style='color:white;background:black;padding:15px'>".print_r($var, true)."</pre>\n";
	if(!empty($exit)) exit();
}

function localhost(string $localhost = 'localhost') {
	if($_SERVER['SERVER_NAME'] == $localhost) {
		return true;
	}
	return false;
}

/**
 * Display a simple error page without using the controller system
 * 
 * @param int $statusCode HTTP status code
 * @param string $message Error message
 */
function displayErrorPage($statusCode, $message) {
	$platform = CONFIG_PLATFORM;
	$version = CONFIG_VERSION;
    $errorTemplate = <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error {$statusCode}</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; text-align: center; }
            .error-container { max-width: 600px; margin: 100px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            h1 { color: #d9534f; }
        </style>
    </head>
    <body>
        <div class="error-container">
            <h1>Error {$statusCode}</h1>
            <p>{$message}</p>
            <p><small>{$platform} v{$version}</small></p>
        </div>
    </body>
    </html>
    HTML;
    
    echo $errorTemplate;
}