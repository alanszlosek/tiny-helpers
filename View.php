<?php
/*
The dumbest view/template class you've ever seen.
This shouldn't do much at all except encapsulate.
*/
class View {
	/*
	Data can be an object or array.
	Each key will be hoisted so it's unnecessary to use "$this->" to access data keys from within the template.
	*/
	public static function File($filename, $data = null) {
		if (is_object($data)) {
			extract((array) $data);
		} elseif (is_array($data)) {
			extract($data);
		}

		ob_start();
		include($filename);
		$out = ob_get_clean();
		return $out;
	}
}

