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
        $this->setupUpdateGroupMethod($self,$app,$controllers);
        $this->setupDeleteGroupMethod($self,$app,$controllers);
        $this->setupSubscribeMethod($self,$app,$controllers);
        $this->setupGroupCategoryMethod($self,$app,$controllers);
        $this->setupWatchMethod($self,$app,$controllers);

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

                
                //check name is unique
                $checkUniqueName = $app['spikadb']->checkGroupNameIsUnique($name);
                
                if(isset($checkUniqueName['_id']))
                    return $self->returnErrorResponse("The name is already taken.");
                                
                $description = "";
                if(isset($requestBodyAry['description']))
                    $description = trim($requestBodyAry['description']);
                                
                $categoryId = "";
                if(isset($requestBodyAry['category_id']))
                    $categoryId = trim($requestBodyAry['category_id']);
                
                $password = "";
                if(isset($requestBodyAry['group_password']))
                    $password = trim($requestBodyAry['group_password']);
                
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
                                
                if($result == null)
                    return $self->returnErrorResponse("create group failed");

                if(isset($result['id'])){
                    $newGroupId = $result['id'];
                    $app['spikadb']->subscribeGroup($newGroupId,$ownerId);
                }else{
                    return $self->returnErrorResponse("subscribe group");
                }
                                
                return json_encode($result);
            }
        )->before($app['beforeTokenChecker'])
         ->before($app['beforeApiGeneral']);
    }

    private function setupUpdateGroupMethod($self,$app,$controllers){
        $controllers->post('/updateGroup', function (Request $request) use ($app,$self) {
                
            $currentUser = $app['currentUser'];
            $requestBody = $request->getContent();

            if(!$self->validateRequestParams($requestBody,array(
                '_id'
            ))){
                return $self->returnErrorResponse("insufficient params");
            }
            
            $requestBodyAry = json_decode($requestBody,true);
            
            $groupId = trim($requestBodyAry['_id']);

            //check permission
            $groupData = $app['spikadb']->findGroupById($groupId);
            
            $groupOwner = $groupData['user_id'];
            if($groupOwner != $currentUser['_id']){
                    return $self->returnErrorResponse("invalid user");
            }

            $name = "";
            if(isset($requestBodyAry['name']))
                    $name = trim($requestBodyAry['name']);
            
            $description = "";
            if(isset($requestBodyAry['description']))
                    $description = trim($requestBodyAry['description']);
                            
                            $categoryId = "";
            if(isset($requestBodyAry['category_id']))
                    $categoryId = trim($requestBodyAry['category_id']);
            
            $password = "";
            if(isset($requestBodyAry['group_password']))
                    $password = trim($requestBodyAry['group_password']);
            
            $avatarURL = "";
            if(isset($requestBodyAry['avatar_file_id']))
                    $avatarURL = trim($requestBodyAry['avatar_file_id']);

            $thumbURL = "";
            if(isset($requestBodyAry['avatar_thumb_file_id']))
            $thumbURL = trim($requestBodyAry['avatar_thumb_file_id']);
                
            $ownerId = $currentUser['_id'];
                            
            if(empty($ownerId))
                return $self->returnErrorResponse("user token is wrong");
                                        
            $result = $app['spikadb']->updateGroup($groupId,$name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL);
                
            return json_encode($result);
        })->before($app['beforeApiGeneral'])
          ->before($app['beforeTokenChecker']);
    }

    private function setupDeleteGroupMethod($self,$app,$controllers){
        $controllers->post('/deleteGroup',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                if(!$self->validateRequestParams($requestBody,array(
                    '_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                
                $groupId = trim($requestBodyAry['_id']);

                //check permission
                $groupData = $app['spikadb']->findGroupById($groupId);
                $groupOwner = $groupData['user_id'];
                if($groupOwner != $currentUser['_id']){
                    return $self->returnErrorResponse("invalid user");
                }

                $result = $app['spikadb']->deleteGroup($groupId);
                
                return json_encode($result);
            }
            
        )->before($app['beforeTokenChecker']);
    }
        
    private function setupGroupCategoryMethod($self,$app,$controllers){
    
        $controllers->get('/findAllGroupCategory',
            function () use ($app,$self) {
                                
                $result = $app['spikadb']->findAllGroupCategory();
                if($result == null)
                    return $self->returnErrorResponse("No group found");
                    
                return json_encode($result);
                
            }
        )->before($app['beforeApiGeneral'])
         ->before($app['beforeTokenChecker']);
        
    }

        
    private function setupFindGroupMethod($self,$app,$controllers){
        $controllers->get('/findGroup/{type}/{value}',
            function ($type,$value) use ($app,$self) {

                if(empty($value) || empty($type)){
                    return "{}";
                }

                switch ($type){
                    case "id":
                        $groupIds = explode(",",$value);
                        if(count($groupIds) == 1){
                            $result = $app['spikadb']->findGroupById($value);
                        }else{
                            $result = $app['spikadb']->findGroupsById($groupIds);
                        }
                        break;
                    case "name":
                        $result = $app['spikadb']->findGroupByName($value);
                        break;
                    case "categoryId":
                        $result = $app['spikadb']->findGroupByCategoryId($value);
                        break;
                    default:
                        return $self->returnErrorResponse("unknown search key");

                }

                if($result == null)
                    return "{}";
                    
                return json_encode($result);
                
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/searchGroups/{type}/',
            function ($type) use ($app,$self) {
                $result = $app['spikadb']->findAllGroupsWithPagingWithCriteria(0,100,"",array());
                return json_encode($result);
            }
        )->before($app['beforeApiGeneral']);
        
        $controllers->get('/searchGroups/{type}/{value}',
            function ($type,$value) use ($app,$self) {

                if(empty($type)){
                    return $self->returnErrorResponse("insufficient params");
                }
                                
                switch ($type){
                    case "name":
                        $result = $app['spikadb']->findGroupsByName($value);
                        break;
                    default:
                        return $self->returnErrorResponse("unknown search key");

                }
                    
                return json_encode($result);
                
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
        

    }
    

    private function setupSubscribeMethod($self,$app,$controllers){
    
        $controllers->post('/subscribeGroup',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                if(!$self->validateRequestParams($requestBody,array(
                    'group_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                $groupId = trim($requestBodyAry['group_id']);

                $result = $app['spikadb']->subscribeGroup($groupId,$currentUser['_id']);
                
                if($result == null)
                        return $self->returnErrorResponse("failed to subscribe group");
                        
                                $userData = $app['spikadb']->findUserById($currentUser['_id']);
                                
                return json_encode($userData);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
        
        $controllers->post('/unSubscribeGroup',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                if(!$self->validateRequestParams($requestBody,array(
                    'group_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                $groupId = trim($requestBodyAry['group_id']);

                $result = $app['spikadb']->unSubscribeGroup($groupId,$currentUser['_id']);
                
                if($result == null)
                    return $self->returnErrorResponse("failed to unsubscribe group");
                        
                $userData = $app['spikadb']->findUserById($currentUser['_id']);
                                
                return json_encode($userData);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/groupUsers/{groupId}/{count}/{offset}',
        
            function (Request $request,$groupId,$count = 30,$offset = 0) use ($app,$self) {
                
                $groupData = $app['spikadb']->findGroupById($groupId);

                if(!isset($groupData['_id'])){
                    return $self->returnErrorResponse("invalid group id");
                }
                
                $users = $app['spikadb']->getAllUsersByGroupId($groupId,$offset,$count);
                
                $usersCount = $app['spikadb']->getAllUsersCountByGroupId($groupId);
                
                return json_encode(array(
                    'count' => $usersCount,
                    'users' => $users
                ));
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

    }

    private function setupWatchMethod($self,$app,$controllers){
            
        $controllers->post('/watchGroup',
        
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();
    
                if(!$self->validateRequestParams($requestBody,array(
                    'group_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                $groupId = trim($requestBodyAry['group_id']);
    
                $result = $app['spikadb']->watchGroup($groupId,$currentUser['_id']);
                
                if($result == false)
                    return $self->returnErrorResponse("failed to watch group");
                        
                return "OK";
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
                
        $controllers->post('/unWatchGroup',
        
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                
                $result = $app['spikadb']->unWatchGroup($currentUser['_id']);
                
                if($result == false)
                    return $self->returnErrorResponse("failed to watch group");
                        
                return "OK";
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
            
        $controllers->get('/getFavoriteGroups',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                $groups = $app['spikadb']->getGroupsByUserId($currentUser['_id']);
                
                return json_encode($groups);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

    }
            
}
