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
            
            $user = $app['spikadb']->findUserByEmail($email);
                        
            if (isset($user['_id'])) {
                
                $user = $app['spikadb']->findUserById($user['_id'],false);

                $resetCode = $app['spikadb']->addPassworResetRequest($user['_id']);
                
                $resetPasswordUrl = ROOT_URL . "/page/resetPassword/" . $resetCode;
                
                $body = "Please reset password here {$resetPasswordUrl}";

                try{
                
                    if(SEND_EMAIL_METHOD == EMAIL_METHOD_LOCALSMTP){
                        
                        $message = \Swift_Message::newInstance()
                            ->setSubject("Spika Reset Password")
                            ->setFrom(AdministratorEmail)
                            ->setTo($user['email'])
                            ->setBody($body);
                        
                        $mailer = \Swift_Mailer::newInstance();
                        
                        $mailer->send($message);
                        
                    }
                    
                    if(SEND_EMAIL_METHOD == EMAIL_METHOD_GMAIL){
                        
                        $transport = \Swift_SmtpTransport::newInstance('smtp.googlemail.com', 465, 'ssl')
                            ->setUsername(GMAIL_USER)
                            ->setPassword(GMAIL_PASSWORD);

                        $message = \Swift_Message::newInstance()
                            ->setSubject("Spika Reset Password")
                            ->setFrom(AdministratorEmail)
                            ->setTo($user['email'])
                            ->setBody($body);
                        
                        $mailer = \Swift_Mailer::newInstance($transport);
                        
                        $mailer->send($message);

                    }
                    
                                        
                } catch(\Exception $e){
                    
                }
                
                return 'OK';

            }else{
                
                return $self->returnErrorResponse("invalid email");
                
            }
    
            return 'OK';
            
        })->before($app['beforeApiGeneral']);
        
        return $controllers;
    }
    
}

?>
