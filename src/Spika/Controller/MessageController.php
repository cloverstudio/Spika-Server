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
        $controllers->post('/SendMessage',
            function (Request $request)use($app,$self) {

                $messageData = $request->getContent();


                if(!$self->validateRequestParams($messageData,array(
                    'to_user_id',
                    'from_user_id',
                    'body',
                    'type',
                    'message_target_type',
                    'message_type'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $messageDataArray=json_decode($messageData,true);


                if(!isset($messageDataArray['from_user_name'])){
                    $fromUserData=$app['spikadb']->findUserById($messageDataArray['from_user_id']);
                    $messageDataArray['from_user_name']=$fromUserData['name'];
                }

                if(!isset($messageDataArray['to_user_id'])){
                    $toUserData=$app['spikadb']->findUserById($messageDataArray['to_user_id']);
                    $messageDataArray['to_user_name']=$toUserData['name'];
                }

                $messageDataArray['modified']=time();
                $messageDataArray['created']=time();


                if( $request->headers->get('user_id') != $messageDataArray['from_user_id']){
                    return $self->returnErrorResponse("forbidden action");
                }

                $result = $app['spikadb']->addNewMessage($messageDataArray);
                $app['monolog']->addDebug("SendMessage API called from user: \n {$messageDataArray['from_user_id']} \n");

                return json_encode($result);
            }
        )->before($app['beforeTokenChecker']);
    }
}