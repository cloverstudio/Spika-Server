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
            if(!isset($requestParams[$param]) || empty($requestParams[$param]))
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
        
        $currentURL =  $request->getBasePath() . $request->getPathInfo();
        $tmp = explode("/",$currentURL);
        $fileName = $tmp[count($tmp) - 1];
        $currentUrlDir = str_replace("/{$fileName}", "", $currentURL);
        
        $port = HTTP_PORT;
        $protocol = "http";
        if($request->isSecure()){
            $protocol = "https";
        }
        
        $requestURL = "{$protocol}://localhost:{$port}{$currentUrlDir}/{$apiName}";
        $request = $client->post($requestURL);
        $json = json_encode($params);
        $request->setBody($json,'application/json');
        
        $request->send();
        
    }   
            
}

?>
