<?php

/**
 * This file can contain additional functions that
 * you'd like to use throughout your entire application.
 */

/* debug function */ 
function pre($var, $exit = false) {
	echo "<pre>".print_r($var, true)."</pre>\n";
	if(!empty($exit)) exit();
}

?>