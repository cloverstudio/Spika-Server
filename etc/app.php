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


$app->mount('/', new Spika\Controllers\InstallerController());
$app->mount('/api/', new Spika\Controllers\SendPasswordController());
$app->mount('/api/', new Spika\Controllers\ReportController());
$app->mount('/api/', new Spika\Controllers\FileController());
$app->mount('/api/', new Spika\Controllers\SearchGroupController());
$app->mount('/api/', new Spika\Controllers\SearchUserController());
$app->mount('/api/', new Spika\Controllers\SignoutController());
$app->mount('/api/', new Spika\Controllers\CheckUniqueController());
$app->mount('/api/', new Spika\Controllers\AuthController());
$app->mount('/api/', new Spika\Controllers\GeneralAPIHandlerController());
