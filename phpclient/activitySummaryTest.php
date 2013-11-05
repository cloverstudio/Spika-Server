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


	$user1Name = "user" . randString();
	$user1Email = "email@" . randString() . ".com";
	$user1Password = "password";
	
	$result = HU_postRequest(API_URL . "/createUser",json_encode(array(
	  "name" => $user1Name,
	  "email" => $user1Email,
	  "password" => md5($user1Password),
	)),array(
		'user_id: create_user'
	));
	
	$resultAry = json_decode($result,true);
	$user1Id = $resultAry['id'];
	
	$result = HU_postRequest(API_URL . "/auth",json_encode(array(
	  "email" => $user1Email,
	  "password" => md5($user1Password),
	)),array(
		'user_id: create_user'
	));
	
	if(empty($result))
	   die("auth failed {$result}");

    $result = json_decode($result,true);
    $token1 = $result['token'];
    
    

	$user2Name = "user" . randString();
	$user2Email = "email@" . randString() . ".com";
	$user2Password = "password";
	
	$result = HU_postRequest(API_URL . "/createUser",json_encode(array(
	  "name" => $user2Name,
	  "email" => $user2Email,
	  "password" => md5($user2Password),
	)),array(
		'user_id: create_user'
	));
	
	$resultAry = json_decode($result,true);
	$user2Id = $resultAry['id'];

	
	$result = HU_postRequest(API_URL . "/auth",json_encode(array(
	  "email" => $user2Email,
	  "password" => md5($user2Password),
	)),array(
		'user_id: create_user'
	));
	
	if(empty($result))
	   die("auth failed {$result}");

    $result = json_decode($result,true);
    $token2 = $result['token'];


    $groupName = "group" . randString();
	$result = HU_postRequest(API_URL . "/createGroup",json_encode(array(
	  "name" => $groupName,
	  "group_password" => "",
	  "category_id" => "361e5fc396c17b44e58eea1a230478ec",
	  "description" => "test group",
	  "type" => "group",
	  "user_id" => $userId,
	  "avatar_file_id" => "",
	  "avatar_thumb_file_id" => "",
	)),array(
		'token' => $token1
	));
	
	$resultAry = json_decode($result,true);
	$newGroupId = $resultAry['id'];
	
	if(empty($resultAry['id']))
	   die("create group failed {$result}");

	$newGroupId = $resultAry['id'];

	
	
	$result = HU_postRequest(API_URL . "/subscribeGroup",json_encode(array(
	  "group_id" => $newGroupId,
	)),array(
		'token' => $token2
	));
	
	$resultAry = json_decode($result,true);
	
	if(empty($resultAry['_id']))
	   die("subscribe group failed {$result}");
	   
    print "subscribe group : OK {$resultAry['id']}\n";

	$result = HU_postRequest(API_URL . "/sendMessageToUser",json_encode(array(
	  "to_user_id" => $user2Id,
	  "body" => "Hi"
	)),array(
		'token' => $token1
	));
	$result = HU_postRequest(API_URL . "/sendMessageToUser",json_encode(array(
	  "to_user_id" => $user2Id,
	  "body" => "Hi"
	)),array(
		'token' => $token1
	));
	$result = HU_postRequest(API_URL . "/sendMessageToUser",json_encode(array(
	  "to_user_id" => $user2Id,
	  "body" => "Hi"
	)),array(
		'token' => $token1
	));
	
	/*
	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $token2,
	));
	
	$resultAry1 = json_decode($result,true);	
	
	
	$result = HU_getRequest(API_URL . "/userMessages/{$user1Id}/30/0",array(
		'token' => $token2
	));
	
	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $token2,
	));
	
	$resultAry2 = json_decode($result,true);	
	
	print_r($resultAry1);
	print_r($resultAry2);
	die();
	*/







	$result = HU_postRequest(API_URL . "/sendMessageToGroup",json_encode(array(
	  "to_group_id" => $newGroupId,
	  "body" => "Hi"
	)),array(
		'token' => $token1
	));
	$result = HU_postRequest(API_URL . "/sendMessageToGroup",json_encode(array(
	  "to_group_id" => $newGroupId,
	  "body" => "Hi"
	)),array(
		'token' => $token1
	));

	$resultAry = json_decode($result,true);
	
	if(empty($resultAry['id']))
	   die("send group message failed {$result}");
	   
	$newMessageId = $resultAry['id'];

	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $token2,
	));
	
	$resultAry1 = json_decode($result,true);	

	$result = HU_getRequest(API_URL . "/groupMessages/{$newGroupId}/30/0",array(
		'token' => $token2
	));
	
	
	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $token2,
	));
	
	$resultAry2 = json_decode($result,true);	
		
	print_r($resultAry1);
	print_r($resultAry2);
	
	
	
?>