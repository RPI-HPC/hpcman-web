<?php

function nice_size ($size, $precision=2)
{
	if ($size == 0) {
		return '0 B';
	}

	$suffixes = array('B','K','M','G','T','P');
	$suffix = floor(log($size, 1024));
	$new_size = $size / pow(1024, $suffix);
	return number_format($new_size, $precision, '.', '') . " {$suffixes[$suffix]}";
}

?>
