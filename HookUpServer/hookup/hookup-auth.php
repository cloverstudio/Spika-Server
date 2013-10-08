<?php
/* HookUp authentication */

include("./lib/startup.php");

/* Check the request type */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $reqBody = file_get_contents('php://input');

    postRequestAuth($reqBody);

} else {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {

        $getRequest = $db_url . $_SERVER['QUERY_STRING'];

        getRequestAuth($getRequest);

    }
}

/* GET request */
function getRequestAuth($url)
{

    global $db_username, $db_password;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);

    curl_close($curl);

    echo $result;

    die();

}

/* POST request */
function postRequestAuth($reqJson)
{

    global $db_username, $db_password, $db_url;

    $curl = curl_init();

    $reqJson = json_decode($reqJson, true);

    $userEmail = '"' . $reqJson['email'] . '"';

    curl_setopt($curl, CURLOPT_URL, $db_url . "_design/app/_view/find_user_by_email?key=" . $userEmail);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($curl);

    curl_close($curl);

    $json = json_decode($result, true);

    if (empty($json['rows'][0]['value']['email'])) {
        $arr = array('message' => 'User not found!', 'error' => 'logout');

        echo json_encode($arr);
        die();
    }

    if ($json['rows'][0]['value']['password'] != $reqJson['password']) {
        $arr = array('message' => 'Wrong password!', 'error' => 'logout');

        echo json_encode($arr);
        die();
    }

    $token = randString(40, 40);

    $json['rows'][0]['value']['token'] = $token;
    $json['rows'][0]['value']['token_timestamp'] = time();
    $json['rows'][0]['value']['last_login'] = time();

    $userJson = $json['rows'][0]['value'];

    saveUserToken(json_encode($userJson), $json['rows'][0]['value']['_id']);

}

/* Save a new generated token, token timestamp and last login */
function saveUserToken($userJson, $id)
{

    global $db_username, $db_password, $db_url;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $db_url . $id);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $curl,
        CURLOPT_HTTPHEADER,
        array("Content-Type: application/json", 'Content-Length: ' . strlen($userJson))
    );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $userJson);

    $result = curl_exec($curl);

    curl_close($curl);

    $userJson = json_decode($userJson, true);
    $json = json_decode($result, true);

    $userJson['_rev'] = $json['rev'];

    $responseJson = array();

    $responseJson['rows'][0]['value'] = $userJson;

    echo json_encode($responseJson);

}


?>
