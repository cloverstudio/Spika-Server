<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class InstallerController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

		// check unique controller
		$controllers->get('/install', function (Request $request) use ($app) {
			
			$randomEmail = Utils::randString(8, 8) . '@clover-studio.com';
			$password = Utils::randString(8, 8);
			
			$result = "";
			
			$resCheckDB = $app['spikadb']->doGetRequest("");
			$resCheckDBDic = json_decode($resCheckDB, true);
			
			if (!isset($resCheckDBDic['db_name'])) {
			    die("Database does not exist \n");
			}else{
				$result .= "Database check OK: Database name is {$resCheckDBDic['db_name']} <br />";
			}
			
			// Create support user
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
			
			$resultCreateUser = $app['spikadb']->doPostRequest($json);
			$resultCreateUserDic = json_decode($resultCreateUser, true);
			
			if ($resultCreateUserDic['ok'] != 'true') {
			    die("Failed to create user. \n");
			}
			
			$result .= "Create User OK: email is '{$randomEmail}',password is '{$password}', support user id {$resultCreateUserDic['id']} <br />";
			
			
			// create stickers
			
			// delete first
			$resAllStickers = $app['spikadb']->doGetRequest("_design/app/_view/find_all_emoticons");
			$resAllStickersDic = json_decode($resAllStickers, true);
			
			foreach ($resAllStickersDic['rows'] as $data) {
			
			    $id = $data['value']['_id'];
			    $rev = $data['value']['_rev'];
			    $app['spikadb']->doDeleteRequest($id,$rev);
			
			}
			
			
			
			$files = array();
			$filesPath = __DIR__.'/../install/resouces/emoticons';
			
			if ($handle = opendir($filesPath)) {
			
			    while ($entry = readdir($handle)) {
			        if (is_file($filesPath . "/" . $entry)) {
			
			            if (preg_match("/png/", $entry)) {
			                $files[] = $filesPath . "/" . $entry;
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
			
			    $resInsertSticker = $app['spikadb']->doPostRequest(json_encode($dataAry));
			    $resInsertStickerDic = json_decode($resInsertSticker, true);
			    
				if ($resInsertStickerDic['ok'] != 'true') {
			   		die("Failed to create sticker. \n");
			    }

			}
			
			$result .= "Stickers are installed <br />";
			$result .= "Install done. <br />";
			$result .= "<br />";
			
			$pageURL = 'http';
			if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
				$pageURL .= "s";
			}
			
			$pageURL .= "://";
			if ($_SERVER["SERVER_PORT"] != "80") {
				$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
			} else {
				$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
			}
			
			$pageURL = str_replace("install","api",$pageURL);

			$result .= "API URL is <strong>{$pageURL}/</strong>";
			
			
			return $result;
		
		});
        
        return $controllers;
    }
    
}

?>
