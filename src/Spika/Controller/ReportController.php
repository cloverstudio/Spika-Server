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

class ReportController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        // check unique controller
        $controllers->get('/reportViolation.php', function (Request $request) use ($app) {
            $documentId = $request->get('docment_id');


            if(SEND_EMAIL_METHOD == EMAIL_METHOD_LOCALSMTP){
                
                $message = \Swift_Message::newInstance()
                    ->setSubject("SpilaViolationReport")
                    ->setFrom(AdministratorEmail)
                    ->setTo(AdministratorEmail)
                    ->setBody($documentId);
                    
                $app['mailer']->send($message);
                
            }
            
            if(SEND_EMAIL_METHOD == EMAIL_METHOD_GMAIL){
                
                $transport = \Swift_SmtpTransport::newInstance('smtp.googlemail.com', 465, 'ssl')
                    ->setUsername(GMAIL_USER)
                    ->setPassword(GMAIL_PASSWORD);

                $message = \Swift_Message::newInstance($transport)
                    ->setSubject("Spika Reset Password")
                    ->setFrom(AdministratorEmail)
                    ->setTo(AdministratorEmail)
                    ->setBody($documentId);
                
                $mailer->send($message);

            }
                    
            return 'OK';
        })->before($app['beforeApiGeneral'])->before($app['beforeTokenChecker']);

        return $controllers;
    }
}
