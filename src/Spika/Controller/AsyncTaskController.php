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


class AsyncTaskController extends SpikaBaseController
{
    public function connect(Application $app)
    {
    
        $controllers = $app['controllers_factory'];
		$self = $this;
		
		$controllers->post('/notifyNewDirectMessage', function (Request $request) use ($self,$app) {
			
			$host = $request->getHttpHost();
			if($host != "localhost"){
				return $self->returnErrorResponse("invalid access to internal API");
			}
			
			$requestBody = $request->getContent();
			$requestData = json_decode($requestBody,true);
			
			if(empty($requestData['messageId']))
				return $self->returnErrorResponse("insufficient params");

			$messageId = $requestData['messageId'];
			$message = $app['spikadb']->findMessageById($messageId);
			
			$app['spikadb']->updateActivitySummaryByDirectMessage($message['to_user_id'],$message['from_user_id']);
			
			return "";
			
		});
		
		$controllers->post('/notifyNewGroupMessage', function (Request $request) use ($self,$app) {

			$host = $request->getHttpHost();
			if($host != "localhost"){
				return $self->returnErrorResponse("invalid access to internal API");
			}
			
			$requestBody = $request->getContent();
			$requestData = json_decode($requestBody,true);
			
			if(empty($requestData['messageId']))
				return $self->returnErrorResponse("insufficient params");

			$messageId = $requestData['messageId'];
			$message = $app['spikadb']->findMessageById($messageId);
			
			$app['spikadb']->updateActivitySummaryByGroupMessage($message['to_group_id'],$message['from_user_id']);
			
			return "";
			
		});
		
        return $controllers;
        
    }
    
}

?>
