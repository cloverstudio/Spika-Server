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
        $this->setupFindGroupMethod($self,$app,$controllers);


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

    private function setupFindGroupMethod($self,$app,$controllers){
        $controllers->get('/findGroup/{type}/{value}',
            function ($type,$value) use ($app,$self) {

                if(empty($value) || empty($type)){
                    return $self->returnErrorResponse("insufficient params");
                }
				
                switch ($type){
                    case "id":
                        $result = $app['spikadb']->findGroupById($value);
                        $app['monolog']->addDebug("FindUserById API called with user id: \n {$value} \n");
                        break;
                    default:
                        return $self->returnErrorResponse("unknown search key");

                }

                if($result == null)
                    return $self->returnErrorResponse("No group found");
                    
                return json_encode($result);
                
            }
        )->before($app['beforeTokenChecker']);
    }
    

}