<?php

require_once('../Loader.php');

class MockLoader extends Loader {
    public $files = array();

    protected function requireFile($file) {
        return in_array($file, $this->files);
    }
}

class RouteTests extends PHPUnit_Framework_TestCase {

    // Test PSR-0 including
	public function testPSR0() {
        $loader = new Loader();
        $loader->basePSR0 = __DIR__ . DIRECTORY_SEPARATOR . 'Loader';
        spl_autoload_register(array($loader, 'loadClass'));

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
        $loader = new Loader();
        $loader->addNamespace('Prefixed', __DIR__ . DIRECTORY_SEPARATOR . 'Loader/Aardvark');
        $loader->addNamespace('GeneratedNamespace', __DIR__ . DIRECTORY_SEPARATOR . 'Loader' . DIRECTORY_SEPARATOR . 'generated/GeneratedNamespace');
        spl_autoload_register(array($loader, 'loadClass'));

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

    public function testPSR4two() {
        $loader = new MockLoader();

        $loader->files = array(
            '/vendor/foo.bar/src/ClassName.php',
            '/vendor/foo.bar/src/DoomClassName.php',
            '/vendor/foo.bar/tests/ClassNameTest.php',
            '/vendor/foo.bardoom/src/ClassName.php',
            '/vendor/foo.bar.baz.dib/src/ClassName.php',
            '/vendor/foo.bar.baz.dib.zim.gir/src/ClassName.php',
        );

        $loader->addNamespace(
            'Foo\Bar',
            '/vendor/foo.bar/src'
        );

        $loader->addNamespace(
            'Foo\Bar',
            '/vendor/foo.bar/tests'
        );

        $loader->addNamespace(
            'Foo\BarDoom',
            '/vendor/foo.bardoom/src'
        );

        $loader->addNamespace(
            'Foo\Bar\Baz\Dib',
            '/vendor/foo.bar.baz.dib/src'
        );

        $loader->addNamespace(
            'Foo\Bar\Baz\Dib\Zim\Gir',
            '/vendor/foo.bar.baz.dib.zim.gir/src'
        );

        // public function testExistingFile()
        $actual = $loader->loadClass('Foo\Bar\ClassName');
        $expect = '/vendor/foo.bar/src/ClassName.php';
        $this->assertSame($expect, $actual);

        $actual = $loader->loadClass('Foo\Bar\ClassNameTest');
        $expect = '/vendor/foo.bar/tests/ClassNameTest.php';
        $this->assertSame($expect, $actual);

        // public function testMissingFile()
        $actual = $loader->loadClass('No_Vendor\No_Package\NoClass');
        $this->assertFalse($actual);

        // public function testDeepFile()
        $actual = $loader->loadClass('Foo\Bar\Baz\Dib\Zim\Gir\ClassName');
        $expect = '/vendor/foo.bar.baz.dib.zim.gir/src/ClassName.php';
        $this->assertSame($expect, $actual);

        // public function testConfusion()
        $actual = $loader->loadClass('Foo\Bar\DoomClassName');
        $expect = '/vendor/foo.bar/src/DoomClassName.php';
        $this->assertSame($expect, $actual);

        $actual = $loader->loadClass('Foo\BarDoom\ClassName');
        $expect = '/vendor/foo.bardoom/src/ClassName.php';
        $this->assertSame($expect, $actual);
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
