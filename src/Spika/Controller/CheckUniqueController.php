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


class CheckUniqueController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

		// check unique controller
		$controllers->get('/checkUnique.php', function (Request $request) use ($app) {
			
			$email = $request->get('email');
			$username = $request->get('username');
			$groupname = $request->get('groupname');
			
			if(!empty($email)){
				return $app['spikadb']->checkEmailIsUnique($email);
			}
			
			if(!empty($username)){
				return $app['spikadb']->checkUserNameIsUnique($username);
			}
			
			if(!empty($groupname)){
				return $app['spikadb']->checkGroupNameIsUnique($groupname);
			}
			
			return '';
		
		});
        
        return $controllers;
    }
    
}

?>
