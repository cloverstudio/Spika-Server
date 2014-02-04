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
use Symfony\Component\HttpFoundation\ParameterBag;

class GeneralAPIHandlerController implements ControllerProviderInterface
{

    var $app;
    
    public function connect(Application $app)
    {
        $this->app = $app;
        
        $controllers = $app['controllers_factory'];
        $self = $this;
        
        $controllers->post('/', function (Request $request) use ($app,$self)  {
                
            $requestBody = $request->getContent();
        
            // convert created and modified fields automatically to be server time
            $requestBody = preg_replace("/,\"created\":[0-9]*?}/", ",\"created\":" . time() . "}", $requestBody);
            $requestBody = preg_replace("/,\"created\":[0-9]*?,/", ",\"created\":" . time() . ",", $requestBody);
            $requestBody = preg_replace("/,\"modified\":[0-9]*?}/", ",\"modified\":" . time() . "}", $requestBody);
            $requestBody = preg_replace("/,\"modified\":[0-9]*?,/", ",\"modified\":" . time() . ",", $requestBody);
            
            $requestBodyAry = json_decode($requestBody,true);
            
            if($requestBodyAry['type'] == 'message'){
                
                $self->handleNewMessage($requestBody);
                return $app['spikadb']->doPostRequest($requestBody);
                
            }else{
                return $app['spikadb']->doPostRequest($requestBody);
            }

        })->before($app['beforeTokenChecker']);

        $controllers->get('/{args}', function (Request $request,$args) use ($app){
                
            $couchDBQuery = $args . "?" . $request->getQueryString();
            
            list($header,$body) = $app['spikadb']->doGetRequestGetHeader($couchDBQuery,true);
            
            $additionalHeader = array();
            
            
            
            $headers = explode("\n",$header);
            foreach($headers as $row){
                
                if(preg_match("/Content-Type/", $row)){
                    
                    $tmp = explode(":",$row);
                    
                    $key = trim($tmp[0]);
                    $value = trim($tmp[1]);
                    
                    $additionalHeader[$key] = $value;
                }
                    
            }
            
            
            return new Response($body, 200, $additionalHeader);
        
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
    
    // this function is kicked when someone post new message to a user or a group.
    public function handleNewMessage($requestBody)
    {
        $logger = $this->app['logger'];
        $requestBodyAry = json_decode($requestBody,true);
        
        $targetType = $requestBodyAry['message_target_type'];
        
        if ($targetType == 'user') {

            $fromUser = $requestBodyAry['from_user_id'];
            $toUser = $requestBodyAry['to_user_id'];
            $message = $requestBodyAry['body'];

            // add to activity summary
            $this->updateActivitySummary($toUser, $fromUser, "direct_messages");

        }
        
        
        
        return $this->app['spikadb']->doPostRequest($requestBody);
    }
    


}

?>
