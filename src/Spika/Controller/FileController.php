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


class FileController implements ControllerProviderInterface
{

	static $paramName = 'file';
	static $fileDirName = 'uploads';
	
    public function connect(Application $app)
    {
    	global $beforeTokenCheker;
    	
        $controllers = $app['controllers_factory'];
        
        // ToDo: Add token check
		$controllers->get('/filedownloader.php', function (Request $request) use ($app) {
			
			$fileID = $request->get('file');
			$filePath = __DIR__.'/../'.FileController::$fileDirName."/".$fileID;
			
			if(file_exists($filePath)){
				return $app->sendFile($filePath);
			}else{
				return "";
			}
		});
			
		//})->before($app['beforeTokenChecker']);
        
        // ToDo: Add token check
		$controllers->post('/fileuploader.php', function (Request $request) use ($app) {
			
			$file = $request->files->get(FileController::$paramName); 
			$fineName = \Spika\Utils::randString(20, 20) . time();
			$file->move(__DIR__.'/../'.FileController::$fileDirName, $fineName); 
			return $fineName; 
					
		});
		
		//})->before($app['beforeTokenChecker']);
        
        return $controllers;
    }

}

?>
