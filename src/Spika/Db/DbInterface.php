<?php
namespace Spika\Db;

interface DbInterface
{
    public function unregistToken($userId);

    public function checkEmailIsUnique($email);

    public function checkUserNameIsUnique($name);

    public function checkGroupNameIsUnique($name);

    public function doSpikaAuth($requestBody);

    public function saveUserToken($userJson, $id);

    public function doPostRequest($requestBody);

    public function doGetRequestGetHeader($queryString, $stripCredentials = true);

    public function doGetRequest($queryString, $stripCredentials = true);

    public function doPutRequest($id, $requestBody);

    public function doDeleteRequest($id, $rev);
}
