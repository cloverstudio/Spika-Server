<?php
	
	$current_user = null;
	
	/* HookUp app requests management */
	include("./lib/startup.php");
	
	if(!preg_match("/hookup-api.php/",$_SERVER['REQUEST_URI'])){
		$_SERVER['QUERY_STRING'] = str_replace("/HookUpServer/hookup/","",$_SERVER['REQUEST_URI']);
	}
		
	if(preg_match("/checkhookupserver/",$_SERVER['REQUEST_URI'])){
		die('yes');
	}
		
	$skipToken = false;
	
	if(preg_match("/\.jpg$/",$_SERVER['REQUEST_URI']))
		$skipToken = true;
		
	/* Check the request type */
	if($_SERVER['REQUEST_METHOD'] == 'GET'){

		$getRequest = $db_url . $_SERVER['QUERY_STRING'];

		if(!$skipToken)
			checkToken();
			
		getRequest($getRequest);

	} else if($_SERVER['REQUEST_METHOD'] == 'POST') {

		
		$postRequest = $db_url . $_SERVER['QUERY_STRING'];
		$reqBody = file_get_contents('php://input');		
		
		if(!$skipToken)
			checkToken();

		postRequest($postRequest, $reqBody);

	} else if($_SERVER['REQUEST_METHOD'] == 'PUT'){

		$putRequest = $db_url . $_SERVER['QUERY_STRING'];
		$reqBody = file_get_contents('php://input');

		if(!$skipToken)
			checkToken();

		putRequest($putRequest, $reqBody);

	} else if($_SERVER['REQUEST_METHOD'] == 'DELETE'){
		
		$deleteRequest = $_SERVER['QUERY_STRING'];

		if(empty($deleteRequest)){
				$arr = array('message' => 'Invalid delete request!', 'error' => 'logout');

				echo json_encode($arr);

			//	die();
		}
		
		if(!$skipToken)
			checkToken();
		
		deleteRequest($db_url . $deleteRequest);

	}

	/* activate plugins */
	activateNotificationHandler();
	
	/* Check user request token validity */
	function checkToken(){

		global $db_username, $db_password, $db_url,$current_user;

		$headers = apache_request_headers();

		$id = $headers['user_id'];
		$token = $headers['token'];

		if($id != 'create_user'){

			$curl = curl_init();

			curl_setopt($curl, CURLOPT_URL, $db_url . "_design/app/_view/find_user_by_id?key=" . urlencode('"' . $id . '"'));	
			curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);	
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$result = curl_exec($curl);

			curl_close($curl);

			$json = json_decode($result, true);

			if(empty($id) || empty($json['rows'][0]['value']['_id']) || empty($token) || empty($json['rows'][0]['value']['token']) || empty($json['rows'][0]['value']['token_timestamp'])){
				$arr = array('message' => 'No token sent!', 'error' => 'logout');
				header("HTTP/1.0 403 Forbidden");
				echo json_encode($arr);

				_log("No token sent! " . json_encode($arr));
				_log("ID: " . $id);
				_log("TOKEN " . $token);
				_log("JSON_ID: " . $json['rows'][0]['value']['_id']);
				_log("JSON_TOKEN " . $json['rows'][0]['value']['token']);
				_log("JSON_TOKEN_TIMESTAMP " . $json['rows'][0]['value']['token_timestamp']);
				_log("                      ");

				die();
			}

			if($id == $json['rows'][0]['value']['_id']){
				if($token == $json['rows'][0]['value']['token']){
					$tokenTimestamp = $json['rows'][0]['value']['token_timestamp'];
					$currentTimestamp = time();

					$tokenTime = $tokenTimestamp + TOKEN_VALID_TIME;

					if($tokenTime < $currentTimestamp){
						$arr = array('message' => 'Token expired!', 'error' => 'logout');
						header("HTTP/1.0 403 Forbidden");
						echo json_encode($arr);

						_log($tokenTime . " > " . $currentTimestamp);
						_log("Token expired! " . json_encode($arr));
						_log("                      ");

						die();
					}
				}else{
						$arr = array('message' => 'Invalid token', 'error' => 'logout');
						header("HTTP/1.0 403 Forbidden");
						echo json_encode($arr);

						_log($tokenTime . " > " . $currentTimestamp);
						_log("Token expired! " . json_encode($arr));
						_log("                      ");

						die();
				}
			}else{
					$arr = array('message' => 'Invalid user', 'error' => 'logout');
					header("HTTP/1.0 403 Forbidden");
					echo json_encode($arr);

					_log($tokenTime . " > " . $currentTimestamp);
					_log("Token expired! " . json_encode($arr));
					_log("                      ");

					die();
			}


			$tokenTimestamp1 = $json['rows'][0]['value']['token_timestamp'];
			$currentTimestamp1 = time();

			$tokenTime1 = $tokenTimestamp1 + 5000;
			//_log($tokenTime1 . " < " . $currentTimestamp1);
			//_log("Token Valid!");
			//_log("                      ");
			
			$current_user = $json['rows'][0]['value'];

		}
	}


?>