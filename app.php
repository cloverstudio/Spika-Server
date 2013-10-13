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
require_once __DIR__.'/lib/SpikaDBProvider.php';

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


// define controllers


// Auth controller
$app->post('/apihandler/hookup-auth.php', function (Request $request) use ($app) {
	
	$requestBody = $request->getContent();
	$authResult = $app['spikadb']->doSpikaAuth($requestBody);
	
	$app['monolog']->addDebug("Auth Response : \n {$authResult} \n");

    return $authResult;

});

// logout controller
$app->get('/apihandler/unregistToken.php', function (Request $request) use ($app) {
	
	$userId = $request->get('user_id');
	return $app['spikadb']->unregistToken($userId);

});

// check unique controller
$app->get('/apihandler/checkUnique.php', function (Request $request) use ($app) {
	
	$email = $request->get('email');
	$username = $request->get('username');
	$groupname = $request->get('groupname');
	
	if(!empty($email)){
		return $app['spikadb']->checkEmailIsUnique($email);
	}
	
	if(!empty($username)){
		return $app['spikadb']->checkUserNameIsUnique($email);
	}
	
	if(!empty($groupname)){
		return $app['spikadb']->checkGroupNameIsUnique($email);
	}
	
	return '';

});

// general post controller
$app->post('/apihandler/', function (Request $request) use ($app) {
	
	$requestBody = $request->getContent();
	
	// convert created and modified fields automatically to be server time
	$requestBody = preg_replace("/,\"created\":[0-9]*?}/", ",\"created\":" . time() . "}", $requestBody);
    $requestBody = preg_replace("/,\"created\":[0-9]*?,/", ",\"created\":" . time() . ",", $requestBody);
    $requestBody = preg_replace("/,\"modified\":[0-9]*?}/", ",\"modified\":" . time() . "}", $requestBody);
    $requestBody = preg_replace("/,\"modified\":[0-9]*?,/", ",\"modified\":" . time() . ",", $requestBody);
    
    return $app['spikadb']->doPostRequest($requestBody);
    
});


// general get controller
$app->get('/apihandler/{args}', function (Request $request,$args) use ($app) {
	
	$couchDBQuery = $args . "?" . $request->getQueryString();

	return $app['spikadb']->doGetRequest($couchDBQuery);
	return $couchDBQuery;
})
->assert('args', '.*')
->convert('args', function ($args) {
    return $args;
});

$app->run();

?>