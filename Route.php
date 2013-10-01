<?php
/*
Route - standalone routing engine to dispatch to your controller class methods
Licensed under MIT. See LICENSE.txt for details.
https://github.com/alanszlosek/tiny-helpers

EXAMPLE

Url: http://abc.com/category/123/?offset=2

Make your routes data structure:

$routes = array(
	'category' => array(
		// :integer is an internal alias that matches integers
		// $path array will be passed to OrderController constructor
		// byId() will be called with an object that looks like this JSON: {id:123}
		':integer' => Route::To('OrderController', 'byId', '//id'),
	),
);

You should only pass the path portion of your URL to dispatch. Without the scheme, domain or query string.
Ensure no repeat slashes (/categories//something///) before calling dispatch().

Now dispatch:

$path = "/category/123/";
$router = new Route($routes);
// dispatch() returns whatever your controller method returns
echo $router->dispatch($path);
*/


/**
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
			$this->path = explode('/', trim($path, '/'));
			$path = $this->path;
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
		} elseif (array_key_exists(':root', $routes) && !$part) {
			$route = $routes[':root'];
		}

		// If $route is an array, then we haven't found a dispatch destination yet
		if (is_array($route)) { // more nesting to do, more path to consume
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
	public static function To($class, $method, $namings = null) {
		return new RouteTo($class, $method, $namings);
	}
}
class RouteTo {
	protected $class;
	protected $method;
	protected $namings;
	public function __construct($class, $method, $namings = null) {
		$this->class = $class;
		$this->method = $method;
		$this->namings = $namings;
	}
	public function dispatch($path) {
		$class = $this->class;
		$method = $this->method;
		if (!is_object($class)) { // If we were given a class name, instead of an instance
			$c = new $class($path);
		}
		$named = null;
		if ($this->namings) {
			$named = new stdClass();
			$namings = explode('/', substr($this->namings, 1)); // Remove leading slash
			foreach ($namings as $i => $name) {
				if (!$name) continue;
				$named->$name = $path[ $i ];
			}
		}
		return $c->$method($named);
	}
}

