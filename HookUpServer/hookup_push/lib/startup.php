<?php
	
	//define document root path,document root url
	if(!defined("ROOT_DIR"))
		define("ROOT_DIR",dirname(dirname(__FILE__)));
		
	define("DS","/");
	
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	ini_set('display_errors', '1');

	//begin include
	include(ROOT_DIR . DS . 'lib' . DS . 'init.php');
	include(ROOT_DIR . DS . 'lib' . DS . 'db.php');
	include(ROOT_DIR . DS . 'lib' . DS . 'debug.php');
	include(ROOT_DIR . DS . 'lib' . DS . 'util.php');

	//constants
	define("STATE_WAIT",1);
	define("STATE_PROCESSING",2);
	define("STATE_ERROR",3);
	define("STATE_SUCCESS",4);
	define("SERVICE_PROVIDOR_APN_PROD",1);
	define("SERVICE_PROVIDOR_APN_DEV",2);
	define("SERVICE_PROVIDOR_GCM",3);
	
	$PROVIDER_LABELS = array(
		SERVICE_PROVIDOR_APN_PROD => "APN PROD",
		SERVICE_PROVIDOR_APN_DEV => "APN DEV",
		SERVICE_PROVIDOR_GCM => "GCM",
	);
	
	$STATE_LABELS = array(
		STATE_WAIT => "Waiting",
		STATE_PROCESSING => "Processing",
		STATE_ERROR => "Error",
		STATE_SUCCESS => "Sent",
	);
	
	
	define("QUEUE_MANAGER_NAME","queue_manager.php");
	define("QUEUE_WORKER_NAME","queue_worker.php");
	
	////////////////////////////////////
	// Error display setting
	/////////////////////////////////////////////////////////////////////////////////
	
	accessLog();

	if(ShowErr){
		ini_set('display_errors', '1');
	} else {
		error_reporting(0);
		ini_set('display_errors', '0');;
	}
	
	set_error_handler("customError");
?>