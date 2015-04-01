<?php
namespace TinyHelpers\Tests;

class InstallerTests extends \PHPUnit_Framework_TestCase
{

    public function testSimple()
    {
        /*
        InstallerTests/thi.json depends on TinyHelpers and Collections (which also depends on TinyHelpers).
        The goal is to make sure Collection's dependency upon TinyHelpers is satisfied by the existing installation.
        */
        $lines = shell_exec('cd InstallerTests; ./test.sh');
        // Verify thi-autoload.php
        require('InstallerTests/thi-autoload.php');
        $this->assertEquals('DefaultPassword', \Collections\Application::$password);
    }
}
