<?php
	
	define("API_URL","http://localhost:8080/wwwroot/api");
	$APP = "spikademo";
	
	//------------------------ low level functions

	function HU_getRequest($url,$requestHeader = array()){
    	
    	$headers = array();
    	foreach($requestHeader as $key => $value){
        	$headers[] = "{$key}: {$value}";
    	}
    	
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);	
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		$response = curl_exec($curl);

		return $response;
			
	}
	
	function HU_postRequest($url, $reqBody,$requestHeader = array()){
		
    	$headers = array();
    	foreach($requestHeader as $key => $value){
        	$headers[] = "{$key}: {$value}";
    	}
    	
    	
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);	
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $reqBody);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		$response = curl_exec($curl);

		return $response;
			
	}

	function HU_putRequest($url, $reqBody,$requestHeader = array()){

    	$headers = array();
    	foreach($requestHeader as $key => $value){
        	$headers[] = "{$key}: {$value}";
    	}
    	

		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $reqBody);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		
		$response = curl_exec($curl);

		return $response;
			
	}

	function HU_checkAndMakeRequestHeader(){
		
		global $HU_USERID,$HU_USERTOKEN,$APP;

		if(empty($HU_USERID) || empty($HU_USERTOKEN)){
			return;
		}
		
		$requestHeader = array(
			"user_id: {$HU_USERID}",
			"token: {$HU_USERTOKEN}",
			"database: {$APP}",
		);
		
		return $requestHeader;
	}
	
	function HU_dbGetDocument($documentId){
		
		$requestHeader = HU_checkAndMakeRequestHeader();
		if($requestHeader == null)
			return;

    	$result = HU_getRequest(API_URL . $documentId,$requestHeader);

		$resultDic = json_decode($result,true);
		return $resultDic;	
	}
	
	function HU_dbFindByKey($view,$key){
		
		$requestHeader = HU_checkAndMakeRequestHeader();
		if($requestHeader == null)
			return;

		$key = urlencode($key);
		
    	$url = "_design/app/_view/{$view}?key=\"{$key}\"";
    	
    	$result = HU_getRequest(API_URL . $url,$requestHeader);
    	
		$resultDic = json_decode($result,true);
		
		
		return $resultDic['rows'][0]['value'];	
	}

	function HU_dbFindAll($view){
		
		$requestHeader = HU_checkAndMakeRequestHeader();
		if($requestHeader == null)
			return;
		
		$key = urlencode($key);
		
    	$url = "_design/app/_view/{$view}";
    	
    	$result = HU_getRequest(API_URL . $url,$requestHeader);
		
		$resultDic = json_decode($result,true);
		
		return $resultDic['rows'];	
	}

	function HU_dbInsert($data){

		$requestHeader = HU_checkAndMakeRequestHeader();
		if($requestHeader == null)
			return;
		
		return HU_postRequest(API_URL . "",json_encode($data),$requestHeader);

	}
	
	function HU_dbUpdate($id,$data){

		$requestHeader = HU_checkAndMakeRequestHeader();
		if($requestHeader == null)
			return;
		
		return HU_putRequest(API_URL . $id,json_encode($data),$requestHeader);

	}
	
	function HU_sendFile($file){
		
		global $APP;
		
		$requestHeader = HU_checkAndMakeRequestHeader();
		if($requestHeader == null)
			return;
		

		$post = array('file'=>'@'.$file);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,API_URL."fileuploader.php?db=" . $APP);
		curl_setopt($ch, CURLOPT_POST,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		
		$result = curl_exec ($ch);
		
		curl_close ($ch);
		
		return $result;
		
	}
	
	//------------------------ high level functions
	
	
	
	function HU_createUser($name,$email,$password){
		
		global $APP;
		
		$userData = array();
		
		$userData['name'] = $name;
		$userData['email'] = $email;
		$userData['password'] = $password;
		$userData['type'] = "user";
		$userData['online_status'] = "online";
		$userData['max_contact_count'] = 20;
		$userData['max_favorite_count'] = 10;
		
		$requestHeader = array(
			'user_id: create_user',
			"database: {$APP}"
		);

		return HU_postRequest(API_URL . "",json_encode($userData),$requestHeader);
		
	}
	

	
	function HU_login($email,$password){
		
		global $APP;
			
		$requestHeader = array(
			"database: {$APP}",
		);
		
		$params = array(
			"email" => $email,
			"password" => $password
		);
		
		$result = HU_postRequest(API_URL . "hookup-auth.php",json_encode($params),$requestHeader);
		
		$resultDic = json_decode($result,true);

		return $resultDic['rows'][0]['value'];
		
	}
	
	function HU_findUserByName($name){
		return HU_dbFindByKey("find_user_by_name",$name);
	}
	
	function HU_findGroupByName($name){
		return HU_dbFindByKey("find_group_by_name",$name);
	}
	
	function HU_sendTextMessageToUser($from,$to,$message){

		$postData = array();
		
		$postData['type'] = "message";
		$postData['message_type'] = "text";
		$postData['body'] = $message;
		$postData['from_user_id'] = $from['_id'];
		$postData['from_user_name'] = $from['name'];
		$postData['created'] = time();
		$postData['modified'] = time();
		$postData['valid'] = true;
		$postData['to_user_name'] = $to['name'];
		$postData['to_user_id'] = $to['_id'];
		$postData['message_target_type'] = 'user';
		
		return HU_dbInsert($postData);
		
	}
	
	function HU_sendImageMessageToUser($from,$to,$bigImagePath,$smallImagePath){
		
		
		$bigImageId = HU_sendFile($bigImagePath);
		$smallImageId = HU_sendFile($smallImagePath);
		
		if(empty($bigImageId) || empty($smallImageId)){
			return;
		}
		
		$postData = array();
		
		$postData['type'] = "message";
		$postData['message_type'] = "image";
		$postData['body'] = "";
		$postData['from_user_id'] = $from['_id'];
		$postData['from_user_name'] = $from['name'];
		$postData['created'] = time();
		$postData['modified'] = time();
		$postData['valid'] = true;
		$postData['to_user_name'] = $to['name'];
		$postData['to_user_id'] = $to['_id'];
		$postData['message_target_type'] = 'user';

		$postData['picture_file_id'] = $bigImageId;
		$postData['picture_thumb_file_id'] = $smallImageId;

		return HU_dbInsert($postData);
		
	}
	
	function HU_subscribeGrupe($group,$user){
		
		$postData = array();
		
		$postData['type'] = "user_group";
		$postData['group_id'] = $group['_id'];
		$postData['user_id'] = $user['_id'];
		$postData['user_name'] = $user['name'];

		if(!isset($user['favorite_groups'])){
			$user['favorite_groups'] = array();
		}
		
		$user['favorite_groups'][] = $group['_id'];
		
		$result = HU_dbInsert($postData);

		return HU_dbUpdate($user['_id'],$user);
	
	}
	
	function HU_sendTextMessageToGroup($from,$to,$message){

		$postData = array();
		
		$postData['type'] = "message";
		$postData['message_type'] = "text";
		$postData['body'] = $message;
		$postData['from_user_id'] = $from['_id'];
		$postData['from_user_name'] = $from['name'];
		$postData['created'] = time();
		$postData['modified'] = time();
		$postData['valid'] = true;
		$postData['to_group_name'] = $to['name'];
		$postData['to_group_id'] = $to['_id'];
		$postData['message_target_type'] = 'group';
		
		return HU_dbInsert($postData);
		
	}
	
	function HU_sendNewsMessageToGroup($from,$to,$message,$url){

		$postData = array();
		
		$postData['type'] = "message";
		$postData['message_type'] = "news";
		$postData['body'] = $message;
		$postData['from_user_id'] = $from['_id'];
		$postData['from_user_name'] = $from['name'];
		$postData['created'] = time();
		$postData['modified'] = time();
		$postData['valid'] = true;
		$postData['to_group_name'] = $to['name'];
		$postData['to_group_id'] = $to['_id'];
		$postData['message_target_type'] = 'group';
		$postData['message_url'] = $url;
		
		return HU_dbInsert($postData);
		
	}
	
	function HU_createGroup($owner,$groupName,$password,$groupCategory,$description,$avatar,$thumbnail){

		$avatarFileId = HU_sendFile($avatar);
		$thumbnailFileId = HU_sendFile($thumbnail);

		$postData = array();
	
		$postData['type'] = "group";
		$postData['user_id'] = $owner['_id'];
		$postData['description'] = $description;
		$postData['name'] = $groupName;
		$postData['password'] = $password;
		$postData['category_id'] = $groupCategory['_id'];
		$postData['category_name'] = $groupCategory['title'];
		$postData['is_favourite'] = false;
		
		if(!empty($avatarFileId)){
			$postData['avatar_file_id'] = $avatarFileId;	
		}
		
		if(!empty($thumbnailFileId)){
			$postData['avatar_thumb_file_id'] = $thumbnailFileId;
		}
		
		return HU_dbInsert($postData);
		
	}
	
	function HU_getGroupCategories(){
		return HU_dbFindAll("find_group_categories");
	}
	//--------------------------------------------------------------



?>