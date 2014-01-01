<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class AsyncTaskController extends SpikaBaseController
{
    public function connect(Application $app)
    {
    
        $controllers = $app['controllers_factory'];
		$self = $this;
		
		$controllers->post('/notifyNewDirectMessage', function (Request $request) use ($self,$app) {
			
			$host = $request->getHttpHost();
			if($host != "localhost"){
				return $self->returnErrorResponse("invalid access to internal API");
			}
			
			$requestBody = $request->getContent();
			$requestData = json_decode($requestBody,true);
			
			if(empty($requestData['messageId']))
				return $self->returnErrorResponse("insufficient params");

			$messageId = $requestData['messageId'];
			$message = $app['spikadb']->findMessageById($messageId);
			
			// send push notification
			$fromUserId = $message['from_user_id'];
			$toUserId = $message['to_user_id'];
			
			$fromUser = $app['spikadb']->getUserById($fromUserId);
			$toUser = $app['spikadb']->getUserById($toUserId);
			
			$pushnotificationMessage = $self->generatePushNotificationMessage($fromUser,$toUser);
			
			// send iOS push notification
			if(!empty($toUser['ios_push_token'])){

				$iosDevCertPath = __DIR__.'/../../../'.APN_DEV_CERT_PATH;
				
				if(file_exists($iosDevCertPath)){
					
					$body = array();
					$body['aps'] = array('alert' => $pushnotificationMessage, 'badge' => 0, 'sound' => 'default', 'value' => "");
					$body['data'] =array('from' => $fromUserId);
					$payload = json_encode($body);
					
					$result = $self->sendAPNDev($toUser['ios_push_token'],$payload,$app);
					
				}else{
					// dev push is disabled
				}
			
			}
			
			// send Android push notification
			if(!empty($toUser['android_push_token'])){

				$registrationIDs = array($toUser['android_push_token']);
			
				$fields = array(
					'registration_ids' => $registrationIDs,
					'data' => array( 
						"message" => $pushnotificationMessage, 
						"fromUser" => $fromUserId,
						"fromUserName"=>$fromUser['name'],
						"type" => "user", 
						"groupId" => ""
					),
				);
			
				$payload = json_encode($fields);
				$result = $self->sendGCM($payload,$app);		
				
			}
			
			$app['spikadb']->updateActivitySummaryByDirectMessage($message['to_user_id'],$message['from_user_id']);
			
			return "";
			
		});
		
		$controllers->post('/notifyNewGroupMessage', function (Request $request) use ($self,$app) {

			$host = $request->getHttpHost();
			if($host != "localhost"){
				return $self->returnErrorResponse("invalid access to internal API");
			}
			
			$requestBody = $request->getContent();
			$requestData = json_decode($requestBody,true);
			
			if(empty($requestData['messageId']))
				return $self->returnErrorResponse("insufficient params");

			$messageId = $requestData['messageId'];
			$message = $app['spikadb']->findMessageById($messageId);
			
			$app['spikadb']->updateActivitySummaryByGroupMessage($message['to_group_id'],$message['from_user_id']);
			
			return "";
			
		});
		
        return $controllers;
        
    }
    
    
	function sendAPNProd($deviceToken, $json) {
		$filePath = __DIR__.'/../../../'.APN_PROD_CERT_PATH;
		return $this->sendAPN($deviceToken,$json,$filePath,'ssl://gateway.push.apple.com:2195');
	}
	
	function sendAPNDev($deviceToken, $json, $app = null) {
		$filePath = __DIR__.'/../../../'.APN_DEV_CERT_PATH;
		return $this->sendAPN($deviceToken,$json,$filePath,'ssl://gateway.sandbox.push.apple.com:2195',$app);
	}


    function sendAPN($deviceToken, $json,$cert,$host,$app = null){
        
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
                $app['monolog']->addDebug("Failed to connect $err $errstr");
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
                   $app['monolog']->addDebug("Error: Unabled to wait for a stream availability");
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
	
	function generatePushNotificationMessage($fromUser,$toUser){
		
		$message = "You got message from {$fromUser['name']}";
		
		return $message;
		
	}

	function sendGCM($json, $app = null) {
		
		$apiKey = GCM_API_KEY;
	
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

		return $result;
	
	}
}

?>
