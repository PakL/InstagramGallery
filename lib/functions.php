<?php

// Daten strip gegen magic quotes von hetored (http://de.php.net/manual/de/function.get-magic-quotes-gpc.php#97783)
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
    $quotes_sybase = strtolower(ini_get('magic_quotes_sybase'));
    $unescape_function = (empty($quotes_sybase) || $quotes_sybase === 'off') ? 'stripslashes($value)' : 'str_replace("\'\'","\'",$value)';
    $stripslashes_deep = create_function('&$value, $fn', '
        if (is_string($value)) {
            $value = ' . $unescape_function . ';
        } else if (is_array($value)) {
            foreach ($value as &$v) $fn($v, $fn);
        }
    ');
    
    $stripslashes_deep($_POST, $stripslashes_deep);
    $stripslashes_deep($_GET, $stripslashes_deep);
    $stripslashes_deep($_COOKIE, $stripslashes_deep);
    $stripslashes_deep($_REQUEST, $stripslashes_deep);
}

function escape( $s ) { // nur ein kürzerer alias
    return mysql_real_escape_string( $s );
}

function orderPictures( $a, $b ) {
	$ta = strtotime( $a["date"] );
	$tb = strtotime( $b["date"] );
	if( $ta == $tb ) {
		return 0;
	}

	return ($ta < $tb) ? 1 : -1;
}