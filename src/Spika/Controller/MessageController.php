<?php
/**
 * Created by IntelliJ IDEA.
 * User: dinko
 * Date: 10/24/13
 * Time: 10:47 AM
 * To change this template use File | Settings | File Templates.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

class MessageController extends SpikaBaseController
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;

        $this->setupEmoticonsMethod($self,$app,$controllers);
        $this->setupGetCommentCountMethod($self,$app,$controllers);
        $this->setupMessageMethod($self,$app,$controllers);

        return $controllers;
    }

    private function setupEmoticonsMethod($self,$app,$controllers){

        $controllers->get('/Emoticons',
            function () use ($app,$self) {

                $result = $app['spikadb']->getEmoticons();
                $app['monolog']->addDebug("Emoticons API called\n");

				if($result == null){
                    return $self->returnErrorResponse("load emoticons error");
                }

				if(!isset($result['rows'])){
                    return $self->returnErrorResponse("load emoticons error");
                }

                return json_encode($result);
            }
        )->before($app['beforeTokenChecker']);

        $controllers->get('/Emoticon/{id}',
            function ($id = "") use ($app,$self) {

				if(empty($id)){
                    return $self->returnErrorResponse("please specify emoticon id");
                }

                $result = $app['spikadb']->getEmoticonImage($id);
                $app['monolog']->addDebug("Emoticon API called\n");

				if($result == null){
                    return $self->returnErrorResponse("load emoticon error");
                }
                
                return new Response(
                	$result,
                	200,
                	array('Content-Type' => 'image/png')
                );

            }
        )->before($app['beforeTokenChecker']);


    }

    private function setupGetCommentCountMethod($self,$app,$controllers){

        $controllers->get('/CommentsCount/{messageId}',
            function ($messageId)use($app,$self) {

                if(empty($messageId)){
                    return $self->returnErrorResponse("insufficient params");
                }

                $result = $app['spikadb']->getCommentCount($messageId);
                $app['monolog']->addDebug("CommentsCount API called\n");

                return json_encode($result);
            }
        )->before($app['beforeTokenChecker']);
    }

    private function setupMessageMethod($self,$app,$controllers){
    
        $controllers->post('/sendMessageToUser',
            function (Request $request)use($app,$self) {

                $currentUser = $app['currentUser'];
                $messageData = $request->getContent();

                if(!$self->validateRequestParams($messageData,array(
                    'to_user_id',
                    'body'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $messageDataArray=json_decode($messageData,true);

				$fromUserId = $currentUser['_id'];
				$toUserId = trim($messageDataArray['to_user_id']);
				$message = $messageDataArray['body'];
				
				if(isset($messageDataArray['message_type'])){
					$messageType = $messageDataArray['message_type'];
				} else {
					$messageType = 'text';
				}
				
				$additionalParams = array();
				
				if(isset($messageDataArray['emoticon_image_url'])){
					$additionalParams['emoticon_image_url'] = $messageDataArray['emoticon_image_url'];
				}
				
                $result = $app['spikadb']->addNewMessage($messageType,$fromUserId,$toUserId,$message,$additionalParams);
                $app['monolog']->addDebug("SendMessage API called from user: \n {$fromUserId} \n");

				if($result == null)
					 return $self->returnErrorResponse("failed to send message");
					 
                return json_encode($result);
            }
            
        )->before($app['beforeTokenChecker']);
        
        $controllers->get('/userMessages/{toUserId}/{count}/{offset}',
            function ($toUserId = "",$count = 30,$offset = 0) use ($app,$self) {

   				$currentUser = $app['currentUser'];
				$ownerUserId = $currentUser['_id'];
				
				if(empty($ownerUserId) || empty($toUserId))
					return $self->returnErrorResponse("failed to get message");
					
                $result = $app['spikadb']->getUserMessages($ownerUserId,$toUserId,$count,$offset);
                $app['monolog']->addDebug("UserMessages API called");

				if($result == null)
					 return $self->returnErrorResponse("failed to get message");
					 
                return json_encode($result);
            }
        )->before($app['beforeTokenChecker']);

    }
}
