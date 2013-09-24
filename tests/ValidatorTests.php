<?php

require('../Validator.php');

class ValidatorTests extends PHPUnit_Framework_TestCase {
	public function testSimple() {

		$rules = array(
			'not-empty' => Validator::Pattern('/^.+$/'),
                        'not-empty-message' => Validator::Pattern('/^.+$/')->Message('Name is required'),
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
		
		$validator = new Validator($rules);
		$values = $validator->Validate($_POST, $initialValues); // With fallback values

		$errors = array(
			'There were errors',
			'Name is required'
		);
		$this->assertEquals($errors, $validator->Errors);

		// Fallback only spedified for 1 field
		$values2 = array(
			'not-empty' => '',
		);
		$this->assertEquals($values2, $values);

	}

	public function testChoices() {
		$rules = array(
			// POST won't have this field
			'null-test1' => Validator::Choice(array(null, 'A')),
			// POST will have field, but it'll be null
			'null-test2' => Validator::Choice(array(null, 'A')),
			// POST won't have this field
			'test1' => Validator::Choice(array('A', 'B')),
			// POST will have A for this field
			'test2' => Validator::Choice(array('A', 'B')),
			// POST won't have this field, and it has a default
			'test3' => Validator::Choice(array('C', 'D'), 'E'),
			// POST will have this field, but with invalid value, and a default specified
			'test4' => Validator::Choice(array('C', 'D'), 'E'),
		);
		$_POST = array(
			'null-test2' => null,
			'test2' => 'A',
			'test4' => 'A'
		);

		$validator = new Validator($rules);
		// Output will contain permitted values
		$values = $validator->Validate($_POST);

		$errors = array();
		$this->assertEquals($errors, $validator->Errors);

		$values2 = array(
			'null-test1' => null,
			'null-test2' => null,
			'test1' => null,
			'test2' => 'A',
			'test3' => 'E',
			'test4' => 'E',
		);
		$this->assertEquals($values2, $values);
		
	}
}

