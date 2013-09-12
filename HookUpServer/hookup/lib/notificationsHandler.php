<?php
	
	define("EXEC_DIR",dirname(__FILE__));
	define("notificationsHandler","1");
	
	if(!defined("HOST")){
	
		// command line mode
		$paramsSerialized = $argv[1];
		$params = unserialize($paramsSerialized);
		
		$reqBody = $params['reqBody'];
		$queryStr = $params['queryStr'];
		$serverMethod = $params['serverMethod'];
		$currentUser = $params['currentUser'];
		$APP = $params['APP'];
		
		define("APP",$APP);
		
		include(EXEC_DIR . "/startup.php");

		if($serverMethod == 'GET'){
	
			handleGetRequest();
			
		} else if($serverMethod == 'POST') {
		
			handlePostRequest($reqBody);
	
		} else if($serverMethod == 'PUT'){
	
	
		} else if($serverMethod == 'DELETE'){
			
	
		}
		
	}
	
	function activateNotificationHandler(){
		global $APP,$_SERVER,$current_user;
		
		$reqBody = file_get_contents('php://input');
		$queryStr = $_SERVER['QUERY_STRING'];
		
		$params = array(
			'reqBody' => $reqBody,
			'queryStr' => $queryStr,
			'serverMethod' => $_SERVER['REQUEST_METHOD'],
			'currentUser' => $current_user['_id'],
			'APP' => $APP
		);
		
		
		$command = PHP_COMMAND . " " .ROOT_DIR."/lib/notificationsHandler.php '" . serialize($params) . "' > /dev/null &";
		system($command);
	}
	
	function handleGetRequest(){
	
		global $queryStr,$currentUser;
		

		preg_match_all("/_view\/(.+)\?/", $queryStr,$out);
		
		$command = $out[1][0];
		
		if($command == "find_user_message"){
			
			preg_match_all("/startkey=%5B%22(.*?)%22,%22(.*?)%22/", $queryStr,$out);
			
			$toUserId = $out[1][0];
			$fromUserId = $out[2][0];
			
			if(isset($currentUser) && $fromUserId){
					
				clearNotification($toUserId,"direct_messages",$fromUserId);
				
			}
			
		}
		

		if($command == "find_group_message"){
			
			preg_match_all("/startkey=%5B%22(.*?)%22/", $queryStr,$out);
			
			$groupId = $out[1][0];
	
			if(isset($currentUser) && isset($groupId)){
				
				clearNotification($currentUser,"group_posts",$groupId);
				
			}
			
		}
		
	}
	
	function handlePostRequest($data){
		global $db_url,$currentUser;

		$bodyAry = json_decode($data,true);
		$requestType = $bodyAry['type'];
		
		if(empty($requestType))
			return;
		
		if($requestType == 'message'){

			$targetType = $bodyAry['message_target_type'];
			
			if($targetType == 'user'){
			
				$fromUser = $bodyAry['from_user_id'];
				$toUser = $bodyAry['to_user_id'];
				$message = $bodyAry['body'];
			
				// send push notification
				sendDirectMessageNotification($fromUser,$toUser,$message);
				
				// add to activity summary
				newMessageNotification($toUser,$fromUser,"direct_messages");
		
			}
			
			
			if($targetType == 'group'){

				$fromUser = $bodyAry['from_user_id'];
				$toGroup = $bodyAry['to_group_id'];
				$message = $bodyAry['body'];
				
				
				// find users who has the group in favorite
				$url = $db_url . "_design/app/_view/find_user_by_id?key=" . urlencode('"' . $fromUser . '"');
				$return = getRequest($url,true);	
				$returnData = json_decode($return,true);
				if(isset($returnData['rows'][0])){
					$userDataFrom = $returnData['rows'][0];
				}
				
				$url = $db_url . "/{$toGroup}";
				$return = getRequest($url,true);	 
				$groupData = json_decode($return,true);
			
				$url = $db_url . "/_design/app/_view/find_users_by_groupid?key=" . urlencode('"' . $toGroup . '"');
				
				$userListResultJSON = getRequest($url,true);
				$userListResult = json_decode($userListResultJSON,true);
				$userListResultRows = $userListResult['rows'];
				
				foreach($userListResultRows as $row){
					$toUser = $row['value']['user_id'];

					if($toUser == $currentUser)
						continue;
					
					//sendGroupMessageNotification($userDataFrom,$toUser,$groupData,$message);
					newGroupNotification($toUser,$fromUser,$toGroup,"group_posts");
					
				}
				
				$url = $db_url . "/_design/app/_view/find_lastwatching_group_by_group_id?key=" . urlencode('"' . $toGroup . '"');
				$userListResultJSON = getRequest($url,true);
				$userListResult = json_decode($userListResultJSON,true);
				$userListResultRows = $userListResult['rows'];
				
				foreach($userListResultRows as $row){
					$toUser = $row['value']['user_id'];

					if($toUser == $currentUser)
						continue;
						
					//sendGroupMessageNotification($userDataFrom,$toUser,$groupData,$message);
					newGroupNotification($toUser,$fromUser,$toGroup,"group_posts");
					
				}
						

			}
				
		}
				
	}
	
	function newGroupNotification($toUserId,$fromUserId,$toGroupId,$type){
		global $db_url;

		// get latest activity summary
		$url = HOST . "/" . DB . "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
		$return = doGet($url);
		$returnDic = json_decode($return,true);
		
		$url = $db_url . "/{$toGroupId}";
		$return = getRequest($url,true);	 
		$toGroupData = json_decode($return,true);
		
		$url = $db_url . "/{$fromUserId}";
		$return = getRequest($url,true);	 
		$fromUserData = json_decode($return,true);
		
		
		if(count($returnDic['rows']) == 0){
		
			// if doesn't exist generate 
			$params = array(
				'type'=>'activity_summary',
				'user_id'=>$toUserId,
				'recent_activity' => array(
					$type => array(
						'name' => 'Groups activity',
						"target_type" => "group",
						'notifications' => array()
					)
				)
			);
			
			$result = postRequest($db_url,json_encode($params),true);

			$url = HOST . "/" . DB . "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
			$return = doGet($url);
			$returnDic = json_decode($return,true);

		}
				
		$userActivitySummary = $returnDic['rows'][0]['value'];
		$userActivitySummary['recent_activity'][$type]['name'] = 'Groups activity';
		$userActivitySummary['recent_activity'][$type]['target_type'] = 'group';
		
		$message = getPushMessageForGroup($fromUserData['name'],$toGroupData['name']);


		if(isset($userActivitySummary)){
			
			//find row
			$targetTypeALL = $userActivitySummary['recent_activity'][$type]['notifications'];
			$isExists = false;
			$inAryKey = 0;
			$baseJSONData = array();
			
			foreach($targetTypeALL as $key => $perTypeRow){
				if($perTypeRow['target_id'] == $toGroupId){
					$isExists = true;
					$baseJSONData = $perTypeRow;
					$inAryKey = $key;
				}
			}
			
			if(!$isExists){
				$baseJSONData = array(
							"target_id" => $toGroupId,
							"count" => 0,
							"messages" => array()
				);
			}
			
			$baseJSONData['count']++;
			$baseJSONData['lastupdate'] = time();
			
			$avatarPath = "/" . $fromUserId . "/";
			foreach($fromUserData['_attachments'] as $key => $val){
				if(preg_match("/avatar/",$key)){
					$avatarPath .= $key;
					break;
				}
			}
			
			$baseJSONData['messages'][0] = array(
					"from_user_id" => $fromUserId,
					"message" => $message,
					"user_image_url" => $avatarPath
			);
			
			if($isExists)
				$userActivitySummary['recent_activity'][$type]['notifications'][$inAryKey] = $baseJSONData;
			else
				$userActivitySummary['recent_activity'][$type]['notifications'][] = $baseJSONData;
				
			// update summary
			$json = json_encode($userActivitySummary,JSON_FORCE_OBJECT);
			$result = putRequest($db_url . $userActivitySummary["_id"] . "/?rev=" . $userActivitySummary["_rev"],$json,true);
			
			
		}
				
	}
	
	function newMessageNotification($toUserId,$fromUserId,$type){
		global $db_url;

		// get latest activity summary
		$url = HOST . "/" . DB . "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
		$return = doGet($url);
		$returnDic = json_decode($return,true);
		
		$url = $db_url . "/{$fromUserId}";
		$return = getRequest($url,true);	 
		$fromUserData = json_decode($return,true);
		
		if(count($returnDic['rows']) == 0){
		
			// if doesn't exist generate 
			$params = array(
				'type'=>'activity_summary',
				'user_id'=>$toUserId,
				'recent_activity' => array(
					$type => array(
						'name' => 'Chat activity',
						"target_type" => "user",
						'notifications' => array()
					)
				)
			);
			
			$result = postRequest($db_url,json_encode($params),true);

			$url = HOST . "/" . DB . "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUserId . '"');
			$return = doGet($url);
			$returnDic = json_decode($return,true);

		}
		
		$userActivitySummary = $returnDic['rows'][0]['value'];
		$userActivitySummary['recent_activity'][$type]['name'] = 'Chat activity';
		$userActivitySummary['recent_activity'][$type]['target_type'] = 'user';

		$message = getPushMessageForMessage($fromUserData['name']);
		

		if(isset($userActivitySummary)){
			
			//find row
			$targetTypeALL = $userActivitySummary['recent_activity'][$type]['notifications'];
			$isExists = false;
			$inAryKey = 0;
			$baseJSONData = array();

			foreach($targetTypeALL as $key => $perTypeRow){
				if($perTypeRow['target_id'] == $fromUserId){
					$isExists = true;
					$baseJSONData = $perTypeRow;
					$inAryKey = $key;
				}
			}
			
			if(!$isExists){
				$baseJSONData = array(
							"target_id" => $fromUserId,
							"count" => 0,
							"messages" => array()
				);
			}
						
			$baseJSONData['count']++;
			$baseJSONData['lastupdate'] = time();
			

			$avatarPath = "/" . $fromUserId . "/";
			foreach($fromUserData['_attachments'] as $key => $val){
				if(preg_match("/avatar/",$key)){
					$avatarPath .= $key;
					break;
				}
			}
			
			$baseJSONData['messages'][0] = array(
					"from_user_id" => $fromUserId,
					"message" => $message,
					"user_image_url" => $avatarPath
			);
			
			if(!$isExists)
				$userActivitySummary['recent_activity'][$type]['notifications'][] = $baseJSONData;
			else
				$userActivitySummary['recent_activity'][$type]['notifications'][$inAryKey] = $baseJSONData;

			// update summary
			$json = json_encode($userActivitySummary,JSON_FORCE_OBJECT);
			$result = putRequest($db_url . $userActivitySummary["_id"] . "/?rev=" . $userActivitySummary["_rev"],$json,true);
			
		}

	}
	
	
	function clearNotification($toUser,$type,$fieldKey){
		global $db_url;

		
		
		// get latest activity summary
		$url = HOST . "/" . DB . "/_design/app/_view/usere_activity_summary?key=" . urlencode('"' . $toUser . '"');
		$return = doGet($url);
		$returnDic = json_decode($return,true);
		
		//_log(print_r($returnDic,true));
		
		$userActivitySummary = $returnDic['rows'][0]['value'];
		$userActivitySummaryType = $returnDic['rows'][0]['value']['recent_activity'][$type];
		$targetIndex = null;

		foreach($userActivitySummaryType['notifications'] as $key => $row){
			
			if($row['target_id'] == $fieldKey)
				$targetIndex = $key;
			
		}
		
		if(isset($userActivitySummaryType['notifications'][$targetIndex])){
			
			unset($userActivitySummary['recent_activity'][$type]['notifications'][$targetIndex]);
			$json = json_encode($userActivitySummary,JSON_FORCE_OBJECT);
			$result = putRequest($db_url . $userActivitySummary["_id"] . "/?rev=" . $userActivitySummary["_rev"],$json,true);
			
			_log("notification cleared to " . $toUser . " type " . $type . " key " . $fieldKey . " index " . $targetIndex);
		}
		
		
				
	}
?>