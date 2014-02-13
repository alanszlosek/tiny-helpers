<?php
/*
Route - standalone routing engine to dispatch to your controller class methods
Licensed under MIT. See LICENSE.txt for details.
https://github.com/alanszlosek/tiny-helpers

EXAMPLE

URL: http://abc.com/category/123/?offset=2

Make your routes data structure:

	$routes = Routes(
		'category',
			Routes(
				// :integer is an internal alias that matches integers
				// $path array will be passed to OrderController constructor
				// byId() will be called with an object that looks like this JSON: {id:123}
				':integer' => Route::To('OrderController', 'byId', '//id'),
			)
	);

Dispatch by passing the path portion of your URL to dispatch, without the scheme, domain or query string.
Ensure no repeat slashes (/categories//something///) before calling dispatch().

	$path = "/category/123/";
	// dispatch() returns whatever your controller method returns
	echo $routes->dispatch($path);

EXTEND FOR MORE POWER

If you extend the Router class you can even use your route tree to generate navigation for your site.

Extend RouteLeaf if you want to route to something else besides methods or callables.
*/

function Routes() {
	$args = func_get_args();
	$r = new Router($args);
	return $r;
}

class Router {
	// $runnable is intended to be an instance of a RouteLeaf class, with a run() method
	public $runnable;
	public $label;
	public $routes = array();
	public $parent;

	public function __construct($routes) {
		for ($i = 0; $i < sizeof($routes); $i+=2) {
			$key = $routes[$i];
			$route = $routes[$i+1];
			$route->parent = $this;
			$this->routes[$key] = $route;
		}
	}
	
	public function label($label) {
		$this->label = $label;
		return $this;
	}


	/**
	 * This method is called recursively.
	 * It walks $path, traversing $routes alongside until one matches, or there are no more routes
	 */
	public function dispatch($path, $labels = null) {
		// First run
		if ($labels == null) {
			$labels = new stdClass;
			$path = trim($path, '/');
			// Don't explode an empty string ... it does weird things
			$path = ($path ? explode('/', $path) : array());
		}

		// No more path to digest
		if (!$path) {
			// If we have no runnable, it'll return false (404)
			if ($this->runnable instanceof RouteLeaf) {
				return $this->runnable->run($labels);
			} else {
				return false;
			}
		}

		$part = array_shift($path);

		// If the current route level has been given a label, use it to label the current path portion
		if ($this->label) {
			$label = $this->label;
			$labels->$label = $part;
		}

		$route = $this->getRoute($part);
		if (!$route) {
			// No route found, 404 time
			return false;
		}

		// If there is no more path to digest, this next call to dispatch will trigger the runnable
		if ($route instanceof RouteLeaf) {
			return $route->run($labels);
		} else {
			return $route->dispatch($path, $labels);
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
		$this->runnable = Route::toClassMethod($class, $method);
		return $this;
	}
}


// The following 3 classes could use some more elegance
class Route {
	public static function toClassMethod($class, $method) {
		return new RouteLeafClassMethod($class, $method);
	}
	public static function toCallable($callable) {
		return new RouteLeaf($callable);
	}
}

class RouteLeaf {
	public $parent;
	protected $callable;

	public function __construct($callable) {
		$this->callable = $callable;
	}

	// Instantiate and run
	public function run($labels) {
		$callable = $this->callable;
		return $callable($labels);
	}
}

class RouteLeafClassMethod extends RouteLeaf {
	public function __construct($class, $method) {
		parent::__construct(array($class, $method));
	}

	// Instantiate and run
	public function run($labels) {
		$class = $this->callable[0];
		$method = $this->callable[1];
		if (!is_object($class)) { // If we were given a class name, instead of an instance
			$c = new $class(null);
		}
		return $c->$method($labels);
	}
}

