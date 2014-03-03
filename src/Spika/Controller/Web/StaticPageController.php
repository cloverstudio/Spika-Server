<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller\Web;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\DBAL\DriverManager;
use Spika\Controller\FileController;

class StaticPageController extends SpikaWebBaseController
{


    public function connect(Application $app)
    {
        ExceptionHandler::register(false);
        $controllers = $app['controllers_factory'];
		$self = $this;
		
		// first screen
		$controllers->get('/eula/{language}', function (Request $request,$language) use ($app,$self) {
			
			return $app['twig']->render("static/eula_{$language}.twig", array(
			));
			
		});
		
		$controllers->get('/privacypolicy/{language}', function (Request $request,$language) use ($app,$self) {
			
			return $app['twig']->render("static/privacy_{$language}.twig", array(
			));
			
		});
        
		$controllers->get('/information/{token}', function (Request $request,$token) use ($app,$self) {
			
            $user = $app['spikadb']->findUserByToken($token);
    
            if (empty($user['token']) || $token !== $user['token']) {
    			return $app['twig']->render("static/tokenExpired.twig", array(
    			    'ROOT_URL' => ROOT_URL,
    			    
    			));
            }
        
            $app['session']->set('user', $user);
			
			return $app['twig']->render("static/information.twig", array(
			    'ROOT_URL' => ROOT_URL,
			    'user' => $user
			));
			
		});
        
        return $controllers;
    }
    
}
