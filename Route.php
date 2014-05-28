<?php

namespace Route;

/*
Route - standalone routing engine that dispatches to controllers
Licensed under MIT. See LICENSE.txt for details.
https://github.com/alanszlosek/tiny-helpers

EXAMPLE

URL: http://abc.com/category/123/?offset=2

Make your routes data structure. Routes() accepts parameters in pairs. The first matches a URL folder path, the second is the configuration for that folder.

	$routes = new Route(array(
		'category' => array(
			// :integer is an internal alias that matches integers
			// byId() will be called with an object that looks like this JSON: {id:123}
			':integer' => array(
				'__to' => 'OrderController->byId',
				'__label' => 'id'
			)
		)
	));

Dispatch by passing the path portion of your URL to dispatch, without the scheme, domain or query string.
Ensure no repeat slashes (/categories//something///) before calling dispatch().

	$path = "/category/123/";
	// dispatch() returns whatever your controller method returns
	echo $routes->dispatch($path);
*/

// Functionality common to the Router and Route classes
// Meant to be the base class for route tree leaves. They do the handoff from Router to the rest of your application
class Route {
	public $parent;
	public $routes = array();

	public function __construct($routes = array()) {
        $this->routes = $routes;
	}

	/*
	Extend the Route class if you need the ability to dispatch to functions or static methods
	*/
	protected function handoff($to, $labels) {
		// We assume handoff to an MVC style controller
		list($class, $method) = explode('->', $to);
		$o = new $class();
		return $o->$method($labels);
	}

	/**
	 * This method is called recursively.
	 * It walks $path, follwing each node from the $routes tree that matches it
	 */
	public function dispatch($path) {
		// First run, $path will be a string
		$path = trim($path, '/');
		// Start label object
		$labels = new \stdClass;
		$labels->__routerInstance = $this;
		$labels->__pathString = $path;
		// Don't explode an empty string ... it does weird things
		$path = ($path ? explode('/', $path) : array());
		$labels->__pathArray = $path;

		return $this->recursiveDispatch($path, $this->routes, $labels);
	}

	protected function recursiveDispatch($path, $routes, $labels) {
		// No more path to digest
		if (!$path) {
			if (isset($routes['__to'])) {
				return $this->handoff($routes['__to'], $labels);
			} else {
				return false;
			}
		}

		$part = array_shift($path);
		$route = $this->getRoute($part, $routes);
		if (!$route) {
			// No route found, 404 time
			return false;
		}

		// If the current route level has been given a label, use it to label the current path portion
		if (isset($route['__label'])) {
			$label = $route['__label'];
			$labels->$label = $part;
		}

		// If there is no more path to digest, this next call to dispatch will trigger the callable
		return $this->recursiveDispatch($path, $route, $labels);
	}


	// Can override this to handle additional patterns
	protected function getRoute($key, $routes) {
		// Make sure not requesting a key prefixed with '__'
		// Those keys are for Route internals
		if (substr($key, 0, 2) == '__') {
			return null;
		}
		if (isset($routes[ $key ])) {
			return $routes[ $key ];
		} elseif (isset($routes[':integer']) && preg_match('/^[0-9]+$/', $key)) {
			return $routes[':integer'];
		} elseif (isset($routes[':string']) && strlen($key)) {
			return $routes[':string'];
		}
		return null;
	}
}

