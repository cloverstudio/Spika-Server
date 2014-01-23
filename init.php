<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
// import yaml parser to composer.json only for this part is too much so I manually do this.
function getEYDBConfiguration($EYDatabaseConfigurationFile){
	
	$keys = array('database','username','password','host');
	$dbConfData = array();
	
	$EYDatabaseConfiguration = file_get_contents($EYDatabaseConfigurationFile);
	$lines = explode("\n",$EYDatabaseConfiguration);
	foreach($lines as $line){
		
		foreach($keys as $key){
			
			preg_match_all("/{$key}: '(.*?)'/", $line, $matches);
			
			print_r($matches);
		}
		
	}
} 

// read database configuration from engine yard
$DBConfig = getEYDBConfiguration("../config/database.yml");

 
define('CouchDBURL', isset($_ENV['SPIKA_COUCH_DB_URL']) ? $_ENV['SPIKA_COUCH_DB_URL'] : "http://localhost:5984/spikademo");
define('AdministratorEmail', isset($_ENV['SPIKA_ADMIN_EMAIL']) ? $_ENV['SPIKA_ADMIN_EMAIL'] : "ken.yasue@clover-studio.com");
define('TOKEN_VALID_TIME', isset($_ENV['SPIKA_TOKEN_VALID_TIME']) ? $_ENV['SPIKA_TOKEN_VALID_TIME'] : 60*60*24);
define('PW_RESET_CODE_VALID_TIME', isset($_ENV['SPIKA_PW_RESET_CODE_VALID_TIME']) ? $_ENV['SPIKA_PW_RESET_CODE_VALID_TIME'] : 60*5);

define('ROOT_URL', isset($_ENV['SPIKA_ROOT_URL']) ? $_ENV['SPIKA_SPIKA_ROOT_URL'] : "");
define("MySQL_HOST", isset($_ENV['SPIKA_MySQL_HOST']) ? $_ENV['SPIKA_MySQL_HOST'] : "");
define('MySQL_DBNAME', isset($_ENV['SPIKA_MySQL_DBNAME']) ? $_ENV['SPIKA_MySQL_DBNAME'] : "");
define('MySQL_USERNAME', isset($_ENV['SPIKA_MySQL_USERNAME']) ? $_ENV['SPIKA_MySQL_USERNAME'] : "");
define('MySQL_PASSWORD', isset($_ENV['SPIKA_MySQL_PASSWORD']) ? $_ENV['SPIKA_MySQL_PASSWORD'] : "");


define('Admin_USERNAME', isset($_ENV['SPIKA_Admin_USERNAME']) ? $_ENV['SPIKA_Admin_USERNAME'] : "admin");
define('Admin_PASSWORD', isset($_ENV['SPIKA_Admin_PASSWORD']) ? $_ENV['SPIKA_Admin_PASSWORD'] : "password");


define('HTTP_PORT', 80);

define("DIRECTMESSAGE_NOTIFICATION_MESSAGE", "You got message from %s");
define("GROUPMESSAGE_NOTIFICATION_MESSAGE", "%s posted message to group %s");
define("ACTIVITY_SUMMARY_DIRECT_MESSAGE", "direct_messages");
define("ACTIVITY_SUMMARY_GROUP_MESSAGE", "group_posts");

define("APN_DEV_CERT_PATH", "files/apns-dev.pem");
define("APN_PROD_CERT_PATH", "files/apns-prod.pem");
define("GCM_API_KEY","AIzaSyDpsF-TIc6GJRGhqi-4T2NI5KHyc463QiM");

?>
