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

function Routes($method = null, $class = null)
{
    /*
    $args = func_get_args();

    // If we pass an instance of Route ... it's a sub-route. otherwise it's probably a callable
    $route = null;
    foreach ($params as $arg) {
        if ($arg instanceof Route) {
            $route = $arg;
            break;
        }
        $args[] = $arg;
    }
    if (!$route) {
        $route = Routes();
    }
    if ($args) {
        $route->run(new RouteRun($args[0], $ars[1]));
    }
    */

    $r = new Route();
    if ($method && $class) {
        $r->runnable(new RouteRun($class, $method));
    }

    return $r;
}

class Route
{
    // This controller and method get called if we've digested all of the path
    protected $runnable;
    protected $label;

    public $parent;
    protected $routes = array();
    //protected $path;

    public function __construct($parent = null)
    {
        $this->parent = $parent;
    }

    // could use reflection and __call and __staticCall to automate this instantiation
    public function run($labels)
    {
        if (!$this->runnable) {
            // 404
            return false;
        }

        return $this->runnable->run($labels);
    }
    public function runnable($r)
    {
        $this->runnable = $r;

        return $this;
    }

    public function _label($label = null)
    {
        if ($label == null) return $this->label;
        $this->label = $label;

        return $this;
    }

    // bah, for now
    public function _pattern($name, $method, $class)
    {
    }

    // If we aren't setting a method+controller, assume we're going to create sub-routes, so return the new child
    public function __get($name)
    {
        $class = __CLASS__;

        return $this->routes[ $name ] = new $class($this);
    }
    public function __call($name, $params)
    {
        if ($params[0] instanceof Route) {
            $this->routes[ $name ] = $params[0];
            // One day this will help with route delegation ... get full path from parent Route classes
            $this->parent = $this;
        } else {
            $this->routes[ $name ] = new RouteRun($params[1], $params[0]);
        }

        return $this;
    }


    /**
     * This method is called recursively.
     * It walks $path, traversing $routes alongside until one matches, or there are no more routes
     */
    public function dispatch($path, $labels = null)
    {
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
        $label = $this->_label();
        if ($label) {
            $labels->$label = $part;
        }

        $route = $this->getRoute($part);
        if (!$route) {
            // No route found, 404 time
            return false;
            //if ($this->four instanceof RouteTo) return $this->four->dispatch($path);
        }

        // If there is no more path to digest, this next call to dispatch will trigger the runnable
        if ($route instanceof RouteRun) {
            return $route->run($labels);
        } else {
            return $route->dispatch($path, $labels);
        }
    }

    // Can override this to handle patterns, etc
    protected function getRoute($key)
    {
        $route = $this->routes[ $key ];
        if (isset($route)) {
            return $route;
        }
        //elseif ($key == '_integer' && preg_match('/^[0-9]+$/', $part)) {
        return null;

        /*
        // numeric catch-all
        } elseif (array_key_exists(':integer', $routes) && preg_match('/^[0-9]+$/', $part)) {
            $route = $routes[':integer'];
        // string catch-all
        } elseif (array_key_exists(':string', $routes) && strlen($part)) {
            $route = $routes[':string'];
        */
    }

}

// Need to define this interface
class RouteRun
{
    protected $class;
    protected $method;

    public function __construct($class, $method)
    {
        $this->class = $class;
        $this->method = $method;
    }

    // Instantiate and run
    public function run($labels)
    {
        $class = $this->class;
        $method = $this->method;
        if (!is_object($class)) { // If we were given a class name, instead of an instance
            $c = new $class($path);
        }

        return $c->$method($labels);
    }
}
