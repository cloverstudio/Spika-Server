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


class SearchGroupController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

		// Auth controller
		$controllers->get('/searchgroup.php', function (Request $request) use ($app) {
			
			$keyword = $request->get('n');
			
			$query = "";
			
			if(!empty($keyword)){
				
				$startKey = "\"{$keyword}\"";
				$endKey = "\"{$keyword}ZZZZ\"";
				$query = "?startkey={$startKey}&endkey={$endKey}";

			} else {

			}
			
			$resultTmp = $app['spikadb']->doGetRequest("/_design/app/_view/searchgroup_name{$query}");
		    $nameResult = json_decode($resultTmp, true);
		    
		    $result = array();
		    foreach ($nameResult['rows'] as $row) {
		        $result[] = $row['value'];
		    }
		
		    return json_encode($result, true);

		})->before($app['beforeTokenChecker']);
        
        return $controllers;
    }
    
}

?>