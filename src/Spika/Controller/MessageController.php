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
        $this->setupSendMessageMethod($self,$app,$controllers);

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

    private function setupSendMessageMethod($self,$app,$controllers){
    
        $controllers->post('/sendMessageToUser',
            function (Request $request)use($app,$self) {

                $currentUser = $app['currentUser'];
                $userData = $request->getContent();

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
				
                $result = $app['spikadb']->addNewTextMessage($fromUserId,$toUserId,$message);
                $app['monolog']->addDebug("SendMessage API called from user: \n {$fromUserId} \n");

				if($result == null)
					 return $self->returnErrorResponse("failed to send message");
					 
                return json_encode($result);
            }
        )->before($app['beforeTokenChecker']);
    }}