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

class NewsController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;
        

        //
        // List/paging logics
        //

        $controllers->get('news/list', function (Request $request) use ($app,$self) {
        	
            $self->setVariables();

            $count = $self->app['spikadb']->findStoriesCount();
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $news = $self->app['spikadb']->findAllNews(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
            
            // convert timestamp to date
            for($i = 0 ; $i < count($news) ; $i++){
                $news[$i]['created'] = date("Y.m.d",$news[$i]['created']);
                $news[$i]['modified'] = date("Y.m.d",$news[$i]['modified']);
            }
            
            return $self->render('admin/newsList.twig', array(
                'news' => $news,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/news/list?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                ),
                
            ));
                        
        })->before($app['adminBeforeTokenChecker']);
        
        ///**************************************************
        //***************************************************

        $controllers->get('news/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            return $self->render('admin/newsForm.twig', array(
                'mode' => 'new',
                'formValues' => $self->getEmptyFormData(),
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // create new logics
        //

        $controllers->post('news/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $validationError = false;
            
            $formValues = $request->request->all();
            
            //validation
            if(empty($formValues['title']) || empty($formValues['content'])){
                $self->setErrorAlert($self->language['messageValidationErrorRequired']);
                $validationError = true;
            }
               
            if(!$validationError){
         
                $result = $self->app['spikadb']->createStory(
                    $formValues['title'],
                    $formValues['content'],
                	$self->loginedUser['_id'],
                	$formValues['story_url']
                );
                
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNewsAdded');
            }
            
            return $self->render('admin/newsForm.twig', array(
                'mode' => 'new',
                'formValues' => $formValues
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // Detail logics
        //
        $controllers->get('news/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            $story = $self->app['spikadb']->findStoryById($id);
            
            return $self->render('admin/newsForm.twig', array(
                'mode' => 'view',
                'formValues' => $story
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        //
        // Edit logics
        //

        $controllers->get('news/edit/{id}', function (Request $request,$id) use ($app,$self) {

            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $story = $self->app['spikadb']->findStoryById($id);
            if($story['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
            	return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }
                  
            return $self->render('admin/newsForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'formValues' => $story
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('news/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            $story = $self->app['spikadb']->findStoryById($id);
            if($story['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
            	return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            
            $validationError = false;
                          
            //validation
            if(empty($formValues['title']) || empty($formValues['content'])){
                $self->setErrorAlert($self->language['messageValidationErrorRequired']);
                $validationError = true;
            }
            
            if(!$validationError){

                $self->app['spikadb']->updateStory(
                    $id,
                    $formValues['title'],
                    $formValues['content'],
                    $formValues['story_url']
                );
                
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNewsChanged');

            }
            
            return $self->render('admin/newsForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'formValues' => $story
            ));
                        
        })->before($app['adminBeforeTokenChecker']);    
        
        //
        // Delete logics
        //
        $controllers->get('news/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $story = $self->app['spikadb']->findStoryById($id);
            if($story['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
            	return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $story = $self->app['spikadb']->findStoryById($id);

            return $self->render('admin/newsDelete.twig', array(
                'id' => $id,
                'mode' => 'delete',
                'formValues' => $story
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('news/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $story = $self->app['spikadb']->findStoryById($id);
            if($story['user_id'] != $self->loginedUser['_id'] && $self->loginedUser['_id'] != SUPPORT_USER_ID){
            	return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            
            if(isset($formValues['submit_delete'])){
                $self->app['spikadb']->deleteStory($id);
                return $app->redirect(ROOT_URL . '/admin/news/list?msg=messageStoryDeleted');
            }else{
                return $app->redirect(ROOT_URL . '/admin/news/list');
            }
            
        })->before($app['adminBeforeTokenChecker']);
        
        return $controllers;
    }
    
    public function getEmptyFormData(){
        return  array(
                    '_id'=>'',
        			'user_id'=>'',
                    'title'=>'',
                    'content'=>'',
        			'story_url'=>'',
        			'modified'=>'',
                    'created'=>'',                  
                );
    }
    
}
