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


class SearchUserController implements ControllerProviderInterface
{
	var $app;
	var $name,$ageFrom,$ageTo,$gender;
	
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $this->app = $app;
        $self = $this;
        
		// check unique controller
		$controllers->get('/searchUsers', function (Request $request) use ($app,$self) {
    
            $self->name = $request->get('n');
            $self->ageFrom = $request->get('af');
            $self->ageTo = $request->get('at');
            $self->gender = $request->get('g');
            
            // returns all users if nothing decleard
            if(empty($self->name) && empty($self->ageFrom) && empty($self->ageTo) && empty($self->gender)){
            	return $this->getDefaultResult();
			}
            
            // set default values
            //$this->setDefaultValues();
            $nameResult = $self->searchByName();
            $ageResult = $self->searchByAge();
            $genderResult = $self->searchByGender();
            
            // you can use this result if you want search in "or" criteria
            $resultAll = $self->mergeOrResults($nameResult,$ageResult,$genderResult);
            
            // this is for "and"
            $resultAnd = $self->mergeAndResults($resultAll,$nameResult,$ageResult,$genderResult);
            
            return json_encode($resultAnd);
          
        });
        
        return $controllers;
    }

    public function mergeAndResults(){
    
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
    
        
    public function mergeOrResults(){
    
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
    
    public function searchByName(){
    	
    	if(empty($this->name))
    		return array();
    		
    	$result = $this->app['spikadb']->searchUserByName($this->name);
     	$nameResult = json_decode($result, true);

    	return $nameResult;

    }
    
    public function searchByGender(){
    	
    	if(empty($this->gender))
    		return array();
    		
    	$result = $this->app['spikadb']->searchUserByGender($this->gender);
     	$genderResult = json_decode($result, true);

    	return $genderResult;

    }

    public function searchByAge(){
		
    	$result = $this->app['spikadb']->searchUserByAge($this->ageFrom,$this->ageTo);
    	
     	$ageResult = json_decode($result, true);

    	return $ageResult;
		
    }
    
    public function setDefaultValues(){
	    
	    if(empty($this->ageFrom)){
		    $this->ageFrom = 0;
	    }
	    
	    if(empty($this->ageTo)){
		    $this->ageTo = 100;
	    }
 
    }
    
    public function getDefaultResult(){	    
	    return $this->app['spikadb']->doGetRequest("/_design/app/_view/searchuser_name");
    }
}

?>
