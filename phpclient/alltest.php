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
    $resultAry['birthday'] = time() - 60 * 60 * 24 * 365 * 32;
    $resultAry['gender'] = 'male';


	$result = HU_postRequest(API_URL . "/updateUser",json_encode(
	   $resultAry
    ),array(
		'token' => $token,
		'user_id' => $userId
	));
	
	$resultAry = json_decode($result,true);	

	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/updateUser : OK \n";
	}else{
    	 die("/updateUser {$result}");
	}

    ///////// activitySummary
	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $token,
		'user_id' => $userId
	));
	
	$resultAry = json_decode($result,true);	

	if(isset($resultAry['total_rows'])){
	   print "/activitySummary : OK \n";
	}else{
    	 die("/activitySummary failed {$result}");
	}
	
    ///////// searchUser
	$result = HU_getRequest(API_URL . "/searchUsers?n=user",array(
	));

	$resultAry = json_decode($result,true);	

	if(count($result)) {
	   print "/search user by name : OK \n";
	}else{
    	 die("/search user by name  {$result}");
	}
	
	$result = HU_getRequest(API_URL . "/searchUsers?af=30&at=35",array(
	));	
	
	$resultAry = json_decode($result,true);	
	
	if(count($result)) {
	   print "/search user by age : OK \n";
	}else{
    	 die("/search user by age  {$result}");
	}
	
	$result = HU_getRequest(API_URL . "/searchUsers?g=male",array(
	));

	$resultAry = json_decode($result,true);	
	
	if(count($result)) {
	   print "/search user by gender : OK \n";
	}else{
    	 die("/search user by gender  {$result}");
	}
	
	//////// create taget user
	$targetUserName = "user" . randString();
	$targetEmail = "email@" . randString() . ".com";
	$targetPassword = "password";
	
	$result = HU_postRequest(API_URL . "/createUser",json_encode(array(
	  "name" => $targetUserName,
	  "email" => $targetEmail,
	  "password" => md5($targetPassword),
	)),array(
		'user_id: create_user'
	));
	
	$resultAry = json_decode($result,true);
	$targetUserId = $resultAry['id'];
	
	if(empty($targetUserId))
	   die("create target user failed {$result}");
	   
    print "Create target user succeed: {$targetUserId}\n";

	///////// Auth target user
	$result = HU_postRequest(API_URL . "/auth",json_encode(array(
	  "email" => $targetEmail,
	  "password" => md5($targetPassword),
	)),array(
		'user_id: create_user'
	));
	
	if(empty($result))
	   die("auth failed {$result}");

    $result = json_decode($result,true);
    
    print "Target Auth succeed: {$result['token']}\n";

    $targetToken = $result['token'];
    
    //////// get Emoticons
	$result = HU_getRequest(API_URL . "/Emoticons",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);	
	
	if(count($resultAry['rows'][0])) {
	   print "Emoticons : OK \n";
	}else{
    	 die("Emoticons failed  {$result}");
	}


    //////// load Emoticon
	$result = HU_getRequest(API_URL . "/Emoticon/{$resultAry['rows'][0]['id']}",array(
		'token' => $token
	));
	
	if($result != null) {
	   print "Load Emoticon : OK \n";
	}else{
    	 die("Load Emoticon failed  {$result}");
	}

    
   
	//////// send text message
	$result = HU_postRequest(API_URL . "/sendMessageToUser",json_encode(array(
	  "to_user_id" => $targetUserId,
	  "body" => "Hi1"
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);


	//////// send text message
	$result = HU_postRequest(API_URL . "/sendMessageToUser",json_encode(array(
	  "to_user_id" => $targetUserId,
	  "body" => "Hi2"
	)),array(
		'token' => $token
	));
		
    $resultAry = json_decode($result,true);


	if(empty($resultAry['id']))
	   die("send message failed {$result}");

    ///////// activitySummary
	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $targetToken
	));
	
	$resultAryA = json_decode($result,true);	
	
	if(isset($resultAryA['total_rows'])){
	   print "/activitySummary : OK \n";
	}else{
    	 die("/activitySummary failed {$result}");
	}

	//////// get message by id
	$result = HU_getRequest(API_URL . "/findMessageById/{$resultAry['id']}",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);

	if(empty($resultAry['_id']))
	   die("findMessageById failed {$result}");
	   
    print "findMessageById : OK {$resultAry['_id']}\n";

    //////// get user messages
	$result = HU_getRequest(API_URL . "/userMessages/{$targetUserId}/30/0",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
    
	if(empty($resultAry['rows'][0]))
	   die("read message failed {$result}");
	   
    print "read message : OK {$resultAry['rows'][0]['value']['body']}\n";

    //////// add contact
	$result = HU_postRequest(API_URL . "/addContact",json_encode(array(
	  "user_id"=>$targetUserId
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/addContact : OK \n";
	}else{
    	 die("/addContact {$result}");
	}

    //////// remove contact
	$result = HU_postRequest(API_URL . "/removeContact",json_encode(array(
	  "user_id"=>$targetUserId
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);

	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/removeContact : OK \n";
	}else{
    	 die("/removeContact {$result}");
	}

    //////// create group test
    $groupName = "group" . randString();
	$result = HU_postRequest(API_URL . "/createGroup",json_encode(array(
	  "name" => $groupName,
	  "group_password" => "",
	  "category_id" => "",
	  "description" => "test group",
	  "type" => "group",
	  "user_id" => $userId,
	  "avatar_file_id" => "",
	  "avatar_thumb_file_id" => "",
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	$newGroupId = $resultAry['id'];
	
	if(empty($resultAry['id']))
	   die("create group failed {$result}");
	   
    print "create group : OK {$resultAry['id']}\n";

	$newGroupId = $resultAry['id'];
	
	$groupName = $groupName . "lll";
    //////// update group test
	$result = HU_postRequest(API_URL . "/updateGroup",json_encode(array(
	  "_id" => $newGroupId,
	  "name" => $groupName
	)),array(
		'token' => $token
	));

	
	$resultAry = json_decode($result,true);
	
	
	if(empty($resultAry['id']))
	   die("update group failed {$result}");
	   
    print "update group : OK {$resultAry['id']}\n";


    //////// find group by id
	$result = HU_getRequest(API_URL . "/findGroup/id/{$newGroupId}",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);


	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/findGroup/id : OK \n";
	}else{
    	 die("/findGroup/id failed {$result}");
	}


    //////// find group by name
	$result = HU_getRequest(API_URL . "/findGroup/name/{$groupName}",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);


	if(isset($resultAry['name']) && isset($resultAry['_id'])){
	   print "/findGroup/name : OK \n";
	}else{
    	 die("/findGroup/name failed {$result}");
	}


    ////////  group category test
	$result = HU_getRequest(API_URL . "/findAllGroupCategory",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	if(empty($resultAry['rows']))
	   //die("get all group category failed {$result}");
	   
    print "get all group category : OK\n";
    
    $categoryId = $resultAry['rows'][0]['value']['_id'];
    
    //////// find group by category id
    
	$result = HU_getRequest(API_URL . "/findGroup/categoryId/{$categoryId}",array(
		'token' => $token
	));

	
	$resultAry = json_decode($result,true);
	
	if(isset($resultAry['rows'])){
	   print "/findGroup/categoryId : OK \n";
	}else{
    	// die("/findGroup/categoryId {$result}");
	}


    //////// find group by name
    
	$result = HU_getRequest(API_URL . "/searchGroups/name/test",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);

	if(isset($resultAry[0])){
	   print "/searchGroups/name : OK \n";
	}else{
      //die("/searchGroups/name {$result}");
	}

	//////// send text message to group
	$result = HU_postRequest(API_URL . "/sendMessageToGroup",json_encode(array(
	  "to_group_id" => $newGroupId,
	  "body" => "Hi"
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);

    //setDelete
	$result = HU_postRequest(API_URL . "/setDelete",json_encode(array(
	  "delete_type" => 5,
	  "message_id" => $resultAry['id']
	)),array(
		'token' => $token
	));


	
    //////// get group messages
	$result = HU_getRequest(API_URL . "/groupMessages/{$newGroupId}/30/0",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	print_r($result);
	die();
	
	$targetUserId = $resultAry['id'];
	
	if(empty($resultAry['rows'][0]))
	   die("read group message failed {$result}");
	   
    print "read group message : OK {$resultAry['rows'][0]['value']['body']}\n";
	

    //////// subscribe group test
	$result = HU_postRequest(API_URL . "/subscribeGroup",json_encode(array(
	  "group_id" => $newGroupId,
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);

	
	if(empty($resultAry['_id']))
	   die("subscribe group failed {$result}");
	   
    print "subscribe group : OK {$resultAry['id']}\n";




    //////// subscribe group test
	$result = HU_postRequest(API_URL . "/subscribeGroup",json_encode(array(
	  "group_id" => $newGroupId,
	)),array(
		'token' => $targetToken
	));

	$result = HU_postRequest(API_URL . "/sendMessageToGroup",json_encode(array(
	  "to_group_id" => $newGroupId,
	  "body" => "Hi1"
	)),array(
		'token' => $token
	));
	$result = HU_postRequest(API_URL . "/sendMessageToGroup",json_encode(array(
	  "to_group_id" => $newGroupId,
	  "body" => "Hi2"
	)),array(
		'token' => $token
	));




    ///////// activitySummary
	$result = HU_getRequest(API_URL . "/activitySummary",array(
		'token' => $targetToken
	));
	
	$resultAryA = json_decode($result,true);	
	
	print_r($resultAryA);
	die();
	
	if(isset($resultAryA['total_rows'])){
	   print "/activitySummary : OK \n";
	}else{
    	 die("/activitySummary failed {$result}");
	}





    //////// unsubscribe group test
	$result = HU_postRequest(API_URL . "/unSubscribeGroup",json_encode(array(
	  "group_id" => $newGroupId,
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	if(empty($resultAry['_id']))
	   die("unsubscribe group failed {$result}");
	   
    print "unsubscribe group : OK\n";


    ////////  group category test
	$result = HU_getRequest(API_URL . "/findAllGroupCategory",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	//if(empty($resultAry['rows']))
	   //die("get all group category failed {$result}");
	   
    print "get all group category : OK\n";



    //////// delete group test
    
    /*
	$result = HU_postRequest(API_URL . "/deleteGroup",json_encode(array(
	  "_id" => $newGroupId,
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);

	
	if(empty($resultAry['id']))
	   die("delete group failed {$result}");
	   
    print "delete group : OK {$resultAry['id']}\n";
	*/


    //////// send commnet
	$result = HU_postRequest(API_URL . "/sendComment",json_encode(array(
	  "message_id" => $newMessageId,
	  "comment" => "Hi"
	)),array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	$targetUserId = $resultAry['id'];
	
	if(empty($resultAry['id']))
	   die("send comment failed {$result}");
	   
    print "send comment : OK {$resultAry['id']}\n";


    //////// comment count
	$result = HU_postRequest(API_URL . "/sendComment",json_encode(array(
	  "message_id" => $newMessageId,
	  "comment" => "Hi1"
	)),array(
		'token' => $token
	));
	$result = HU_postRequest(API_URL . "/sendComment",json_encode(array(
	  "message_id" => $newMessageId,
	  "comment" => "Hi2"
	)),array(
		'token' => $token
	));
	$result = HU_postRequest(API_URL . "/sendComment",json_encode(array(
	  "message_id" => $newMessageId,
	  "comment" => "Hi3"
	)),array(
		'token' => $token
	));

	$result = HU_getRequest(API_URL . "/commentsCount/{$newMessageId}",array(
		'token' => $token
	));

	$resultAry = json_decode($result,true);

	$targetUserId = $resultAry['id'];
	
	if(empty($resultAry['rows'][0]['value']))
	   die("count comment failed {$result}");
	   
    print "count comment : OK {$resultAry['rows'][0]['value']}\n";

	
    //////// get comments
	$result = HU_getRequest(API_URL . "/comments/{$newMessageId}/30/0",array(
		'token' => $token
	));
	
	$resultAry = json_decode($result,true);
	
	
	if(empty($resultAry['rows'][0]))
	   die("read comment failed {$result}");
	   
    print "read comment : OK\n";


    //////// watch group test
	$result = HU_postRequest(API_URL . "/watchGroup",json_encode(array(
	  "group_id" => $newGroupId,
	)),array(
		'token' => $token
	));

	if($result != 'OK')
	   die("watch group failed {$result}");
	   
    print "watch group : OK \n";


    //////// unwatch group test
	$result = HU_postRequest(API_URL . "/unWatchGroup",json_encode(array(
	  "group_id" => $newGroupId,
	)),array(
		'token' => $token
	));
	
	if($result != 'OK')
	   die("unwatch group failed {$result}");
	   
    print "unwatch group : OK \n";



?>