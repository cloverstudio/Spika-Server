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

class GroupController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;
        

        //
        // List/paging logics
        //

        $controllers->get('group/list', function (Request $request) use ($app,$self) {
        
            $self->setVariables();

            // search criteria
            $searchCriteriaGroupName = $app['session']->get('groupnameCriteria');
            
            $criteria = "";
            $searchGroupNameCriteriaValues = array();
            if(!empty($searchCriteriaGroupName)){
                $criteria .= " and LOWER(name) like LOWER(?)";
                $searchGroupNameCriteriaValues[] = "%{$searchCriteriaGroupName}%";
            }
            
            $count = $self->app['spikadb']->findGroupCountWithCriteria($criteria,$searchGroupNameCriteriaValues);
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $groups = $self->app['spikadb']->findAllGroupsWithPagingWithCriteria(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT,$criteria,$searchGroupNameCriteriaValues);
            
            // convert timestamp to date
            for($i = 0 ; $i < count($groups) ; $i++){
                $groups[$i]['created'] = date("Y.m.d",$groups[$i]['created']);
                $groups[$i]['modified'] = date("Y.m.d",$groups[$i]['modified']);
            }
            
            return $self->render('admin/groupList.twig', array(
                'categoryList' => $self->getGroupCategoryList(),
                'groups' => $groups,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/group/list?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                ),
                'searchCriteria' => array(
                    'groupName' => $searchCriteriaGroupName
                )
                
            ));
                        
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('group/list', function (Request $request) use ($app,$self) {
            
            $groupnameCriteria = trim($request->get('search-groupname'));
            $clearButton = $request->get('clear');
            
            if(!empty($clearButton)){
                $app['session']->set('groupnameCriteria', '');
            } else {
                $app['session']->set('groupnameCriteria', $groupnameCriteria);
            }
            
            return $app->redirect(ROOT_URL . '/admin/group/list'); 
                        
        })->before($app['adminBeforeTokenChecker']);


        $controllers->get('group/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            return $self->render('admin/groupAdd.twig', array(
                'mode' => 'new',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $self->getEmptyFormData(),
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // create new logics
        //

        $controllers->post('group/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            $validationError = false;
            $fileName = "";
            $thumbFileName = "";
            
            if($request->files->has("file")){
            
                $file = $request->files->get("file");
                
                if($file && $file->isValid()){
                
                    $mimeType = $file->getClientMimeType();
                    
                    if(!preg_match("/jpeg/", $mimeType)){
                        $self->setErrorAlert($self->language['messageValidationErrorFormat']);
                        $validationError = true;
                    }else{
                        $fileName = $self->savePicture($file);
                        $thumbFileName = $self->saveThumb($file);      
                    }
                
                }
                
            }
            
            $formValues = $request->request->all();
            
            //validation
            if(empty($formValues['name']) || empty($formValues['category_id']) || empty($formValues['description'])){
                $self->setErrorAlert($self->language['messageValidationErrorRequired']);
                $validationError = true;
            }
            
            // check name is unique
            $check = $self->app['spikadb']->findGroupByName($formValues['name']);
            if(isset($check['_id'])){
                $self->setErrorAlert($self->language['messageValidationErrorGroupNotUnique']);
                $validationError = true;
            }
            
            if(!$validationError){
                
                $password = '';
                if(!empty($formValues['group_password']))
                    $password = md5($formValues['group_password']);
                    
                $result = $self->app['spikadb']->createGroup(
                    $formValues['name'],
                    $self->loginedUser['_id'],
                    $formValues['category_id'],
                    $formValues['description'],
                    $password,
                    $fileName,
                    $thumbFileName
                );
                
                $self->app['spikadb']->subscribeGroup(
                    $result['id'],
                    $self->loginedUser['_id']
                );

                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageGroupAdded');

            }
            
            return $self->render('admin/groupAdd.twig', array(
                'mode' => 'new',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $formValues
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // Detail logics
        //
        $controllers->get('group/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            $group = $self->app['spikadb']->findGroupById($id);
            $tab = 'profile';

            $action = $request->get('action');
            
            if($action == 'subscribe'){
                $self->app['spikadb']->subscribeGroup($group['_id'],$self->loginedUser['_id']);
                $self->setInfoAlert($self->language['messageSubscribed']);
                $self->updateLoginUserData();
            }
            
            if($action == 'unsubscribe'){
                $self->app['spikadb']->unSubscribeGroup($group['_id'],$self->loginedUser['_id']);
                $self->setInfoAlert($self->language['messageUnsubscribed']);
                $self->updateLoginUserData();
            }
            
            $categoryList = $self->getGroupCategoryList();
            
            $categoryName = $categoryList[$group['category_id']]['title'];
            $group['categoryName'] = $categoryName;
            
            
            $pageSubscribedUsers = $request->get('page');
            if(empty($pageSubscribedUsers))
                $pageSubscribedUsers = 1;
            else
                $tab = 'users';
            
            $criteria = "";
            $searchUsernameCriteria = $app['session']->get('subscribedUsersCriteria');
            $searchUsernameCriteriaValues = array();
            if(!empty($searchUsernameCriteria)){
                $criteria .= " and LOWER(name) like LOWER(?)";
                $searchUsernameCriteriaValues[] = "%{$searchUsernameCriteria}%";
                $tab = 'users';
            }

            $userList = $self->app['spikadb']->getAllUsersByGroupIdWithCriteria($group['_id'],($pageSubscribedUsers-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT,$criteria,$searchUsernameCriteriaValues);
            $userCount = $self->app['spikadb']->getAllUsersCountByGroupIdWithCriteria($group['_id'],$criteria,$searchUsernameCriteriaValues);
            
            $isSubscribed = $self->checkUserIsSubscribedGroup($group['_id']);
            
            return $self->render('admin/groupProfile.twig', array(
                'mode' => 'view',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group,
                'groupId' => $id,
                'isSubscribed' => $isSubscribed,
                'subscribedUsers' => $userList,
                'tab' => $tab,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/group/view/{$group['_id']}?page=",
                    'pageCount' => ceil($userCount / ADMIN_LISTCOUNT) - 1,
                    'page' => $pageSubscribedUsers,
                ),
                'searchCriteria' => array(
                    'userName' => $searchUsernameCriteria
                )
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('group/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $usernameCriteria = trim($request->get('search-subscribedusers'));
            $clearButton = $request->get('clear');
            
            if(!empty($clearButton)){
                $app['session']->set('subscribedUsersCriteria', '');
            } else {
                $app['session']->set('subscribedUsersCriteria', $usernameCriteria);
            }
            
            return $app->redirect(ROOT_URL . "/admin/group/view/{$id}"); 
                        
        })->before($app['adminBeforeTokenChecker']);


        //
        // Edit logics
        //

        $controllers->get('group/edit/{id}', function (Request $request,$id) use ($app,$self) {

            $self->setVariables();
            $tab = 'profile';
            
            $group = $self->app['spikadb']->findGroupById($id);

            if($group['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageNoPermission');
            }

            $action = $request->get('action');
            if($action == 'unsubscribeUser'){
                $userId = $request->get('value');
                $self->app['spikadb']->unSubscribeGroup($group['_id'],$userId);
                $self->setInfoAlert($self->language['messageKicked']);
                $self->updateLoginUserData();
                $tab = 'users';
            }
            
            $pageSubscribedUsers = $request->get('page');
            if(empty($pageSubscribedUsers))
                $pageSubscribedUsers = 1;
            else
                $tab = 'users';
            
            $criteria = "";
            $searchUsernameCriteria = $app['session']->get('subscribedUsersCriteria');
            $searchUsernameCriteriaValues = array();
            if(!empty($searchUsernameCriteria)){
                $criteria .= " and LOWER(name) like LOWER(?)";
                $searchUsernameCriteriaValues[] = "%{$searchUsernameCriteria}%";
                $tab = 'users';
            }

            $userList = $self->app['spikadb']->getAllUsersByGroupIdWithCriteria($group['_id'],($pageSubscribedUsers-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT,$criteria,$searchUsernameCriteriaValues);
            $userCount = $self->app['spikadb']->getAllUsersCountByGroupIdWithCriteria($group['_id'],$criteria,$searchUsernameCriteriaValues);

            $categoryList = $self->getGroupCategoryList();            
            if(isset($categoryList[$group['category_id']]['title']))
                $categoryName = $categoryList[$group['category_id']]['title'];
            else
                $categoryName = '';
                
            $group['categoryName'] = $categoryName;
            
            return $self->render('admin/groupEdit.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group,
                'tab' => $tab,
                'subscribedUsers' => $userList,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/group/edit/{$group['_id']}?page=",
                    'pageCount' => ceil($userCount / ADMIN_LISTCOUNT) - 1,
                    'page' => $pageSubscribedUsers,
                ),
                'searchCriteria' => array(
                    'userName' => $searchUsernameCriteria
                )

            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('group/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            // search
            $usernameCriteria = trim($request->get('search-subscribedusers'));
            $clearButton = $request->get('clear');
            $searchButton = $request->get('search');
            if(!empty($clearButton)){
                $app['session']->set('subscribedUsersCriteria', '');
                return $app->redirect(ROOT_URL . "/admin/group/edit/{$id}"); 
            } 
            if(!empty($searchButton)){
                $app['session']->set('subscribedUsersCriteria', $usernameCriteria);
                return $app->redirect(ROOT_URL . "/admin/group/edit/{$id}"); 
            }
            
            // update
            $self->setVariables();
            $tab = 'profile';
            
            $group = $self->app['spikadb']->findGroupById($id);
            if($group['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageNoPermission');
            }

            $validationError = false;
            $fileName = "";
            $thumbFileName = "";
            $formValues = $request->request->all();

            $fileName = $group['avatar_file_id'];
            $thumbFileName = $group['avatar_thumb_file_id'];

            if($request->files->has("file")){
            
                $file = $request->files->get("file");
                
                if($file && $file->isValid()){
                
                    $mimeType = $file->getClientMimeType();
                    
                    if(!preg_match("/jpeg/", $mimeType)){
                        $self->setErrorAlert($self->language['messageValidationErrorFormat']);
                        $validationResult = true;
                        
                    }else{
                                            
                        $fileName = $self->savePicture($file);
                        $thumbFileName = $self->saveThumb($file);
                                
                    }
                
                }
                
            } else {
                
                
            }
    
            if(isset($formValues['chkbox_delete_picture'])){
                $fileName = '';
                $thumbFileName = '';
            }
                        
            
            //validation
            if(empty($formValues['name']) || empty($formValues['category_id']) || empty($formValues['description'])){
                $self->setErrorAlert($self->language['messageValidationErrorRequired']);
                $validationError = true;
            }
            
            // check name is unique
            $check = $self->app['spikadb']->findGroupByName($formValues['name']);
            if(isset($check['_id']) && $check['_id'] != $group['_id']){
                $self->setErrorAlert($self->language['messageValidationErrorGroupNotUnique']);
                $validationError = true;
            }
            
            if(!$validationError){
                
                $password = '';
                
                if(isset($formValues['chkbox_change_password'])){
                    if(!empty($formValues['group_password']))
                        $password = md5($formValues['group_password']);
                }else{
                    $password = $group['group_password'];
                }

                $self->app['spikadb']->updateGroup(
                    $id,
                    $formValues['name'],
                    SUPPORT_USER_ID,
                    $formValues['category_id'],
                    $formValues['description'],
                    $password,
                    $fileName,
                    $thumbFileName
                );
                
                $group = $self->app['spikadb']->findGroupById($id);
                
            }
            
            $criteria = "";
            $searchUsernameCriteria = $app['session']->get('subscribedUsersCriteria');
            $searchUsernameCriteriaValues = array();
            if(!empty($searchUsernameCriteria)){
                $criteria .= " and LOWER(name) like LOWER(?)";
                $searchUsernameCriteriaValues[] = "%{$searchUsernameCriteria}%";
                $tab = 'users';
            }

            $userList = $self->app['spikadb']->getAllUsersByGroupIdWithCriteria($group['_id'],0,ADMIN_LISTCOUNT,$criteria,$searchUsernameCriteriaValues);
            $userCount = $self->app['spikadb']->getAllUsersCountByGroupIdWithCriteria($group['_id'],$criteria,$searchUsernameCriteriaValues);

            return $self->render('admin/groupEdit.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group,
                'tab' => $tab,
                'subscribedUsers' => $userList,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/group/edit/{$group['_id']}?page=",
                    'pageCount' => ceil($userCount / ADMIN_LISTCOUNT) - 1,
                    'page' => 1,
                ),
                'searchCriteria' => array(
                    'userName' => $searchUsernameCriteria
                )

            ));
                        
        })->before($app['adminBeforeTokenChecker']);    
        
        //
        // Delete logics
        //
        $controllers->get('group/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            $group = $self->app['spikadb']->findGroupById($id);
            
            if($group['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageNoPermission');
            }

            $categoryList = $self->getGroupCategoryList();
            
            $categoryName = $categoryList[$group['category_id']]['title'];
            $group['categoryName'] = $categoryName;

            return $self->render('admin/groupDelete.twig', array(
                'id' => $id,
                'mode' => 'delete',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('group/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            $group = $self->app['spikadb']->findGroupById($id);
            
            if($group['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            
            if(isset($formValues['submit_delete'])){
                $self->app['spikadb']->deleteGroup($id);
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageGroupDeleted');
            }else{
                return $app->redirect(ROOT_URL . '/admin/group/list');
            }
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->get('group/users/{groupId}', function (Request $request,$groupId) use ($app,$self) {
            
            $page = $request->get('page');
            
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
                
            $users = $self->app['spikadb']->getAllUsersByGroupId($groupId,($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
            $count = $self->app['spikadb']->getAllUsersCountByGroupId($groupId);
            
            return $self->render('admin/groupUserList.twig', array(
                'groupId' => $groupId,
                'users' => $users,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/group/users/{$groupId}?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                )                
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->get('group/unsubscribeUser/{groupId}/{userId}', function (Request $request,$groupId,$userId) use ($app,$self) {
            
            $group = $self->app['spikadb']->findGroupById($id);
            
            if($group['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageNoPermission');
            }
            
            $self->app['spikadb']->unSubscribeGroup($groupId,$userId);
            
            return $app->redirect(ROOT_URL . "/admin/group/users/{$groupId}?msg=messageRemoveUser");
            
        })->before($app['adminBeforeTokenChecker']);

        return $controllers;

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
                    'category_id'=>'',
                    'name'=>'',
                    'group_password'=>'',
                    'description'=>'',                  
                );
    }
    
}
