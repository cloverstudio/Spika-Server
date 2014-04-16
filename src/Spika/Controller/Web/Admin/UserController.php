<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Spika\Controller\Web\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\DBAL\DriverManager;
use Spika\Controller\Web\SpikaWebBaseController;
use Spika\Controller\FileController;
use Symfony\Component\HttpFoundation\Cookie;

class UserController extends SpikaWebBaseController
{

    var $userStatusList = array(
        'online' => 'online',
        'away' => 'away',
        'busy' => 'busy',
        'offline' => 'offline',
        
    );
    
    var $userGenderList = array(
        ' ' => '',
        'male' => 'male',
        'felame' => 'felame',
    );
    
    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;

        //
        // List/paging logics
        //

        $controllers->get('user/list', function (Request $request) use ($app,$self) {
            
            $self->setVariables();
            
            // search criteria
            $searchCriteriaUserName = $app['session']->get('usernameCriteria');
            
            $criteria = "";
            $criteriaValues = array();
            if(!empty($searchCriteriaUserName)){
                $criteria .= " and LOWER(name) like LOWER(?)";
                $criteriaValues[] = "%{$searchCriteriaUserName}%";
            }
            
            $count = $self->app['spikadb']->findUserCountWithCriteria($criteria,$criteriaValues);
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $users = $self->app['spikadb']->findAllUsersWithPagingWithCriteria(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT,$criteria,$criteriaValues);
            
            // convert timestamp to date
            for($i = 0 ; $i < count($users['rows']) ; $i++){
                $users['rows'][$i]['value']['created'] = date("Y.m.d",$users['rows'][$i]['value']['created']);
                $users['rows'][$i]['value']['modified'] = date("Y.m.d",$users['rows'][$i]['value']['modified']);
            }
            
            return $self->render('admin/userList.twig', array(
                'categoryList' => $self->getGroupCategoryList(),
                'users' => $users['rows'],
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/user/list?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                ),
                'searchCriteria' => array(
                    'username' => $searchCriteriaUserName
                )
                
            ));
                        
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('user/list', function (Request $request) use ($app,$self) {
            
            $usernameCriteria = trim($request->get('search-username'));
            $clearButton = $request->get('clear');
            
            if(!empty($clearButton)){
                $app['session']->set('usernameCriteria', '');
            } else {
                $app['session']->set('usernameCriteria', $usernameCriteria);
            }
            
            
            return $app->redirect(ROOT_URL . '/admin/user/list'); 
                        
        })->before($app['adminBeforeTokenChecker']);



        $controllers->get('user/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }
            
            return $self->render('admin/userAdd.twig', array(
                'mode' => 'new',
                'statusList' => $self->userStatusList,
                'genderList' => $self->userGenderList,
                'formValues' => $self->getEmptyFormData(),
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // create new logics
        //

        $controllers->post('user/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            $validationError = false;
            $fileName = "";
            $thumbFileName = "";
            
            $validationResult = $self->validate($request);

            if($validationResult){
                
                if($request->files->has("file")){
                
                    $file = $request->files->get("file");
                    
                    if($file && $file->isValid()){
                    
                        $fileName = $self->savePicture($file);
                        $thumbFileName = $self->saveThumb($file);
                    
                    }
                    
                }
                    
                $self->app['spikadb']->createUserDetail(
                    $formValues['name'],
                    md5($formValues['password']),
                    $formValues['email'],
                    $formValues['about'],
                    $formValues['online_status'],
                    $formValues['max_contact_count'],
                    $formValues['max_favorite_count'],
                    strtotime($formValues['birthday']),
                    $formValues['gender'],
                    $fileName,
                    $thumbFileName
                );
                
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageUserAdded');
            }
            
            return $self->render('admin/userAdd.twig', array(
                'mode' => 'new',
                'statusList' => $self->userStatusList,
                'genderList' => $self->userGenderList,
                'formValues' => $formValues
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // Detail logics
        //
        $controllers->get('user/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            $user = $self->app['spikadb']->findUserById($id,false);
            
            $action = $request->get('action');
                        
            if($action == 'addToContact'){
                $self->app['spikadb']->addContact($self->loginedUser['_id'],$user['_id']);
                $self->setInfoAlert($self->language['labelAddToContact']);
                $self->updateLoginUserData();
            }
            
            if($action == 'removeFromContact'){
                $self->app['spikadb']->removeContact($self->loginedUser['_id'],$user['_id']);
                $self->setInfoAlert($self->language['messageRemoveContact']);
                $self->updateLoginUserData();
            }
            
            $isInMyContact = $self->checkUserIsInLoginUserContact($user['_id']);
            
            $contact = $self->app['spikadb']->getContactsByUserId($id);
            $contacted = $self->app['spikadb']->getContactedByUserId($id);
            $group = $self->app['spikadb']->getGroupsByUserId($id);
            
            return $self->render('admin/userProfile.twig', array(
                'mode' => 'view',
                'statusList' => $self->userStatusList,
                'genderList' => $self->userGenderList,
                'userId' => $id,
                'formValues' => $user,
                'contacts' => $contact,
                'contacted' => $contacted,
                'groups' => $group,
                'categoryList' => $self->getGroupCategoryList(),
                'isInMyContact' => $isInMyContact
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        //
        // Edit logics
        //

        $controllers->get('user/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $tab = 'profile';
            
            $self->setVariables();
            
            if(!$self->checkPermission() && $self->loginedUser['_id'] != $id){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }            
            
            $action = $request->get('action');
            
            if($action == 'removeContact'){
                $removeUserId = $request->get('value');
                if(!empty($removeUserId)){
                    $self->app['spikadb']->removeContact($id,$removeUserId);
                    $self->setInfoAlert($self->language['messageRemoveContact']);
                }
                $self->updateLoginUserData();
                
                $tab = 'contacts';
            }
              
            if($action == 'removeGroup'){
                $groupId = $request->get('value');
                if(!empty($groupId)){
                    $self->app['spikadb']->unSubscribeGroup($groupId,$id);
                    $self->setInfoAlert($self->language['messagUnsubscribed']);
                }
                $self->updateLoginUserData();
                
                $tab = 'groups';
            }


            $user = $self->app['spikadb']->findUserById($id,false);
            $user['birthday'] = date('Y-m-d',$user['birthday']);
            
            $contact = $self->app['spikadb']->getContactsByUserId($id);
            $group = $self->app['spikadb']->getGroupsByUserId($id);


            return $self->render('admin/userEdit.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'statusList' => $self->userStatusList,
                'genderList' => $self->userGenderList,
                'contacts' => $contact,
                'groups' => $group,
                'formValues' => $user,
                'userId' => $id,
                'contacts' => $contact,
                'groups' => $group,
                'tab' => $tab,
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('user/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission() && $self->loginedUser['_id'] != $id){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $validationError = false;
            $fileName = "";
            $thumbFileName = "";
            $user = $self->app['spikadb']->findUserById($id,false);
            $formValues = $request->request->all();

            $fileName = $user['avatar_file_id'];
            $thumbFileName = $user['avatar_thumb_file_id'];
            
            $validationResult = $self->validate($request,true,$id);
            
            if($validationResult){

                if($request->files->has("file")){
                
                    $file = $request->files->get("file");
                    
                    if($file && $file->isValid()){
                    
                        $fileName = $self->savePicture($file);
                        $thumbFileName = $self->saveThumb($file);
                    
                    }
                    
                }

                if(isset($formValues['chkbox_delete_picture'])){
                    $fileName = '';
                    $thumbFileName = '';
                }
                
                $password = $user['password'];
                
                if(isset($formValues['chkbox_change_password'])){
                
                    if(!empty($formValues['password']))
                        $password = md5($formValues['password']);
                
                }
                
                $self->app['spikadb']->updateUser(
                    $id,
                    array(
                        'name' => $formValues['name'],
                        'email' => $formValues['email'],
                        'password' => $password,
                        'about' => $formValues['about'],
                        'online_status' => $formValues['online_status'],
                        'birthday' => strtotime($formValues['birthday']),
                        'gender' => $formValues['gender'],
                        'avatar_file_id' => $fileName,
                        'avatar_thumb_file_id' => $thumbFileName,
                        'max_contact_count' => $formValues['max_contact_count'],
                        'max_favorite_count' => $formValues['max_favorite_count']
                    ),
                    false // allow to change password and email
                );
                
                $user = $self->app['spikadb']->findUserById($id,false);
                
                $self->setInfoAlert($self->language['messageUserChanged']);

            }
    
            $contact = $self->app['spikadb']->getContactsByUserId($id);
            $group = $self->app['spikadb']->getGroupsByUserId($id);

            $user['birthday'] = date('Y-m-d',$user['birthday']);

            return $self->render('admin/userEdit.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'statusList' => $self->userStatusList,
                'genderList' => $self->userGenderList,
                'userId' => $id,
                'contacts' => $contact,
                'groups' => $group,
                'formValues' => $user,
                'tab' => 'profile',
            ));
                        
        })->before($app['adminBeforeTokenChecker']);    
        
        //
        // Delete logics
        //
        $controllers->get('user/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $user = $self->app['spikadb']->findUserById($id,false);
            
            return $self->render('admin/userDelete.twig', array(
                'id' => $id,
                'mode' => 'delete',
                'formValues' => $user
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('user/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            
            if(isset($formValues['submit_delete'])){
                $self->app['spikadb']->deleteUser($id);
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageUserDeleted');
            }else{
                return $app->redirect(ROOT_URL . '/admin/user/list');
            }
            
        })->before($app['adminBeforeTokenChecker']);
    
        $controllers->get('user/conversation/{userId}', function (Request $request,$userId) use ($app,$self) {
            
            $self->setVariables();
            
            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $count = $self->app['spikadb']->getConversationHistoryCount($userId);
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $conversationHistory = $self->app['spikadb']->getConversationHistory($userId,($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
            
            // convert timestamp to date
            for($i = 0 ; $i < count($conversationHistory) ; $i++){
                $conversationHistory[$i]['created'] = date("Y.m.d H:i:s",$conversationHistory[$i]['created']);
            }

            $user = $self->app['spikadb']->findUserById($userId);
            
            return $self->render('admin/userConversationHistory.twig', array(
                'conversations' => $conversationHistory,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/user/conversateion/{$userId}?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                ),
                'user' => $self->app['spikadb']->findUserById($userId,false)
                
            ));
            
        })->before($app['adminBeforeTokenChecker']);
        
        
        
        return $controllers;
    }
    
    public function validate($request,$editmode = false,$userId = ""){
        
        $formValues = $request->request->all();
        
        $validationResult = true;
        
        // required field check
        
        if($editmode){

            if(empty($formValues['name']) || empty($formValues['email']) || empty($formValues['max_contact_count']) || empty($formValues['max_favorite_count'])){
                $this->setErrorAlert($this->language['messageValidationErrorRequired']);
                $validationResult = false;
            }

            if(isset($formValues['chkbox_change_password']) && empty($formValues['password'])){
                $this->setErrorAlert($this->language['messageValidationErrorRequired']);
                $validationResult = false;
            }
            
        }else{
        
            if(empty($formValues['name']) || empty($formValues['email']) || empty($formValues['password']) || empty($formValues['max_contact_count']) || empty($formValues['max_favorite_count'])){
                $this->setErrorAlert($this->language['messageValidationErrorRequired']);
                $validationResult = false;
            }
            
        }

        // numeric
        if(!empty($formValues['max_contact_count']) && !is_numeric($formValues['max_contact_count'])){
            $this->setErrorAlert($this->language['formMaxContacts'] . " " . $this->language['messageValidationErrorNumeric']);
            $validationResult = false;
        }

        if(!empty($formValues['max_favorite_count']) && !is_numeric($formValues['max_favorite_count'])){
            $this->setErrorAlert($this->language['formMaxFavorites'] . " " . $this->language['messageValidationErrorNumeric']);
            $validationResult = false;
        }

        if($editmode){
        
            // check name is unique
            $check = $this->app['spikadb']->findUserByName($formValues['name']);
            if(isset($check['_id']) && $check['_id'] != $userId){
                $this->setErrorAlert($this->language['messageValidationErrorUserNameNotUnique']);
                $validationResult = false;
            }
    
            // check email is unique
            $check = $this->app['spikadb']->findUserByEmail($formValues['email']);
            if(isset($check['_id']) && $check['_id'] != $userId){
                $this->setErrorAlert($this->language['messageValidationErrorUserEmailNotUnique']);
                $validationResult = false;
            } 
            
        }else{
        
            // check name is unique
            $check = $this->app['spikadb']->findUserByName($formValues['name']);
            if(isset($check['_id'])){
                $this->setErrorAlert($this->language['messageValidationErrorUserNameNotUnique']);
                $validationResult = false;
            }
    
            // check email is unique
            $check = $this->app['spikadb']->findUserByEmail($formValues['email']);
            if(isset($check['_id'])){
                $this->setErrorAlert($this->language['messageValidationErrorUserEmailNotUnique']);
                $validationResult = false;
            } 
            
        }

        if($request->files->has("file")){
        
            $file = $request->files->get("file");
            
            if($file && $file->isValid()){
            
                $mimeType = $file->getClientMimeType();
                
                if(!preg_match("/jpeg/", $mimeType)){
                    $this->setErrorAlert($this->language['messageValidationErrorFormat']);
                    $validationResult = false;
                    
                }else{
                                        
                }
            
            }
            
        }
        
        return $validationResult;
        

    }
    
    
    public function getGroupCategoryList(){
    
        $result = $this->app['spikadb']->findAllGroupCategory();
        $list = array();
        
        foreach($result['rows'] as $row){
            $list[$row['value']['_id']] = $row['value'];
        }
        
        return $list;
    }
    
    public function getEmptyFormData(){
        return  array(
                    'name'=>'',
                    'email'=>'',
                    'onlineStatus'=>'',
                    'password'=>'',
                    'about'=>'',    
                    'online_status' => '',      
                    'max_favorite_count' => 10,
                    'max_contact_count' => 20,
                    'birthday' => '',
                    'gender' => ''      
                );
    }
    
}
