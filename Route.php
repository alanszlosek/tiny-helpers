<?php
/*
Route - standalone routing engine to dispatch to your controller class methods
Licensed under MIT. See LICENSE.txt for details.
https://github.com/alanszlosek/tiny-helpers

Routes look like this:

// No slashes in array keys. This expects that you split on forward slashes,
// When you dispatch, you pass an array of folder names from the URL
$routes = array(
	'help' => array(
		'contact' => Route::To('ContactController', 'contact'),
	),
	'order' => array(
		// An internal alias ... Modify Route and make your own if you need regex patterns
		':integer' => Route::To('OrderController', 'byId'),
	),
);
*/


/**
 * I know regex would be more powerful, but I figured if you needed more complex matching, you could create more fallbacks
 * Not sure how or whether this should address GET vs POST
 */

class Route {
	protected $routes;
	protected $four;
	protected $path;

	// Pass your own Route::To if you want custom 404 support
	public function __construct($routes, $four = null) {
		$this->routes = $routes;
		$this->four = $four;
	}
	/**
	 * This method is called recursively.
	 * It walks $path, traversing $routes alongside until one matches, or there are no more routes
	 */
	public function dispatch($path, $routes = array()) {
		// First run
		if (!$routes) {
			$this->path = $path;
			$routes = $this->routes;
		}

		$part = array_shift($path);
		$route = null; // assigned when we match a folder to a route

		if (array_key_exists($part, $routes)) {
			$route = $routes[ $part ];
		// numeric catch-all
		} elseif (array_key_exists(':integer', $routes) && preg_match('/^[0-9]+$/', $part)) {
			$route = $routes[':integer'];
		// string catch-all
		} elseif (array_key_exists(':string', $routes)) {
			$route = $routes[':string'];
		/*
		// Make your own alias like so:
		} elseif (array_key_exists(':num-alpha', $routes) && preg_match('/[0-9]+\-[a-z]+/i', $part)) {
			// Boom

		*/
		} elseif (array_key_exists(':root', $routes) && $part == '') {
			$route = $routes[':root'];
		}

		// If $route is an array, then we haven't found a dispatch destination yet
		if (is_array($route) && $path) { // more nesting to do, more path to consume
			return $this->dispatch($path, $route);
		} elseif ($route instanceof RouteTo) {
			// Run the controller
			return $route->dispatch($this->path);
		} else {
			// 404
			if ($this->four instanceof RouteTo) return $this->four->dispatch($path);
		}
		return null;
	}

	/**
	 * Use this within your routes data structure
	 */
	public static function To($class, $method) {
		return new RouteTo($class, $method);
	}
}
class RouteTo {
	protected $class;
	protected $method;
	public function __construct($class, $method) {
		$this->class = $class;
		$this->method = $method;
	}
	public function dispatch($path) {
		$class = $this->class;
		$method = $this->method;
		$c = new $class();
		return $c->$method($path);
	}
}

