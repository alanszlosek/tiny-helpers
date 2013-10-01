<?php

require('../Route.php');


class RouteTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {
		$fourZeroFour = Route::To('Controller', 'four');
		$fallback = Route::To('Controller', 'fallback'); // the method doesn't actually exist

		// You can nest as deeply as you want ... But the first Route::To that matches, is the one that'll be called
		// Figured this would allow easy fallback, while being able to override as well
		$r = array(
			':string' => $fallback,
			// Test mapping each folder to a member within a stdClass instance
			'parent' => array(
				'child' => array(
					'grandchild' => Route::To('TestController', 'folders', '/first/second/third')
				),
			),

			'categories' => array(
				':root' => Route::To('CategoryController', 'listing'),
				':integer' => array(
					':root' => Route::To('CategoryController', 'view', '//id'),
					'edit' => Route::To('CategoryController', 'edit', '//id/action'),
				),
				'create' => Route::To('CategoryController', 'create'),
			)
		);
		$paths = array(
			// Test :string fallback
			'/fallback' => 'fallback',
			'/123' => 'fallback',
			'/fall/back/' => 'fallback',

			// Test assigning names to folders in the URL path
			'/parent/child/grandchild' => 'parent child grandchild',


			'/categories' => 'list categories',
			'/categories/' => 'list categories',
			'/categories/all' => '404',
			'/categories/all/' => '404',
			'/categories/create' => 'category creation',
			'/categories/create/' => 'category creation',
			'/categories/123' => 'show category 123',
			'/categories/123/' => 'show category 123',
			'/categories/123/edit' => 'edit category 123',
			'/categories/123/move' => '404',

			// Test 404
			// tests doesn't exist, should it fallback or 404?
			/*
			'/tests/404path' => '404',
			'/tests/string' => '404',
			*/
		);

		$r = new Route($r, $fourZeroFour);

		foreach ($paths as $path => $output) {
			// Remove leading and trailing forward-slash
			$this->assertEquals($output, $r->dispatch(explode('/', trim($path, '/'))));
		}
	}
}



class Controller {
	protected $path;
	public function __construct($path) {
		$this->path;
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
	public function folders($folders) {
		return implode(' ', array($folders->first, $folders->second, $folders->third));
	}
}
class CategoryController extends Controller {
	public function create() {
		return 'category creation';
	}
	public function listing() {
		return 'list categories';
	}
	public function view($named) {
		return 'show category ' . $named->id;
	}
	public function edit($named) {
		return $named->action . ' category ' . $named->id;
	}
}


