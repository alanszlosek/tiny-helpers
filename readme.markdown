Hi There
====

These are standalone classes that play well with your in-house framework, spaghetti code, etc. So far we have:

* Routing engine
* Incremental markup builder with HTML special character preparation (H class)
* Input validator that works on deeply nested arrays of data
* Autoloader
* Package and dependency installer (like PHP Composer)
* ConcurrentExec runs an array of commands in batches, returns the output when they're finished
* StatsD client pushes batches of metrics over UDP

Route class - URL Routing engine
----

Example URL: http://abc.com/category/123?offset=2

Make your route tree using associative arrays.

	$routes = new Route(array(
		'category' => array(
            '__label' => 'module',
            // :integer is an internal alias that matches integers
            ':integer' => array(
                // Specify which controller method to call
                '__to' => 'OrderController->byId',
                '__label' => 'id'
            ),
        )
	));

Do the following to your request URL:

* Remove domain
* Remove query string

Now pass it to dispatch():

	$path = '/category/123';
	// dispatch() returns whatever your controller method returns
	echo $routes->dispatch($path);
    // OrderController will be instantiated, and it's byId() method will be called
    // with an object that looks like this: {module:'category',id:123}

See the unit tests for a more complex example.


H class - Programmatic HTML generation
----

For when you need to build HTML programatically. It helps you build markup, leveraging __toString() to generate the HTML tags. And it can help you with htmlentities/htmlspecialchars.

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


Validate class - Input validation
----

Perhaps you want to validate POST data (maybe you have nested fields with names like "product[1][name]"). Perhaps you need it to play nice with your existing framework or legacy code. Maybe you want the power to construct your own validation functions or regex patters. In that case, Validator is for you.

Check tests/ValidateTests.php for example usage


TinyLoader class - Class autoloader
----

An autoloader. Maps a top-level namespace to a folder. It assumes:

* That all classes within a top-level namespace are contained in the same subfolder tree
    * ie. \Project\Models\First and \Project\Models\Second must have the same Project folder as a parent
* Namespace paths are a mirror of file-system paths:
    * Example: The \MyApplication\Controllers\Base class lives at /some/path/MyApplication/Controllers/Base.php


Installer class - Think PHP Composer
----

My light-on-features version of PHP Composer. Partners with TinyLoader to autoload namespaced classes.


ConcurrentExec - Forks shells to run commands concurrently
----

    $commands = array(
        'a' => 'date',
        'b' => 'ls /',
        'c' => 'sleep 10'
    );
    $run = new \TinyHelpers\ConcurrentExec($commands);
    // Run up to 10 at a time
    $results = $run->run(10);
    /*
    Gives you:
    array(
        'a' => 'Fri Mar  4 10:43:30 EST 2016',
        'b' => 'Applications System ...',
        'c' => ''
    );
    */
