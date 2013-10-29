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
		$this->saveUserToken(json_encode($userJson), $json['rows'][0]['value']['_id']);
		
		return $token;
		
    }

	function saveUserToken($userJson, $id)
	{
	
    	$this->logger->addDebug("Token saved : \n {$userJson} \n");
    	
    	list($header,$result) = $this->execCurl("PUT",$this->couchDBURL . "/{$id}",
    		$userJson,array("Content-Type: application/json"));

		$userJson = json_decode($userJson, true);
		$json = json_decode($result, true);
		
		$userJson['_rev'] = $json['rev'];
		
		$responseJson = array();
		
		$responseJson['rows'][0]['value'] = $userJson;
		
		return json_encode($responseJson);
		
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

    public function updateUser($user){

        $json = $this->doPutRequest($user['_id'],json_encode($user));
        $result = json_decode($json, true);


        if(isset($result['ok']) && $result['ok'] == 'true' && isset($result['id'])){
            return $this->getUserById($result['id']);
        }else
            $arr = array('message' => 'Update user error!', 'error' => 'logout');
            return json_encode($arr);;
    }

    public function getUserById($user_id){
        $json = $this->doGetRequest($user_id);
        $result = json_decode($json, true);

        return $result;
    }

    public function addNewMessage($messageData){

        $query = json_encode($messageData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
    }

    public function getUserMessages($startKey,$endKey,$descending,$limit,$skip){

        $query = "?startkey={$startKey}&endkey={$endKey}&descending={$descending}&limit={$limit}&skip={$skip}";
        $json = $this->doGetRequest("/_design/app/_view/find_user_message{$query}");

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

    public function getCommentCount($messageId){
        $query  = "?key=" . urlencode('"' . $messageId . '"');
        $json   = $this->doGetRequest("/_design/app/_view/get_comment_count{$query}", false);

        $result = json_decode($json, true);

        return $result;
    }

    public function getAvatarFileId($user_id){
        $query  = "?key=" . urlencode('"' . $user_id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_avatar_file_id{$query}", false);

        $result = json_decode($json, true);

        return $result;
    }


    public function createGroup($groupData){
        $query = json_encode($groupData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
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

	    $this->logger->addDebug($jsonToSave);
	    
	    return $body;

    }
    
    public function doDeleteRequest($id,$rev)
    {
    
		list($header,$body) = $this->execCurl("DELETE",$this->couchDBURL . "/{$id}?rev={$rev}");

	    return $body;

    }
}
