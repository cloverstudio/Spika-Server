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
	
	static public function checkEmailIsValid($email)
	{
	    $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/'; 
	    return preg_match($regex, $email);
	}

	static public function checkPasswordIsValid($password)
	{
	    $regex = '/^[a-zA-Z0-9]{6,}$/'; 
	    return preg_match($regex, $password);
	}


	
}

?>