<?php

require('../Traverse.php');


class TraverseTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {
		$nested = array(
			'ONE' => array(
				'One' => array(
					'one' => 1
				)
			),
			'TWO' => array(
				'Two' => array(
					'two' => 2
				)
			)
		);

		$tests = array(
			1 => array('ONE', 'One', 'one'),
			2 => array('TWO', 'Two', 'two')
		);
		foreach ($tests as $val => $args) {
			$using = $args;
			array_unshift($using, $nested);
			$a = call_user_func_array(array('Traverse', 'arrayTo'), $using);
			$this->assertEquals($val, $a);
		}
	}
}
