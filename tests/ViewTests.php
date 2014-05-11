<?php

require('../View.php');

$globalVar = 'hello';

class ViewTests extends PHPUnit_Framework_TestCase {
	// Make sure globals aren't accessible within template
	public function testSimple() {
		global $globalVar;
		global $anotherGlobalVar;

		$globalVar = 'hellos';

		$data = new stdClass;
		$data->test = $this;
		$out = View::file('view-template.php', $data);

		$this->assertEquals('hellos', $globalVar);
		$this->assertFalse(isset($anotherGlobalVar));
		$this->assertEquals('', $out);
	}
}

