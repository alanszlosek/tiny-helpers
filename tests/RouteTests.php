<?php

require('../Route.php');


class RouteTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {
		$fourZeroFour = Route::To('Controller', 'four');
		$fallback = Route::To('FallbackController', 'get');

		// You can nest as deeply as you want ... But the first Route::To that matches, is the one that'll be called
		// Figured this would allow easy fallback, while being able to override as well
		$r = array(
			'"' => $fallback,
			'test' => Route::To('TestController', 'test'),
			'tests' => array(
				'#' => Route::To('TestController', 'id'),
			),
			'fallback' => $fallback,
			'categories' => array(
				'#' => array(
					'edit' => Route::To('CategoryController', 'edit'),
				),
				'create' => Route::To('CategoryController', 'create'),
			)
		);
		$paths = array(
			'fall/back' => 'fall back',
			'test' => 'testing',
			'tests/123' => 'id: 123',
			'tests/404path' => '404',
			'tests/string' => '404',
			'fallback' => 'fallback',
			'123' => '123', // there's a string catch-all, which 123 will match

			'categories' => '404',
			'categories/123' => '404',
			'categories/123/edit' => 'categories 123 edit',
			'categories/create' => 'create category'
		);

		$path = 'test';
		$r = new Route($r, $fourZeroFour);

		foreach ($paths as $path => $output) {
			$this->assertEquals($output, $r->dispatch(explode('/', $path)));
		}
	}
}



class Controller {
	public function __call($name, $args) {
		return implode(' ', $args[0]);
	}

	public function four($path) {
		return '404';
	}
}

class FallbackController extends Controller {
}
class TestController extends Controller {
	public function test($path) {
		return 'testing';
	}
	public function id($path) {
		return 'id: ' . array_pop($path);
	}
}
class CategoryController extends Controller {
	public function create($path) {
		return 'create category';
	}
}


