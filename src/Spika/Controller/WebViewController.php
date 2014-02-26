<?php
/**
 * Created by IntelliJ IDEA.
 * User: dinko
 * Date: 10/22/13
 * Time: 2:45 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Spika\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class WebViewController extends SpikaBaseController
{
	var $language = array();

	public function __construct(){
	
		$currentLanguage = "en";
		if(defined("DEFAULT_LANGUAGE")){
			$currentLanguage = DEFAULT_LANGUAGE;
		}
	
		$languageFile = __DIR__."/../../../config/i18n/{$currentLanguage}.ini";
	
		$this->language = parse_ini_file($languageFile);
	
	}
	
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;

        //
        // Webview logic for devices
        //
        
        $controllers->get('webview/news/list', function (Request $request) use ($app,$self) {
        
        	$count = $app['spikadb']->findStoriesCount();
        	
        	$page = $request->get('page');
        	if(empty($page))
        		$page = 1;

        	$news = $app['spikadb']->findAllNews(($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
        
        	// convert timestamp to date and use first 100 character for list off news
        	for($i = 0 ; $i < count($news) ; $i++){
        		$news[$i]['created'] = date("Y.m.d",$news[$i]['created']);
        		$news[$i]['modified'] = date("Y.m.d",$news[$i]['modified']);
        		if(strlen($news[$i]['content']) > 200){
        			$news[$i]['content'] = substr($news[$i]['content'], 0, 200)."...";
        		}
        	}
        
        	return $app['twig']->render('webview/newsListDevice.twig', array(
        			'news' => $news,
        			'ROOT_URL' => ROOT_URL,
        			'lang' => $this->language,
        			'pager' => array(
        					'baseURL' => ROOT_URL . "/api/webview/news/list?page=",
        					'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
        					'page' => $page,
        			),
        	));
        
        })->before($app['beforeTokenChecker']);
        
        $controllers->get('webview/news/view/{id}', function (Request $request, $id) use ($app,$self) {
        
        	$story = $app['spikadb']->findStoryById($id);
        
        	// convert timestamp to date
        	$story['created'] = date("Y.m.d",$story['created']);
        	$story['modified'] = date("Y.m.d",$story['modified']);
        
        	return $app['twig']->render('webview/newsViewDevice.twig', array(
        			'story' => $story,
        			'ROOT_URL' => ROOT_URL,
        			'lang' => $this->language
        	));
        
        })->before($app['beforeTokenChecker']);
        
        return $controllers;
        
    }
    
}


















