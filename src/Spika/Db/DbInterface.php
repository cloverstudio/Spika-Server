<?php
namespace Spika\Db;

interface DbInterface
{
    public function createUser($userName,$password,$email);
    public function createUserDetail($userName,$password,$email,$about,$onlineStatus,$maxContacts,$maxFavorites,$birthday,$gender,$avatarFile,$thumbFile);
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
    public function updateUser($userId,$user,$secure);
    public function getEmoticons();
    public function getEmoticonImage($emoticonId);
    public function getAvatarFileId($user_id);
    public function searchUserByName($name);
    public function searchUserByGender($gender);
    public function searchUserByAge($ageFrom,$ageTo);
    public function searchUser($name,$agefrom,$ageTo,$gender);
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
    public function addPassworResetRequest($toUserId);
    public function getPassworResetRequest($requestCode);
    public function changePassword($userId,$newPassword);
    
    public function findUserCount();
    public function findAllUsersWithPaging($offect,$count);
    public function deleteUser($id);

    public function createGroupCategory($title,$picture);
    public function findAllGroupCategoryWithPaging($offect,$count);
    public function findGroupCategoryCount();
    public function findGroupCategoryById($id);
    public function updateGroupCategory($id,$title,$picture);
    public function deleteGroupCategory($id);
    
    public function createEmoticon($identifier,$picture);
    public function findAllEmoticonsWithPaging($offect,$count);
    public function findEmoticonCount();
    public function findEmoticonById($id);
    public function updateEmoticon($id,$title,$picture);
    public function deleteEmoticon($id);

    public function getMessageCount();
    public function getLastLoginedUsersCount();

    public function setMessageDelete($messageId,$deleteAt,$deleteAfterShownFlag);
    public function deleteMessage($messageId);
    
    public function getConversationHistory($user,$offset = 0,$count);
    public function getConversationHistoryCount($user);
    
}
