<?php
	
	//test
	include("./lib/startup.php");
	
	$_POST = stripBackSlash($_POST);
	
	$serviceProvidor = $_POST['s'];
	$token = $_POST['t'];
	$payload = $_POST['p'];
	
	if(empty($serviceProvidor) ||
		empty($token) ||
		empty($payload)){
			die('param error');
	}
	
	$serviceProvidor = urldecode($serviceProvidor);
	$token = urldecode($token);
	$payload = urldecode($payload);
	
	$state = STATE_WAIT; // waiting
	$queued = date("Y-m-d H:i:s",time());
	
	$DB = connectToDB();
	
	if(USE_QUEUE){

		$query = generateQuery(INSERT,"queue",array(
			'service_provider' => escp($serviceProvidor),
			'token' => escp($token),
			'payload' => escp($payload),
			'state' => escp($state),
			'queued' => escp($queued)
		));
		
		executeQuery($DB,$query);
		
		processExists(QUEUE_MANAGER_NAME);
		
		if(!processExists(QUEUE_MANAGER_NAME)){
			exec(PHP_COMMAND . " " . QUEUE_MANAGER_NAME . " > /dev/null &");
			_log("manager start");
		}else{
		}
					
	}else{

		// get latest ID
		executeQuery($DB,"lock tables queue");
		
		$result = executeQuery($DB,"select max(id) as maxid from queue");
		$id = $result[0]['maxid'] + 1;
		
		$state = STATE_PROCESSING;
		
		$query = generateQuery(INSERT,"queue",array(
			'id' => $id,
			'service_provider' => escp($serviceProvidor),
			'token' => escp($token),
			'payload' => escp($payload),
			'state' => escp($state),
			'queued' => escp($queued)
		));

		executeQuery($DB,$query);
		
		executeQuery($DB,"unlock tables queue");
		
		if($serviceProvidor == SERVICE_PROVIDOR_APN_PROD){
			$result = sendAPNProd($token,$payload);
		}else if($serviceProvidor == SERVICE_PROVIDOR_APN_DEV){
			$result = sendAPNDev($token,$payload);
		}else if($serviceProvidor == SERVICE_PROVIDOR_GCM){
			$result = sendGCM($token,$payload);
		}
		
		$now = date("Y-m-d H:i:s",time());
		
		$succeed = false;
		
		if($serviceProvidor == SERVICE_PROVIDOR_APN_PROD){
			$succeed = getAPNResult($result);
		}else if($serviceProvidor == SERVICE_PROVIDOR_APN_DEV){
			$succeed = getAPNResult($result);
		}else if($serviceProvidor == SERVICE_PROVIDOR_GCM){
			$succeed = getGCMResult($result);
		}

		$state = STATE_ERROR;
		if($succeed)
			$state = STATE_SUCCESS;

		$query = "update queue set state = {$state},sent='{$now}',result_from_service_provider='{$result}' where id = {$id}";
		executeQuery($DB,$query);

	}
	
	mysql_close($DB);
		

	die('ok');
?>