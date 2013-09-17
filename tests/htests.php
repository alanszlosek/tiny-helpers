<?php

require('../H.php');

class HTests extends PHPUnit_Framework_TestCase {

	public function testSimple() {
		// markup
		$a = array(
			'<DIV></DIV>',
			'<DIV>test &amp; entities</DIV>',
			'<DIV class="a"><P>test</P></DIV>',
			'<DIV class="a"><P>test</P></DIV>'
		);
		// objects
		$b = array(
			H::div(),
			H::div('test & entities'),
			H::div(
				H::p('test')
			)->class('a'),
			H::div()->class('a')->children(array( H::p('test') ))
		);
		foreach ($a as $i => $item) {
			$this->assertEquals($item, '' . $b[ $i ]);
		}
	}

	public function testFormInputs() {
	}
}


// echo H::div();
/*

$lis = H::li(
	// Maybe T::value() should accept a closure ... but would that be overkill
	T::value()
);

$a = H::ol(
	H::each(
		array('Zero', 'One'),
		$lis
	),
	H::each(
		array('Three', 'Four'),
		$lis
	)
);
//var_dump($a);exit;
echo $a;

*/
