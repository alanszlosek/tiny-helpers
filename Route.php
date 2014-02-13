<?php
/*
Route - standalone routing engine to dispatch to your controller class methods
Licensed under MIT. See LICENSE.txt for details.
https://github.com/alanszlosek/tiny-helpers

EXAMPLE

Url: http://abc.com/category/123/?offset=2

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

You should only pass the path portion of your URL to dispatch. Without the scheme, domain or query string.
Ensure no repeat slashes (/categories//something///) before calling dispatch().

Now dispatch:

$path = "/category/123/";
// dispatch() returns whatever your controller method returns
echo $routes->dispatch($path);
*/

function Routes() {
	$args = func_get_args();
	$r = new Route($args);
	return $r;
}

class Route {
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
	

	// could use reflection and __call and __staticCall to automate this instantiation
	public function run($labels) {
		if (!$this->runnable) {
			// 404
			return false;
		}
		return $this->runnable->run($labels);
	}

	public function label($label) {
		$this->label = $label;
		return $this;
	}

	public function toMethod($class, $method) {
		$this->runnable = RouteTo::method($class, $method);
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
			return $this->run($labels);
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
			//if ($this->four instanceof RouteTo) return $this->four->dispatch($path);
		}

		// If there is no more path to digest, this next call to dispatch will trigger the runnable
		if ($route instanceof RouteLeaf) {
			return $route->run($labels);
		} else {
			return $route->dispatch($path, $labels);
		}
	}

	// Can override this to handle patterns, etc
	protected function getRoute($key) {
		if (isset($this->routes[ $key ])) {
			return $this->routes[ $key ];
		} elseif (isset($this->routes[':integer']) && preg_match('/^[0-9]+$/', $key)) {
			return $this->routes[':integer'];
		}
		return null;
	}
        /*
	// numeric catch-all
	} elseif (array_key_exists(':integer', $routes) && preg_match('/^[0-9]+$/', $part)) {
		$route = $routes[':integer'];
	// string catch-all
	} elseif (array_key_exists(':string', $routes) && strlen($part)) {
		$route = $routes[':string'];
	*/

}

class RouteTo {
	public static function method($class, $method) {
		return new RouteLeaf($class, $method);
	}
}

// Need to define this interface
class RouteLeaf {
	protected $class;
	protected $method;
	public $parent;

    public function __construct($class, $method) {
        $this->class = $class;
        $this->method = $method;
    }

    // Instantiate and run
    public function run($labels) {
		$class = $this->class;
		$method = $this->method;
		if (!is_object($class)) { // If we were given a class name, instead of an instance
			$c = new $class(null);
		}
        return $c->$method($labels);
    }
}

