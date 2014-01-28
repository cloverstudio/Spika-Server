<?php

/*
 * This file is part of the Silex framework.
 *
 * Copyright (c) 2013 clover studio official account
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spika\Controller\Web\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Doctrine\DBAL\DriverManager;
use Spika\Controller\Web\SpikaWebBaseController;
use Spika\Controller\FileController;
use Symfony\Component\HttpFoundation\Cookie;

class GroupController extends SpikaWebBaseController
{

    public function connect(Application $app)
    {
		parent::connect($app);
		
        $controllers = $app['controllers_factory'];
		$self = $this;
		
		$controllers->get('group/view/{id}', function (Request $request,$id) use ($app,$self) {
			
			$group = $self->app['spikadb']->findGroupById($id);
			$categoryList = $self->getGroupCategoryList();
			
			$categoryName = $categoryList[$group['category_id']]['title'];
			$group['categoryName'] = $categoryName;
			
			return $self->render('admin/groupForm.twig', array(
				'mode' => 'view',
				'categoryList' => $self->getGroupCategoryList(),
				'formValues' => $group
			));
			
		})->before($app['adminBeforeTokenChecker']);

		$controllers->get('group/edit/{id}', function (Request $request,$id) use ($app,$self) {
			
			$group = $self->app['spikadb']->findGroupById($id);
			$categoryList = $self->getGroupCategoryList();
			
			$categoryName = $categoryList[$group['category_id']]['title'];
			$group['categoryName'] = $categoryName;
			
			return $self->render('admin/groupForm.twig', array(
				'mode' => 'edit',
				'categoryList' => $self->getGroupCategoryList(),
				'formValues' => $group
			));
			
		})->before($app['adminBeforeTokenChecker']);


		$controllers->get('group/list', function (Request $request) use ($app,$self) {
			
			$count = $self->app['spikadb']->findGroupCount();
			
			$page = $request->get('page');
			if(empty($page))
				$page = 1;
			
			$groups = $self->app['spikadb']->findAllGroups(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
			
			// convert timestamp to date
			for($i = 0 ; $i < count($groups['rows']) ; $i++){
				$groups['rows'][$i]['value']['created'] = date("Y.m.d",$groups['rows'][$i]['value']['created']);
				$groups['rows'][$i]['value']['modified'] = date("Y.m.d",$groups['rows'][$i]['value']['modified']);
			}
			
			return $self->render('admin/groupList.twig', array(
				'categoryList' => $self->getGroupCategoryList(),
				'groups' => $groups['rows'],
				'pager' => array(
					'baseURL' => ROOT_URL . "/admin/group/list?page=",
					'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
					'page' => $page,
				),
				
			));
						
		})->before($app['adminBeforeTokenChecker']);

		$controllers->get('group/add', function (Request $request) use ($app,$self) {
			
			return $self->render('admin/groupForm.twig', array(
				'mode' => 'new',
				'categoryList' => $self->getGroupCategoryList(),
				'formValues' => $self->getEmptyFormData(),
			));
						
		})->before($app['adminBeforeTokenChecker']);		
		
		$controllers->post('group/add', function (Request $request) use ($app,$self) {
			
			$validationError = false;
			$fileName = "";
			$thumbFileName = "";
			
			if($request->files->has("file")){
			
				$file = $request->files->get("file");
				
				if($file && $file->isValid()){
				
					$mimeType = $file->getClientMimeType();
					
					if(!preg_match("/jpeg/", $mimeType)){
						$self->setErrorAlert($self->language['messageValidationErrorFormat']);
						$validationError = true;
						
					}else{
											
						$fileName = $self->savePicture($file);
						$thumbFileName = $self->saveThumb($file);
								
					}
				
				}
				
			}
			
			$formValues = $request->request->all();
			
			//validation
			if(empty($formValues['name']) || empty($formValues['category_id']) || empty($formValues['description'])){
				$self->setErrorAlert($self->language['messageValidationErrorRequired']);
				$validationError = true;
			}
			
			// check name is unique
			$check = $self->app['spikadb']->findGroupByName($formValues['name']);
			if(isset($check['_id'])){
				$self->setErrorAlert($self->language['messageValidationErrorNotUnique']);
				$validationError = true;
			}
			
			if(!$validationError){
				
				$password = '';
				if(!empty($formValues['group_password']))
					$password = md5($formValues['group_password']);
					
				$self->app['spikadb']->createGroup(
					$formValues['name'],
					SUPPORT_USER_ID,
					$formValues['category_id'],
					$formValues['description'],
					$password,
					$fileName,
					$thumbFileName
				);
				
				$self->setInfoAlert($self->language['messageGroupAdded']);
				
				$formValues = $self->getEmptyFormData();

			}
			
			return $self->render('admin/groupForm.twig', array(
				'mode' => 'new',
				'categoryList' => $self->getGroupCategoryList(),
				'formValues' => $formValues
			));
						
		})->before($app['adminBeforeTokenChecker']);		
		
        return $controllers;
    }
    
    public function getGroupCategoryList(){
    
	    $result = $this->app['spikadb']->findAllGroupCategory();
	    $list = array();
	    
	    foreach($result['rows'] as $row){
		    $list[$row['value']['_id']] = $row['value'];
	    }
	    
	    return $list;
    }
    
    public function getEmptyFormData(){
		return  array(
					'category_id'=>'',
					'name'=>'',
					'group_password'=>'',
					'description'=>'',					
				);
    }
    
}