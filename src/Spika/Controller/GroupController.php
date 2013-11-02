<?php
/**
 * Created by IntelliJ IDEA.
 * User: dinko
 * Date: 10/24/13
 * Time: 2:27 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class GroupController extends SpikaBaseController
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;

        $this->setupCreateGroupMethod($self,$app,$controllers);


        return $controllers;
    }

    private function setupCreateGroupMethod($self,$app,$controllers){

        $controllers->post('/createGroup',
            function (Request $request) use ($app,$self) {

                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                if(!$self->validateRequestParams($requestBody,array(
                    'name'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                
                $name = trim($requestBodyAry['name']);
                
                $description = "";
                if(isset($requestBodyAry['description']))
                	$description = trim($requestBodyAry['description']);
				
				$categoryId = "";
                if(isset($requestBodyAry['category_id']))
                	$categoryId = trim($requestBodyAry['category_id']);
                
                $password = "";
                if(isset($requestBodyAry['password']))
                	$password = trim($requestBodyAry['password']);
                
                $avatarURL = "";
                if(isset($requestBodyAry['avatar_file_id']))
	                $avatarURL = trim($requestBodyAry['avatar_file_id']);

				$thumbURL = "";
                if(isset($requestBodyAry['avatar_thumb_file_id']))
	                $thumbURL = trim($requestBodyAry['avatar_thumb_file_id']);
	                
				$ownerId = $currentUser['_id'];
				
				if(empty($ownerId))
					return $self->returnErrorResponse("user token is wrong");
					
                $result = $app['spikadb']->createGroup($name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL);
                $app['monolog']->addDebug("CreateGroup API called by user: \n {$result} \n");

                return json_encode($result);
            }
        )->before($app['beforeTokenChecker']);
    }

    private function setupSendGroupMessage($self,$app,$controllers){

        $controllers->post('/SendGroupMessage',
            function (Request $request) use ($app,$self) {

                $messageData = $request->getContent();

                if(!$self->validateRequestParams($messageData,array(
                    'to_group_id',
                    'from_user_id',
                    'body',
                    'type',
                    'message_target_type',
                    'message_type'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $messageDataArray=json_decode($messageData,true);

                //FIRST THING TO DO : GET GROUP DATA BY ID

                /*
                if(!isset($messageDataArray['from_user_name'])){
                    $fromUserData=$app['spikadb']->findUserById($messageDataArray['from_user_id']);
                    $messageDataArray['from_user_name']=$fromUserData['name'];
                }

                if(!isset($messageDataArray['to_group_id'])){
                    //load group data
                    //$groupData=$app['spikadb']->findUserById($messageDataArray['to_user_id']);
                    //$messageDataArray['to_user_name']=$toUserData['name'];
                } */

                echo print_r($messageData);
                die();

            }
        );
    }


}