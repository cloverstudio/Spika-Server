<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//error_reporting( E_ALL );
//ini_set( "display_errors", 1 );
date_default_timezone_set("GMT");

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../init.php';
require_once __DIR__.'/../etc/tokenCheker.php';
require_once __DIR__.'/../services/SpikaDBProvider.php';
require_once __DIR__.'/../controllers/GeneralAPIHandlerController.php';
require_once __DIR__.'/../controllers/CheckUniqueController.php';
require_once __DIR__.'/../controllers/AuthController.php';
require_once __DIR__.'/../controllers/SignoutController.php';
require_once __DIR__.'/../controllers/SearchUserController.php';
require_once __DIR__.'/../controllers/SearchGroupController.php';
require_once __DIR__.'/../controllers/FileController.php';
require_once __DIR__.'/../controllers/ReportController.php';
require_once __DIR__.'/../controllers/SendPasswordController.php';
require_once __DIR__.'/../controllers/InstallerController.php';
require_once __DIR__.'/../etc/utils.php';


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Silex\Provider\MonologServiceProvider;
use Monolog\Logger;

$app = new Silex\Application();
$app['debug'] = true;


$app['beforeTokenChecker'] = $app->protect($beforeTokenCheker);

// register providers

// logging
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/debug.log',
));

$app->register(new Spika\SpikaDBProvider(), array(
    'couchdb.couchDBURL' => CouchDBURL,
));


$app->mount('/', new Spika\InstallerController());
$app->mount('/api/', new Spika\SendPasswordController());
$app->mount('/api/', new Spika\ReportController());
$app->mount('/api/', new Spika\FileController());
$app->mount('/api/', new Spika\SearchGroupController());
$app->mount('/api/', new Spika\SearchUserController());
$app->mount('/api/', new Spika\SignoutController());
$app->mount('/api/', new Spika\CheckUniqueController());
$app->mount('/api/', new Spika\AuthController());
$app->mount('/api/', new Spika\GeneralAPIHandlerController());
