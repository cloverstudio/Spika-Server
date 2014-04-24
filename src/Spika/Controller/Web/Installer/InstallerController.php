<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller\Web\Installer;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\DBAL\DriverManager;
use Spika\Controller\FileController;

class InstallerController implements ControllerProviderInterface
{

    public function curPageURLLocal() {
        $pageURL = "http://localhost".$_SERVER["REQUEST_URI"];
        return $pageURL;
    }
    
    public function curPageURL() {
    
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        
        $pageURL .= "://";
        
        $pageURL .= $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        
        return $pageURL;
    }

    public function connect(Application $app)
    {
        ExceptionHandler::register(false);
        $controllers = $app['controllers_factory'];
        $self = $this;
        
        // first screen
        $controllers->get('/installer', function (Request $request) use ($app,$self) {
            
            $app['monolog']->addDebug("top");
            
            $rootUrl = str_replace("/installer","",$self->curPageURL());
            
            return $app['twig']->render('installer/installerTop.twig', array(
                'ROOT_URL' => $rootUrl
            ));         
        });

        // connect to DB
        $controllers->post('/installer/step1', function (Request $request) use ($app,$self) {
            
            
            
            $app['monolog']->addDebug("step1");
            
            $rootUrl = str_replace("/installer/step1","",$self->curPageURL());
            
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
                $app['monolog']->addDebug("Failed to connect DB");
            }
            
            if($connectionResult){
                return $app['twig']->render('installer/installerStep1.twig', array(
                    'ROOT_URL' => $rootUrl,
                    'ConnectionSucceed' => $connectionResult
                ));         
            }else{
                return $app['twig']->render('installer/installerTop.twig', array(
                    'ROOT_URL' => $rootUrl,
                    'ConnectionSucceed' => $connectionResult
                ));         
            }
        });

        // create database schema
        $controllers->post('/installer/step2', function (Request $request) use ($app,$self) {
            
            $app['monolog']->addDebug("step2");
            
            $rootUrl = str_replace("/installer/step2","",$self->curPageURL());
            
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
                return $app['twig']->render('installer/installerError.twig', array(
                    'ROOT_URL' => $rootUrl
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
                $app['monolog']->addDebug($e->getMessage());
                
                $conn->rollback();      
                return $app['twig']->render('installer/installerError.twig', array(
                    'ROOT_URL' => $rootUrl
                ));
                
            }
            
            return $app['twig']->render('installer/installerStep2.twig', array(
                'ROOT_URL' => $rootUrl,
                'ConnectionSucceed' => $connectionResult
            ));     
        
        });

        // generate initial data
        $controllers->post('/installer/step3', function (Request $request) use ($app,$self) {
            
            $app['monolog']->addDebug("step3");
            
            $rootUrl = str_replace("/installer/step3","",$self->curPageURL());
            $localRootUrl = str_replace("/installer/step3","",$self->curPageURLLocal());
                    
            $config = new \Doctrine\DBAL\Configuration();
            $connectionParams = $app['session']->get('databaseConfiguration');
            
            $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
            
            try{
                $connectionResult = $conn->connect();           
            }catch(\PDOException $e){
                $app['monolog']->addDebug("failed to connect DB" . var_dump($connectionParams));
                $app['monolog']->addDebug($e->getMessage());
                $app->redirect('/installer');
            }
            
            $fileDir = __DIR__.'/../../../../../'.FileController::$fileDirName;
            if(!is_writable($fileDir)){
                $app['monolog']->addDebug("{$fileDir} is not writable.");
                return $app['twig']->render('installer/installerError.twig', array(
                    'ROOT_URL' => $rootUrl
                ));
            }
            
            $conn->beginTransaction();
            
            // generate group categories

            $files = array();
            $filesPath = __DIR__.'/../../../../../install/resouces/categoryimages';
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
                    $app['monolog']->addDebug($e->getMessage());
                    $conn->rollback();  
                        
                    return $app['twig']->render('installer/installerError.twig', array(
                        'ROOT_URL' => $rootUrl
                    ));
                    
                }
                
            }
            
            // generate emoticons
            $files = array();
            $filesPath = __DIR__.'/../../../../../install/resouces/emoticons';
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
                    $app['monolog']->addDebug($e->getMessage());
                    $conn->rollback();  
                        
                    return $app['twig']->render('installer/installerError.twig', array(
                        'ROOT_URL' => $rootUrl,
                    ));
                    
                }
                
            }
            
            // create support user
            $password = 'password';
            $userData = array();
            $userData['name'] = "Administrator";
            $userData['email'] = "admin@spikaapp.com";
            $userData['password'] = md5($password);
            $userData['online_status'] = "online";
            $userData['max_contact_count'] = 100;
            $userData['max_favorite_count'] = 100;
            $userData['birthday'] = 0;
            $userData['created'] = time();

            $conn->insert('user',$userData);            
            $conn->commit();    
                
            return $app['twig']->render('installer/installerStep3.twig', array(
                'ROOT_URL' => $rootUrl,
                'LOCAL_ROOT_URL' => $localRootUrl,
                'ConnectionSucceed' => $connectionResult,
                'DbParams' => $connectionParams,
                'SupportUserId' => $conn->lastInsertId("_id"),
            ));     
            
        });
        
        return $controllers;
    }
    
}
