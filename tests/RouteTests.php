<?php

require('../Router.php');


class RouteTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {
		$stringFallback = Route::toClassMethod('Controller', 'stringFallback');
		$intFallback = Route::toClassMethod('Controller', 'intFallback');

		// You can nest as deeply as you want ... But the first Route::To that matches, is the one that'll be called
		// Figured this would allow easy fallback, while being able to override as well
		$routes = Routes(
			':string', $stringFallback,
			':integer', $intFallback,
			// no root method to run
			'parent',
				Routes(
					'child',
						Routes(
							'grandchild', Route::toClassMethod('TestController', 'folders')
						)->label('third')
				)->label('second'),
			'categories',
				Routes(
					':integer',
						Routes(
							'edit', Route::toClassMethod('CategoryController', 'edit')
						)->toClassMethod('CategoryController', 'view')->label('action'),
					'create',
						Route::toClassMethod('CategoryController', 'create'),
					'func',
						Route::toCallable('callableFunction')
				)->toClassMethod('CategoryController', 'listing')->label('id')
		)->toClassMethod('TestController', 'index')->label('first');

		$paths = array(
			// Test our base route, triggered when the path is empty. Make sure :string isn't triggered instead
			'/' => 'root',

			// Test :string fallback
			'/fallback' => 'stringFallback',
			'/123' => 'intFallback',
			'/fall/back/' => 'stringFallback',

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
			'/categories/func' => 'wee callable'
		);

		foreach ($paths as $path => $output) {
			$this->assertEquals($output, $routes->dispatch($path));
		}
	}
}




class Controller {
	protected $path;
	public function __construct($path) {
		$this->path = $path;
	}

	public function stringFallback() {
		return 'stringFallback';
	}
	public function intFallback() {
		return 'intFallback';
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
}

function callableFunction($params) {
	return 'wee callable';
}
