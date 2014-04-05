<?php
/**
 * Created by IntelliJ IDEA.
 * User: dinko
 * Date: 10/22/13
 * Time: 2:45 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class UserController extends SpikaBaseController
{


    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;

        $this->setupAuthMethod($self,$app,$controllers);
        $this->setupCreateUserMethod($self,$app,$controllers);
        $this->setupUpdateUserMethod($self,$app,$controllers);
        $this->setupFindUserMethod($self,$app,$controllers);
        $this->setupActivitySummaryMethod($self,$app,$controllers);
        $this->setupGetAvatarFileIdMethod($self,$app,$controllers);
        $this->setupContactsMethod($self,$app,$controllers);

        return $controllers;
    }


    private function setupAuthMethod($self,$app,$controllers){

        // Auth controller
        $controllers->post('/auth', function (Request $request) use ($app,$self) {
            
            $requestBody = $request->getContent();
            $requestBodyAry = json_decode($requestBody,true);

            $email = trim($requestBodyAry['email']);
            $password = trim($requestBodyAry['password']);
        
            if(empty($email))
                return $self->returnErrorResponse("Email is empty");
            
            if(empty($password))
                return $self->returnErrorResponse("Password is empty");

            
            $authResult = $app['spikadb']->doSpikaAuth($email,$password);
                    
            return $authResult;
        
        })->before($app['beforeApiGeneral']);;

    }


    private function setupCreateUserMethod($self,$app,$controllers){

        $controllers->post('/createUser', function (Request $request) use ($app,$self) {
    
            $requestBody = $request->getContent();
            
            if(!$self->validateRequestParams($requestBody,array(
                'name',
                'email',
                'password'
            ))){
                return $self->returnErrorResponse("insufficient params");
            }
            
            $requestBodyAry = json_decode($requestBody,true);
    
            $email = trim($requestBodyAry['email']);
            $username = trim($requestBodyAry['name']);
            $password = trim($requestBodyAry['password']);
            
            if(empty($email))
              return $self->returnErrorResponse("Email is empty");
              
            if(empty($username))
              return $self->returnErrorResponse("Name is empty");
              
            if(empty($password))
              return $self->returnErrorResponse("Password is empty");
              
            $checkUniqueName = $app['spikadb']->checkUserNameIsUnique($username);
            $checkUniqueEmail = $app['spikadb']->checkEmailIsUnique($email);
    
            if(is_array($checkUniqueName))
              return $self->returnErrorResponse("The name is already taken.");
              
            if(is_array($checkUniqueEmail))
              return $self->returnErrorResponse("You are already signed up.");
    
            $newUserId = $app['spikadb']->createUser(
              $username,
              $email,
              $password);
              
            $responseBodyAry = array(
                'ok' => true,
                'id' => $newUserId,
                'rev' => 'tmprev'
            );
            
            return json_encode($responseBodyAry);
            
        })->before($app['beforeApiGeneral']);


    }

    private function setupUpdateUserMethod($self,$app,$controllers){
    
        $controllers->post('/updateUser',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $userData = $request->getContent();

                if(!$self->validateRequestParams($userData,array(
                    'name',
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }

                $userDataArray=json_decode($userData,true);

                // prevent change password and token
                unset($userDataArray['password']);
                unset($userDataArray['token']);
                unset($userDataArray['token_timestamp']);
                
                $result = $app['spikadb']->updateUser($currentUser['_id'],$userDataArray);
                
                return json_encode($result);
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
    }

    private function setupFindUserMethod($self,$app,$controllers){
    
        $controllers->get('/findUser/{type}/{value}',
            function ($type,$value) use ($app,$self) {
                
                if(empty($value) || empty($type)){
                    return $self->returnErrorResponse("insufficient params");
                }

                $value = urldecode($value);
                
                switch ($type){
                    case "id":
                        $userIds = explode(",",$value);
                        if(count($userIds) == 1){
                            $result = $app['spikadb']->findUserById($value);
                        }else{
                            $result = $app['spikadb']->findUsersById($userIds);
                        }
                        break;
                    case "email":
                        $result = $app['spikadb']->findUserByEmail($value);
                        break;
                    case "name":
                        $result = $app['spikadb']->findUserByName($value);
                        break;
                    default:
                        return $self->returnErrorResponse("unknown search key");

                }
                
            
                if($result == null)
                    return "{}";
                    
                
                return json_encode($result);
                
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
    }

    private function setupActivitySummaryMethod($self,$app,$controllers){

        $controllers->get('/activitySummary',
            function () use ($app,$self) {
                
                $user = $app['currentUser'];
                $userId = $user['_id'];
                
                if(empty($userId)){
                    return $self->returnErrorResponse("insufficient params");
                }

                $result = $app['spikadb']->getActivitySummary($userId);

                return json_encode($result);
            }
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
    }

    private function setupGetAvatarFileIdMethod($self,$app,$controllers){

        $controllers->get('/GetAvatarFileId/{user_id}',
            function ($user_id) use ($app,$self) {

                if(empty($user_id)){
                    return $self->returnErrorResponse("insufficient params");
                }

                $result = $app['spikadb']->getAvatarFileId($user_id);


                return json_encode($result);
            }
        )->before($app['beforeApiGeneral']);
    }

    private function setupContactsMethod($self,$app,$controllers){

    
        $controllers->post('/addContact',
            function (Request $request) use ($app,$self) {
                

                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                if(!$self->validateRequestParams($requestBody,array(
                    'user_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                $userId = trim($requestBodyAry['user_id']);

                $result = $app['spikadb']->addContact($currentUser['_id'],$userId);
                
                if($result == null)
                    return $self->returnErrorResponse("failed to add contact");
                    
                $userData = $app['spikadb']->findUserById($currentUser['_id']);
                
                return json_encode($userData);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);
        
        $controllers->post('/removeContact',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                if(!$self->validateRequestParams($requestBody,array(
                    'user_id'
                ))){
                    return $self->returnErrorResponse("insufficient params");
                }
                
                $requestBodyAry = json_decode($requestBody,true);
                $userId = trim($requestBodyAry['user_id']);

                $result = $app['spikadb']->removeContact($currentUser['_id'],$userId);
                
                if($result == null)
                    return $self->returnErrorResponse("failed to remove contact");
                    
                $userData = $app['spikadb']->findUserById($currentUser['_id']);
                
                return json_encode($userData);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        $controllers->get('/getContacts',
            function (Request $request) use ($app,$self) {
                
                $currentUser = $app['currentUser'];
                $requestBody = $request->getContent();

                $users = $app['spikadb']->getContactsByUserId($currentUser['_id']);
                
                return json_encode($users);
                
            }
            
        )->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

    }

    
}


















