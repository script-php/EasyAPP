<?php

/**
 * This file can contain additional functions that
 * you'd like to use throughout your entire application.
 * 
 * We will leve here some functions you can use for debugging your app.
 */


function pre($var, $exit = false) {
	echo "<pre>".print_r($var, true)."</pre>\n";
	if(!empty($exit)) exit();
}