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

define("DIRECTMESSAGE_NOTIFICATION_MESSAGE", "You got message from %s");
define("GROUPMESSAGE_NOTIFICATION_MESSAGE", "%s posted message to group %s");

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

class GeneralAPIHandlerController implements ControllerProviderInterface
{

    var $app;
    
    public function connect(Application $app)
    {
        $this->app = $app;
        
        $controllers = $app['controllers_factory'];
        $self = $this;
        
        $controllers->post('/', function (Request $request) use ($app,$self)  {
	        
			$requestBody = $request->getContent();
		
			// convert created and modified fields automatically to be server time
			$requestBody = preg_replace("/,\"created\":[0-9]*?}/", ",\"created\":" . time() . "}", $requestBody);
			$requestBody = preg_replace("/,\"created\":[0-9]*?,/", ",\"created\":" . time() . ",", $requestBody);
			$requestBody = preg_replace("/,\"modified\":[0-9]*?}/", ",\"modified\":" . time() . "}", $requestBody);
			$requestBody = preg_replace("/,\"modified\":[0-9]*?,/", ",\"modified\":" . time() . ",", $requestBody);
			
			$requestBodyAry = json_decode($requestBody,true);
			
			if($requestBodyAry['type'] == 'message'){
                
                $self->handleNewMessage($requestBody);
                return $app['spikadb']->doPostRequest($requestBody);
                
			}else{
    			return $app['spikadb']->doPostRequest($requestBody);
			}

        })->before($app['beforeTokenChecker']);

		$controllers->get('/{args}', function (Request $request,$args) use ($app){
			
			$couchDBQuery = $args . "?" . $request->getQueryString();
			
			list($header,$body) = $app['spikadb']->doGetRequestGetHeader($couchDBQuery,true);
			
			$additionalHeader = array();
			
			
			
			$headers = explode("\n",$header);
		    foreach($headers as $row){
		    	
			    if(preg_match("/Content-Type/", $row)){
			    	
			    	$tmp = explode(":",$row);
			    	
			    	$key = trim($tmp[0]);
			    	$value = trim($tmp[1]);
			    	
			    	$additionalHeader[$key] = $value;
			    }
			    
		    }
		    
		    
			return new Response($body, 200, $additionalHeader);
		
		})
		->before($app['beforeTokenChecker'])
		->assert('args', '.*')
		->convert('args', function ($args) {
			return $args;
		});
        
        $controllers->put('/{id}',  function (Request $request,$id) use ($app) {

			$requestBody = $request->getContent();
			return $app['spikadb']->doPutRequest($id,$requestBody);

        })->before($app['beforeTokenChecker']);
        
        $controllers->delete('/{id}',  function (Request $request,$id) use ($app) {

			return $app['spikadb']->doDeleteRequest($id);

        })->before($app['beforeTokenChecker']);


        return $controllers;
    }
    
    // this function is kicked when someone post new message to a user or a group.
    public function handleNewMessage($requestBody)
    {
        $logger = $this->app['logger'];
        $requestBodyAry = json_decode($requestBody,true);
        
        $targetType = $requestBodyAry['message_target_type'];
        
         if ($targetType == 'user') {

            $fromUser = $requestBodyAry['from_user_id'];
            $toUser = $requestBodyAry['to_user_id'];
            $message = $requestBodyAry['body'];

            // add to activity summary
            $this->updateActivitySummary($toUser, $fromUser, "direct_messages");

        }
        
        
        
        return $this->app['spikadb']->doPostRequest($requestBody);
    }
    
    // change this after API is done
    function updateActivitySummary($toUserId, $fromUserId, $type)
    {
        
        // get latest activity summary
        $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
        $return = $this->app['spikadb']->doGetRequest($url);
        $returnDic = json_decode($return, true);
        
        $return = $this->app['spikadb']->doGetRequest($fromUserId);
        $fromUserData = json_decode($return, true);
        
        if (count($returnDic['rows']) == 0) {
    
            // if doesn't exist generate
            $params = array(
                'type' => 'activity_summary',
                'user_id' => $toUserId,
                'recent_activity' => array(
                    $type => array(
                        'name' => 'Chat activity',
                        "target_type" => "user",
                        'notifications' => array()
                    )
                )
            );
    
            $result = $this->app['spikadb']->doPostRequest(json_encode($params));
            
            $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
            $return = $this->app['spikadb']->doGetRequest($url);
            $returnDic = json_decode($return, true);
    
        }
    
        $userActivitySummary = $returnDic['rows'][0]['value'];
        $userActivitySummary['recent_activity'][$type]['name'] = 'Chat activity';
        $userActivitySummary['recent_activity'][$type]['target_type'] = 'user';
    
        $message = sprintf(DIRECTMESSAGE_NOTIFICATION_MESSAGE,$fromUserData['name']);
        
        if (isset($userActivitySummary)) {
    
            //find row
            $targetTypeALL = $userActivitySummary['recent_activity'][$type]['notifications'];
            $isExists = false;
            $inAryKey = 0;
            $baseJSONData = array();
    
            foreach ($targetTypeALL as $key => $perTypeRow) {
                if ($perTypeRow['target_id'] == $fromUserId) {
                    $isExists = true;
                    $baseJSONData = $perTypeRow;
                    $inAryKey = $key;
                }
            }
    
            if (!$isExists) {
                $baseJSONData = array(
                    "target_id" => $fromUserId,
                    "count" => 0,
                    "messages" => array()
                );
            }
    
            $baseJSONData['count']++;
            $baseJSONData['lastupdate'] = time();
    
    
            $avatarPath = "/" . $fromUserId . "/";
            
            if(isset($fromUserData['_attachments']) && is_array($fromUserData['_attachments'])){
                foreach ($fromUserData['_attachments'] as $key => $val) {
                    if (preg_match("/avatar/", $key)) {
                        $avatarPath .= $key;
                        break;
                    }
                }
            }else{
                $avatarPath = '';
            }

    
            $baseJSONData['messages'][0] = array(
                "from_user_id" => $fromUserId,
                "message" => $message,
                "user_image_url" => $avatarPath
            );
    
            if (!$isExists) {
                $userActivitySummary['recent_activity'][$type]['notifications'][] = $baseJSONData;
            } else {
                $userActivitySummary['recent_activity'][$type]['notifications'][$inAryKey] = $baseJSONData;
            }
    
            // update summary
            $json = json_encode($userActivitySummary, JSON_FORCE_OBJECT);
            
            $this->app['spikadb']->doPutRequest($userActivitySummary["_id"],$json);
            
        }
        
    }

}

?>
