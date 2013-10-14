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


class GeneralAPIHandlerController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/', function (Request $request) use ($app)  {
	        
			$requestBody = $request->getContent();
		
			// convert created and modified fields automatically to be server time
			$requestBody = preg_replace("/,\"created\":[0-9]*?}/", ",\"created\":" . time() . "}", $requestBody);
			$requestBody = preg_replace("/,\"created\":[0-9]*?,/", ",\"created\":" . time() . ",", $requestBody);
			$requestBody = preg_replace("/,\"modified\":[0-9]*?}/", ",\"modified\":" . time() . "}", $requestBody);
			$requestBody = preg_replace("/,\"modified\":[0-9]*?,/", ",\"modified\":" . time() . ",", $requestBody);
	    
			return $app['spikadb']->doPostRequest($requestBody);

        })->before($app['beforeTokenChecker']);

		$controllers->get('/{args}', function (Request $request,$args) use ($app){
			
			$couchDBQuery = $args . "?" . $request->getQueryString();
			return $app['spikadb']->doGetRequest($couchDBQuery);
		
		})
		->before($app['beforeTokenChecker'])
		->assert('args', '.*')
		->convert('args', function ($args) {
			return $args;
		});
        
        $controllers->put('/{id}',  function (Request $request,$id) use ($app) {

			$requestBody = $request->getContent();
			return $app['spikadb']->doPutRequest($id,$requestBody);

        })->before($app['beforeTokenChecker']);
        
        $controllers->delete('/{id}',  function (Request $request,$id) use ($app) {

			return $app['spikadb']->doDeleteRequest($id);

        })->before($app['beforeTokenChecker']);


        return $controllers;
    }
}

?>