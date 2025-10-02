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
	private $router;

    public function __construct($registry) {
		$this->registry = $registry;
		$this->router = $registry->get('router');
		
	}

	public function link(string $route, array $args = [], bool $js = false) {
        // Try to find the best matching route
        $url = $this->findBestMatchingRoute($route, $args);
        
        if ($url === null) {
            // Fall back to the old style if no route matches
            $url = 'index.php?route=' . $route;
            
            if ($args) {
                $url .= '&' . http_build_query($args);
            }
        }

		$url = ltrim($url, '/');
        $url = CONFIG_URL . $url;
        
        return $js ? $url : str_replace('&', '&amp;', $url);
    }


	protected function findBestMatchingRoute($controllerPath, $args) {
		$matchingRoutes = $this->findRoutesForController($controllerPath);
		
		if (empty($matchingRoutes)) {
			return null;
		}

		// Sort routes by specificity (routes with more parameters first)
		usort($matchingRoutes, function($a, $b) {
			return substr_count($b['uri'], '{') - substr_count($a['uri'], '{');
		});

		foreach ($matchingRoutes as $route) {
			$missingParams = $this->getMissingRouteParams($route['uri'], $args);
			
			// If all required parameters are provided, use this route
			if (empty($missingParams)) {
				$url = $this->buildRouteUrl($route['uri'], $args);
				$remainingParams = array_diff_key($args, $this->getRouteParams($route['uri']));
				
				// Always add remaining parameters as query string
				if (!empty($remainingParams)) {
					$url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($remainingParams);
				}
				return $url;
			}
		}

		// If no perfect match, use the first route and append all params as query string
		$firstRoute = reset($matchingRoutes);
		$url = $this->buildRouteUrl($firstRoute['uri'], $args);
		$url .= (strpos($url, '?') === false ? '?' : '&');
		$url .= http_build_query($args);
		
		return $url;
	}

    protected function findRoutesForController($controllerPath) {
        $routes = $this->router->getRoutes();
        $matchingRoutes = [];

        foreach ($routes as $method => $methodRoutes) {
            foreach ($methodRoutes as $uri => $handler) {
                if (is_string($handler) && $handler === $controllerPath) {
                    $matchingRoutes[] = ['uri' => $uri, 'method' => $method];
                }
            }
        }

        return $matchingRoutes;
    }

    protected function getRouteParams($uri) {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $uri, $matches);
        return array_flip($matches[1]);
    }

    protected function getMissingRouteParams($uri, $args) {
        $requiredParams = $this->getRouteParams($uri);
        return array_diff_key($requiredParams, $args);
    }

    protected function buildRouteUrl($uri, $args) {
        foreach ($args as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
        }
        
        // Remove any remaining optional parameters (marked with ?)
        $uri = preg_replace('/\/\{[^}]+\}\?/', '', $uri);
        // Remove any remaining required parameters (shouldn't happen if we checked missing params)
        $uri = preg_replace('/\/\{[^}]+\}/', '', $uri);
        
        return $uri;
    }

}