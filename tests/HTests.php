<?php


require '../src/TinyHelpers/H.php';
use \TinyHelpers\H;

class HTests extends \PHPUnit_Framework_TestCase
{
    protected function common($a, $b)
    {
        foreach ($a as $i => $item) {
            $this->assertEquals($item, '' . $b[ $i ]);
        }

    }

    public function testSimple()
    {
        // markup
        $a = array(
            '<DIV></DIV>',
            '<DIV>test &amp; entities</DIV>',
            '<DIV class="a"><P>test</P></DIV>',
            '<DIV class="a"><P>test</P></DIV>'
        );
        // objects
        $b = array(
            H::div(),
            H::div('test & entities'),
            H::div(
                H::p('test')
            )->class('a'),
            H::div()->class('a')->children(array( H::p('test') ))
        );
        $this->common($a, $b);
    }

    public function testFormInputs()
    {
        // markup
        $a = array(
            '<INPUT type="text" name="title" value="Body &amp; Soul"></INPUT>',
            '<INPUT type="checkbox" name="active" value="1" checked="checked"></INPUT>',
            '<SELECT name="choice"><OPTION value="a">A</OPTION><OPTION value="b" selected="selected">B</OPTION></SELECT>',
        );
        // objects
        $expression = true;
        $options = array(
            'a' => 'A',
            'b' => 'B'
        );
        $b = array(
            H::input()->name('title')->value('Body & Soul'),
            H::input()->type('checkbox')->name('active')->value(1)->attributeIf('checked', 'checked', $expression),
            H::select()->name('choice')->value('b')->options($options),
        );
        $this->common($a, $b);
    }

    public function testAttributes()
    {
        // markup
        $a = array(
            '<A href="https://github.com" target="_blank">github.com</A>',
            '<A href="https://github.com" data-bool="true">github.com</A>',
        );
        // objects
        $expression = true;
        $b = array(
            H::a('github.com')->href('https://github.com')->target('_blank'),
            H::a('github.com')->href('https://github.com')->attributeIf('data-bool', 'true', $expression),
        );
        $this->common($a, $b);
    }
}

// echo H::div();
/*

$lis = H::li(
    // Maybe T::value() should accept a closure ... but would that be overkill
    T::value()
);

$a = H::ol(
    H::each(
        array('Zero', 'One'),
        $lis
    ),
    H::each(
        array('Three', 'Four'),
        $lis
    )
);
//var_dump($a);exit;
echo $a;

*/
