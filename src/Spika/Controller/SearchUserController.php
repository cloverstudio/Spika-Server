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


class SearchUserController implements ControllerProviderInterface
{
    var $app;
    var $name,$ageFrom,$ageTo,$gender;
    
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $this->app = $app;
        $self = $this;
        
        $controllers->get('/searchUsers', function (Request $request) use ($app,$self) {
            $searchResult = $self->app['spikadb']->searchUser($request->get('n'),$request->get('af'),$request->get('at'),$request->get('g'));
            return json_encode($searchResult);
        })->before($app['beforeApiGeneral']);
        
        return $controllers;
    }
    
}

?>
