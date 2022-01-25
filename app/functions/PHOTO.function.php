<?php

function PHOTO($var, $default) {
	GLOBAL $config;
	if($var == NULL || $var == '') {
		$photo = $default;
	}
	else {
		$photo = $var;
	}
	return $photo;
}

?>