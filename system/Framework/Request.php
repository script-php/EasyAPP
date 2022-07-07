<?php


namespace System\Framework;
class Request {
	public $get = array();
	public $post = array();
	public $cookie = array();
	public $files = array();
	public $server = array();
	public $ip;
    public $registry;

	public function __construct($registry) {
        $this->registry = $registry;
		$this->get = $this->clean($_GET);
		$this->post = $this->clean($_POST);
		$this->request = $this->clean($_REQUEST);
		$this->cookie = $this->clean($_COOKIE);
		$this->files = $this->clean($_FILES);
		$this->server = $this->clean($_SERVER);
        $this->ip = $this->ip();
	}
	
	public function clean($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				unset($data[$key]);

				$data[$this->clean($key)] = $this->clean($value);
			}
		} else {
			$data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
		}

		return $data;
	}

    private function ip() {
		return $this->server['HTTP_CLIENT_IP'] ?? $this->server["HTTP_CF_CONNECTING_IP"] ?? $this->server['HTTP_X_FORWARDED'] ?? $this->server['HTTP_X_FORWARDED_FOR'] ?? $this->server['HTTP_FORWARDED'] ?? $this->server['HTTP_FORWARDED_FOR'] ?? $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
	}

    public function csrf($method='get') {

        $util = $this->registry->get('util');

        $request_method = strtolower($this->server['REQUEST_METHOD']);
        $method = strtolower($method);

		if ($request_method===$method) {

            if($method == 'get') {
                $origin = !empty($this->server['HTTP_REFERER']) ? $this->server['HTTP_REFERER'] : NULL;
            }
            else if($method == 'post') {
                $origin = !empty($this->server['HTTP_ORIGIN']) ? $this->server['HTTP_ORIGIN'] : NULL;
            }

			$hostname = !is_null($this->server['HTTP_HOST']) ? $this->server['HTTP_HOST'] : NULL;
			if($origin != NULL && $util->contains($origin,$hostname)) {
				return true;
			}

		}
		return false;
	}

}