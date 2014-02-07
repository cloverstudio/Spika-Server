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
            
            $count = $self->app['spikadb']->findGroupCount();
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $groups = $self->app['spikadb']->findAllGroups(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
            
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
                
            ));
                        
        })->before($app['adminBeforeTokenChecker']);

        $controllers->get('group/add', function (Request $request) use ($app,$self) {
            
            return $self->render('admin/groupForm.twig', array(
                'mode' => 'new',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $self->getEmptyFormData(),
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // create new logics
        //

        $controllers->post('group/add', function (Request $request) use ($app,$self) {
            
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
                    
                $self->app['spikadb']->createGroup(
                    $formValues['name'],
                    SUPPORT_USER_ID,
                    $formValues['category_id'],
                    $formValues['description'],
                    $password,
                    $fileName,
                    $thumbFileName
                );
                
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageGroupAdded');
            }
            
            return $self->render('admin/groupForm.twig', array(
                'mode' => 'new',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $formValues
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // Detail logics
        //
        $controllers->get('group/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $group = $self->app['spikadb']->findGroupById($id);
            $categoryList = $self->getGroupCategoryList();
            
            $categoryName = $categoryList[$group['category_id']]['title'];
            $group['categoryName'] = $categoryName;
            
            return $self->render('admin/groupForm.twig', array(
                'mode' => 'view',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        //
        // Edit logics
        //

        $controllers->get('group/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $group = $self->app['spikadb']->findGroupById($id);
            $categoryList = $self->getGroupCategoryList();
            
            $categoryName = $categoryList[$group['category_id']]['title'];
            $group['categoryName'] = $categoryName;
            
            return $self->render('admin/groupForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('group/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $validationError = false;
            $fileName = "";
            $thumbFileName = "";
            $group = $self->app['spikadb']->findGroupById($id);
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
                
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageGroupChanged');

            }
            
            return $self->render('admin/groupForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'categoryList' => $self->getGroupCategoryList(),
                'formValues' => $group
            ));
                        
        })->before($app['adminBeforeTokenChecker']);    
        
        //
        // Delete logics
        //
        $controllers->get('group/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $group = $self->app['spikadb']->findGroupById($id);
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
            
            $formValues = $request->request->all();
            
            if(isset($formValues['submit_delete'])){
                $self->app['spikadb']->deleteGroup($id);
                return $app->redirect(ROOT_URL . '/admin/group/list?msg=messageGroupDeleted');
            }else{
                return $app->redirect(ROOT_URL . '/admin/group/list');
            }
            
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
