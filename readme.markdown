Hi There
====

The goal is to create standalone classes that play well with your in-house framework, spaghetti code, etc.

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


Validator class
----

Perhaps you want to validate POST data (maybe you have nest fields with names like "product[1][name]"). Perhaps you need it to play nice with your existing framework or legacy code. Maybe you want the power to construct your own validation functions or regex patters. In that case, Validator is for you.

Like so:

	// Coming soon
