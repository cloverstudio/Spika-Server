<?php
namespace Spika\Db;

interface DbInterface
{
    public function createUser($userName,$password,$email);
    public function unregistToken($userId);
    public function checkEmailIsUnique($email);
    public function checkUserNameIsUnique($name);
    public function checkGroupNameIsUnique($name);
    public function doSpikaAuth($email,$password);
    public function saveUserToken($userJson, $id);
    public function findUserByToken($token);
    public function findUserById($id);
    public function findUserByEmail($email);
    public function findUserByName($name);
    public function getActivitySummary($user_id);
    public function updateUser($userId,$user);
    public function getEmoticons();
    public function getEmoticonImage($emoticonId);
    public function getAvatarFileId($user_id);
    public function getUserContacts($user_id,$include_docs);
    public function searchUserByName($name);
    public function searchUserByGender($gender);
    public function searchUserByAge($ageFrom,$ageTo);
    public function addContact($userId,$targetUserId);
    public function removeContact($userId,$targetUserId);
    public function addNewUserMessage($messageType,$fromUserId,$toUserId,$message,$additionalParams);
    public function addNewGroupMessage($messageType,$fromUserId,$toGroupId,$message,$additionalParams);
    public function getUserMessages($ownerUserId,$targetUserId,$count,$offset);
    public function getCommentCount($messageId);
    public function findMessageById($messageId);
	public function addNewComment($messageId,$userId,$comment);
	public function getComments($messageId,$count,$offset);
    public function getGroupMessages($targetGroupId,$count,$offset);
    public function findGroupById($id);
    public function findGroupByName($name);
    public function findGroupByCategoryId($categoryId);
    public function findGroupsByName($name);
    public function createGroup($name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL);
    public function updateGroup($groupId,$name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL);
    public function deleteGroup($groupId);
    public function subscribeGroup($groupId,$userId);
    public function unSubscribeGroup($groupId,$userId);
    public function watchGroup($groupId,$userId);
    public function unWatchGroup($userId);
    public function findAllGroupCategory();
	public function updateActivitySummaryByDirectMessage($toUserId, $fromUserId);
	public function updateActivitySummaryByGroupMessage($toUserId, $fromUserId);
	public function clearActivitySummary($toUserId, $type, $fieldKey);
	
    //public function addToContact($owserUserId,$tagetUserId);
    //public function removeFromContact($owserUserId,$tagetUserId);

    /**
     * Create a users
     *
     * @param  string $json
     * @return string $id
     */
    public function doPostRequest($requestBody);
    public function doGetRequestGetHeader($queryString, $stripCredentials = true);
    public function doGetRequest($queryString, $stripCredentials = true);
    public function doPutRequest($id, $requestBody);
    public function doDeleteRequest($id, $rev);
}
