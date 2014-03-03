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
use Spika\Controller\Web\SpikaWebBaseController;

class WebViewController extends SpikaWebBaseController
{
	
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];
        $self = $this;
        
        $self->setUpNewsListMethod($self,$app,$controllers);
        $self->setUpNewsViewMethod($self,$app,$controllers);
        $self->setUpSendCommentForNewsMethod($self,$app,$controllers);

        return $controllers;
        
    }
    
    private function setUpNewsListMethod($self,$app,$controllers){
    
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
        	
        	$lang=$self->language;
        	
        	$user = $app['session']->get('user');
        	$token=$user['token'];
        	
        	return $app['twig']->render('webview/newsListDevice.twig', array(
        			'news' => $news,
        			'token' =>  $token,
        			'ROOT_URL' => ROOT_URL,
        			'lang' => $lang,
        			'pager' => array(
        					'baseURL' => ROOT_URL . "/api/webview/news/list?page=",
        					'pageCount' => ceil($count / ADMIN_LISTCOUNT) - 1,
        					'page' => $page,
        			),
        	));
        
        });
    
    }
    
    private function setUpNewsViewMethod($self,$app,$controllers){
    
    	$controllers->get('webview/news/view/{id}', function (Request $request, $id) use ($app,$self) {
        
        	$story = $app['spikadb']->findStoryById($id);
        	
        	$countComment = $app['spikadb']->getStoryCommentCount($id);
        	        	
        	$page = $request->get('page');
        	if(empty($page))
        		$page = 1;
        	
        	$comments = $app['spikadb']->getCommentsForStory($id,($page-1)*ADMIN_LISTCOUNT,ADMIN_LISTCOUNT);
        	
        	// convert timestamp to date and use first 100 character for list off news
        	for($i = 0 ; $i < count($comments) ; $i++){
        		$comments[$i]['created'] = date("Y.m.d",$comments[$i]['created']);
        		$comments[$i]['avatar'] = $app['spikadb']->getAvatarOfUser($comments[$i]['user_id']);
        	}
        	
        	$messageError='';
        	$error = $request->get('error');
        	if(!empty($error))
        		$messageError = $self->language[$request->get('error')];
        	
        	$messageInfo='';
        	$info = $request->get('info');
        	if(!empty($info))
        		$messageInfo = $self->language[$request->get('info')];
        
        	// convert timestamp to date
        	$story['created'] = date("Y.m.d",$story['created']);
        	$story['modified'] = date("Y.m.d",$story['modified']);
        	
        	$list_page = $request->get('list_page');
        	if(empty($list_page))
        		$list_page = 1;
        
        	return $app['twig']->render('webview/newsViewDevice.twig', array(
        			'story' => $story,
        			'comments' => $comments,
        			'ROOT_URL' => ROOT_URL,
        			'list_page' => $list_page,
        			'lang' => $self->language,
        			'pager' => array(
        					'baseURL' => ROOT_URL . "/api/webview/news/view/".$id."?list_page=".$list_page."&page=",
        					'pageCount' => ceil($countComment / ADMIN_LISTCOUNT) - 1,
        					'page' => $page,
        			),
        			'errorMessage' => $messageError,
        			'infoMessage' => $messageInfo
        	));
        
        });
    
    }
    
    private function setUpSendCommentForNewsMethod($self,$app,$controllers){
    
    	$controllers->post('webview/news/sendComment/storyid/{story_id}', 
    			function (Request $request, $story_id) use ($app,$self) {
    				
    		$loginedUser = $app['session']->get('user');
    
    		$comment=$request->get('comment_text');
    		$user_id=$loginedUser['_id'];
    		$user_name=$loginedUser['name'];
    		
    		$validationError = false;
    		 
    		//validation
    		if(empty($comment)){
    			$validationError = true;
    		}
    		
    		$list_page = $request->get('list_page');
    		if(empty($list_page))
    			$list_page = 1;
    		
    		if(!$validationError){
    		
    			$result = $app['spikadb']->addCommentForStory(
    					$comment,
    					$user_id,
    					$user_name,
    					$story_id
    			);
    			
    			$countComment = ceil($app['spikadb']->getStoryCommentCount($story_id) / ADMIN_LISTCOUNT);
 				
    			return $app->redirect(ROOT_URL . '/api/webview/news/view/'.$story_id.'?info=commentAdded&page='.$countComment.'&list_page='.$list_page);
    		}
    		
    		return $app->redirect(ROOT_URL . '/api/webview/news/view/'.$story_id.'?error=messageValidationErrorRequired&list_page='.$list_page);
    		
    	});
    
    }
    
}


















