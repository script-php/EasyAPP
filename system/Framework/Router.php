<?php
/**
* @package      Router
* @version      1.0.0
* @author       YoYo
* @copyright    Copyright (c) 2022, script-php.ro
* @link         https://script-php.ro
*/

namespace System\Framework;
final class Router {
	private $registry;
	private $pre_action = array();
	private $error;
	public $request;
	
	public function __construct() {
		$this->registry = new Registry();

        $this->request = $this->registry->get('request');
		if (CONFIG_PRE_ACTION) {
			foreach (CONFIG_PRE_ACTION as $value) {
				$this->addPreAction(new Action($value));
			}
		}
		$this->dispatch(new Action(CONFIG_ACTION_ROUTER), new Action(CONFIG_ACTION_ERROR));

		$this->registry->get('response')->output();
	}
	
	public function addPreAction(Action $pre_action) {
		$this->pre_action[] = $pre_action;
	}
	
	public function dispatch(Action $action, Action $error) {
		foreach ($this->pre_action as $pre_action) {
			$result = $this->execute($pre_action);
			if ($result instanceof Action) {
				$action = $result;
				break;
			}
		}
		if (isset($this->request->get['route'])) {
			$query = preg_replace('/[^a-zA-Z0-9_\/\|]/', '', (string)$this->request->get['route']);
			$query_exploded = explode('|', $query); // explode it 
			$route = (!empty($query_exploded[0]) && $query != NULL) ? $query_exploded[0] : '';
			$file = CONFIG_DIR_CONTROLLER . "{$route}.php";
			$query = is_file($file) ? $query : $error->route;
			$action = new Action($query); ###
		}
        while ($action instanceof Action) {
            $action = $this->execute($action);
        }
	}
	
	private function execute(Action $action) {
		$result = $action->execute($this->registry);
		if ($result instanceof Action) {
			return $result;
		} 
		if ($result instanceof Exception) {
			$action = $this->error;
			$this->error = null;
			return $action;
		}
	}
}

