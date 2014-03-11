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
        $controllers->get('/checkUnique', function (Request $request) use ($app) {

            $email = $request->get('email');
            $username = $request->get('username');
            $groupname = $request->get('groupname');

            if(!empty($email)){

                $result = $app['spikadb']->checkEmailIsUnique($email);
                if(count($result) == 0 || $result == false){
                    return "[]";
                }else{
                    return json_encode($result);
                }
            }

            if(!empty($username)){
                $result = $app['spikadb']->checkUserNameIsUnique($email);
                if(count($result) == 0 || $result == false){
                    return "[]";
                }else{
                    return json_encode($result);
                }
            }

            if(!empty($groupname)){
                $result = $app['spikadb']->checkGroupNameIsUnique($email);
                if(count($result) == 0 || $result == false){
                    return "[]";
                }else{
                    return json_encode($result);
                }
            }

            return '';

        })->before($app['beforeApiGeneral']);

        return $controllers;
    }

}

?>
