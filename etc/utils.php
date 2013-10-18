<?php

namespace Spika;

class Utils{
	
	static public function randString($min = 5, $max = 8)
	{
	    $length = rand($min, $max);
	    $string = '';
	    $index = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    for ($i = 0; $i < $length; $i++) {
	        $string .= $index[rand(0, strlen($index) - 1)];
	    }
	    return $string;
	}
	
}

?>