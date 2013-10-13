<?php

include("./lib/startup.php");

if (isset($_GET['at']) && $_GET['at'] == 100) {
    $_GET['at'] = "";
}

if (isset($_GET['af']) && $_GET['af'] == 0) {
    $_GET['af'] = "";
}

if (empty($_GET['n']) && empty($_GET['af']) && empty($_GET['at']) && empty($_GET['g'])) {
    $query = "";
    $result = doGet(HOST . "/" . DB . "/_design/app/_view/searchuser_name{$query}");
    $defaultResult = json_decode($result, true);
    _log('emptyquery');
}

if (!empty($_GET['n'])) {
    $nameQuery = urldecode($_GET['n']);
    $startKey = "\"{$nameQuery}\"";
    $endKey = "\"{$nameQuery}ZZZZ\"";
    $query = "?startkey={$startKey}&endkey={$endKey}";
    $result = doGet(HOST . "/" . DB . "/_design/app/_view/searchuser_name{$query}");
    $nameResult = json_decode($result, true);
}

if ($_GET['af']) {
    $ageFrom = $_GET['af'];
    if ($ageFrom == "0") {
        $ageFrom = "";
    }
} else {
    $ageFrom = "";
}

if ($_GET['at']) {
    $ageTo = $_GET['at'];
    if ($ageTo == "100") {
        $ageTo = "";
    }
} else {
    $ageTo = "";
}

$ageQuery = "";
if (!empty($ageFrom) && !empty($ageTo)) {
    $ageQuery = "?startkey={$ageFrom}&endkey={$ageTo}";
}

if (!empty($ageFrom) && empty($ageTo)) {
    $ageQuery = "?startkey={$ageFrom}";
}

if (empty($ageFrom) && !empty($ageTo)) {
    $ageQuery = "?endkey={$ageTo}";
}

if (!empty($ageQuery)) {

    $startKey = "{$ageFrom}";
    $endKey = "{$ageTo}";

    $url = HOST . "/" . DB . "/_design/app/_view/searchuser_age{$ageQuery}";
    $result = doGet($url);
    $ageResult = json_decode($result, true);

}

if (!empty($_GET['g'])) {
    $genderQuery = $_GET['g'];
    $query = "?key=\"{$genderQuery}\"";
    $result = doGet(HOST . "/" . DB . "/_design/app/_view/searchuser_gender{$query}");
    $genderResult = json_decode($result, true);
}

$ids = array();
$users = array();

if (isset($nameResult)) {

    foreach ($nameResult['rows'] as $row) {

        $ids[] = $row['id'];
        $users[$row['id']] = $row;
    }

}

if (isset($ageResult)) {

    foreach ($ageResult['rows'] as $row) {

        $ids[] = $row['id'];
        $users[$row['id']] = $row;
    }

}

if (isset($genderResult)) {

    foreach ($genderResult['rows'] as $row) {

        $ids[] = $row['id'];
        $users[$row['id']] = $row;
    }

}

$ids = array_unique($ids);

if (isset($nameResult)) {

    foreach ($ids as $key => $id) {

        $isExists = false;

        foreach ($nameResult['rows'] as $row) {


            if ($row['id'] == $id) {
                $isExists = true;
            }

        }


        if (!$isExists) {
            unset($ids[$key]);
        }
    }

}

if (isset($ageResult)) {

    foreach ($ids as $key => $id) {

        $isExists = false;

        foreach ($ageResult['rows'] as $row) {


            if ($row['id'] == $id) {
                $isExists = true;
            }

        }


        if (!$isExists) {
            unset($ids[$key]);
        }
    }

}


if (isset($genderResult)) {

    foreach ($ids as $key => $id) {

        $isExists = false;

        foreach ($genderResult['rows'] as $row) {


            if ($row['id'] == $id) {
                $isExists = true;
            }

        }


        if (!$isExists) {
            unset($ids[$key]);
        }
    }

}


$result = array();
foreach ($ids as $id) {

    $result[] = $users[$id]['value'];

}

if (empty($_GET['n']) && empty($_GET['af']) && empty($_GET['at']) && empty($_GET['g'])) {

    foreach ($defaultResult['rows'] as $row) {
        $result[] = $row['value'];
    }

}

_log(print_r($result, true));

echo stripParamsFromJson(json_encode($result, true));
//echo json_encode($result,true);

?>