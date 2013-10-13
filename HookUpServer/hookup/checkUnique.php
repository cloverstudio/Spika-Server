<?php

include("./lib/startup.php");

if (!empty($_GET['email'])) {


    $nameQuery = urldecode($_GET['email']);


    $startKey = "\"{$nameQuery}\"";
    $query = "?key={$startKey}";
    $result = doGet(HOST . "/" . DB . "/_design/app/_view/find_user_by_email{$query}");
    $nameResult = json_decode($result, true);

    $result = array();
    foreach ($nameResult['rows'] as $row) {
        $result[] = $row['value'];
    }

    echo stripParamsFromJson(json_encode($result, true));
}


if (!empty($_GET['username'])) {
    $nameQuery = urldecode($_GET['username']);
    $startKey = "\"{$nameQuery}\"";
    $query = "?key={$startKey}";
    $result = doGet(HOST . "/" . DB . "/_design/app/_view/find_user_by_name{$query}");
    $nameResult = json_decode($result, true);

    $result = array();
    foreach ($nameResult['rows'] as $row) {
        $result[] = $row['value'];
    }

    echo stripParamsFromJson(json_encode($result, true));
}

if (!empty($_GET['groupname'])) {
    $nameQuery = urldecode($_GET['groupname']);
    $startKey = "\"{$nameQuery}\"";
    $query = "?key={$startKey}";
    $result = doGet(HOST . "/" . DB . "/_design/app/_view/find_group_by_name{$query}");
    $nameResult = json_decode($result, true);

    $result = array();
    foreach ($nameResult['rows'] as $row) {
        $result[] = $row['value'];
    }

    echo json_encode($result, true);
}

?>