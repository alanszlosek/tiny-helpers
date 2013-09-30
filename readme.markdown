Hi There
====

These are standalone classes that play well with your in-house framework, spaghetti code, etc. So far we have:

* Routing engine (Route)
* Incremental markup builder with HTML special character preparation (H)
* Input validator that works on deeply nested arrays of data

Route class
----

Usage:

	Url: http://abc.com/category/123?offset=2

	Make your routes data structure:

	$routes = array(
		'category' => array(
			// :integer is an internal alias that matches integers
			// $path array will be passed to OrderController constructor
			// byId() will be called with an object that looks like this JSON: {id:123}
			':integer' => Route::To('OrderController', 'byId', '//id'),
		),
	);

	Do the following to your request URL:

	* Remove domain
	* Remove leading slash
	* Remove query string
	* Split folder path into an array

	Now, dispatch:

	$path = array('category','123');
	$router = new Route($routes);
	// dispatch() returns whatever your controller method returns
	echo $router->dispatch($path);

See the unit tests for a more complex example.


H class
----

Maybe you like s-expressions. Or maybe you want to build your markup programatically. Or maybe you're looking for an alternative to the form/input class you cooked up some years ago (been there ... Autoform). In that case, H is for you. It helps you build markup, leveraging __toString() to generate the HTML tags. And it can help you with htmlentities/htmlspecialchars.

Like so:

	<?= H::div(
		H::h1('Title'),
		H::p('Paragraph of text.'),
		H::a('Link text')->href('google.com')->class('link')->title('entities & magic')
	) ?>

Experimental looping support:

	$li = H::li(
		T::value()
	);
	$a = H::ol(
		H::each(
			array('Zero', 'One'),
			$li
		),
		H::each(
			array('Three', 'Four'),
			$li
		)
	);
	echo $a;
	// Gives you: <OL><LI>Zero</LI><LI>One</LI><LI>Three</LI><LI>Four</LI></OL>

Form fields:

	$expression = true;
	$options = array(
		'a' => 'A',
		'b' => 'B'
	);
	echo H::input()->type('checkbox')->name('active')->value(1)->attributeIf('checked', 'checked', $expression);
	echo H::select()->name('choice')->value('b')->options($options);


Validate class
----

Perhaps you want to validate POST data (maybe you have nest fields with names like "product[1][name]"). Perhaps you need it to play nice with your existing framework or legacy code. Maybe you want the power to construct your own validation functions or regex patters. In that case, Validator is for you.

Check tests/ValidateTests.php for example usage
