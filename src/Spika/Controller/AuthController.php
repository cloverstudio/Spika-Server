<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class AuthController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

		// Auth controller
		$controllers->post('/auth', function (Request $request) use ($app) {
			
			$requestBody = $request->getContent();
    		$requestBodyAry = json_decode($requestBody,true);
    
    		$email = trim($requestBodyAry['email']);
    		$password = trim($requestBodyAry['password']);
		
            if(empty($email))
                return $self->returnErrorResponse("Email is empty");
            
            if(empty($password))
                return $self->returnErrorResponse("Password is empty");

            
			$authResult = $app['spikadb']->doSpikaAuth($email,$password);
			
			$app['monolog']->addDebug("Auth Request : \n {$requestBody} \n");
			$app['monolog']->addDebug("Auth Response : \n {$authResult} \n");
		
		    return $authResult;
		
		});
        
        return $controllers;
    }
    
}

?>
