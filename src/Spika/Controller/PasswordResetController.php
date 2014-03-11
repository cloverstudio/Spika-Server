<?php

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

class PasswordResetController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;
        
        $controllers->get('/resetPassword/{resetCode}', function (Request $request,$resetCode) use ($app,$self) {
            
            if(!$self->checkResetCode($app,$resetCode)){
                return $app['twig']->render('passwordReset/passwordResetError.twig', array(
                    'ROOT_URL' => ROOT_URL,
                ));
            }

            return $app['twig']->render('passwordReset/passwordReset.twig', array(
                'ROOT_URL' => ROOT_URL,
                'resetCode' => $resetCode,
            ));

        });

        // check unique controller
        $controllers->post('/resetPassword', function (Request $request) use ($app,$self) {
            
            $resetCode = $request->get('resetCode');
            if(!$self->checkResetCode($app,$resetCode)){
                return $app['twig']->render('passwordReset/passwordResetError.twig', array(
                    'ROOT_URL' => ROOT_URL,
                ));
            }

            
            $newPassword = $request->get('password');
            $newPasswordConfirm = $request->get('confirm');
            $errorMessage = "";
            $Message = "";
            
            if(!preg_match("/[a-zA-Z0-9_-]{6}/",$newPassword)){
                $errorMessage = "Password should be alphanumeric and at least 6 characters.";
            }
            
            if($newPassword != $newPasswordConfirm){
                $errorMessage = "Passwords is not same.Plase try again.";
            }
            
            if(strlen($errorMessage) > 0){
                return $app['twig']->render('passwordReset/passwordReset.twig', array(
                    'ROOT_URL' => ROOT_URL,
                    'error' => $errorMessage,
                    'resetCode' => $resetCode
                ));
            }else{
                
                // change password
                $resetData = $app['spikadb']->getPassworResetRequest($resetCode);
                $app['spikadb']->changePassword($resetData['user_id'],md5($newPassword));
                return $app['twig']->render('passwordReset/passwordResetSucceed.twig', array(
                    'ROOT_URL' => ROOT_URL
                ));
            }
            
        })->before($app['beforeApiGeneral']);


        return $controllers;

    }
    
    function checkResetCode($app,$resetCode){
        
        $resetData = $app['spikadb']->getPassworResetRequest($resetCode);
        
        if(count($resetData) == 0 || isset($resetData['error'])){
            return false;
        }
        
        $requestReceivedTimeStamp = $resetData['created'];
        $interval = time() - $requestReceivedTimeStamp;
        
        if($interval > PW_RESET_CODE_VALID_TIME || $resetData['valid'] == 0){
            return false;
        }
        
        return true;
    }
}

?>
