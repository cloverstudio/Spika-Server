<?php
/**
 * Created by IntelliJ IDEA.
 * User: dinko
 * Date: 10/22/13
 * Time: 2:45 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class ServerListController extends SpikaBaseController
{


    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;

        $controllers->get('/servers', function (Request $request) use ($app,$self) {
        
        	$serverList = $app['spikadb']->findAllServersWitoutId();
            return json_encode($serverList);
            
        })->before($app['beforeApiGeneral']);;

        return $controllers;
    }

    
}


















