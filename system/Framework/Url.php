<?php

/**
* @package      Url
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Url {

	private $registry;
	private $rewrite = FALSE;
	private $rewrite_url = [];

    public function __construct($registry) {
		$this->registry = $registry;
	}

	public function link(string $route, array $args = [], bool $js = false) {
		$url ='index.php?route=' . $route;

		if ($args) {
			if (is_array($args)) {
				$url .= '&' . http_build_query($args);
			} else {
				$url .= '&' . trim($args, '&');
			}
		}

		if($this->rewrite) {
			$url = $this->rewrite_url($url);
		}

		$url =  CONFIG_URL . $url;
		if (!$js) {
			return str_replace('&', '&amp;', $url);
		} else {
			return $url;
		}
	}

	private function rewrite_url($url) {
		$rewrite_url = (!empty($this->rewrite_url) ? array_merge(CONFIG_SYSTEM_REWRITE_URL,$this->rewrite_url) : (!empty(CONFIG_SYSTEM_REWRITE_URL) ? CONFIG_SYSTEM_REWRITE_URL : []));
		foreach($rewrite_url as $regex => $rewrite) {
			preg_match('/^'.$regex.'$/',$url,$match);
			if(!empty($match)) {
				return preg_replace("/^" .$regex. "$/", $rewrite, $url);
			}
		}
		return $url;
	}

	public function rewrite($rewrite_url) {
		$this->rewrite = TRUE;
		$this->rewrite_url = $rewrite_url;
	}

}