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
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\DBAL\DriverManager;

class InstallerController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
    	ExceptionHandler::register(false);
        $controllers = $app['controllers_factory'];

		// first screen
		$controllers->get('/installer', function (Request $request) use ($app) {
			return $app['twig']->render('installerTop.twig', array(
				'ROOT_URL' => ROOT_URL
			));			
		});

		// connect to DB
		$controllers->post('/installer/step1', function (Request $request) use ($app) {
			
			$host = $request->get('host');
			$database = $request->get('database');
			$userName = $request->get('username');
			$password = $request->get('password');
			
			$config = new \Doctrine\DBAL\Configuration();
			
			$connectionParams = array(
			    'dbname' => $database,
			    'user' => $userName,
			    'password' => $password,
			    'host' => $host,
			    'driver' => 'pdo_mysql',
			);
			
			$app['session']->set('databaseConfiguration', $connectionParams);
			
			$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
			
			try{
				$connectionResult = $conn->connect();			
			}catch(\PDOException $e){
				$connectionResult = false;
			}
			
			if($connectionResult){
				return $app['twig']->render('installerStep1.twig', array(
					'ROOT_URL' => ROOT_URL,
					'ConnectionSucceed' => $connectionResult
				));			
			}else{
				return $app['twig']->render('installerTop.twig', array(
					'ROOT_URL' => ROOT_URL,
					'ConnectionSucceed' => $connectionResult
				));			
			}
		});

		// create database schema
		$controllers->post('/installer/step2', function (Request $request) use ($app) {
			
			$config = new \Doctrine\DBAL\Configuration();
			$connectionParams = $app['session']->get('databaseConfiguration');
			
			$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
			
			try{
				$connectionResult = $conn->connect();			
			}catch(\PDOException $e){
				$app->redirect('/installer');
			}
			
			// read sql file
			$pathToSchemaFile = "../install/databaseschema.sql";
			if(!file_exists("../install/databaseschema.sql")){
				return $app['twig']->render('installerError.twig', array(
					'ROOT_URL' => ROOT_URL
				));
			}
			
			$schemacontent = file_get_contents($pathToSchemaFile);
			
			$queries = explode(";",$schemacontent);
			
			$conn->beginTransaction();
			
			try{
			
				foreach($queries as $query){
					$query = trim($query);
					
					if(!empty($query))
						$conn->executeQuery($query);
				}
				
				$conn->commit();	
			} catch(\Exception $e){
				$conn->rollback();		
				return $app['twig']->render('installerError.twig', array(
					'ROOT_URL' => ROOT_URL
				));
				
			}
			
			return $app['twig']->render('installerStep2.twig', array(
				'ROOT_URL' => ROOT_URL,
				'ConnectionSucceed' => $connectionResult
			));		
		
		});

		// generate initial data
		$controllers->post('/installer/step3', function (Request $request) use ($app) {
			
			$config = new \Doctrine\DBAL\Configuration();
			$connectionParams = $app['session']->get('databaseConfiguration');
			
			$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
			
			try{
				$connectionResult = $conn->connect();			
			}catch(\PDOException $e){
				$app->redirect('/installer');
			}
			
			$fileDir = __DIR__.'/../../../'.FileController::$fileDirName;
			if(!is_writable($fileDir)){
				return $app['twig']->render('installerError.twig', array(
					'ROOT_URL' => ROOT_URL
				));
			}
			
			$conn->beginTransaction();
			
			// generate group categories

			$files = array();
			$filesPath = __DIR__.'/../../../install/resouces/categoryimages';
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
				
				// copy to file dir
				$pathinfo = pathinfo($path);
				$categoryName = $pathinfo['filename'];
			    $imgbinary = @file_get_contents($path);
			    
			    $fileName = \Spika\Utils::randString(20, 20) . time();
			    $newFilePath = $fileDir."/".$fileName;
			    copy($path,$newFilePath);
			    
			    // create data
			    $data = array(
			    	'title' => $categoryName,
			    	'avatar_file_id' => $fileName,
			    	'created' => time()
			    );

				try{
				
			        $conn->insert('group_category',$data);
					
				} catch(\Exception $e){
				
					$conn->rollback();	
						
					return $app['twig']->render('installerError.twig', array(
						'ROOT_URL' => ROOT_URL
					));
					
				}
			    
			}
			
			// generate emoticons
			$files = array();
			$filesPath = __DIR__.'/../../../install/resouces/emoticons';
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
				
				// copy to file dir
				$pathinfo = pathinfo($path);
				$emoticonname = $pathinfo['filename'];
			    $imgbinary = @file_get_contents($path);
			    
			    $fileName = \Spika\Utils::randString(20, 20) . time();
			    $newFilePath = $fileDir."/".$fileName;
			    copy($path,$newFilePath);
			    
			    // create data
			    $data = array(
			    	'identifier' => $emoticonname,
			    	'file_id' => $fileName,
			    	'created' => time()
			    );

				try{
				
			        $conn->insert('emoticon',$data);
					
				} catch(\Exception $e){
				
					$conn->rollback();	
						
					return $app['twig']->render('installerError.twig', array(
						'ROOT_URL' => ROOT_URL
					));
					
				}
			    
			}
			
			// create support user
			$password = \Spika\Utils::randString(6, 6);
			$userData = array();
			$userData['name'] = "Support";
			$userData['email'] = "change@spikaapp.com";
			$userData['password'] = md5($password);
			$userData['online_status'] = "online";
			$userData['max_contact_count'] = 100;
			$userData['max_favorite_count'] = 100;
			$userData['birthday'] = 0;
			$userData['created'] = time();

			$conn->insert('user',$userData);			
			$conn->commit();	
				
			return $app['twig']->render('installerStep3.twig', array(
				'ROOT_URL' => ROOT_URL,
				'ConnectionSucceed' => $connectionResult
			));		
			
		});

		// check unique controller
		$controllers->get('/install', function (Request $request) use ($app) {
			
			$randomEmail = \Spika\Utils::randString(8, 8) . '@clover-studio.com';
			$password = \Spika\Utils::randString(8, 8);
			
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
			$resAllStickers = $app['spikadb']->doGetRequest("/_design/app/_view/find_all_emoticons");
			$resAllStickersDic = json_decode($resAllStickers, true);
			
			if(count($resAllStickersDic['rows']) > 0){
    			foreach ($resAllStickersDic['rows'] as $data) {
    			
    			    $id = $data['value']['_id'];
    			    $rev = $data['value']['_rev'];
    			    $app['spikadb']->doDeleteRequest($id,$rev);
    			
    			}
			}

			
			$files = array();
			$filesPath = __DIR__.'/../../../install/resouces/emoticons';
			
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
			
			
			// generate tmp group categories
			// Create support user
			$json = '{
				   "type": "group_category",
				   "title": "tmp_group_category_1"
				}';
			
			$app['spikadb']->doPostRequest($json);

			$json = '{
				   "type": "group_category",
				   "title": "tmp_group_category_2"
				}';
			
			$app['spikadb']->doPostRequest($json);

			
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
