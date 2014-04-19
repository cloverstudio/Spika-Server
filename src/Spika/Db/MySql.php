<?php
/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Spika\Db;

use Spika\Db\DbInterface;
use Spika\Controller\FileController;

use Psr\Log\LoggerInterface;
use Guzzle\Http\Client;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Doctrine\DBAL\Connection;

class MySQL implements DbInterface

{
    private $logger;
    private $DB;


    public function __construct(LoggerInterface $logger,Connection $DB){
        $this->logger     = $logger;
        $this->DB         = $DB;
    }
    
    public function unregistToken($userId){
    
        $result = $this->DB->executeupdate(
                'update user set 
                    token = \'\',
                    modified = ?
                    WHERE _id = ?', 
                array(
                    time(),
                    $userId));

        return "OK";
        
    }
    
    public function checkEmailIsUnique($email){
        $user = $this->DB->fetchAssoc('select * from user where email = ?',array($email));
        return $user;
    }
    
    public function checkUserNameIsUnique($name){
        $user = $this->DB->fetchAssoc('select * from user where name = ?',array($name));
        return $user;
    }
    
    public function checkGroupNameIsUnique($name){        
        $group = $this->DB->fetchAssoc('select * from `group` where name = ?',array($name));
        return $group;
    }
    
    public function doSpikaAuth($email,$password)
    {
        $user = $this->DB->fetchAssoc('select * from user where email = ? and password = ?',array($email,$password));

        
        if (empty($user['_id'])) {
            $arr = array('message' => 'User not found!', 'error' => 'logout');
            return json_encode($arr);
        }
        
        if (empty($user['_id'])) {
            $arr = array('message' => 'Wrong password!', 'error' => 'logout');
            return json_encode($arr);
        }
        


        $token = \Spika\Utils::randString(40, 40);
        
        $this->DB->executeupdate('update user set token = ?,token_timestamp = ?,last_login = ? WHERE _id = ?', 
            array($token,time(),time(),$user['_id']));
        
        $newUser = $this->findUserById($user['_id'],false);

        return json_encode($newUser);

    }

    function saveUserToken($userJson, $id)
    {   
    }


    /**
     * Finds a user by Token
     *
     * @param  string $token
     * @return array
     */
    public function findUserByToken($token)
    {
        $user = $this->DB->fetchAssoc('select * from user where token = ?',array($token));
        return $user;
    }
    
    private function filterUserAry($userAry){
        

        for($i = 0 ; $i < count($userAry['rows']) ; $i++){
        
            // favorite groups force to be array
            if(isset($userAry['rows'][$i]['value']['favorite_groups'] )){
                $userAry['rows'][$i]['value']['favorite_groups'] = array_values($userAry['rows'][$i]['value']['favorite_groups']);
            }
            
            // contacts groups force to be array
            if(isset($userAry['rows'][$i]['value']['contacts'])){
                $userAry['rows'][$i]['value']['contacts'] = array_values($userAry['rows'][$i]['value']['contacts']);
            }
            
        }
        
        
        
        return $userAry;
        
    }
    
    private function filterUser($userAry){
        
        // favorite groups force to be array
        if(isset($userAry['favorite_groups'])){
            $userAry['favorite_groups'] = array_values($userAry['favorite_groups']);
        }
        
        // contacts groups force to be array
        if(isset($userAry['contacts'])){
            $userAry['contacts'] = array_values($userAry['contacts']);
        }
        
        return $userAry;
        
    }
    
     /**
     * Finds all users
     *
     * @return array
     */
    public function findAllUsers()
    {
        $result = $this->DB->fetchAll('select * from user');
        return $this->formatResult($result);
    }
    
    /**
     * Finds a user by User ID
     *
     * @param  string $id
     * @return array
     */
    public function findUserById($id,$deletePersonalInfo = true)
    {
        $user = $this->DB->fetchAssoc('select * from user where _id = ?',array($id));
        $contacts = $this->DB->fetchAll('select contact_user_id from user_contact where user_id = ?',array($id));
        $groups = $this->DB->fetchAll('select group_id from user_group where user_id = ?',array($id));
        
        $contactIds = array();      
        if(is_array($contacts)){
            foreach($contacts as $row){
                $contactIds[] = $row['contact_user_id'];
            }
        }
        
        $groupIds = array();    
        if(is_array($groups)){
            foreach($groups as $row){
                $groupIds[] = $row['group_id'];
            }
        }
                
        $user['contacts'] = $contactIds;
        $user['favorite_groups'] = $groupIds;
        
        $user = $this->reformatUserData($user,$deletePersonalInfo);
                
        return $user;
        
    }

    /**
     * Finds a users by User ID array
     *
     * @param  string $id
     * @return array
     */
    public function findUsersById($ids)
    {
        
        $usersAry = array();
        
        foreach($ids as $row => $id){
            $ids[$row] = intval($id);
        }
        
        $idsStr = implode(',',$ids);

        $result = $this->DB->fetchAll('select * from user where _id in (' . $idsStr . ')');

        foreach($result as $user){
            $user = $this->reformatUserData($user,true);   
            $usersAry[] = $user;
        } 
           
        return $usersAry;
        
    }


    /**
     * Finds a user by email
     *
     * @param  string $email
     * @return array
     */
    public function findUserByEmail($email)
    {
        $user = $this->DB->fetchAssoc('select * from user where email = ?',array($email));
        $user = $this->reformatUserData($user);
        return $user;
    }
    
    /**
     * Finds a user by name
     *
     * @param  string $name
     * @return array
     */
    public function findUserByName($name)
    {
        $user = $this->DB->fetchAssoc('select * from user where LOWER(name) = LOWER(?)',array($name));
        $user = $this->reformatUserData($user);
        return $user;
    }

    /** Sreach user
     *
     * @param  string $name
     * @return array
     */
    public function searchUser($name = "",$agefrom = 0,$ageTo = 0,$gender = ""){
        
        $query = "select * from user where 1 = 1 ";
        $yearIntervalInSec = 60 * 60 * 24 * 365;
        
        //calc birthday range ( can be better )
        $toDate = time() - $yearIntervalInSec * $agefrom;
        
        if(!empty($name)){
            $query .= " and LOWER(name) like :name "; 
        }
        
        if(!empty($gender)){
            $query .= " and gender = :gender "; 
        }
        
        if($agefrom != 0){
            $query .= " and birthday < :birthdayTo "; 
        }
        
        if($ageTo != 0){
            $query .= " and birthday > :birthdayfrom "; 
        }
        
        $stmt = $this->DB->prepare($query);

        if(!empty($name)){
            $name = strtolower($name);
            $stmt->bindValue("name", "%{$name}%");
        }
        
        if(!empty($gender)){
            $stmt->bindValue("gender", $gender);
        }
        
        if($agefrom != 0){
            $toDate = time() - $yearIntervalInSec * $agefrom;
            $stmt->bindValue("birthdayTo", $toDate);
        }
        
        if($ageTo != 0){
            $fromDate = time() - $yearIntervalInSec * $ageTo;
            $stmt->bindValue("birthdayfrom", $fromDate);
        }
        
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        $formatedUsers = array();
        
        foreach($result as $row){
            $formatedUsers[] = $this->reformatUserData($row);
        }
        
        return $formatedUsers;
    }
        
    /**
     * Search a user by name
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByName($name){
        $result = $this->DB->fetchAll('select * from user where name like ?',array("%{$name}%"));
        return $this->formatResult($result);
    }
    
    /**
     * Search a user by gender
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByGender($gender){
        $result = $this->DB->fetchAll('select * from user where gender = ?',array($gender));
        return $this->formatResult($result);
    }
    
    /**
     * Search a user by age
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByAge($agefrom,$ageTo){
        
        $yearIntervalInSec = 60 * 60 * 24 * 365;
        
        //calc birthday range ( can be better )
        $fromDate = time() - $yearIntervalInSec * $ageTo;
        $toDate = time() - $yearIntervalInSec * $agefrom;

        $result = $this->DB->fetchAll('select * from user where birthday > ? and birthday < ? ',array($fromDate,$toDate));
        
        return $this->formatResult($result);
    }

    /**
     * Gets user activity summary
     *
     * @param  string $user_id
     * @return array
     */
    public function getActivitySummary($user_id)
    {

        $myNotifications = $this->DB->fetchAll('
            select 
                notification.*,
                user.avatar_thumb_file_id,
                user.name
            from notification  
                left join user on notification.from_user_id = user._id
            where user_id = ?
            order by modified desc',array($user_id));
                
        $directMessages = array();
        $groupMessages = array();
        
        foreach($myNotifications as $row){
            
            if($row['target_type'] == ACTIVITY_SUMMARY_DIRECT_MESSAGE)
                $directMessages[] = $row;
            
            if($row['target_type'] == ACTIVITY_SUMMARY_GROUP_MESSAGE)
                $groupMessages[] = $row;
            
            
        }
        
        $responseData = array(
            'total_rows' => count($myNotifications),
            'offset' => 0,
            'rows' => array(
                array(
                    'id' => $user_id,
                    'key' => $user_id,
                    'value' => array(
                        
                        '_id' => $user_id,
                        '_rev'  => 'tmprev',
                        'type'  => 'activity_summary' ,
                        'user_id'  => $user_id
                    )
                )
            )
        );
        
        if(count($directMessages) > 0){
            
            $responseData['rows'][0]['value']['recent_activity'][ACTIVITY_SUMMARY_DIRECT_MESSAGE] = array(
                'name' => 'Chat activity',
                'target_type' => 'user',
                'notifications' => array()
            );
            
            foreach($directMessages as $row){
                
                $responseData['rows'][0]['value']['recent_activity'][ACTIVITY_SUMMARY_DIRECT_MESSAGE]['notifications'][] = array(
                    
                    'target_id' => $row['from_user_id'],
                    'count' => $row['count'],
                    'messages' => array(array(
                        'from_user_id' => $row['from_user_id'],
                        'avatar_thumb_file_id' => $row['avatar_thumb_file_id'],
                        'message' => $row['message'],
                        'user_image_url' => $row['user_image_url'],
                        'modified' => intval($row['modified'])
                    )),
                    'lastupdate' => intval($row['modified'])
                                    
                );
            }
        }
        
        if(count($groupMessages) > 0){
            
            $responseData['rows'][0]['value']['recent_activity'][ACTIVITY_SUMMARY_GROUP_MESSAGE] = array(
                'name' => 'Groups activity',
                'target_type' => 'group',
                'notifications' => array()
            );
            
            foreach($groupMessages as $row){
                
                $responseData['rows'][0]['value']['recent_activity'][ACTIVITY_SUMMARY_GROUP_MESSAGE]['notifications'][] = array(
                    
                    'target_id' => $row['to_group_id'],
                    'count' => $row['count'],
                    'messages' => array(array(
                        'from_user_id' => $row['from_user_id'],
                        'avatar_thumb_file_id' => $row['avatar_thumb_file_id'],
                        'message' => $row['message'],
                        'user_image_url' => $row['user_image_url'],
                        'modified' => intval($row['modified'])
                    )),
                    'lastupdate' => intval($row['modified'])
                                    
                );
            }
        }

        return $responseData;
        
    }


    /**
     * create a user
     *
     * @param  string $json
     * @return id
     */
    public function createUser($userName,$email,$password)
    {
        
        $now = time();
 
        $valueArray = array();
        $valueArray['name'] = $userName;
        $valueArray['email'] = $email;
        $valueArray['password'] = $password;
        $valueArray['online_status'] = "online";
        $valueArray['max_contact_count'] = 20;
        $valueArray['max_favorite_count'] = 10;
        $valueArray['birthday'] = 0;
        $valueArray['created'] = $now;
        $valueArray['modified'] = $now;
        
        if($this->DB->insert('user',$valueArray)){
            return $this->DB->lastInsertId("_id");
        }else{
            return null;
        }
        
     }

    /**
     * create a user with detailed params
     *
     * @param  string $json
     * @return id
     */
    public function createUserDetail($userName,$password,$email,$about,$onlineStatus,$maxContacts,$maxFavorites,$birthday,$gender,$avatarFile,$thumbFile)
    {
        
        $now = time();
 
        $valueArray = array();
        $valueArray['name'] = $userName;
        $valueArray['email'] = $email;
        $valueArray['password'] = $password;
        $valueArray['about'] = $about;
        $valueArray['online_status'] =$onlineStatus;
        $valueArray['max_contact_count'] = $maxContacts;
        $valueArray['max_favorite_count'] = $maxFavorites;
        $valueArray['birthday'] = $birthday;
        $valueArray['gender'] = $gender;
        $valueArray['avatar_file_id'] = $avatarFile;
        $valueArray['avatar_thumb_file_id'] = $thumbFile;
        $valueArray['created'] = $now;
        $valueArray['modified'] = $now;
        
        if($this->DB->insert('user',$valueArray)){
            return $this->DB->lastInsertId("_id");
        }else{
            return null;
        }
        
     }

    public function updateUser($userId,$user,$secure = true){

        $originalData = $this->findUserById($userId,false);
        
        $now = time();
        
        if(!isset($user['name']))
            $user['name'] = $originalData['name'];

        if(!$secure){
        
            if(!isset($user['email']))
                $user['email'] = $originalData['email'];
                
            if(!isset($user['password']))
                $user['password'] = $originalData['password'];
                
        }
                    
        if(!isset($user['about']))
            $user['about'] = $originalData['about'];
            
        if(!isset($user['online_status']))
            $user['online_status'] = $originalData['online_status'];
            
        if(!isset($user['birthday']))
            $user['birthday'] = $originalData['birthday'];
            
        if(!isset($user['gender']))
            $user['gender'] = $originalData['gender'];
            
        if(!isset($user['avatar_file_id']))
            $user['avatar_file_id'] = $originalData['avatar_file_id'];
            
        if(!isset($user['avatar_thumb_file_id']))
            $user['avatar_thumb_file_id'] = $originalData['avatar_thumb_file_id'];
        
        if(!isset($user['ios_push_token']))
            $user['ios_push_token'] = $originalData['ios_push_token'];
        
        if(!isset($user['android_push_token']))
            $user['android_push_token'] = $originalData['android_push_token'];
        
        if(!isset($user['max_contact_count']))
            $user['max_contact_count'] = $originalData['max_contact_count'];
        
        if(!isset($user['max_favorite_count']))
            $user['max_favorite_count'] = $originalData['max_favorite_count'];
        
        if(!isset($user['token']))
            $user['token'] = $originalData['token'];
        
        if($secure){
        
            $result = $this->DB->executeupdate(
                'update user set 
                    name = ?,
                    about = ?,
                    online_status = ?,
                    birthday = ?,
                    gender = ?,
                    avatar_file_id = ?,
                    avatar_thumb_file_id = ?,
                    ios_push_token = ?,
                    android_push_token = ?,
                    max_contact_count = ?,
                    max_favorite_count = ?,
                    token = ?,
                    modified = ?
                    WHERE _id = ?', 
                array(
                    $user['name'],
                    $user['about'],
                    $user['online_status'],
                    $user['birthday'],
                    $user['gender'],
                    $user['avatar_file_id'],
                    $user['avatar_thumb_file_id'],
                    $user['ios_push_token'],
                    $user['android_push_token'],
                    $user['max_contact_count'],
                    $user['max_favorite_count'],
                    $user['token'],
                    $now,
                    $userId));
        }else{
            
            $result = $this->DB->executeupdate(
                'update user set 
                    name = ?,
                    email = ?,
                    password = ?,
                    about = ?,
                    online_status = ?,
                    birthday = ?,
                    gender = ?,
                    avatar_file_id = ?,
                    avatar_thumb_file_id = ?,
                    ios_push_token = ?,
                    android_push_token = ?,
                    max_contact_count = ?,
                    max_favorite_count = ?,
                    token = ?,
                    modified = ?
                    WHERE _id = ?', 
                array(
                    $user['name'],
                    $user['email'],
                    $user['password'],
                    $user['about'],
                    $user['online_status'],
                    $user['birthday'],
                    $user['gender'],
                    $user['avatar_file_id'],
                    $user['avatar_thumb_file_id'],
                    $user['ios_push_token'],
                    $user['android_push_token'],
                    $user['max_contact_count'],
                    $user['max_favorite_count'],
                    $user['token'],
                    $now,
                    $userId));
                    
        }

        if($result){
            return $this->findUserById($userId,false);
        }else
            $arr = array('message' => 'update user error!', 'error' => 'logout');
            return json_encode($arr);
    }
    
    public function getUserById($userId){
        return $this->findUserById($userId);
    }

    public function addNewUserMessage($addNewMessage = 'text',$fromUserId,$toUserId,$message,$additionalParams=array()){
        
        $this->logger->addDebug(" write from : {$fromUserId}, to : {$toUserId}");


        $messageData = array();
        
        $messageData['from_user_id']=$fromUserId;
        $messageData['to_user_id']=$toUserId;
        $messageData['body']=$message;
        $messageData['modified']=time();
        $messageData['created']=time();
        $messageData['message_target_type']='user';
        $messageData['message_type']=$addNewMessage;
        $messageData['valid']=true;

        if(is_array($additionalParams)){
            foreach($additionalParams as $key => $value){
                $messageData[$key]=$value;
            }
        }
        
        if(isset($fromUserId)){
            $fromUserData=$this->findUserById($fromUserId);
            $messageData['from_user_name']=$fromUserData['name'];
        }else{
            return null;
        }

        if(isset($toUserId)){
            $toUserData=$this->findUserById($toUserId);
            $messageData['to_user_name']=$toUserData['name'];
        }else{
            return null;
        }
        
        if($this->DB->insert('message',$messageData)){
        
            $couchDBCompatibleResponse = array(
                'ok' => true,
                'id' => $this->DB->lastInsertId("_id"),
                'rev' => 'tmprev'
            );

            return $couchDBCompatibleResponse;
            
        }else{
            return null;
        }
        
    }

    public function addNewGroupMessage($addNewMessage = 'text',$fromUserId,$toGroupId,$message,$additionalParams=array()){
        
        $messageData = array();
        
        $messageData['from_user_id']=$fromUserId;
        $messageData['to_group_id']=$toGroupId;
        $messageData['body']=$message;
        $messageData['modified']=time();
        $messageData['created']=time();
        $messageData['message_target_type']='group';
        $messageData['message_type']=$addNewMessage;
        $messageData['valid']=true;

        if(is_array($additionalParams)){
            foreach($additionalParams as $key => $value){
                $messageData[$key]=$value;
            }
        }
        
        if(isset($fromUserId)){
            $fromUserData=$this->findUserById($fromUserId);
            $messageData['from_user_name']=$fromUserData['name'];
        }else{
            return null;
        }

        if(isset($toGroupId)){
            $toGroupData=$this->findGroupById($toGroupId);
            $messageData['to_group_name']=$toGroupData['name'];
        }else{
            return null;
        }
                
        if($this->DB->insert('message',$messageData)){
        
            $couchDBCompatibleResponse = array(
                'ok' => true,
                'id' => $this->DB->lastInsertId("_id"),
                'rev' => 'tmprev'
            );

            return $couchDBCompatibleResponse;
            
        }else{
            return null;
        }
    }


    public function getUserMessages($ownerUserId,$targetUserId,$count,$offset){
    
        $this->logger->addDebug(" read from : {$ownerUserId}, to : {$targetUserId}");

        $result = $this->DB->fetchAll("
            select 
                message._id,
                message.from_user_id,
                message.to_user_id,
                message.to_group_id,
                message.to_group_name,
                message.body,
                message.message_target_type,
                message.message_type,
                message.emoticon_image_url,
                message.picture_file_id,
                message.picture_thumb_file_id,
                message.voice_file_id, 
                message.video_file_id,
                message.longitude,
                message.latitude,
                message.valid,
                message.from_user_name,
                message.to_user_name,
                message.created as created,
                message.modified,
                message.delete_type,
                message.delete_at,
                message.delete_flagged_at,
                message.delete_after_shown,
                message.read_at,
                message.comment_count,
                user.avatar_thumb_file_id as avatar_thumb_file_id
            from message
                left join user on user._id = message.from_user_id
                where 
                    message_target_type = ? 
                    and from_user_id = ?
                    and to_user_id = ?
                
            union
            
            select
                message._id,
                message.from_user_id,
                message.to_user_id,
                message.to_group_id,
                message.to_group_name,
                message.body,
                message.message_target_type,
                message.message_type,
                message.emoticon_image_url,
                message.picture_file_id,
                message.picture_thumb_file_id,
                message.voice_file_id, 
                message.video_file_id,
                message.longitude,
                message.latitude,
                message.valid,
                message.from_user_name,
                message.to_user_name,
                message.created as created,
                message.modified,
                message.delete_type,
                message.delete_at,
                message.delete_flagged_at,
                message.delete_after_shown,
                message.read_at,
                message.comment_count,
                user.avatar_thumb_file_id as avatar_thumb_file_id
            from message 
                left join user on user._id = message.from_user_id
                where 
                    message_target_type = ? 
                    and to_user_id = ?
                    and from_user_id = ?
            order by created desc
            limit {$count}
            offset {$offset}",
            array(
                'user',
                $ownerUserId,
                $targetUserId,
                'user',
                $ownerUserId,
                $targetUserId));

        
        $formatedMessages = array();
        
        foreach($result as $message){
            $message = $this->reformatMessageData($message);
            $formatedMessages[] = $message;
        }
        
        return $this->formatResult($formatedMessages,$offset);
        
    }

    public function getGroupMessages($targetGroupId,$count,$offset){
        
        $result = $this->DB->fetchAll("
            select 
                message._id,
                message.from_user_id,
                message.to_user_id,
                message.to_group_id,
                message.to_group_name,
                message.body,
                message.message_target_type,
                message.message_type,
                message.emoticon_image_url,
                message.picture_file_id,
                message.picture_thumb_file_id,
                message.voice_file_id, 
                message.video_file_id,
                message.longitude,
                message.latitude,
                message.valid,
                message.from_user_name,
                message.to_user_name,
                message.created as created,
                message.modified,
                message.delete_type,
                message.delete_at,
                message.delete_flagged_at,
                message.delete_after_shown,
                message.read_at,
                message.comment_count,
                user.avatar_thumb_file_id as avatar_thumb_file_id
            from message 
                left join user on user._id = message.from_user_id
                where 
                    message_target_type = ? 
                    and to_group_id = ?
                order by created desc
                limit {$count}
                offset {$offset}
            ",
            array(
                'group',
                $targetGroupId));

        $formatedMessages = array();
        
        foreach($result as $message){
            $message = $this->reformatMessageData($message);
            $formatedMessages[] = $message;
        }

        return $this->formatResult($formatedMessages,$offset);

    }
    
    public function addContact($userId,$targetUserId){
        
        $valueArray = array();
        $valueArray['user_id'] = $userId;
        $valueArray['contact_user_id'] = $targetUserId;
        $valueArray['created'] = time();
        

        if($this->DB->insert('user_contact',$valueArray)){
            return true;
        }else{
            return false;
        }
                
        return true;
    }
    
    public function removeContact($userId,$targetUserId){
        
        $contact = $this->DB->fetchAssoc('select _id from user_contact where user_id = ? and contact_user_id = ?',
                                        array($userId,$targetUserId));
        
        $this->DB->delete('user_contact', array('_id' => $contact['_id']));

        return true;
        
    }
    
    public function getEmoticons(){
        $result = $this->DB->fetchAll("select * from emoticon");
        return $this->formatResult($result,0);
    }

    public function getEmoticonByIdentifier($identifier){
        $result = $this->DB->fetchAssoc("select * from emoticon where identifier = ?",array($identifier));
        return $result;
    }

    public function getEmoticonImage($emoticonId){
        
        $fileDir = __DIR__.'/../../../'.FileController::$fileDirName;
        
        $emoticon = $this->DB->fetchAssoc('select file_id from emoticon where _id = ?',
                                        array($emoticonId));
                                        
        $filePath = $fileDir . "/" . $emoticon['file_id'];
        
        return file_get_contents($filePath);
        
    }

    public function getEmoticonById($emoticonId){
        
        $fileDir = __DIR__.'/../../../'.FileController::$fileDirName;
        
        $emoticon = $this->DB->fetchAssoc('select file_id from emoticon where _id = ?',
                                        array($emoticonId));
                                        
        return $emoticon;
        
    }

    public function getCommentCount($messageId){
        $count = $this->DB->fetchAssoc('select count(*) as count from media_comment where message_id = ?',array($messageId));
        return array('rows'=>array(array('key'=>"",'value'=>$count['count'])));
    }

    public function findMessageById($messageId){
        $message = $this->DB->fetchAssoc('select * from message where _id = ?',array($messageId));
        $message = $this->reformatMessageData($message);

        return $this->formatRow($message);
    }
    
    public function addNewComment($messageId,$userId,$comment){
        
        $userData=$this->findUserById($userId);
        
        $commentData = array();
        $commentData['message_id'] = $messageId;
        $commentData['user_id'] = $userId;
        $commentData['comment'] = $comment;
        $commentData['user_name'] = $userData['name'];
        $commentData['created'] = time();
        
        if($this->DB->insert('media_comment',$commentData)){
            
            $result = $this->DB->executeupdate(
                'update message set 
                    comment_count = comment_count + 1
                    where _id = ?',
                array(
                    $messageId));
            
            return array(
                'ok' => 1,
                'id' => $this->DB->lastInsertId("_id")
            );
            
        }else{
            return null;
        }
        
        
    }

    public function getComments($messageId,$count,$offset){
        
        $result = $this->DB->fetchAll("
            select media_comment.*,user.avatar_thumb_file_id from media_comment 
            left join user on user._id = media_comment.user_id
                where 
                    message_id = ? 
                order by created desc
                limit {$count}
                offset {$offset}
            ",
            array($messageId));

        $formatedComments = array();
        
        foreach($result as $comment){
            $comment = $this->reformatCommentData($comment);
            $formatedComments[] = $comment;
        }

        return $this->formatResult($formatedComments,$offset);
        
    }

    public function getAvatarFileId($user_id){
        $user = $this->findUserById($user_id);
        if(!isset($user['_id']))
            return array('rows'=>array(array('key'=>"",'value'=>"")));
        else
            return array('rows'=>array(array('key'=>"",'value'=>$user['avatar_file_id'])));
        
    }

    public function createGroup($name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL){

        
        $groupData = array(
            'name' => $name,
            'group_password' => $password,
            'category_id' => $categoryId,
            'description' => $description,
            'user_id' => $ownerId,
            'avatar_file_id' => $avatarURL,
            'avatar_thumb_file_id' => $thumbURL,
            'created' => time(),
            'modified' => time()
        );
    
        if($this->DB->insert('`group`',$groupData)){
            return array(
                'ok' => 1,
                'id' => $this->DB->lastInsertId("_id")
            );
        }else{
            return null;
        }

    }

    public function updateGroup($groupId,$name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL){

        $now = time();

        $result = $this->DB->executeupdate(
                'update `group` set 
                    name = ?,
                    user_id = ?,
                    description = ?,
                    group_password = ?,
                    category_id = ?,
                    avatar_file_id = ?,
                    avatar_thumb_file_id = ?,
                    modified = ?
                    WHERE _id = ?', 
                array(
                    $name,
                    $ownerId,
                    $description,
                    $password,
                    $categoryId,
                    $avatarURL,
                    $thumbURL,
                    time(),
                    $groupId));

        if($result){
            return array(
                'ok' => 1,
                'id' => $groupId,
                'rev' => 'tmprev'
            );
        }else{
            $arr = array('message' => 'update group error!', 'error' => 'logout');
            return json_encode($arr);;
        }
        
        return null;
        
    }

    public function deleteGroup($groupId){

        $this->DB->delete('`group`', array('_id' => $groupId));
        
        return array(
                'ok' => 1,
                'id' => $groupId,
                'rev' => 'tmprev' 
        );
    }

    public function findGroupById($id)
    {
        $group = $this->DB->fetchAssoc('select * from `group` where _id = ?',array($id));
        $group = $this->reformatGroupData($group);
        
        // find group cateogory
        $groupCategory = $this->DB->fetchAssoc('select * from group_category where _id = ?',array($group['category_id']));
        $group['category_name'] = $groupCategory['title'];
        
        return $this->formatRow($group);
    }

    public function findGroupsById($ids)
    {

        $groupsAry = array();
        
        foreach($ids as $row => $id){
            $ids[$row] = intval($id);
        }
        
        $idsStr = implode(',',$ids);

        $result = $this->DB->fetchAll('select * from `group` where _id in (' . $idsStr . ')');

        foreach($result as $group){
            $group = $this->formatRow($group,true);   
            $groupsAry[] = $group;
        } 
           
        return $groupsAry;

    }

    public function findGroupByName($name)
    {
        $name = strtolower($name);
        $group = $this->DB->fetchAssoc('select * from `group` where LOWER(name) = ?',array($name));
        
        if(isset($group['_id']))
            $group = $this->reformatGroupData($group);
            
        return $this->formatRow($group);
    }
    
    public function findGroupByCategoryId($categoryId)
    {
        $result = $this->DB->fetchAll('select * from `group` where category_id = ?',array($categoryId));
        

        $formatedGroups = array();
        foreach($result as $group){
            $group = $this->reformatGroupData($group);
            $formatedGroups[] = $group;
        }
        
        return $this->formatResult($formatedGroups);
    }
    
   public function findAllGroups($offset = 0,$count=0)
    {
    
        $result = $this->DB->fetchAll("select * from `group` order by _id limit {$count} offset {$offset}");
        
        $formatedGroups = array();
        foreach($result as $group){
            $group = $this->reformatGroupData($group);
            $formatedGroups[] = $group;
        }
        
        return $formatedGroups;

    }
    
   public function findGroupCount()
    {
        $query = "select count(*) as count from `group`";
        
        $result = $this->DB->fetchColumn($query);

        return $result;
    }
    
    public function findGroupsByName($name)
    {
        $result = $this->DB->fetchAll('select * from `group` where LOWER(name) like LOWER(?)',array("%{$name}%"));
        
        $formatedGroups = array();
        foreach($result as $group){
            $group = $this->reformatGroupData($group);
            $formatedGroups[] = $group;
        }
        
        return $formatedGroups;
    }

    public function findAllGroupCategory(){
        
        $result = $this->DB->fetchAll('select * from group_category');
        return $this->formatResult($result);

    }

    public function subscribeGroup($groupId,$userId){
        
        $valueArray = array();
        $valueArray['user_id'] = $userId;
        $valueArray['group_id'] = $groupId;
        $valueArray['created'] = time();
        
        if($this->DB->insert('user_group',$valueArray)){
            return true;
        }else{
            return false;
        }
                
        return true;

    }
    
    public function unSubscribeGroup($groupId,$userId){

        $contact = $this->DB->fetchAssoc('select _id from user_group where user_id = ? and group_id = ?',
                                        array($userId,$groupId));
        
        $this->DB->delete('user_group', array('_id' => $contact['_id']));

        return true;
        
    }
    
    
    public function watchGroup($groupId,$userId){
        
        return true;
    }
    
    public function unWatchGroup($userId){

        return true;
        
    }

    function updateActivitySummaryByDirectMessage($toUserId, $fromUserId)
    {

        $type = ACTIVITY_SUMMARY_DIRECT_MESSAGE;
        $fromUserData = $this->findUserById($fromUserId);
        $message = sprintf(DIRECTMESSAGE_NOTIFICATION_MESSAGE,$fromUserData['name']);
        
        
        // check row exists
        $notificationRow = $this->DB->fetchAssoc('select _id from notification where target_type = \'' . $type . '\' and user_id = ? and from_user_id = ?',
                                        array($toUserId,$fromUserId));
        if(!$notificationRow){
            
            $data = array(
                'user_id' => $toUserId,
                'from_user_id' => $fromUserId,
                'count' => 1,
                'target_type' => $type,
                'message' => $message,
                'created' => time(),
                'modified' => time()
            );
            
            $this->DB->insert('notification',$data);
            
        }else{
            
            $this->DB->executeupdate(
                    'update notification set 
                        count = count + 1,
                        modified = ?
                        WHERE _id = ?', 
                    array(
                        time(),
                        $notificationRow['_id']));   
            
            
        }
        
    }
    
    function updateActivitySummaryByGroupMessage($toGroupId, $fromUserId)
    {
        
        $type = ACTIVITY_SUMMARY_GROUP_MESSAGE;
        $fromUserData = $this->findUserById($fromUserId);
        $toGroupData = $this->findGroupById($toGroupId);
        $message = sprintf(GROUPMESSAGE_NOTIFICATION_MESSAGE,$fromUserData['name'],$toGroupData['name']);
        
        $users = $this->DB->fetchAll('select user_id from user_group where group_id = ?',
                                        array($toGroupId));
        
        foreach($users as $row){
            
            $toUserId = $row['user_id'];

            $notificationRow = $this->DB->fetchAssoc('
                select _id from notification where target_type = \'' . $type . '\' and user_id = ? and to_group_id = ? and from_user_id = ?',
                array($toUserId,$toGroupId,$fromUserId));
            
            if(!$notificationRow){
                
                $data = array(
                    'user_id' => $toUserId,
                    'from_user_id' => $fromUserId,
                    'to_group_id' => $toGroupId,
                    'count' => 1,
                    'target_type' => $type,
                    'message' => $message,
                    'created' => time(),
                    'modified' => time()
                );
                
                $this->DB->insert('notification',$data);
                
            }else{
                
                $this->DB->executeupdate(
                        'update notification set 
                            count = count + 1,
                            modified = ?
                            WHERE _id = ?', 
                        array(
                            time(),
                            $notificationRow['_id']));   
                
                
            }
            
        }
           
    }
    
    function clearActivitySummary($toUser, $type, $fieldKey)
    {
        global $db_url;
        
        if($type == ACTIVITY_SUMMARY_DIRECT_MESSAGE){
            
            $fromUserId = $fieldKey;
            
            $this->DB->executeUpdate(
                    'update notification 
                        set count = 0
                        where 
                        user_id = ?
                        and from_user_id = ?
                        and target_type = ?
                    ',
                    array(
                        $toUser,
                        $fromUserId,
                        $type));
            
        }
        
        
        if($type == ACTIVITY_SUMMARY_GROUP_MESSAGE){
            
            $toGroupId = $fieldKey;
            
            $this->DB->executeUpdate(
                    'update notification 
                        set count = 0
                        where 
                        user_id = ?
                        and to_group_id = ?
                        and target_type = ?
                    ',
                    array(
                        $toUser,
                        $toGroupId,
                        $type));
            
        }   
        
        $this->logger->adddebug("touser : {$toUser} , type : {$type} , fieldkey : {$fieldKey}");
    
    }

    public function addPassworResetRequest($toUserId){
        
        $token = \Spika\Utils::randString(40, 40);
        
        $data = array(
            'user_id' => $toUserId,
            'created' => time(),
            'token' => $token,
            'valid' => 1
        );
        
        $this->logger->addDebug(print_r($data,true));
        
        if($this->DB->insert('password_change_request',$data)){
            return $token;
        }else{
            return null;
        }
        
    }
    
    public function getPassworResetRequest($requestCode){

        $resetRequest = $this->DB->fetchAssoc('
            select * from password_change_request where token = ?',
            array($requestCode));
                
        return $resetRequest;
    }
    
    public function changePassword($userId,$newPassword){

            $this->DB->executeUpdate('update user set password = ?,token=\'\' where _id = ?',
                    array($newPassword,$userId));
                    
            $this->DB->executeUpdate('update password_change_request set valid = 0 where user_id = ?',
                    array($userId));
        
    }
    
    private function sendRequest($method,$URL,$postBody = ""){
    
        $client = new Client();
        $request = null;
        
        if($method == "POST"){
            $request = $client->post($URL);
        }
        else if($method == "PUT")
            $request = $client->put($URL);
            
        else if($method == "DELETE")
            $request = $client->delete($URL);

        else
            $request = $client->get($URL);
            
        if($method == "POST" || $method == "PUT" || $method == "DELETE"){
            if(!empty($postBody))
                $request->setBody($postBody,'application/json');

        }
        
        $response = $request->send();
    
        return array($response->getHeaderLines(),$response->getBody());
        
    }

    /**
     * Format simple row array to couchdb compatible row
     *
     * @param  string $result
     * @return array
     */
    public function formatRow($row){
        $row['_rev'] = "";
        return $row;
    }


    /**
     * Format simple array to couchdb compatible result
     *
     * @param  string $result
     * @return array
     */
    public function formatResult($result,$offset = 0){
    
        $newResultRows = array();
        
        foreach($result as $row){
            
            $newResultRows[] = array(
                'id' => $row['_id'],
                'key' =>  $row['_id'],
                'value' => $row
            );
                
        }
        
        return array(
            'total_rows' => count($result),
            'offset' => $offset,
            'rows'  => $newResultRows
        );
    }
 
    public function reformatUserData($user,$deletePersonalInfo = true){

        if($deletePersonalInfo){
            unset($user['password']);
            unset($user['email']);
            unset($user['token']);
        }

        if(isset($user['birthday']))
            $user['birthday'] = intval($user['birthday']);

        if(isset($user['last_login']))
            $user['last_login'] = intval($user['last_login']);

        if(isset($user['max_contact_count']))
            $user['max_contact_count'] = intval($user['max_contact_count']);

        if(isset($user['max_favorite_count']))
            $user['max_favorite_count'] = intval($user['max_favorite_count']);

        if(isset($user['created']))
            $user['created'] = intval($user['created']);

        if(isset($user['modified']))
            $user['modified'] = intval($user['modified']);

        $user['type'] = 'user';
        $user['_rev'] = 'tmprev';
        
        return $user;
    }
    
    public function reformatMessageData($message){

        $message['created'] = intval($message['created']);
        $message['modified'] = intval($message['modified']);
        $message['type'] = 'message';

        return $message;
    }
    
    public function reformatCommentData($comment){

        $comment['created'] = intval($comment['created']);
        $comment['type'] = 'comment';

        return $comment;
    }
    
    public function reformatGroupData($gourp){

        if(isset($gourp['created']))
            $gourp['created'] = intval($gourp['created']);
        
        if(isset($gourp['modified']))
            $gourp['modified'] = intval($gourp['modified']);
        
        $gourp['type'] = 'group';

        return $gourp;
    }
    
   public function findAllUsersWithPaging($offset = 0,$count=0)
   {
        $query = "select * from user order by _id  ";
        
        if($count != 0){
            $query .= " limit {$count} offset {$offset} ";
        }
        
        
        $result = $this->DB->fetchAll($query);
        
        $formatedUsers = array();
        foreach($result as $user){
            $user = $this->reformatUserData($user,false);
            $formatedUsers[] = $user;
        }
        
        return $this->formatResult($formatedUsers);
    }
    
   public function findUserCount()
    {
        $query = "select count(*) as count from user";
        
        $result = $this->DB->fetchColumn($query);

        return $result;
    }

    public function deleteUser($userId){

        $this->DB->delete('user', array('_id' => $userId));
        
        return array(
                'ok' => 1,
                'id' => $groupId,
                'rev' => 'tmprev' 
        );
    }
    
    public function createGroupCategory($title,$picture){
        
                
        $now = time();
 
        $valueArray = array();
        $valueArray['title'] = $title;
        $valueArray['avatar_file_id'] = $picture;
        $valueArray['created'] = $now;
        $valueArray['modified'] = $now;
        
        if($this->DB->insert('group_category',$valueArray)){
            return $this->DB->lastInsertId("_id");
        }else{
            return null;
        }
        
    }
    
    public function findAllGroupCategoryWithPaging($offset = 0,$count){
        
        $query = "select * from group_category order by _id ";
        
        if($count != 0){
            $query .= " limit {$count} offset {$offset} ";
        }
        
        
        $result = $this->DB->fetchAll($query);
        
        return $this->formatResult($result);
        
    }
    
    public function findGroupCategoryCount()
    {
        $query = "select count(*) as count from group_category";
        $result = $this->DB->fetchColumn($query);
        return $result;
    }

    public function findGroupCategoryById($id){
        
        $groupCategory = $this->DB->fetchAssoc('select * from group_category where _id = ?',array($id));                
        return $groupCategory;
        
    }
    
    public function updateGroupCategory($id,$title,$picture){

        $result = $this->DB->executeupdate(
                'update group_category set 
                    title = ?,
                    avatar_file_id = ?,
                    modified = ?
                    WHERE _id = ?', 
                array(
                    $title,
                    $picture,
                    time(),
                    $id));
        
        return $result;
    }

    public function deleteGroupCategory($id){

        $this->DB->delete('group_category', array('_id' => $id));
        
        return array(
                'ok' => 1,
                'id' => $groupId,
                'rev' => 'tmprev' 
        );
    }
    
    
    public function createEmoticon($idenfier,$picture){
        
        $now = time();
 
        $valueArray = array();
        $valueArray['identifier'] = $idenfier;
        $valueArray['file_id'] = $picture;
        $valueArray['created'] = $now;
        $valueArray['modified'] = $now;
        
        if($this->DB->insert('emoticon',$valueArray)){
            return $this->DB->lastInsertId("_id");
        }else{
            return null;
        }
        
    }

    public function findAllEmoticonsWithPaging($offset = 0,$count){
        
        $query = "select * from emoticon order by _id ";
        
        if($count != 0){
            $query .= " limit {$count} offset {$offset} ";
        }
        
        
        $result = $this->DB->fetchAll($query);
        
        return $this->formatResult($result);
        
    }
    
    public function findEmoticonCount()
    {
        $query = "select count(*) as count from emoticon";
        $result = $this->DB->fetchColumn($query);
        return $result;
    }

    public function findEmoticonById($id){
        
        $groupCategory = $this->DB->fetchAssoc('select * from emoticon where _id = ?',array($id));              
        return $groupCategory;
        
    }
    
    public function updateEmoticon($id,$title,$picture){

        $result = $this->DB->executeupdate(
                'update emoticon set 
                    identifier = ?,
                    file_id = ?,
                    modified = ?
                    WHERE _id = ?', 
                array(
                    $title,
                    $picture,
                    time(),
                    $id));
        
        return $result;
    }

    public function deleteEmoticon($id){

        $this->DB->delete('emoticon', array('_id' => $id));
        
        return array(
                'ok' => 1,
                'id' => $groupId,
                'rev' => 'tmprev' 
        );
    }

    public function getMessageCount(){
        $query = "select count(*) as count from message";
        $result = $this->DB->fetchColumn($query);
        return $result;
    }
    
    public function getLastLoginedUsersCount(){
        $timeFrom = time() - 60 * 60 * 24;
        $query = "select count(*) as count from message where created > {$timeFrom}";
        $result = $this->DB->fetchColumn($query);
        return $result;
    }
 
    public function setMessageDelete($messageId,$deleteType,$deleteAt,$deleteAfterShownFlag = 0){
        
        $now = time();
        
        $result = $this->DB->executeupdate(
                'update message set 
                    delete_at = ?,
                    delete_flagged_at = ?,
                    delete_after_shown = ?,
                    delete_type = ?
                    WHERE _id = ?',
                array(
                    $deleteAt,
                    $now,
                    $deleteAfterShownFlag,
                    $deleteType,
                    $messageId));
        
        return $result;

    }
    
    public function deleteMessage($messageId){
        
        $this->DB->delete('message', array('_id' => $messageId));
        
        return array(
                'ok' => 1,
                'id' => $messageId,
                'rev' => 'tmprev' 
        );

    }
    
    public function getConversationHistory($userId,$offset = 0,$count=10){
        
        $result = $this->DB->fetchAll("
            select * from message where _id in 
            (
                select max(_id) from message where from_user_id = ? group by to_user_id
                union
                select max(_id) from message where from_user_id = ? group by to_group_id
            )
            order by created desc
            limit {$count}
            offset {$offset}
            ",array($userId,$userId));
            
        return $result;
        
    }
    
    public function getConversationHistoryCount($userId){
        
        $result = $this->DB->fetchColumn("
            select count(*) as count from message where _id in 
            (
                select max(_id) from message where from_user_id = ? group by to_user_id
                union
                select max(_id) from message where from_user_id = ? group by to_group_id
            )
            ",array($userId,$userId));
            
        return $result;
        
    }
    
    public function updateReadAt($messageId){
        
        $result = $this->DB->executeupdate(
                'update message set 
                    read_at = ?
                    WHERE _id = ?',
                array(
                    time(),
                    $messageId));
        
        return $result;
        
    }
    
    //************************NEWS database logic
    public function findStoriesCount()
    {
    	$query = "select count(*) as count from `news`";
    
    	$result = $this->DB->fetchColumn($query);
    
    	return $result;
    }
    
    public function findAllNews($offset = 0,$count=0)
    {
    	
    	$query = "select * from news order by created desc  ";
    	
    	if($count != 0){
    		$query .= " limit {$count} offset {$offset} ";
    	}
    	
    	$result = $this->DB->fetchAll($query);
    	
    	$formatedNews = array();
    	foreach($result as $story){
    		$story = $this->reformatStoryData($story);
    		$formatedNews[] = $story;
    	}
    
    	return $formatedNews;
    
    }
    
    public function reformatStoryData($story){
    
    	$story['created'] = intval($story['created']);
    	$story['modified'] = intval($story['modified']);
    
    	return $story;
    }
    
    public function createStory($title,$content,$user_id,$story_url){
    
    
    	$newsData = array(
    			'title' => $title,
    			'content' => $content,
    			'story_url' => $story_url,
    			'user_id' => $user_id,
    			'modified' => time(),
    			'created' => time()
    	);
    
    	if($this->DB->insert('`news`',$newsData)){
    		return array(
    				'ok' => 1,
    				'id' => $this->DB->lastInsertId("_id")
    		);
    	}else{
    		return null;
    	}
    
    }
    
    public function findStoryById($id)
    {
    	$story = $this->DB->fetchAssoc('select * from `news` where _id = ?',array($id));
    	$story = $this->reformatStoryData($story);
    
    	return $this->formatRow($story);
    }
    
    public function updateStory($storyId,$title,$content,$story_url){
    
    	$now = time();
    
    	$result = $this->DB->executeupdate(
    			'update `news` set
                    title = ?,
                    content = ?,
    				story_url = ?,
    				modified = ?
                    WHERE _id = ?',
    			array(
    					$title,
    					$content,
    					$story_url,
    					time(),
    					$storyId));
    
    	if($result){
    		return array(
    				'ok' => 1,
    				'id' => $storyId,
    				'rev' => 'tmprev'
    		);
    	}else{
    		$arr = array('message' => 'update story error!', 'error' => 'logout');
    		return json_encode($arr);;
    	}
    
    	return null;
    
    }
    
    public function deleteStory($storyId){
    
    	$this->DB->delete('`news`', array('_id' => $storyId));
    
    	return array(
    			'ok' => 1,
    			'id' => $storyId,
    			'rev' => 'tmprev'
    	);
    }
    
    public function addCommentForStory($comment,$user_id,$user_name,$story_id){
    	
    	$commentData = array();
    	$commentData['story_id'] = $story_id;
    	$commentData['user_id'] = $user_id;
    	$commentData['comment'] = $comment;
    	$commentData['user_name'] = $user_name;
    	$commentData['created'] = time();
    	
    	if($this->DB->insert('news_comment',$commentData)){
    		return array(
    				'ok' => 1,
    				'id' => $this->DB->lastInsertId("_id")
    		);
    	}else{
    		return null;
    	}
    }
    
    public function getStoryCommentCount($storyId){
    	$query = "select count(*) as count from news_comment where story_id = ?";
    	
    	$result = $this->DB->fetchColumn($query, array($storyId));
    	
    	return $result;
    }
    
    public function getCommentsForStory($storyId,$offset = 0,$count=0)
    {
    	 
    	$query = "select * from news_comment where story_id =? ";
    	 
    	if($count != 0){
    		$query .= " limit {$count} offset {$offset} ";
    	}
    	 
    	$result = $this->DB->fetchAll($query, array($storyId));
    	 
    	$formatedComments = array();
    	
    	foreach($result as $comment){
    		$comment = $this->reformatCommentData($comment);
    		$formatedComments[] = $comment;
    	}
    
    	return $formatedComments;
    
    }
    
    public function getAvatarOfUser($user_id){

    	$query = "select avatar_thumb_file_id from user where _id =? ";
    	
    	$result=$this->DB->fetchAssoc($query, array($user_id));
    	
    	return $result['avatar_thumb_file_id'];
    }
    
    public function getAllUsersByGroupId($groupId,$offset = 0,$count = 30){
        $query = "
            select * from user where _id in 
                (select user_id from user_group where group_id = ?) 
                limit {$count} offset {$offset}";

        $users = $this->DB->fetchAll($query,array($groupId));
        
        
                      
        return $users;
    }
    
    public function getAllUsersCountByGroupId($groupId){
    
        $query = "
            select count(*) from user where _id in 
                (select user_id from user_group where group_id = ?)";

        $users = $this->DB->fetchColumn($query,array($groupId));

        return $users;
        
    }
    
    public function reportMessage($messageId){
        
    	$result = $this->DB->executeupdate(
    			'update message
                    set report_count = report_count + 1
                    WHERE _id = ?',
    			array($messageId));
        
    }
    
   public function findAllUsersWithPagingWithCriteria($offset = 0,$count=0,$criteria='',$criteriaValues=array())
   {
        $query = "select * from user where 1 = 1 {$criteria} order by _id ";
        
        if($count != 0){
            $query .= " limit {$count} offset {$offset} ";
        }
        
        $result = $this->DB->fetchAll($query,$criteriaValues);
        
        $formatedUsers = array();
        foreach($result as $user){
            $user = $this->reformatUserData($user,false);
            $formatedUsers[] = $user;
        }
        
        return $this->formatResult($formatedUsers);
    }

   public function findUserCountWithCriteria($criteria = '',$criteriaValues=array())
    {
        $query = "select count(*) as count from user where 1 = 1 {$criteria}";
        
        $result = $this->DB->fetchColumn($query,$criteriaValues);

        return $result;
    }

    public function findAllGroupsWithPagingWithCriteria($offset,$count,$criteria,$criteriaValues){
        
        $result = $this->DB->fetchAll("select * from `group` where 1 = 1 {$criteria} order by _id limit {$count} offset {$offset}",$criteriaValues);
        
        $formatedGroups = array();
        foreach($result as $group){
            $group = $this->reformatGroupData($group);
            $formatedGroups[] = $group;
        }
        
        return $formatedGroups;

    }
    
    public function findGroupCountWithCriteria($criteria,$criteriaValues){
        $query = "select count(*) as count from `group` where 1 = 1 {$criteria} ";
        $result = $this->DB->fetchColumn($query,$criteriaValues);
        return $result;
    }

    public function getContactsByUserId($userId){
        $query = "select * from user where _id in (select contact_user_id from user_contact where user_id = ?)";
        $users = $this->DB->fetchAll($query,array($userId));
        return $users;
    }
    
    public function getContactedByUserId($userId){
        $query = "select * from user where _id in (select user_id from user_contact where contact_user_id = ?)";
        $users = $this->DB->fetchAll($query,array($userId));
        return $users;
    }
    
    public function getGroupsByUserId($userId){
        $query = "select * from `group` where _id in (select group_id from user_group where user_id = ?)";
        $groups = $this->DB->fetchAll($query,array($userId));
        return $groups;
    }

    public function getAllUsersByGroupIdWithCriteria($groupId,$offset = 0,$count = 30,$criteria = "",$criteriaValues = array()){
        $query = "
            select * from user where _id in 
                (select user_id from user_group where group_id = ?) {$criteria}
                limit {$count} offset {$offset}";

        $users = $this->DB->fetchAll($query,array_merge(array($groupId),$criteriaValues));

        return $users;
    }
    
    public function getAllUsersCountByGroupIdWithCriteria($groupId,$criteria = "",$criteriaValues = array()){
    
        $query = "
            select count(*) from user where _id in 
                (select user_id from user_group where group_id = ?) {$criteria}";

        $users = $this->DB->fetchColumn($query,array_merge(array($groupId),$criteriaValues));

        return $users;
        
    }
    
    //******method for add servers
    public function findServersCount()
    {
    	$query = "select count(*) as count from `servers`";
    
    	$result = $this->DB->fetchColumn($query);
    
    	return $result;
    }
    
    public function reformatServerData($server){
    
    	$server['created'] = intval($server['created']);
    	$server['modified'] = intval($server['modified']);
    
    	return $server;
    }

    public function findAllServers($offset = 0,$count=0)
    {
    	 
    	$query = "select * from servers order by created desc  ";
    	 
    	if($count != 0){
    		$query .= " limit {$count} offset {$offset} ";
    	}
    	 
    	$result = $this->DB->fetchAll($query);
    	 
    	$formatedServers = array();
    	foreach($result as $server){
    		$server = $this->reformatServerData($server);
    		$formatedServers[] = $server;
    	}
    
    	return $formatedServers;
    
    }
    
    public function createServer($name,$url){
    
    
    	$serversData = array(
    			'name' => $name,
    			'url' => $url,
    			'modified' => time(),
    			'created' => time()
    	);
    
    	if($this->DB->insert('`servers`',$serversData)){
    		return array(
    				'ok' => 1,
    				'id' => $this->DB->lastInsertId("_id")
    		);
    	}else{
    		return null;
    	}
    
    }
    
    public function findServerById($id)
    {
    	$server = $this->DB->fetchAssoc('select * from `servers` where _id = ?',array($id));
    	$server = $this->reformatServerData($server);
    
    	return $this->formatRow($server);
    }
    
    public function updateServer($server_id,$name,$url){
    
    	$now = time();
    
    	$result = $this->DB->executeupdate(
    			'update `servers` set
                    name = ?,
                    url = ?,
    				modified = ?
                    WHERE _id = ?',
    			array(
    					$name,
    					$url,
    					time(),
    					$server_id));
    
    	if($result){
    		return array(
    				'ok' => 1,
    				'id' => $server_id,
    				'rev' => 'tmprev'
    		);
    	}else{
    		$arr = array('message' => 'update server error!', 'error' => 'logout');
    		return json_encode($arr);;
    	}
    
    	return null;
    
    }
    
    public function deleteServer($id){
    
    	$this->DB->delete('`servers`', array('_id' => $id));
    
    	return array(
    			'ok' => 1,
    			'id' => $id,
    			'rev' => 'tmprev'
    	);
    }
    
    public function findAllServersWitoutId()
    {
    
    	$query = "select name, url from servers order by created asc  ";
    
    	$result = $this->DB->fetchAll($query);
    
    	return $result;
    
    }
}
