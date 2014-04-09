<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller\Web\Client;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\DBAL\DriverManager;
use Spika\Controller\Web\SpikaWebBaseController;
use Spika\Utils;
use Symfony\Component\HttpFoundation\Cookie;
use Guzzle\Http\Client;

class LoginController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;
    
        $controllers->get('/', function (Request $request) use ($app,$self) {
            return $app->redirect(ROOT_URL . '/client/login');   
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
    
            return $self->render('client/login.twig', array(
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

            $registBtn = $request->get('regist');
            if(!empty($registBtn)){
                return new RedirectResponse("regist");
            }
            
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
                
                $html = $self->render('client/login.twig', array(
                    'ROOT_URL' => ROOT_URL,
                    'formValues' => array(
                        'username'  => $username,
                        'password'  => $password,                   
                        'rememberChecked'  => $rememberChecked,                 
                    )
                ));

                $response = new RedirectResponse(ROOT_URL . "/client/main");
                $app['session']->set('user', $authData);
                return $response;
                
            }else{
                
                $self->setErrorAlert($self->language['messageLoginFailed']);
                
                return $self->render('client/login.twig', array(
                    'ROOT_URL' => ROOT_URL,
                    'formValues' => array(
                        'username'  => $username,
                        'password'  => $password,                   
                        'rememberChecked'  => $rememberChecked,                 
                    )
                ));
                
            }
            
                        
        });
        
        $controllers->get('/logout', function (Request $request) use ($app,$self) {
            
            $app['session']->remove('user');
            $response = new RedirectResponse("login");
            
            return $response;        
        });
        
        $controllers->get('/regist', function (Request $request) use ($app,$self) {
            
            $cookies = $request->cookies;
            
            $email = "";
            $username = "";
            $password = "";

            return $self->render('client/regist.twig', array(
                'ROOT_URL' => ROOT_URL,
                'formValues' => array(
                    'username'  => $username,
                    'password'  => $password,                   
                    'email'  => $email                  
                )
            ));
                        
        });

        $controllers->post('/regist', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            $username = $request->get('username');
            $password = $request->get('password');
            $email = $request->get('email');

            $loginBtn = $request->get('login');
            if(!empty($loginBtn)){
                return new RedirectResponse("login");
            }
            
            // validation
            $errorMessage = "";
            if(empty($username)){
                $errorMessage = $self->language['messageValidationErrorEmptyUserName'];
            }
            else if(empty($email)){
                $errorMessage = $self->language['messageValidationErrorEmptyEmail'];
            }
            else if(empty($password)){
                $errorMessage = $self->language['messageValidationErrorEmptyPassword'];
            }
            
            if(empty($errorMessage)){
                if (!Utils::checkEmailIsValid($email)) {
                    $errorMessage = $self->language['messageValidationErrorInvalidEmail'];
                }
            }
            
            if(empty($errorMessage)){
                if (!Utils::checkPasswordIsValid($password)) {
                    $errorMessage = $self->language['messageValidationErrorInvalidPassword'];
                }
            }            

            if(empty($errorMessage)){
                $check = $app['spikadb']->findUserByName($username);
                if(!empty($check['_id']))
                    $errorMessage = $self->language['messageValidationErrorUserNameNotUnique'];
            }            

            if(empty($errorMessage)){
                $check = $app['spikadb']->findUserByEmail($email);
                if(!empty($check['_id']))
                    $errorMessage = $self->language['messageValidationErrorUserEmailNotUnique'];
            }            

            if(!empty($errorMessage)){
                $self->setErrorAlert($errorMessage);
            }else{
                
                $newUserId = $app['spikadb']->createUser(
                    $username,
                    $email,
                    md5($password)
                );
                
                $authData = $self->app['spikadb']->doSpikaAuth($email,md5($password));
                $authData = json_decode($authData,true);
                
                $response = new RedirectResponse("main");
                $app['session']->set('user', $authData);
                return $response;
                
            }
            
            
            return $self->render('client/regist.twig', array(
                'ROOT_URL' => ROOT_URL,
                'formValues' => array(
                    'username'  => $username,
                    'password'  => $password,                   
                    'email'  => $email                  
                )
            ));
            
        });
    
        $controllers->get('/resetPassword', function (Request $request) use ($app,$self) {
            
            $self->setVariables();
    
            return $self->render('client/resetpassword.twig', array(
                'ROOT_URL' => ROOT_URL,
                'formValues' => array(
                    'email'  => ''                 
                )
            ));
            
        });
        
        $controllers->post('/resetPassword', function (Request $request) use ($app,$self) {
            
            $self->setVariables();

            $email = $request->get('email');

            $loginBtn = $request->get('login');
            if(!empty($loginBtn)){
                return new RedirectResponse("login");
            }
            
            // validation
            $errorMessage = "";
            if(empty($email)){
                $errorMessage = $self->language['messageValidationErrorEmptyEmail'];
            }
            
            if(empty($errorMessage)){
                $check = $app['spikadb']->findUserByEmail($email);
                if(empty($check['_id']))
                    $errorMessage = $self->language['messageValidationEmailIsNotExist'];
            }            

            if(!empty($errorMessage)){
                $self->setErrorAlert($errorMessage);
            }else{
                
                // call api
                $client = new Client();
                $request = $client->get(LOCAL_ROOT_URL . "/api/resetPassword?email=" . $email);
                $response = $request->send();
                        
                $self->setInfoAlert($self->language['messageResetPasswordEmailSent']);
                
            }
            
            return $self->render('client/resetpassword.twig', array(
                'ROOT_URL' => ROOT_URL,
                'formValues' => array(
                    'email'  => $email                  
                )
            ));
                  
                        
        });
        
        return $controllers;
    }
    
}
