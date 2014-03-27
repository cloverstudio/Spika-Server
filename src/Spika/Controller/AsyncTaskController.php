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
            
            set_time_limit(60 * 10);
            
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

            $app['spikadb']->updateActivitySummaryByDirectMessage($message['to_user_id'],$message['from_user_id']);

            $pushnotificationMessage = $self->generatePushNotificationMessage($fromUser,$toUser);

            // send iOS push notification
            if(!empty($toUser['ios_push_token'])){
                $body = array();
                $body['aps'] = array('alert' => $pushnotificationMessage, 'badge' => 0, 'sound' => 'default', 'value' => "");
                $body['data'] =array('from' => $fromUserId);
                $payload = json_encode($body);

                $app['sendProdAPN'](array($toUser['ios_push_token']),$payload);
                $app['sendDevAPN'](array($toUser['ios_push_token']),$payload);
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
                $app['sendGCM']($payload,$app);
            }

            return "";

        });

        $controllers->post('/notifyNewGroupMessage', function (Request $request) use ($self,$app) {

            set_time_limit(60 * 10);

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
            
            // send pushnotification too all subscribed members
            
            $users = $app['spikadb']->getAllUsersByGroupId($message['to_group_id']);
            $iosTokens = array();
            $androidTokens = array();
            
            foreach($users as $user){
            
                if(!empty($user['ios_push_token']))
                    $iosTokens[] = $user['ios_push_token'];
                        
                if(!empty($user['android_push_token']))
                    $androidTokens[] = $user['android_push_token'];
                        
            }
            
            $fromUserData = $app['spikadb']->findUserById($message['from_user_id']);
            $toGroupData = $app['spikadb']->findGroupById($message['to_group_id']);
            $pushMessage = sprintf(GROUPMESSAGE_NOTIFICATION_MESSAGE . "  test ",$fromUserData['name'],$toGroupData['name']);
            
            $fields = array(
                            'registration_ids' => $androidTokens,
                            'data' => array( 
                                    "message" => $pushMessage, 
                                    "fromUser" => $message['from_user_id'],
                                    "fromUserName"=>$fromUserData['name'],
                                    "type" => "group", 
                                    "groupId" => $message['to_group_id']
                                    ),
                           );

            $payload = json_encode($fields);
            $app['sendGCM']($payload,$app);
            
            $body = array();
            $body['aps'] = array('alert' => $pushMessage, 'badge' => 0, 'sound' => 'default', 'value' => "");
            $body['data'] =array('type' => 'group','to_group' => $message['to_group_id']);
            $payload = json_encode($body);

            $app['sendProdAPN']($iosTokens,$payload);
            $app['sendDevAPN']($iosTokens,$payload);
            
            return "";

        });

        return $controllers;

    }

    function generatePushNotificationMessage($fromUser,$toUser){

        $message = sprintf(DIRECTMESSAGE_NOTIFICATION_MESSAGE, $fromUser['name']);

        return $message;

    }

}

?>
