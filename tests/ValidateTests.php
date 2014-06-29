<?php
namespace TinyHelpers\tests;

require '../src/TinyHelpers/Validate.php';

use \TinyHelpers\Validate;
use \TinyHelpers\Validator;

class ValidateTests extends \PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $rules = array(
            'not-empty' => Validate::Pattern('/^.+$/'),
                        'not-empty-message' => Validate::Pattern('/^.+$/')->Message('Name is required'),
        );
        $_POST = array(
            'not-empty' => '',
            //'not-empty-message' => ''
        );

        $validator = new Validator($rules, $_POST);

        $errors = array(
            'There were errors',
            'Name is required'
        );
        $this->assertEquals($errors, $validator->Errors());

        // Fall back to these if there's an error ... they should get squashed if a field passes validation
        $initialValues = array(
            'not-empty' => '',
        );
        $values = $validator->Validated($initialValues);
        $values2 = array(
            'not-empty' => '',
        );
        $this->assertEquals($values2, $values);

    }

    public function testChoices()
    {
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

    public function testNested()
    {
        $rules = array(
            'strings' => array(
                'en' => array(
                    'title' => Validate::Choice(array('one', 'two')),
                    // Pass-through rule ... all input data is valid
                    'summary' => true
                ),
                'es' => array(
                    'title' => Validate::Choice(array('one', 'two'))
                )
            )
        );
        $_POST = array(
            'strings' => array(
                'en' => array(
                    'title' => 'four',
                    'summary' => 'Test'
                ),
                'es' => array(
                    'title' => 'three'
                )
            )
        );

        $validator = new Validator($rules, $_POST);

        $errors = array(
            'There were errors',
            'There were errors',
        );
        $this->assertEquals($errors, $validator->Errors());

        $fallback = array(
            'strings' => array(
                'en' => array(
                    'summary' => 'Old Summary'
                ),
                'es' => array(
                    'summary' => 'Spanish'
                ),
            )
        );
        $values = array(
            'strings' => array(
                'en' => array(
                    'summary' => 'Test'
                ),
                'es' => array(
                    'summary' => 'Spanish'
                )
            )
        );
        $this->assertEquals($values, $validator->Validated($fallback));

    }
}
