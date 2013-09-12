<?php
	
	include("./lib/startup.php");
	
	require_once("qdmail.php");
	require_once("qdsmtp.php");

	function mySendMail($to,$subject,$body,$email){
	
		mail($to,$subject,$body,$email);
		
	}
	
	
	if(!empty($_GET['email'])){
		$nameQuery = urldecode($_GET['email']);
		$startKey = "\"{$nameQuery}\"";
		$query = "?key={$startKey}";
		$result = doGet(HOST . "/" . DB . "/_design/app/_view/find_user_by_email{$query}");
		$nameResult = json_decode($result,true);
		
		$result = array();
		
		if(count($nameResult['rows'] != 0)){
			
			$user = $nameResult['rows'][0]['value'];
			
			$email = $user['email'];
			
			$body = sprintf(REMINDER_BODY,$user['password']);
			
			mySendMail($email,"hookup password reminder",$body,REMINDER_FROM);
			
			print "OK";
			
			die();
		}

	}

	print "NG";
	
?>