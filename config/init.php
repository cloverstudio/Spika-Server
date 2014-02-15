<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
 
define('CouchDBURL', isset($_ENV['SPIKA_COUCH_DB_URL']) ? $_ENV['SPIKA_COUCH_DB_URL'] : "http://localhost:5984/spikademo");
define('AdministratorEmail', isset($_ENV['SPIKA_ADMIN_EMAIL']) ? $_ENV['SPIKA_ADMIN_EMAIL'] : "ken.yasue@clover-studio.com");
define('TOKEN_VALID_TIME', isset($_ENV['SPIKA_TOKEN_VALID_TIME']) ? $_ENV['SPIKA_TOKEN_VALID_TIME'] : 60*60*24);
define('PW_RESET_CODE_VALID_TIME', isset($_ENV['SPIKA_PW_RESET_CODE_VALID_TIME']) ? $_ENV['SPIKA_PW_RESET_CODE_VALID_TIME'] : 60*5);

define('ROOT_URL', isset($_ENV['SPIKA_ROOT_URL']) ? $_ENV['SPIKA_ROOT_URL'] : "http://officialapi.spikaapp.com/");
define('LOCAL_ROOT_URL', isset($_ENV['SPIKA_LOCAL_ROOT_URL']) ? $_ENV['SPIKA_LOCAL_ROOT_URL'] : "http://localhost/projects/spika_orig/Spika-Server/wwwroot");

define("MySQL_HOST", isset($_ENV['MySQL_HOST']) ? $_ENV['MySQL_HOST'] : "localhost");
define('MySQL_DBNAME', isset($_ENV['MySQL_DBNAME']) ? $_ENV['MySQL_DBNAME'] : "spika_orig");
define('MySQL_USERNAME', isset($_ENV['MySQL_USERNAME']) ? $_ENV['MySQL_USERNAME'] : "root");
define('MySQL_PASSWORD', isset($_ENV['MySQL_PASSWORD']) ? $_ENV['MySQL_PASSWORD'] : "cloverpass013");
define('ADMIN_USERNAME', isset($_ENV['SPIKA_Admin_USERNAME']) ? $_ENV['SPIKA_Admin_USERNAME'] : "admin");
define('ADMIN_PASSWORD', isset($_ENV['SPIKA_Admin_PASSWORD']) ? $_ENV['SPIKA_Admin_PASSWORD'] : "testtest");
define('ENABLE_LOGGING', isset($_ENV['SPIKA_ENABLE_LOGGING']) ? $_ENV['SPIKA_ENABLE_LOGGING'] : true);

define('SUPPORT_USER_ID', 1);
define('ADMIN_LISTCOUNT', 10);
define("DEFAULT_LANGUAGE","en");

define('HTTP_PORT', 80);

define("DIRECTMESSAGE_NOTIFICATION_MESSAGE", "You got message from %s");
define("GROUPMESSAGE_NOTIFICATION_MESSAGE", "%s posted message to group %s");
define("ACTIVITY_SUMMARY_DIRECT_MESSAGE", "direct_messages");
define("ACTIVITY_SUMMARY_GROUP_MESSAGE", "group_posts");

define("APN_DEV_CERT_PATH", "files/apns-dev.pem");
define("APN_PROD_CERT_PATH", "files/apns-prod.pem");
define("GCM_API_KEY","AIzaSyDOkqeO0MZ_igwH_zGyy95DO1ahM8-Ebrw");

define("SEND_EMAIL_METHOD",2); // 0: dont send 1:local smtp 2:gmail
define("GMAIL_USER","spika@clover-studio.com"); 
define("GMAIL_PASSWORD","cloverpass013"); 
