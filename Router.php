<?php
/*
Route - standalone routing engine that dispatched to callables
Licensed under MIT. See LICENSE.txt for details.
https://github.com/alanszlosek/tiny-helpers

EXAMPLE

URL: http://abc.com/category/123/?offset=2

Make your routes data structure. Routes() accepts parameters in pairs. The first matches a URL folder path, the second is the configuration for that folder.

	$routes = Routes(
		'category', Routes(
			// :integer is an internal alias that matches integers
			// byId() will be called with an object that looks like this JSON: {id:123}
			':integer', Route::toClassMethod('OrderController', 'byId')->label('id')
		)
	);

Dispatch by passing the path portion of your URL to dispatch, without the scheme, domain or query string.
Ensure no repeat slashes (/categories//something///) before calling dispatch().

	$path = "/category/123/";
	// dispatch() returns whatever your controller method returns
	echo $routes->dispatch($path);
*/

// IF YOU WANT TO EXTEND THE Router CLASS, IMPLEMENT THIS FUNCTION YOURSELF TO RETAIN THE SYNTACTIC SUGAR
if (!function_exists('Routes')) {
	function Routes() {
		$args = func_get_args();
		$r = new Router($args);
		return $r;
	}
}

// Functionality common to the Router and Route classes
// Meant to be the base class for route tree leaves. They do the handoff from Router to the rest of your application
class RouteNode {
	/*
	$callable can be:

	array('ClassName', 'methodName')
	array($classInstance, 'methodName')
	'functionName'
	*/
	protected $callable;
	protected $label;
	public $parent;

	public function __construct($callable) {
		$this->callable = $callable;
	}

	public function hasLabel() {
		return isset($this->label);
	}

	public function label($label = null) {
		if ($label == null) return $this->label;
		$this->label = $label;
		return $this;
	}

	// Instantiate and run
	public function run($labels) {
		$callable = $this->callable;
		return $callable($labels);
	}
}

class Router extends RouteNode {
	public $routes = array();

	public function __construct($routes) {
		for ($i = 0; $i < sizeof($routes); $i+=2) {
			$key = $routes[$i];
			$route = $routes[$i+1];
			$route->parent = $this;
			$this->routes[$key] = $route;
		}
	}

	/**
	 * This method is called recursively.
	 * It walks $path, follwing each node from the $routes tree that matches it
	 */
	public function dispatch($path, $labels = null) {
		// First run, $path should be a string
		if (!is_array($path)) {
            // Could use a method here that can be overridden, to make it easier to pass more info to callables
			$labels = new stdClass;
			$labels->__routerInstance = $this;
			$path = trim($path, '/');
			$labels->__pathString = $path;
			// Don't explode an empty string ... it does weird things
			$path = ($path ? explode('/', $path) : array());
			$labels->__pathArray = $path;
		}

		// No more path to digest
		if (!$path) {
			// If we have no callable, it'll return false (404)
			if ($this->callable) {
				return $this->run($labels);
			} else {
				return false;
			}
		}

		$part = array_shift($path);
		$route = $this->getRoute($part);
		if (!$route) {
			// No route found, 404 time
			return false;
		}

		// If the current route level has been given a label, use it to label the current path portion
		if ($route->hasLabel()) {
			$label = $route->label();
			$labels->$label = $part;
		}

		// If there is no more path to digest, this next call to dispatch will trigger the callable
		if ($route instanceof Router) {
			return $route->dispatch($path, $labels);
		} else {
			// If it's not an instance of Router, it SHOULD be an instance of RouteNode
			return $route->run($labels);
		}
	}

	// Can override this to handle additional patterns
	protected function getRoute($key) {
		if (isset($this->routes[ $key ])) {
			return $this->routes[ $key ];
		} elseif (isset($this->routes[':integer']) && preg_match('/^[0-9]+$/', $key)) {
			return $this->routes[':integer'];
		} elseif (isset($this->routes[':string']) && strlen($key)) {
			return $this->routes[':string'];
		}
		return null;
	}


	public function toClassMethod($class, $method) {
		$this->callable = array($class, $method);
		return $this;
	}
}


class Route {
	public static function toClassMethod($class, $method) {
		return new RouteNode(array($class, $method));
	}
	public static function toCallable($callable) {
		return new RouteNode($callable);
	}
}

