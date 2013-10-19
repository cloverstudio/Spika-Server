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

class CouchDb
{
	private $couchDBURL = "";
	private $app;


	public function __construct($URL,$app){
		$this->couchDBURL = $URL;
		$this->app = $app;
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
    	
    	$startKey = "\"{$email}\"";
	    $query = "?key={$startKey}";
	    $result = $this->doGetRequest("/_design/app/_view/find_user_by_email{$query}");
	    $nameResult = json_decode($result, true);

	    $result = array();
	    foreach ($nameResult['rows'] as $row) {
	        $result[] = $row['value'];
	    }
	    
	    return $this->stripParamsFromJson(json_encode($result, true));
    	
    }
    
    public function checkUserNameIsUnique($name){
    	
    	$startKey = "\"{$name}\"";
	    $query = "?key={$startKey}";
	    $result = $this->doGetRequest("/_design/app/_view/find_user_by_name{$query}");
	    $nameResult = json_decode($result, true);

	    $result = array();
	    foreach ($nameResult['rows'] as $row) {
	        $result[] = $row['value'];
	    }
	    
	    return $this->stripParamsFromJson(json_encode($result, true));
    	
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
    
    public function doSpikaAuth($requestBody)
    {
    	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Receive Auth Request : \n {$requestBody} \n");
    	
    	$reqJson = json_decode($requestBody, true);
    	
    	$email = $reqJson['email'];
    	$password = $reqJson['password'];
		
		$emailQuery = '"' . $email . '"';
		list($header,$result) = $this->execCurl("GET",$this->couchDBURL . "/_design/app/_view/find_user_by_email?key=" . $emailQuery);
		
		$this->app['monolog']->addDebug("Receive Auth Request : \n {$result} \n");
		$json = json_decode($result, true);
		
		$this->app['monolog']->addDebug($result);
		
		if (empty($json['rows'][0]['value']['email'])) {
		    $arr = array('message' => 'User not found!', 'error' => 'logout');
		
		    return json_encode($arr);
		}
		
		if ($json['rows'][0]['value']['password'] != $reqJson['password']) {
		    $arr = array('message' => 'Wrong password!', 'error' => 'logout');
		
		    return json_encode($arr);
		}
		
		$token = \Spika\Utils::randString(40, 40);
		
		$json['rows'][0]['value']['token'] = $token;
		$json['rows'][0]['value']['token_timestamp'] = time();
		$json['rows'][0]['value']['last_login'] = time();
		
		$userJson = $json['rows'][0]['value'];
		
		return $this->saveUserToken(json_encode($userJson), $json['rows'][0]['value']['_id']);
		
    }

	function saveUserToken($userJson, $id)
	{
	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Token saved : \n {$userJson} \n");

    	
    	list($header,$body) = $this->execCurl("PUT",$this->couchDBURL . "/{$id}",
    		$userJson,array("Content-Type: application/json"));

		$userJson = json_decode($userJson, true);
		$json = json_decode($result, true);
		
		$userJson['_rev'] = $json['rev'];
		
		$responseJson = array();
		
		$responseJson['rows'][0]['value'] = $userJson;
		
		return json_encode($responseJson);
		
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
		
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		
		curl_close($curl);
		
		return array($header,$body);
		
    }
    
    public function doPostRequest($requestBody)
    {
    	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Receive Post Request : \n {$requestBody} \n");
    	
    	list($header,$body) = $this->execCurl("POST",$this->couchDBURL,$requestBody,array("Content-Type: application/json"));
    	
	    return $body;

    }
 
    public function doGetRequestGetHeader($queryString,$stripCredentials = true)
    {
    	
    	$couchDBQuery = $this->couchDBURL . "/" . $queryString;
    	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Receive Get Request : \n {$couchDBQuery} \n");
    	
		list($header,$body) = $this->execCurl("GET",$couchDBQuery);
		
		if($stripCredentials)
			return array($header,$this->stripParamsFromJson($body));
		else
			return array($header,$body);
    
	}
	
	
    public function doGetRequest($queryString,$stripCredentials = true)
    {
    	
    	$couchDBQuery = $this->couchDBURL . "/" . $queryString;
    	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Receive Get Request : \n {$couchDBQuery} \n");
    	
		list($header,$body) = $this->execCurl("GET",$couchDBQuery);
		
		if($stripCredentials)
			return $this->stripParamsFromJson($body);
		else
			return $body;
    
	}
	
    public function doPutRequest($id,$requestBody)
    {
    	
    	if(isset($this->app['monolog']))
			$this->app['monolog']->addDebug("Receive Put Request : \n {$requestBody} \n");
	
		// merge with original json
		// put request is update in couchdb. for all get requests backend cuts off password and email
		// so I have to merge with original data here. Other wise password will gone.
		list($header,$originalJSON) = $this->execCurl("GET",$this->couchDBURL . "/{$id}");
		
		$originalData = json_decode($originalJSON,true);
		$newData = json_decode($requestBody,true);
		$newData["_rev"] = $originalData["_rev"];
		
		$mergedData = array_merge($originalData,$newData);
		$jsonToSave = json_encode($mergedData,true);
	    	    
	    // save
	    list($header,$body) = $this->execCurl("PUT",$this->couchDBURL . "/{$id}",$jsonToSave,array("Content-Type: application/json"));

	    $this->app['monolog']->addDebug($jsonToSave);
	    
	    return $body;

    }
    
    public function doDeleteRequest($id,$rev)
    {
    
		list($header,$body) = $this->execCurl("DELETE",$this->couchDBURL . "/{$id}?rev={$rev}");

	    return $body;

    }
}
