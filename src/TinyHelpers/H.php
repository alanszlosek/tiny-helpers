<?php
namespace TinyHelpers;

/*
H - Sometimes you need to build HTML via code

MIT Licensed. See LICENSE.txt for details.
*/

class H
{
    // So subclasses can add additional tags
    protected static $_tags = array('a', 'br', 'button', 'div', 'em', 'fieldset', 'form', 'img', 'label', 'legend', 'li', 'ol', 'option', 'p', 'span', 'strong', 'submit', 'td', 'th', 'tr', 'ul');
    protected $_tag;
    protected $_attributes = array();
    protected $_children = array();
    public function __construct($tag, $children = array())
    {
        $this->_tag = strtoupper($tag);
        $this->_children = $children;
    }

    public function __toString()
    {
        $out = '<' . $this->_tag;
        // escaping
        foreach ($this->_attributes as $key => $value) {
            $out .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        $out .= '>';
        foreach ($this->_children as $child) {
            if ($child instanceof H) $out .= $child;
            else $out .= htmlentities($child);
        }
        $out .= '</' . $this->_tag . '>';

        return $out;
    }
    public function __call($name, $args)
    {
        return $this->attribute($name, $args[0]);
    }

    // TODO
    public function __clone()
    {
    }

    public function attribute($name, $value)
    {
        $this->_attributes[ $name ] = $value;

        return $this;
    }
    // if test is true, set the attribute ... helpful for checkboxes and radios
    public function attributeIf($name, $value, $test)
    {
        if ($test) $this->attribute($name, $value);
        return $this;
    }

    public function children($children)
    {
        $this->_children = $children;

        return $this;
    }

    // STATIC METHODS

    public static function __callStatic($name, $arguments)
    {
        $name = strtolower($name);
        if (in_array($name, static::$_tags)) return new H($name, $arguments);
        // What if it's an unsupported tag?
        return null;
    }

    // Special cases
    public static function input()
    {
        return new HInput('input', func_get_args());
    }

    public static function select()
    {
        return new HSelect('select', func_get_args());
    }

    public static function textarea()
    {
        return new HInput('textarea', func_get_args());
    }

    // Experimental ... looping
    public static function each($rows, $tree)
    {
        return H::eachElse($rows, $tree);
    }
    public static function eachElse($rows, $tree, $alternate = null)
    {
        if (!$rows) return $alternate;
        return new T($rows, $tree);
    }
}

// These are intended to be private. Don't instantiate them directly
class HInput extends H
{
    // Defaults to text input type
    protected $_attributes = array(
        'type' => 'text'
    );
    //  Support for array names for form fields
    // customer[123][name]
    // name('customer', '123', 'name')
    public function name()
    {
        $args = func_get_args();
        if (sizeof($args) > 1) {
            $name = array_shift($args) . '[' . implode('][', $args) . ']';
            $this->attribute('name', $name);
        } else $this->attribute('name', $args[0]);

        return $this;
    }
}

class HSelect extends HInput
{
    protected $_attributes = array();
    protected $_options = array();
    protected $_value;

    // But what if we already set children?
    public function __toString()
    {
        // Prepare children
        foreach ($this->_options as $key => $value) {
            // Doing the following causes strange problems
            // $child = H::option($value);
            $child = new H('option', array($value));
            $child->value($key);
            if ($this->_value == $key) $child->selected('selected');
            $this->_children[] = $child;
        }

        return parent::__toString();
    }

    public function options($data)
    {
        $this->_options = $data;

        return $this;
    }
    public function value($val)
    {
        $this->_value = $val;

        return $this;
    }

}

// Templating ... for looping
class T
{
    protected $rows;
    public static $key;
    public static $value;

    public function __construct($rows, $tree)
    {
        $this->rows = $rows;
        $this->tree = $tree;
    }

    public function __toString()
    {
        $out = '';
        foreach ($this->rows as T::$key => T::$value) {
            // Evaluate the tree now. Wish there was a way I could lazy eval this, but I think not
            // Unless key() and value() return an instance of a class that simply holds the value of $key or $value
            // But then I'd need some way to push the next statement as a child to the parent containing the loop.
            $out .= $this->tree;
        }

        return $out;
    }

    public static function key()
    {
        return new Tvalue( T::$key );
    }
    public static function value()
    {
        return new Tvalue( T::$value );
    }
}
class Tkey
{
    public function __toString()
    {
        return T::$key;
    }
}
class Tvalue
{
    public function __toString()
    {
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
