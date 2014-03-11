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
use Guzzle\Http\Client;
use Guzzle\Plugin\Async\AsyncPlugin;

class SpikaBaseController implements ControllerProviderInterface
{
    
    public $app = null;
    
    public function connect(Application $app)
    {
        $this->app = $app;
        $controllers = $app['controllers_factory'];
        
        return $controllers;        
    }
    
   
    public function validateRequestParams($requestBody,$requiredParams){
        $requestParams = json_decode($requestBody,true);

        if(!is_array($requestParams))
            return false;
            
        foreach($requiredParams as $param){
            if(!isset($requestParams[$param]))
                return false;
        }
        
        return true;
    }

    public function returnErrorResponse($errorMessage,$httpCode = 500){
        $arr  = array('message' => $errorMessage, 'error' => 'error');
        $json = json_encode($arr);
        return new Response($json, $httpCode);
    }
    
    public function doAsyncRequest($app,$request,$apiName,$params = null){
    
        $client = new Client();
        $client->addSubscriber(new AsyncPlugin());
        
        $requestURL = LOCAL_ROOT_URL . "/api/{$apiName}";
        
        $app['monolog']->addDebug($requestURL);


        $request = $client->post($requestURL,array(),array('timeout'=>0,'connect_timeout'=>0));
                
        $json = json_encode($params);
        $request->setBody($json,'application/json');
        
        $request->send();
        
    }   

}

?>
