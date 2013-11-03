<?php
/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Spika\Db;

use Spika\Db\DbInterface;
use Psr\Log\LoggerInterface;

class CouchDb implements DbInterface

{
	private $couchDBURL = "";
	private $logger;


	public function __construct($URL, LoggerInterface $logger){
		$this->couchDBURL = $URL;
		$this->logger     = $logger;
	}
	
	private function stripParamsFromJson($json)
	{
	
	    $removeParams = array(
	        "password",
	        "token"
	    );
	
	    foreach ($removeParams as $paramToRemove) {
	        $json = preg_replace("/,\"{$paramToRemove}\":\"[0-9a-zA-Z]*\"}/", "}", $json);
	        $json = preg_replace("/,\"{$paramToRemove}\":\"[0-9a-zA-Z]*\",/", ",", $json);
	    }
	
	    return $json;
	
	}

	public function unregistToken($userId){
	
	    $result = $this->doGetRequest("/{$userId}");
	    $dic = json_decode($result, true);
	
	    unset($dic['ios_push_token']);
	    unset($dic['android_push_token']);
	
	    $dic['online_status'] = "offline";
	    $jsonToSave = json_encode($dic);
	    
	    $result = $this->doPutRequest($userId,$jsonToSave);

	    return "OK";
		
	}
	
    public function checkEmailIsUnique($email){
    	
    	$startKey = urlencode("\"{$email}\"");
	    $query = "?key={$startKey}";
	    $result = $this->doGetRequest("/_design/app/_view/find_user_by_email{$query}");
	    
	    $resultAry = json_decode($result, true);

	    $result = array();
	    
	    foreach ($resultAry['rows'] as $row) {
	        $result[] = $row['value'];
	    }
	    
	    return $result;
    	
    }
    
    public function checkUserNameIsUnique($name){
    	
    	$startKey = urlencode("\"{$name}\"");
	    $query = "?key={$startKey}";
	    
	    $result = $this->doGetRequest("/_design/app/_view/find_user_by_name{$query}");
	    $nameResult = json_decode($result, true);

	    $result = array();
	    foreach ($nameResult['rows'] as $row) {
	        $result[] = $row['value'];
	    }
	    
	    return $result;
    	
    }
    
    public function checkGroupNameIsUnique($name){
    	
    	$startKey = "\"{$name}\"";
	    $query = "?key={$startKey}";
	    $result = $this->doGetRequest("/_design/app/_view/find_group_by_name{$query}");
	    $nameResult = json_decode($result, true);

	    $result = array();
	    foreach ($nameResult['rows'] as $row) {
	        $result[] = $row['value'];
	    }
	    
	    return $this->stripParamsFromJson(json_encode($result, true));
    	
    }
    
    public function doSpikaAuth($email,$password)
    {
		
		$emailQuery = urlencode('"' . $email . '"');
		
		list($header,$result) = $this->execCurl("GET",$this->couchDBURL . "/_design/app/_view/find_user_by_email?key=" . $emailQuery);
		
		$this->logger->addDebug("Receive Auth Request : \n {$result} \n");
		$json = json_decode($result, true);
		
		$this->logger->addDebug($result);
		
		if (empty($json['rows'][0]['value']['email'])) {
		    $arr = array('message' => 'User not found!', 'error' => 'logout');
		
		    return json_encode($arr);
		}
		
		if ($json['rows'][0]['value']['password'] != $password) {
		    $arr = array('message' => 'Wrong password!', 'error' => 'logout');
		
		    return json_encode($arr);
		}
		
		$token = \Spika\Utils::randString(40, 40);
		
		$json['rows'][0]['value']['token'] = $token;
		$json['rows'][0]['value']['token_timestamp'] = time();
		$json['rows'][0]['value']['last_login'] = time();
		
		$userJson = $json['rows'][0]['value'];

		$result = $this->saveUserToken(json_encode($userJson), $json['rows'][0]['value']['_id']);
		
		return json_encode($result);

    }

	function saveUserToken($userJson, $id)
	{
	
    	$this->logger->addDebug("Token saved : \n {$userJson} \n");
    	
    	list($header,$result) = $this->execCurl("PUT",$this->couchDBURL . "/{$id}",
    		$userJson,array("Content-Type: application/json"));

		$userJson = json_decode($userJson, true);
		$json = json_decode($result, true);
		
		$userJson['_rev'] = $json['rev'];
		
		return $userJson;
		
	}

    /**
     * Finds a user by Token
     *
     * @param  string $token
     * @return array
     */
    public function findUserByToken($token)
    {
        $query  = "?key=" . urlencode('"' . $token . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_token{$query}", false);
        $result = json_decode($json, true);
        
        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }
    
    /**
     * Finds a user by User ID
     *
     * @param  string $id
     * @return array
     */
    public function findUserById($id)
    {


        $query  = "?key=" . urlencode('"' . $id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_id{$query}", true);
        $result = json_decode($json, true);


        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }

    /**
     * Finds a user by email
     *
     * @param  string $email
     * @return array
     */
    public function findUserByEmail($email)
    {


        $query  = "?key=" . urlencode('"' . $email . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_email{$query}", true);
        $result = json_decode($json, true);


        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }


    /**
     * Finds a user by name
     *
     * @param  string $name
     * @return array
     */
    public function findUserByName($name)
    {


        $query  = "?key=" . urlencode('"' . $name . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_name{$query}", true);
        $result = json_decode($json, true);
        
        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $this->stripParamsFromJson($result['rows'][0]['value'])
            : null;
    }

    /**
     * Search a user by name
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByName($name){

    	$escapedKeyword = urlencode($name);
	    $startKey = "\"{$escapedKeyword}\"";
	    $endKey = "\"{$escapedKeyword}ZZZZ\"";
	    $query = "?startkey={$startKey}&endkey={$endKey}";
    	
    	//$result = $this->app['spikadb']->doGetRequest("/_design/app/_view/searchuser_name{$query}");
    	$result = $this->doGetRequest("/_design/app/_view/searchuser_name{$query}");

		return $result;
    }
    
    /**
     * Search a user by gender
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByGender($gender){
	    $query = "?key=\"{$gender}\"";
    	$result = $this->doGetRequest("/_design/app/_view/searchuser_gender{$query}");
    	return $result;
    }
    
    /**
     * Search a user by age
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByAge($ageFrom,$ageTo){

		$ageQuery = "";
		
		if (empty($ageFrom) && empty($ageTo)){
			return "";
		}
		
		if (!empty($ageFrom) && !empty($ageTo)) {
		    $ageQuery = "?startkey={$ageFrom}&endkey={$ageTo}";
		}
		
		if (!empty($ageFrom) && empty($ageTo)) {
		    $ageQuery = "?startkey={$ageFrom}";
		}
		
		if (empty($ageFrom) && !empty($ageTo)) {
		    $ageQuery = "?endkey={$ageTo}";
		}
		
		$result = $this->doGetRequest("/_design/app/_view/searchuser_age{$ageQuery}");
		
		return $result;
    }

    /**
     * Gets user activity summary
     *
     * @param  string $user_id
     * @return array
     */
    public function getActivitySummary($user_id)
    {
        $query  = "?key=" . urlencode('"' . $user_id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/user_activity_summary{$query}", false);
        $result = json_decode($json, true);

        return $result;
    }


    /**
     * create a user
     *
     * @param  string $json
     * @return id
     */
    public function createUser($userName,$email,$password)
    {
        
        $requestBodyAry = array();
        
        $requestBodyAry['name'] = $userName;
        $requestBodyAry['email'] = $email;
        $requestBodyAry['password'] = $password;
        $requestBodyAry['type'] = "user";
		$requestBodyAry['online_status'] = "online";
		$requestBodyAry['max_contact_count'] = 20;
		$requestBodyAry['max_favorite_count'] = 10;
    
		$requestBodyJson = json_encode($requestBodyAry);
		
        $json   = $this->doPostRequest($requestBodyJson);
        $result = json_decode($json, true);
        
        if(isset($result['ok']) && $result['ok'] == 'true' && isset($result['id'])){
	        return $result['id'];
        }else
        	return null;

    }

    public function updateUser($userId,$user){
        
        $user['_id'] = $userId;
        
        $json = $this->doPutRequest($userId,json_encode($user));
        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 1 && isset($result['id'])){
            return $this->getUserById($result['id']);
        }else
            $arr = array('message' => 'Update user error!', 'error' => 'logout');
            return json_encode($arr);;
    }

    public function getUserById($userId){
        $json = $this->doGetRequest("/{$userId}");
        $result = json_decode($json, true);

        return $result;
    }

    public function addNewUserMessage($addNewMessage = 'text',$fromUserId,$toUserId,$message,$additionalParams=array()){
		
		$messageData = array();
		
        $messageData['from_user_id']=$fromUserId;
        $messageData['to_user_id']=$toUserId;
        $messageData['body']=$message;
        $messageData['modified']=time();
        $messageData['created']=time();
        $messageData['type']='message';
        $messageData['message_target_type']='user';
        $messageData['message_type']=$addNewMessage;
        $messageData['valid']=true;

		if(is_array($additionalParams)){
			foreach($additionalParams as $key => $value){
				$messageData[$key]=$value;
			}
		}
		
        if(isset($fromUserId)){
            $fromUserData=$this->findUserById($fromUserId);
            $messageData['from_user_name']=$fromUserData['name'];
        }else{
            return null;
        }

        if(isset($toUserId)){
            $toUserData=$this->findUserById($toUserId);
            $messageData['to_user_name']=$toUserData['name'];
        }else{
            return null;
        }
                
        $query = json_encode($messageData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
    }

    public function addNewGroupMessage($addNewMessage = 'text',$fromUserId,$toGroupId,$message,$additionalParams=array()){
		
		$messageData = array();
		
        $messageData['from_user_id']=$fromUserId;
        $messageData['to_group_id']=$toGroupId;
        $messageData['body']=$message;
        $messageData['modified']=time();
        $messageData['created']=time();
        $messageData['type']='message';
        $messageData['message_target_type']='group';
        $messageData['message_type']=$addNewMessage;
        $messageData['valid']=true;

		if(is_array($additionalParams)){
			foreach($additionalParams as $key => $value){
				$messageData[$key]=$value;
			}
		}
		
        if(isset($fromUserId)){
            $fromUserData=$this->findUserById($fromUserId);
            $messageData['from_user_name']=$fromUserData['name'];
        }else{
            return null;
        }

        if(isset($toGroupId)){
            $toGroupData=$this->findUserById($toGroupId);
            $messageData['to_group_name']=$toGroupData['name'];
        }else{
            return null;
        }
                
        $query = json_encode($messageData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
    }


    public function getUserMessages($ownerUserId,$targetUserId,$count,$offset){
		
		$startKey = "[\"{$ownerUserId}\",\"{$targetUserId}\",{}]";
		$endKey = "[\"{$ownerUserId}\",\"{$targetUserId}\"]";
        $query = "?startkey={$startKey}&endkey={$endKey}&descending=true&limit={$count}&skip={$offset}";
        $json = $this->doGetRequest("/_design/app/_view/find_user_message{$query}");

        $result = json_decode($json, true);

        return $result;
    }

    public function getGroupMessages($targetGroupId,$count,$offset){
		
		$startKey = "[\"{$targetGroupId}\",{}]";
		$endKey = "[\"{$targetGroupId}\"]";
        $query = "?startkey={$startKey}&endkey={$endKey}&descending=true&limit={$count}&skip={$offset}";
        $json = $this->doGetRequest("/_design/app/_view/find_group_message{$query}");

        $result = json_decode($json, true);

        return $result;
    }
    
    public function getUserContacts($user_id,$include_docs){
        $query = "?key=". urlencode('"' . $user_id . '"')."&include_docs={$include_docs}";
        $json = $this->doGetRequest("/_design/app/_view/find_contacts{$query}");

        $result = json_decode($json, true);
        return $result;
    }

    public function getEmoticons(){
        $json = $this->doGetRequest("/_design/app/_view/find_all_emoticons");
        $result = json_decode($json, true);

        return $result;
    }

    public function getEmoticonImage($emoticonId){
        $json = $this->doGetRequest("/{$emoticonId}");
        $result = json_decode($json, true);
		
		if(!isset($result['_attachments'])){
			return null;
		}
		
		foreach($result['_attachments'] as $imageName => $image){
			$imageBody = $this->doGetRequest("/{$emoticonId}/{$imageName}");
			return $imageBody;
		}
		
        return null;
    }

    public function getCommentCount($messageId){
        $query  = "?key=" . urlencode('"' . $messageId . '"');
        $json   = $this->doGetRequest("/_design/app/_view/get_comment_count{$query}", false);

        $result = json_decode($json, true);

        return $result;
    }

	public function addNewComment($messageId,$userId,$comment){
		
		$userData=$this->findUserById($userId);
		
		$commentData = array();
		
		$commentData['message_id'] = $messageId;
		$commentData['user_id'] = $userId;
		$commentData['type'] = 'comment';
		$commentData['comment'] = $comment;
		$commentData['user_name'] = $userData['name'];
		$commentData['created'] = time();
		
        $query = json_encode($commentData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            return $result;;
        }

        return null;
		
	}

    public function getAvatarFileId($user_id){
        $query  = "?key=" . urlencode('"' . $user_id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_avatar_file_id{$query}", false);

        $result = json_decode($json, true);

        return $result;
    }


    public function createGroup($name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL){

		// get category name
		$categoryJson = $this->doGetRequest("/" . $categoryId, false);
		$categoryArray = json_decode($categoryJson,true);
		
		$categoryName = "";
		if(!empty($categoryArray['title'])){
			$categoryName = $categoryArray['title'];
		}
		
    	$groupData = array(
    		'name' => $name,
    		'group_password' => $password,
    		'category_id' => $categoryId,
    		'category_name' => $categoryName,
    		'description' => $description,
    		'type' => 'group',
    		'user_id' => $ownerId,
    		'is_favourite' => false,
    		'avatar_file_id' => $avatarURL,
    		'avatar_thumb_file_id' => $thumbURL
    	);
    
        $query = json_encode($groupData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
    }

    public function findGroupById($id)
    {


        $query  = "?key=" . urlencode('"' . $id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_group_by_id{$query}", true);
        $result = json_decode($json, true);


        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }


    private function execCurl($method,$URL,$postBody = "",$httpheaders = array()){
    
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $URL);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheaders);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		
		if($method == "POST")
			curl_setopt($curl, CURLOPT_POST, true);
			
		if($method == "PUT")
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			
		if($method == "DELETE")
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
			
			
		if(!empty($postBody))
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postBody);
			
		$response = curl_exec($curl);
		
		if($response === false){
    		$error = curl_error($curl);
    		return array("",$error);
		}
		
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		
		curl_close($curl);
		
		return array($header,$body);
		
    }
    
    public function doPostRequest($requestBody)
    {
    	
    	$this->logger->addDebug("Receive Post Request : \n {$requestBody} \n");
    	
    	list($header,$body) = $this->execCurl("POST",$this->couchDBURL,$requestBody,array("Content-Type: application/json"));
    	
	    return $body;

    }
 
    public function doGetRequestGetHeader($queryString,$stripCredentials = true)
    {
    	
    	$couchDBQuery = $this->couchDBURL . "/" . $queryString;
    	
    	$this->logger->addDebug("Receive Get Request : \n {$couchDBQuery} \n");
    	
		list($header,$body) = $this->execCurl("GET",$couchDBQuery);
		
		if($stripCredentials)
			return array($header,$this->stripParamsFromJson($body));
		else
			return array($header,$body);
    
	}
	
	
    public function doGetRequest($queryString,$stripCredentials = true)
    {
    	
    	$couchDBQuery = $this->couchDBURL . $queryString;
    	
    	$this->logger->addDebug("Receive Get Request : \n {$couchDBQuery} \n");
    	
		list($header,$body) = $this->execCurl("GET",$couchDBQuery);
		
		if($stripCredentials)
			return $this->stripParamsFromJson($body);
		else
			return $body;
    
	}
	
    public function doPutRequest($id,$requestBody)
    {
    	
		$this->logger->addDebug("Receive Put Request : \n {$requestBody} \n");
	
		// merge with original json
		// put request is update in couchdb. for all get requests backend cuts off password and email
		// so I have to merge with original data here. Other wise password will gone.
		list($header,$originalJSON) = $this->execCurl("GET",$this->couchDBURL . "/{$id}");

		$originalData = json_decode($originalJSON,true);
		$newData = json_decode($requestBody,true);

        if(isset($originalData["_rev"])) $newData["_rev"] = $originalData["_rev"];
		
		$mergedData = array_merge($originalData,$newData);
		$jsonToSave = json_encode($mergedData,true);
	    	    
	    // save
	    list($header,$body) = $this->execCurl("PUT",$this->couchDBURL . "/{$id}",$jsonToSave,array("Content-Type: application/json"));

	    return $body;

    }
    
    public function doDeleteRequest($id,$rev)
    {
    
		list($header,$body) = $this->execCurl("DELETE",$this->couchDBURL . "/{$id}?rev={$rev}");

	    return $body;

    }
    
    /*
    public function addToContact($owserUserId,$tagetUserId){
	    
    }
    
    public function removeFromContact($owserUserId,$tagetUserId){
	    
    }*/

}
