<?php
	
	include("./lib/startup.php");


	if(!empty($_GET['user_id'])){

		$result = doGet(HOST . "/" . DB . "/{$_GET['user_id']}");
		$dic = json_decode($result,true);
		
		unset($dic['ios_push_token']);
		unset($dic['android_push_token']);
		
		$dic['online_status'] = "offline";
		
		$jsonToSave = json_encode($dic);
		
		_log($jsonToSave);
		
		$curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, HOST . "/" . DB);	
		curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));	
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonToSave);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($curl, CURLOPT_HEADER, 1);
		
		$response = curl_exec($curl);

		echo "OK";
		
	}else{
		
		header("HTTP/1.0 500 Server error");
		
	}

	
?>