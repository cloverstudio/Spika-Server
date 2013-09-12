<?php

function stripBackSlash($ary){
	foreach($ary as $key => $value){
		$ary[$key] = str_replace("\\", "", $value);
	}
	return $ary;
}

function getYesNo($val){
	if($val)
		return "Yes";
	else
		return "No";
}

function getDeletedCount($DB){
	
	$result = executeQuery($DB,"select * from misc where s_key = 'deletedCount'");

	if(count($result) == 0){
		executeQuery($DB,generateQuery(INSERT,"misc",array(
			's_key' => "deletedCount",
			's_value' => 0,
			'created' => date('Y-m-d H:i:s',time())
		)));
		
		$result = executeQuery($DB,"select * from misc where s_key = 'deletedCount'");
	}
	
	return $result[0]['s_value'];
	
}
function truncateStr($str, $limit){
	
	if ($limit < 3){
		$limit = 3;
	}

	if (strlen($str) > $limit){
		return substr($str, 0, $limit - 3) . '...';
	} else {
		return $str;
	}
}

function getCPUUsage(){

	$stat1 = file('/proc/stat');
	sleep(1);
	$stat2 = file('/proc/stat');
	$info1 = explode(" ", preg_replace("!cpu +!", "", $stat1[0]));
	$info2 = explode(" ", preg_replace("!cpu +!", "", $stat2[0]));
	$dif = array();
	$dif['user'] = $info2[0] - $info1[0];
	$dif['nice'] = $info2[1] - $info1[1];
	$dif['sys'] = $info2[2] - $info1[2];
	$dif['idle'] = $info2[3] - $info1[3];
	$total = array_sum($dif);
	$cpu = array();
	foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);
	
	return 100 - $cpu['idle'];

}

function getMemoryUsage() {

	exec("free -m",$out);
	
	foreach($out as $row){
		
		$ary = preg_split("/\s+/", $row);
		
		foreach($ary as $row2){

			if(preg_match("/buffers\/cache/",$row2)){
				
				$free = $ary[3];
				$used = $ary[2];
				
			}
			
		}
		
	}
	
	if(isset($free) && isset($used)){
		return ($used / ($free + $used)) * 100;
	}else
		return 0;

}

function getHDUsage(){
	
	$ds = disk_total_space("/");
	$df = disk_free_space("/");
	
	return 100 - ($df / $ds) * 100;
	
}
function sendAPN($deviceToken, $json,$cert,$host){
	
	
	$apn_status = array(
		'0' => "No errors encountered",
		'1' => "Processing error",
		'2' => "Missing device token",
		'3' => "Missing topic",
		'4' => "Missing payload",
		'5' => "Invalid token size",
		'6' => "Invalid topic size",
		'7' => "Invalid payload size",
		'8' => "Invalid token",
		'255' => "unknown"
	);
	
	if(strlen($deviceToken) == 0) return;
	
	$ctx = stream_context_create();
	stream_context_set_option($ctx, 'ssl', 'local_cert', $cert);
	
	$fp = stream_socket_client($host, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
	
	if (!$fp) {
		_log("Failed to connect $err $errstr");
		return;
	}
	else {
		stream_set_blocking($fp, 0);
	}

	$identifiers = array();
	for ($i = 0; $i < 4; $i++) {
	    $identifiers[$i] = rand(1, 100);
	}

	$msg = chr(1) . chr($identifiers[0]) . chr($identifiers[1]) . chr($identifiers[2]) . chr($identifiers[3]) . pack('N', time() + 3600) 
    . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n",strlen($json)) . $json;
	
	stream_set_timeout($fp,SP_TIMEOUT);
	$result = fwrite($fp, $msg);
	
	if(!$result){

	}else{
		
		$read = array($fp);
		$null = null;
		$changedStreams = stream_select($read, $null, $null, 0, 1000000);
	
		if ($changedStreams === false) {    
		
		   _log("Error: Unabled to wait for a stream availability");
		   return false;
		   
		} elseif ($changedStreams > 0) {
		
		    $responseBinary = fread($fp, 6);
		    
		    if ($responseBinary !== false || strlen($responseBinary) == 6) {
		
		        $response = unpack('Ccommand/Cstatus_code/Nidentifier', $responseBinary);
		        $response['error_message'] = $apn_status[$response['status_code']];
		        $result = json_encode($response);
		        
		    }
		    
		} else {
			$result = "succeed";
		}
		
	}
	
	fclose($fp);
	
	return $result;

}

function getAPNResult($response){

	if($response === false)
		return false;
	
	$responseAry = json_decode($response,true);
	
	if(isset($responseAry['status_code']) && $responseAry['status_code'] != 0){
		return false;
	}
	
	return true;
	
}

function getGCMResult($response){
	return  preg_match("/\"success\":1/", $response);
}

function sendAPNProd($deviceToken, $json) {

	return sendAPN($deviceToken,$json,APN_PROD_CERT,'ssl://gateway.push.apple.com:2195');
	
}

function sendAPNDev($deviceToken, $json) {
	
	return sendAPN($deviceToken,$json,APN_DEV_CERT,'ssl://gateway.sandbox.push.apple.com:2195');
}

function sendGCM($deviceToken, $json) {

	_log("test");

	$apiKey = GCM_API_KEY;

	// Replace with real client registration IDs 
	$registrationIDs = array( $deviceToken);

	// Set POST variables
	$url = 'https://android.googleapis.com/gcm/send';

	$headers = array( 
		        'Authorization: key=' . $apiKey,
		        'Content-Type: application/json'
		    );
	// Open connection
	$ch = curl_init();

	// Set the url, number of POST vars, POST data
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS,$json);
	curl_setopt( $ch, CURLOPT_TIMEOUT,SP_TIMEOUT);

	// Execute post
	$result = curl_exec($ch);

	curl_close($ch);

	_log($result);
	
	return $result;

}

function processExists($processName){

	exec("ps -auxw",$process);
	
	$processExists = false;
	
	foreach($process as $line){
		
		if(preg_match("/{$processName}/", $line)){
			$processExists = true;
		}
			
	}
	
	return $processExists;
}

function processCount($processName){

	exec("ps -auxw",$process);
	
	$processCount = 0;
	
	foreach($process as $line){
		
		if(preg_match("/{$processName}/", $line)){
			$processCount++;
		}
			
	}
	
	return $processCount;
}


?>
