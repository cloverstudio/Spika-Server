<?php

function getMicrotime()
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

function pr($message)
{

    if (!defined('SHELL')) {
        print "<pre>" . print_r($message, true) . "</pre>";
    } else {
        print print_r($message, true) . "\n";
    }
}

function prd($message)
{
    print "<pre>" . print_r($message, true) . "</pre>";
    die();
}

//error handling function
function customError($errno, $errstr)
{
    global $_BASEDIR;

    $errortype = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_STRICT => 'Runtime Notice'
    );

    $errString = $errortype[$errno] . ": $errstr";

    $traceAry = debug_backtrace();
    //$lastOccured = $traceAry[count($traceAry) - 1];
    $lastOccured = $traceAry[0];

    if (isset($lastOccured['file']) && isset($lastOccured['line'])) {
        $errString .= " in {$lastOccured['file']} on line {$lastOccured['line']}";
    }

    if (ShowErr) {
        echo $errString . "<br />";
    }

    if (LogErr) {
        $debug_file = ROOT_DIR . DS . "tmp" . DS . "log" . DS . "err.log";
        file_put_contents($debug_file, $errString . "\n", FILE_APPEND);
    }

}


//application log
function _log($text, $file = 'debug.log')
{

    global $_BASEDIR;
    $debug_file = ROOT_DIR . DS . "tmp" . DS . "log" . DS . $file;

    $traceAry = debug_backtrace();
    $lastOccured = $traceAry[count($traceAry) - 1];

    if (is_array($text) || is_object($text)) {
        $text = print_r($text, true);
    }

    $text = "[{$lastOccured['file']}:{$lastOccured['line']}] " . $text . "\n";

    file_put_contents($debug_file, $text, FILE_APPEND);
}

//query log
function queryLog($text, $file = 'query.log')
{
    global $_BASEDIR;

    if (!LogQuery) {
        return;
    }

    $traceAry = debug_backtrace();
    $lastOccured = $traceAry[count($traceAry) - 1];
    $text = "( {$text} ) in {$lastOccured['file']} on line {$lastOccured['line']} \n";

    $debug_file = ROOT_DIR . DS . "tmp" . DS . 'log' . DS . $file;

    file_put_contents($debug_file, $text, FILE_APPEND);
}

//access log
function accessLog($file = 'access.log')
{
    global $_BASEDIR, $_POST, $_SERVER;

    if (!LogAccess) {
        return;
    }

    $scriptPath = $_SERVER["SCRIPT_NAME"];
    $remote = $_SERVER["REMOTE_ADDR"];
    $tmp = explode("/", $scriptPath);
    $scriptName = $tmp[count($tmp) - 1];

    $post = '';
    foreach ($_POST as $key => $val) {
        $post .= "{$key}:{$val}\t";
    }

    $text = "{$scriptName}\t{$remote}\t{$post}" . date("Y-m-d H:i:s") . "\n";

    $debug_file = ROOT_DIR . DS . "tmp" . DS . 'log' . DS . $file;
    file_put_contents($debug_file, $text, FILE_APPEND);
}

?>