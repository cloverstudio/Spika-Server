<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/init.php';
require_once __DIR__.'/services/SpikaDBProvider.php';
require_once __DIR__.'/controllers/GeneralAPIHandlerController.php';
require_once __DIR__.'/controllers/CheckUniqueController.php';
require_once __DIR__.'/controllers/AuthController.php';
require_once __DIR__.'/controllers/SignoutController.php';
require_once __DIR__.'/controllers/SearchUserController.php';
require_once __DIR__.'/controllers/SearchGroupController.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Silex\Provider\MonologServiceProvider;
use Monolog\Logger;

$app = new Silex\Application();
$app['debug'] = true;


// register providers

// logging
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/logs/debug.log',
));

$app->register(new Spika\SpikaDBProvider(), array(
    'couchdb.couchDBURL' => CouchDBURL,
));


$app->mount('/apihandler/', new Spika\SearchGroupController());
$app->mount('/apihandler/', new Spika\SearchUserController());
$app->mount('/apihandler/', new Spika\SignoutController());
$app->mount('/apihandler/', new Spika\CheckUniqueController());
$app->mount('/apihandler/', new Spika\AuthController());
$app->mount('/apihandler/', new Spika\GeneralAPIHandlerController());

$app->run();

?>