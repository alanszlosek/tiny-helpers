<?php
/*
Extreme work in progress. The goal is to allow you to:

* Construct a validation hierarchy (with error messages) matching the POST data that you want to process
* Call a method to validate against said POST data
* Request the error messages

*/
/*
Want this to work through a hierarchy, to process a whole post.

$fields = array(
	'strings' => array(
		'en' => array(
			'hash1' => Validation::Pattern(
		)
	)
);

*/
/*
Ideas for updates:

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
class Validator {
	protected $tree;
	
	public $Errors;
	
	// Takes a validation tree
	public function __construct($tree) {
		$this->tree = $tree;
		$this->Errors = array();
	}
	
	public static function Pattern($pattern) {
		return new VPattern($pattern);
	}
	public static function Choice($choices = array(), $default = null) {
		return new VChoice($choices, $default);
	}
	public static function Dollar() {
		return new VPattern('/^([0-9]+.)?[0-9]+$/');
	}
	
	// Does the work of traversing the field name tree
	public function Validate($data, $fallback) {
		$this->Errors = array();
		return $this->ValidateRecursive($this->tree, $data, $fallback);
	}
	
	protected function ValidateRecursive($tree, $data, $fallback) {
		$out = array();
		foreach ($tree as $key => $value) {
			if ($value instanceof V) {
				/*
				return value should be sanitized value, ready to be filled into form,
				or false, indicating an error
				*/
				if (!array_key_exists($key, $data)) {
					// Does the validation rule allow the key to be non-existent?
					if ($value->_nullOk) continue;
					$a = $value->Valid($data[ $key ]);
					if ($a === false) {
						$message = $value->Message();
						if ($message) $this->Errors[] = $message;
						else $this->Errors[] = 'There were errors';
						$out[ $key ] = $fallback[ $key ];
					} else $out[ $key ] = $a;
					continue;
				}
				$a = $value->Valid($data[ $key ]);
				if ($a === false) {
					$message = $value->Message();
					if ($message) $this->Errors[] = $message;
					else $this->Errors[] = 'There were errors';
					$out[ $key ] = $fallback[ $key ];
				} else $out[ $key ] = $a;
			} elseif ($value === true) {
				// copy out ... no validation necessary
				$out[ $key ] = $data[ $key ];
			} else {
				// What if data key no exist?
				$out[ $key ] = $this->ValidateRecursive($value, $data[ $key ], $fallback[ $key ]);
			}
		}
		return $out;
	}
}

class V {
	public $_nullOk = false;
	protected $message = '';
	
	public function NullOk($bool = true) {
		$this->_nullOk = $bool;
		return $this;
	}
	
	public function Trim($bool) {
		$this->_trim = $bool;
	}
	
	public function Message($m = null) {
		if ($m === null) return $this->message;
		$this->message = $m;
		return $this;
	}
}

class VPattern extends V {
	protected $pattern;
	public function __construct($p) {
		$this->pattern = $p;
	}
	// multiple values?
	public function Valid($value) {
		if (preg_match($this->pattern, $value) == 0) {
			return false;
		}
		return $value;
	}
}

class VChoice extends V {
	protected $choices = array();
	protected $default = null;
	public function __construct($choices = array(), $default = null) {
		$this->choices = $choices;
		$this->default = $default;
	}
	
	public function Valid($value) {
		if (in_array($value, $this->choices)) return $value;
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
