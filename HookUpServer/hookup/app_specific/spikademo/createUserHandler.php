<?php

$HU_USERID = "";
$HU_USERTOKEN = "";

define("AP_USER", "FgC6ONV2@clover-studio.com");
define("AP_PASS", "qa1AHz7h");

function activateCreateUserHandler($resultFromCouchDB, $originalData)
{

    $UserSupportName = "user support";
    $WelcomeMessage = "Hi! Thanks for using hookup. Here is direct hotline to user support. Please ask me everything.";

    $newbieGroupName = "New Users";
    $groupMessage = "There is a new user! Please say hello to him! hookup://user/";

    global $_SERVER, $reqBody, $HU_USERID, $HU_USERTOKEN;

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        return;
    }

    $headers = apache_request_headers();

    $id = $headers['user_id'];
    $token = $headers['token'];

    if ($id == "create_user") {

        $dic = json_decode($resultFromCouchDB, true);

        $newUserID = $dic['id'];

        // send message
        $loginUser = HU_login(AP_USER, AP_PASS);

        if (!isset($loginUser['_id'])) {
            die('login failed');
        }

        $HU_USERID = $loginUser['_id'];
        $HU_USERTOKEN = $loginUser['token'];

        $supportUser = HU_findUserByName($UserSupportName);
        $createdUser = HU_dbGetDocument($newUserID);

        $originalUser = json_decode($originalData, true);
        $createdUser['password'] = $originalUser['password'];

        HU_sendTextMessageToUser($supportUser, $createdUser, $WelcomeMessage);

        $group = HU_findGroupByName($newbieGroupName);
        $result = HU_subscribeGrupe($group, $createdUser);

        $result = HU_sendTextMessageToGroup($supportUser, $group, $groupMessage . urlencode($createdUser['name']));

    }

}

?>