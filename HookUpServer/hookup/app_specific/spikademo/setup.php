<?php

define("APP", "spikademo");

include("../../lib/startup.php");

$randomEmail = randString(8, 8) . '@clover-studio.com';
$password = randString(8, 8);

////////////////////////////////////////////////////////////////////////////////////////
/////////// Check instalattion
////////////////////////////////////////////////////////////////////////////////////////

$result = doGet(HOST . "/" . DB);
$resultDic = json_decode($result, true);

if ($resultDic['db_name'] != APP) {
    die("Database does not exist \n");
}

// Create demo user
$json = '{
	   "about": "Auto pilot user",
	   "password": "' . $password . '",
	   "favorite_groups": [],
	   "token": "testtesttest",
	   "type": "user",
	   "contacts": [],
	   "email": "' . $randomEmail . '",
	   "online_status": "online",
	   "birthday": 1377554400,
	   "token_timestamp": 1378467097,
	   "max_favorite_count": 10,
	   "gender": "female",
	   "name": "Create User Test",
	   "avatar_file_id": "",
	   "max_contact_count": 20,
	   "avatar_thumb_file_id": ""
	}';

$result = doPost(HOST . "/" . DB, json_decode($json));
$resultDic = json_decode($result, true);

if ($resultDic['ok'] != 'true') {
    die("Failed to create user. \n");
}

echo "User for auto generate content is generated. Please change createUserHander.php with this information.
AP_USER = '{$randomEmail}'
AP_PASS = '{$password}'
";

// check fetch user via HU_Client library
$loginUser = HU_login($randomEmail, $password);


if (!isset($loginUser['_id'])) {
    die("Failed to login. \n");
}

////////////////////////////////////////////////////////////////////////////////////////
/////////// Insert Emoticons
////////////////////////////////////////////////////////////////////////////////////////

// clear first
$result = doGet(HOST . "/" . DB . "/_design/app/_view/find_all_emoticons");
$resultData = json_decode($result, true);

foreach ($resultData['rows'] as $data) {

    $id = $data['value']['_id'];
    $rev = $data['value']['_rev'];


    $url = HOST . "/" . DB . "/" . $id . "?rev=" . $rev;

    $result = doDelete($url);

}

$files = array();
$filesPath = ROOT_DIR . DS . "app_specific" . DS . APP . DS . "resouces" . DS . "emoticons";

if ($handle = opendir($filesPath)) {

    while ($entry = readdir($handle)) {
        if (is_file($filesPath . DS . $entry)) {

            if (preg_match("/png/", $entry)) {
                $files[] = $filesPath . DS . $entry;
            }
        }
    }

    closedir($handle);
}

foreach ($files as $path) {


    $imgbinary = @file_get_contents($path);
    $base64EncodedImage = base64_encode($imgbinary);

    $pathinfo = pathinfo($path);

    $dataAry = array(
        'type' => 'master_emoticon',
        'identifier' => $pathinfo['filename'],
        '_attachments' => array(
            $pathinfo['basename'] => array(
                "content_type" => "image/png",
                "data" => $base64EncodedImage
            )
        )

    );

    $result = doPost(HOST . "/" . DB, $dataAry);

}

print "insert emotions finished.\n";


print "Spika DB setup completed.\n";
?>
