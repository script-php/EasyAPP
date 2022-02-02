<?php

//use this file if you have some code that should run on every page
//example: we assume that we need to know if we are logged or not on all the pages from our site

$logged = APP::FUNCTION("logged"); //we use a function called "logged" to check if we are logged or not

// we use APP::VAR() function to declare a variable instead of classic variables
// why!? because the APP class can't read external variables and we can't use them in our file pages if we need that
if($logged) {
	APP::VAR('we_are_logged', true);	
}
else {
	APP::VAR('we_are_logged', false);
}




?>