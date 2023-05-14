<?php

/**
* @package      Response
* @version      v1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

/**
  $data is ARRAY and the framework will transform all IDs in variable ready to use them in template:
  Example:
  
  in controller you set
  $data['something'] = "something";
  
  and in pages/home.html you will use it like that:
  <div><?php echo $something; ?></div>
  
*/
/*
! 1. redirect
$this->response->redirect($url);

! 2. show template
$this->response->setOutput($this->load->view('pages/home.html', $data));

! 3. add headers
$this->response->addHeader('Content-Type: application/json');
$this->response->setOutput(json_encode($json));

! 4. get the output
! useful for plugins which want to add or replace parts or whole output
$output = $this->response->getOutput();
! here do whatever you want with the output before you set it
$this->response->setOutput($output);

! 5. set compression 
$this->response->setCompression(5);
$this->response->setOutput($this->load->view('pages/home.html'));
*/
namespace System\Framework;
class Response {
	private $headers = array();
	private $level = 0;
	private $output;

	public function addHeader($header) {
		$this->headers[] = $header;
	}
	
	public function redirect($url, $status = 302) {
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
		exit();
	}
	
	public function setCompression($level) {
		$this->level = $level;
	}
	
	public function getOutput() {
		return $this->output;
	}
	
	public function setOutput($output) {
		$this->output = $output;
	}
	
	private function compress($data, $level = 0) {
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding) || ($level < -1 || $level > 9)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
	}
	
	public function output() {
		if ($this->output) {
			$output = $this->level ? $this->compress($this->output, $this->level) : $this->output;
			
			if (!headers_sent()) {
				foreach ($this->headers as $header) {
					header($header, true);
				}
			}
			
			echo $output;
		}
	}
}