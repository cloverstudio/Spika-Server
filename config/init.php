<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/
 
 
/* change here */

if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false) {
    define('ROOT_URL','');
    define('ROOT_URL_WITHOUT_HOST', '/wwwroot');
    define('LOCAL_ROOT_URL', 'http://localhost/wwwroot');
    define("MySQL_HOST", '');
    define("MySQL_SOCKET", '');
    define('MySQL_DBNAME', '');
    define('MySQL_USERNAME', 'root');
    define('MySQL_PASSWORD', '');
    define('GCS_BUCKET_NAME', '');
 
} else {
    define('ROOT_URL','http://localhost:8080/wwwroot');
    define('ROOT_URL_WITHOUT_HOST', '/wwwroot');
    define('LOCAL_ROOT_URL', 'http://localhost/wwwroot');
    define('ROOT_URL_WITHOUT_HOST','/wwwroot');
    define("MySQL_HOST", '');
    define("MySQL_SOCKET", '');
    define('MySQL_DBNAME', '');
    define('MySQL_USERNAME', '');
    define('MySQL_PASSWORD', '');
    define('GCS_BUCKET_NAME','');
}
  

/* end change here */

define('AdministratorEmail', "admin@spikaapp.com");

define('ENABLE_LOGGING',false);

define('SUPPORT_USER_ID', 1);
define('ADMIN_LISTCOUNT', 10);
define("DEFAULT_LANGUAGE","en");

define('HTTP_PORT', 80);

define('TOKEN_VALID_TIME',60*60*24);
define('PW_RESET_CODE_VALID_TIME',60*5);

define("DIRECTMESSAGE_NOTIFICATION_MESSAGE", "You got message from %s");
define("GROUPMESSAGE_NOTIFICATION_MESSAGE", "%s posted message to group %s");
define("ACTIVITY_SUMMARY_DIRECT_MESSAGE", "direct_messages");
define("ACTIVITY_SUMMARY_GROUP_MESSAGE", "group_posts");

define("APN_DEV_CERT_PATH", "files/apns-dev.pem");
define("APN_PROD_CERT_PATH", "files/apns-prod.pem");
define("GCM_API_KEY","AIzaSyCEvnSSPg7zq5tMIz0zXLVK9ytwQTgjPEE");

define("SEND_EMAIL_METHOD",1); // 0: dont send 1:local smtp 2:gmail
define("GMAIL_USER",""); 
define("GMAIL_PASSWORD",""); 
