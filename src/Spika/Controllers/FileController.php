<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class FileController implements ControllerProviderInterface
{

	var $paramName = 'file';
	var $fileDirName = 'uploads';
	
    public function connect(Application $app)
    {
    	global $beforeTokenCheker;
    	
        $controllers = $app['controllers_factory'];

		$controllers->get('/filedownloader.php', function (Request $request) use ($app) {
			
			$fileID = $request->get('file');
			$filePath = __DIR__.'/../'.$this->fileDirName."/".$fileID;
			
			if(file_exists($filePath)){
				return $app->sendFile($filePath);
			}else{
				return "";
			}
			
		})->before($app['beforeTokenChecker']);
        
		$controllers->post('/fileuploader.php', function (Request $request) use ($app) {
			
			$file = $request->files->get($this->paramName); 
			$fineName = $this->generateRandomString() . time();
			$file->move(__DIR__.'/../'.$this->fileDirName, $fineName); 
			return $fineName; 
					
		})->before($app['beforeTokenChecker']);
        
        return $controllers;
    }
    
    private function generateRandomString($length = 20)
	{
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, strlen($characters) - 1)];
	    }
	    return $randomString;
	}

    
}

?>