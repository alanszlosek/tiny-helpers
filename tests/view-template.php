<?php

// Make sure this global isn't accessible
$test->assertFalse(isset($globalVar));

$globalVar = 'from template';
$anotherGlobalVar = 'from template';
