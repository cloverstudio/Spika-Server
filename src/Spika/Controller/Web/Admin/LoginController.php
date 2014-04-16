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
use Symfony\Component\HttpFoundation\Cookie;

class LoginController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;
    
        $controllers->get('/', function (Request $request) use ($app,$self) {
            return $app->redirect(ROOT_URL . '/admin/login');   
        }); 
        
        $controllers->get('/login', function (Request $request) use ($app,$self) {
            
            $cookies = $request->cookies;
            
            $username = "";
            $password = "";

            if ($cookies->has('username')) {
                $username = $cookies->get('username');
            }
    
            if ($cookies->has('password')) {
                $password = $cookies->get('password');
            }
    
            return $self->render('admin/login.twig', array(
                'ROOT_URL' => ROOT_URL,
                'formValues' => array(
                    'username'  => $username,
                    'password'  => $password,                   
                    'rememberChecked'  => '',                   
                )
            ));
                        
        });
        
        $controllers->post('/login', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            $username = $request->get('username');
            $password = $request->get('password');
            $remember = $request->get('remember');
            $rememberChecked = "";
            
            if(!empty($remember)){
                $rememberChecked = "checked=\"checked\"";
            }
            
            $authData = $self->app['spikadb']->doSpikaAuth($username,md5($password));
            $authData = json_decode($authData,true);

            if(isset($authData['token'])){
                
                $html = $self->render('admin/login.twig', array(
                    'ROOT_URL' => ROOT_URL,
                    'formValues' => array(
                        'username'  => $username,
                        'password'  => $password,                   
                        'rememberChecked'  => $rememberChecked,                 
                    )
                ));

                $response = new RedirectResponse(ROOT_URL . "/admin/dashboard");
                
                if(!empty($remember)){
                    $response->headers->setCookie(new Cookie("username", $username));
                    $response->headers->setCookie(new Cookie("password", $password));
                }
                
                
                
                $app['session']->set('user', $authData);
                
                return $response;
                
            }else{
                
                $self->setErrorAlert($self->language['messageLoginFailed']);
                
                return $self->render('admin/login.twig', array(
                    'ROOT_URL' => ROOT_URL,
                    'formValues' => array(
                        'username'  => $username,
                        'password'  => $password,                   
                        'rememberChecked'  => $rememberChecked,                 
                    )
                ));
                
            }
            
                        
        });
        
        $controllers->get('/dashboard', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            $countUsers = $self->app['spikadb']->findUserCount();
            $countMessages = $self->app['spikadb']->getMessageCount();
            $countLastLoginedUsers = $self->app['spikadb']->getLastLoginedUsersCount();

            return $self->render('admin/dashboard.twig', array(
                'countUsers' => $countUsers,
                'countMessages' => $countMessages,
                'countLastLoginedUsers' => $countLastLoginedUsers
            ));
            
                        
        })->before($app['adminBeforeTokenChecker']);
        
        $controllers->get('/logout', function (Request $request) use ($app,$self) {
            
            $app['session']->remove('user');
            $response = new RedirectResponse("login");
            
            return $response;        
        });
        
        return $controllers;
    }
    
}
