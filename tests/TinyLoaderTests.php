<?php
namespace TinyHelpers\Tests;

require_once '../src/TinyHelpers/TinyLoader.php';

class RouteTests extends \PHPUnit_Framework_TestCase
{

    public function testSimple()
    {
        $loader = new \TinyHelpers\TinyLoader();
        $loader->setNamespacePath('Aardvark', __DIR__ . DIRECTORY_SEPARATOR . 'LoaderTests');
        $loader->setNamespacePath('GeneratedNamespace', __DIR__ . DIRECTORY_SEPARATOR . 'LoaderTests' . DIRECTORY_SEPARATOR . 'generated');
        $loader->register();

        $classes = array(
            'Aardvark' => 'ants',
            'Aardvark\\Boardwalk' => 'water',
            'Aardvark\\Boardwalk\\Cat' => 'scratch',
            'GeneratedNamespace\\GenClass' => 'manufactured',
        );

        foreach ($classes as $class => $ret) {
            $a = new $class();
            $this->assertEquals($ret, $a->ident());
        }
    }
}
