<?php
/*
How to Use (WIP)
====

* Construct a validation hierarchy (with error messages) matching the POST data that you want to process
* Call a method to validate against said POST data
* Valid values will be returned (using initial values, or default if specified)
* Check whether errors were generated during validation, and display them how you want

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
class Validate {
	
	public static function Pattern($pattern) {
		return new VPattern($pattern);
	}
	public static function Choice($choices = array(), $default = null) {
		return new VChoice($choices, $default);
	}
	public static function Dollar() {
		return new VPattern('/^([0-9]+.)?[0-9]+$/');
	}
}

class Validator {
	protected $_out;
	protected $_errors = array();
	protected $_errorsByField = array();
	
	// Takes a validation tree
	public function __construct($rules, $data, $fallback = array()) {
		$this->ValidateRecursive($rules, $data, $fallback, true);
	}

	protected function ValidateRecursive($tree, $data, $fallback = array(), $first = false) {
		$out = array();
		$errors = array();
		foreach ($tree as $key => $value) {
			if ($value instanceof V) {
				/*
				return value should be sanitized value, ready to be filled into form,
				or false, indicating an error
				*/
				if (!array_key_exists($key, $data)) {
					// Try validation against null
					$a = $value->IsValid(null); //$data[ $key ]);
					if ($a === false) {
						$this->_errors[] = $errors[$key][] = $value->Message();
						if (array_key_exists($key, $fallback)) {
							$out[ $key ] = $fallback[ $key ];
						}
					} else $out[ $key ] = $a;
					continue;
				}
				/*
				Same as above, but I feel I can roll the fallback into the Valid() call,
				since Choice accepts a default value. But default and initial values ... confusing how they interact with
				one-another.
				*/
				$a = $value->IsValid($data[ $key ]);
				if ($a === false) {
					$this->_errors[] = $errors[$key][] = $value->Message();
					if (array_key_exists($key, $fallback)) {
						$out[ $key ] = $fallback[ $key ];
					}
				} else $out[ $key ] = $a;
			} elseif ($value === true) {
				// copy out ... no validation necessary
				$out[ $key ] = $data[ $key ];
			} else {
				// What if data key no exist?
				$ret = $this->ValidateRecursive($value, $data[ $key ], $fallback[ $key ]);
				$out[ $key ] = $ret[0];
				$errors[ $key ] = $ret[1];
			}
		}
		if ($first) {
			$this->_out = $out;
			$this->_errorsByField = $errors;
		}
		return array($out, $errors);
	}

	public function Errors() {
		return $this->_errors;
	}
	public function ErrorsByField() {
		return $this->_errorsByField;
	}
	public function Validated() {
		return $this->_out;
	}
}

abstract class V {
	protected $message = 'There were errors';
	protected $_trim = false;
	
	public function Trim($bool) {
		$this->_trim = $bool;
		return $this;
	}
	
	public function Message($m = null) {
		if ($m === null) return $this->message;
		$this->message = $m;
		return $this;
	}

	// Implement this
	public abstract function IsValid($value);
}

class VPattern extends V {
	protected $pattern;
	public function __construct($p) {
		$this->pattern = $p;
	}
	// multiple values?
	public function IsValid($value) {
		if (preg_match($this->pattern, $value) == 0) {
			return false;
		}
		return $value;
	}
}

class VChoice extends V {
	protected $choices = array();
	/*
	Since the goal of this class is to validate textual input, null doesn't make sense.
	If default is null, then no default has been specified.
	*/
	protected $default = null;
	public function __construct($choices = array(), $default = null) {
		$this->choices = $choices;
		$this->default = $default;
	}
	
	public function IsValid($value) {
		if (in_array($value, $this->choices)) return $value;
		if ($this->default === null) return false;
		return $this->default;
	}
}

/*
class Field {
	public function Error() {
		echo 'Error';
	}
}

$field = new Field();

$tree = array(
	'strings' => array(
		'en' => array(
			// probably should be able to point this at an object to alert about an error
			'hash1' => Validation::Pattern('/^ey/', $field)
		)
	)
);
$data = array(
	'strings' => array(
		'en' => array(
			'hash1' => 'Hey'
		)
	)
);

$errors = Validator::Validate($tree, $data);
*/
