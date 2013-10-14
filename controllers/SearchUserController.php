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


class SearchUserController implements ControllerProviderInterface
{
	var $app;
	var $name,$ageFrom,$ageTo,$gender;
	
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $this->app = $app;
        
		// check unique controller
		$controllers->get('/searchuser.php', function (Request $request) use ($app) {
    
            $this->name = $request->get('n');
            $this->ageFrom = $request->get('af');
            $this->ageTo = $request->get('at');
            $this->gender = $request->get('g');
            
            // returns all users if nothing decleard
            if(empty($this->name) && empty($this->ageFrom) && empty($this->ageTo) && empty($this->gender)){
            	return $this->getDefaultResult();
			}
            
            // set default values
            //$this->setDefaultValues();
            $nameResult = $this->searchByName();
            $ageResult = $this->searchByAge();
            $genderResult = $this->searchByGender();
            
            // you can use this result if you want search in "or" criteria
            $resultAll = $this->mergeOrResults($nameResult,$ageResult,$genderResult);
            
            // this is for "and"
            $resultAnd = $this->mergeAndResults($resultAll,$nameResult,$ageResult,$genderResult);
            
            return json_encode($resultAnd);
            
		});
        
        return $controllers;
    }

    private function mergeAndResults(){
    
    	$results = func_get_args();
    	
    	$andAry = array();
    	
    	$resultAll = $results[0];
    	unset($results[0]);
    	
    	foreach($resultAll as $userId => $row){
	    	
	    	$findInAllArr = true;
	    	
	    	foreach($results as $result){
		    	
		    	$findInArr = false;
		    	
		    	if(!isset($result['rows'])){
			    	continue;
		    	}
		    		
		    	foreach($result['rows'] as $rows){
			    	
			    	if($rows['value']['_id'] == $userId)
			    		$findInArr = true;
			    	
		    	}
	    		
	    		$findInAllArr = $findInAllArr & $findInArr;
	    		
	    	}
	    	
	    	if($findInAllArr)
	    		$andAry[] = $row;
	    		
    	}

	    return $andAry;
    }
    
        
    private function mergeOrResults(){
    
    	$results = func_get_args();
    	
    	$uniqueAry = array();
    	
	    foreach($results as $result){
	    	
	    	if(!isset($result['rows']))
	    		continue;
	    		
	    	foreach($result['rows'] as $rows){
		    	
		    	$uniqueAry[$rows['value']['_id']] = $rows['value'];
		    	
	    	}
	    
	    }
	    
	    return $uniqueAry;
    }
    
    private function searchByName(){
    	
    	if(empty($this->name))
    		return array();
    		
    	$escapedKeyword = urlencode($this->name);
	    $startKey = "\"{$escapedKeyword}\"";
	    $endKey = "\"{$escapedKeyword}ZZZZ\"";
	    $query = "?startkey={$startKey}&endkey={$endKey}";
    	
    	$result = $this->app['spikadb']->doGetRequest("/_design/app/_view/searchuser_name{$query}");
    	
     	$nameResult = json_decode($result, true);

    	return $nameResult;

    }
    
    private function searchByGender(){
    	
    	if(empty($this->gender))
    		return array();
    		
    	$query = "?key=\"{$this->gender}\"";
    	
    	$result = $this->app['spikadb']->doGetRequest("/_design/app/_view/searchuser_gender{$query}");
   
    	$genderResult = json_decode($result, true);

    	return $genderResult;

    }

    private function searchByAge(){

		$ageQuery = "";
		
		if (empty($this->ageFrom) && empty($this->ageTo)){
			return array();
		}
		
		if (!empty($this->ageFrom) && !empty($this->ageTo)) {
		    $ageQuery = "?startkey={$this->ageFrom}&endkey={$this->ageTo}";
		}
		
		if (!empty($this->ageFrom) && empty($this->ageTo)) {
		    $ageQuery = "?startkey={$this->ageFrom}";
		}
		
		if (empty($this->ageFrom) && !empty($this->ageTo)) {
		    $ageQuery = "?endkey={$this->ageTo}";
		}
		
		$result = $this->app['spikadb']->doGetRequest("/_design/app/_view/searchuser_age{$ageQuery}");
		$ageResult = json_decode($result, true);
			
		return $ageResult;
		
    }
    
    private function setDefaultValues(){
	    
	    if(empty($this->ageFrom)){
		    $this->ageFrom = 0;
	    }
	    
	    if(empty($this->ageTo)){
		    $this->ageTo = 100;
	    }
 
    }
    
    private function getDefaultResult(){	    
	    return $this->app['spikadb']->doGetRequest("/_design/app/_view/searchuser_name");
    }
}

?>