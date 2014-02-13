<?php

require('../Route.php');


class RouteTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {
		// You can nest as deeply as you want ... But the first Route::To that matches, is the one that'll be called
		// Figured this would allow easy fallback, while being able to override as well
		$routes = Routes(
			//_pattern(':string', 'fallback', 'Controller')->
			// no root method to run
			'parent',
				Routes(
					'child',
						Routes(
							'grandchild', RouteTo::method('TestController', 'folders')
						)->label('third')
				)->label('second'),
			'categories',
				Routes(
					':integer',
						Routes(
							'edit', RouteTo::method('CategoryController', 'edit')
						)->toMethod('CategoryController', 'view')->label('action'),
					'create',
						RouteTo::method('CategoryController', 'create')
					//_pattern(':string', 'invalidPath', 'CategoryController')
				)->toMethod('CategoryController', 'listing')->label('id')
		)->toMethod('TestController', 'index')->label('first');

		$paths = array(
			// Test :root usage, make sure :string isn't triggered instead
			'/' => 'root',
			// Test :string fallback
			/*
			'/fallback' => 'fallback',
			'/123' => 'fallback',
			'/fall/back/' => 'fallback',
			*/

			// Test assigning names to folders in the URL path
			'/parent/child/grandchild' => 'parent child grandchild',

			'/categories' => 'list categories',
			'/categories/' => 'list categories',
			'/categories/invalid' => false,
			'/categories/invalid/' => false,
			'/categories/invalid/path' => false,
			'/categories/create' => 'category creation',
			'/categories/create/' => 'category creation',
			'/categories/123' => 'show category 123',
			'/categories/123/' => 'show category 123',
			'/categories/123/edit' => 'edit category 123',
			'/categories/123/move' => false,

			// Test 404
			// tests doesn't exist, should it fallback or 404?
			/*
			'/tests/404path' => '404',
			'/tests/string' => '404',
			*/
		);

		foreach ($paths as $path => $output) {
			// Remove leading and trailing forward-slash
			$this->assertEquals($output, $routes->dispatch($path));
		}
	}
}




class Controller {
	protected $path;
	public function __construct($path) {
		$this->path = $path;
	}
	// If the controller method doesn't exists, here's our fallback
	// It simply returns the folder names
	public function __call($name, $args) {
		return 'fallback';
	}

	public function four() {
		return '404';
	}
}

class TestController extends Controller {
	public function index() {
		return 'root';
	}
	public function folders($params) {
		return $params->first . ' ' . $params->second . ' ' . $params->third;
	}
}
class CategoryController extends Controller {
	public function create() {
		return 'category creation';
	}
	public function listing() {
		return 'list categories';
	}
	public function view($params) {
		return 'show category ' . $params->id;
	}
	public function edit($params) {
		return $params->action . ' category ' . $params->id;
	}
	public function invalidPath() {
		return 'invalid path: /'. implode('/', $this->path);
	}
}
