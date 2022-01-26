<?php

class staticClass {
	
	public static function a($arg) {
		return $arg;
	}
	
	public static function b() {
		return "bb";
	}
	
	private static function c() {
		return 'private';
	}
}