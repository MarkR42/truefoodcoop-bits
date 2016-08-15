<?php

function fix_ean_13($code)
{
	/*
		Take a code with 12 or 13 digits,
		remove the check digit, recalculate it and return
		the fixed code.
	*/

	$l = strlen($code);
	if (($l < 12) || ($l > 13)) {
		throw new Exception("Invalid length $l");
	}
	$code = substr($code, 0, 12);

	$sum = 0;
	for ($n = 0; $n < 12; $n ++ ) {
		$weight = 1;
		if (($n % 2) == 1) {
			$weight = 3;
		}
		$sum += ((int) $code[$n]) * $weight;
	}
	$check = (10 - ($sum % 10)) % 10;
	return $code . $check; // Append check digit
}

?>
