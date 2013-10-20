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

    /**
     * Finds a user by User ID
     *
     * @param  string $id
     * @return array
     */
    public function findUserById($id);

    /**
     * Create a users
     *
     * @param  string $json
     * @return string $id
     */
    public function createUser($userAry);

    public function doPostRequest($requestBody);

    public function doGetRequestGetHeader($queryString, $stripCredentials = true);

    public function doGetRequest($queryString, $stripCredentials = true);

    public function doPutRequest($id, $requestBody);

    public function doDeleteRequest($id, $rev);
}
