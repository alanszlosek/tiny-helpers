<?php

require_once('../Loader.php');

class RouteTests extends PHPUnit_Framework_TestCase {

	public function testSimple() {
        Loader::$base = __DIR__ . DIRECTORY_SEPARATOR . 'Loader';
        Loader::$paths = array(
                'generated',
        );
//var_dump(Loader::$base);exit;
        spl_autoload_register(array('Loader', 'loadClass'));


        $classes = array(
            'Aardvark' => 'ants', // root namespace
            '\\Aardvark\\Boardwalk' => 'water',
            '\\Aardvark\\Boardwalk\\Cat' => 'scratch',
            '\\GeneratedNamespace\\GenClass' => 'manufactured',
        );

        foreach ($classes as $class => $ret) {
            $a = new $class();
            $this->assertEquals($ret, $a->ident());
        }
    }
}
