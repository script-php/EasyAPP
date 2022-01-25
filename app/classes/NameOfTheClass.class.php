<?php

class NameOfTheClass {
	
	public $a = "a";
	public $b = "b";
	public function __construct($aa, $bb) {
		$this->a = $aa;
		$this->b = $bb;
	}

	public function a($plm) {
		echo "a: ".$this->a.", b: ".$this->b;
		echo "<br>";
		echo $plm;
	}

}

?>