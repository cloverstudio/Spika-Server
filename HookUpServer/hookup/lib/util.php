<?php


function stripParamsFromJson($json)
{

    $removeParams = array(
        "password",
        "token"
    );

    foreach ($removeParams as $paramToRemove) {
        $json = preg_replace("/,\"{$paramToRemove}\":\"[0-9a-zA-Z]*\"}/", "}", $json);
        $json = preg_replace("/,\"{$paramToRemove}\":\"[0-9a-zA-Z]*\",/", ",", $json);
    }

    echo $json;

}

function sendAPNProd($deviceToken, $message, $badge = "", $key = "", $params = array())
{

    $body = array();
    $body['aps'] = array('alert' => $message, 'badge' => $badge, 'sound' => 'default', 'value' => $key);
    $body['data'] = $params;
    $payload = json_encode($body);

    sendPushToQueue(APN_PRODUCTION_KEY, $deviceToken, $payload);

}

function sendAPNDev($deviceToken, $message, $badge = "", $key = "", $params = array())
{

    $body = array();
    $body['aps'] = array('alert' => $message, 'badge' => $badge, 'sound' => 'default', 'value' => $key);
    $body['data'] = $params;
    $payload = json_encode($body);

    sendPushToQueue(APN_DEVELOPMENT_KEY, $deviceToken, $payload);
}

function sendGCM($deviceToken, $message, $fromUser = "", $groupId = "", $type = "user", $fromUserName = "")
{

    $registrationIDs = array($deviceToken);

    $fields = array(
        'registration_ids' => $registrationIDs,
        'data' => array(
            "message" => $message,
            "fromUser" => $fromUser,
            "fromUserName" => $fromUserName,
            "type" => $type,
            "groupId" => $groupId
        ),
    );

    $payload = json_encode($fields);

    sendPushToQueue(GCM_KEY, $deviceToken, $payload);
}

function sendGroupMessageNotification($userDataFrom, $toUser, $toGroupData, $message)
{
    global $db_url;

    $url = $db_url . "/{$toUser}";
    $return = getRequest($url, true);
    $userDataTo = json_decode($return, true);

    $message = getPushMessageForGroup($userDataFrom['value']['name'], $toGroupData['name'], true);

    if (!empty($userDataTo['ios_push_token'])) {
        sendAPNProd(
            $userDataTo['ios_push_token'],
            $message,
            "",
            "",
            array('from_user' => $userDataFrom['value']['_id'], 'to_group' => $toGroupData['_id'], 'type' => 'group')
        );
        sendAPNDev(
            $userDataTo['ios_push_token'],
            $message,
            "",
            "",
            array('from_user' => $userDataFrom['value']['_id'], 'to_group' => $toGroupData['_id'], 'type' => 'group')
        );

    }

    if (!empty($userDataTo['android_push_token'])) {
        SendGCM(
            $userDataTo['android_push_token'],
            $message,
            $userDataFrom['value']['_id'],
            $toGroupData['_id'],
            'group',
            $userDataFrom['value']['name']
        );
    }

}

function sendDirectMessageNotification($fromUser, $toUser, $message)
{

    $url = HOST . "/" . DB . "/_design/app/_view/find_user_by_id?key=" . urlencode('"' . $toUser . '"');
    $return = doGet($url);

    $returnData = json_decode($return, true);
    if (isset($returnData['rows'][0])) {
        $userDataTo = $returnData['rows'][0];
    }


    $url = HOST . "/" . DB . "/_design/app/_view/find_user_by_id?key=" . urlencode('"' . $fromUser . '"');
    $return = doGet($url);

    $returnData = json_decode($return, true);
    if (isset($returnData['rows'][0])) {
        $userDataFrom = $returnData['rows'][0];
    }

    $message = getPushMessageForMessage($userDataFrom['value']['name'], true);

    if (!empty($userDataTo['value']['ios_push_token'])) {
        sendAPNProd($userDataTo['value']['ios_push_token'], $message, "", "", array('from' => $fromUser));
        sendAPNDev($userDataTo['value']['ios_push_token'], $message, "", "", array('from' => $fromUser));
    }

    if (!empty($userDataTo['value']['android_push_token'])) {
        SendGCM(
            $userDataTo['value']['android_push_token'],
            $message,
            $fromUser,
            "",
            "user",
            $userDataFrom['value']['name']
        );
    }
}

function getPushMessageForMessage($fromUserName, $encode = false)
{

    if ($encode) {
        return encodeForPush(sprintf(DIRECTMESSAGE_NOTIFICATION_MESSAGE, $fromUserName));
    } else {
        return sprintf(DIRECTMESSAGE_NOTIFICATION_MESSAGE, $fromUserName);
    }
}

function getPushMessageForGroup($fromUserName, $toGroupName, $encode = false)
{
    if ($encode) {
        return encodeForPush(sprintf(GROUPMESSAGE_NOTIFICATION_MESSAGE, $fromUserName, $toGroupName));
    } else {
        return sprintf(GROUPMESSAGE_NOTIFICATION_MESSAGE, $fromUserName, $toGroupName);
    }
}

function encodeForPush($str)
{
    $urlEncoded = urlencode($str);
    $plusToSpace = str_replace('+', ' ', $urlEncoded);
    return $plusToSpace;
}

function doGet($url)
{

    $process = curl_init($url);
    curl_setopt($process, CURLOPT_HEADER, 0);
    curl_setopt($process, CURLINFO_HEADER_OUT, true);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
    $return = curl_exec($process);
    $headerSent = curl_getinfo($process, CURLINFO_HEADER_OUT);
    curl_close($process);

    return $return;
}

function doDelete($url)
{

    $process = curl_init($url);
    curl_setopt($process, CURLOPT_HEADER, 0);
    curl_setopt($process, CURLINFO_HEADER_OUT, true);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($process, CURLOPT_CUSTOMREQUEST, "DELETE");
    $return = curl_exec($process);
    $headerSent = curl_getinfo($process, CURLINFO_HEADER_OUT);
    curl_close($process);

    return $return;
}


function doPOST($url, $data)
{

    $process = curl_init($url);
    curl_setopt($process, CURLOPT_HEADER, 0);
    curl_setopt($process, CURLINFO_HEADER_OUT, true);

    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($process, CURLOPT_POST, true);
    curl_setopt($process, CURLOPT_POSTFIELDS, json_encode($data));

    curl_setopt($process, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

    $return = curl_exec($process);
    $headerSent = curl_getinfo($process, CURLINFO_HEADER_OUT);

    curl_close($process);

    return $return;
}

define("APN_PRODUCTION_KEY", 1);
define("APN_DEVELOPMENT_KEY", 2);
define("GCM_KEY", 3);

function sendPushToQueue($serviceProvidor, $token, $payload)
{

    $receiverURL = PUSH_API;

    $postParam = array(
        's' => urlencode($serviceProvidor),
        't' => urlencode($token),
        'p' => urlencode($payload)
    );

    $fields_string = "";
    foreach ($postParam as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');

    $process = curl_init($receiverURL);
    curl_setopt($process, CURLOPT_HEADER, 0);
    curl_setopt($process, CURLOPT_TIMEOUT, 30);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($process, CURLOPT_POST, true);
    curl_setopt($process, CURLOPT_POST, count($postParam));
    curl_setopt($process, CURLOPT_POSTFIELDS, $fields_string);

    $return = curl_exec($process);
    curl_close($process);

    return true;

}


/* GET request */
function getRequest($url, $return = false)
{
    global $db_username, $db_password;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    $response = curl_exec($curl);

    if ($return) {
        return returnResponseBody($curl, $response);
    } else {
        printForAPI($curl, $response);
    }

}

/* POST request */
function postRequest($url, $reqBody, $return = false)
{

    global $db_username, $db_password;

    if (empty($reqBody)) {
        //die();
    }

    $reqBody = preg_replace("/,\"created\":[0-9]*?}/", ",\"created\":" . time() . "}", $reqBody);
    $reqBody = preg_replace("/,\"created\":[0-9]*?,/", ",\"created\":" . time() . ",", $reqBody);

    $reqBody = preg_replace("/,\"modified\":[0-9]*?}/", ",\"modified\":" . time() . "}", $reqBody);
    $reqBody = preg_replace("/,\"modified\":[0-9]*?,/", ",\"modified\":" . time() . ",", $reqBody);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $reqBody);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    $response = curl_exec($curl);

    $result = returnResponseBody($curl, $response);

    activateCreateUserHandler($result, $reqBody);

    echo $result;

}

/* PUT request */
function putRequest($url, $reqBody, $return = false)
{

    global $db_username, $db_password, $db_url;

    if (empty($reqBody)) {
        die();
    }

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt(
        $curl,
        CURLOPT_HTTPHEADER,
        array("Content-Type: application/json", 'Content-Length: ' . strlen($reqBody))
    );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $reqBody);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    $response = curl_exec($curl);

    if ($return) {
        return returnResponseBody($curl, $response);
    } else {
        printForAPI($curl, $response);
    }


}

/* DELETE request */
function deleteRequest($url, $return = false)
{

    global $db_username, $db_password, $db_url;

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    //curl_setopt($curl, CURLOPT_USERPWD, $db_username . ':' . $db_password);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    $response = curl_exec($curl);

    if ($return) {
        return returnResponseBody($curl, $response);
    } else {
        printForAPI($curl, $response);
    }

}

function returnResponseBody($curl, $response)
{

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    curl_close($curl);

    return $body;

}


function printForAPI($curl, $response)
{

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);

    $headers = explode("\r\n", $header);

    foreach ($headers as $row) {
        $row = trim($row);

        if (!empty($row)) {

            if (!preg_match("/Transfer-Encoding/", $row)) {
                header($row);
            }

        }

    }

    curl_close($curl);

    return stripParamsFromJson($body);

}

function randString($min = 5, $max = 8)
{
    $length = rand($min, $max);
    $string = '';
    $index = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < $length; $i++) {
        $string .= $index[rand(0, strlen($index) - 1)];
    }
    return $string;
}

?>