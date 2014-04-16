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
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;

        $this->setupEmoticonsMethod($self,$app,$controllers);
        $this->setupGetCommentMethod($self,$app,$controllers);
        $this->setupMessageMethod($self,$app,$controllers);

        return $controllers;
    }

    private function setupEmoticonsMethod($self,$app,$controllers){

        $controllers->get('/Emoticons',
            function () use ($app,$self) {

                $result = $app['spikadb']->getEmoticons();

                if($result == null){
                    return $self->returnErrorResponse("load emoticons error");
                }

                if(!isset($result['rows'])){
                    return $self->returnErrorResponse("load emoticons error");
                }

                return json_encode($result);
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/Emoticon/{id}',
        
            function (Request $request,$id = "") use ($app,$self) {

                if(empty($id)){
                    return $self->returnErrorResponse("please specify emoticon id");
                }
                
                
                $emoticonData = $app['spikadb']->getEmoticonById($id);
                $fileID = $emoticonData['file_id'];
                
                if($emoticonData == null){
                    return $self->returnErrorResponse("load emoticon error");
                }
                
                $filePath = $filePath = __DIR__.'/../../../'.FileController::$fileDirName."/".basename($fileID);
                $response = new Response();
                $lastModified = new \DateTime();
                $file = new \SplFileInfo($filePath);
                
                $lastModified = new \DateTime();
                $lastModified->setTimestamp($file->getMTime());
                $response->setLastModified($lastModified);
                                    
                if ($response->isNotModified($request)) {
                    $response->prepare($request)->send();
                    return $response;
                }

                $response = $app->sendFile($filePath);
                $currentDate = new \DateTime(null, new \DateTimeZone('UTC'));
                $response->setDate($currentDate)->prepare($request)->send();
                
                return $response;


            }
        )->before($app['beforeApiGeneral']);


    }

    private function setupGetCommentMethod($self,$app,$controllers){

        $controllers->get('/commentsCount/{messageId}',
            function ($messageId)use($app,$self) {

                if(empty($messageId)){
                    return $self->returnErrorResponse("insufficient params");
                }

                $result = $app['spikadb']->getCommentCount($messageId);
                
                return json_encode($result);
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->post('/sendComment',
        
            function (Request $request)use($app,$self) {

                $currentUser = $app['currentUser'];
                $messageData = $request->getContent();

                if(!$self->validateRequestParams($messageData,array(
                    'message_id',
                    'comment'                    
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $messageDataArray=json_decode($messageData,true);
                
                $messageId = $messageDataArray['message_id'];
                $comment = $messageDataArray['comment'];
                $fromUserId = $currentUser['_id'];
                
                $result = $app['spikadb']->addNewComment($messageId,$fromUserId,$comment);

                if($result == null)
                    return $self->returnErrorResponse("failed to add comment");
                    
                return json_encode($result);
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);


        $controllers->get('/comments/{messageId}/{count}/{offset}',
            function ($messageId = "",$count = 30,$offset = 0) use ($app,$self) {
                
                if(empty($messageId))
                    return $self->returnErrorResponse("failed to get message");
                
                $result = $app['spikadb']->getComments($messageId,$count,$offset);

                if($result == null)
                     return $self->returnErrorResponse("failed to get message");
                     
                return json_encode($result);
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
        
    }

    private function setupMessageMethod($self,$app,$controllers){
    
        $controllers->post('/sendMessageToUser',
            function (Request $request)use($app,$self) {

                $currentUser = $app['currentUser'];
                $messageData = $request->getContent();

                if(!$self->validateRequestParams($messageData,array(
                    'to_user_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $messageDataArray=json_decode($messageData,true);
                
                
                $fromUserId = $currentUser['_id'];
                $toUserId = trim($messageDataArray['to_user_id']);
                
                if(isset($messageDataArray['body']))
                    $message = $messageDataArray['body'];
                else
                    $message = "";
                
                if(isset($messageDataArray['message_type'])){
                    $messageType = $messageDataArray['message_type'];
                } else {
                    $messageType = 'text';
                }
                
                $additionalParams = array();
                
                // emoticon message
                if(isset($messageDataArray['emoticon_image_url'])){
                    $additionalParams['emoticon_image_url'] = $messageDataArray['emoticon_image_url'];
                }else{
                    
                    // search emoticon id 
                    $emoticon = $app['spikadb']->getEmoticonByIdentifier($message);
                    
                    if(isset($emoticon['_id'])){
                        $additionalParams['emoticon_image_url'] = ROOT_URL . "/api/Emoticon/{$emoticon['_id']}";                    
                    }
                    
                    
                }
                
                // pitcure message
                if(isset($messageDataArray['picture_file_id'])){
                    $additionalParams['picture_file_id'] = $messageDataArray['picture_file_id'];
                }
                if(isset($messageDataArray['picture_thumb_file_id'])){
                    $additionalParams['picture_thumb_file_id'] = $messageDataArray['picture_thumb_file_id'];
                }

                // voice message
                if(isset($messageDataArray['voice_file_id'])){
                    $additionalParams['voice_file_id'] = $messageDataArray['voice_file_id'];
                }
                
                // video message
                if(isset($messageDataArray['video_file_id'])){
                    $additionalParams['video_file_id'] = $messageDataArray['video_file_id'];
                }
                
                // location message
                if(isset($messageDataArray['longitude'])){
                    $additionalParams['longitude'] = $messageDataArray['longitude'];
                }
                if(isset($messageDataArray['latitude'])){
                    $additionalParams['latitude'] = $messageDataArray['latitude'];
                }
                
                $result = $app['spikadb']->addNewUserMessage($messageType,$fromUserId,$toUserId,$message,$additionalParams);

                if($result == null)
                     return $self->returnErrorResponse("failed to send message");
                
                $newMessageId = $result['id'];
                
                // send async request
                $self->doAsyncRequest($app,$request,"notifyNewDirectMessage",array('messageId' => $newMessageId));
                
                return json_encode($result);
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
        
        $controllers->get('/userMessages/{toUserId}/{count}/{offset}',
        
            function ($toUserId = "",$count = 30,$offset = 0) use ($app,$self) {

                $currentUser = $app['currentUser'];
                $loginedUserId = $currentUser['_id'];
                
                $count = intval($count);
                $offset = intval($offset);
                
                if(empty($loginedUserId) || empty($toUserId))
                    return $self->returnErrorResponse("failed to get message");
                    
                $result = $app['spikadb']->getUserMessages($loginedUserId,$toUserId,$count,$offset);

                if($result == null)
                     return $self->returnErrorResponse("failed to get message");
                     
                $app['spikadb']->clearActivitySummary($loginedUserId, ACTIVITY_SUMMARY_DIRECT_MESSAGE, $toUserId);
                
                if(is_array($result) && count($result['rows']) > 0)
                    $result['rows'] = $self->fileterMessage($result['rows'],$app['spikadb']);

                // set read_at
                foreach($result['rows'] as $message){
                
                    $toUserId = $message['value']['to_user_id'];
                    $readAt = $message['value']['read_at'];
                    
                    if($readAt == 0 && $toUserId == $loginedUserId){                        
                        $app['spikadb']->updateReadAt($message['id']);
                    }
                    
                }
                
                return json_encode($result);
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/findMessageById/{id}',
            function ($id) use ($app,$self) {

                $currentUser = $app['currentUser'];
                $ownerUserId = $currentUser['_id'];
                
                if(empty($ownerUserId) || empty($id))
                    return $self->returnErrorResponse("failed to get message");
                    
                $result = $app['spikadb']->findMessageById($id);

                if($result == null)
                     return $self->returnErrorResponse("failed to get message");
                     
                //$result = $self->fileterMessage($result,$app['spikadb']);
                $result = $self->fileterOneMessage($result);
                
                return json_encode($result);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);


        $controllers->post('/sendMessageToGroup',
            function (Request $request)use($app,$self) {

                $currentUser = $app['currentUser'];
                $messageData = $request->getContent();

                if(!$self->validateRequestParams($messageData,array(
                    'to_group_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $messageDataArray=json_decode($messageData,true);

                $fromUserId = $currentUser['_id'];
                $toGroupId = trim($messageDataArray['to_group_id']);
                
                if(isset($messageDataArray['body']))
                    $message = $messageDataArray['body'];
                else
                    $message = "";
                
                if(isset($messageDataArray['message_type'])){
                    $messageType = $messageDataArray['message_type'];
                } else {
                    $messageType = 'text';
                }
                
                $additionalParams = array();
                
                // emoticon message
                if(isset($messageDataArray['emoticon_image_url'])){
                    $additionalParams['emoticon_image_url'] = $messageDataArray['emoticon_image_url'];
                }else{
                    
                    // search emoticon id 
                    $emoticon = $app['spikadb']->getEmoticonByIdentifier($message);
                    
                    if(isset($emoticon['_id'])){
                        $additionalParams['emoticon_image_url'] = ROOT_URL . "/api/Emoticon/{$emoticon['_id']}";                    
                    }
                    
                    
                }
                
                
                // pitcure message
                if(isset($messageDataArray['picture_file_id'])){
                    $additionalParams['picture_file_id'] = $messageDataArray['picture_file_id'];
                }
                if(isset($messageDataArray['picture_thumb_file_id'])){
                    $additionalParams['picture_thumb_file_id'] = $messageDataArray['picture_thumb_file_id'];
                }

                // voice message
                if(isset($messageDataArray['voice_file_id'])){
                    $additionalParams['voice_file_id'] = $messageDataArray['voice_file_id'];
                }
                
                // video message
                if(isset($messageDataArray['video_file_id'])){
                    $additionalParams['video_file_id'] = $messageDataArray['video_file_id'];
                }
                
                // location message
                if(isset($messageDataArray['longitude'])){
                    $additionalParams['longitude'] = $messageDataArray['longitude'];
                }
                if(isset($messageDataArray['latitude'])){
                    $additionalParams['latitude'] = $messageDataArray['latitude'];
                }

                
                $result = $app['spikadb']->addNewGroupMessage($messageType,$fromUserId,$toGroupId,$message,$additionalParams);

                if($result == null)
                     return $self->returnErrorResponse("failed to send message");
                     
                $newGroupMessageId = $result['id'];
                
                // send async request
                $self->doAsyncRequest($app,$request,"notifyNewGroupMessage",array('messageId' => $newGroupMessageId));
                
                return json_encode($result);
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/groupMessages/{toGroupId}/{count}/{offset}',
            function ($toGroupId = "",$count = 30,$offset = 0) use ($app,$self) {
                
                if(empty($toGroupId))
                    return $self->returnErrorResponse("failed to get message");
                    
                $result = $app['spikadb']->getGroupMessages($toGroupId,$count,$offset);

                if($result == null)
                     return $self->returnErrorResponse("failed to get message");
                     
                $currentUser = $app['currentUser'];
                $app['spikadb']->clearActivitySummary($currentUser['_id'], ACTIVITY_SUMMARY_GROUP_MESSAGE, $toGroupId);
                
                if(is_array($result) && count($result['rows']) > 0)
                    $result['rows'] = $self->fileterMessage($result['rows'],$app['spikadb']);

                return json_encode($result);
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->post('/setDelete',
        
            function (Request $request)use($app,$self) {

                $now = time();
                
                $currentUser = $app['currentUser'];
                $requestData = $request->getContent();

                if(!$self->validateRequestParams($requestData,array(
                    'delete_type',
                    'message_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestAry=json_decode($requestData,true);
                
                $deleteType = $requestAry['delete_type'];
                $messageId = $requestAry['message_id'];
                
                $message = $app['spikadb']->findMessageById($messageId);
                
                if($message['from_user_id'] != $currentUser['_id']){
                    return $self->returnErrorResponse("invalid user");
                }
                
                if($deleteType == DELETE_TYPE_NOTDELETE){
                    
                    $app['spikadb']->setMessageDelete($messageId,$deleteType,0,0);
                    
                } else if ($deleteType == DELETE_TYPE_NOW){
                    
                    $app['spikadb']->deleteMessage($messageId);
                    
                } else if ($deleteType == DELETE_TYPE_FIVEMIN){
                    
                    $app['spikadb']->setMessageDelete($messageId,$deleteType,$now+60*5,0);                    
                    
                } else if ($deleteType == DELETE_TYPE_ONEDAY){
                    
                    $app['spikadb']->setMessageDelete($messageId,$deleteType,$now+60*60*24,0);                    
                    
                } else if ($deleteType == DELETE_TYPE_ONEWEEK){
                    
                    $app['spikadb']->setMessageDelete($messageId,$deleteType,$now+60*60*24*7,0);                    
                    
                } else if ($deleteType == DELETE_TYPE_AFTERSHOWN){
                    
                    $app['spikadb']->setMessageDelete($messageId,$deleteType,0,1);                    
                    
                } else {
                
                   return $self->returnErrorResponse("invalid params"); 
                   
                }
                                
                return 'OK';  
                 
            }        

        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/reportViolation',
            function (Request $request) use ($app,$self) {
                $messageId = $request->get('message_id');
                $app['spikadb']->reportMessage($messageId); 
                return 'OK';
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

    }
    
    public function fileterMessage($messages,$database){
        
        if(empty($messages) || !is_array($messages))
            return array();
            
        $newResult = array();
        
        $now = time();
        
        foreach($messages as $message){
            
            if(!isset($message['value']['delete_at']) || !isset($message['value']['delete_flagged_at']) || !isset($message['value']['delete_after_shown'])){
                $newResult[] = $message;
                continue;                
            }
            
            $messageId = $message['value']['_id'];
            $deleteAt = $message['value']['delete_at'];
            $deleteFlaggedAt = $message['value']['delete_flagged_at'];
            $deleteAterShown = $message['value']['delete_after_shown'];
            $readAt = $message['value']['read_at'];
            
            // if delete time passed from flagged time delete from ary and delete from db
            if($deleteAt != 0 && $deleteAt < $now){
                $database->deleteMessage($messageId);
                continue;
            }
            
            // if delete after shown flag is true add to ary delete from db so next time will not show
            if($deleteAterShown == 1 && $message['value']['from_user_id'] != $this->app['currentUser']['_id'] ){
                $database->deleteMessage($messageId);
            }
            
            $message['value'] = $this->fileterOneMessage($message['value']);
            
            $newResult[] = $message;
            
        }
        
        return $newResult;
        
    }

    public function fileterOneMessage($message){
        
        // force decimal
        $message['read_at'] = intval($message['read_at']);
        $message['delete_at'] = intval($message['delete_at']);
        
        return $message;
        
    }

}
