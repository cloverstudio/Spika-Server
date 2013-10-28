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
use Symfony\Component\HttpFoundation\Response;

class SpikaBaseController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        return $controllers;        
    }
    
   
    public function validateRequestParams($requestBody,$requiredParams){
        $requestParams = json_decode($requestBody,true);

	    if(!is_array($requestParams))
	    	return false;
	    	
	    foreach($requiredParams as $param){
		    if(!isset($requestParams[$param]) || empty($requestParams[$param]))
		    	return false;
	    }
	    
	    return true;
    }

    public function returnErrorResponse($errorMessage){
	    $arr  = array('message' => $errorMessage, 'error' => 'error');
        $json = json_encode($arr);
        return new Response($json, 403);
    }
}

?>
