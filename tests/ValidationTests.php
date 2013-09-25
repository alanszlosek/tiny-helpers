<?php

require('../Validate.php');

class ValidationTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {

		$rules = array(
			'not-empty' => Validate::Pattern('/^.+$/'),
                        'not-empty-message' => Validate::Pattern('/^.+$/')->Message('Name is required'),
		);
		$_POST = array(
			'not-empty' => '',
			//'not-empty-message' => ''
		);

		// Fall back to these if there's an error
		$initialValues = array(
			'not-empty' => '',
			//'not-empty-message' => ''
		);
		
		$validator = new Validator($rules, $_POST, $initialValues);
		$values = $validator->Validated();

		$errors = array(
			'There were errors',
			'Name is required'
		);
		$this->assertEquals($errors, $validator->Errors());

		// Fallback only spedified for 1 field
		$values2 = array(
			'not-empty' => '',
		);
		$this->assertEquals($values2, $values);

	}

	public function testChoices() {
		$rules = array(
			// POST won't have this field
			'null-test1' => Validate::Choice(array(null, 'A')),
			// POST will have field, but it'll be null
			'null-test2' => Validate::Choice(array(null, 'A')),
			// POST won't have this field
			'test1' => Validate::Choice(array('A', 'B')),
			// POST will have A for this field
			'test2' => Validate::Choice(array('A', 'B')),
			// POST will have A for this field
			'test3' => Validate::Choice(array('C', 'B')),
			// POST won't have this field, and it has a default
			'test4' => Validate::Choice(array('C', 'D'), 'E'),
			// POST will have this field, but with invalid value, and a default specified
			'test5' => Validate::Choice(array('C', 'D'), 'E'),
		);
		$_POST = array(
			'null-test2' => null,
			'test2' => 'A',
			'test3' => 'A',
			'test5' => 'A'
		);

		$validator = new Validator($rules, $_POST);
		$values = $validator->Validated();

		$errors = array(
			'There were errors',
			'There were errors'
		);
		
		$this->assertEquals($errors, $validator->Errors());

		$values2 = array(
			'null-test1' => null,
			'null-test2' => null,
			'test2' => 'A',
			'test4' => 'E',
			'test5' => 'E',
		);
		$this->assertEquals($values2, $values);
		
	}
}

