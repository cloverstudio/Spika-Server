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
use Psr\Log\LoggerInterface;
use Guzzle\Http\Client;
use Guzzle\Http\Message\EntityEnclosingRequest;

class CouchDb implements DbInterface

{
    private $couchDBURL = "";
    private $logger;


    public function __construct($URL, LoggerInterface $logger){
        $this->couchDBURL = $URL;
        $this->logger     = $logger;
    }
    
    private function stripParamsFromJson($json)
    {
    
        $removeParams = array(
            "password",
            "token"
        );
    
        foreach ($removeParams as $paramToRemove) {
            $json = preg_replace("/,\"{$paramToRemove}\":\"[0-9a-zA-Z]*\"}/", "}", $json);
            $json = preg_replace("/,\"{$paramToRemove}\":\"[0-9a-zA-Z]*\",/", ",", $json);
        }
    
        return $json;
    
    }

    public function unregistToken($userId){
    
        $result = $this->doGetRequest("/{$userId}");
        $dic = json_decode($result, true);
    
        unset($dic['ios_push_token']);
        unset($dic['android_push_token']);
    
        $dic['online_status'] = "offline";
        $jsonToSave = json_encode($dic);
        
        $result = $this->doPutRequest($userId,$jsonToSave);

        return "OK";
        
    }
    
    public function checkEmailIsUnique($email){
        
        $startKey = urlencode("\"{$email}\"");
        $query = "?key={$startKey}";
        $result = $this->doGetRequest("/_design/app/_view/find_user_by_email{$query}");
        
        $resultAry = json_decode($result, true);

        $result = array();
        
        foreach ($resultAry['rows'] as $row) {
            $result[] = $row['value'];
        }
        
        return $result;
        
    }
    
    public function checkUserNameIsUnique($name){
        
        $startKey = urlencode("\"{$name}\"");
        $query = "?key={$startKey}";
        
        $result = $this->doGetRequest("/_design/app/_view/find_user_by_name{$query}");
        $nameResult = json_decode($result, true);

        $result = array();
        foreach ($nameResult['rows'] as $row) {
            $result[] = $row['value'];
        }
        
        return $result;
        
    }
    
    public function checkGroupNameIsUnique($name){
        
        $startKey = "\"{$name}\"";
        $query = "?key={$startKey}";
        $result = $this->doGetRequest("/_design/app/_view/find_group_by_name{$query}");
        
        $nameResult = json_decode($result, true);

        $result = array();
        foreach ($nameResult['rows'] as $row) {
            $result[] = $row['value'];
        }
        
        return $result;
        
    }
    
    public function doSpikaAuth($email,$password)
    {
        
        $emailQuery = urlencode('"' . $email . '"');
        
        list($header,$result) = $this->sendRequest("GET",$this->couchDBURL . "/_design/app/_view/find_user_by_email?key=" . $emailQuery);
        
        $json = json_decode($result, true);
                
        if (empty($json['rows'][0]['value']['email'])) {
            $arr = array('message' => 'User not found!', 'error' => 'logout');
        
            return json_encode($arr);
        }
        
        if ($json['rows'][0]['value']['password'] != $password) {
            $arr = array('message' => 'Wrong password!', 'error' => 'logout');
        
            return json_encode($arr);
        }
        
        $token = \Spika\Utils::randString(40, 40);
        
        $json['rows'][0]['value']['token'] = $token;
        $json['rows'][0]['value']['token_timestamp'] = time();
        $json['rows'][0]['value']['last_login'] = time();
        
        $userJson = $json['rows'][0]['value'];

        $result = $this->saveUserToken(json_encode($userJson), $json['rows'][0]['value']['_id']);
        $filteredUserData = $this->filterUser($result);
        
        return json_encode($filteredUserData);

    }

    function saveUserToken($userJson, $id)
    {
    
        list($header,$result) = $this->sendRequest("PUT",$this->couchDBURL . "/{$id}",
            $userJson);

        $userJson = json_decode($userJson, true);
        $json = json_decode($result, true);
        
        $userJson['_rev'] = $json['rev'];
        
        return $userJson;
        
    }

    /**
     * Finds a user by Token
     *
     * @param  string $token
     * @return array
     */
    public function findUserByToken($token)
    {
        $query  = "?key=" . urlencode('"' . $token . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_token{$query}", false);
        $result = json_decode($json, true);
        
        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
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

        $json = $this->doGetRequest("/_design/app/_view/searchuser_name");
        $result = json_decode($json, true);
        
        $result = $this->filterUserAry($result);
        
        return $result;
    }
    
    /**
     * Finds a user by User ID
     *
     * @param  string $id
     * @return array
     */
    public function findUserById($id)
    {


        $query  = "?key=" . urlencode('"' . $id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_id{$query}", true);
        $result = json_decode($json, true);

        $result = $this->filterUserAry($result);
        
        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }

    /**
     * Finds a user by email
     *
     * @param  string $email
     * @return array
     */
    public function findUserByEmail($email)
    {


        $query  = "?key=" . urlencode('"' . $email . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_email{$query}", true);
        $result = json_decode($json, true);
        $result = $this->filterUserAry($result);

        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }


    /**
     * Finds a user by name
     *
     * @param  string $name
     * @return array
     */
    public function findUserByName($name)
    {


        $query  = "?key=" . urlencode('"' . $name . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_user_by_name{$query}", true);
        $result = json_decode($json, true);
        $result = $this->filterUserAry($result);
        
        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }

    /**
     * Search a user by name
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByName($name){

        $escapedKeyword = urlencode($name);
        $startKey = "\"{$escapedKeyword}\"";
        $endKey = "\"{$escapedKeyword}ZZZZ\"";
        $query = "?startkey={$startKey}&endkey={$endKey}";
        
        //$result = $this->>doGetRequest("/_design/app/_view/searchuser_name{$query}");
        $json = $this->doGetRequest("/_design/app/_view/searchuser_name{$query}");
        $result = json_decode($json, true);
        
        $result = $this->filterUserAry($result);
        
        return $result;
    }
    
    /**
     * Search a user by gender
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByGender($gender){
        $query = "?key=\"{$gender}\"";
        $json = $this->doGetRequest("/_design/app/_view/searchuser_gender{$query}");
        $result = json_decode($json, true);
        
        $result = $this->filterUserAry($result);
        return $result;
    }
    
    /**
     * Search a user by age
     *
     * @param  string $name
     * @return array
     */
    public function searchUserByAge($ageFrom,$ageTo){

        $ageQuery = "";
        
        if (empty($ageFrom) && empty($ageTo)){
            return "";
        }
        
        if (!empty($ageFrom) && !empty($ageTo)) {
            $ageQuery = "?startkey={$ageFrom}&endkey={$ageTo}";
        }
        
        if (!empty($ageFrom) && empty($ageTo)) {
            $ageQuery = "?startkey={$ageFrom}";
        }
        
        if (empty($ageFrom) && !empty($ageTo)) {
            $ageQuery = "?endkey={$ageTo}";
        }
        
        $json = $this->doGetRequest("/_design/app/_view/searchuser_age{$ageQuery}");
        $result = json_decode($json, true);
        
        $result = $this->filterUserAry($result);
        
        return $result;
    }

    /**
     * Gets user activity summary
     *
     * @param  string $user_id
     * @return array
     */
    public function getActivitySummary($user_id)
    {
        $query  = "?key=" . urlencode('"' . $user_id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/user_activity_summary{$query}", false);
        $result = json_decode($json, true);

        return $result;
    }


    /**
     * create a user
     *
     * @param  string $json
     * @return id
     */
    public function createUser($userName,$email,$password)
    {
        
        $requestBodyAry = array();
        
        $requestBodyAry['name'] = $userName;
        $requestBodyAry['email'] = $email;
        $requestBodyAry['password'] = $password;
        $requestBodyAry['type'] = "user";
        $requestBodyAry['online_status'] = "online";
        $requestBodyAry['max_contact_count'] = 20;
        $requestBodyAry['max_favorite_count'] = 10;
    
        $requestBodyJson = json_encode($requestBodyAry);
        
        $json   = $this->doPostRequest($requestBodyJson);
        $result = json_decode($json, true);
        
        if(isset($result['ok']) && $result['ok'] == 'true' && isset($result['id'])){
            return $result['id'];
        }else
            return null;

    }

    public function updateUser($userId,$user){
        
        $user['_id'] = $userId;
        
        $json = $this->doPutRequest($userId,json_encode($user,JSON_FORCE_OBJECT));
        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 1 && isset($result['id'])){
            return $this->getUserById($result['id']);
        }else
            $arr = array('message' => 'Update user error!', 'error' => 'logout');
            return json_encode($arr);;
    }

    public function getUserById($userId){
        $json = $this->doGetRequest("/{$userId}");
        $result = json_decode($json, true);

        return $result;
    }

    public function addNewUserMessage($addNewMessage = 'text',$fromUserId,$toUserId,$message,$additionalParams=array()){
        
        $messageData = array();
        
        $messageData['from_user_id']=$fromUserId;
        $messageData['to_user_id']=$toUserId;
        $messageData['body']=$message;
        $messageData['modified']=time();
        $messageData['created']=time();
        $messageData['type']='message';
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
                
        $query = json_encode($messageData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);


        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }
        
        return $result;
    }

    public function addNewGroupMessage($addNewMessage = 'text',$fromUserId,$toGroupId,$message,$additionalParams=array()){
        
        $messageData = array();
        
        $messageData['from_user_id']=$fromUserId;
        $messageData['to_group_id']=$toGroupId;
        $messageData['body']=$message;
        $messageData['modified']=time();
        $messageData['created']=time();
        $messageData['type']='message';
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
            $toGroupData=$this->findUserById($toGroupId);
            $messageData['to_group_name']=$toGroupData['name'];
        }else{
            return null;
        }
                
        $query = json_encode($messageData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
    }


    public function getUserMessages($ownerUserId,$targetUserId,$count,$offset){
        
        $startKey = "[\"{$ownerUserId}\",\"{$targetUserId}\",{}]";
        $endKey = "[\"{$ownerUserId}\",\"{$targetUserId}\"]";
        $query = "?startkey={$startKey}&endkey={$endKey}&descending=true&limit={$count}&skip={$offset}";
        $json = $this->doGetRequest("/_design/app/_view/find_user_message{$query}");

        $result = json_decode($json, true);
                
        return $result;
    }

    public function getGroupMessages($targetGroupId,$count,$offset){
        
        $startKey = "[\"{$targetGroupId}\",{}]";
        $endKey = "[\"{$targetGroupId}\"]";
        $query = "?startkey={$startKey}&endkey={$endKey}&descending=true&limit={$count}&skip={$offset}";
        $json = $this->doGetRequest("/_design/app/_view/find_group_message{$query}");

        $result = json_decode($json, true);

        return $result;
    }
    
    public function getUserContacts($user_id,$include_docs){
        $query = "?key=". urlencode('"' . $user_id . '"')."&include_docs={$include_docs}";
        $json = $this->doGetRequest("/_design/app/_view/find_contacts{$query}");

        $result = json_decode($json, true);
        return $result;
    }


    public function addContact($userId,$targetUserId){
        
        // find user
        $userJSON = $this->doGetRequest("/" . $userId, false);
        $userArray = json_decode($userJSON,true);
        
        if(empty($userArray['_id'])){
            return null;
        }
        
        $targetUserJSON = $this->doGetRequest("/" . $targetUserId, false);
        $tagertUserArray = json_decode($targetUserJSON,true);
        
        if(empty($tagertUserArray['_id'])){
            return null;
        }

        if(!isset($userArray['contacts']))
             $userArray['contacts'] = array();
        
        if(!in_array($targetUserId, $userArray['contacts'])){
        
            array_push($userArray['contacts'], strval($targetUserId));
            
            $this->updateUser($userId,$userArray);
        }
        
        return true;
    }
    
    public function removeContact($userId,$targetUserId){
        
        // find user
        $userJSON = $this->doGetRequest("/" . $userId, false);
        $userArray = json_decode($userJSON,true);
        
        if(empty($userArray['_id'])){
            return null;
        }
        
        $targetUserJSON = $this->doGetRequest("/" . $targetUserId, false);
        $tagertUserArray = json_decode($targetUserJSON,true);
        
        if(empty($tagertUserArray['_id'])){
            return null;
        }

        if(!isset($userArray['contacts']))
             $userArray['contacts'] = array();
        
        if(in_array($targetUserId, $userArray['contacts'])){
            
            $deleteIndex = "";
            
            foreach($userArray['contacts'] as $index => $row){
                
                if($row == $targetUserId){
                    $deleteIndex = $index;
                    break;
                }
                
            }

            unset($userArray['contacts'][$deleteIndex]);

            $this->updateUser($userId,$userArray);
            
        }else{
            return null;
        }
        
        return true;
    }
    
    public function getEmoticons(){
        $json = $this->doGetRequest("/_design/app/_view/find_all_emoticons");
        $result = json_decode($json, true);

        return $result;
    }

    public function getEmoticonImage($emoticonId){
        $json = $this->doGetRequest("/{$emoticonId}");
        $result = json_decode($json, true);
        
        if(!isset($result['_attachments'])){
            return null;
        }
        
        foreach($result['_attachments'] as $imageName => $image){
            $imageBody = $this->doGetRequest("/{$emoticonId}/{$imageName}");
            return $imageBody;
        }
        
        return null;
    }

    public function getCommentCount($messageId){
        $query  = "?key=" . urlencode('"' . $messageId . '"');
        $json   = $this->doGetRequest("/_design/app/_view/get_comment_count{$query}", false);

        $result = json_decode($json, true);

        return $result;
    }

    public function findMessageById($messageId){
        $json = $this->doGetRequest("/{$messageId}");
        
        $result = json_decode($json, true);
        return $result;
    }
    
    public function addNewComment($messageId,$userId,$comment){
        
        $userData=$this->findUserById($userId);
        
        $commentData = array();
        
        $commentData['message_id'] = $messageId;
        $commentData['user_id'] = $userId;
        $commentData['type'] = 'comment';
        $commentData['comment'] = $comment;
        $commentData['user_name'] = $userData['name'];
        $commentData['created'] = time();
        
        $query = json_encode($commentData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            return $result;;
        }

        return null;
        
    }

    public function getComments($messageId,$count,$offset){
        
        $query = "?key=\"{$messageId}\"&descending=true&limit={$count}&skip={$offset}";
        
        $json = $this->doGetRequest("/_design/app/_view/find_comments_by_message_id{$query}");

        $result = json_decode($json, true);

        return $result;

        
    }

    public function getAvatarFileId($user_id){
        $query  = "?key=" . urlencode('"' . $user_id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_avatar_file_id{$query}", false);

        $result = json_decode($json, true);

        return $result;
    }


    public function createGroup($name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL){

        // get category name
        $categoryJson = $this->doGetRequest("/" . $categoryId, false);
        $categoryArray = json_decode($categoryJson,true);
        
        $categoryName = "";
        if(!empty($categoryArray['title'])){
            $categoryName = $categoryArray['title'];
        }
        
        $groupData = array(
            'name' => $name,
            'group_password' => $password,
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'description' => $description,
            'type' => 'group',
            'user_id' => $ownerId,
            'is_favourite' => false,
            'avatar_file_id' => $avatarURL,
            'avatar_thumb_file_id' => $thumbURL
        );
    
        $query = json_encode($groupData);
        $json = $this->doPostRequest($query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            if(isset($result['rev']))unset($result['rev']);
        }

        return $result;
    }

    public function updateGroup($groupId,$name,$ownerId,$categoryId,$description,$password,$avatarURL,$thumbURL){

        // get category name
        $categoryJson = $this->doGetRequest("/" . $categoryId, false);
        $categoryArray = json_decode($categoryJson,true);
        
        $categoryName = "";
        if(!empty($categoryArray['title'])){
            $categoryName = $categoryArray['title'];
        }
        
        $groupData = array();
    
        if(!empty($name))
            $groupData['name'] = $name;
            
        if(!empty($password))
            $groupData['group_password'] = $password;
        else
            $groupData['group_password'] = "";
        
        if(!empty($categoryId)){
            $groupData['category_id'] = $categoryId;
            $groupData['category_name'] = $categoryName;
            
        }

        if(!empty($description))
            $groupData['description'] = $description;
            
        if(!empty($ownerId))
            $groupData['user_id'] = $ownerId;
            
        if(!empty($avatarURL))
            $groupData['avatar_file_id'] = $avatarURL;
            
        if(!empty($thumbURL))
            $groupData['avatar_thumb_file_id'] = $thumbURL;

            
        $query = json_encode($groupData);
        $json = $this->doPutRequest($groupId,$query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            return $result;
        }

        return null;
        
    }

    public function deleteGroup($groupId){

        $groupData = array("deleted" => true);
            
        $query = json_encode($groupData);
        $json = $this->doPutRequest($groupId,$query);

        $result = json_decode($json, true);

        if(isset($result['ok']) && $result['ok'] == 'true'){
            return $result;
        }

        return null;
        
    }

    public function findGroupById($id)
    {


        $query  = "?key=" . urlencode('"' . $id . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_group_by_id{$query}", true);
        $result = json_decode($json, true);


        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }

    public function findGroupByName($name)
    {

        $query  = "?key=" . urlencode('"' . $name . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_group_by_name{$query}", true);
        $result = json_decode($json, true);

        return isset($result) && isset($result['rows']) &&
            isset($result['rows'][0]) && isset($result['rows'][0]['value'])
            ? $result['rows'][0]['value']
            : null;
    }
    
    public function findGroupByCategoryId($categoryId)
    {

        $query  = "?key=" . urlencode('"' . $categoryId . '"');
        $json   = $this->doGetRequest("/_design/app/_view/find_group_by_category_id{$query}", true);
        $result = json_decode($json, true);

        if(isset($result['rows'])){
            return $result;
        }
        
        return null;
    }

   public function findAllGroups()
    {
        
        $json   = $this->doGetRequest("/_design/app/_view/searchgroup_name", true);
        $result = json_decode($json, true);
        
        if(!isset($result['rows'])){
            return null;
        }
        
        // format array
        $returnResult = array();
        
        foreach($result['rows'] as $row){
            
            $returnResult[] = $row['value'];
            
        }
        
        return $returnResult;
    }
    
    public function findGroupsByName($name)
    {
        
        $query = "";
        
        if(!empty($name)){
            
            $startKey = "\"{$name}\"";
            $endKey = "\"{$name}ZZZZ\"";
            $query = "?startkey={$startKey}&endkey={$endKey}";

        } else {

        }

        $json   = $this->doGetRequest("/_design/app/_view/searchgroup_name{$query}", true);
        $result = json_decode($json, true);
        
        if(!isset($result['rows'])){
            return null;
        }
        
        // format array
        $returnResult = array();
        
        foreach($result['rows'] as $row){
            
            $returnResult[] = $row['value'];
            
        }
        
        return $returnResult;
    }

    public function findAllGroupCategory(){
        
        $json = $this->doGetRequest("/_design/app/_view/find_group_categories");

        $result = json_decode($json, true);

        return $result;

        
    }


    public function subscribeGroup($groupId,$userId){
        
        // find group
        $groupJSON = $this->doGetRequest("/" . $groupId, false);
        $groupArray = json_decode($groupJSON,true);
        
        if(empty($groupArray['_id'])){
            return null;
        }

        // find user
        $userJSON = $this->doGetRequest("/" . $userId, false);
        $userArray = json_decode($userJSON,true);
        
        if(empty($userArray['_id'])){
            return null;
        }
        
        $groupUserData = array(
            'group_id' => $groupArray['_id'],
            'user_id' => $userArray['_id'],
            'user_name' => $userArray['name'],
            'type' => "user_group"
        );
    
        $query = json_encode($groupUserData);
        $json = $this->doPostRequest($query);
        $jsonArray = json_decode($json,true);
        
        if(!isset($jsonArray['ok']) || $jsonArray['ok'] != true){
            return null;
        }
        
        $favoriteGroupList = array();
        if(!isset($userArray['favorite_groups']))
             $userArray['favorite_groups'] = array();
        
        if(!in_array($groupId, $favoriteGroupList)){
            array_push($userArray['favorite_groups'], strval($groupId));
            $this->updateUser($userId,$userArray);
        }
        
        return true;
    }
    
    public function unSubscribeGroup($groupId,$userId){
        
        // find group
        $groupJSON = $this->doGetRequest("/" . $groupId, false);
        $groupArray = json_decode($groupJSON,true);
        
        if(empty($groupArray['_id'])){
            return null;
        }

        // find user
        $userJSON = $this->doGetRequest("/" . $userId, false);
        $userArray = json_decode($userJSON,true);
        
        if(empty($userArray['_id'])){
            return null;
        }
        
        // delete users groups
        $query = "?key=[\"{$groupId}\",\"{$userId}\"]";
        $strUrl = "/_design/app/_view/find_users_group{$query}";
        $json = $this->doGetRequest($strUrl, false);
        $jsonAry = json_decode($json,true);

        if(isset($jsonAry['rows'][0]['value'])){
            $usersGroupsData = $jsonAry['rows'][0]['value'];
            $this->doDeleteRequest($usersGroupsData['_id'],$usersGroupsData['_rev']);
        }

        $groupUserData = array(
            'group_id' => $groupArray['_id'],
            'user_id' => $userArray['_id'],
            'user_name' => $userArray['name'],
            'type' => "user_group"
        );
    
        $query = json_encode($groupUserData);
        $json = $this->doPostRequest($query);
        $jsonArray = json_decode($json,true);
        
        if(!isset($jsonArray['ok']) || $jsonArray['ok'] != true){
            return null;
        }
        
        $favoriteGroupList = $userArray['favorite_groups'];
        
        if(in_array($groupId, $favoriteGroupList)){
            
            $deleteIndex = "";
            
            foreach($favoriteGroupList as $index => $row){
                
                if($row == $groupId){
                    $deleteIndex = $index;
                    break;
                }
                
            }

            unset($userArray['favorite_groups'][$deleteIndex]);

            $this->updateUser($userId,$userArray);
            
        }else{
            return null;
        }
        
        return true;
    }
    
    
    
    public function watchGroup($groupId,$userId){
        
        // find group
        $groupJSON = $this->doGetRequest("/" . $groupId, false);
        $groupArray = json_decode($groupJSON,true);
        
        if(empty($groupArray['_id'])){
            return false;
        }

        // find user
        $userJSON = $this->doGetRequest("/" . $userId, false);
        $userArray = json_decode($userJSON,true);
        
        if(empty($userArray['_id'])){
            return false;
        }
        
        $groupUserData = array(
            'group_id' => $groupArray['_id'],
            'user_id' => $userId,
            'group_id' => $groupId,
            'type' => "watching_group_log"
        );
    
        $query = json_encode($groupUserData);
        $json = $this->doPostRequest($query);
        $jsonArray = json_decode($json,true);
        
        if(!isset($jsonArray['ok']) || $jsonArray['ok'] != true){
            return false;
        }
        
        return true;
    }
    
    public function unWatchGroup($userId){
        
        // find user
        $userJSON = $this->doGetRequest("/" . $userId, false);
        $userArray = json_decode($userJSON,true);
        
        if(empty($userArray['_id'])){
            return false;
        }

        // delete watch log
        $query = "?key=\"{$userId}\"";
        $strUrl = "/_design/app/_view/find_lastwatching_group_by_user_id{$query}";
        $json = $this->doGetRequest($strUrl, false);
        $jsonAry = json_decode($json,true);
        
        if(!isset($jsonAry['rows']))
            return false;
        
        foreach($jsonAry['rows'] as $row){
            $watchLogData = $row['value'];
            $this->doDeleteRequest($watchLogData['_id'],$watchLogData['_rev']);
        }
                
        return true;
        
    }

    function updateActivitySummaryByDirectMessage($toUserId, $fromUserId)
    {
        
        $type = ACTIVITY_SUMMARY_DIRECT_MESSAGE;
        
        // get latest activity summary
        $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
        $return = $this->doGetRequest($url);
        $returnDic = json_decode($return, true);
        
        $return = $this->doGetRequest("/" . $fromUserId);
        $fromUserData = json_decode($return, true);
        
        if (count($returnDic['rows']) == 0) {
    
            // if doesn't exist generate
            $params = array(
                'type' => 'activity_summary',
                'user_id' => $toUserId,
                'recent_activity' => array(
                    $type => array(
                        'name' => 'Chat activity',
                        "target_type" => "user",
                        'notifications' => array()
                    )
                )
            );
    
            $result = $this->doPostRequest(json_encode($params));
            
            $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
            $return = $this->doGetRequest($url);
            $returnDic = json_decode($return, true);
    
        }
    
        $userActivitySummary = $returnDic['rows'][0]['value'];
        $userActivitySummary['recent_activity'][$type]['name'] = 'Chat activity';
        $userActivitySummary['recent_activity'][$type]['target_type'] = 'user';
    
        $message = sprintf(DIRECTMESSAGE_NOTIFICATION_MESSAGE,$fromUserData['name']);
        
        if (isset($userActivitySummary)) {
    
            //find row
            $targetTypeALL = $userActivitySummary['recent_activity'][$type]['notifications'];
            $isExists = false;
            $inAryKey = 0;
            $baseJSONData = array();
    
            foreach ($targetTypeALL as $key => $perTypeRow) {
                if ($perTypeRow['target_id'] == $fromUserId) {
                    $isExists = true;
                    $baseJSONData = $perTypeRow;
                    $inAryKey = $key;
                }
            }
    
            if (!$isExists) {
                $baseJSONData = array(
                    "target_id" => $fromUserId,
                    "count" => 0,
                    "messages" => array()
                );
            }
    
            $baseJSONData['count']++;
            $baseJSONData['lastupdate'] = time();
    
    
            $avatarPath = "/" . $fromUserId . "/";
            
            if(isset($fromUserData['_attachments']) && is_array($fromUserData['_attachments'])){
                foreach ($fromUserData['_attachments'] as $key => $val) {
                    if (preg_match("/avatar/", $key)) {
                        $avatarPath .= $key;
                        break;
                    }
                }
            }else{
                $avatarPath = '';
            }

    
            $baseJSONData['messages'][0] = array(
                "from_user_id" => $fromUserId,
                "message" => $message,
                "user_image_url" => $avatarPath
            );
    
            if (!$isExists) {
                $userActivitySummary['recent_activity'][$type]['notifications'][] = $baseJSONData;
            } else {
                $userActivitySummary['recent_activity'][$type]['notifications'][$inAryKey] = $baseJSONData;
            }
    
            // update summary
            $json = json_encode($userActivitySummary, JSON_FORCE_OBJECT);
            
            $this->doPutRequest($userActivitySummary["_id"],$json);
            
        }
        
    }
    
    function updateActivitySummaryByGroupMessage($toGroupId, $fromUserId)
    {
        
        $type = ACTIVITY_SUMMARY_GROUP_MESSAGE;
        
        $url =  "/{$toGroupId}";
        $return = $this->doGetRequest($url, false);
        $toGroupData = json_decode($return, true);
        
        $url = "/{$fromUserId}";
        $return = $this->doGetRequest($url, false);
        $fromUserData = json_decode($return, true);

        // find users who are subscribing the group
        $url = "/_design/app/_view/find_users_by_groupid?key=" . urlencode('"' . $toGroupId . '"');
        $subscribedUsers = array();
        $userListResultJSON = $this->doGetRequest($url, true);
        $userListResult = json_decode($userListResultJSON, true);
        $userListResultRows = $userListResult['rows'];
        foreach ($userListResultRows as $row) {
            $toUser = $row['value']['user_id'];

            if ($toUser == $fromUserId) {
                continue;
            }

            $subscribedUsers[] = $toUser;
            
        }

        foreach($subscribedUsers as $toUserId){
        
            // get latest activity summary
            $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
            $return = $this->doGetRequest($url);
            $returnDic = json_decode($return, true);

            if (count($returnDic['rows']) == 0) {
        
                // if doesn't exist generate
                $params = array(
                    'type' => 'activity_summary',
                    'user_id' => $toUserId,
                    'recent_activity' => array(
                        $type => array(
                            'name' => 'Groups activity',
                            "target_type" => "group",
                            'notifications' => array()
                        )
                    )
                );
        
                $result = $this->doPostRequest(json_encode($params));
        
                $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
                $return = $this->doGetRequest($url);
                $returnDic = json_decode($return, true);
        
            }
        
            $userActivitySummary = $returnDic['rows'][0]['value'];
            $userActivitySummary['recent_activity'][$type]['name'] = 'Groups activity';
            $userActivitySummary['recent_activity'][$type]['target_type'] = 'group';
        
            $message = sprintf(GROUPMESSAGE_NOTIFICATION_MESSAGE,$fromUserData['name'],$toGroupData['name']);
            
            if (isset($userActivitySummary)) {
                
                //find row
                $targetTypeALL = $userActivitySummary['recent_activity'][$type]['notifications'];
                $isExists = false;
                $inAryKey = 0;
                $baseJSONData = array();
        
                foreach ($targetTypeALL as $key => $perTypeRow) {
                    if ($perTypeRow['target_id'] == $toGroupId) {
                        $isExists = true;
                        $baseJSONData = $perTypeRow;
                        $inAryKey = $key;
                    }
                }
        
                if (!$isExists) {
                    $baseJSONData = array(
                        "target_id" => $toGroupId,
                        "count" => 0,
                        "messages" => array()
                    );
                }
        
                $baseJSONData['count']++;
                $baseJSONData['lastupdate'] = time();
        
                $baseJSONData['messages'][0] = array(
                    "from_user_id" => $fromUserId,
                    "message" => $message
                );
                
                if(isset( $fromUserData['avatar_thumb_file_id']))
                    $baseJSONData['messages'][0]['user_image_url'] = $fromUserData['avatar_thumb_file_id'];
                
                if ($isExists) {
                    $userActivitySummary['recent_activity'][$type]['notifications'][$inAryKey] = $baseJSONData;
                } else {
                    $userActivitySummary['recent_activity'][$type]['notifications'][] = $baseJSONData;
                }
        
                // update summary
                $json = json_encode($userActivitySummary, JSON_FORCE_OBJECT);
                $result = $this->doPutRequest(
                    $userActivitySummary["_id"],
                    $json
                );
                
            }
            
        }
                
    }
    
    function clearActivitySummary($toUser, $type, $fieldKey)
    {
        global $db_url;
    
    
        // get latest activity summary
        $url = "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUser . '"');
        $return = $this->doGetRequest($url);
        $returnDic = json_decode($return, true);
    
        if(!isset($returnDic['rows'][0]['value']['recent_activity'][$type]))
            return;
            
        $userActivitySummary = $returnDic['rows'][0]['value'];
        $userActivitySummaryType = $returnDic['rows'][0]['value']['recent_activity'][$type];
        $targetIndex = null;
    
        foreach ($userActivitySummaryType['notifications'] as $key => $row) {
    
            if ($row['target_id'] == $fieldKey) {
                $targetIndex = $key;
            }
    
        }
    
        if (isset($userActivitySummaryType['notifications'][$targetIndex])) {
    
            unset($userActivitySummary['recent_activity'][$type]['notifications'][$targetIndex]);
            $json = json_encode($userActivitySummary, JSON_FORCE_OBJECT);
            $result = $this->doPutRequest(
                $userActivitySummary["_id"],
                $json
            );
    
        }
    
    
    }

    public function addPassworResetRequest($toUserId){
        
        $params = array(
            'user_id' => $toUserId,
            'timestamp' => time()
        );
        
        $result = $this->doPostRequest(json_encode($params));
        $resultDic = json_decode($result, true);
        
        
        return $resultDic['id'];
    }
    
    public function getPassworResetRequest($requestCode){
        $result = $this->doGetRequest("/" . $requestCode);
        return json_decode($result, true);
    }
    
    public function changePassword($userId,$newPassword){
        
        $changeData = array(
            'password' => $newPassword
        ); 
        
        // update summary
        $json = json_encode($changeData);
        $result = $this->doPutRequest(
            $userId,
            $json
        );
        
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
    
    public function doPostRequest($requestBody)
    {
        
        list($header,$body) = $this->sendRequest("POST",$this->couchDBURL,$requestBody);
        
        return $body;

    }
 
    public function doGetRequestGetHeader($queryString,$stripCredentials = true)
    {
        
        $couchDBQuery = $this->couchDBURL . "/" . $queryString;
        
        list($header,$body) = $this->sendRequest("GET",$couchDBQuery);
        
        if($stripCredentials)
            return array($header,$this->stripParamsFromJson($body));
        else
            return array($header,$body);
    
    }
    
    
    public function doGetRequest($queryString,$stripCredentials = true)
    {
        
        $couchDBQuery = $this->couchDBURL . $queryString;
        
        list($header,$body) = $this->sendRequest("GET",$couchDBQuery);
        
        if($stripCredentials)
            return $this->stripParamsFromJson($body);
        else
            return $body;
    
    }
    
    public function doPutRequest($id,$requestBody)
    {
        
        // merge with original json
        // put request is update in couchdb. for all get requests backend cuts off password and email
        // so I have to merge with original data here. Other wise password will gone.
        list($header,$originalJSON) = $this->sendRequest("GET",$this->couchDBURL . "/{$id}");

        $originalData = json_decode($originalJSON,true);
        $newData = json_decode($requestBody,true);

        if(isset($originalData["_rev"])) $newData["_rev"] = $originalData["_rev"];
        
        $mergedData = array_merge($originalData,$newData);
        $jsonToSave = json_encode($mergedData,true);
                
        // save
        list($header,$body) = $this->sendRequest("PUT",$this->couchDBURL . "/{$id}",$jsonToSave);

        return $body;

    }
    
    public function doDeleteRequest($id,$rev)
    {
    
        list($header,$body) = $this->sendRequest("DELETE",$this->couchDBURL . "/{$id}?rev={$rev}");

        return $body;

    }
    
 
    /*
    public function addToContact($owserUserId,$tagetUserId){
        
    }
    
    public function removeFromContact($owserUserId,$tagetUserId){
        
    }*/

}
