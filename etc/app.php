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
require_once __DIR__.'/../init.php';
require_once __DIR__.'/../etc/utils.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;

$app = new Silex\Application(isset($dependencies) ? $dependencies : array());
$app['debug'] = true;

// register providers

// logging
$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../../../shared/logs/debug.log',
));

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
    'couchdb.couchDBURL' => CouchDBURL
));

$app->register(new SwiftmailerServiceProvider());
$app->register(new Spika\Provider\TokenCheckerServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/Spika/Views',
));

$app->register(new Silex\Provider\SessionServiceProvider(), array(
));


$app->mount('/', new Spika\Controller\InstallerController());
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
$app->mount('/page/', new Spika\Controller\PasswordResetController());
//$app->mount('/api/', new Spika\Controller\GeneralAPIHandlerController());