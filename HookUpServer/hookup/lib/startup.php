<?php

//define document root path,document root url
define("ROOT_DIR", dirname(dirname(__FILE__)));
define("DS", "/");

//begin include
include(ROOT_DIR . DS . 'lib' . DS . 'util.php');
include(ROOT_DIR . DS . 'lib' . DS . 'db.php');
include(ROOT_DIR . DS . 'lib' . DS . 'debug.php');
include(ROOT_DIR . DS . 'lib' . DS . 'hu_client.php');
include(ROOT_DIR . DS . 'lib' . DS . 'init.php');

if (!defined("notificationsHandler")) {
    include(ROOT_DIR . DS . 'lib' . DS . 'notificationsHandler.php');
}

include(ROOT_DIR . DS . 'app_specific' . DS . $APP . DS . 'createUserHandler.php');
include(ROOT_DIR . DS . 'app_specific' . DS . $APP . DS . 'appConst.php');

////////////////////////////////////
// Error display setting
/////////////////////////////////////////////////////////////////////////////////

accessLog();

if (ShowErr) {
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');;
}

set_error_handler("customError");

/*
	header("HTTP/1.0 503 Service Unavailable");
	echo "Sorry We are now working on backend, please access after.";
	die();
*/
?>