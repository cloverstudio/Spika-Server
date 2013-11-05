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

		// check unique controller
		$controllers->get('/resetPassword', function (Request $request) use ($app) {
			return $app['twig']->render('passwordReset.twig', array(
				'ROOT_URL' => ROOT_URL,
			));
		});

        return $controllers;

    }
    
}

?>