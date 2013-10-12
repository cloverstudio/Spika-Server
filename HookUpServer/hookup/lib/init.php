<?php

define('SERVER_HOST', 'spikademo.clover-studio.com');
define('ROOT_URL', 'http://spikademo.clover-studio.com/HookUpServer/hookup');
define("API_URL", "http://spikademo.clover-studio.com/HookUpServer/hookup/");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', '1');

define("TOKEN_VALID_TIME", 60 * 60 * 24); // 24 hours

define("TMPDILR", "/tmp");
define('PHP_COMMAND', "php"); // apsolute path for php command

/////////////////////////////////////////////////////////////////////////////////////
// Debug setting
/////////////////////////////////////////////////////////////////////////////////////

define('ShowErr', false);
define('LogAccess', false);
define('LogErr', true);
define('LogQuery', false);

$databaseServers = array(
    'spikademo' => "http://localhost:5984/"
);

if (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
}

if (isset($headers['database'])) {
    $APP = $headers['database'];
} else {
    if (defined('APP')) {
        $APP = APP;
    } else {
        if (isset($_GET['db'])) {
            $APP = $_GET['db'];
        }
    }
}

if (isset($APP)
    && isset($databaseServers[$APP])
) {

    define("HOST", $databaseServers[$APP]);
    define("DB", $APP);
    define("DBUSERNAME", "");
    define("DBPASSWORD", "");

    $db_url = HOST . DB . "/";

} else {

    header("HTTP/1.0 503 Service Unavailable");
    echo "Sorry We are now working on backend, please access after.";
    die();

}

?>
