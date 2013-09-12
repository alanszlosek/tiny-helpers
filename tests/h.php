<?php

require('../H.php');

// echo H::div();

$lis = H::li(
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

