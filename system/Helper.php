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