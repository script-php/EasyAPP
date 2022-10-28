<?php

/**
* @package      Url
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;

class Url {

    private $config;
	private $registry;
    private array $rewrite = [];

    public function __construct($registry) {
		$this->registry = $registry;
		$this->config = $registry->get('config');
	}

    public function addRewrite($rewrite): void {
		$this->rewrite[] = $rewrite;
	}

	public function link(string $route, array $args = [], bool $js = false): string {
		$url = $this->config->url . 'index.php?route=' . $route;

		if ($args) {
			if (is_array($args)) {
				$url .= '&' . http_build_query($args);
			} else {
				$url .= '&' . trim($args, '&');
			}
		}

		foreach ($this->rewrite as $rewrite) {
			$url = $rewrite->rewrite($url);
		}

		// if (!$js) {
		// 	return str_replace('&', '&amp;', $url);
		// } else {
			return $url;
		// }
	}

}