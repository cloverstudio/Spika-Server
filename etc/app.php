<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


error_reporting( E_ALL );
ini_set( "display_errors", 1 );
date_default_timezone_set("GMT");

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../etc/constants.php';
require_once __DIR__.'/../config/init.php';
require_once __DIR__.'/../etc/utils.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

$app = new Silex\Application(isset($dependencies) ? $dependencies : array());
$app['debug'] = true;

// register providers

// logging  
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/debug.log',
));

if(!ENABLE_LOGGING){
    $app['monolog.handler'] = function () use ($app) {
        return new NullHandler();
    };
}
   
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array (
            'driver'    => 'pdo_mysql',
            'host'      => MySQL_HOST,
            'dbname'    => MySQL_DBNAME,
            'user'      => MySQL_USERNAME,
            'password'  => MySQL_PASSWORD,
            'charset'   => 'utf8'
    )
));


$app->register(new Spika\Provider\SpikaDbServiceProvider(), array(
));

$app->register(new SwiftmailerServiceProvider());
$app->register(new Spika\Provider\TokenCheckerServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/Spika/Views',
));

$app->register(new Silex\Provider\SessionServiceProvider(), array(
));

$app->register(new Spika\Provider\PushNotificationProvider(), array(
    'pushnotification.options' => array (
            'GCMAPIKey'    => GCM_API_KEY,
            'APNProdPem'   => __DIR__.'/../'.APN_PROD_CERT_PATH,
            'APNDevPem'    => __DIR__.'/../'.APN_DEV_CERT_PATH
    )
));


$app['beforeApiGeneral'] = $app->share(function () use ($app) {
    return new Spika\Middleware\APIGeneralBeforeHandler(
        $app['spikadb'],
        $app['logger'],
        $app
    );
});

$app['adminBeforeTokenChecker'] = $app->share(function () use ($app) {
    return new Spika\Middleware\AdminChecker(
        $app
    );
});


$app->mount('/api/', new Spika\Controller\ServerListController());
$app->mount('/api/', new Spika\Controller\SendPasswordController());
$app->mount('/api/', new Spika\Controller\ReportController());
$app->mount('/api/', new Spika\Controller\FileController());
$app->mount('/api/', new Spika\Controller\SearchUserController());
$app->mount('/api/', new Spika\Controller\SignoutController());
$app->mount('/api/', new Spika\Controller\CheckUniqueController());
$app->mount('/api/', new Spika\Controller\UserController());
$app->mount('/api/', new Spika\Controller\MessageController());
$app->mount('/api/', new Spika\Controller\GroupController());
$app->mount('/api/', new Spika\Controller\CheckUniqueController());
$app->mount('/api/', new Spika\Controller\AsyncTaskController());
$app->mount('/api/', new Spika\Controller\WebViewController());
$app->mount('/page/', new Spika\Controller\PasswordResetController());
$app->mount('/page/', new Spika\Controller\Web\StaticPageController());

$app->mount('/', new Spika\Controller\Web\Installer\InstallerController());
$app->mount('/admin', new Spika\Controller\Web\Admin\LoginController());
$app->mount('/admin/', new Spika\Controller\Web\Admin\GroupController());
$app->mount('/admin/', new Spika\Controller\Web\Admin\NewsController());
$app->mount('/admin/', new Spika\Controller\Web\Admin\UserController());
$app->mount('/admin/', new Spika\Controller\Web\Admin\GroupCategoryController());
$app->mount('/admin/', new Spika\Controller\Web\Admin\EmoticonController());
$app->mount('/admin/', new Spika\Controller\Web\Admin\ServerController());

$app->mount('/client/', new Spika\Controller\Web\Client\LoginController());
$app->mount('/client/', new Spika\Controller\Web\Client\MainController());



