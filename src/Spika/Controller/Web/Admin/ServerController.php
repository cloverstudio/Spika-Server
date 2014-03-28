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

class ServerController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;
        

        //
        // List/paging logics
        //

        $controllers->get('servers/list', function (Request $request) use ($app,$self) {
        	
            $self->setVariables();

            $count = $self->app['spikadb']->findServersCount();
            
            $page = $request->get('page');
            if(empty($page))
                $page = 1;
            
            $msg = $request->get('msg');
            if(!empty($msg))
                $self->setInfoAlert($self->language[$msg]);
            
            $servers = $self->app['spikadb']->findAllServers(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
            
            // convert timestamp to date
            for($i = 0 ; $i < count($servers) ; $i++){
                $servers[$i]['created'] = date("Y.m.d",$servers[$i]['created']);
                $servers[$i]['modified'] = date("Y.m.d",$servers[$i]['modified']);
            }
            
            return $self->render('admin/serversList.twig', array(
                'servers' => $servers,
                'pager' => array(
                    'baseURL' => ROOT_URL . "/admin/servers/list?page=",
                    'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
                    'page' => $page,
                ),
                
            ));
                        
        })->before($app['adminBeforeTokenChecker']);
        
        ///**************************************************
        //***************************************************

        $controllers->get('servers/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageNoPermission');
            }

            return $self->render('admin/serversForm.twig', array(
                'mode' => 'new',
                'formValues' => $self->getEmptyFormData(),
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // create new logics
        //

        $controllers->post('servers/add', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageNoPermission');
            }

            $validationError = false;
            
            $formValues = $request->request->all();
            
            //validation
            if(empty($formValues['name']) || empty($formValues['url'])){
                $self->setErrorAlert($self->language['messageValidationErrorRequired']);
                $validationError = true;
            }
            
            //checking url
            $pattern="/^https?:\/\/(.*)[^\/]$/";
            if(preg_match($pattern, $formValues['url'], $match) == 0){
            	$self->setErrorAlert($self->language['messageUrlIsNotValid']);
            	$validationError = true;
            }
               
            if(!$validationError){
         
                $result = $self->app['spikadb']->createServer(
                    $formValues['name'],
                    $formValues['url']
                );
                
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageServerAdded');
            }
            
            return $self->render('admin/serversForm.twig', array(
                'mode' => 'new',
                'formValues' => $formValues
            ));
                        
        })->before($app['adminBeforeTokenChecker']);        
        
        //
        // Detail logics
        //
        $controllers->get('servers/view/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();

            $server = $self->app['spikadb']->findServerById($id);
            
            return $self->render('admin/serversForm.twig', array(
                'mode' => 'view',
                'formValues' => $server
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        //
        // Edit logics
        //

        $controllers->get('servers/edit/{id}', function (Request $request,$id) use ($app,$self) {

            $self->setVariables();

            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageNoPermission');
            }

            $server = $self->app['spikadb']->findServerById($id);
                  
            return $self->render('admin/serversForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'formValues' => $server
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('servers/edit/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            $server = $self->app['spikadb']->findServerById($id);

            $formValues = $request->request->all();
            
            $validationError = false;
                          
            //validation
            if(empty($formValues['name']) || empty($formValues['url'])){
                $self->setErrorAlert($self->language['messageValidationErrorRequired']);
                $validationError = true;
            }
            
            //checking url
            $pattern="/^https?:\/\/(.*)[^\/]$/";
            if(preg_match($pattern, $formValues['url'], $match) == 0){
            	$self->setErrorAlert($self->language['messageUrlIsNotValid']);
            	$validationError = true;
            }
            
            if(!$validationError){

                $self->app['spikadb']->updateServer(
                    $id,
                    $formValues['name'],
                    $formValues['url']
                );
                
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageServerChanged');

            }
            
            return $self->render('admin/serversForm.twig', array(
                'id' => $id,
                'mode' => 'edit',
                'formValues' => $server
            ));
                        
        })->before($app['adminBeforeTokenChecker']);    
        
        //
        // Delete logics
        //
        $controllers->get('servers/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageNoPermission');
            }

            $server = $self->app['spikadb']->findServerById($id);

            return $self->render('admin/serversDelete.twig', array(
                'id' => $id,
                'mode' => 'delete',
                'formValues' => $server
            ));
            
        })->before($app['adminBeforeTokenChecker']);

        $controllers->post('servers/delete/{id}', function (Request $request,$id) use ($app,$self) {
            
            $self->setVariables();
            
            if(!$self->checkPermission()){
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageNoPermission');
            }

            $formValues = $request->request->all();
            
            if(isset($formValues['submit_delete'])){
                $self->app['spikadb']->deleteServer($id);
                return $app->redirect(ROOT_URL . '/admin/servers/list?msg=messageServerDeleted');
            }else{
                return $app->redirect(ROOT_URL . '/admin/servers/list');
            }
            
        })->before($app['adminBeforeTokenChecker']);
        
        return $controllers;
    }
    
    public function getEmptyFormData(){
        return  array(
                    '_id'=>'',
        			'name'=>'',
                    'url'=>'',
        			'modified'=>'',
                    'created'=>'',                  
                );
    }
    
}
