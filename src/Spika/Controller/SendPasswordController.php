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


class SendPasswordController extends SpikaBaseController
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
		$self = $this;
		
		// check unique controller
		$controllers->get('/resetPassword', function (Request $request) use ($app,$self) {

			$email = $request->get('email');
			
			$startKey = "\"{$email}\"";
			$query = "?key={$startKey}";
			$resultTmp = $app['spikadb']->doGetRequest("/_design/app/_view/find_user_by_email{$query}",false);
			$resutlData = json_decode($resultTmp, true);

		    if (count($resutlData['rows']) != 0) {
		
		        $user = $resutlData['rows'][0]['value'];
				$resetCode = $app['spikadb']->addPassworResetRequest($user['_id']);
				
				$resetPasswordUrl = ROOT_URL . "/page/resetPassword/" . $resetCode;
				
				$app['monolog']->addDebug("Generated reset password url {$resetPasswordUrl}");
				
				$body = "Please reset password here {$resetPasswordUrl}";
				
				$message = \Swift_Message::newInstance()
					->setSubject("Spika Reset Password")
					->setFrom(AdministratorEmail)
					->setTo($user['email'])
					->setBody($body);
					
				if($app['mailer']->send($message)){
					return 'OK';
				}else{
					 return $self->returnErrorResponse("faied to send email.");
				}
				
				
				
		    }else{
			    
			    return $self->returnErrorResponse("invalid email");
			    
		    }
    
			return 'OK';
			
		});
        
        return $controllers;
    }
    
}

?>
