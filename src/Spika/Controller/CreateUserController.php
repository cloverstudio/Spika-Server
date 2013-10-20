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

/* CrateUser API
Params :
name
email
password
type=user
online_status=online
max_contact_count=20
max_favorite_count=10

Response sample:
"{
	""ok"": true,
	""id"": ""9b396ef6f2ea68ff9eee2263881cd3ee"",
	""rev"": ""1-d4c2ac3c0cb683a14245a7eab3a0b4d6""
}"

*/
class CreateUserController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

		// Auth controller
		$controllers->post('/createUser', function (Request $request) use ($app) {
			
			$requestBody = $request->getContent();
			
			$newUserId = $app['spikadb']->createUser($requestBody);
			$app['monolog']->addDebug("Create User API called : \n {$requestBody} \n");
				
			$responseBodyAry = array(
				'ok' => true,
				'id' => $newUserId,
				'rev' => 'tmprev'
			);
			
			return json_encode($responseBodyAry);
			
		});
        
        return $controllers;
    }
    
    
}

?>
