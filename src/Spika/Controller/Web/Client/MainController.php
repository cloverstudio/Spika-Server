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

class MainController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
        parent::connect($app);
        
        $controllers = $app['controllers_factory'];
        $self = $this;
    
        $controllers->get('/main', function (Request $request) use ($app,$self) {
            
            if(!$self->checkLogin()){
                return $app->redirect(ROOT_URL . '/client/login');
            }
            
            $self->setVariables();
            
            return $self->render('client/main.twig', array(
                'ROOT_URL' => ROOT_URL,              
            ));
        }); 
      
    
        $controllers->get('/user/{userId}', function (Request $request,$userId) use ($app,$self) {
            
            if(!$self->checkLogin()){
                return $app->redirect(ROOT_URL . '/client/login');
            }
            
            $self->setVariables();
            
            return $self->render('client/main.twig', array(
                'ROOT_URL' => ROOT_URL,      
                'targetUserId' => $userId        
            ));
        }); 
      
    
        $controllers->get('/group/{groupId}', function (Request $request,$groupId) use ($app,$self) {
            
            if(!$self->checkLogin()){
                return $app->redirect(ROOT_URL . '/client/login');
            }
            
            $self->setVariables();
            
            return $self->render('client/main.twig', array(
                'ROOT_URL' => ROOT_URL,              
                'targetGroupId' => $groupId        
            ));
        }); 
      
        return $controllers;
        
    }
    
}
