<?php

require_once('../Loader.php');

class RouteTests extends PHPUnit_Framework_TestCase {

    // Test PSR-0 including
	public function testPSR0() {
        Loader::$basePSR0 = __DIR__ . DIRECTORY_SEPARATOR . 'Loader';
        spl_autoload_register(array('Loader', 'loadClass'));

        $classes = array(
            'Aardvark' => 'ants', // root namespace
            '\\Aardvark\\Boardwalk' => 'water',
            '\\Aardvark\\Boardwalk\\Cat' => 'scratch',
        );

        foreach ($classes as $class => $ret) {
            $a = new $class();
            $this->assertEquals($ret, $a->ident());
        }
    }

    // Test PSR-4 including
	public function testPSR4() {
        Loader::addNamespace('Prefixed', __DIR__ . DIRECTORY_SEPARATOR . 'Loader/Aardvark');
        Loader::addNamespace('GeneratedNamespace', __DIR__ . DIRECTORY_SEPARATOR . 'Loader' . DIRECTORY_SEPARATOR . 'generated/GeneratedNamespace');
        spl_autoload_register(array('Loader', 'loadClass'));

        $classes = array(
            // Namespace is part of class name, but not part of file system
            'Aardvark\\Boardwalk' => 'water',
            'Aardvark\\Boardwalk\\Cat' => 'scratch',
            'GeneratedNamespace\\GenClass' => 'manufactured',
        );

        foreach ($classes as $class => $ret) {
            $a = new $class();
            $this->assertEquals($ret, $a->ident());
        }
    }




/*
    // Test PHP include paths
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
*/
}
