<?php

namespace System\Framework;

use System\Framework\Exceptions\RouteNotFound;

class Router {
    protected $registry;
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];
    protected $patterns = [];
    protected $currentRoute;
    protected $params = [];
    protected $fallbackHandler = null;

    public function __construct(Registry $registry) {
        $this->registry = $registry;
    }

    public function pattern($key, $pattern) {
        $this->patterns[$key] = $pattern;
        return $this;
    }

    public function get($uri, $handler) {
        return $this->addRoute('GET', $uri, $handler);
    }

    public function post($uri, $handler) {
        return $this->addRoute('POST', $uri, $handler);
    }

    public function put($uri, $handler) {
        return $this->addRoute('PUT', $uri, $handler);
    }

    public function delete($uri, $handler) {
        return $this->addRoute('DELETE', $uri, $handler);
    }

    public function patch($uri, $handler) {
        return $this->addRoute('PATCH', $uri, $handler);
    }

    protected function addRoute($method, $uri, $handler) {
        $this->routes[$method][$uri] = $handler;
        return $this;
    }

    public function fallback($handler) {
        if (!is_callable($handler) && !is_string($handler)) {
            throw new \InvalidArgumentException("Fallback handler must be callable or controller path string");
        }
        
        $this->fallbackHandler = $handler;
        return $this;
    }

    public function dispatch() {
        $request = $this->registry->get('request');
        $method = $request->server['REQUEST_METHOD'] ?? 'GET';
        $uri = $this->getCurrentUri();

        // Check if we have a direct match first
        if (isset($this->routes[$method][$uri])) {
            $this->handleRoute($this->routes[$method][$uri]);
            return;
        }

        // Check for parameterized routes
        foreach ($this->routes[$method] as $route => $handler) {
            if ($this->matchRoute($route, $uri)) {
                $this->handleRoute($handler);
                return;
            }
        }

        // No route found - try fallback if set
        if ($this->fallbackHandler !== null) {
            $this->handleRoute($this->fallbackHandler);
            return;
        }

        // No route found
        throw new RouteNotFound("Route not found: {$uri}");
    }

    protected function getCurrentUri() {
        $request = $this->registry->get('request');
        
        if (!isset($request->get['rewrite'])) {
            $request->get['rewrite'] = '';
        }
        
        return '/' . trim($request->get['rewrite'], '/');
    }

    protected function matchRoute($route, $uri) {
        // Special case for root route
        if ($route === '/' && $uri === '/') {
            return true;
        }

        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) {
            $key = $matches[1];
            return isset($this->patterns[$key]) ? "(?P<{$key}>" . $this->patterns[$key] . ")" : "(?P<{$key}>[^/]+)";
        }, $route);

        $pattern = '@^' . $pattern . '$@';

        if (preg_match($pattern, $uri, $matches)) {
            $request = $this->registry->get('request');
            foreach ($matches as $key => $value) {
                if (!is_numeric($key)) {
                    $this->setParam($key, $value);
                    $this->registry->get('request')->get[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    protected function handleRoute($handler) {
        if (is_callable($handler)) {
            // Pass both registry and router to callbacks
            call_user_func($handler, $this->registry, $this);
            return;
        }
        if (is_string($handler)) {
            $this->registry->get('load')->runController($handler);
            return;
        }
        throw new \InvalidArgumentException("Invalid route handler type");
    }

    public function setParam($key, $value) {
        $this->params[$key] = $value;
        return $this;
    }

    public function getParam($key, $default = null) {
        return $this->params[$key] ?? $default;
    }

    public function getParams() {
        return $this->params;
    }

    public function hasParam($key) {
        return isset($this->params[$key]);
    }

    // Add this to your Router.php
    public function getRoutes() {
        return $this->routes;
    }

}