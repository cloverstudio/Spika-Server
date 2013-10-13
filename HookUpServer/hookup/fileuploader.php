<?php

function generateRandomString($length = 20)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}


include("./lib/startup.php");

$paramName = "file";
$fineName = generateRandomString() . time();
$filePath = ROOT_DIR . DS . "files" . DS . $fineName;

if ($_FILES[$paramName]["error"] > 0) {
    echo "error";
} else {
    if (file_exists($filePath)) {
        echo "error";
    } else {
        move_uploaded_file($_FILES[$paramName]["tmp_name"], $filePath);
        echo $fineName;
    }
}

?>