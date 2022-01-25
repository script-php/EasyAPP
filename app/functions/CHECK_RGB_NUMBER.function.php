<?php

function CHECK_RGB_NUMBER(int $number = NULL) {
	if(isset($number) && $number >= 0 && $number <= 255) {
		return TRUE;
	}
	return FALSE;
}
	
?>