<?php

/*
Totally unnecessary, but fun.
If you've got a deeply nested array, you can access an element without a bunch of bracket syntax.

$nested = array(
	'ONE' => array(
		'One' => array(
			'one' => 'Hey'
		),
	),
	'TWO' => array(
		'Two' => array(
			'two' => 'There'
		),
	),
);

$help = new ArrayTraverser($nested);
$element = $help->at('TWO', 'Two', 'two');


*/

class ArrayTraverser {
	protected $data = array();
	public function __construct($data = array()) {
		$this->data = $data;
	}
	public function at() {
		$args = func_get_args();
		$values = $this->data;
		while ($args) {
			$key = array_shift($args);
			$values = $values[ $key ];
		}
		return $values;
	}
}
