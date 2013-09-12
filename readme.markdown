Hi There
====

H class
----

Maybe you like s-expressions. Or maybe you want to build your markup programatically. Or maybe you're looking for an alternative to the form/input class you cooked up some years ago (been there ... Autoform). In that case, H is for you. It helps you build markup, leveraging __toString() to generate the HTML tags. And it can help you with htmlentities/htmlspecialchars.

Like so:

	<?= H::div(
		H::h1('Title'),
		H::p('Paragraph of text.'),
		H::a('Link text')->href('google.com')->class('link')->title('entities & magic')
	) ?>

Validator class
----

Perhaps you want to validate POST data (maybe you have nest fields with names like "product[1][name]"). Perhaps you need it to play nice with your existing framework or legacy code. Maybe you want the power to construct your own validation functions or regex patters. In that case, Validator is for you.

Like so:

	// Coming soon
