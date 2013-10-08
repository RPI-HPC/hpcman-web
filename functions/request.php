<?php

/*
 * get_request_field(string field, multi fallback)
 * Returns the value of a request field if it exists
 * otherwise return the fallback value which defaults to NULL
 *
 * Attempts to determine if the field is a number and return it appropriately.
 */
function get_request_field($field, $fallback=NULL)
{
	if (!isset($_REQUEST[$field]))
		return $fallback;

	$val = $_REQUEST[$field];

	if (is_numeric($val)) {
		$i = intval($val);
		$f = floatval($val);
		if ($i == $f)
			return $i;
		return $f;
	}

	if ($val == '')
		return $fallback;

	return $val;
}

?>
