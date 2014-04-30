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
define('ROOT_URL','http://localhost:8080/wwwroot');
define('LOCAL_ROOT_URL', 'http://localhost/wwwroot');

define("MySQL_HOST", 'localhost');
define('MySQL_DBNAME', 'spikadb');
define('MySQL_USERNAME', 'root');
define('MySQL_PASSWORD', 'yourpassword');
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
define("GCM_API_KEY","AIzaSyDOkqeO0MZ_igwH_zGyy95DO1ahM8-Ebrw");

define("SEND_EMAIL_METHOD",1); // 0: dont send 1:local smtp 2:gmail
define("GMAIL_USER",""); 
define("GMAIL_PASSWORD",""); 
