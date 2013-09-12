<?php
class H {
	protected $_tag;
	protected $_attributes = array();
	protected $_children = array();
	public function __construct($tag, $children = array()) {
		$this->_tag = strtoupper(substr($tag, 3));
		$this->_children = $children;
		$this->_attributes = array();
	}

	public function __toString() {
		$out = '<' . $this->_tag;
		// escaping
		foreach ($this->_attributes as $key => $value) {
			$out .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
		}
		$out .= '>';
		foreach ($this->_children as $child) {
			/*
			if ($child instanceof H) $out .= $child->__toString();
			else $out .= htmlentities($child);
			*/
			$out .= $child;
		}
		$out .= '</' . $this->_tag . '>';
		return $out;
	}
	public function __call($name, $args) {
		return $this->attribute($name, $args[0]);
		return $this;
	}
	public function __set($name, $value) {
		$this->attribute($name, $value);
	}
	public function __get($name) {
		if ($name == 'tag') return $this->_tag;
		elseif ($name == 'children') return $this->_children;
		elseif ($name == 'attributes') return $this->_attributes;
	}
	// TODO
	public function __clone() {
	}

	public function attribute($name, $value) {
		$this->_attributes[ $name ] = $value;
		return $this;
	}
	// if test is true, set the attribute ... helpful for checkboxes and radios
	public function attributeIf($name, $value, $test) {
		if ($test) $this->attribute($name, $value);
		return $this;
	}
	
	public function children($children) {
		$this->_children = $children;
		return $this;
	}

	public static function __callStatic($name, $arguments) {
		$name = strtolower($name);
		$tags = explode(',', 'a,br,button,div,em,fieldset,form,img,label,legend,li,ol,option,p,span,strong,submit,td,th,tr,ul');
		if (in_array($name, $tags)) return new H('H::' . $name, $arguments);
		// What if it's an unsupported tag?
	}

	// Special cases
	public static function input() {
		return new HInput(__METHOD__, func_get_args());
	}

	public static function select() {
		return new HSelect(__METHOD__, func_get_args());
	}

	public static function textarea() {
		$a = new HInput(__METHOD__, func_get_args());
		$a->_short = false;
		return $a;
	}

	// Experimental ... looping
	public static function each($rows, $tree) {
		$a = H::eachElse($rows, $tree);
		return H::eachElse($rows, $tree);
	}
	public static function eachElse($rows, $tree, $alternate = null) {
		if (!$rows) return $alternate;
		return new T($rows, $tree);
	}
}

// These are intended to be private. Don't instantiate them directly
class HInput extends H {
	//  Support for array names for form fields
	// customer[123][name]
	// name('customer', '123', 'name')
	public function name() {
		$args = func_get_args();
		if (sizeof($args) > 1) {
			$name = array_shift($args) . '[' . implode('][', $args) . ']';
			$this->attribute('name', $name);
		} else $this->attribute('name', $args[0]);
		return $this;
	}
}

class HSelect extends HInput {
	protected $_options = array();
	protected $_value;

	public function __toString() {
		// Prepare children
		$this->_children = array();
		foreach ($this->_options as $key => $value) {
			$child = H::option(
				$value
			)->value($key);
			if ($this->_value == $key) $child->selected('selected');
			$this->_children[] = $child;
		}
		return parent::__toString();
	}

	public function options($data) {
		$this->_options = $data;
		return $this;
	}
	public function value($val) {
		$this->_value = $val;
		return $this;
	}


}

// Templating ... for looping
class T {
	protected $rows;
	public static $key;
	public static $value;

	public function __construct($rows, $tree) {
		$this->rows = $rows;
		$this->tree = $tree;
	}

	public function __toString() {
		$out = '';
		foreach ($this->rows as T::$key => T::$value) {
			// Evaluate the tree now. Wish there was a way I could lazy eval this, but I think not
			// Unless key() and value() return an instance of a class that simply holds the value of $key or $value
			// But then I'd need some way to push the next statement as a child to the parent containing the loop.
			$out .= $this->tree;
		}
		return $out;
	}

	public static function key() {
		return new Tvalue( T::$key );
	}
	public static function value() {
		return new Tvalue( T::$value );
	}
}
class Tkey {
	public function __toString() {
		return T::$key;
	}
}
class Tvalue {
	public function __toString() {
		return T::$value;
	}
}

/*
$a = H::div(
	H::div(
		"hey"
	)->class('boo hiss')->attribute('data-for', 'hey"sucka"')
);
echo $a;
*/
