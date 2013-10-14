<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Spika;

use Silex\Application;
use Silex\ServiceProviderInterface;

class SpikaDBProvider implements ServiceProviderInterface
{

    public function register(\Silex\Application $app)
    {
    
        $app['spikadb'] = $app->share(function () use ($app) {
            return new SpikaDBHandler(
                $app['couchdb.couchDBURL'],
                $app
            );
        });

    }

    public function boot(Application $app)
    {
    }

}

class SpikaDBHandler
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

	private function randString($min = 5, $max = 8)
	{
	    $length = rand($min, $max);
	    $string = '';
	    $index = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    for ($i = 0; $i < $length; $i++) {
	        $string .= $index[rand(0, strlen($index) - 1)];
	    }
	    return $string;
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
    	
		$curl = curl_init();
		
		$emailQuery = '"' . $email . '"';
		curl_setopt($curl, CURLOPT_URL, $this->couchDBURL . "/_design/app/_view/find_user_by_email?key=" . $emailQuery);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		
		$result = curl_exec($curl);
		curl_close($curl);
		
		$this->app['monolog']->addDebug("Receive Auth Request : \n {$result} \n");
		$json = json_decode($result, true);
		
		
		if (empty($json['rows'][0]['value']['email'])) {
		    $arr = array('message' => 'User not found!', 'error' => 'logout');
		
		    return json_encode($arr);
		}
		
		if ($json['rows'][0]['value']['password'] != $reqJson['password']) {
		    $arr = array('message' => 'Wrong password!', 'error' => 'logout');
		
		    return json_encode($arr);
		}
		
		$token = $this->randString(40, 40);
		
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


		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $this->couchDBURL . "/" . $id);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt(
		    $curl,
		    CURLOPT_HTTPHEADER,
		    array("Content-Type: application/json", 'Content-Length: ' . strlen($userJson))
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $userJson);
		
		$result = curl_exec($curl);
		
		curl_close($curl);
		
		$userJson = json_decode($userJson, true);
		$json = json_decode($result, true);
		
		$userJson['_rev'] = $json['rev'];
		
		$responseJson = array();
		
		$responseJson['rows'][0]['value'] = $userJson;
		
		return json_encode($responseJson);
		
	}
	

    public function doPostRequest($requestBody)
    {
    	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Receive Post Request : \n {$requestBody} \n");
    	
    	$curl = curl_init();
    	
	    curl_setopt($curl, CURLOPT_URL, $this->couchDBURL);
	    
	    // ADD this line if couchdb uses basic authorization
	    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
	    
	    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
	    curl_setopt($curl, CURLOPT_POST, true);
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, 1);
	
	    $response = curl_exec($curl);
	
	    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
	    $header = substr($response, 0, $header_size);
	    $body = substr($response, $header_size);
	
	    curl_close($curl);

	    return $body;

    }
    
    public function doGetRequest($queryString)
    {
    	
    	$couchDBQuery = $this->couchDBURL . "/" . $queryString;
    	
    	if(isset($this->app['monolog']))
    		$this->app['monolog']->addDebug("Receive Get Request : \n {$couchDBQuery} \n");
    	
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $couchDBQuery);

	    // ADD this line if couchdb uses basic authorization
	    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		
		$response = curl_exec($curl);
		
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		
		curl_close($curl);
		
		return $this->stripParamsFromJson($body);
    
	}
	
    public function doPutRequest($id,$requestBody)
    {
    	
    	if(isset($this->app['monolog']))
			$this->app['monolog']->addDebug("Receive Put Request : \n {$requestBody} \n");
	
		// merge with original json
		// put request is update in couchdb. for all get requests backend cuts off password and email
		// so I have to merge with original data here. Other wise password will gone.
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->couchDBURL . "/{$id}");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		$response = curl_exec($curl);		
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		$originalJSON = $body;
		
		$originalData = json_decode($originalJSON,true);
		$newData = json_decode($requestBody,true);
		
		$mergedData = array_merge($originalData,$newData);
		
		$jsonToSave = json_encode($mergedData,true);
				
		$curl = curl_init();

	    curl_setopt($curl, CURLOPT_URL, $this->couchDBURL . "/{$id}");
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt(
	        $curl,
	        CURLOPT_HTTPHEADER,
	        array("Content-Type: application/json", 'Content-Length: ' . strlen($jsonToSave))
	    );
	    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonToSave);
	    curl_setopt($curl, CURLOPT_HEADER, 1);
	    
	    $response = curl_exec($curl);

    	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);
		
		$this->app['monolog']->addDebug("JSON to save : \n {$jsonToSave} \n");
		$this->app['monolog']->addDebug("JSON original : \n {$jsonToSave} \n");
		
	    return $body;

    }
    
    public function doDeleteRequest($id)
    {
		
		$curl = curl_init();

	    curl_setopt($curl, CURLOPT_URL, $this->couchDBURL . "/{$id}");
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt(
	        $curl,
	        CURLOPT_HTTPHEADER,
	        array("Content-Type: application/json", 'Content-Length: ' . strlen($requestBody))
	    );

	    curl_setopt($curl, CURLOPT_HEADER, 1);
	
	    $response = curl_exec($curl);

    	$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);

	    return $body;

    }


}
?>