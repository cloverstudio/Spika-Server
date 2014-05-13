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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

use google\appengine\api\cloud_storage\CloudStorageTools;

class FileController extends SpikaBaseController
{

    static $paramName = 'file';
    static $fileDirName = 'uploads';
        
    public function connect(Application $app)
    {
        global $beforeTokenCheker;
        
        $controllers = $app['controllers_factory'];
        $self = $this;
        
        // ToDo: Add token check
        $controllers->get('/filedownloader', function (Request $request) use ($app,$self) {
                
            $fileID = $request->get('file');
            $filePath = \Spika\Utils::getGCSPath(FileController::$fileDirName)."/".basename($fileID);
            return $app->redirect(CloudStorageTools::getPublicUrl($filePath,false));
                    
        });
        
        // ToDo: Add token check
        $controllers->post('/fileuploader', function (Request $request) use ($app,$self) {
             
            $fileName = \Spika\Utils::randString(20, 20) . time();
            $tmpName = \Spika\Utils::randString(20, 20) . time();
            
            $tmpFilePath = \Spika\Utils::getGCSPath(FileController::$fileDirName . "/" . $tmpName);
            $filePath = \Spika\Utils::getGCSPath(FileController::$fileDirName . "/" . $fileName);
            
            move_uploaded_file($_FILES['file']['tmp_name'], $tmpFilePath);
            $ctx = stream_context_create(['gs'=>['acl'=>'public-read']]);
            rename($tmpFilePath, $filePath, $ctx);
            
            return $fileName; 
                                
        })->before($app['beforeApiGeneral']);
        
        $controllers->get('/createuploadurl', function (Request $request) use ($app,$self) {
             
            return CloudStorageTools::createUploadUrl(ROOT_URL . '/api/fileuploader',  [ 'gs_bucket_name' => GCS_BUCKET_NAME ]);
            
        })->before($app['beforeApiGeneral']);
        
        return $controllers;
    }

}

?>
