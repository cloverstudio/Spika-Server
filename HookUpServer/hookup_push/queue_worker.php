<?php
	
	if(empty($argv[1]))
		die();
		
	define("ROOT_DIR",dirname(__FILE__));
	include(ROOT_DIR . "/lib/startup.php");
	
	$queueId = $argv[1];
	
	$DB = connectToDB();
	
	$lastPushAry = executeQuery($DB,"select * from queue where id = {$queueId}");
	
	if(count($lastPushAry) > 0){
		
		$lastPush = $lastPushAry[0];
		
		$now = date("Y-m-d H:i:s",time());
		
		
		$token = $lastPush['token'];
		$payload = $lastPush['payload'];
		$serviceProvidor = $lastPush['service_provider'];
		
		$result = false;
		
		if($serviceProvidor == SERVICE_PROVIDOR_APN_PROD){
			$result = sendAPNProd($token,$payload);
		}else if($serviceProvidor == SERVICE_PROVIDOR_APN_DEV){
			$result = sendAPNDev($token,$payload);
		}else if($serviceProvidor == SERVICE_PROVIDOR_GCM){
			$result = sendGCM($token,$payload);
		}
		
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
		
		$now = date("Y-m-d H:i:s",time());
		//executeQuery($DB,"update queue set state = {$state},sent='{$now}',result_from_service_provider='{$result}' where id = {$lastPush['id']}");
		executeQuery($DB,"delete from queue where id = {$lastPush['id']}");
		
	}

	_log("Push sent to queue {$queueId}");
	
	mysql_close($DB);
	
?>