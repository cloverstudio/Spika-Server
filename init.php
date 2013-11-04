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

define("DIRECTMESSAGE_NOTIFICATION_MESSAGE", "You got message from %s");
define("GROUPMESSAGE_NOTIFICATION_MESSAGE", "%s posted message to group %s");

define("ACTIVITY_SUMMARY_DIRECT_MESSAGE", "direct_messages");
define("ACTIVITY_SUMMARY_GROUP_MESSAGE", "group_posts");

?>
