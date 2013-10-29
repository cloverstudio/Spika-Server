<?php
namespace Spika\Db;

interface DbInterface
{
    public function unregistToken($userId);
    public function checkEmailIsUnique($email);
    public function checkUserNameIsUnique($name);
    public function checkGroupNameIsUnique($name);
    public function doSpikaAuth($email,$password);
    public function saveUserToken($userJson, $id);

    /**
     * Finds a user by User ID
     *
     * @param  string $id
     * @return array
     */
    public function findUserById($id);
    public function findUserByEmail($email);
    public function findUserByName($name);
    public function getActivitySummary($user_id);
    public function updateUser($user);
    public function getEmoticons();
    public function getCommentCount($messageId);
    public function getAvatarFileId($user_id);
    public function addNewMessage($messageData);
    public function getUserContacts($user_id,$include_docs);
    public function createGroup($groupData);

    /**
     * Create a users
     *
     * @param  string $json
     * @return string $id
     */
    public function createUser($userName,$password,$email);
    public function doPostRequest($requestBody);
    public function doGetRequestGetHeader($queryString, $stripCredentials = true);
    public function doGetRequest($queryString, $stripCredentials = true);
    public function doPutRequest($id, $requestBody);
    public function doDeleteRequest($id, $rev);
}
