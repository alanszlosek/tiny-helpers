<?php
namespace TinyHelpers\Tests;

require '../src/TinyHelpers/Route.php';

class RouteTests extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $stringFallback = 'Controller->stringFallback';
        $intFallback = 'Controller->intFallback';

        /*
        Using a route tree composed purely of associative arrays should save on the memory.
        And having only 1 instance of a Route class at the top-level makes it easy to subclass Route,
        since you only have to instantiate the class once.
        */
        $routes = array(
            '__to' => 'TestController->index',
            ':string' => array('__to' => $stringFallback),
            ':integer' => array('__to' => $intFallback),
            // no root method to run
            'parent' => array(
                '__label' => 'first',
                'child' => array(
                    '__label' => 'second',
                    'grandchild' => array(
                        '__label' => 'third',
                        '__to' => 'TestController->folders'
                    )
                ),
            ),
            'categories' => array(
                '__to' => 'CategoryController->listing',
                ':integer' => array(
                    '__label' => 'id',
                    '__to' => 'CategoryController->view',
                    'edit' => array(
                        '__label' => 'action',
                        '__to' => 'CategoryController->edit',
                    )
                ),
                'create' => array(
                    '__to' => 'CategoryController->create',
                ),
            ),
        );
        // Use base Route class, or your own subclass
        $routes = new Route\Route($routes);

        $paths = array(
            // Test our base route, triggered when the path is empty. Make sure :string isn't triggered instead
            '/' => 'root',

            // Test :string fallback
            '/fallback' => 'stringFallback',
            '/fallback/' => 'stringFallback',
            '/123' => 'intFallback',
            '/123/' => 'intFallback',
            // Will 404
            '/bad/path' => false,
            '/bad/path/' => false,

            // Test assigning names to folders in the URL path
            '/parent/child/grandchild' => 'parent child grandchild',
            // Make sure incomplete paths trigger 404
            '/parent/child' => false,
            '/parent' => false,

            '/categories' => 'list categories',
            '/categories/' => 'list categories',
            '/categories/invalid/long/path' => false,
            '/categories/invalid/long/path/' => false,
            '/categories/create' => 'category creation',
            '/categories/create/' => 'category creation',
            '/categories/123' => 'show category 123',
            '/categories/123/' => 'show category 123',
            '/categories/123/edit' => 'edit category 123',
            '/categories/123/edit/' => 'edit category 123',
            '/categories/123/move' => false,
            '/categories/123/move/' => false,

            // URL paths shouldn't contain internal keys or aliases
            // Make sure they don't match
            '/:integer' => false,
            '/categories/__to' => false,
        );

        foreach ($paths as $path => $output) {
            $this->assertEquals($output, $routes->dispatch($path));
        }
    }
}




class Controller
{
    protected $foo = 'bar';
    public function stringFallback()
    {
        return 'stringFallback';
    }
    public function intFallback()
    {
        return 'intFallback';
    }

    public function four()
    {
        return '404';
    }
}

class TestController extends Controller
{
    public function index()
    {
        return 'root';
    }
    public function folders($params)
    {
        return $params->first . ' ' . $params->second . ' ' . $params->third;
    }
}
class CategoryController extends Controller
{
    public function create()
    {
        return 'category creation';
    }
    public function listing()
    {
        return 'list categories';
    }
    public function view($params)
    {
        return 'show category ' . $params->id;
    }
    public function edit($params)
    {
        return $params->action . ' category ' . $params->id;
    }
}
