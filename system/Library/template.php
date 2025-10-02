<?php

/**
 * @package      Template Library
 * @author       script-php.ro
 * @link         https://script-php.ro
 * @copyright    Copyright (c) 2022-present, script-php.ro
 * @license      https://script-php.ro/license
 * @version      1.0
 */

class LibraryTemplate extends Library {

	private $metas = []; // Array of meta tags
	private $keywords; // Meta keywords
	private $base; // Base template
    private $title; // Page title
    private $description; // Meta description
    private $body; // Main body content
    private $styles = []; // Array of style tags
    private $scripts = []; // Array of script tags
    private $links = []; // Array of link tags
	private $data = []; // Additional data for the template

	// Constructor
	function __construct($registry) {
		parent::__construct($registry);
		$this->base = '';
		$this->title = '';
		$this->description = '';
		$this->body = '';
		$this->styles = [];
		$this->scripts = [];
		$this->links = [];
		$this->data = [];
	}

	/* Main method to render the template */
	function output($data=[]) {

		$template = $this->load->library('template');

		$this->data['base'] = $this->base;
		$this->data['title'] = $this->title;
		$this->data['description'] = $this->description;
		$this->data['keywords'] = $this->keywords;
		$this->data['body'] = $this->body;
		$this->data['styles'] = $this->styles;
		$this->data['scripts'] = $this->scripts;
		$this->data['links'] = $this->links;
		$this->data['body'] = $this->body;
		$this->data = array_merge($this->data, $data);
		
		$this->response->setOutput($this->base ? $this->load->view($this->base, $this->data) : '');

	}

	/* Setters and Getters */

	/**
	 * Set base template
	 */
	function base($base) {
		$this->base = !empty($base) ? $base : '';
	}

	/**
	 * Set page title
	 */
	function title($title) {
		$this->title = !empty($title) ? $title : '';
	}

	/**
	 * Set meta description
	 */
	function description($description) {
		$this->description = !empty($description) ? $description : '';
	}

	/**
	 * Set meta keywords
	 */
	function keywords($keywords) {
		$this->keywords = !empty($keywords) ? $keywords : '';
	}

	/**
	 * Set main body content
	 */
	function body($body) {
		$this->body = !empty($body) ? $body : '';
	}

	/**
	 * Add a link tag
	 */
	function addLink($href, $rel = '', $type = '') {
		$href = !empty($href) ? $href : '';
		$rel = !empty($rel) ? $rel : 'stylesheet';
		$type = !empty($type) ? $type : 'text/css';
		$this->links[] = [
			'href' => $href,
			'rel' => $rel,
			'type' => $type
		];
	}

	/**
	 * Add a meta tag
	 */
	function addMeta($name, $content) {
		$name = !empty($name) ? $name : '';
		$content = !empty($content) ? $content : '';
		$this->metas[] = [
			'name' => $name,
			'content' => $content
		];
	}

	/**
	 * Add a style tag
	 */
	function addStyle($href, $media = '', $rel = '') {
		$href = !empty($href) ? $href : '';
		$media = !empty($media) ? $media : 'all';
		$rel = !empty($rel) ? $rel : 'stylesheet';
		$this->styles[] = [
			'href' => $href,
			'rel' => $rel,
			'media' => $media
		];
	}

	/**
	 * Add a script tag
	 */
	function addScript($src, $defer = false) {
		$src = !empty($src) ? $src : '';
		$this->scripts[] = [
			'src' => $src,
			'defer' => $defer
		];
	}

	/**
	 * Set additional data for the template
	 */
	function setData($key, $value) {
		$this->data[$key] = $value;
	}

	/* End of Setters */

	/* Getters */

	/**
	 * Get base template
	 */
	function getBase() {
		return $this->base;
	}

	/**
	 * Get page title
	 */
	function getTitle() {
		return $this->title;
	}

	/**
	 * Get meta description
	 */
	function getDescription() {
		return $this->description;
	}

	/**
	 * Get meta keywords
	 */
	function getMetas() {
		return $this->metas;
	}

	/**
	 * Get style tags
	 */
	function getStyles() {
		return $this->styles;
	}

	/**
	 * Get script tags
	 */
	function getScripts() {
		return $this->scripts;
	}

	/**
	 * Get main body content
	 */
	function getBody() {
		return $this->body;
	}

	/**
	 * Get additional data
	 */
	function getData() {
		return $this->data;
	}

	/**
	 * Get link tags
	 */
	function getLinks() {
		return $this->links;
	}

	/* End of Getters */

}