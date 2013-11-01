<?php

	function randString($min = 5, $max = 8)
	{
	    $length = rand($min, $max);
	    $string = '';
	    $index = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    for ($i = 0; $i < $length; $i++) {
	        $string .= $index[rand(0, strlen($index) - 1)];
	    }
	    return $string;
	}
	
	include("hu_client.php");
	define("API_URL","http://localhost:8080/wwwroot/api");



	///////// create user API
	$userName = "user" . randString();
	$email = "email@" . randString() . ".com";
	$password = "password";
	
	$result = HU_postRequest(API_URL . "/createUser",json_encode(array(
	  "name" => $userName,
	  "email" => $email,
	  "password" => md5($password),
	)),array(
		'user_id: create_user'
	));
	
	$resultAry = json_decode($result,true);
	$userId = $resultAry['id'];
	
	if(empty($userId))
	   die("create user failed {$result}");
	   
    print "Create user succeed: {$userId}\n";
    

	///////// Auth API
	$result = HU_postRequest(API_URL . "/auth",json_encode(array(
	  "email" => $email,
	  "password" => md5($password),
	)),array(
		'user_id: create_user'
	));
	
	if(empty($result))
	   die("auth failed {$result}");

    $result = json_decode($result,true);
    
    print_r($result);
    print "Auth succeed: {$result['token']}\n";

    $token = $result['token'];

    ///////// /findUser/email/****
	$result = HU_getRequest(API_URL . "/findUser/email/{$email}",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/findUser/email : OK \n";
	}else{
    	 die("/findUser/email {$result}");
	}
	
    ///////// /findUser/name/****
	$result = HU_getRequest(API_URL . "/findUser/name/{$userName}",array(
		'token' => $token,
		'user_id' => $userId
	));
	
	$resultAry = json_decode($result,true);
	
	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/findUser/name : OK \n";
	}else{
    	 die("/findUser/name {$result}");
	}
	
	
	
    ///////// /findUser/id/****
	$result = HU_getRequest(API_URL . "/findUser/id/{$userId}",array(
		'token' => $token,
		'user_id' => $userId
	));
	
	$resultAry = json_decode($result,true);
	
	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/findUser/id : OK \n";
	}else{
    	 die("/findUser/id {$result}");
	}
	

	///////// update user API	
	
	$newUserAry = $resultAry;
    $resultAry['name'] = "newname_user" . randString();

	$result = HU_postRequest(API_URL . "/updateUser",json_encode(
	   $resultAry
    ),array(
		'token' => $token,
		'user_id' => $userId
	));
	
	print $result;
	
    
?>