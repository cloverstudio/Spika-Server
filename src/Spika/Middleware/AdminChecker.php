<?php
namespace Spika\Middleware;

use Spika\Db\DbInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminChecker
{

    private $username;
    private $password;
    private $app;
    
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function __invoke(Request $request,\Silex\Application $app)
    {
        if ($app['session']->get('user') === null) {
            return $app->redirect(ROOT_URL . '/admin/login');
        }
    }

}
