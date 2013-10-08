<?php

/*
 * Print the contents of a variable if it's set
 * otherwise print another string/variable.
 * If the "backup" value it is a variable, it must exist!
 *
 * Passing the first argument by reference avoids an error if the variable
 * doesn't exist
 */
function hpcman_print(&$var, $bad)
{
	echo (isset($var)?$var:$bad);
}

?>
