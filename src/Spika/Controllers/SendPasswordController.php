<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class SendPasswordController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

		// check unique controller
		$controllers->get('/sendpassword.php', function (Request $request) use ($app) {

			$email = $request->get('email');
			
			$startKey = "\"{$email}\"";
			$query = "?key={$startKey}";
			$resultTmp = $app['spikadb']->doGetRequest("/_design/app/_view/find_user_by_email{$query}",false);
			$resutlData = json_decode($resultTmp, true);

		    if (count($resutlData['rows'] != 0)) {
		
		        $user = $resutlData['rows'][0]['value'];
		
		        $email = $user['email'];
		
		        $body = sprintf("here is your password %s", $user['password']);
		
		        mail($email, "spika password reminder", $body, AdministratorEmail);
		
		
		    }
    
			return 'OK';
			
		});
        
        return $controllers;
    }
    
}

?>
