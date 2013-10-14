<?php

define("SELECT", 1);
define("UPDATE", 2);
define("INSERT", 3);
define("DELETE", 4);

/////////////////////////////////////////////////////////////
// generate query
/////////////////////////////////////////////////////////////
function generateQuery($action, $table, $data, $where = '', $tail = '')
{

    if ($action == UPDATE) {

        $valueAry = array();
        foreach ($data as $key => $value) {

            if (preg_match('/null/i', $value)) {
                $valueAry[] = "{$key} = null";
            } else {
                $valueAry[] = "{$key} = {$value}";
            }
        }

        $query = "UPDATE {$table} SET ";
        $query .= implode(" ,", $valueAry);
        $query .= " WHERE {$where} {$tail}";

        return $query;
    }

    if ($action == INSERT) {

        $keyAry = array();
        foreach ($data as $key => $value) {
            $keyAry[] = "{$key}";
        }

        $valueKey = array();
        foreach ($data as $key => $value) {
            $valueKey[] = "{$value}";
        }

        $query = "INSERT INTO {$table}( ";
        $query .= implode(", ", $keyAry);
        $query .= " ) VALUES ( '";
        $query .= implode("', '", $valueKey);
        $query .= "' ) ";

        return $query;
    }

    if ($action == DELETE) {

        $query = "DELETE FROM {$table}";
        $query .= " WHERE {$where} {$tail}";

        return $query;
    }

    if ($action == SELECT) {

        $query = "SELECT ";
        if (is_array($data)) {
            $query .= implode(",", $data);
        } else {
            $query .= "*";
        }

        $query .= " FROM {$table}";
        $query .= " WHERE {$where} {$tail}";

        return $query;
    }

}

/////////////////////////////////////////////////////////////
// execute query
/////////////////////////////////////////////////////////////
function executeQuery($conn, $query)
{

    $result = array();

    if (!$conn) {
        return false;
    }

    $startTime = getMicrotime();
    $resultLink = mysql_query($query, $conn);
    $finishTime = getMicrotime();

    if (!$resultLink) {
        trigger_error("Invalid query: {$query} " . mysql_error(), E_USER_WARNING);
        $result = mysql_error();

    } else {
        $message = round($finishTime - $startTime, 4) . "s\t" . $query;
        queryLog($message);

    }

    if ($resultLink && (preg_match('/select/', $query) || preg_match('/SELECT/', $query))) {

        while ($line = mysql_fetch_array($resultLink, MYSQL_ASSOC)) {
            $result[] = $line;
        }

        mysql_free_result($resultLink);

    }

    return $result;
}

/////////////////////////////////////////////////////////////
// get db for user
/////////////////////////////////////////////////////////////

function connectToDB()
{

    global $_DATABASES;

    $db_host = DB_HOST;
    $db_name = DB_NAME;


    //connect to db
    $link = mysql_connect($db_host, DB_USER, DB_PASS, true);
    if (!$link) {
        trigger_error('Not connected : ' . mysql_error(), E_USER_WARNING);
        return false;
    }

    $selectDbResult = mysql_select_db($db_name, $link);
    if (!$selectDbResult) {
        trigger_error('Not connected : ' . mysql_error(), E_USER_WARNING);
        $link = false;
    }

    mysql_set_charset('utf8');

    return $link;

}

// get information from settings table
function getSettings()
{

    global $_MASTERDB;

    $data = null;
    $tmpData = executeQuery($_MASTERDB, "select * from settings");

    $data = array();
    foreach ($tmpData as $row) {
        $data[$row['s_key']] = $row['s_value'];
    }

    return $data;
}

function closeDB($link)
{
    mysql_close($link);
}

function escp($text)
{
    return mysql_real_escape_string($text);
}

?>