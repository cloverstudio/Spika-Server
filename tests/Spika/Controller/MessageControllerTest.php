<?php
namespace Spika\Controller;

use Silex\Application;
use Silex\WebTestCase;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class MessageControllerTest extends WebTestCase
{

    const FIXTURE_TOKEN   = 'some_token';

    public function createApplication()
    {
        $pimple = new \Pimple;
        
        $dependencies = array(
            'beforeTokenChecker' => $pimple->protect(function () {
            }),
            'currentUser' => array("_id"=>"testid","token"=>"testtoken")
        );
        
        require realpath(__DIR__ . '/../../../') . '/etc/app.php';
 
        $spikadb = $this->getMock('\Spika\Db\DbInterface');
        
        $spikadb->expects($this->any())
            ->method('getEmoticons')
            ->will($this->returnValue(array('rows'=>array())));
                        
        $spikadb->expects($this->any())
            ->method('getEmoticonImage')
            ->will($this->returnValue('OK'));
                        
        $spikadb->expects($this->any())
            ->method('addNewTextMessage')
            ->will($this->returnValue('OK'));
                        
        $spikadb->expects($this->any())
            ->method('getUserMessages')
            ->will($this->returnValue('OK'));
                        
        $app['spikadb'] = $spikadb;
        
        return $app;
    }

    /** @test */
    public function loadEmoticonsTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/Emoticons');
        assertRegExp('/rows/', $client->getResponse()->getContent());
    }
    
    /** @test */
    public function loadEmoticonTest()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/Emoticon/test');
        assertRegExp('/OK/', $client->getResponse()->getContent());
    }


    /** @test */
    public function sendTextMessage()
    {
    
        $client = $this->createClient();
        
        $sendParams = array(
            'to_user_id' => 'test',
            'body' => 'hi',
        );
        
        $crawler = $client->request(
            'POST',
            '/api/sendMessageToUser',
            array(),
            array(),
            array('CONTENT_TYPE' => 'application/json'),
            json_encode($sendParams)
        );

        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
    
    /** @test */
    public function getTextMessage()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/userMessages/test/20/0');

        assertRegExp('/OK/', $client->getResponse()->getContent());
    }
}
