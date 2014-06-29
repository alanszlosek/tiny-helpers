<?php
namespace TinyHelpers;
/*
How to Use (WIP)
====

* Construct a validation hierarchy (with error messages) matching the POST data that you want to process
* Create a Validator class, passing rules and POST data
* Call Validated() to get valid values, optionally passing an array of fallback values
* Call Errors() or ErrorsByField() get errors, and display them how you want

Features
====

Supports POST with data in nested associative arrays

    $fields = array(
        'strings' => array(
            'en' => array(
                'choice' => Validation::Choice(array('one','two'))
            )
        )
    );

*/

/*

REGARDING INITIAL VALUES AND FALLBACKS/DEFAULTS:

Torn. I'd like to specify default when you specify the rule/pattern/choice/etc. But what does that do to the initial values? Should we also validate the initial values to ensure they're still valid? If a default is specified, I'm thinking it should NOT be validated.


IDEAS FOR UPDATES:

- be more specific with API: call sanitizations methods what they are, and validation as well
    - Sanitize::Markup()
    - Sanitize::Trim()
    - Verify::Pattern()
    - Verify::Numeric()
    - Verify::Decimal()
    - Verify::Choice()
*/

// recursively visits all values, constructs an identically nested structure
// with an error array at the top level
class Validate
{
    public static function Pattern($pattern)
    {
        return new VPattern($pattern);
    }
    public static function Choice($choices = array(), $default = null)
    {
        return new VChoice($choices, $default);
    }
    public static function Dollar()
    {
        return new VPattern('/^([0-9]+.)?[0-9]+$/');
    }
}

class Validator
{
    protected $_validated;
    protected $_errors = array();
    protected $_errorsByField = array();

    // Takes a validation tree
    public function __construct($rules, $data)
    {
        $this->ValidateRecursive($rules, $data, true);
    }

    protected function ValidateRecursive($tree, $data, $first = false)
    {
        $out = array(); // Holds valid data
        $errors = array(); // Holds errors, indexed by field
        foreach ($tree as $key => $value) {
            if ($value instanceof V) {
                $input = (array_key_exists($key, $data) ? $data[$key] : null);
                $a = $value->IsValid($input);
                if ($a === false) {
                    $this->_errors[] = $errors[$key][] = $value->Message();
                } else {
                    // We only push valid values to output
                    $out[ $key ] = $a;
                }

            } elseif ($value === true) {
                // copy out ... no validation necessary
                $out[ $key ] = $data[ $key ];

            } else {
                $ret = $this->ValidateRecursive($value, $data[ $key ]);
                $out[ $key ] = $ret[0];
                $errors[ $key ] = $ret[1];
            }
        }
        if ($first) {
            $this->_validated = $out;
            $this->_errorsByField = $errors;
        }
        // Hack to return 2 values
        // So we can build up valid output data AND errors while recursing.
        return array($out, $errors);
    }

    public function Errors()
    {
        return $this->_errors;
    }
    public function ErrorsByField()
    {
        return $this->_errorsByField;
    }
    public function Validated($fallbacks = array())
    {
        if ($fallbacks) return array_replace_recursive($fallbacks, $this->_validated);
        return $this->_validated;
    }
}

abstract class V
{
    protected $message = 'There were errors';
    protected $_trim = false;

    public function Trim($bool)
    {
        $this->_trim = $bool;

        return $this;
    }

    public function Message($m = null)
    {
        if ($m === null) return $this->message;
        $this->message = $m;

        return $this;
    }

    // Implement this
    abstract public function IsValid($value);
}

class VPattern extends V
{
    protected $pattern;
    public function __construct($p)
    {
        $this->pattern = $p;
    }
    // multiple values?
    public function IsValid($value)
    {
        if (preg_match($this->pattern, $value) == 0) {
            return false;
        }

        return $value;
    }
}

class VChoice extends V
{
    protected $choices = array();
    /*
    Since the goal of this class is to validate textual input, null doesn't make sense.
    If default is null, then no default has been specified.
    */
    protected $default = null;
    public function __construct($choices = array(), $default = null)
    {
        $this->choices = $choices;
        $this->default = $default;
    }

    public function IsValid($value)
    {
        if (in_array($value, $this->choices)) return $value;
        if ($this->default === null) return false;
        return $this->default;
    }
}
