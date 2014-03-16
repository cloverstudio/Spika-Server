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

class GroupCategoryController extends SpikaWebBaseController
{

    
    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;

        //
        // List/paging logics
        //

        $controllers->get('groupcategory/list', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $count = $self->app['spikadb']->findGroupCategoryCount();
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $categories = $self->app['spikadb']->findAllGroupCategoryWithPaging(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
            
            // convert timestamp to date
            for($i = 0 ; $i < count($categories['rows']) ; $i++){
                $categories['rows'][$i]['value']['created'] = date("Y.m.d",$categories['rows'][$i]['value']['created']);
                $categories['rows'][$i]['value']['modified'] = date("Y.m.d",$categories['rows'][$i]['value']['modified']);
            }

            return $self->render('admin/categoryList.twig', array(
                'categories' => $categories['rows'],
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/groupcategory/list?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                ),
                
            ));
                        
        })->before($app['adminBeforeTokenChecker']);

        $controllers->get('groupcategory/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            return $self->render('admin/categoryForm.twig', array(
                'mode' => 'new',
                'formValues' => $self->getEmptyFormData(),
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // create new logics
        //

        $controllers->post('groupcategory/add', function (Request $request) use ($app,$self) {
            
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
                    
                    }
                    
                }
                    
                $self->app['spikadb']->createGroupCategory(
                    $formValues['title'],
                    $fileName
                );
                
                return $app->redirect(ROOT_URL . '/admin/groupcategory/list?msg=messageGroupCategoryAdded');
            }
            
            return $self->render('admin/categoryForm.twig', array(
                'mode' => 'new',
                'formValues' => $formValues
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // Detail logics
        //
        $controllers->get('groupcategory/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $category = $self->app['spikadb']->findGroupCategoryById($id);

            return $self->render('admin/categoryForm.twig', array(
                'mode' => 'view',
                'formValues' => $category
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        //
        // Edit logics
        //

        $controllers->get('groupcategory/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $category = $self->app['spikadb']->findGroupCategoryById($id,false);
            
            return $self->render('admin/categoryForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'formValues' => $category
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('groupcategory/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $validationError = false;
            $fileName = "";
            $category = $self->app['spikadb']->findGroupCategoryById($id,false);
            $formValues = $request->request->all();

            $fileName = $category['avatar_file_id'];
            
            $validationResult = $self->validate($request,true,$id);
            
            if($validationResult){

                if($request->files->has("file")){
                
                    $file = $request->files->get("file");
                    
                    if($file && $file->isValid()){
                    
                        $fileName = $self->savePicture($file);
                    
                    }
                    
                }

                if(isset($formValues['chkbox_delete_picture'])){
                    $fileName = '';
                }
                
                $self->app['spikadb']->updateGroupCategory(
                    $id,
                    $formValues['title'],
                    $fileName
                );
                
                return $app->redirect(ROOT_URL . '/admin/groupcategory/list?msg=messageGroupCategoryChanged');

            }
    
            return $self->render('admin/categoryForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'formValues' => $category
            ));
                        
        })->before($app['adminBeforeTokenChecker']);    
        
        //
        // Delete logics
        //
        $controllers->get('groupcategory/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $category = $self->app['spikadb']->findGroupCategoryById($id,false);
            
            return $self->render('admin/categoryDelete.twig', array(
                'id' => $id,
                'mode' => 'delete',
                'formValues' => $category
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('groupcategory/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/user/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            
            if(isset($formValues['submit_delete'])){
                $self->app['spikadb']->deleteGroupCategory($id);
                return $app->redirect(ROOT_URL . '/admin/groupcategory/list?msg=messageGroupCategoryDeleted');
            }else{
                return $app->redirect(ROOT_URL . '/admin/groupcategory/list');
            }
            
        })->before($app['adminBeforeTokenChecker']);

    
        
        return $controllers;
    }
    
    public function validate($request,$editmode = false,$userId = ""){
        
        $formValues = $request->request->all();
        
        $validationResult = true;
        
        // required field check
        if(empty($formValues['title'])){
            $this->setErrorAlert($this->language['messageValidationErrorRequired']);
            $validationResult = false;
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
                    'title'=>'',
                );
    }
    
}
