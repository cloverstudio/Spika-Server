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


class SignoutController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        // logout controller
        $controllers->get('/unregistToken', function (Request $request) use ($app) {
            
            $userId = $request->get('user_id');
            return $app['spikadb']->unregistToken($userId);
        
        })->before($app['beforeApiGeneral']);

        return $controllers;
        
    }
    
}

?>
