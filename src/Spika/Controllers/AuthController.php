<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika;

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
		$controllers->post('/hookup-auth.php', function (Request $request) use ($app) {
			
			$requestBody = $request->getContent();
			$authResult = $app['spikadb']->doSpikaAuth($requestBody);
			
			$app['monolog']->addDebug("Auth Response : \n {$authResult} \n");
		
		    return $authResult;
		
		});
        
        return $controllers;
    }
    
}

?>