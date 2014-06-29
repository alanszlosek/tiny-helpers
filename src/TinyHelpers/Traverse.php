<?php
namespace TinyHelpers;

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

$element = Traverse::arrayTo($nested, 'TWO', 'Two', 'two');
// $element now contains 'There'

*/

class Traverse
{
    public static function arrayTo()
    {
        $args = func_get_args();
        $values = array_shift($args);
        while ($args) {
            $key = array_shift($args);
            $values = $values[ $key ];
        }

        return $values;
    }
}
